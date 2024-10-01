<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins;

class SmartCoupons {
	
	public function __construct() {
		
		add_action( 'role_customer_specific_pricing/pricing/price_in_cart', function ( $price, $cartItem, $key ) {
			
			if ( empty( $cartItem['wc_sc_product_source'] ) ) {
				return $price;
			}
			
			if ( ! class_exists( '\WC_SC_Coupon_Actions' ) ) {
				return $price;
			}
			
			if ( ! ( $cartItem['data'] instanceof \WC_Product ) ) {
				return $price;
			}
			
			$coupons = \WC_SC_Coupon_Actions::get_instance();
			
			$cartItem = $coupons->modify_cart_item_data( $cartItem, $cartItem['product_id'], $cartItem['variation_id'],
				$cartItem['quantity'] );
			
			$cartItem['data']->add_meta_data( 'rcbp_price_in_cart_recalculated', 'yes' );
			
			return $cartItem['data']->get_price();
			
		}, 10, 3 );
	}
}