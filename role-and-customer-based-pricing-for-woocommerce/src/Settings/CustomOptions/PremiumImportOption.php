<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions;

class PremiumImportOption {

	const FIELD_TYPE = 'rcbp_premium_import';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );
	}

	public function render( $value ) {
		?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>">WooCommerce import/export tool</label>
            </th>
            <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                <fieldset>
                    <p class="description">
                        <b><?php esc_html_e( 'Available in the premium version' ); ?></b>
                        <a target="_blank" style="color:red"
                           href="<?php echo esc_url( racbpfw_fs()->get_upgrade_url() ) ?>">
			                <?php esc_html_e( 'Upgrade now', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
                        </a>
                    </p>
                </fieldset>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>">WP All Import</label>
            </th>
            <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                <fieldset>
                    <p class="description">
                        <b><?php esc_html_e( 'Available in the premium version' ); ?></b>
                        <a target="_blank" style="color:red"
                           href="<?php echo esc_url( racbpfw_fs()->get_upgrade_url() ) ?>">
							<?php esc_html_e( 'Upgrade now', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
                        </a>
                    </p>
                </fieldset>
            </td>
        </tr>
		<?php
	}
}
