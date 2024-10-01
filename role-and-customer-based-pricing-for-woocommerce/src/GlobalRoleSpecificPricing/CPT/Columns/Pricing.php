<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns;

use Exception;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;

class Pricing {

	public function getName() {
		return __( 'Pricing', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function render( GlobalPricingRule $rule ) {
		try {
			$rule->validatePricing();

			if ( $rule->getPriceType() === 'flat' ) {

				if ( $rule->getRegularPrice() ) {
					echo wp_kses_post( sprintf( '<span>%s: <b>%s</b></span>', __( 'Regular price', 'role-and-customer-based-pricing-for-woocommerce' ), wc_price( $rule->getRegularPrice() ) ) );
				}

				if ( $rule->getSalePrice() ) {
					echo wp_kses_post( sprintf( '<div><span>%s: <b>%s</b></span></div>', __( 'Sale price', 'role-and-customer-based-pricing-for-woocommerce' ), wc_price( $rule->getSalePrice() ) ) );
				}

			} else {
				if ( $rule->getDiscount() ) {
					echo wp_kses_post( sprintf( '%s: <b>%s</b>', __( 'Discount', 'role-and-customer-based-pricing-for-woocommerce' ), $rule->getDiscount() . '%' ) );
				}
			}

		} catch ( Exception $e ) {
			echo wp_kses_post( '<div class="help_tip rcbp-rule-status rcbp-rule-status--invalid" data-tip="' . $e->getMessage() . '">!</div>' );
		}
	}
}
