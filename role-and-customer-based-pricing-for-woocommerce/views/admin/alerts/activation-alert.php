<?php if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Activation plugin message
 *
 * @var string $link
 */
?>

<div id="message" class="updated notice is-dismissible">
	<p>
		<strong>
			<?php esc_attr__( 'Thanks for installing Tiered Price Table for WooCommerce! You can customize it ', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
			<a href="<?php echo esc_url( $link ); ?>"><?php esc_attr__( 'here', 'role-and-customer-based-pricing-for-woocommerce' ); ?></a>
		</strong>
	</p>
	<button type="button" class="notice-dismiss"></button>
</div>
