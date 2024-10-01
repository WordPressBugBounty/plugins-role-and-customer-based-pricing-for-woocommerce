<?php use MeowCrew\RoleAndCustomerBasedPricing\Core\FileManager;
use MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage\PricingRulesManager;

defined( 'ABSPATH' ) || die;

/**
 * Available variables
 *
 * @var $fileManager FileManager
 * @var $post WP_Post
 * @var $product_type string
 * @var $loop int
 */

?>
	<div class="clear"></div>
<?php

$fileManager->includeTemplate( 'admin/product-page/role-specific-pricing/pricing-block.php', array(
	'fileManager'   => $fileManager,
	'product_id'    => $post->ID,
	'loop'          => $loop,
	'product_type'  => $product_type,
	'type'          => 'role',
	'label'         => __( 'Role based pricing', 'role-and-customer-based-pricing-for-woocommerce' ),
	'description'   => __( 'Choose the role to create the new pricing rules that will apply to all users with that role.', 'role-and-customer-based-pricing-for-woocommerce' ),
	'pricing_rules' => PricingRulesManager::getProductRoleSpecificPricingRules( $post->ID, false )
) );

?>
	<hr style="margin: 30px 0; border-color: #f4f4f4">
<?php

$fileManager->includeTemplate( 'admin/product-page/role-specific-pricing/pricing-block.php', array(
	'fileManager'   => $fileManager,
	'product_id'    => $post->ID,
	'loop'          => $loop,
	'product_type'  => $product_type,
	'type'          => 'customer',
	'label'         => __( 'Customer based pricing', 'role-and-customer-based-pricing-for-woocommerce' ),
	'description'   => __( 'If you need to make pricing rules only for specific customers (not whole user role), choose the customer name here', 'role-and-customer-based-pricing-for-woocommerce' ),
	'pricing_rules' => PricingRulesManager::getProductCustomerSpecificPricingRules( $post->ID, false )
) );

