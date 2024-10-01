<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainer;
use WC_Admin_Settings;

class PremiumSelectOption {

	const FIELD_TYPE = 'ps_premium_select';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );
	}

	public function render( $value ) {
		if ( ! isset( $value['id'] ) ) {
			$value['id'] = '';
		}
		if ( ! isset( $value['title'] ) ) {
			$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
		}
		if ( ! isset( $value['class'] ) ) {
			$value['class'] = '';
		}
		if ( ! isset( $value['css'] ) ) {
			$value['css'] = '';
		}
		if ( ! isset( $value['default'] ) ) {
			$value['default'] = '';
		}
		if ( ! isset( $value['desc'] ) ) {
			$value['desc'] = '';
		}
		if ( ! isset( $value['desc_tip'] ) ) {
			$value['desc_tip'] = false;
		}
		if ( ! isset( $value['placeholder'] ) ) {
			$value['placeholder'] = '';
		}
		if ( ! isset( $value['suffix'] ) ) {
			$value['suffix'] = '';
		}

		if ( ! isset( $value['is_free'] ) ) {
			$value['is_free'] = false;
		}

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		// Description handling.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];

		$option_value = $value['value'];

		?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?><?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
            </th>
            <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                <select
                        name="<?php echo esc_attr( $value['id'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
                        id="<?php echo esc_attr( $value['id'] ); ?>"
                        style="<?php echo esc_attr( $value['css'] ); ?>"
                        class="<?php echo esc_attr( $value['class'] ); ?>
                        <?php echo esc_html( $value['is_free'] ? 'rcbp-pricing-premium-content' : '' ) ?>
"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
					<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
                >
					<?php
					foreach ( $value['options'] as $key => $val ) {
						?>
                        <option value="<?php echo esc_attr( $key ); ?>"
							<?php
							if ( is_array( $option_value ) ) {
								selected( in_array( (string) $key, $option_value, true ), true );
							} else {
								selected( $option_value, (string) $key );
							}
							?>>
							<?php echo esc_html( $val ); ?></option>
						<?php
					}
					?>
                </select> <?php echo $description; // WPCS: XSS ok. ?>

				<?php if ( $value['is_free'] ): ?>
                    <br>
                    <p class="description">
                        <b><?php esc_html_e( 'Available in the premium version' ); ?></b>
                        <a target="_blank" style="color:red"
                           href="<?php echo esc_url( racbpfw_fs()->get_upgrade_url() ) ?>">
							<?php esc_html_e( 'Upgrade now', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
                        </a>
                    </p>
				<?php endif; ?>
            </td>
        </tr>
		<?php
	}
}
