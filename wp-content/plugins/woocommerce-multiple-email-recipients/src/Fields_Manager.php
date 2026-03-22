<?php

namespace Barn2\Plugin\WC_Multiple_Email_Recipients;

/**
 * Manager for multiple email recipients data
 *
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Fields_Manager {

	public function get_emails_count() {
		$count = intval( get_option( 'multiple_email_recipients_customer_email_addresses', 2 ) );

		return apply_filters( 'multiple_email_recipients_additional_emails_count', max( 1, $count ) );
	}

	public function need_to_provide_additional_emails() {
		return $this->get_emails_count() > 1;
	}

	public function is_enabled_for_checkout() {
		$enabled = get_option( 'multiple_email_recipients_display_checkout', 'yes' ) === 'yes';

		return apply_filters( 'multiple_email_recipients_enabled_for_checkout', $enabled );
	}

	public function is_enabled_for_account_page() {
		$enabled = get_option( 'multiple_email_recipients_display_account', 'yes' ) === 'yes';

		return apply_filters( 'multiple_email_recipients_enabled_for_account_page', $enabled );
	}

	public function get_meta_key_for_email( $i ) {
		$meta_key = '_wmep_email_' . $i;

		return apply_filters( 'multiple_email_recipients_email_meta_key', $meta_key, $i );
	}

	public function get_meta_label_for_email( $i ) {

		$labels = get_option( 'wmer_customer_email_labels', [] );

		$meta_label = esc_html__( 'Email address', 'woocommerce-multiple-email-recipients' ) . ' ' . $i;
		if ( ! empty( $labels[ $i - 1 ] ) ) {
			$meta_label = $labels[ $i - 1 ];
		}

		return apply_filters( 'multiple_email_recipients_email_meta_label', $meta_label, $i );
	}

	public function get_additional_emails_from_request( $request = null ) {
		$emails = [];
		if ( empty( $request ) ) {
			$request = $_REQUEST;
		}

		for ( $i = 2; $i <= $this->get_emails_count(); $i ++ ) {
			$key = $this->get_meta_key_for_email( $i );
			if ( empty( $request[ $key ] ) ) {
				continue;
			}
			$email = sanitize_email( $request[ $key ] );
			if ( ! empty( $email ) ) {
				$emails[ $key ] = $email;
			}
		}

		$emails = array_filter( $emails );

		return apply_filters( 'multiple_email_recipients_additional_emails', $emails, null );
	}

	public function get_additional_emails_for_order( $order_id ) {
		$emails = [];
		$order = wc_get_order( $order_id );
		
		for ( $i = 2; $i <= $this->get_emails_count(); $i ++ ) {
			$key = $this->get_meta_key_for_email( $i );
			
			$emails[ $key ] = $order->get_meta( $key, true );
		}

		$emails = array_filter( $emails );

		return apply_filters( 'multiple_email_recipients_additional_order_emails', $emails, $order_id );
	}

	public function update_additional_emails_for_order( $order_id ) {
		$emails      = [];
		$order       = wc_get_order( $order_id );
		$customer_id = $order->get_customer_id();

		for ( $i = 2; $i <= $this->get_emails_count(); $i ++ ) {
			$key = $this->get_meta_key_for_email( $i );

			$emails[ $key ] = get_user_meta( $customer_id, $key, true );

			if ( $emails[ $key ] ) {
				$order->update_meta_data( $key, $emails[ $key ] );
			} else {
				$order->delete_meta_data( $key );
			}
		}

		$order->save();

		$emails = array_filter( $emails );

		return apply_filters( 'multiple_email_recipients_additional_order_emails', $emails, $order_id );
	}

	public function get_additional_emails_for_customer( $customer_id ) {
		$emails = [];

		for ( $i = 2; $i <= $this->get_emails_count(); $i ++ ) {
			$key = $this->get_meta_key_for_email( $i );

			$emails[ $key ] = get_user_meta( $customer_id, $key, true );
		}

		$emails = array_filter( $emails );

		return apply_filters( 'multiple_email_recipients_additional_customer_emails', $emails, $customer_id );
	}

}
