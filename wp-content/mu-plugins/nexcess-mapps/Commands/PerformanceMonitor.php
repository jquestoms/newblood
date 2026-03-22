<?php

namespace Nexcess\MAPPS\Commands;

use Nexcess\MAPPS\Integrations\PerformanceMonitor as Integration;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Migration\MigrationHandler\MigrationHandlerBase;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Migration\MigrationManager;
use Nexcess\MAPPS\Settings;
use StellarWP\PluginFramework\Services\FeatureFlags;

/**
 * WP-CLI sub-commands for the Nexcess Performance Monitor.
 */
class PerformanceMonitor extends Command {

	/**
	 * @var FeatureFlags
	 */
	protected $featureFlags;

	/**
	 * @var \Nexcess\MAPPS\Integrations\PerformanceMonitor
	 */
	protected $integration;

	/**
	 * @var Settings
	 */
	protected $settings;

	/**
	 * @param Settings                                       $settings
	 * @param \Nexcess\MAPPS\Integrations\PerformanceMonitor $integration
	 * @param FeatureFlags                                   $flags
	 */
	public function __construct( Settings $settings, Integration $integration, FeatureFlags $flags ) {
		$this->settings     = $settings;
		$this->integration  = $integration;
		$this->featureFlags = $flags;
	}

	/**
	 * Enable the Performance Monitor for this site.
	 */
	public function enable() {
		if ( $this->settings->is_temp_domain ) {
			return $this->error( 'The Performance Monitor is only available on live domains.', 2 );
		}

		if ( ! $this->settings->performance_monitor_endpoint ) {
			return $this->error( 'The performance_monitor_endpoint is not configured, unable to proceed.', 1 );
		}

		if ( $this->integration->getPerformanceMonitorSetting() ) {
			$this->warning( 'The Performance Monitor has been enabled for this site!' );
		} else {
			$this->integration->enablePerformanceMonitor();

			$this->success( 'The Performance Monitor has been enabled for this site!' );
		}
	}

	/**
	 * Disable the Performance Monitor for this site.
	 */
	public function disable() {
		if ( $this->settings->is_temp_domain ) {
			return $this->error( 'The Performance Monitor is only available on live domains.', 2 );
		}

		$this->integration->disablePerformanceMonitor();

		$this->success( 'The Performance Monitor has been disabled for this site!' );
	}

	/**
	 * Show status of Performance Monitor.
	 */
	public function status() {
		if ( $this->integration->getPerformanceMonitorSetting() ) {
			$this->log( 'Performance Monitor Status: Enabled.' );
		} else {
			$this->log( 'Performance Monitor Status: Disabled.' );
		}
	}

	/**
	 * Migrate Performance Monitor data to the latest version if needed.
	 */
	public function migrate() {
		$current_version = $this->integration->getDb()->getDataVersion();
		$target_version  = $this->integration->getDb()->getClientVersion();

		if ( ! $this->integration->isMigrationNeeded() ) {
			$this->log(
				sprintf(
					/* Translators: %d is the version number, e.g. 1 */
					__(
						'[Performance Monitor] Data already migrated to the latest version (v%d)',
						'nexcess-mapps'
					),
					$current_version
				)
			);
			return;
		}

		$this->log(
			sprintf(
				/* Translators: %1$d and %2$d are version numbers, e.g. 1 */
				__(
					'[Performance Monitor] Migrating data from v%1$d to v%2$d',
					'nexcess-mapps'
				),
				$current_version,
				$target_version
			)
		);

		$migration_manager = new MigrationManager( $current_version, $target_version );
		$handler           = $migration_manager->getMigrationHandler( $this->integration );

		if ( ! $handler instanceof MigrationHandlerBase ) {
			$this->warning(
				sprintf(
					/* Translators: %1$d and %2$d are version numbers, e.g. 1 */
					__(
						'[Performance Monitor] Unable to migrate from v%1$d to v%2$d. No migration handler found.',
						'nexcess-mapps'
					),
					$current_version,
					$target_version
				)
			);
			return;
		}

		$this->step( __( 'Migrating data', 'nexcess-mapps' ) );

		$step            = $handler->step();
		$total_steps     = intval( $step['total_steps'] );
		$cleanup_started = false;

		for ( $i = 0; $i < $total_steps; $i++ ) {
			if ( isset( $step['errors'] ) && ! empty( $step['errors'] ) ) {
				if ( is_array( $step['errors'] ) ) {
					foreach ( $step['errors'] as $error ) {
						$this->error( $error );
					}
				} else {
					$this->error( (string) $step['errors'] );
				}
			}

			$this->log(
				sprintf(
					/* Translators: %1$d is the current step integer and %2$d is the total steps integer */
					__(
						'Migration progress: %1$d / %2$d',
						'nexcess-mapps'
					),
					$step['current_step'],
					$total_steps
				)
			);

			$step = $handler->step();

			if ( false === $cleanup_started && true === $step['migration_done'] ) {
				$cleanup_started = true;
				$this->step( __( 'Cleaning up', 'nexcess-mapps' ) );
			}
		}

		if ( isset( $step['done'] ) && true === $step['done'] ) {
			$this->newline();
			$this->success( __( '[Performance Monitor] Migration succeeded!', 'nexcess-mapps' ) );
		} else {
			$this->error( __( '[Performance Monitor] Migration failed, see errors above', 'nexcess-mapps' ) );
		}
	}
}
