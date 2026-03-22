<?php

namespace Nexcess\MAPPS\Commands;

use Nexcess\MAPPS\Concerns\HasWordPressDependencies;
use Nexcess\MAPPS\Plugins\ObjectCachePro as Plugin;
use Nexcess\MAPPS\Services\Installer;
use Nexcess\MAPPS\Services\Logger;
use Nexcess\MAPPS\Settings;
use StellarWP\PluginFramework\Exceptions\InstallationException;
use StellarWP\PluginFramework\Exceptions\LicensingException;

/**
 * WP-CLI sub-commands for integrating with Object Cache Pro.
 */
class ObjectCachePro extends Command {
	use HasWordPressDependencies;

	/**
	 * @var \Nexcess\MAPPS\Services\Logger
	 */
	protected $logger;

	/**
	 * @var \Nexcess\MAPPS\Plugins\ObjectCachePro
	 */
	private $plugin;

	/**
	 * @var \Nexcess\MAPPS\Settings
	 */
	private $settings;

	/**
	 * Create a new command instance.
	 *
	 * @param Settings $settings
	 * @param Plugin   $plugin
	 * @param Logger   $logger
	 */
	public function __construct( Settings $settings, Plugin $plugin, Logger $logger ) {
		$this->plugin   = $plugin;
		$this->logger   = $logger;
		$this->settings = $settings;
	}

	/**
	 * Run the migration from Redis Cache to Object Cache Pro.
	 *
	 * @when before_wp_load
	 *
	 * ## EXAMPLES
	 *
	 * $ wp nxmapps object-cache-pro migrate
	 * Success: Redis Cache migration complete.
	 */
	public function migrate() {

		// Bail if OCP is already installed.
		if ( $this->isPluginActive( 'object-cache-pro/object-cache-pro.php' ) ) {
			$this->error( __( 'Object Cache Pro already installed.', 'nexcess-mapps' ) );
		}

		// Bail if Redis Cache isn't installed.
		if ( ! $this->isPluginActive( 'redis-cache/redis-cache.php' ) ) {
			$this->error( __( 'Redis Cache not installed - nothing to migrate.', 'nexcess-mapps' ) );
		}

		$this->step( __( 'Deactivating Redis Cache', 'nexcess-mapps' ) );

		// Remove Redis Cache.
		$this->deactivatePlugin( 'redis-cache/redis-cache.php' );

		// Install and activate OCP.
		$this->step( __( 'Installing Object Cache Pro', 'nexcess-mapps' ) );
		$installer    = new Installer( $this->settings, $this->logger );
		$installer_id = $this->settings->is_qa_environment ? 80 : 125;

		try {
			$installer->install( $installer_id );
		} catch ( InstallationException $e ) {
			$this->logger->info( sprintf(
				/* Translators: %1$s is the previous exception message. */
				__( 'Unable to install Object Cache Pro: %1$s', 'nexcess-mapps' ),
				$e->getMessage()
			) );
			$this->error( __( 'Unable to install Object Cache Pro.', 'nexcess-mapps' ) );
		}

		$this->step( __( 'Licensing Object Cache Pro', 'nexcess-mapps' ) );

		try {
			$installer->license( $installer_id );
		} catch ( LicensingException $e ) {
			$this->logger->info( sprintf(
				/* Translators: %1$s is the previous exception message. */
				__( 'Unable to license Object Cache Pro: %1$s', 'nexcess-mapps' ),
				$e->getMessage()
			) );
			$this->error( __( 'Unable to install Object Cache Pro.', 'nexcess-mapps' ) );
		}

		$this->success( __( 'Object Cache Pro installed successfully.', 'nexcess-mapps' ) );
		$this->confirm( __( 'Do you want to delete the free version of Redis Cache?', 'nexcess-mapps' ) );
		$this->wp( 'plugin delete redis-cache' );
		$this->success( __( 'Redis Cache deleted.', 'nexcess-mapps' ) );
	}

	/**
	 * Activate the Object Cache Pro License.
	 *
	 * ## OPTIONS
	 *
	 * <license>
	 * : License to activate
	 *
	 * ## EXAMPLES
	 *
	 * $ wp nxmapps object-cache-pro activate abcdefghijklm1234567890
	 * Success: Activated Object Cache Pro License.
	 *
	 * @param string[] $args Top-level arguments.
	 */
	public function activate( $args ) {

		// OCP uses object-cache-pro.php as the entry file in older versions, so check for both variations.
		if ( ! is_plugin_active( 'object-cache-pro/object-cache-pro.php' ) ) {
			$this->error( __( 'Object Cache Pro must be activated first.', 'nexcess-mapps' ) );
		}

		list( $license_key ) = $args;

		update_option( 'object_cache_pro_license', sanitize_text_field( $license_key ) );
		$this->line( __( 'Object Cache Pro license stored. ✅', 'nexcess-mapps' ) );

		$this->line( __( 'Setting configuration in wp-config.php....', 'nexcess-mapps' ) );
		$wrote = $this->plugin->writeConfig();

		if ( $wrote ) {
			if ( 60 === strlen( (string) $license_key ) ) {
				$this->line( __( 'Installing Object Cache Pro drop-in....', 'nexcess-mapps' ) );
				$this->wp( 'redis enable' );
			}
			$this->success( __( 'You are all set to begin using Object Cache Pro! 🎉', 'nexcess-mapps' ) );
		} else {
			$this->warning( __( ' There was an issue writing the Object Cache Pro configuration to your wp-config.php file; it will need adjusted manually. Please ensure the following constants are added to your wp-config.php:', 'nexcess-mapps' ) );
			$this->newline();
			$this->line( "define( 'WP_REDIS_CONFIG', " . $this->plugin->getRedisConfig() . " );
define( 'WP_REDIS_DISABLED', false );" );
			$this->newline();
		}
	}
}
