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
	
	/**
	 * Whether the current request manages the store rather than shops in it
	 *
	 * @var bool|null
	 */
	private $isManagementContext = null;
	
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
				$hash[] = $this->isManagementContext() ? 'management' : 'shop';
				
				if ( $user ) {
					$hash[] = md5( json_encode( $user->roles ) );
					$hash[] = $user->ID;
				}
				
				return $hash;
				
			}, 99, 3 );
		
		
		add_action( 'woocommerce_before_calculate_totals', function ( \WC_Cart $cart ) {
			if ( $this->isManagementContext() ) {
				return;
			}
			
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
		
		if ( $this->isManagementContext() ) {
			return $price;
		}
		
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
			
			$adjustedPrice = $pricingRule ? $pricingRule->getPrice() : null;
			
			// No rule, or the rule cannot produce a price (the product has no price to discount)
			if ( null === $adjustedPrice ) {
				$this->priceCache['price'][ $product->get_id() ] = 'no_pricing_rule';
				
				return $price;
			}
			
			$this->priceCache['price'][ $product->get_id() ] = $adjustedPrice;
		}
		
		/**
		 * Adjusted product price
		 *
		 * @since 1.6.0
		 */
		return apply_filters( 'role_customer_specific_pricing/pricing/adjusted_price', $adjustedPrice, $product );
	}
	
	public function adjustSalePrice( $price, WC_Product $product ) {
		
		if ( $this->isManagementContext() ) {
			return $price;
		}
		
		if ( array_key_exists( $product->get_id(), $this->priceCache['sale_price'] ) ) {
			return $this->priceCache['sale_price'][ $product->get_id() ];
		}
		
		$pricingRule = PricingRulesDispatcher::dispatchRule( $product->get_id() );
		
		if ( $pricingRule ) {
			if ( $pricingRule->getPriceType() === 'flat' && $pricingRule->getSalePrice() ) {
				$price = $pricingRule->getSalePrice();
			} else {
				$rulePrice = $pricingRule->getPrice();
				
				if ( null !== $rulePrice ) {
					$price = $rulePrice;
				}
			}
		}
		
		$this->priceCache['sale_price'][ $product->get_id() ] = $price;
		
		return $price;
	}
	
	public function adjustRegularPrice( $price, WC_Product $product ) {
		
		if ( $this->isManagementContext() ) {
			return $price;
		}
		
		if ( array_key_exists( $product->get_id(), $this->priceCache['regular_price'] ) ) {
			return $this->priceCache['regular_price'][ $product->get_id() ];
		}
		
		$pricingRule = PricingRulesDispatcher::dispatchRule( $product->get_id() );
		
		if ( $pricingRule ) {
			
			if ( $pricingRule->getPriceType() === 'flat' && $pricingRule->getRegularPrice() ) {
				$price = $pricingRule->getRegularPrice();
			} elseif ( $pricingRule->getPriceType() !== 'percentage' || $this->getContainer()->getSettings()->getPercentageBasedRulesBehavior() !== 'sale_price' ) {
				// Do no modify regular price if "sale_price" chosen
				$rulePrice = $pricingRule->getPrice();
				
				if ( null !== $rulePrice ) {
					$price = $rulePrice;
				}
			}
		}
		
		$this->priceCache['regular_price'][ $product->get_id() ] = $price;
		
		return $price;
	}
	
	/**
	 * Whether the current request manages the store rather than shops in it.
	 *
	 * Role and customer prices are meant for shoppers. In wp-admin screens, in admin AJAX started from a
	 * wp-admin screen (quick edit, variations, CSV export), in the REST API outside the Store API and in
	 * WP-CLI the real product prices are returned, so editing and exporting never persist adjusted prices.
	 *
	 * @return bool
	 */
	public function isManagementContext() {
		
		if ( null === $this->isManagementContext ) {
			$this->isManagementContext = (bool) apply_filters( 'role_customer_specific_pricing/pricing/is_management_context',
				$this->detectManagementContext() );
		}
		
		return $this->isManagementContext;
	}
	
	protected function detectManagementContext() {
		
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			// The Store API serves shoppers, every other REST namespace manages the store
			return false === strpos( $this->getRequestRoute(), 'wc/store' );
		}
		
		if ( ! is_admin() ) {
			return false;
		}
		
		if ( ! wp_doing_ajax() ) {
			return true;
		}
		
		// Admin AJAX: only requests started from a wp-admin screen manage the store. Frontend AJAX (add to cart, quick view) keeps shopper prices.
		$referer = wp_get_raw_referer();
		
		return $referer && false !== strpos( $referer, '/wp-admin/' );
	}
	
	protected function getRequestRoute() {
		
		if ( ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			return (string) $GLOBALS['wp']->query_vars['rest_route'];
		}
		
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['rest_route'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_text_field( wp_unslash( $_GET['rest_route'] ) );
		}
		
		return isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	}
}
