<?php

namespace Barn2\Plugin\WC_Multiple_Email_Recipients;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Setup_Wizard\Starter;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\Plugin_Activation_Listener;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Service\Standard_Service;

/**
 * Handles automated redirect to the setup wizard after plugin activation.
 */
class Plugin_Setup implements Plugin_Activation_Listener, Registerable, Standard_Service {

	/**
	 * Plugin's entry file
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Wizard starter.
	 *
	 * @var Starter
	 */
	private $starter;

	/**
	 * Plugin instance
	 *
	 * @var Licensed_Plugin
	 */
	private $plugin;

	/**
	 * Get things started
	 *
	 * @param string $file
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( $file, Licensed_Plugin $plugin ) {
		$this->file    = $file;
		$this->plugin  = $plugin;
		$this->starter = new Starter( $this->plugin );
	}

	/**
	 * Register the service
	 *
	 * @return void
	 */
	public function register() {
		register_activation_hook( $this->file, [ $this, 'on_activate' ] );
		add_action( 'admin_init', [ $this, 'after_plugin_activation' ] );
	}

	/**
	 * On plugin activation determine if the setup wizard should run.
	 *
	 * @return void
	 */
	public function on_activate( $network_wide ) {
		// Network wide.
		// phpcs:disable
		$network_wide = ! empty( $_GET['networkwide'] )
			? (bool) $_GET['networkwide']
			: false;
		// phpcs:enable

		if ( $this->starter->should_start() ) {
			$this->starter->create_transient();
		}
	}

	/**
	 * Do nothing.
	 *
	 * @return void
	 */
	public function on_deactivate( $network_wide ) {}

	/**
	 * Detect the transient and redirect to wizard.
	 *
	 * @return void
	 */
	public function after_plugin_activation() {
		if ( ! $this->starter->detected() ) {
			return;
		}

		$this->starter->delete_transient();
		$this->starter->redirect();
	}

}
