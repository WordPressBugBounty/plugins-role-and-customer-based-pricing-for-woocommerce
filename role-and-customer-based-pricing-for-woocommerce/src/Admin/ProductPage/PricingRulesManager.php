<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage;

use Exception;
use MeowCrew\RoleAndCustomerBasedPricing\Core\Logger;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\PricingRule;

class PricingRulesManager {

	const PRODUCT_ROLE_SPECIFIC_PRICING_RULES_KEY = '_role_based_pricing_rules';
	const PRODUCT_CUSTOMER_SPECIFIC_PRICING_RULES_KEY = '_customer_based_pricing_rules';

	/**
	 * Get product related role-based pricing rules
	 *
	 * @param int $productId
	 * @param bool $filterValidPricing
	 *
	 * @return PricingRule[]
	 */
	public static function getProductRoleSpecificPricingRules( $productId, $filterValidPricing = true ) {
		return self::getProductPricingRules( $productId, 'role', $filterValidPricing );
	}

	/**
	 * Get product related customer-based pricing rules
	 *
	 * @param int $productId
	 * @param bool $filterValidPricing
	 *
	 * @return PricingRule[]
	 */
	public static function getProductCustomerSpecificPricingRules( $productId, $filterValidPricing = true ) {
		return self::getProductPricingRules( $productId, 'customer', $filterValidPricing );
	}

	/**
	 * Get pricing rule related to a product
	 *
	 * @param int $productId
	 * @param string $type
	 * @param bool $filterValidPricing
	 *
	 * @return array
	 */
	public static function getProductPricingRules( $productId, $type, $filterValidPricing = true ) {

		$key = 'role' === $type ? self::PRODUCT_ROLE_SPECIFIC_PRICING_RULES_KEY : self::PRODUCT_CUSTOMER_SPECIFIC_PRICING_RULES_KEY;

		$rules = get_post_meta( $productId, $key, true );

		$pricingRules = array();

		if ( ! empty( $rules ) && is_array( $rules ) ) {

			foreach ( $rules as $identifier => $rule ) {
				try {
					$pricingRule = PricingRule::fromArray( $rule, $productId );

					// Skip pricing rules with invalid pricing
					if ( $filterValidPricing && ! $pricingRule->isValidPricing() ) {
						continue;
					}

					$pricingRules[ $identifier ] = $pricingRule;

				} catch ( Exception $e ) {
					$logger = new Logger();

					$logger->log( $e->getMessage(), Logger::ERROR__LEVEL );
				}
			}
		}

		return apply_filters( 'role_customer_specific_pricing/pricing_rules/pricing_rules', $pricingRules, $productId, $type, $filterValidPricing );
	}

	public static function updateProductCustomerSpecificPricingRules( $productId, array $rules ) {
		self::updateProductPricingRules( $productId, $rules, 'customer' );
	}

	public static function updateProductRoleSpecificPricingRules( $productId, array $rules ) {
		self::updateProductPricingRules( $productId, $rules, 'role' );
	}

	public static function updateProductPricingRules( $productId, array $rules, $type ) {

		$key = 'role' === $type ? self::PRODUCT_ROLE_SPECIFIC_PRICING_RULES_KEY : self::PRODUCT_CUSTOMER_SPECIFIC_PRICING_RULES_KEY;

		$data = array();

		foreach ( $rules as $identifier => $rule ) {
			if ( $rule instanceof PricingRule ) {
				$data[ $identifier ] = $rule->asArray();
			}
		}

		update_post_meta( $productId, $key, $data );
	}
}
