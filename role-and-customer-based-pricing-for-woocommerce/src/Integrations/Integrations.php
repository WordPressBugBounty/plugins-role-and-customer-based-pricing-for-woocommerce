<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Integrations;

use MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins\SmartCoupons;
use MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins\WooCommerceProductAddons;
use MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins\WCPA;
use MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins\WombatProductAddons;

class Integrations {
	
	public function __construct() {
		$this->init();
	}
	
	public function init() {
		
		$plugins = apply_filters( 'role_customer_specific_pricing/integrations/plugins', array(
			WooCommerceProductAddons::class,
			SmartCoupons::class,
			WCPA::class,
			WombatProductAddons::class,
		) );
		
		foreach ( $plugins as $plugin ) {
			new $plugin();
		}
	}
}
