<?php
/**
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Multiple_Email_Recipients\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Setup_Wizard\Api;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Util\Settings,
	Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Setup_Wizard\Step,
	Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Setup_Wizard\Util as Wizard_Util;

class Emails extends Step {

	/**
	 * Configure the step.
	 */
	public function __construct() {
		$this->set_id( 'emails' );
		$this->set_name( __( 'Emails', 'woocommerce-multiple-email-recipients' ) );
		$this->set_title( __( 'Customer Emails', 'woocommerce-multiple-email-recipients' ) );
		$this->set_description( __( 'Add extra email addresses for your customers', 'woocommerce-multiple-email-recipients' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {
		$fields = [
			'customer_email_addresses' => [
				'type'  => 'number',
				'label' => __( 'Customer email addresses', 'woocommerce-multiple-email-recipients' ),
				'value' => get_option( 'multiple_email_recipients_customer_email_addresses', 1 ),
			],
			'email_labels'             => [
				'type'                     => 'labels',
				'label'                    => __( 'Additional email labels', 'woocommerce-multiple-email-recipients' ),
				'value'                    => get_option( 'wmer_customer_email_labels', [] ),
				'needs_values'             => true,
				'customer_email_addresses' => [
					'op'    => 'gt',
					'value' => 1,
				],
			],
			'title'                    => [
				'type'       => 'heading',
				'label'      => __( 'Display additional email fields', 'woocommerce-multiple-email-recipients' ),
				'size'       => 'h3',
				'conditions' => [
					'customer_email_addresses' => [
						'op'    => 'gt',
						'value' => 1,
					],
				],
			],
			'account'                  => [
				'type'       => 'checkbox',
				'title'      => __( 'Account page', 'woocommerce-multiple-email-recipients' ),
				'value'      => get_option( 'multiple_email_recipients_display_account', 'yes' ) === 'yes' ? 1 : 0,
				'conditions' => [
					'customer_email_addresses' => [
						'op'    => 'gt',
						'value' => 1,
					],
				],
				'classes'		=> ['barn2-no-bottom-margin']
			],
			'checkout'                 => [
				'type'       => 'checkbox',
				'title'      => __( 'Checkout', 'woocommerce-multiple-email-recipients' ),
				'value'      => get_option( 'multiple_email_recipients_display_checkout', 'yes' ) === 'yes' ? 1 : 0,
				'conditions' => [
					'customer_email_addresses' => [
						'op'    => 'gt',
						'value' => 1,
					],
				],
				'classes'		=> ['barn2-no-bottom-margin']
			],
		];

		return $fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submit( array $values ) {

		update_option( 'multiple_email_recipients_customer_email_addresses', absint( $values['customer_email_addresses'] ) );

		$labels = [];

		if ( is_array( $values['email_labels'] ) ) {
			foreach ( $values['email_labels'] as $index => $label ) {
				$labels[ $index + 1 ] = $label;
			}
		}

		update_option( 'wmer_customer_email_labels', $labels );

		$account  = filter_var( $values['account'], FILTER_VALIDATE_BOOLEAN );
		$checkout = filter_var( $values['checkout'], FILTER_VALIDATE_BOOLEAN );

		if ( $account ) {
			update_option( 'multiple_email_recipients_display_account', 'yes' );
		} else {
			update_option( 'multiple_email_recipients_display_account', 'no' );
		}

		if ( $checkout ) {
			update_option( 'multiple_email_recipients_display_checkout', 'yes' );
		} else {
			update_option( 'multiple_email_recipients_display_checkout', 'no' );
		}

		return Api::send_success_response();
	}

}
