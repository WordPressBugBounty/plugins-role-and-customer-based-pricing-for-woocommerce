<?php

namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections;

use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\RoleBasedTaxesOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Settings;
class RoleBasedTaxesSection extends AbstractSection {
    public function getTitle() {
        return __( 'Role-based tax settings', 'role-and-customer-based-pricing-for-woocommerce' );
    }

    public function getDescription() {
        return __( 'Set custom tax rules for each role, separate from store defaults: disable taxes or hide them on shop and checkout pages.', 'role-and-customer-based-pricing-for-woocommerce' );
    }

    public function getName() {
        return 'role_based_taxes_test_section';
    }

    public function getSettings() {
        return array(array(
            'title' => __( 'Roles tax settings', 'role-and-customer-based-pricing-for-woocommerce' ),
            'type'  => RoleBasedTaxesOption::FIELD_TYPE,
            'id'    => Settings::SETTINGS_PREFIX . 'role_based_taxes',
            'desc'  => __( 'Enable role-based taxes', 'role-and-customer-based-pricing-for-woocommerce' ),
        ));
    }

    public static function getRolesTaxSettings() {
        $rolesTaxSetting = get_option( Settings::SETTINGS_PREFIX . 'role_based_taxes', array() );
        $_rolesTaxSetting = array();
        return $_rolesTaxSetting;
    }

}
