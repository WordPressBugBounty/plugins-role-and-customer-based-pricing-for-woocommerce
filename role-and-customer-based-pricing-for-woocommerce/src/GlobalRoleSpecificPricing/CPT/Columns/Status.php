<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns;

use Exception;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;

class Status {

	public function getName() {
		return __( 'Status', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function render( GlobalPricingRule $rule ) {
		if ( $rule->isSuspended() ) {
			?>
			<mark class="rcbp-rule-suspend-status rcbp-rule-suspend-status--suspended">
				<span><?php esc_html_e( 'Suspended', 'role-and-customer-based-pricing-for-woocommerce' ); ?></span>
			</mark>
			<?php
		} else {
			?>
			<mark class="rcbp-rule-suspend-status rcbp-rule-suspend-status--active">
				<span><?php esc_html_e( 'Active', 'role-and-customer-based-pricing-for-woocommerce' ); ?></span>
			</mark>
			<?php
		}
	}
}
