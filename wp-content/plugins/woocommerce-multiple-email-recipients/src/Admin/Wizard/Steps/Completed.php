<?php
/**
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Setup_Wizard\Steps\Ready;

class Completed extends Ready {

	public function __construct() {
		parent::__construct();
		$this->set_name( esc_html__( 'Ready', 'woocommerce-multiple-email-recipients' ) );
		$this->set_title( esc_html__( 'Complete Setup', 'woocommerce-multiple-email-recipients' ) );
		$this->set_description(
			sprintf(
				'<p>%s</p><p>%s</p>',
				__( 'Congratulations, you can now start using the plugin!', 'woocommerce-multiple-email-recipients' ),
				__( 'The next step is to choose which customer emails to send to the additional recipients; and to add extra recipients for admin-related emails.', 'woocommerce-multiple-email-recipients' )
			)
		);
	}

}
