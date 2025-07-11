<?php

use MeowCrew\RoleAndCustomerBasedPricing\Entity\PricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\Utils\Formatter;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Available variables
 *
 * @var string $identifier
 * @var string $type
 * @var int|false $loop
 * @var PricingRule $pricing_rule
 */

$loop                = false !== $loop ? "_variation[{$loop}]" : '';
$labelRuleAppliedFor = '';

switch ( $type ) {
	case 'customer':
		try {
			$customer            = new WC_Customer( $identifier );
			$labelRuleAppliedFor = Formatter::formatCustomerString( $customer );
		} catch ( Exception $e ) {
			$ruleAppliedFor = $identifier;
		}
		break;
	case 'role':
		$labelRuleAppliedFor = Formatter::formatRoleString( $identifier ) . ' ' . __( 'user role', 'role-and-customer-based-pricing-for-woocommerce' );
		break;
	case 'global':
		$labelRuleAppliedFor = __( 'users in this rule', 'role-and-customer-based-pricing-for-woocommerce' );
		break;
	default:
		$labelRuleAppliedFor = $identifier;
}

?>

<div class="rcbp-pricing-rule-form rcbp-pricing-rule-form--product">

    <div class="rcbp-pricing-rule-form__prices">

        <div class="rcbp-pricing-rule-form__pricing-type">
			<?php $fieldName = "_rcbp_{$type}_pricing_type{$loop}[{$identifier}]"; ?>

            <p class="form-field <?php echo esc_attr( $fieldName ); ?>_field">

                <label><?php esc_html_e( 'Pricing type', 'role-and-customer-based-pricing-for-woocommerce' ); ?> </label>

                <input type="radio" value="flat" name="<?php echo esc_html( $fieldName ); ?>"
                       class="rcbp-pricing-type-input"
					<?php checked( $pricing_rule->getPriceType(), 'flat' ); ?>
                       id="<?php echo esc_html( $fieldName ); ?>-flat">

                <label class="rcbp-pricing-rule-form__pricing-type-label"
                       for="<?php echo esc_html( $fieldName ); ?>-flat">
					<?php esc_html_e( 'Flat prices', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
                </label>

                <input type="radio" value="percentage" name="<?php echo esc_html( $fieldName ); ?>"
                       class="rcbp-pricing-type-input"
                       max="99"
					<?php checked( $pricing_rule->getPriceType(), 'percentage' ); ?>
                       id="<?php echo esc_html( $fieldName ); ?>-percentage">

                <label class="rcbp-pricing-rule-form__pricing-type-label"
                       for="<?php echo esc_html( $fieldName ); ?>-percentage">
					<?php esc_html_e( 'Percentage discount', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
                </label>

            </p>
        </div>

        <div class="rcbp-pricing-rule-form__flat_prices"
             style="<?php echo esc_attr( $pricing_rule->getPriceType() === 'percentage' ? 'display:none' : '' ); ?>">

            <section class="notice notice-warning rcbp-pricing-rule-form__flat-prices-warning">
                <p>
					<?php esc_html_e( 'Note that prices indicated below will be applied to the whole range of products you specified in the products/categories sections. Be careful to not mess up the pricing.', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
                </p>
            </section>

			<?php $fieldName = "_rcbp_{$type}_regular_price{$loop}[{$identifier}]"; ?>

            <p class="form-field <?php echo esc_attr( $fieldName ); ?>_field">
                <label for="<?php echo esc_attr( $fieldName ); ?>"><?php echo esc_attr( __( 'Regular price', 'role-and-customer-based-pricing-for-woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')' ); ?> </label>
				<?php
				/* translators: %s: Customer role or name */
				$placeholder = sprintf( __( 'Specify the regular price for %s', 'role-and-customer-based-pricing-for-woocommerce' ), $labelRuleAppliedFor );
				?>
                <input type="text"
                       value="<?php echo esc_attr( wc_format_localized_price( $pricing_rule->getRegularPrice() ) ); ?>"
                       placeholder="<?php echo esc_attr( $placeholder ); ?>"
                       class="wc_input_price"
                       name="<?php echo esc_attr( $fieldName ); ?>"
                       id="<?php echo esc_attr( $fieldName ); ?>">

				<?php echo wp_kses_post( wc_help_tip( esc_attr__( 'If you don\'t want to change standard product pricing - leave field empty.', 'role-and-customer-based-pricing-for-woocommerce' )) ); ?>
            </p>

			<?php $fieldName = "_rcbp_{$type}_sale_price{$loop}[{$identifier}]"; ?>

            <p class="form-field <?php echo esc_attr( $fieldName ); ?>_field">
                <label for="<?php echo esc_attr( $fieldName ); ?>">
					<?php echo esc_attr( __( 'Sale price', 'role-and-customer-based-pricing-for-woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')' ); ?>
                </label>
				<?php
				/* translators: %s: Customer role or name */
				$placeholder = sprintf( __( 'Specify the sale price for %s', 'role-and-customer-based-pricing-for-woocommerce' ), $labelRuleAppliedFor );
				?>
                <input type="text"
                       value="<?php echo esc_attr( wc_format_localized_price( $pricing_rule->getSalePrice() ) ); ?>"
                       placeholder="<?php echo esc_attr( $placeholder ); ?>"
                       class="wc_input_price"
                       name="<?php echo esc_attr( $fieldName ); ?>"
                       id="<?php echo esc_attr( $fieldName ); ?>">

				<?php echo wp_kses_post( wc_help_tip( esc_attr__( 'If you don\'t want to change standard product pricing - leave field empty.', 'role-and-customer-based-pricing-for-woocommerce' ) ) ); ?>
            </p>
        </div>

        <div class="rcbp-pricing-rule-form__percentage_discount"
             style="<?php echo esc_attr( $pricing_rule->getPriceType() === 'flat' ? 'display:none' : '' ); ?>">

			<?php if ( ! racbpfw_fs()->can_use_premium_code() ): ?>
                <section class="notice notice-warning rcbp-pricing-rule-form__premium-version-warning">
                    <p>
                        <b><?php esc_html_e( 'Available in the premium version.', 'role-and-customer-based-pricing-for-woocommerce' ); ?></b>
                        <a target="_blank" style="color:red"
                           href="<?php echo esc_url( racbpfw_fs()->get_upgrade_url() ) ?>"><?php esc_html_e( 'Upgrade now', 'role-and-customer-based-pricing-for-woocommerce' ); ?></a>
                    </p>
                </section>
			<?php endif; ?>

            <div class="<?php echo esc_attr( !racbpfw_fs()->can_use_premium_code() ? 'rcbp-pricing-premium-content' : '' ); ?>">
				<?php $fieldName = "_rcbp_{$type}_discount{$loop}[{$identifier}]"; ?>

                <p class="form-field <?php echo esc_attr( $fieldName ); ?>_field">
                    <label for="<?php echo esc_attr( $fieldName ); ?>">
						<?php echo esc_attr( __( 'Discount (%)', 'role-and-customer-based-pricing-for-woocommerce' ) ); ?>
                    </label>

                    <input type="number" step="any" value="<?php echo esc_attr( $pricing_rule->getDiscount() ); ?>"
                           name="<?php echo esc_attr( $fieldName ); ?>"
                           id="<?php echo esc_attr( $fieldName ); ?>">
                </p>
            </div>

        </div>

    </div>

    <hr class="rcbp-title-separator"
        data-title="<?php esc_attr_e( 'Product quantity control', 'role-and-customer-based-pricing-for-woocommerce' ); ?>">

	<?php if ( ! racbpfw_fs()->can_use_premium_code() ): ?>
        <section class="notice notice-warning rcbp-pricing-rule-form__premium-version-warning">
            <p>
                <b><?php esc_html_e( 'Available in the premium version.', 'role-and-customer-based-pricing-for-woocommerce' ); ?></b>
                <a target="_blank" style="color:red"
                   href="<?php echo esc_url( racbpfw_fs()->get_upgrade_url() ) ?>"><?php esc_html_e( 'Upgrade now', 'role-and-customer-based-pricing-for-woocommerce' ); ?></a>
            </p>
        </section>
	<?php endif; ?>

    <div class="rcbp-pricing-rule-form__product_quantity <?php echo esc_attr( ! racbpfw_fs()->can_use_premium_code() ? 'rcbp-pricing-premium-content' : '' ); ?>">

		<?php $fieldName = "_rcbp_{$type}_minimum{$loop}[{$identifier}]"; ?>

        <p class="form-field <?php echo esc_attr( $fieldName ); ?>_field">
            <label for="<?php echo esc_attr( $fieldName ); ?>">
				<?php echo esc_attr( __( 'Minimum quantity', 'role-and-customer-based-pricing-for-woocommerce' ) ); ?>
            </label>
			<?php
			// translators: %s = role name
			echo wp_kses_post( wc_help_tip( sprintf( __( 'Specify the minimal amount of products that %s can purchase.', 'role-and-customer-based-pricing-for-woocommerce' ), '<b>' . $labelRuleAppliedFor . '</b>' ) ) );
			?>
            <input type="number"
                   step="1"
                   name="<?php echo esc_attr( $fieldName ); ?>"
                   id="<?php echo esc_attr( $fieldName ); ?>"
                   value="<?php echo esc_attr( $pricing_rule->getMinimum() ); ?>">
        </p>

		<?php $fieldName = "_rcbp_{$type}_maximum{$loop}[{$identifier}]"; ?>

        <p class="form-field <?php echo esc_attr( $fieldName ); ?>_field">
            <label for="<?php echo esc_attr( $fieldName ); ?>">
				<?php echo esc_attr( __( 'Maximum quantity', 'role-and-customer-based-pricing-for-woocommerce' ) ); ?>
            </label>
			<?php
			// translators: %s = role name
			echo wp_kses_post(  wc_help_tip( sprintf( __( 'Specify the maximum number of products available for purchase by %s in one order.', 'role-and-customer-based-pricing-for-woocommerce' ), '<b>' . $labelRuleAppliedFor . '</b>' ) ) );
			?>
            <input type="number"
                   step="1"
                   name="<?php echo esc_attr( $fieldName ); ?>"
                   id="<?php echo esc_attr( $fieldName ); ?>"
                   value="<?php echo esc_attr( $pricing_rule->getMaximum() ); ?>">
        </p>

		<?php $fieldName = "_rcbp_{$type}_group_of{$loop}[{$identifier}]"; ?>

        <p class="form-field <?php echo esc_attr( $fieldName ); ?>_field">
            <label for="<?php echo esc_attr( $fieldName ); ?>">
				<?php echo esc_attr( __( 'Quantity step', 'role-and-customer-based-pricing-for-woocommerce' ) ); ?>
            </label>
			<?php
			// translators: %s = role name
			echo wp_kses_post(  wc_help_tip( sprintf( __( 'Specify by how many products quantity will increase or decrease when a customer adds the product to the cart for purchase by %s. Leave blank if products can be added one by one.', 'role-and-customer-based-pricing-for-woocommerce' ), '<b>' . $labelRuleAppliedFor . '</b>' ) ) );
			?>
            <input type="number"
                   step="1"
                   name="<?php echo esc_attr( $fieldName ); ?>"
                   id="<?php echo esc_attr( $fieldName ); ?>"
                   value="<?php echo esc_attr( $pricing_rule->getGroupOf() ); ?>">
        </p>
    </div>

</div>
