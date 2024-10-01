<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections;

use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\TemplateOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\SwitchCheckboxOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Settings;

class MainSection extends AbstractSection {

	public function getTitle() {
		return __( 'Role and Customer Based Pricing', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function getDescription() {
		return __( 'You can set up different scenarios for how the plugin should handle unauthorized users here.', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function getName() {
		return 'main_section';
	}

	public function getSettings() {
		return array(
			'prevent_purchase_for_non_logged_in_users' => array(
				'title'   => __( 'Prevent purchase for non-logged users', 'role-and-customer-based-pricing-for-woocommerce' ),
				'id'      => Settings::SETTINGS_PREFIX . 'prevent_purchase_for_non_logged_in_users',
				'default' => 'no',
				'desc'    => __( 'When the users isn\'t logged in, they\'ll not be able to make a purchase', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'    => SwitchCheckboxOption::FIELD_TYPE,
			),

			'non_logged_in_users_purchase_message' => array(
				'title'   => __( 'Error message when non-logged users add to cart', 'role-and-customer-based-pricing-for-woocommerce' ),
				'id'      => Settings::SETTINGS_PREFIX . 'non_logged_in_users_purchase_message',
				'type'    => TemplateOption::FIELD_TYPE,
				// translators: %s: login page url
				'default' => sprintf( __( 'Please enter %s to make a purchase', 'role-and-customer-based-pricing-for-woocommerce' ), sprintf( '<a href="%s">%s</a>', wc_get_account_endpoint_url( 'dashboard' ), __( 'your account', 'role-and-customer-based-pricing-for-woocommerce' ) ) ),
			),

			'hide_prices_for_non_logged_in_users' => array(
				'title'   => __( 'Hide prices for non-logged users', 'role-and-customer-based-pricing-for-woocommerce' ),
				'id'      => Settings::SETTINGS_PREFIX . 'hide_prices_for_non_logged_in_users',
				'default' => 'no',
				'desc'    => __( 'Show all prices in the store only for logged users', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'    => SwitchCheckboxOption::FIELD_TYPE,
			),

			'add_to_cart_label_for_non_logged_in_users' => array(
				'title'       => __( 'Add to cart label for non logged users', 'role-and-customer-based-pricing-for-woocommerce' ),
				'desc'        => __( 'Change default Add to cart label to something else to be displayed for non-logged users', 'role-and-customer-based-pricing-for-woocommerce' ),
				'id'          => Settings::SETTINGS_PREFIX . 'add_to_cart_label_for_non_logged_in_users',
				'default'     => '',
				'placeholder' => __( 'Leave it empty to keep as it is', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'        => 'text',
			),
		);
	}

}
