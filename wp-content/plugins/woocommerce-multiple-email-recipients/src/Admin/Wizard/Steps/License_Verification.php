<?php
/**
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Setup_Wizard\Steps\Welcome;

class License_Verification extends Welcome {

	public function __construct() {
		parent::__construct();
		$this->set_name( esc_html__( 'Welcome', 'woocommerce-multiple-email-recipients' ) );
		$this->set_title( esc_html__( 'Welcome to WooCommerce Multiple Email Recipients', 'woocommerce-multiple-email-recipients' ) );
		$this->set_description( esc_html__( 'Add extra recipients in minutes', 'woocommerce-multiple-email-recipients' ) );
		$this->set_tooltip( esc_html__( 'Use this setup wizard to quickly configure the plugin’s main options. You can easily change these options later on the plugin settings page or by relaunching the setup wizard.', 'woocommerce-multiple-email-recipients' ) );
	}

}
