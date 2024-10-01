<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Services;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\PricingRulesDispatcher;
use WC_Product;

class ProductPricingService {
	
	use ServiceContainerTrait;
	
	protected $priceCache = array(
		'price'         => array(),
		'sale_price'    => array(),
		'regular_price' => array(),
	);
	
	public function __construct() {
		
		add_filter( 'woocommerce_product_get_regular_price', array(
			$this,
			'adjustRegularPrice',
		), 99, 2 );
		
		add_filter( 'woocommerce_product_get_sale_price', array(
			$this,
			'adjustSalePrice',
		), 99, 2 );
		
		add_filter( 'woocommerce_product_get_price', array(
			$this,
			'adjustPrice',
		), 99, 2 );
		
		// Variations
		add_filter( 'woocommerce_product_variation_get_regular_price', array(
			$this,
			'adjustRegularPrice',
		), 99, 2 );
		
		add_filter( 'woocommerce_product_variation_get_sale_price', array(
			$this,
			'adjustSalePrice',
		), 99, 2 );
		
		add_filter( 'woocommerce_product_variation_get_price', array(
			$this,
			'adjustPrice',
		), 99, 2 );
		
		// Variable (price range)
		add_filter( 'woocommerce_variation_prices_price', array( $this, 'adjustPrice' ), 99, 3 );
		
		// Variation
		add_filter( 'woocommerce_variation_prices_regular_price', array(
			$this,
			'adjustRegularPrice',
		), 99, 3 );
		
		add_filter( 'woocommerce_variation_prices_sale_price', array(
			$this,
			'adjustSalePrice',
		), 99, 3 );
		
		// Price caching
		add_filter( 'woocommerce_get_variation_prices_hash',
			function ( $hash, \WC_Product_Variable $product, $forDisplay ) {
				
				$user = wp_get_current_user();
				
				$hash[] = json_encode( $product->get_category_ids() );
				
				if ( $user ) {
					$hash[] = md5( json_encode( $user->roles ) );
					$hash[] = $user->ID;
				}
				
				return $hash;
				
			}, 99, 3 );
		
		
		add_action( 'woocommerce_before_calculate_totals', function ( \WC_Cart $cart ) {
			if ( ! empty( $cart->cart_contents ) ) {
				
				foreach ( $cart->cart_contents as $key => $cartItem ) {
					
					if ( $cartItem['data'] instanceof WC_Product ) {
						$productId = ! empty( $cartItem['variation_id'] ) ? $cartItem['variation_id'] : $cartItem['product_id'];
						
						$price = $this->adjustPrice( false, wc_get_product( $productId ) );
						
						if ( false !== $price ) {
							
							$price = apply_filters( 'role_customer_specific_pricing/pricing/price_in_cart', $price,
								$cartItem, $key );
							
							$cartItem['data']->set_price( $price );
							$cartItem['data']->add_meta_data( 'rcbp_price_in_cart_recalculated', 'yes' );
						}
					}
				}
			}
		}, 10, 3 );
		
	}
	
	public function adjustPrice( $price, WC_Product $product ) {
		
		// Price already recalculated in the cart
		if ( $product->get_meta( 'rcbp_price_in_cart_recalculated' ) === 'yes' ) {
			return $price;
		}
		
		if ( $product->get_meta( 'addons_instance' ) === 'yes' ) {
			return $price;
		}
		
		if ( array_key_exists( $product->get_id(), $this->priceCache['price'] ) ) {
			
			if ( $this->priceCache['price'][ $product->get_id() ] === 'no_pricing_rule' ) {
				return $price;
			}
			
			$adjustedPrice = $this->priceCache['price'][ $product->get_id() ];
		} else {
			$pricingRule = PricingRulesDispatcher::dispatchRule( $product->get_id() );
			
			if ( $pricingRule ) {
				$adjustedPrice = $pricingRule->getPrice();
				
				if ( $adjustedPrice ) {
					$this->priceCache['price'][ $product->get_id() ] = $adjustedPrice;
				}
			} else {
				$this->priceCache['price'][ $product->get_id() ] = 'no_pricing_rule';
				
				return $price;
			}
		}
		
		/**
		 * Adjusted product price
		 *
		 * @since 1.6.0
		 */
		return apply_filters( 'role_customer_specific_pricing/pricing/adjusted_price', $adjustedPrice, $product );
	}
	
	public function adjustSalePrice( $price, WC_Product $product ) {
		
		if ( array_key_exists( $product->get_id(), $this->priceCache['sale_price'] ) ) {
			return $this->priceCache['sale_price'][ $product->get_id() ];
		}
		
		$pricingRule = PricingRulesDispatcher::dispatchRule( $product->get_id() );
		
		if ( $pricingRule ) {
			if ( $pricingRule->getPriceType() === 'flat' && $pricingRule->getSalePrice() ) {
				$price = $pricingRule->getSalePrice();
			} else {
				$price = $pricingRule->getPrice();
			}
		}
		
		$this->priceCache['sale_price'][ $product->get_id() ] = $price;
		
		return $price;
	}
	
	public function adjustRegularPrice( $price, WC_Product $product ) {
		
		if ( array_key_exists( $product->get_id(), $this->priceCache['regular_price'] ) ) {
			return $this->priceCache['regular_price'][ $product->get_id() ];
		}
		
		$pricingRule = PricingRulesDispatcher::dispatchRule( $product->get_id() );
		
		if ( $pricingRule ) {
			
			if ( $pricingRule->getPriceType() === 'flat' && $pricingRule->getRegularPrice() ) {
				$price = $pricingRule->getRegularPrice();
			} elseif ( $pricingRule->getPriceType() !== 'percentage' || $this->getContainer()->getSettings()->getPercentageBasedRulesBehavior() !== 'sale_price' ) {
				// Do no modify regular price if "sale_price" chosen
				$price = $pricingRule->getPrice();
			}
		}
		
		$this->priceCache['regular_price'][ $product->get_id() ] = $price;
		
		return $price;
	}
}
