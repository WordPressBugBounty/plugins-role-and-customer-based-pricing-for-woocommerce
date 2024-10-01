<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections;

use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\PremiumImportOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\PremiumSelectOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\SwitchCheckboxOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Settings;

class PricingSection extends AbstractSection {
	
	public function getTitle() {
		return __( 'Pricing', 'role-and-customer-based-pricing-for-woocommerce' );
	}
	
	public function getDescription() {
		return __( 'When you use percentage discounts to assign prices for specific users, you can choose to display either exact prices or show it as the sale price.',
			'role-and-customer-based-pricing-for-woocommerce' );
	}
	
	public function getName() {
		return 'pricing_section';
	}
	
	public function getSettings() {
		return array(
			array(
				'title'   => __( 'Display as', 'role-and-customer-based-pricing-for-woocommerce' ),
				'id'      => Settings::SETTINGS_PREFIX . 'percentage_based_pricing_rule_behaviour',
				'default' => 'full_price',
				'type'    => PremiumSelectOption::FIELD_TYPE,
				'is_free' => ! racbpfw_fs()->can_use_premium_code(),
				'options' => array(
					'full_price' => __( 'Exact set price', 'role-and-customer-based-pricing-for-woocommerce' ),
					'sale_price' => __( 'Sale price', 'role-and-customer-based-pricing-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Always use regular price to calculate percentage discounts',
					'role-and-customer-based-pricing-for-woocommerce' ),
				'id'          => Settings::SETTINGS_PREFIX . 'use_regular_price_for_percentage_discounts',
				'default'     => 'no',
				'type'        => SwitchCheckboxOption::FIELD_TYPE,
				'desc' => __( 'If enabled, the plugin will always use the regular price to calculate percentage discounts. By default, the plugin uses the sale price if it exists.',
					'role-and-customer-based-pricing-for-woocommerce' ),
			),
		);
	}
	
}
