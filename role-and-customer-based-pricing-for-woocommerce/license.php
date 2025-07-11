<?php

if ( !function_exists( 'racbpfw_fs' ) ) {
    // Create a helper function for easy SDK access.
    function racbpfw_fs() {
        global $racbpfw_fs;
        if ( !isset( $racbpfw_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_9596_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_9596_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $racbpfw_fs = fs_dynamic_init( array(
                'id'               => '9596',
                'slug'             => 'role-and-customer-based-pricing-for-woocommerce',
                'premium_slug'     => 'age-verification-for-woocommerce-premium',
                'type'             => 'plugin',
                'public_key'       => 'pk_7b9f024ab59d07769da18fd2f7824',
                'is_premium'       => false,
                'premium_suffix'   => 'Premium version',
                'is_org_compliant' => true,
                'has_addons'       => false,
                'has_paid_plans'   => true,
                'trial'            => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
                'menu'             => array(
                    'first-path' => 'plugins.php',
                    'contact'    => false,
                    'support'    => false,
                ),
                'is_live'          => true,
            ) );
        }
        return $racbpfw_fs;
    }

    // Init Freemius.
    racbpfw_fs();
    // Signal that SDK was initiated.
    do_action( 'racbpfw_fs_loaded' );
}