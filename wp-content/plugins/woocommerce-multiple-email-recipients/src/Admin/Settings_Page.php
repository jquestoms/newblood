<?php

namespace Barn2\Plugin\WC_Multiple_Email_Recipients\Admin;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Util\Settings;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\License\Admin\License_Setting;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\WooCommerce\Admin\Custom_Settings_Fields;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\WooCommerce\Admin\Plugin_Promo;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Service\Standard_Service;


/**
 * The Multiple Email Recipients settings page. Appears under the main WooCommerce -> Settings menu.
 *
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Settings_Page implements Registerable, Standard_Service {
	/**
	 * @var Licensed_Plugin
	 */
	private $plugin;

	/**
	 * @var License_Setting
	 */
	private $license_setting;

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $label;

	/**
	 * Settings_Page constructor.
	 *
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->id    = 'multiple-email-recipients';
		$this->label = __( 'Multiple email recipients', 'woocommerce-multiple-email-recipients' );

		$this->plugin          = $plugin;
		$this->license_setting = $plugin->get_license_setting();

		// Add plugin promo section.
		$plugin_promo = new Plugin_Promo( $this->plugin, 'products', $this->id );
		$plugin_promo->register();
	}

	public function register() {
		add_filter( "barn2_wc_settings_custom_fields_load_scripts_{$this->plugin->get_slug()}", [ $this, 'should_load_scripts' ] );
		
		$extra_setting_fields = new Custom_Settings_Fields( $this->plugin );
		$extra_setting_fields->register();

		// Sanitize options on save.
		add_filter( 'woocommerce_admin_settings_sanitize_option', [ $this, 'sanitize_options' ], 10, 3 );
		add_filter( 'woocommerce_get_sections_products', [ $this, 'add_settings_section' ], 10, 1 );
		add_filter( 'woocommerce_get_settings_products', [ $this, 'get_settings' ], 10, 2 );

		// Settings types
		add_action( 'woocommerce_admin_field_customer_email_addresses', [ $this, 'customer_email_addresses_settings_type' ] );

		// Save recipient labels
		add_action( 'woocommerce_update_options_products', [ $this, 'save_labels' ] );

	}

	public function should_load_scripts( $plugin ) {
		if( isset( $_GET['section'] ) && $_GET['section'] === 'multiple-email-recipients' ) {
			return true;
		}
		return false;
	}

	public function get_settings( $settings, $current_section ) {
		if ( $current_section === $this->id ) {
			return apply_filters( 'woocommerce_get_settings_' . $this->id, Settings::get_settings( $this->plugin, $this->id ) );
		}

		return $settings;
	}

	/**
	 * @param array $sections
	 *
	 * @return array
	 */
	public function add_settings_section( $sections ) {
		$sections[ $this->id ] = $this->label;

		return $sections;
	}

	/**
	 * @param array $value
	 */
	public function customer_email_addresses_settings_type( $value ) {

		$email_labels = get_option( 'wmer_customer_email_labels', [] );

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?><?php echo wc_help_tip( $value['tooltip_description'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<input
						name="<?php echo esc_attr( $value['id'] ); ?>"
						id="<?php echo esc_attr( $value['id'] ); ?>"
						type="number"
						min="1"
						style="width: 50px"
						value="<?php echo esc_attr( $value['value'] ); ?>"
						data-currentValue="<?php echo esc_attr( $value['value'] ); ?>"
						data-alertShown=""
						class="<?php echo esc_attr( $value['class'] ); ?>"
						placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
				/>
				<p class="description" style="line-height: 42px; display: inline">
					<?php echo $value['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			</td>
		</tr>
		<tr valign="top" class="wmer-additional-email-label"
		<?php
		if ( $value['value'] < 2 ) {
			echo ' style="display:none"';}
		?>
		>
			<th scope="row" class="titledesc" style="padding-top:6px">
				<label><?php esc_html_e( 'Additional email labels', 'woocommerce-multiple-email-recipients' ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>-labels" style="padding-top:0;display:flex;flex-wrap:wrap;align-items:flex-start">
			<?php
			$email_count = absint( $value['value'] );
			for ( $i = 1; $i < $email_count; $i++ ) {
				?>

				<input
					name="wmer_customer_email_label[<?php echo esc_attr( $i ); ?>]"
					id="wmer_customer_email_label_<?php echo esc_attr( $i ); ?>"
					type="text"
					value="<?php echo esc_attr( isset( $email_labels[ $i ] ) ? $email_labels[ $i ] : '' ); ?>"
					placeholder="<?php echo esc_attr(
						sprintf(
							'%s %s',
							__( 'Email address', 'woocommerce-multiple-email-recipients' ),
							$i + 1
						)
					); ?>"
					style="margin-bottom:5px;margin-right:5px"
				/>

				<?php
			}
			?>
			</td>
		</tr>
		<?php

	}

	public function save_labels() {

		if ( empty( $_POST['wmer_customer_email_label'] ) ) {
			return;
		}

		$labels = $_POST['wmer_customer_email_label'];
		foreach ( $labels as &$label ) {
			$label = $this->filter_string_polyfill( $label );
		}

		update_option( 'wmer_customer_email_labels', $labels );

	}

	function filter_string_polyfill(string $string): string
	{
		$str = preg_replace('/\x00|<[^>]*>?/', '', $string);
		return str_replace(["'", '"'], ['&#39;', '&#34;'], $str);
	}

	public function sanitize_options( $value, $option, $raw_value ) {

		if ( empty( $option['id'] ) ) {
			return $value;
		}

		switch ( $option['id'] ) {
			case $this->license_setting->get_license_setting_name():
				$value = $this->license_setting->save_license_key( $value );
				break;
			case 'multiple_email_recipients_customer_email_addresses':
				$value = max( 1, \intval( $value ) );
				break;
		}

		return $value;
	}

}
