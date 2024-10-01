<?php

use MeowCrew\RoleAndCustomerBasedPricing\Core\FileManager;
use MeowCrew\RoleAndCustomerBasedPricing\Core\Logger;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\PricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\Utils\Formatter;

defined( 'ABSPATH' ) || die;

/**
 * Available variables
 *
 * @var PricingRule $pricing_rule
 * @var string $identifier
 * @var string $type
 * @var int|false $loop
 * @var FileManager $fileManager
 *
 */
global $wp_roles;

$ruleTitle = $identifier;

if ( 'role' === $type ) {
	global $wp_roles;

	$ruleTitle = isset( $wp_roles->role_names[ $identifier ] ) ? translate_user_role( $wp_roles->role_names[ $identifier ] ) : $identifier;
} else if ( 'customer' === $type ) {
	try {
		$customer = new WC_Customer( $identifier );

		$ruleTitle = Formatter::formatCustomerString( $customer );

	} catch ( Exception $e ) {
		$logger = new Logger();

		$logger->log( $e->getMessage(), Logger::ERROR__LEVEL );
	}
}

?>

<div class="rcbp-pricing-rule rcbp-pricing-rule--<?php echo esc_attr( $identifier ); ?>"
	 data-identifier-slug="<?php echo esc_attr( $ruleTitle ); ?>" data-identifier="<?php echo esc_attr( $identifier ); ?>">
	<div class="rcbp-pricing-rule__header">
		<div class="rcbp-pricing-rule__name">
			<b><?php echo esc_attr( $ruleTitle ); ?></b>
		</div>
		<div class="rcbp-pricing-rule__actions">
			<span class="rcbp-pricing-rule__action-toggle-view rcbp-pricing-rule__action-toggle-view--open"></span>
			<a href="#" class="rcbp-pricing-rule-action--delete"><?php esc_attr_e( 'Remove', 'woocommerce' ); ?></a>
		</div>
	</div>
	<div class="rcbp-pricing-rule__content">
		<?php
		$fileManager->includeTemplate( 'admin/product-page/role-specific-pricing/single-rule-form.php', array(
			'pricing_rule' => $pricing_rule,
			'type'         => $type,
			'loop'         => $loop,
			'identifier'   => $identifier,
		) );
		?>
	</div>
</div>
