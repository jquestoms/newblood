<?php

namespace Barn2\Plugin\WC_Multiple_Email_Recipients\Util;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Util as Lib_Util;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Admin\Settings_Util;

/**
 * Utility functions for the product table plugin settings.
 *
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Settings {

	const SECTION_SLUG = 'multiple-email-recipients';

	public static function get_settings( $plugin ) {
		$license_setting = $plugin->get_license_setting();

		return [
			[
				'id'    => 'multiple_email_recipients_settings_start',
				'type'  => 'settings_start',
				'class' => 'barn2-plugins-settings'
			],
			[
				'name' => __( 'Multiple email recipients', 'woocommerce-multiple-email-recipients' ),
				'type' => 'title',
				'desc' => '<p>' . __( 'The following options control the WooCommerce Multiple Email Recipients extension.', 'woocommerce-multiple-email-recipients' ) . '</p>' .
							'<p>' .
							Settings_Util::get_help_links( $plugin ) .
							'</p>',
				'id'   => 'multiple_email_recipients_options'
			],
			$license_setting->get_license_key_setting(),
			$license_setting->get_license_override_setting(),
			[
				'name'                => __( 'Customer email addresses', 'woocommerce-multiple-email-recipients' ),
				'type'                => 'customer_email_addresses',
				'description'         => __( 'The number of email addresses to allow per customer.', 'woocommerce-multiple-email-recipients' ),
				'tooltip_description' => __( 'Choose how many customer email addresses to allow for each user. This number should include the email address field that is provided by WooCommerce itself, plus any additional email address fields that you wish to add.', 'woocommerce-multiple-email-recipients' ),
				'id'                  => 'multiple_email_recipients_customer_email_addresses',
				'default'             => 2,
			],
			[
				'title'         => __( 'Display additional email fields', 'woocommerce-multiple-email-recipients' ),
				'desc'          => __( 'Account page', 'woocommerce-multiple-email-recipients' ),
				'id'            => 'multiple_email_recipients_display_account',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
			],
			[
				'desc'          => __( 'Checkout', 'woocommerce-multiple-email-recipients' ),
				'id'            => 'multiple_email_recipients_display_checkout',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
			],
			[
				'type' => 'sectionend',
				'id'   => 'multiple_email_recipients_form'
			],
			[
				'id'   => 'multiple_email_recipients_settings_end',
				'type' => 'settings_end'
			]
		];
	}
}
