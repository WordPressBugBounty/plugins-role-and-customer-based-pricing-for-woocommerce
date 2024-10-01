<?php defined( 'ABSPATH' ) || die;

use MeowCrew\RoleAndCustomerBasedPricing\Core\FileManager;

/**
 * Available variables
 *
 * @var  FileManager $fileManager
 * @var  GlobalPricingRule $priceRule
 */
?>
<div id="rcbp-pricing-rule-block-global"
	 class="rcbp-pricing-rule-block rcbp-pricing-rule-block rcbp-pricing-rule-block--global">
	<?php
	$fileManager->includeTemplate( 'admin/product-page/role-specific-pricing/single-rule-form.php', array(
		'pricing_rule' => $priceRule,
		'type'         => 'global',
		'loop'         => false,
		'identifier'   => 'global',
	) );
	?>
</div>

