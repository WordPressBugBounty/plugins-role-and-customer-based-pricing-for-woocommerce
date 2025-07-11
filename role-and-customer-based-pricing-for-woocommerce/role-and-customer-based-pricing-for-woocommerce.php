<?php use MeowCrew\RoleAndCustomerBasedPricing\RoleAndCustomerBasedPricingPlugin;
	
	/**
	 *
	 * Plugin Name:       Role Based Pricing for Woo by Meow Crew
	 * Plugin URI:        https://meow-crew.com/role-and-customer-based-pricing-for-wooommerce
	 * Requires Plugins:  woocommerce
	 * Description:       Use this plugin to create pricing rules based on user roles or individual pricing for various customers.
	 * Version:           1.6.3
	 * Author:            Meow Crew
	 * Author URI:        https://meow-crew.com
	 * License:           GPL-2.0+
	 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	 * Text Domain:       role-and-customer-based-pricing-for-woocommerce
	 * Domain Path:       /languages
	 * Requires at least: 5.0
	 * Requires PHP: 5.6
	 *
	 * WC requires at least: 5.0
	 * WC tested up to: 10.0
	 *
	 	 */

	
// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) {
		die;
	}
	
	require_once 'license.php';
	
	call_user_func( function () {
		
		require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
		
		( new RoleAndCustomerBasedPricingPlugin( __FILE__ ) )->run();
	} );
