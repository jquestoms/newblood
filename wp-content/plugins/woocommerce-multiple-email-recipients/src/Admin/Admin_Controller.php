<?php

namespace Barn2\Plugin\WC_Multiple_Email_Recipients\Admin;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Registerable,
	Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Service\Standard_Service,
	Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Service\Service_Container,
	Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\Licensed_Plugin,
	Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\Admin\Admin_Links,
	Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Setup_Wizard;

/**
 * General admin functions for WooCommerce Multiple Email Recipients.
 *
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Admin_Controller implements Registerable, Standard_Service {

	use Service_Container;

	private $plugin;

	/**
	 * @var Admin_Links
	 */
	private $admin_links;

	/**
	 * Constructor.
	 *
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;

		$this->add_services();
	}

	public function add_services() {
		$this->add_service( 'admin_links', new Admin_Links( $this->plugin ) );
		$this->add_service( 'settings_page', new Settings_Page( $this->plugin ) );
	}

	public function register() {
		$this->register_services();
		$this->start_all_services();
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'wp_ajax_wmer_update_order_emails', [ $this, 'update_order_emails' ] );
		add_action( 'wp_ajax_nopriv_wmer_update_order_emails', [ $this, 'update_order_emails' ] );
	}

	public function admin_enqueue_scripts( $hook ) {
		global $current_tab, $current_section;

		$script_handle = '';
		$script_path   = '';
		$script_params = [];

		if ( 'products' === $current_tab && 'multiple-email-recipients' === $current_section ) {
			$script_handle = 'wmer_settings';
			$script_path   = '/assets/js/admin/wmer-settings.js';
			$script_params = [
				'i18n' => [
					'email_label_placeholder' => __( 'Email address', 'woocommerce-multiple-email-recipients' ),
					'warning_reduce_emails'   => __( 'Warning! If you reduce the number of additional email address fields then any customer data stored in these fields will become inactive. Notifications will only be sent to the remaining email addresses.', 'woocommerce-multiple-email-recipients' ),
				]
			];
		}

		if ( 'email' === $current_tab && $current_section ) {
			$script_handle = 'wmer_settings';
			$script_path   = '/assets/js/admin/email-settings.js';
			$script_params = [
				'i18n'   => [
					'warning_remove_recipient' => __( 'Are you sure you want to remove these additional recipients?', 'woocommerce-multiple-email-recipients' ),
					'add_recipient'            => __( 'Add recipient', 'woocommerce-multiple-email-recipients' ),
					'recipient_tip'            => __( 'Enter additional recipients (comma separated) for this email, and optionally select which products and/or categories they will receive it for.', 'woocommerce-multiple-email-recipients' ),
				],
				'emails' => $this->get_email_list(),
			];

			wp_enqueue_style( 'wmer_email_style', $this->plugin->get_dir_url() . '/assets/css/admin/email-settings.css', [], $this->plugin->get_version() );
		}

		if ( $script_handle && $script_path ) {
			wp_enqueue_script( $script_handle, $this->plugin->get_dir_url() . $script_path, [ 'jquery' ], $this->plugin->get_version(), true );
		}

		if ( ! empty( $script_params ) ) {
			wp_add_inline_script(
				$script_handle,
				sprintf( 'var wmer_params = %s;', wp_json_encode( $script_params ) ),
				'before'
			);
		}

		$screen = get_current_screen();

		if ( $screen->post_type === 'shop_order' ) {
			wp_enqueue_script( 'wmer-meta-boxes-order', $this->plugin->get_dir_url() . 'assets/js/admin/meta-boxes-order.js', [ 'jquery' ], $this->plugin->get_version(), true );
		}

	}

	private function get_email_list() {
		$emails_service = $this->plugin->get_service( 'emails_service' );

		if ( is_null( $emails_service ) ) {
			return [];
		}

		$emails = $emails_service->get_emails();

		return array_combine(
			array_map( 'strtolower', array_keys( $emails ) ),
			array_map(
				function( $v ) {
					return [
						'id'          => $v->plugin_id . $v->id,
						'is_customer' => $v->is_customer_email()
					];
				},
				$emails
			)
		);
	}

	public function update_order_emails() {
		$order_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $order_id ) {
			wp_send_json_error( __( 'Invalid order ID', 'woocommerce-multiple-email-recipients' ) );
		}

		$order = wc_get_order( $order_id );
		$fields_manager = $this->plugin->get_fields_manager();
		$order_emails   = $fields_manager->get_additional_emails_for_order( $order_id );

		$fields_manager->update_additional_emails_for_order( $order_id );

		wp_send_json_success(
			[
				'order_emails' => $order_emails,
				'user_emails'  => $fields_manager->get_additional_emails_for_customer( $order->get_meta( '_customer_user', true ) )
			]
		);
	}
}
