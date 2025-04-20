<?php

namespace MeowCrew\RoleAndCustomerBasedPricing;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use MeowCrew\RoleAndCustomerBasedPricing\Admin\Admin;
use MeowCrew\RoleAndCustomerBasedPricing\Core\AdminNotifier;
use MeowCrew\RoleAndCustomerBasedPricing\Core\FileManager;
use MeowCrew\RoleAndCustomerBasedPricing\Core\Logger;
use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\RoleSpecificPricingCPT;
use MeowCrew\RoleAndCustomerBasedPricing\Integrations\Integrations;
use MeowCrew\RoleAndCustomerBasedPricing\RoleManagement\RoleManagement;
use MeowCrew\RoleAndCustomerBasedPricing\Services\Import\WPAllImport;
use MeowCrew\RoleAndCustomerBasedPricing\Services\RoleBasedTaxService;
use MeowCrew\RoleAndCustomerBasedPricing\Services\Select2LookupService;
use MeowCrew\RoleAndCustomerBasedPricing\Services\NonLoggedUsersService;
use MeowCrew\RoleAndCustomerBasedPricing\Services\ProductPricingService;
use MeowCrew\RoleAndCustomerBasedPricing\Services\QuantityManagementService;
use MeowCrew\RoleAndCustomerBasedPricing\Settings\Settings;
use MeowCrew\RoleAndCustomerBasedPricing\Services\Import\WooCommerce as WooCommerceImport;
use MeowCrew\RoleAndCustomerBasedPricing\Services\Export\WooCommerce as WooCommerceExport;
/**
 * Class RoleAndCustomerBasedPricingPlugin
 *
 * @package MeowCrew\RoleSpecificPricing
 */
class RoleAndCustomerBasedPricingPlugin {
    use ServiceContainerTrait;
    const VERSION = '1.6.1';

    /**
     * RoleAndCustomerBasedPricingPlugin constructor.
     *
     * @param  string  $mainFile
     */
    public function __construct( $mainFile ) {
        FileManager::init( $mainFile, 'role-and-customer-based-pricing-for-woocommerce' );
        add_action( 'plugins_loaded', array($this, 'loadTextDomain') );
        add_action( 'role_customer_specific_pricing/container/services_init', array($this, 'addSettingsLink') );
        add_action( 'before_woocommerce_init', function () use($mainFile) {
            if ( class_exists( FeaturesUtil::class ) ) {
                FeaturesUtil::declare_compatibility( 'custom_order_tables', $mainFile, true );
            }
            if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
                FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', $mainFile, true );
            }
        } );
    }

    public function addSettingsLink() {
        add_filter(
            'plugin_action_links_' . plugin_basename( $this->getContainer()->getFileManager()->getMainFile() ),
            function ( $actions ) {
                $actions[] = '<a href="' . $this->getContainer()->getSettings()->getLink() . '">' . __( 'Settings', 'role-and-customer-based-pricing-for-woocommerce' ) . '</a>';
                if ( !racbpfw_fs()->is_anonymous() && racbpfw_fs()->is_installed_on_site() ) {
                    $actions[] = '<a href="' . racbpfw_fs()->get_account_url() . '"><b style="color: green">' . __( 'Account', 'role-and-customer-based-pricing-for-woocommerce' ) . '</b></a>';
                }
                $actions[] = '<a href="' . racbpfw_fs()->contact_url() . '"><b style="color: green">' . __( 'Contact Us', 'role-and-customer-based-pricing-for-woocommerce' ) . '</b></a>';
                if ( !racbpfw_fs()->can_use_premium_code() ) {
                    $actions[] = '<a href="' . racbpfw_fs()->get_upgrade_url() . '"><b style="color: red">' . __( 'Go Premium', 'role-and-customer-based-pricing-for-woocommerce' ) . '</b></a>';
                }
                return $actions;
            },
            10,
            4
        );
    }

    /**
     * Run plugin part
     */
    public function run() {
        $this->getContainer()->add( 'fileManager', FileManager::getInstance() );
        $this->getContainer()->add( 'adminNotifier', new AdminNotifier() );
        $this->getContainer()->add( 'logger', new Logger() );
        if ( $this->checkRequirements() ) {
            $this->initServices();
        }
    }

    public function initServices() {
        $this->getContainer()->add( 'settings', new Settings() );
        $this->getContainer()->add( 'admin', new Admin() );
        $this->getContainer()->add( 'globalRoleSpecificPricingCPT', new RoleSpecificPricingCPT() );
        $this->getContainer()->add( 'roleManagement', new RoleManagement() );
        $this->getContainer()->add( 'ProductPricingService', new ProductPricingService() );
        $this->getContainer()->add( 'NonLoggedUsersService', new NonLoggedUsersService() );
        $this->getContainer()->add( 'Select2LookupService', new Select2LookupService() );
        $this->getContainer()->add( 'Integrations', new Integrations() );
        do_action( 'role_customer_specific_pricing/container/services_init' );
    }

    /**
     * Load plugin translations
     */
    public function loadTextDomain() {
        $name = $this->getContainer()->getFileManager()->getPluginName();
        load_plugin_textdomain( 'role-and-customer-based-pricing-for-woocommerce', false, $name . '/languages/' );
    }

    public function checkRequirements() {
        /* translators: %s = required plugin */
        $message = __( 'Role and Customer Based Pricing for WooCommerce requires %s plugin to be active!', 'role-and-customer-based-pricing-for-woocommerce' );
        $plugins = $this->getRequiredPluginsToBeActive();
        if ( count( $plugins ) ) {
            foreach ( $plugins as $plugin ) {
                $error = sprintf( $message, $plugin );
                $this->getContainer()->getAdminNotifier()->push( $error, AdminNotifier::ERROR, false );
            }
            return false;
        }
        return true;
    }

    public function getRequiredPluginsToBeActive() {
        $plugins = array();
        if ( !function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( !(is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' )) ) {
            $plugins[] = 'WooCommerce';
        }
        return $plugins;
    }

}
