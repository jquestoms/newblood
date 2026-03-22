<?php

/**
 * Template for the additional recipient field of the admin emails
 */

?>

<div class="wmer-field-wrapper">
	<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Enter additional recipients (comma separated) for this email, and optionally select which products and/or categories they will receive it for.', 'woocommerce-multiple-email-recipients' ); ?>"></span>
	<fieldset>
		<input
			class="input-text regular-input wmer-input"
			type="text"
			name="{{{ data.id }}}_wmer_recipient_{{{ data.count }}}"
			id="{{{ data.id }}}_wmer_recipient_{{{ data.count }}}"
			placeholder="<?php esc_attr_e( 'Email address(es)', 'woocommerce-multiple-email-recipients' ); ?>"
		/>
		<button class="button wmer-button-icon wmer-minus-button" type="button"></button>
	</fieldset>
	<fieldset>
		<select
			multiple
			class="wc-category-search"
			name="{{{ data.id }}}_wmer_recipient_categories_{{{ data.count }}}[]"
			id="{{{ data.id }}}_wmer_recipient_categories_{{{ data.count }}}"
			data-placeholder="<?php esc_attr_e( 'Search for a category', 'woocommerce-multiple-email-recipients' ); ?>"
		>
		</select>
	</fieldset>
	<fieldset>
		<select
			multiple
			class="wc-product-search"
			name="{{{ data.id }}}_wmer_recipient_products_{{{ data.count }}}[]"
			id="{{{ data.id }}}_wmer_recipient_products_{{{ data.count }}}"
			data-placeholder="<?php esc_attr_e( 'Search for a product', 'woocommerce-multiple-email-recipients' ); ?>"
		>
		</select>
	</fieldset>
	<button class="button wmer-action add_recipient" type="button">
		<?php esc_html_e( 'Add recipient', 'woocommerce-multiple-email-recipients' ); ?>
	</button>
</div>
