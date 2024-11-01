<?php

namespace MeowCrew\RoleAndCustomerBasedPricing\Settings;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\PremiumImportOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\PremiumSelectOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\RoleBasedTaxesOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\TemplateOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions\SwitchCheckboxOption;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections\AbstractSection;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections\ImportExportSection;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections\MainSection;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections\PricingSection;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections\RoleBasedTaxesSection;
/**
 * Class Settings
 *
 * @package Settings
 */
class Settings {
    use ServiceContainerTrait;
    const SETTINGS_PREFIX = 'role_and_customer_based_pricing_';

    const SETTINGS_PAGE = 'role_and_customer_based_pricing_settings';

    /**
     * Array with the settings
     *
     * @var array
     */
    private $settings;

    /**
     * Sections
     *
     * @var AbstractSection[]
     */
    private $sections;

    /**
     * Settings constructor.
     */
    public function __construct() {
        $this->initCustomOptions();
        $this->initSections();
        $this->hooks();
    }

    public function initCustomOptions() {
        $this->getContainer()->add( 'settings.SwitchCheckboxOption', new SwitchCheckboxOption() );
        $this->getContainer()->add( 'settings.TemplateOption', new TemplateOption() );
        $this->getContainer()->add( 'settings.PremiumSelectOption', new PremiumSelectOption() );
        $this->getContainer()->add( 'settings.PremiumImportOption', new PremiumImportOption() );
        $this->getContainer()->add( 'settings.roleBasedTaxesOption', new RoleBasedTaxesOption() );
    }

    public function initSections() {
        $this->sections = array(
            'main'                    => new MainSection(),
            'pricing'                 => new PricingSection(),
            'role_based_tax_settings' => new RoleBasedTaxesSection(),
        );
        $this->sections['import_export'] = new ImportExportSection();
    }

    /**
     * Handle updating settings
     */
    public function updateSettings() {
        woocommerce_update_options( $this->settings );
    }

    /**
     * Init all settings
     */
    public function initSettings() {
        $settings = array();
        foreach ( $this->sections as $section ) {
            $settings[$section->getName() . '__section'] = array(
                'title' => $section->getTitle(),
                'desc'  => $section->getDescription(),
                'id'    => self::SETTINGS_PREFIX . $section->getName() . '__section',
                'type'  => 'title',
            );
            foreach ( $section->getSettings() as $key => $value ) {
                $settings[] = $value;
            }
            $settings[$section->getName() . '__section_end'] = array(
                'id'   => self::SETTINGS_PREFIX . $section->getName() . '__section_end',
                'type' => 'sectionend',
            );
        }
        $this->settings = apply_filters( 'role_customer_specific_pricing/settings/settings', $settings );
    }

    /**
     * Register hooks
     */
    public function hooks() {
        if ( !racbpfw_fs()->can_use_premium_code() ) {
            $template = 'upgrade-alert.php';
            add_action( 'woocommerce_settings_' . self::SETTINGS_PAGE, function () use($template) {
                $this->getContainer()->getFileManager()->includeTemplate( 'admin/alerts/' . $template, [
                    'upgradeUrl'   => racbpfw_fs()->get_upgrade_url(),
                    'contactUsUrl' => racbpfw_fs()->contact_url(),
                ] );
            } );
        }
        add_action( 'init', array($this, 'initSettings') );
        add_filter( 'woocommerce_settings_tabs_' . self::SETTINGS_PAGE, array($this, 'registerSettings') );
        add_filter( 'woocommerce_settings_tabs_array', array($this, 'addSettingsTab'), 50 );
        add_action( 'woocommerce_update_options_' . self::SETTINGS_PAGE, array($this, 'updateSettings') );
    }

    /**
     * Add own settings tab
     *
     * @param  array  $settings_tabs
     *
     * @return mixed
     */
    public function addSettingsTab( $settings_tabs ) {
        $settings_tabs[self::SETTINGS_PAGE] = __( 'Role\\Customer Based Pricing', 'role-and-customer-based-pricing-for-woocommerce' );
        return $settings_tabs;
    }

    /**
     * Add settings to WooCommerce
     */
    public function registerSettings() {
        woocommerce_admin_fields( $this->settings );
    }

    /**
     * Get setting by name
     *
     * @param  string  $option_name
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function get( $option_name, $default = null ) {
        return get_option( self::SETTINGS_PREFIX . $option_name, $default );
    }

    public function getAllSettings() {
        $settings = array_filter( $this->settings, function ( $setting ) {
            return !in_array( $setting['type'], array(
                'section',
                'sectionend',
                'title',
                CustomizerButtonOption::FIELD_TYPE
            ) );
        } );
        return array_map( function ( $key, $value ) {
            return $this->get( $key, $value['default'] );
        }, array_keys( $settings ), $settings );
    }

    public function doPreventPurchaseForNonLoggedInUsers() {
        return $this->get( 'prevent_purchase_for_non_logged_in_users', 'no' ) === 'yes';
    }

    public function doHidePricesForNonLoggedInUsers() {
        return $this->get( 'hide_prices_for_non_logged_in_users', 'no' ) === 'yes';
    }

    public function getNonLoggedInUsersPurchaseMessage() {
        // translators: %s: login page url
        return $this->get( 'non_logged_in_users_purchase_message', sprintf( __( 'Please enter %s to make a purchase', 'role-and-customer-based-pricing-for-woocommerce' ), sprintf( '<a href="%s">%s</a>', wc_get_account_endpoint_url( 'dashboard' ), __( 'your account', 'role-and-customer-based-pricing-for-woocommerce' ) ) ) );
    }

    public function getNonLoggedInUsersAddToCartButtonLabel() {
        return $this->get( 'add_to_cart_label_for_non_logged_in_users', '' );
    }

    public function isDebugEnabled() {
        return $this->get( 'debug', 'no' ) === 'yes';
    }

    public function getPercentageBasedRulesBehavior() {
        if ( !racbpfw_fs()->can_use_premium_code() ) {
            return 'full_price';
        }
        return $this->get( 'percentage_based_pricing_rule_behaviour', 'full_price' );
    }

    public function useRegularPriceToCalculateDiscounts() {
        return $this->get( 'use_regular_price_for_percentage_discounts', 'no' ) === 'yes';
    }

    /**
     * Get url to settings page
     *
     * @return string
     */
    public function getLink() {
        return admin_url( 'admin.php?page=wc-settings&tab=' . self::SETTINGS_PAGE );
    }

    public function isSettingsPage() {
        return isset( $_GET['tab'] ) && self::SETTINGS_PAGE === $_GET['tab'];
    }

}
