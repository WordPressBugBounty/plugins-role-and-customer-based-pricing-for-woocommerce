<?php

namespace MeowCrew\RoleAndCustomerBasedPricing\Services;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections\RoleBasedTaxesSection;
class RoleBasedTaxService {
    use ServiceContainerTrait;
    public function __construct() {
        add_filter(
            'woocommerce_product_variation_get_tax_status',
            array($this, 'getProductsTaxStatus'),
            10,
            2
        );
        add_filter(
            'woocommerce_product_get_tax_status',
            array($this, 'getProductsTaxStatus'),
            10,
            2
        );
        add_filter( 'pre_option_woocommerce_tax_display_shop', function ( $value ) {
            return $this->getTaxDisplaySetting( $value, 'prices_in_shop' );
        } );
        add_filter( 'pre_option_woocommerce_tax_display_cart', function ( $value ) {
            return $this->getTaxDisplaySetting( $value, 'prices_in_cart' );
        } );
    }

    public function getTaxDisplaySetting( $taxSetting, $context ) {
        $roleTaxSettings = $this->getCurrentRoleTaxSettings();
        if ( !$roleTaxSettings ) {
            return $taxSetting;
        }
        if ( $roleTaxSettings[$context] === 'excluding' ) {
            return 'excl';
        }
        if ( $roleTaxSettings[$context] === 'including' ) {
            return 'incl';
        }
        return $taxSetting;
    }

    public function getProductsTaxStatus( $taxStatus ) {
        $roleTaxSettings = $this->getCurrentRoleTaxSettings();
        if ( !$roleTaxSettings ) {
            return $taxStatus;
        }
        if ( $roleTaxSettings['tax_enabled'] === 'enabled' ) {
            return 'taxable';
        }
        if ( $roleTaxSettings['tax_enabled'] === 'disabled' ) {
            return 'none';
        }
        return $taxStatus;
    }

    public function getCurrentRoleTaxSettings() {
        if ( !is_user_logged_in() ) {
            return null;
        }
        return null;
    }

}
