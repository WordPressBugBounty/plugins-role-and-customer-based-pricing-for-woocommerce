<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use WP_Post;

class RoleSpecificVariableProduct {

	use ServiceContainerTrait;

	public function __construct() {
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'renderPriceRules' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'updatePriceRules' ), 10, 3 );
	}

	/**
	 * Update price quantity rules for variation product
	 *
	 * @param int $variation_id
	 * @param int $loop
	 */
	public function updatePriceRules( $variation_id, $loop ) {

		check_ajax_referer( 'save-variations', 'security' );

		foreach ( array( 'role', 'customer' ) as $type ) {

			$data = array();

			$fields = array(
				"_rcbp_{$type}_pricing_type_variation",
				"_rcbp_{$type}_regular_price_variation",
				"_rcbp_{$type}_sale_price_variation",
				"_rcbp_{$type}_discount_variation",
				"_rcbp_{$type}_minimum_variation",
				"_rcbp_{$type}_maximum_variation",
				"_rcbp_{$type}_group_of_variation",
			);

			foreach ( $fields as $field ) {

				if ( ! isset( $_POST[ $field ] ) || ! isset( $_POST[ $field ][ $loop ] ) ) {
					// wipe out
					break;
				}

				$data[ str_replace( '_variation', '', $field ) ] = array_map( 'sanitize_text_field', (array) $_POST[ $field ][ $loop ] );
			}

			Product::handleUpdatePricingRule( $data, $variation_id, $type );
		}

	}

	/**
	 * Render inputs for price rules on variation
	 *
	 * @param int $loop
	 * @param array $variation_data
	 * @param WP_Post $variation
	 */
	public function renderPriceRules( $loop, $variation_data, WP_Post $variation ) {
		$this->getContainer()->getFileManager()->includeTemplate( 'admin/product-page/role-specific-pricing/index.php', array(
			'post'         => $variation,
			'loop'         => $loop,
			'product_type' => 'variation',
			'fileManager'  => $this->getContainer()->getFileManager()
		) );
	}
}
