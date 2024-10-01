<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;

class RoleSpecificPricingTab {

	const SLUG = 'role-specific-pricing-options';

	use ServiceContainerTrait;

	public function __construct() {

		add_filter( 'woocommerce_product_data_tabs', array( $this, 'register' ), 999 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
	}

	public function register( $productTabs ) {

		$productTabs[ self::SLUG ] = array(
			'label'  => __( 'Role\User pricing', 'role-and-customer-based-pricing-for-woocommerce' ),
			'target' => self::SLUG,
			'class'  => array(
				'show_if_simple',
				'show_if_variable',
				'show_if_course',
				'show_if_subscription',
				'show_if_variable_subscription'
			)
		);

		return $productTabs;
	}


	public function render() {

		global $post;

		$product = wc_get_product( $post->ID );

		if ( $product ) {
			?>
            <div id="<?php echo esc_attr( self::SLUG ); ?>" class="panel woocommerce_options_panel">
				<?php
				$this->getContainer()->getFileManager()->includeTemplate( 'admin/product-page/role-specific-pricing/index.php', array(
					'post'         => $post,
					'product_type' => $product->get_type(),
					'loop'         => false,
					'fileManager'  => $this->getContainer()->getFileManager()
				) );
				?>
            </div>
			<?php
		}
	}

	public function save( $productId ) {
		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}

		foreach ( array( 'role', 'customer' ) as $type ) {

			$data = array();

			$fields = array(
				"_rcbp_{$type}_pricing_type",
				"_rcbp_{$type}_regular_price",
				"_rcbp_{$type}_sale_price",
				"_rcbp_{$type}_discount",
				"_rcbp_{$type}_minimum",
				"_rcbp_{$type}_maximum",
				"_rcbp_{$type}_group_of",
			);

			foreach ( $fields as $field ) {
				if ( ! isset( $_POST[ $field ] ) ) {
					// wipe out
					break;
				}

				$data[ $field ] = array_map( 'sanitize_text_field', (array) $_POST[ $field ] );
			}

			Product::handleUpdatePricingRule( $data, $productId, $type );
		}

	}

}
