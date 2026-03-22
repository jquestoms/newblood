<?php

/**
 * Perform regular maintenance.
 */

namespace Nexcess\MAPPS\Integrations;

use Nexcess\MAPPS\Concerns\HasCronEvents;
use Nexcess\MAPPS\Services\DropIn;
use Nexcess\MAPPS\Services\MigrationCleaner;
use Nexcess\MAPPS\Settings;
use StellarWP\PluginFramework\Services\FeatureFlags;

class Maintenance extends Integration {
	use HasCronEvents;

	/**
	 * @var \Nexcess\MAPPS\Services\DropIn
	 */
	protected $dropIn;

	/**
	 * @var \StellarWP\PluginFramework\Services\FeatureFlags
	 */
	protected $flags;

	/**
	 * @var \Nexcess\MAPPS\Services\MigrationCleaner
	 */
	protected $migrationCleaner;

	/**
	 * @var \Nexcess\MAPPS\Settings
	 */
	protected $settings;

	/**
	 * Unwanted option names.
	 *
	 * @var array<string>
	 */
	private $unwantedCrons = [
		'nexcess_mapps_daily_w3tc_plugin_migration',
		'nexcess_mapps_daily_ocp_plugin_migration',
	];

	/**
	 * Unwanted option names.
	 *
	 * @var array<string>
	 */
	private $unwantedOptions = [
		'nexcess_did_migrate_cache_enabler_to_w3tc',
		'nexcess_did_migrate_redis_to_ocp',
		'nexcess_wp_6_4_patched',
	];

	/**
	 * @var \Nexcess\MAPPS\Integrations\WooCommerceAutomatedTesting
	 */
	protected $wcat;

	/**
	 * The daily cron action name.
	 */
	const DAILY_MAINTENANCE_CRON_ACTION = 'nexcess_mapps_daily_maintenance';

	/**
	 * The weekly cron action name.
	 */
	const WEEKLY_MAINTENANCE_CRON_ACTION = 'nexcess_mapps_weekly_maintenance';

	/**
	 * @param \Nexcess\MAPPS\Services\DropIn                          $drop_in
	 * @param \Nexcess\MAPPS\Services\MigrationCleaner                $cleaner
	 * @param \Nexcess\MAPPS\Integrations\WooCommerceAutomatedTesting $wcat
	 * @param \StellarWP\PluginFramework\Services\FeatureFlags        $flags
	 * @param \Nexcess\MAPPS\Settings                                 $settings
	 */
	public function __construct(
		DropIn $drop_in,
		MigrationCleaner $cleaner,
		WooCommerceAutomatedTesting $wcat,
		FeatureFlags $flags,
		Settings $settings
	) {
		$this->dropIn           = $drop_in;
		$this->migrationCleaner = $cleaner;
		$this->wcat             = $wcat;
		$this->flags            = $flags;
		$this->settings         = $settings;
	}

	/**
	 * Perform any necessary setup for the integration.
	 *
	 * This method is automatically called as part of Plugin::loadIntegration(), and is the
	 * entry-point for all integrations.
	 */
	public function setup() {
		$this->registerCronEvent( self::DAILY_MAINTENANCE_CRON_ACTION, 'daily' );
		$this->registerCronEvent( self::WEEKLY_MAINTENANCE_CRON_ACTION, 'weekly' );
		$this->addHooks();
	}

	/**
	 * Retrieve all actions for the integration.
	 *
	 * @return array[]
	 */
	protected function getActions() {
		return [

			/*
			 * Daily operations:
			 *
			 * - Set up any features that have been hidden behind feature flags.
			 * - Run the DropIn::cleanBrokenDropIns() method.
			 */
			[ self::DAILY_MAINTENANCE_CRON_ACTION, [ $this, 'enableFlaggedFeatures' ] ],
			[ self::DAILY_MAINTENANCE_CRON_ACTION, [ $this->dropIn, 'cleanBrokenDropIns' ] ],

			/*
			 * Weekly operations:
			 *
			 * - Run the migration cleaner.
			 * - Remove unwanted options.
			 * - Remove unwanted crons.
			 * - PHP version warnings.
			 */
			[ self::WEEKLY_MAINTENANCE_CRON_ACTION, [ $this, 'removeOptionsAndCrons' ] ],
			[ self::WEEKLY_MAINTENANCE_CRON_ACTION, [ $this, 'cleanCache' ] ],
			[ self::WEEKLY_MAINTENANCE_CRON_ACTION, [ $this->migrationCleaner, 'clean' ] ],
		];
	}

	/**
	 * Activate features that are currently locked behind feature flags.
	 *
	 * Once a day, loop through known feature flags that require some activation step and, if the
	 * site is eligible but not yet connected, activate it.
	 */
	public function enableFlaggedFeatures() {
		// WooCommerce Automated Testing.
		if (
			$this->wcat->eligibleForAutomatedTesting()
			&& ! $this->wcat->registered()
			&& $this->flags->enabled( 'woocommerce-automated-testing' )
		) {
			$this->wcat->registerSite();
		}
	}

	/**
	 * Remove unwanted cron events, and options from the database.
	 */
	public function removeOptionsAndCrons() {

		// Remove cron events.
		$crons = get_option( 'cron' );
		if ( ! empty( $crons ) ) {
			foreach ( $crons as $timestamp => $array ) {
				if ( is_array( $array ) ) {
					foreach ( $array as $hook => $hash ) {
						if ( in_array( $hook, $this->unwantedCrons, true ) ) {
							foreach ( $hash as $data ) {
								wp_clear_scheduled_hook( $hook, $data['args'] );
							}
						}
					}
				}
			}
		}

		// Remove options.
		if ( ! empty( $this->unwantedOptions ) ) {
			foreach ( $this->unwantedOptions as $option ) {
				delete_option( $option );
			}
		}
	}

	/**
	 * Clean the wp-content/cache folder when it's over 5GB.
	 */
	public function cleanCache() {
		if ( file_exists( WP_CONTENT_DIR . '/cache/' ) && $this->dirSize( WP_CONTENT_DIR . '/cache', 'g' ) >= 5 ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
			shell_exec( 'rm -rf ' . WP_CONTENT_DIR . '/cache/' );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
			shell_exec( 'mkdir ' . WP_CONTENT_DIR . '/cache/' );
		}
	}

	/**
	 * Scan the files in a given directory.
	 *
	 * @param string $dir Path to directory.
	 *
	 * @return array Directory files.
	 */
	private function scanDir( $dir ) {
		return (array) glob( rtrim( $dir, '/' ) . '/*', GLOB_NOSORT );
	}

	/**
	 * Get the size of a directory.
	 *
	 * @param string $dir  Path to directory.
	 * @param string $unit Filesize unit (k = kiolbytes; m = megabytes; g = gigabytes; default = bytes).
	 *
	 * @return int Size of directory
	 */
	private function dirSize( $dir, $unit = '' ) {
		$size  = 0;
		$files = $this->scanDir( $dir );

		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				$size += is_file( $file ) ? filesize( $file ) : $this->dirSize( $file );
			}
		}

		switch ( $unit ) {

			case 'g':
				$size = $size / ( pow( 1024, 3 ) );
				break;

			case 'm':
				$size = $size / ( pow( 1024, 2 ) );
				break;

			case 'k':
				$size = $size / 1024;
				break;

			default:
				break;
		}

		return $size;
	}
}
