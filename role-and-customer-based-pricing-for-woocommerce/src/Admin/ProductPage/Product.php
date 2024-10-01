<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\PricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\Utils\Strings;

class Product {

	const GET_PRODUCT_RULE_ROW_HTML__ACTION = 'rbp_get_product_rule_row_html';

	use ServiceContainerTrait;

	public function __construct() {

		add_action( 'wp_ajax_' . self::GET_PRODUCT_RULE_ROW_HTML__ACTION, array( $this, 'getProductRuleRowHtml' ) );

		new RoleSpecificPricingTab();
		new RoleSpecificVariableProduct();
	}

	public static function handleUpdatePricingRule( $data, $productId, $type ) {

		$pricingRules = array();

		if ( ! empty( $data["_rcbp_{$type}_pricing_type"] ) ) {
			foreach ( $data["_rcbp_{$type}_pricing_type"] as $key => $value ) {

				$pricingRule = new PricingRule( $data["_rcbp_{$type}_pricing_type"][ $key ],
					! Strings::IsNullOrEmpty( $data["_rcbp_{$type}_regular_price"][ $key ] ) ? wc_format_decimal( $data["_rcbp_{$type}_regular_price"][ $key ] ) : null,
					! Strings::IsNullOrEmpty( $data["_rcbp_{$type}_sale_price"][ $key ] ) ? wc_format_decimal( $data["_rcbp_{$type}_sale_price"][ $key ] ) : null,
					! empty( $data["_rcbp_{$type}_discount"][ $key ] ) ? floatval( $data["_rcbp_{$type}_discount"][ $key ] ) : null,
					sanitize_text_field( $data["_rcbp_{$type}_minimum"][ $key ] ),
					sanitize_text_field( $data["_rcbp_{$type}_maximum"][ $key ] ),
					sanitize_text_field( $data["_rcbp_{$type}_group_of"][ $key ] ) );

				$pricingRules[ $key ] = $pricingRule;
			}
		}
		PricingRulesManager::updateProductPricingRules( $productId, $pricingRules, $type );
	}

	/**
	 * AJAX Handler
	 */
	public function getProductRuleRowHtml() {

		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : false;

		if ( wp_verify_nonce( $nonce, self::GET_PRODUCT_RULE_ROW_HTML__ACTION ) ) {

			$identifier = ! empty( $_GET['identifier'] ) ? sanitize_text_field( $_GET['identifier'] ) : false;
			$productId  = ! empty( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : false;
			$loop       = isset( $_GET['loop'] ) && '' !== $_GET['loop'] ? intval( $_GET['loop'] ) : false;
			$type       = ! empty( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : false;

			$product = wc_get_product( $productId );

			if ( $identifier && $product && $type ) {

				$productType = $product->is_type( 'variation' ) ? 'variation' : 'simple';

				wp_send_json( array(
					'success'       => true,
					'role_row_html' => $this->getContainer()->getFileManager()->renderTemplate( 'admin/product-page/role-specific-pricing/single-rule.php',
						array(
							'identifier'   => $identifier,
							'pricing_rule' => new PricingRule( 'flat' ),
							'type'         => $type,
							'loop'         => $loop,
							'productType'  => $productType,
							'fileManager'  => $this->getContainer()->getFileManager(),
						) ),
				) );
			}

			wp_send_json( array(
				'success'       => false,
				'error_message' => __( 'Invalid pricing rule', 'role-specific-pricing' ),
			) );
		}

		wp_send_json( array(
			'success'       => false,
			'error_message' => __( 'Invalid nonce', 'role-specific-pricing' ),
		) );
	}

}
