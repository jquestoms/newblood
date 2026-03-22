<?php
/**
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Setup_Wizard\Steps\Cross_Selling;

class Upsell extends Cross_Selling {

	public function __construct() {
		parent::__construct();
		$this->set_name( esc_html__( 'More', 'woocommerce-multiple-email-recipients' ) );
		$this->set_description( __( 'Enhance your store with these fantastic plugins from Barn2.', 'woocommerce-multiple-email-recipients' ) );
		$this->set_title( esc_html__( 'Extra features', 'woocommerce-multiple-email-recipients' ) );
	}

}
