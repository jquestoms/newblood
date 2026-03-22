<?php

namespace Barn2\Plugin\WC_Multiple_Email_Recipients;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Setup_Wizard;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Admin\Notices;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\Premium_Plugin;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Util as Lib_Util;


/**
 * The main plugin class. Responsible for setting up to core plugin services.
 *
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin extends Premium_Plugin {

	const NAME    = 'WooCommerce Multiple Email Recipients';
	const ITEM_ID = 220192;

	/**
	 * @var Fields_Manager
	 */
	private $fields_manager;

	/**
	 * Constructs and initializes the main plugin class.
	 *
	 * @param string $file The main plugin file.
	 * @param string $version The current plugin version.
	 */
	public function __construct( $file = null, $version = '1.0' ) {
		parent::__construct(
			[
				'name'               => self::NAME,
				'item_id'            => self::ITEM_ID,
				'version'            => $version,
				'file'               => $file,
				'is_woocommerce'     => true,
				'settings_path'      => 'admin.php?page=wc-settings&tab=products&section=multiple-email-recipients',
				'documentation_path' => '/kb-categories/multiple-email-recipients-kb/',
			]
		);

		$this->fields_manager = new Fields_Manager();
	}

	/**
	 * @return Fields_Manager
	 */
	public function get_fields_manager() {
		return $this->fields_manager;
	}

	public function add_services() {
		$this->add_service( 'plugin_setup', new Plugin_Setup( $this->get_file(), $this ) );
		$this->add_service( 'admin', new Admin\Admin_Controller( $this, $this->get_license_setting() ) );
		$this->add_service( 'wizard', new Setup_Wizard( $this ) );
		
		$this->add_service( 'fields_service', new Fields_Service( $this ) );
		$this->add_service( 'emails_service', new Emails_Service( $this ) );
	}

	private function add_missing_woocommerce_notice() {
		if ( Lib_Util::is_admin() ) {
			$admin_notice = new Notices();
			$admin_notice->add(
				'wps_woocommerce_missing',
				'',
				\sprintf( __( 'Please %1$sinstall WooCommerce%2$s in order to use WooCommerce Multiple Email Recipients.', 'woocommerce-multiple-email-recipients' ), Lib_Util::format_link_open( 'https://woocommerce.com/', true ), '</a>' ),
				[
					'type'       => 'error',
					'capability' => 'install_plugins'
				]
			);
			$admin_notice->boot();
		}
	}

	public function add_view_details_link( $links, $file ) {

		if ( $file !== $this->get_basename() ) {
			return $links;
		}

		$view_details_url = 'https://barn2.com/wordpress-plugins/woocommerce-multiple-email--recipients';

		\array_push( $links, \sprintf( '<a href="%1$s" target="_blank">%2$s</a>', \esc_url( $view_details_url ), __( 'View details', 'woocommerce-multiple-email-recipients' ) ) );

		return $links;
	}

}
