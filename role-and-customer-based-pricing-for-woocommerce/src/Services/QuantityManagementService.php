<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Services;

use MeowCrew\RoleAndCustomerBasedPricing\PricingRulesDispatcher;
use WC_Product_Simple;

class QuantityManagementService {
	
	public function __construct() {
		
		add_action( 'woocommerce_before_calculate_totals', function ( \WC_Cart $cart ) {
			foreach ( $cart->get_cart_contents() as $cartItemKey => $cartItem ) {
				if ( $cartItem['data'] instanceof \WC_Product ) {
					$productId = ! empty( $cartItem['variation_id'] ) ? $cartItem['variation_id'] : $cartItem['product_id'];
					
					$pricingRule = PricingRulesDispatcher::dispatchRule( $productId, null, null, false );
					
					if ( $pricingRule ) {
						$min     = $pricingRule->getMinimum();
						$max     = $pricingRule->getMaximum();
						$groupOf = $pricingRule->getGroupOf();
						
						$itemQuantity = $cartItem['quantity'];
						
						if ( $min && $itemQuantity < $min ) {
							$cart->cart_contents[ $cartItemKey ]['quantity'] = $min;
							// translators: %1$s: item name, %2$s: minimum quantity
							wc_add_notice( sprintf( __( 'Minimum quantity for the %1$s is %2$d',
								'role-and-customer-based-pricing-for-woocommerce' ), $cartItem['data']->get_name(),
								$min ), 'error' );
						}
						
						if ( $max && $itemQuantity > $max ) {
							$cart->cart_contents[ $cartItemKey ]['quantity'] = $max;
							// translators: %1$s: item name, %2$s: minimum quantity
							wc_add_notice( sprintf( __( 'Maximum quantity for the %1$s is %2$d',
								'role-and-customer-based-pricing-for-woocommerce' ), $cartItem['data']->get_name(),
								$max ), 'error' );
						}
						
						if ( $groupOf && $itemQuantity % $groupOf !== 0 ) {
							$cart->cart_contents[ $cartItemKey ]['quantity'] = $groupOf;
							// translators: %1$s: item name, %2$s: minimum quantity
							wc_add_notice( sprintf( __( 'Quantity for the %1$s must must be multiple of %2$d',
								'role-and-customer-based-pricing-for-woocommerce' ), $cartItem['data']->get_name(),
								$groupOf ), 'error' );
						}
					}
				}
			}
		} );
		
		add_filter( 'woocommerce_quantity_input_args', function ( $args ) {
			
			global $product;
			
			if ( $product && $product instanceof WC_Product_Simple ) {
				
				$pricingRule = PricingRulesDispatcher::dispatchRule( $product->get_id(), null, null, false );
				
				if ( $pricingRule ) {
					
					$min     = $pricingRule->getMinimum();
					$max     = $pricingRule->getMaximum();
					$groupOf = $pricingRule->getGroupOf();
					
					if ( '' !== $min ) {
						$min = max( 1, $min - $this->getProductCartQuantity( $product->get_id() ) );
						$min = max( $groupOf, $min );
						
						$args['min_value'] = $min;
					}
					
					if ( $max ) {
						$max = max( 1, $max - $this->getProductCartQuantity( $product->get_id() ) );
						$max = max( $groupOf, $max );
						
						$args['max_value'] = $max;
					}
					
					
					if ( $groupOf ) {
						$args['step'] = $groupOf;
					}
				}
			}
			
			return $args;
		}, 999999 );
		
		add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $productId, $qty, $variationId = null ) {
			
			// Do not show additional notices if there are already notices
			if ( ! $passed ) {
				return false;
			}
			
			$productId   = $variationId ? $variationId : $productId;
			$pricingRule = PricingRulesDispatcher::dispatchRule( $productId, null, null, false );
			
			if ( $pricingRule ) {
				
				$min     = $pricingRule->getMinimum();
				$max     = $pricingRule->getMaximum();
				$groupOf = $pricingRule->getGroupOf();
				
				if ( $min ) {
					$min = max( 1, $min - $this->getProductCartQuantity( $productId ) );
					
					if ( $groupOf ) {
						$min = max( $groupOf, $min );
					}
					
					if ( $qty < $min ) {
						// translators: %s: minimum quantity
						wc_add_notice( sprintf( 'Minimum order quantity for the product is %s', $min ), 'error' );
						
						return false;
					}
				}
				
				if ( $max ) {
					
					$max = max( 1, $max - $this->getProductCartQuantity( $productId ) );
					$max = max( $groupOf, $max );
					
					if ( $qty > $max ) {
						
						// translators: %s: maximum quantity
						wc_add_notice( sprintf( 'Maximum order quantity for the product is %s', $max ), 'error' );
						
						return false;
					}
				}
				
				if ( $groupOf ) {
					
					if ( 0 !== $qty % $groupOf ) {
						// translators: %s: quantity step
						wc_add_notice( sprintf( 'Order quantity must be multiple for %s', $groupOf ), 'error' );
						
						return false;
					}
				}
			}
			
			
			return $passed;
			
		}, 10, 4 );
		
		add_action( 'wp_head', function () {
			if ( is_product() ) {
				?>
				<script>
					jQuery(document).ready(function () {

						let $quantity = jQuery('.single_variation_wrap').find('[name=quantity]');

						jQuery(document).on('found_variation', function (e, variation) {

							if (variation.step) {
								$quantity.attr('step', variation.step);
								$quantity.data('step', variation.step);
							} else {
								$quantity.attr('step', 1);
								$quantity.data('step', 1);
							}
						});

						jQuery(document).on('reset_data', function (e, variation) {
							$quantity.removeAttr('step');
							$quantity.removeAttr('max');
							$quantity.removeAttr('min');
						});
					});
				</script>
				
				<?php
			}
		} );
		
		add_filter( 'woocommerce_update_cart_validation', function ( $passed, $cart_item_key, $values, $quantity ) {
			
			$productId   = $values['variation_id'] ? $values['variation_id'] : $values['product_id'];
			$pricingRule = PricingRulesDispatcher::dispatchRule( $productId, null, null, false );
			
			if ( $pricingRule ) {
				
				$min     = $pricingRule->getMinimum();
				$max     = $pricingRule->getMaximum();
				$groupOf = $pricingRule->getGroupOf();
				
				if ( $min ) {
					
					if ( $quantity < $min ) {
						
						// translators: %s: minimum quantity
						wc_add_notice( sprintf( __( 'Minimum order quantity for the product is %s',
							'role-and-customer-based-pricing-for-woocommerce' ), $min ), 'error' );
						
						return false;
					}
				}
				
				if ( $max ) {
					
					if ( $quantity > $max ) {
						
						// translators: %s: maximum quantity
						wc_add_notice( sprintf( __( 'Maximum order quantity for the product is %s',
							'role-and-customer-based-pricing-for-woocommerce' ), $max ), 'error' );
						
						return false;
					}
				}
				
				if ( $groupOf ) {
					
					if ( 0 !== $quantity % $groupOf ) {
						
						// translators: %s: quantity step
						wc_add_notice( sprintf( __( 'Order quantity must be multiple for %s',
							'role-and-customer-based-pricing-for-woocommerce' ), $groupOf ), 'error' );
						
						return false;
					}
				}
			}
			
			return $passed;
			
		}, 10, 4 );
		
		add_filter( 'woocommerce_available_variation', function ( $variation ) {
			
			$pricingRule = PricingRulesDispatcher::dispatchRule( $variation['variation_id'], null, null, false );
			
			if ( $pricingRule ) {
				
				$min     = $pricingRule->getMinimum();
				$max     = $pricingRule->getMaximum();
				$groupOf = $pricingRule->getGroupOf();
				
				if ( $min ) {
					$min = max( 1, $min - $this->getProductCartQuantity( $variation['variation_id'] ) );
					
					if ( $groupOf ) {
						$min = max( $groupOf, $min );
					}
					
					$variation['min_qty']   = $min;
					$variation['qty_value'] = $min;
				}
				
				if ( $max ) {
					$max = max( 1, $max - $this->getProductCartQuantity( $variation['variation_id'] ) );
					
					if ( $groupOf ) {
						$max = max( $groupOf, $max );
					}
					
					$variation['max_qty'] = $max;
				}
				
				if ( $groupOf ) {
					$variation['step'] = $groupOf;
				}
			}
			
			return $variation;
		}, 999 );
	}
	
	public function getProductCartQuantity( $product_id ) {
		$qty = 0;
		
		if ( is_array( wc()->cart->cart_contents ) ) {
			foreach ( wc()->cart->cart_contents as $cart_content ) {
				if ( $cart_content['product_id'] == $product_id ) {
					$qty += $cart_content['quantity'];
				}
			}
		}
		
		return $qty;
	}
}
