<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections;

use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\PremiumImportOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\PremiumSelectOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Settings;

class ImportExportSection extends AbstractSection {
	
	public function getTitle() {
		return __( 'Import/Export', 'role-and-customer-based-pricing-for-woocommerce' );
	}
	
	public function getDescription() {
		return __( 'Manage role-based prices for each product separately via regular WooCommerce Import\Export tool or WP All Import plugin.',
			'role-and-customer-based-pricing-for-woocommerce' );
	}
	
	public function getName() {
		return 'import_export_section';
	}
	
	public function getSettings() {
		return array(
			'premium_import' => array(
				'type' => PremiumImportOption::FIELD_TYPE,
			),
		);
	}
	
}
