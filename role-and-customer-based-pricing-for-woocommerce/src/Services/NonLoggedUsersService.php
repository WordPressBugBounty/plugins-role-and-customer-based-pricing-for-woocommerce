<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Services;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;

class NonLoggedUsersService {

	use ServiceContainerTrait;

	public function __construct() {

		add_action( 'init', function () {

			if ( ! is_user_logged_in() ) {

				if ( $this->getContainer()->getSettings()->doHidePricesForNonLoggedInUsers() ) {
					add_filter( 'woocommerce_get_price_html', '__return_empty_string', 99999 );
				}

				if ( $this->getContainer()->getSettings()->doPreventPurchaseForNonLoggedInUsers() ) {

					add_filter( 'woocommerce_add_to_cart_validation', function ( $valid ) {

						if ( $valid ) {
							wc_add_notice( $this->getContainer()->getSettings()->getNonLoggedInUsersPurchaseMessage(), 'error' );
						}

						return false;
					} );
				}

				$label = $this->getContainer()->getSettings()->getNonLoggedInUsersAddToCartButtonLabel();

				if ( $label ) {
					add_filter( 'woocommerce_product_single_add_to_cart_text', function () use ( $label ) {
						return $label;
					} );

					add_filter( 'woocommerce_product_add_to_cart_text', function () use ( $label ) {
						return $label;
					} );
				}
			}
		} );
	}
}
