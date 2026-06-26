<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\Tabs;

use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\FormTab;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;

class ProductAndCategories extends FormTab {

	public function getId(): string {
		return 'products-and-categories';
	}

	public function getTitle(): string {
		return __( 'Products', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function getDescription(): string {
		return __( 'Select products or product categories the rule will work for.', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function render( GlobalPricingRule $pricingRule ) {

		$this->renderSectionTitle( __( 'Included Products', 'role-and-customer-based-pricing-for-woocommerce' ), array(
			'description' => __( 'Choose the products and/or categories to apply the pricing rule.', 'role-and-customer-based-pricing-for-woocommerce' ),
		) );

		if ( empty( $pricingRule->getIncludedProductCategories() ) && empty( $pricingRule->getIncludedProductTags() ) && empty( $pricingRule->getIncludedProductBrands() ) && empty( $pricingRule->getIncludedProducts() ) ) {
			$this->renderHint( __( 'If you do not specify products, categories, tags, or brands, the rule will apply to all products in your store.', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}

		$this->renderSelect2( array(
			'id'            => '_rps_included_categories',
			'label'         => __( 'Apply for categories', 'role-and-customer-based-pricing-for-woocommerce' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getIncludedProductCategories() as $categoryId ) {
					$category = get_term_by( 'id', $categoryId, 'product_cat' );
					
					if ( $category ) {
						$options[ $categoryId ] = $category->name;
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search for a category &hellip;', 'role-and-customer-based-pricing-for-woocommerce' ),
			'search_action' => 'woocommerce_json_search_rcbp_categories',
			'description'   => __( 'Choose the categories for which this pricing rule will apply. The rule applies to all products in the category.', 'role-and-customer-based-pricing-for-woocommerce' )
		) );

		$this->renderSelect2( array(
			'id'            => '_rps_included_tags',
			'label'         => __( 'Apply for tags', 'role-and-customer-based-pricing-for-woocommerce' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getIncludedProductTags() as $tagId ) {
					$tag = get_term_by( 'id', $tagId, 'product_tag' );
					
					if ( $tag ) {
						$options[ $tagId ] = $tag->name;
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search for a tag &hellip;', 'role-and-customer-based-pricing-for-woocommerce' ),
			'search_action' => 'woocommerce_json_search_rcbp_tags',
			'description'   => __( 'Choose the tags for which this pricing rule will apply. The rule applies to all products with the tag.', 'role-and-customer-based-pricing-for-woocommerce' )
		) );

		if ( taxonomy_exists( 'product_brand' ) ) {
			$this->renderSelect2( array(
				'id'            => '_rps_included_brands',
				'label'         => __( 'Apply for brands', 'role-and-customer-based-pricing-for-woocommerce' ),
				'value'         => ( function () use ( $pricingRule ) {
					$options = [];
					
					foreach ( $pricingRule->getIncludedProductBrands() as $brandId ) {
						$brand = get_term_by( 'id', $brandId, 'product_brand' );
						
						if ( $brand && ! is_wp_error( $brand ) ) {
							$options[ $brandId ] = $brand->name;
						}
					}
					
					return $options;
				} )(),
				'placeholder'   => __( 'Search for a brand &hellip;', 'role-and-customer-based-pricing-for-woocommerce' ),
				'search_action' => 'woocommerce_json_search_rcbp_brands',
				'description'   => __( 'Choose the brands for which this pricing rule will apply. The rule applies to all products from the brand.', 'role-and-customer-based-pricing-for-woocommerce' )
			) );
		}

		$this->renderSelect2( array(
			'id'            => '_rps_included_products',
			'label'         => __( 'Apply for specific products', 'role-and-customer-based-pricing-for-woocommerce' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getIncludedProducts() as $productId ) {
					$product = wc_get_product( $productId );
					
					if ( $product ) {
						$options[ $productId ] = $product->get_name();
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search for a product &hellip;', 'role-and-customer-based-pricing-for-woocommerce' ),
			'search_action' => 'woocommerce_json_search_products',
			'description'   => __( 'Pick up products for which you want to apply the pricing rule.', 'role-and-customer-based-pricing-for-woocommerce' )
		) );
	}

	public function getIcon(): string {
		return 'dashicons-archive';
	}
}
