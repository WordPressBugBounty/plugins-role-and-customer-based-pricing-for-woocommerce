<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\Tabs;

use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\FormTab;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;

class Pricing extends FormTab {

	public function getId(): string {
		return 'pricing';
	}

	public function getTitle(): string {
		return __( 'Pricing', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function getDescription(): string {
		return __( 'Set up custom pricing rules.', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function render( GlobalPricingRule $pricingRule ) {
		$this->renderSectionTitle( __( 'Pricing settings', 'role-and-customer-based-pricing-for-woocommerce' ), array(
				'description' => __( 'Configure the pricing adjustments here.', 'role-and-customer-based-pricing-for-woocommerce' ),
		) );

		?>
		<div id="rcbp-pricing-rule-block-global"
			 class="rcbp-pricing-rule-block rcbp-pricing-rule-block rcbp-pricing-rule-block--global">
			<?php
			$this->getContainer()->getFileManager()->includeTemplate( 'admin/product-page/role-specific-pricing/single-rule-form.php', array(
				'pricing_rule' => $pricingRule,
				'type'         => 'global',
				'loop'         => false,
				'identifier'   => 'global',
			) );
			?>
		</div>
		<?php
	}

	public function getIcon(): string {
		return '$';
	}
}
