<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Services\Import;

use MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage\PricingRulesManager;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\PricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\Utils\Strings;
use WC_Product;

class WooCommerce {
	
	/**
	 * Import constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'addColumnsToImporter' ) );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns',
			array( $this, 'addColumnToMappingScreen' ) );
		
		add_filter( 'woocommerce_product_import_inserted_product_object', array( $this, 'processImport' ), 10, 2 );
	}
	
	public function addColumnsToImporter( $columns ) {
		return array_merge( $columns, $this->getPluginColumns() );
	}
	
	public function addColumnToMappingScreen( $columns ) {
		return array_merge( array_flip( $this->getPluginColumns() ), $columns );
	}
	
	public function processImport( WC_Product $product, $data ) {
		$columnsToImport = array_keys( $this->getPluginColumns() );
		
		$roleBasedRules        = array();
		$customerBasedRules    = array();
		$hasCustomerBasedRules = false;
		$hasRoleBasedRules     = false;
		
		foreach ( $data as $importedKey => $importedValue ) {
			if ( in_array( $importedKey, $columnsToImport ) ) {
				
				if ( Strings::startsWith( $importedKey, 'rcbp_role_based' ) ) {
					$rules             = &$roleBasedRules;
					$hasRoleBasedRules = true;
				} else {
					$rules                 = &$customerBasedRules;
					$hasCustomerBasedRules = true;
				}
				
				// Pricing type
				if ( Strings::endsWith( $importedKey, 'pricing_type' ) ) {
					$types = explode( ';', $importedValue );
					
					foreach ( $types as $type ) {
						$typeData = explode( ':', $type );
						
						$identifier = isset( $typeData[0] ) ? $typeData[0] : false;
						
						if ( $identifier ) {
							
							$type = isset( $typeData[1] ) ? $typeData[1] : '';
							
							$rules[ $identifier ]['pricing_type'] = in_array( $type, array(
								'flat',
								'percentage',
							) ) ? $type : 'none';
						}
					}
				}
				
				// Discount
				if ( Strings::endsWith( $importedKey, 'discount' ) ) {
					$discounts = explode( ';', $importedValue );
					
					foreach ( $discounts as $discount ) {
						
						$discountData = explode( ':', $discount );
						
						$identifier = isset( $discountData[0] ) ? $discountData[0] : false;
						
						if ( $identifier ) {
							
							$discount = isset( $discountData[1] ) ? $discountData[1] : '';
							
							$rules[ $identifier ]['discount'] = floatval( $discount );
						}
					}
				}
				
				// Regular price
				if ( Strings::endsWith( $importedKey, 'regular_price' ) ) {
					$regularPrices = explode( ';', $importedValue );
					
					foreach ( $regularPrices as $regularPrice ) {
						
						$regularPriceData = explode( ':', $regularPrice );
						
						$identifier = isset( $regularPriceData[0] ) ? $regularPriceData[0] : false;
						
						if ( $identifier ) {
							$regularPrice                          = isset( $regularPriceData[1] ) ? $regularPriceData[1] : '';
							$rules[ $identifier ]['regular_price'] = wc_format_decimal( $regularPrice );
						}
					}
				}
				
				// Sale price
				if ( Strings::endsWith( $importedKey, 'sale_price' ) ) {
					$salePrices = explode( ';', $importedValue );
					
					foreach ( $salePrices as $salePrice ) {
						
						$salePriceData = explode( ':', $salePrice );
						
						$identifier = isset( $salePriceData[0] ) ? $salePriceData[0] : false;
						
						if ( $identifier ) {
							$salePrice                          = isset( $salePriceData[1] ) ? $salePriceData[1] : '';
							$rules[ $identifier ]['sale_price'] = wc_format_decimal( $salePrice );
						}
					}
				}
				
				// Minimum quantity
				if ( Strings::endsWith( $importedKey, 'minimum_quantity' ) ) {
					$minimumQuantities = explode( ';', $importedValue );
					
					foreach ( $minimumQuantities as $quantity ) {
						
						$minimumQuantityData = explode( ':', $quantity );
						
						$identifier = isset( $minimumQuantityData[0] ) ? $minimumQuantityData[0] : false;
						
						if ( $identifier ) {
							$quantity = isset( $minimumQuantityData[1] ) ? $minimumQuantityData[1] : '';
							
							$rules[ $identifier ]['minimum'] = intval( $quantity );
						}
					}
				}
				
				// Maximum quantity
				if ( Strings::endsWith( $importedKey, 'maximum_quantity' ) ) {
					$maximumQuantities = explode( ';', $importedValue );
					
					foreach ( $maximumQuantities as $quantity ) {
						
						$maximumQuantityData = explode( ':', $quantity );
						
						$identifier = isset( $maximumQuantityData[0] ) ? $maximumQuantityData[0] : false;
						
						if ( $identifier ) {
							$quantity = isset( $maximumQuantityData[1] ) ? $maximumQuantityData[1] : '';
							
							$rules[ $identifier ]['maximum'] = intval( $quantity );
						}
					}
				}
				
				// Maximum quantity
				if ( Strings::endsWith( $importedKey, 'quantity_step' ) ) {
					$stepOfs = explode( ';', $importedValue );
					
					foreach ( $stepOfs as $stepOf ) {
						
						$stepOfsData = explode( ':', $stepOf );
						
						$identifier = isset( $stepOfsData[0] ) ? $stepOfsData[0] : false;
						
						if ( $identifier ) {
							$stepOf                           = isset( $stepOfsData[1] ) ? $stepOfsData[1] : '';
							$rules[ $identifier ]['group_of'] = intval( $stepOf );
						}
					}
				}
				
			}
		}
		
		if ( $hasCustomerBasedRules ) {
			
			$customerBasedRules = array_filter( $customerBasedRules, function ( $rule ) {
				return in_array( $rule['pricing_type'], array( 'flat', 'percentage' ) );
			} );
			
			$customerBasedRules = array_map( function ( $rule ) {
				return PricingRule::fromArray( $rule );
			}, $customerBasedRules );
			
			PricingRulesManager::updateProductCustomerSpecificPricingRules( $product->get_id(), $customerBasedRules );
		}
		
		if ( $hasRoleBasedRules ) {
			$roleBasedRules = array_filter( $roleBasedRules, function ( $rule ) {
				return in_array( $rule['pricing_type'], array( 'flat', 'percentage' ) );
			} );
			
			$roleBasedRules = array_map( function ( $rule ) {
				return PricingRule::fromArray( $rule );
			}, $roleBasedRules );
			
			PricingRulesManager::updateProductRoleSpecificPricingRules( $product->get_id(), $roleBasedRules );
		}
		
		return $product;
	}
	
	public function getPluginColumns() {
		
		$columns['rcbp_role_based_pricing_type']  = __( 'Role-based pricing type',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_role_based_discount']      = __( 'Role-based discount',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_role_based_regular_price'] = __( 'Role-based regular price',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_role_based_sale_price']    = __( 'Role-based sale price',
			'role-and-customer-based-pricing-for-woocommerce' );
		
		$columns['rcbp_role_based_minimum_quantity'] = __( 'Role-based minimum quantity',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_role_based_maximum_quantity'] = __( 'Role-based maximum quantity',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_role_based_quantity_step']    = __( 'Role-based quantity step',
			'role-and-customer-based-pricing-for-woocommerce' );
		
		$columns['rcbp_customer_based_pricing_type']  = __( 'Customer-based pricing type',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_customer_based_discount']      = __( 'Customer-based discount',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_customer_based_regular_price'] = __( 'Customer-based regular price',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_customer_based_sale_price']    = __( 'Customer-based sale price',
			'role-and-customer-based-pricing-for-woocommerce' );
		
		$columns['rcbp_customer_based_minimum_quantity'] = __( 'Customer-based minimum quantity',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_customer_based_maximum_quantity'] = __( 'Customer-based maximum quantity',
			'role-and-customer-based-pricing-for-woocommerce' );
		$columns['rcbp_customer_based_quantity_step']    = __( 'Customer-based quantity step',
			'role-and-customer-based-pricing-for-woocommerce' );
		
		return $columns;
	}
	
}
