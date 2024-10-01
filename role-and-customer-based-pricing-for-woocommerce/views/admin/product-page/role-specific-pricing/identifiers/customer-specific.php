<?php defined( 'ABSPATH' ) || die;

/**
 * Available variables
 *
 * @var array $present_rules
 */
?>

<div class="rcbp-add-new-rule-form">
	<select class="rcbp-add-new-rule-form__identifier-selector rcbp-add-new-rule-form__identifier-selector--customer wc-product-search"
			data-action="woocommerce_json_search_rcbp_customers"
			data-minimum_input_length="1"
			data-placeholder="<?php esc_attr_e( 'Select for a customer&hellip;', 'role-and-customer-based-pricing-for-woocommerce' ); ?>"
			style="width: 200px;">
	</select>

	<button class="button rcbp-add-new-rule-form__add-button"> <?php esc_attr_e( 'Setup for customer', 'role-specific-pricing' ); ?></button>

	<div class="clear"></div>
</div>
