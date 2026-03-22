<?php namespace Barn2\Plugin\WC_Multiple_Email_Recipients;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Registerable,
	Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Service\Premium_Service;

/**
 * Provide additional email at different places depend on the settings
 *
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Fields_Service implements Premium_Service, Registerable {

	/**
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * @var Fields_Manager
	 */
	private $fields_manager;

	/**
	 * Abstract_Email_Provider constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin         = $plugin;
		$this->fields_manager = $this->plugin->get_fields_manager();
	}

	public function register() {
		add_filter( 'woocommerce_billing_fields', [ $this, 'add_frontend_emails' ] );
		add_filter( 'woocommerce_customer_meta_fields', [ $this, 'add_admin_user_profile_emails' ] );
		add_action( 'woocommerce_new_order', [ $this, 'save_fields_from_order_post' ], 10 );

		add_action( 'woocommerce_edit_account_form', [ $this, 'add_account_emails' ] );
		add_action( 'woocommerce_save_account_details', [ $this, 'save_fields_from_user_post' ] );
	}

	/**
	 * Store the additional recipients as order metadata
	 *
	 * In addition to attaching the additional email addresses to the order as metadata
	 * it also saves them as additional emails for the user who placed the order
	 *
	 * @param  WC_Order $order
	 */
	public function save_fields_from_order_post( $order ) {
		if ( is_a( $order, 'WC_Order' ) ) {
			$order_id = $order->get_id();
		} elseif ( is_numeric( $order ) && $order > 0 ) {
			$order_id = $order;
			$order    = wc_get_order( $order_id );
		} else {
			return;
		}

		$is_edited   = false;
		$user_id     = $order->get_customer_id();
		$email_count = $this->fields_manager->get_emails_count();
		$user_emails = $this->fields_manager->get_additional_emails_for_customer( $user_id );

		// if the additional emails are not used on the checkout page
		// or the order was not created from the checkout page (i.e. it was added from the backend)
		// then we need to use the additional email addresses saved in the user account
		$use_saved_emails = ! $this->fields_manager->is_enabled_for_checkout() || ! is_checkout();

		for ( $i = 2; $i <= $email_count; $i ++ ) {
			$email_meta_key = $this->fields_manager->get_meta_key_for_email( $i );
			$email          = isset( $_POST[ $email_meta_key ] ) ? $_POST[ $email_meta_key ] : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! $email && $use_saved_emails ) {
				$email = $user_emails[ $email_meta_key ];
			}

			if ( $email ) {
				if ( $user_id && is_checkout() ) {
					update_user_meta( $user_id, $email_meta_key, sanitize_email( $email ) );
				}

				$order->update_meta_data( $email_meta_key, sanitize_email( $email ) ?? '' );
				$is_edited = true;
			}
		}

		if ( $is_edited ) {
			$order->save();
		}
	}

	/**
	 * Store additional emails in the user account (customer billing address)
	 *
	 * @since 1.2.4
	 *
	 * @param  int $user_id
	 */
	public function save_fields_from_user_post( $user_id ) {
		if ( ! $user_id ) {
			return;
		}

		$email_count = $this->fields_manager->get_emails_count();

		for ( $i = 2; $i <= $email_count; $i ++ ) {
			$email_meta_key = $this->fields_manager->get_meta_key_for_email( $i );
			$email          = isset( $_POST[ $email_meta_key ] ) ? $_POST[ $email_meta_key ] : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			update_user_meta( $user_id, $email_meta_key, sanitize_email( $email ) );
		}
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public function add_admin_user_profile_emails( $fields ) {

		for ( $i = 2; $i <= $this->fields_manager->get_emails_count(); $i ++ ) {

			$email_meta_key   = $this->fields_manager->get_meta_key_for_email( $i );
			$email_meta_label = $this->fields_manager->get_meta_label_for_email( $i );

			$fields['billing']['fields'][ $email_meta_key ] = [
				'label'       => $email_meta_label,
				'description' => ''
			];
		}

		return $fields;
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public function add_frontend_emails( $fields ) {

		if ( is_checkout() && ! $this->fields_manager->is_enabled_for_checkout() ) {
			return $fields;
		}

		if ( is_account_page() && ! $this->fields_manager->is_enabled_for_account_page() ) {
			return $fields;
		}

		for ( $i = 2; $i <= $this->fields_manager->get_emails_count(); $i ++ ) {

			$email_meta_key   = $this->fields_manager->get_meta_key_for_email( $i );
			$email_meta_label = $this->fields_manager->get_meta_label_for_email( $i );

			$fields[ $email_meta_key ] = [
				'label'        => $email_meta_label,
				'required'     => false,
				'class'        => [ 'form-row-wide' ],
				'validate'     => [ 'email' ],
				'type'         => 'email',
				'autocomplete' => 'email username',
				'priority'     => 110 + $i,
			];
		}

		return $fields;
	}

	public function add_account_emails() {

		if ( ! $this->fields_manager->is_enabled_for_account_page() ) {
			return;
		}

		for ( $i = 2; $i <= $this->fields_manager->get_emails_count(); $i ++ ) {

			$email_meta_key   = $this->fields_manager->get_meta_key_for_email( $i );
			$email_meta_label = $this->fields_manager->get_meta_label_for_email( $i );

			?>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="<?php echo esc_attr( $email_meta_key ); ?>"><?php echo esc_html( $email_meta_label ); ?></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--email input-text"
					   name="<?php echo esc_attr( $email_meta_key ); ?>"
					   id="<?php echo esc_attr( $email_meta_key ); ?>" autocomplete="email"
					   value="<?php echo esc_attr( get_user_meta( get_current_user_id(), $email_meta_key, true ) ); ?>"/>
			</p>
			<?php
		}
	}

	public function add_email_fields_to_blocks_checkout() {
		for ( $i = 2; $i <= $this->fields_manager->get_emails_count(); $i ++ ) {

			$email_meta_key   = $this->fields_manager->get_meta_key_for_email( $i );
			$email_meta_label = $this->fields_manager->get_meta_label_for_email( $i );

			woocommerce_register_additional_checkout_field(
				array(
					'id'            => 'woocommerce-multiple-email-recipients/additional-email-' . $i,
					'label'         => $email_meta_label,
					'optionalLabel' => $email_meta_label . ' (optional)',
					'location'      => 'contact',
					'required'      => false,
					'attributes'    => array(
						'pattern'          => '[a-zA-Z0-9.-]+(.[a-zA-Z]{2,})+', // A Valid email address that allows subdomains as well
					),
				),
			);
		}
	}

}
