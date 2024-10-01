<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns;

use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;
use WC_Product;
use WP_Term;

class AppliedProducts {

	public function getName() {
		return __( 'Products', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function render( GlobalPricingRule $rule ) {

		$productsMoreThanCanBeShown   = count( $rule->getIncludedProducts() ) > 10;
		$categoriesMoreThanCanBeShown = count( $rule->getIncludedProductCategories() ) > 10;

		$appliedProductIds  = array_slice( $rule->getIncludedProducts(), 0, 10 );
		$appliedCategoryIds = array_slice( $rule->getIncludedProductCategories(), 0, 10 );

		$appliedProducts = array_filter( array_map( function ( $productId ) {
			return wc_get_product( $productId );
		}, $appliedProductIds ) );

		$appliedCategories = array_filter( array_map( function ( $categoryId ) {
			return get_term( $categoryId );
		}, $appliedCategoryIds ) );

		$appliedCategories = array_filter( $appliedCategories, function ( $category ) {
			return $category instanceof WP_Term;
		} );

		if ( ! empty( $appliedCategories ) ) {

			esc_html_e( 'Categories: ', 'role-and-customer-based-pricing-for-woocommerce' );
			$appliedCategoriesString = array_map( function ( WP_Term $category ) {
				return sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_term_link( $category->term_id ), $category->name );
			}, $appliedCategories );

			echo wp_kses_post( implode( ', ', $appliedCategoriesString ) . ( $categoriesMoreThanCanBeShown ? '<span> ...</span>' : '' ) . '<br><br>' );
		}

		if ( ! empty( $appliedProducts ) ) {

			esc_html_e( 'Products: ', 'role-and-customer-based-pricing-for-woocommerce' );

			$appliedProductsString = array_map( function ( WC_Product $product ) {
				return sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ), $product->get_name() );
			}, $appliedProducts );

			echo wp_kses_post( implode( ', ', $appliedProductsString ) . ( $productsMoreThanCanBeShown ? '<span> ...</span>' : '' ) );
		}

		if ( empty( $appliedProducts ) && empty( $appliedCategories ) ) {
			?>
			<b style="color:#d63638"><?php esc_html_e( 'Applied to every product', 'role-and-customer-based-pricing-for-woocommerce' ); ?></b>
			<?php
		}
	}
}
