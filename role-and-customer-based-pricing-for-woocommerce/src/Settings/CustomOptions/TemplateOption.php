<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use WC_Admin_Settings;

class TemplateOption {

	const FIELD_TYPE = 'rcbp_template';

	use ServiceContainerTrait;

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );

		add_action( 'woocommerce_admin_settings_sanitize_option', function ( $value, $option, $rawValue ) {

			if ( self::FIELD_TYPE === $option['type'] ) {
				return wp_kses_post( $rawValue );
			}

			return $value;
		}, 10, 3 );

	}

	public function render( $value ) {
		if ( ! isset( $value['id'] ) ) {
			$value['id'] = '';
		}
		if ( ! isset( $value['title'] ) ) {
			$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
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
		if ( ! isset( $value['value'] ) ) {
			$value['value'] = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		$option_value = $value['value'];

		$value['description'] = isset( $value['description'] ) ? $value['description'] : '';

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<?php

				wp_editor( $option_value, $value['id'], array(
					'wpautop'       => true,
					'media_buttons' => false,

					'textarea_name'    => $value['id'],
					'textarea_rows'    => 5,
					'tabindex'         => null,
					'editor_class'     => 'sp-message-template-mce',
					'tinymce'          => array(
						'resize'   => 'vertical',
						'menubar'  => false,
						'wpautop'  => true,
						'toolbar2' => '',
						'toolbar1' => implode( ',', array(
							'bold',
							'italic',
							'strikethrough',
							'link',
							'spellchecker',
						) ),
					),
					'quicktags'        => array(
						'id'      => $value['id'],
						'buttons' => 'strong,del',
					),
					'drag_drop_upload' => false
				) );
				?>

				<p class="description"><?php echo esc_attr( $value['description'] ); ?></p>
			</td>
		</tr>
		<?php
	}

}
