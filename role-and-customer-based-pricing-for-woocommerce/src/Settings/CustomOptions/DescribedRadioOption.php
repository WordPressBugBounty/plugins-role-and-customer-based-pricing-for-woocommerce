<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainer;
use WC_Admin_Settings;

class DescribedRadioOption {

	const FIELD_TYPE = 'rcbp_described_radio';

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
		if ( ! isset( $value['default'] ) ) {
			$value['default'] = '';
		}

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		$option_value = $value['value'];
		?>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> </label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<fieldset>

					<ul>
						<?php
						foreach ( $value['options'] as $key => $val ) {
							?>
							<li>
								<label>
									<input
											name="<?php echo esc_attr( $value['id'] ); ?>"
											value="<?php echo esc_attr( $key ); ?>"
											type="radio"
											style="<?php echo esc_attr( $value['css'] ); ?>"
											class="<?php echo esc_attr( $value['class'] ); ?>"
										<?php checked( $key, $option_value ); ?>
									/> <?php echo esc_html( $val['label'] ); ?>
								</label>
								<p class="description"><?php echo esc_html( $val['description'] ); ?></p>
							</li>
							<?php
						}
						?>
					</ul>
				</fieldset>
			</td>
		</tr>
		<?php
	}
}
