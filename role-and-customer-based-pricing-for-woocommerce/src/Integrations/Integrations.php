<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Integrations;

use MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins\SmartCoupons;
use MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins\WooCommerceProductAddons;

class Integrations {
	
	public function __construct() {
		$this->init();
	}
	
	public function init() {
		
		$plugins = apply_filters( 'tiered_pricing_table/integrations/plugins', array(
			WooCommerceProductAddons::class,
			SmartCoupons::class,
		) );
		
		foreach ( $plugins as $plugin ) {
			new $plugin();
		}
	}
}