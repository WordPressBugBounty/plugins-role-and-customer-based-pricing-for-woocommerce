<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns;

use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;

class AppliedQuantityRules {

	public function getName() {
		return __( 'Quantity rules', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function render( GlobalPricingRule $rule ) {

		$notSetLabel = __( 'Not set', 'role-and-customer-based-pricing-for-woocommerce' );

		$min     = $rule->getMinimum() ? $rule->getMinimum() : $notSetLabel;
		$max     = $rule->getMaximum() ? $rule->getMaximum() : $notSetLabel;
		$groupOf = $rule->getGroupOf() ? $rule->getGroupOf() : $notSetLabel;

		// translators: %s: minimum amount
		echo esc_html( sprintf( __( 'Minimum: %s', 'role-and-customer-based-pricing-for-woocommerce' ), $min ) ) . '<br>';
		// translators: %s: maximum amount
		echo esc_html( sprintf( __( 'Maximum: %s', 'role-and-customer-based-pricing-for-woocommerce' ), $max ) ) . '<br>';
		// translators: %s: quantity step
		echo esc_html( sprintf( __( 'Quantity step: %s', 'role-and-customer-based-pricing-for-woocommerce' ), $groupOf ) );
	}
}
