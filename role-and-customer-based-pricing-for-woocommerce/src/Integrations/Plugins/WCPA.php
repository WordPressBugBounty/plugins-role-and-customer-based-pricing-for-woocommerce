<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins;

class WCPA {

	public function __construct() {
		add_filter( 'role_customer_specific_pricing/pricing/price_in_cart', array( $this, 'addAddonsPriceToItem' ), 20, 2 );
	}

	public function addAddonsPriceToItem( $price, $cart_item ) {

		if ( $price && ! empty( $cart_item['wcpa_options_price_start'] ) ) {
			$price += $cart_item['wcpa_options_price_start'];
		}

		return $price;
	}
}
