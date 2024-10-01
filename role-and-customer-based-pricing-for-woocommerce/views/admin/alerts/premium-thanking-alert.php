<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var string $accountUrl
 * @var string $contactUsUrl
 */
?>
<div class="rcbp-alert">

    <div class="rcbp-alert__text">
        <div class="rcbp-alert__inner">
            <?php
                _e( 'Thanks! You are using premium version of the plugin!', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
        </div>
    </div>

    <div class="rcbp-alert__buttons">
        <div class="rcbp-alert__inner">
            <a class="rcbp-button rcbp-button--accent" href="<?php echo $accountUrl; ?>"><?php _e( 'My Account',
			        'role-and-customer-based-pricing-for-woocommerce' ); ?></a>
            <a class="rcbp-button rcbp-button--default" href="<?php echo $contactUsUrl; ?>"><?php _e( 'Contact us', 'role-and-customer-based-pricing-for-woocommerce' ); ?></a>
        </div>
    </div>
</div>