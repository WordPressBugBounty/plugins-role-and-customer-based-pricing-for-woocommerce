<?php namespace MeowCrew\RoleAndCustomerBasedPricing;

use MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage\PricingRulesManager;
use \MeowCrew\RoleAndCustomerBasedPricing\Entity\PricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\RoleSpecificPricingCPT;
use WP_User;

class PricingRulesDispatcher {
	
	/**
	 * Dispatched rules. Used for the cache
	 *
	 * @var PricingRule[]
	 */
	protected static $dispatchedRules = array();
	
	/**
	 * Wrapper for the main dispatch function to provide the hook for 3rd-party devs
	 *
	 * @param  int  $productId
	 * @param  null  $parentId
	 * @param  null  $user
	 * @param  bool  $validatePricing
	 *
	 * @return false|PricingRule
	 */
	public static function dispatchRule( $productId, $parentId = null, $user = null, $validatePricing = true ) {
		$dispatchedRule = self::_dispatchRule( $productId, $parentId, $user, $validatePricing );
		
		return apply_filters( 'role_customer_specific_pricing/pricing_rules_dispatcher/dispatched_rule',
			$dispatchedRule, $productId, $parentId, $user, $validatePricing, self::$dispatchedRules );
	}
	
	
	/**
	 * The main method to get applied rule for a product
	 *
	 * @param  int  $productId
	 * @param  null  $parentId
	 * @param  null  $user
	 * @param  bool  $validatePricing
	 *
	 * @return false|PricingRule
	 */
	protected static function _dispatchRule( $productId, $parentId = null, $user = null, $validatePricing = true ) {
		
		$cacheKey = $productId . '_' . $validatePricing ? 1 : 0;
		
		// Cache
		if ( array_key_exists( $productId . $validatePricing, self::$dispatchedRules ) ) {
			return self::$dispatchedRules[ $cacheKey ];
		}
		
		$product = wc_get_product( $productId );
		
		if ( ! $product || ! $product->is_type( array(
				'variation',
				'simple',
				'subscription',
				'subscription-variation',
				'course',
			) ) ) {
			
			return false;
		}
		
		$parentId = $parentId ? $parentId : $product->get_parent_id();
		$user     = $user instanceof WP_User ? $user : wp_get_current_user();
		
		if ( ! $user ) {
			$user = new WP_User( 0 );
		}
		
		$customerSpecificRules = PricingRulesManager::getProductCustomerSpecificPricingRules( $productId,
			$validatePricing );
		
		if ( empty( $customerSpecificRules ) && $product->get_type() === 'variation' ) {
			$customerSpecificRules = PricingRulesManager::getProductCustomerSpecificPricingRules( $parentId,
				$validatePricing );
		}
		
		foreach ( $customerSpecificRules as $userId => $rule ) {
			if ( intval( $userId ) === $user->ID ) {
				self::$dispatchedRules[ $cacheKey ] = $rule;
				
				return $rule;
			}
		}
		
		$roleSpecificRules = PricingRulesManager::getProductRoleSpecificPricingRules( $productId, $validatePricing );
		
		if ( empty( $roleSpecificRules ) && $product->get_type() === 'variation' ) {
			$roleSpecificRules = PricingRulesManager::getProductRoleSpecificPricingRules( $parentId, $validatePricing );
		}
		
		foreach ( $roleSpecificRules as $role => $rule ) {
			if ( in_array( $role, $user->roles ) ) {
				// in case there is a variation. By default, rule is tied to the parent product
				$rule->setProductId( $productId );
				
				self::$dispatchedRules[ $cacheKey ] = $rule;
				
				return $rule;
			}
		}
		
		// role-and-customer-based-pricing-for-woocommerce: make it as a generator to save performance
		$globalRules = RoleSpecificPricingCPT::getGlobalRules( $validatePricing );
		
		foreach ( $globalRules as $rule ) {
			
			if ( $rule->matchRequirements( $user, $product ) ) {
				
				$rule->setAppliedProductId( $productId );
				
				$rule->setOriginalProductPrice( floatval( $product->get_price( 'edit' ) ) );
				
				self::$dispatchedRules[ $cacheKey ] = $rule;
				
				return $rule;
			}
		}
		
		self::$dispatchedRules[ $cacheKey ] = false;
		
		return false;
	}
	
}
