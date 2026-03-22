<?php namespace Barn2\Plugin\WC_Multiple_Email_Recipients;

use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Multiple_Email_Recipients\Dependencies\Lib\Service\Premium_Service;
use WC_Email;
use WC_Emails;
use WC_Order;

use const Barn2\Plugin\WC_Multiple_Email_Recipients\PLUGIN_FILE;

/**
 * Handle WooCommerce Emails with additional emails
 *
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Emails_Service implements Premium_Service, Registerable {

	/**
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * @var Fields_Manager
	 */
	private $emails_manager;

	private $views_path;

	/**
	 * Abstract_Email_Provider constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin         = $plugin;
		$this->emails_manager = $this->plugin->get_fields_manager();
		$this->views_path     = plugin_dir_path( PLUGIN_FILE ) . 'src/Admin/views/';
	}

	public function register() {
		add_action( 'init', [ $this, 'load_service' ], 15 );
	}

	public function load_service() {
		if ( $this->emails_manager->need_to_provide_additional_emails() ) {
			foreach ( $this->get_customer_email_ids() as $email_id ) {
				add_filter( "woocommerce_settings_api_form_fields_{$email_id}", [ $this, "add_multiple_email_option_{$email_id}" ], 10, 3 );
			}

			add_filter( 'woocommerce_mail_callback_params', [ $this, 'add_multiple_email_recipients' ], 10, 2 );
		}

		foreach ( $this->get_admin_email_ids() as $email_id ) {
			add_filter( "woocommerce_settings_api_form_fields_{$email_id}", [ $this, 'admin_email_form_fields' ] );
			add_filter( "woocommerce_email_recipient_{$email_id}", [ $this, 'admin_email_recipients' ], 10, 3 );
			add_filter( "woocommerce_settings_api_sanitized_fields_{$email_id}", [ $this, 'clean_settings' ] );
		}

		// add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'woocommerce_generate_wmer_admin_email_addresses_html', [ $this, 'admin_email_addresses_field_type' ], 10, 3 );
		add_action( 'admin_footer', [ $this, 'print_recipient_addon_field_template' ] );
	}

	public function add_multiple_email_recipients( $params, WC_Email $email ) {
		if ( $email->is_customer_email() && $email->get_option( 'multiple_email_recipients_enabled' ) === 'yes' ) {
			$object = $email->object;

			if ( is_a( $object, 'WC_Order' ) ) {
				// the object attached to the email is a WC_ORDER
				// so, get the additional emails attached to the order
				$additional_emails = $this->emails_manager->get_additional_emails_for_order( $object->get_id() );
			} elseif ( is_a( $object, 'WP_User' ) ) {
				// the object attached to the email is a WP_USER
				// so, get their additional emails
				$additional_emails = $this->emails_manager->get_additional_emails_for_customer( $object->ID );
			} else {
				// the object attached to the email is none of the above
				// so, get the user by the main email address passed to the email
				// and see if the user has any additional emails
				$user = get_user_by( 'email', $params[0] );

				if ( is_a( $user, 'WP_User' ) ) {
					$additional_emails = $this->emails_manager->get_additional_emails_for_customer( $user->ID );
				}
			}

			if ( empty( $additional_emails ) ) {
				// if nothing else has worked, see if the additional emails are specified in the request
				$additional_emails = $this->emails_manager->get_additional_emails_from_request();
			}

			if ( ! empty( $additional_emails ) ) {
				$params[0] .= ',' . implode( ',', $additional_emails );
			}
		}

		return $params;
	}

	public function add_multiple_email_option( $options, $email ) {
		$options = $options[0];

		$settings = get_option( 'woocommerce_' . $email . '_settings' );

		if ( ! empty( $settings['enabled'] ) ) {
			$default = $settings['enabled'];
		} else {
			$default = ! empty( $options['enabled']['default'] ) ? $options['enabled']['default'] : 'no';
		}

		$new_options = [];

		if ( empty( $options['enabled'] ) ) {
			$new_options['multiple_email_recipients_enabled'] = [
				'title'   => 'Additional recipients',
				'type'    => 'checkbox',
				'label'   => __( 'Enable this notification for additional customer email addresses', 'woocommerce-multiple-email-recipients' ),
				'default' => $default,
			];
		}

		foreach ( $options as $key => $option ) {

			$new_options[ $key ] = $option;

			if ( $key === 'enabled' ) {

				$new_options['multiple_email_recipients_enabled'] = [
					'title'   => 'Additional recipients',
					'type'    => 'checkbox',
					'label'   => __( 'Enable this notification for additional customer email addresses', 'woocommerce-multiple-email-recipients' ),
					'default' => $default,
				];
			}
		}

		return $new_options;
	}

	public function get_emails() {
		$wc_emails = WC_Emails::instance();
		$emails    = $wc_emails->get_emails();

		return array_combine(
			array_map( 'strtolower', array_keys( $emails ) ),
			$emails
		);
	}

	protected function get_admin_email_ids() {
		$admin_emails = array_values(
			array_map(
				function( $email ) {
					return $email->id;
				},
				array_filter(
					$this->get_emails(),
					function( $email ) {
						return ! $email->is_customer_email();
					}
				)
			)
		);

		return apply_filters(
			'multiple_email_recipients_admin_email_ids',
			$admin_emails
		);
	}

	protected function get_customer_email_ids() {
		$customer_emails = array_values(
			array_map(
				function( $email ) {
					return $email->id;
				},
				array_filter(
					$this->get_emails(),
					function( $email ) {
						return $email->is_customer_email();
					}
				)
			)
		);

		return apply_filters(
			'multiple_email_recipients_customer_email_ids',
			$customer_emails
		);
	}

	public function __call( $name, $arguments ) {
		foreach ( $this->get_customer_email_ids() as $email_id ) {
			if ( substr_compare( $name, $email_id, strlen( $name ) - strlen( $email_id ), strlen( $email_id ) ) === 0 ) {
				return $this->add_multiple_email_option( $arguments, $email_id );
			}
		}

		return null;
	}

	// public function enqueue_scripts( $hook ) {
	// if ( 'woocommerce_page_wc-settings' !== $hook ) {
	// return;
	// }

	// global $current_tab, $current_section;

	// if ( 'email' === $current_tab && $current_section ) {
	// wp_enqueue_script( 'wmer_email_script', $this->plugin->get_dir_url() . '/assets/js/admin/email-settings.js', [ 'jquery' ], $this->plugin->get_version(), true );

	// $params = [
	// 'i18n' => [
	// 'warning_remove_recipient' => __( 'Are you sure you want to remove these additional recipients?', 'woocommerce-multiple-email-recipients' ),
	// ],
	// ];

	// wp_add_inline_script(
	// 'wmer_email_script',
	// sprintf( 'const wmer_params = %s;', wp_json_encode( $params ) ),
	// 'before'
	// );
	// }
	// }

	public function admin_email_addresses_field_type( $key, $data, $email ) {
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $email->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp">
				<ul class="wmer_admin_email_list">
						<li>
							<fieldset>
								<input class="wmer-admin-recipients" type="text" name="wmer_admin_recipients[]" />
								<select></select>
								<select></select>
							</fieldset>
						</li>
				</ul>
			</td>
		</tr>
		<?php
	}

	/**
	 * Filter the `$form_fields` array adding the extra field to the admin email pages
	 *
	 * @param array $form_fields The array with all the form fields
	 *
	 * @return array The filtered array of fields
	 */
	public function admin_email_form_fields( $form_fields ) {
		global $current_section;

		$keys   = array_keys( $form_fields );
		$pos    = array_search( 'recipient', $keys, true );
		$emails = $this->get_emails();
		$email  = isset( $emails[ $current_section ] ) ? $emails[ $current_section ] : null;

		// if recipient is one of the fields AND
		// the current section corresponds to an admin email...
		if ( -1 !== $pos && ! is_null( $email ) && ! $email->is_customer_email() ) {
			$email_id = $email->id;
			$settings = get_option( "woocommerce_{$email_id}_settings", [] );

			if ( isset( $_POST ) && ! empty( $_POST ) ) {
				$settings = array_combine(
					array_map(
						function( $k ) use ( $email_id ) {
							return str_replace( "woocommerce_{$email_id}_", '', $k );
						},
						array_keys( $_POST )
					),
					$_POST
				);
			}

			$index = 1;

			$additional_recipients = [];

			while ( isset( $settings[ "wmer_recipient_{$index}" ] ) ) {
				$category_options = isset( $settings[ "wmer_recipient_categories_{$index}" ] ) ?
					$settings[ "wmer_recipient_categories_{$index}" ] :
					[];

				if ( ! is_array( $category_options ) ) {
					$category_options = [ $category_options ];
				}

				$category_options = array_combine(
					$category_options,
					array_map(
						function( $k ) {
							$t = get_term_by( 'slug', $k, 'product_cat' );
							if ( $t ) {
								return get_term_parents_list(
									$t->term_id,
									'product_cat',
									[
										'separator' => ' > ',
										'link'      => false,
										'inclusive' => false,
									]
								) . $t->name . ' (' . $t->count . ')';
							}
							return $k;
						},
						$category_options
					)
				);

				$product_options = isset( $settings[ "wmer_recipient_products_{$index}" ] ) ?
					$settings[ "wmer_recipient_products_{$index}" ] :
					[];

				if ( ! is_array( $product_options ) ) {
					$product_options = [ $product_options ];
				}

				$product_options = array_combine(
					$product_options,
					array_map(
						function( $k ) {
							$product = wc_get_product( $k );
							if ( $product ) {
								return wp_strip_all_tags( $product->get_formatted_name() );
							}
							return $k;
						},
						$product_options
					)
				);

				$additional_recipients = array_merge(
					$additional_recipients,
					[
						"wmer_recipient_{$index}"          => [
							'type'        => 'text',
							'placeholder' => get_option( 'admin_email' ),
							'default'     => '',
							'class'       => 'wmer-input',
						],
						"wmer_recipient_categories_{$index}" => [
							'type'              => 'multiselect',
							'default'           => '',
							'class'             => 'wc-category-search',
							'options'           => $category_options,
							'custom_attributes' => [
								'multiple'         => 'multiple',
								'data-placeholder' => __( 'Search for a category', 'woocommerce-multiple-email-recipients' ),
							],
						],
						"wmer_recipient_products_{$index}" => [
							'type'              => 'multiselect',
							'default'           => '',
							'class'             => 'wc-product-search',
							'options'           => $product_options,
							'custom_attributes' => [
								'multiple'         => 'multiple',
								'data-placeholder' => __( 'Search for a product', 'woocommerce-multiple-email-recipients' ),
							],
						],
					]
				);

				$index++;
			}

			$head = array_slice( $form_fields, 0, $pos + 1 );
			$tail = array_slice( $form_fields, $pos + 1 );

			$form_fields = array_merge(
				$head,
				$additional_recipients,
				$tail
			);
		}

		return $form_fields;
	}

	/**
	 * Filter the `$settings` array of an email object
	 * unsetting all the fields that were not submitted
	 * (this is necessary because the additional recipients are dynamic fields
	 * that can be freely added or removed every time the settings are saved)
	 *
	 * @param array $settings The settings of the email page
	 *
	 * @return array The filtered array
	 */
	public function clean_settings( $settings ) {
		if ( empty( $_POST ) ) {
			return $settings;
		}

		global $current_section;

		$emails = $this->get_emails();
		$email  = isset( $emails[ $current_section ] ) ? $emails[ $current_section ] : null;

		if ( is_null( $email ) || $email->is_customer_email() ) {
			return $settings;
		}

		$email_id  = $email->id;
		$post_data = array_combine(
			array_map(
				function( $k ) use ( $email_id ) {
					return str_replace( "woocommerce_{$email_id}_", '', $k );
				},
				array_keys( $_POST )
			),
			$_POST
		);

		foreach ( $settings as $key => $value ) {
			if ( ! isset( $post_data[ $key ] ) ) {
				unset( $settings[ $key ] );
			}
		}

		return $settings;
	}

	/**
	 * Filter the list of recipients depending on the `$order` and `$email` objects
	 *
	 * @param string $recipients A comma separated list of recipients
	 * @param WC_Order $order An order object
	 * @param WC_Email $email An email object
	 *
	 * @return string The filtered list of recipients
	 */
	public function admin_email_recipients( $recipients, $object, $email ) {
		$recipients    = array_map( 'trim', explode( ',', $recipients ) );
		$email_id      = $email->id;
		$product_items = [];

		if ( is_a( $object, 'WC_Product' ) ) {
			$product_items = [ $object ];
		} elseif ( is_a( $object, 'WC_Order' ) ) {
			$product_items = $object->get_items();
		} elseif ( is_array( $object ) && isset( $object['product'] ) && is_a( $object['product'], 'WC_Product' ) ) {
			$product_items = [ $object['product'] ];
		} else {
			// in any other cases, the list of products is empty
			$product_items = [];
		}

		$product_ids = array_values(
			array_map(
				function( $pi ) {
					$product_id   = $pi->get_product_id();
					$variation_id = $pi->get_variation_id();

					if ( $variation_id ) {
						return $variation_id;
					}

					return $product_id;
				},
				$product_items
			)
		);

		$additional_recipients = apply_filters(
			'multiple_email_recipients_admin_email_recipients',
			$this->get_interested_recipients( $product_ids, $email_id ),
			$object,
			$email
		);

		if ( $additional_recipients ) {
			$recipients = array_unique(
				array_merge(
					$recipients,
					$additional_recipients
				)
			);
		}

		return implode( ',', $recipients );
	}

	public function get_interested_recipients( $product_ids, $email_id ) {
		$index                 = 1;
		$email_settings        = get_option( "woocommerce_{$email_id}_settings", [] );
		$additional_recipients = [];

		while ( isset( $email_settings[ "wmer_recipient_{$index}" ] ) ) {
			$_additional_recipients = array_map( 'trim', explode( ',', $email_settings[ "wmer_recipient_{$index}" ] ) );

			$recipient_categories  = isset( $email_settings[ "wmer_recipient_categories_{$index}" ] ) ? $email_settings[ "wmer_recipient_categories_{$index}" ] : [];
			$recipient_product_ids = isset( $email_settings[ "wmer_recipient_products_{$index}" ] ) ? $email_settings[ "wmer_recipient_products_{$index}" ] : [];

			if ( empty( $recipient_categories ) && empty( $recipient_product_ids ) ) {
				// this should never occur because unconditional email addresses
				// could be added to the default recipient fields, separated by commas
				// Still, we want to catch this case for completeness
				$additional_recipients = array_merge( $additional_recipients, $_additional_recipients );
				$index++;
				continue;
			}

			if ( ! empty( $recipient_product_ids ) ) {
				$recipient_product_ids = array_map( 'intval', $recipient_product_ids );
				$children_ids          = [];

				foreach ( $recipient_product_ids as $recipient_product_id ) {
					$_product = wc_get_product( $recipient_product_id );

					if ( is_a( $_product, 'WC_Product' ) ) {
						$children     = $_product->get_children();
						$children_ids = array_merge( $children_ids, $children );
					}
				}

				$recipient_product_ids = array_unique( array_merge( $recipient_product_ids, $children_ids ) );

				if ( ! empty( array_intersect( $product_ids, $recipient_product_ids ) ) ) {
					// there is no need to check this recipients any further
					// since we have already a positive match
					$additional_recipients = array_merge( $additional_recipients, $_additional_recipients );
					$index++;
					continue;
				}
			}

			if ( ! empty( $recipient_categories ) ) {
				$categories = [];

				foreach ( $product_ids as $product_id ) {
					$_product = wc_get_product( $product_id );

					if ( $_product ) {
						if ( is_a( $_product, 'WC_Product_Variation' ) ) {
							$product_id = $_product->get_parent_id();
						}

						$terms = get_the_terms( $product_id, 'product_cat' );

						if ( is_array( $terms ) ) {
							foreach ( $terms as $term ) {
								$all_terms  = get_term_parents_list(
									$term->term_id,
									'product_cat',
									[
										'format'    => 'slug',
										'separator' => ',',
										'link'      => false,
									]
								);
								$categories = array_merge(
									$categories,
									explode( ',', $all_terms )
								);
							}
						}
					}
				}

				$categories = array_unique( array_filter( $categories ) );

				if ( ! empty( array_intersect( $categories, $recipient_categories ) ) ) {
					$additional_recipients = array_merge( $additional_recipients, $_additional_recipients );
					$index++;
					continue;
				}
			}

			$index++;
		}

		return $additional_recipients;
	}

	public function print_recipient_addon_field_template() {
		echo '<script type="text/html" id="tmpl-wmer-recipient">';
		require_once "{$this->views_path}html-recipient-addon-field.php";
		echo '</script>';
	}

}
