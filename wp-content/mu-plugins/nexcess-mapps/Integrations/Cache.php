<?php

/**
 * Generic cache integration for Nexcess MAPPS.
 *
 * More specific implementations are available:
 *
 * @see Nexcess\MAPPS\Integrations\ObjectCache
 * @see Nexcess\MAPPS\Integrations\PageCache
 */

namespace Nexcess\MAPPS\Integrations;

use Nexcess\MAPPS\Concerns\HasHooks;
use Nexcess\MAPPS\Services\AdminBar;
use Nexcess\MAPPS\Services\MappsApiClient;
use Nexcess\MAPPS\Support\AdminNotice;
use StellarWP\PluginFramework\Exceptions\RequestException;
use WP_Error;

class Cache extends Integration {
	use HasHooks;

	/**
	 * @var \Nexcess\MAPPS\Services\AdminBar
	 */
	protected $adminBar;

	/**
	 * @var \Nexcess\MAPPS\Services\MappsApiClient
	 */
	protected $client;

	/**
	 * @param \Nexcess\MAPPS\Services\AdminBar       $admin_bar
	 * @param \Nexcess\MAPPS\Services\MappsApiClient $client
	 */
	public function __construct( AdminBar $admin_bar, MappsApiClient $client ) {
		$this->adminBar = $admin_bar;
		$this->client   = $client;
	}

	/**
	 * Retrieve all actions for the integration.
	 *
	 * @return array[]
	 */
	protected function getActions() {
		return [
			[ 'muplugins_loaded', [ $this, 'maybeFlushAllCaches' ] ],
			[ 'init', [ $this, 'registerAdminBarMenu' ] ],
			[ 'admin_init', [ $this, 'adminBarFlushCache' ] ],
			[ 'admin_action_nexcess-mapps-flush-cache', [ $this, 'adminBarFlushCache' ] ],
			[ 'admin_post_nexcess-mapps-flush-cache', [ $this, 'adminBarFlushCache' ] ],
		];
	}

	/**
	 * Check for the presence of a .flush-cache file in the web root.
	 *
	 * If present, flush the object cache, then remove the file.
	 *
	 * This handles a case when a migration is executed which directly manipulates the database and
	 * filesystem. This can sometimes leave the cache in a state where it's still populated with
	 * the original theme, plugins, and site options, causing a broken site experience.
	 */
	public function maybeFlushAllCaches() {
		$filepath = ABSPATH . '.flush-cache';

		// No file means there's nothing to do.
		if ( ! file_exists( $filepath ) ) {
			return;
		}

		// Only remove the file if all relevant caches were flushed successfully.
		if ( wp_cache_flush() ) {
			wp_delete_file( $filepath );
		}
	}

	/**
	 * Register the admin bar menu item.
	 */
	public function registerAdminBarMenu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->adminBar->addMenu(
			'flush-cache',
			AdminBar::getActionPostForm(
				'nexcess-mapps-flush-cache',
				_x( 'Flush Nexcess Edge CDN', 'admin bar menu title', 'nexcess-mapps' )
			)
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$get_purged = isset( $_GET['nexcess-cache-purged'] ) ? sanitize_text_field( $_GET['nexcess-cache-purged'] ) : '';
		if ( $get_purged ) {

			switch ( $get_purged ) {
				case 'success':
					$message = __( 'The cache has been flushed successfully!', 'nexcess-mapps' );
					$type    = 'success';
					break;

				default:
					$message = __( 'CDN cannot be flushed at this time. Please wait 5 minutes and try again.', 'nexcess-mapps' );
					$type    = 'error';
					break;
			}

			if ( ! empty( $message ) ) {
				$this->adminBar->addNotice( new AdminNotice(
					$message,
					$type,
					true
				) );
			}
		}
	}

	/**
	 * Callback for requests to flush the object cache via the Admin Bar.
	 */
	public function adminBarFlushCache() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['action'] ) || 'nexcess-mapps-flush-cache' !== $_POST['action'] ) {
			return;
		}

		if ( ! AdminBar::validateActionNonce( 'nexcess-mapps-flush-cache' ) ) {
			return $this->adminBar->addNotice( new AdminNotice(
				__( 'We were unable to flush the cache, please try again.', 'nexcess-mapps' ),
				'error',
				true
			) );
		}

		try {
			$purged = $this->purge();
		} catch ( RequestException $e ) {
			return $this->adminBar->addNotice( new AdminNotice(
				sprintf( 'Unexpected error attempting to flush the cache: %s', $e->getMessage() ),
				'error',
				true
			) );
		}

		// If we have a referrer, we likely came from the front-end of the site.
		$referrer = wp_get_referer();

		if ( $referrer ) {
			wp_safe_redirect( $referrer );
			exit;
		}

		if ( is_wp_error( $purged ) ) {
			wp_safe_redirect( add_query_arg( 'nexcess-cache-purged', $purged->get_error_code() ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'nexcess-cache-purged', 'success' ) );
		exit;
	}

	/**
	 * Call to purge the caches with the MappsApiClient.
	 *
	 * @throws RequestException Request failed.
	 *
	 * @return MappsApiClient|WP_Error WP_Error when rate limited, else the MappsApiClient.
	 */
	public function purge() {
		try {
			$purged = $this->client->purgeCaches();
		} catch ( RequestException $e ) {
			throw $e;
		}

		return $purged;
	}
}
