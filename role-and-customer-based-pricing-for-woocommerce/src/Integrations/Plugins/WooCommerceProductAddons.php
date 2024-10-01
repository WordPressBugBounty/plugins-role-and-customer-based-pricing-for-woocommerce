<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Integrations\Plugins;

class WooCommerceProductAddons {
	public function __construct() {
		add_action( 'role_customer_specific_pricing/pricing/price_in_cart', array( $this, 'addAddonsPrice' ), 10, 3 );
		
		add_filter( 'woocommerce_addons_cloned_product_with_filtered_price', function ( \WC_Product $product ) {
			$product->add_meta_data( 'addons_instance', 'yes' );
			
			return $product;
		} );
	}

	/**
	 * Add extra addons costs to product price in cart.
	 *
	 * @param  float  $price
	 * @param  array  $cart_item
	 *
	 * @return int|mixed
	 */
	public function addAddonsPrice( $price, $cart_item ) {

		$extra_cost = 0;

		if ( isset( $cart_item['addons'] ) && false !== $price ) {
			foreach ( $cart_item['addons'] as $addon ) {
				$price_type  = $addon['price_type'];
				$addon_price = $addon['price'];

				switch ( $price_type ) {

					case 'percentage_based':
						$extra_cost += $price * ( $addon_price / 100 );
						break;
					case 'flat_fee':
						$extra_cost += (float) ( $addon_price / $cart_item['quantity'] );
						break;
					default:
						$extra_cost += (float) $addon_price;
						break;
				}
			}

			return $price + $extra_cost;
		}

		return $price;
	}
}