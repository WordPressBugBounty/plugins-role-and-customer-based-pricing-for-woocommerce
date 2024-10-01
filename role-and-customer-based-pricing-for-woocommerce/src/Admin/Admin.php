<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Admin;

use MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage\Product;
use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\RoleAndCustomerBasedPricingPlugin;
use MeowCrew\RoleAndCustomerBasedPricing\RoleSpecificPricingPlugin;

class Admin {

	use ServiceContainerTrait;

	/**
	 * Admin constructor.
	 */
	public function __construct() {

		new Product();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
	}

	public function enqueueScripts( $page ) {

		wp_enqueue_script( 'role-specific-pricing-admin-js', $this->getContainer()->getFileManager()->locateAsset( 'admin/main.js' ),
			array( 'jquery' ), RoleAndCustomerBasedPricingPlugin::VERSION );
		wp_enqueue_style( 'role-specific-pricing-admin-css', $this->getContainer()->getFileManager()->locateAsset( 'admin/style.css' ),
			array(), RoleAndCustomerBasedPricingPlugin::VERSION );

		if ( $this->getContainer()->getSettings()->isSettingsPage() ) {
			wp_enqueue_script( 'role-specific-pricing-admin-settings-js', $this->getContainer()->getFileManager()->locateAsset( 'admin/settings.js' ),
				array( 'jquery' ), RoleAndCustomerBasedPricingPlugin::VERSION );
		}
	}
}
