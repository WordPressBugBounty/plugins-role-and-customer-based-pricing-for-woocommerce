<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins;

use SW_WAPF\Includes\Classes\Fields;

class WombatProductAddons {

	public function __construct() {
		add_filter( 'role_customer_specific_pricing/pricing/price_in_cart', array( $this, 'addAddonsPriceToItem' ), 20, 2 );
	}

	public function addAddonsPriceToItem( $price, $cart_item ) {
		
		if ( ! $price ) {
			return $price;
		}
		
		// Premium version
		if ( ! empty( $cart_item['wapf_item_price']['options_total'] ) ) {
			$price += $cart_item['wapf_item_price']['options_total'];
		}
		
		// Free version
		if ( class_exists( 'SW_WAPF\Includes\Classes\Fields' ) && ! empty( $cart_item['wapf'] ) ) {
			$optionsTotal = 0;
			
			foreach ( $cart_item['wapf'] as $field ) {
				if ( ! empty( $field['price'] ) ) {
					foreach ( $field['price'] as $_price ) {
						
						if ( 0 === $_price['value'] ) {
							continue;
						}
						
						$optionsTotal = $optionsTotal + Fields::do_pricing( $_price['value'], $cart_item['quantity'] );
					}
				}
			}
			
			$price += $optionsTotal;
		}
		
		return $price;
	}
}
