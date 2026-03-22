<?php
/**
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Steps\License_Verification;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Steps\Emails;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Steps\Upsell;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Steps\Completed;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Setup_Wizard\Setup_Wizard as Wizard;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Setup_Wizard\Util;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Util\Settings;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\License\EDD_Licensing;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\License\Plugin_License;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Util as Lib_Util;

class Setup_Wizard implements Registerable, Standard_Service {

	private $plugin;

	private $wizard;

	public function __construct( Licensed_Plugin $plugin ) {

		$this->plugin = $plugin;

		$steps = [
			new License_Verification(),
			new Emails(),
			new Upsell(),
			new Completed(),
		];

		$wizard = new Wizard( $this->plugin, $steps );

		$wizard->configure(
			[
				'skip_url'        => admin_url( 'admin.php?page=wc-settings&tab=products&section=multiple-email-recipients' ),
				'license_tooltip' => esc_html__( 'The licence key is contained in your order confirmation email.', 'woocommerce-multiple-email-recipients' ),
				'wc_email_url'    => admin_url( 'admin.php?page=wc-settings&tab=email' ),
				'utm_id'          => 'wmer',
			]
		);

		$wizard->add_edd_api( EDD_Licensing::class );
		$wizard->add_license_class( Plugin_License::class );
		$wizard->add_restart_link( Settings::SECTION_SLUG, 'multiple_email_recipients_options' );

		$wizard->add_custom_asset(
			$plugin->get_dir_url() . 'assets/js/admin/wizard.js',
			Util::get_script_dependencies( $this->plugin, './assets/js/admin/wizard.js' )
		);

		$this->wizard = $wizard;

	}

	public function register() {
		$this->wizard->boot();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_additional_scripts' ), 21 );
	}

	public function enqueue_additional_scripts( $hook_suffix ) {
		if ( 'toplevel_page_' . $this->wizard->get_slug() !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style( 'wme-setup-wizard', $this->plugin->get_dir_url() . 'assets/css/admin/wizard.css', array( $this->wizard->get_slug() ), $this->plugin->get_version() );
	}

}
