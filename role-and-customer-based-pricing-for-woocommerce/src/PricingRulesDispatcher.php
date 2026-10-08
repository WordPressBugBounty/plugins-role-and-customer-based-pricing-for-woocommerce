<?php namespace MeowCrew\RoleAndCustomerBasedPricing;

use MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage\PricingRulesManager;
use \MeowCrew\RoleAndCustomerBasedPricing\Entity\PricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\RoleSpecificPricingCPT;
use WP_User;

class PricingRulesDispatcher {
	
	/**
	 * Dispatched rules. Used for the cache
	 *
	 * @var array<string, PricingRule|false>
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
		
		$user = $user instanceof WP_User ? $user : wp_get_current_user();
		
		if ( ! $user ) {
			$user = new WP_User( 0 );
		}
		
		$cacheKey = $productId . '_' . ( $validatePricing ? 1 : 0 ) . '_' . $user->ID;
		
		// Cache
		if ( array_key_exists( $cacheKey, self::$dispatchedRules ) ) {
			return self::$dispatchedRules[ $cacheKey ];
		}
		
		$product = wc_get_product( $productId );
		
		if ( ! $product || ! $product->is_type( array(
				'variation',
				'simple',
				'course',
				'subscription',
				'subscription_variation',
			) ) ) {
			
			return false;
		}
		
		$parentId    = $parentId ? $parentId : $product->get_parent_id();
		$isVariation = $product->is_type( 'variation' );
		
		$customerSpecificRules = PricingRulesManager::getProductCustomerSpecificPricingRules( $productId,
			$validatePricing );
		
		if ( empty( $customerSpecificRules ) && $isVariation ) {
			$customerSpecificRules = PricingRulesManager::getProductCustomerSpecificPricingRules( $parentId,
				$validatePricing );
		}
		
		foreach ( $customerSpecificRules as $userId => $rule ) {
			if ( intval( $userId ) === $user->ID ) {
				// A rule inherited from the parent product must calculate prices from the variation
				$rule->setProductId( $productId );
				
				self::$dispatchedRules[ $cacheKey ] = $rule;
				
				return $rule;
			}
		}
		
		$roleSpecificRules = PricingRulesManager::getProductRoleSpecificPricingRules( $productId, $validatePricing );
		
		if ( empty( $roleSpecificRules ) && $isVariation ) {
			$roleSpecificRules = PricingRulesManager::getProductRoleSpecificPricingRules( $parentId, $validatePricing );
		}
		
		foreach ( $roleSpecificRules as $role => $rule ) {
			if ( in_array( $role, $user->roles ) ) {
				// A rule inherited from the parent product must calculate prices from the variation
				$rule->setProductId( $productId );
				
				self::$dispatchedRules[ $cacheKey ] = $rule;
				
				return $rule;
			}
		}
		
		$globalRules = RoleSpecificPricingCPT::getGlobalRules( $validatePricing );
		
		foreach ( $globalRules as $globalRule ) {
			
			if ( $globalRule->matchRequirements( $user, $product ) ) {
				
				// Global rule instances are shared between products within the request, so work on a copy
				$rule = clone $globalRule;
				
				$rule->setAppliedProductId( $productId );
				$rule->setOriginalProductPriceFromProduct( $product );
				
				self::$dispatchedRules[ $cacheKey ] = $rule;
				
				return $rule;
			}
		}
		
		self::$dispatchedRules[ $cacheKey ] = false;
		
		return false;
	}
	
	/**
	 * Drop the dispatched rules cache
	 */
	public static function resetCache() {
		self::$dispatchedRules = array();
	}
}
