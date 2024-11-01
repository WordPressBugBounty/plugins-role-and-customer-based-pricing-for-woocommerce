<?php

namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\CustomOptions;

class RoleBasedTaxesOption {
    const FIELD_TYPE = 'role_based_tax_options';

    public function __construct() {
        add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array($this, 'render') );
        add_action(
            'woocommerce_admin_settings_sanitize_option',
            function ( $value, $option, $rawValue ) {
                if ( self::FIELD_TYPE === $option['type'] ) {
                    $value = array();
                }
                return $value;
            },
            10,
            3
        );
    }

    public function render( $value ) {
        $option_value = $value['value'];
        $option_value = ( is_array( $option_value ) ? $option_value : array() );
        ?>
        <script>
			jQuery(document).ready(function () {
				jQuery('.rcbp_setting_role-based-taxes_role_enabled input[type="radio"]').on('change', function () {
					if (jQuery(this).val() === 'enabled') {
						jQuery(this).closest('fieldset').find('.rcbp_setting_role-based-taxes_override_options').removeClass('hidden');
					} else {
						jQuery(this).closest('fieldset').find('.rcbp_setting_role-based-taxes_override_options').addClass('hidden');
					}
				}).filter(':checked').trigger('change');
			});
        </script>

        <tr>
            <th scope="row" class="titledesc">
                <label for="<?php 
        echo esc_attr( $value['id'] );
        ?>"><?php 
        echo esc_html( $value['title'] );
        ?></label>
            </th>
            <td class="forminp forminp-<?php 
        echo esc_attr( sanitize_title( $value['type'] ) );
        ?>">
				
				<?php 
        foreach ( array_reverse( wp_roles()->roles ) as $slug => $role ) {
            ?>
					
					<?php 
            $enabled = array_key_exists( $slug, $option_value );
            $taxesEnabled = ( $enabled && !empty( $option_value[$slug]['tax_enabled'] ) ? $option_value[$slug]['tax_enabled'] : 'default' );
            $taxesEnabled = ( in_array( $taxesEnabled, ['default', 'enabled', 'disabled'] ) ? $taxesEnabled : 'default' );
            $pricesInShop = ( $enabled && !empty( $option_value[$slug]['prices_in_shop'] ) ? $option_value[$slug]['prices_in_shop'] : 'default' );
            $pricesInShop = ( in_array( $pricesInShop, ['default', 'excluding', 'including'] ) ? $pricesInShop : 'default' );
            $pricesInCart = ( $enabled && !empty( $option_value[$slug]['prices_in_cart'] ) ? $option_value[$slug]['prices_in_cart'] : 'default' );
            $pricesInCart = ( in_array( $pricesInCart, ['default', 'excluding', 'including'] ) ? $pricesInCart : 'default' );
            ?>

                    <fieldset style="margin-bottom: 5px;">
                        <legend style="margin-bottom: 5px">
                            <strong style="font-size: 1.1em">
                                <span class="dashicons dashicons-admin-users"></span> <?php 
            echo esc_html( $role['name'] );
            ?>
                            </strong>
                        </legend>

                        <div style="display: flex; gap: 15px;" class="rcbp_setting_role-based-taxes_role_enabled">

                            <div>
                                <label>
                                    <input type="radio"
                                           style="margin-right: 0"
										<?php 
            checked( !$enabled );
            ?>
                                           name="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][enabled]"
                                           value="">
									<?php 
            esc_html_e( 'Default
                                    WooCommerce settings', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                </label>
                            </div>

                            <div>
                                <label>
                                    <input type="radio"
                                           style="margin-right: 0"
										<?php 
            checked( $enabled );
            ?>
                                           name="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][enabled]"
                                           value="enabled">
									<?php 
            esc_html_e( 'Custom settings', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                </label>
                            </div>
                        </div>

                        <div class="rcbp_setting_role-based-taxes_override_options hidden" style="margin:10px 0 20px 0">
							
							<?php 
            if ( !racbpfw_fs()->can_use_premium_code() ) {
                ?>
                                <fieldset>
                                    <p class="description">
                                        <b><?php 
                esc_html_e( 'Available in the premium version' );
                ?></b>
                                        <a target="_blank" style="color:red"
                                           href="<?php 
                echo esc_url( racbpfw_fs()->get_upgrade_url() );
                ?>">
											<?php 
                esc_html_e( 'Upgrade now', 'role-and-customer-based-pricing-for-woocommerce' );
                ?>
                                        </a>
                                    </p>
                                </fieldset>
							<?php 
            }
            ?>

                            <div>
                                <div>
                                    <label for="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][tax_enabled]">
										<?php 
            esc_html_e( 'Enable taxes for this role', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </label>
                                </div>
                                <select id="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][tax_enabled]"
                                        name="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][tax_enabled]">
                                    <option value="default"
										<?php 
            selected( $taxesEnabled, 'default' );
            ?>>
										<?php 
            esc_html_e( 'Default WooCommerce settings', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </option>
                                    <option value="enabled"
										<?php 
            selected( $taxesEnabled, 'enabled' );
            ?>>
										<?php 
            esc_html_e( 'Enabled', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </option>
                                    <option value="disabled"
										<?php 
            selected( $taxesEnabled, 'disabled' );
            ?>>
										<?php 
            esc_html_e( 'Disabled', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </option>
                                </select>
                            </div>

                            <div>
                                <div>
                                    <label for="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][prices_in_shop]">
										<?php 
            esc_html_e( 'Display prices in the shop', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </label>
                                </div>
                                <select id="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][prices_in_shop]"
                                        name="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][prices_in_shop]">
                                    <option value="default"
										<?php 
            selected( $pricesInShop, 'default' );
            ?>>
										<?php 
            esc_html_e( 'Default WooCommerce settings', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </option>
                                    <option value="excluding"
										<?php 
            selected( $pricesInShop, 'excluding' );
            ?>>
										<?php 
            esc_html_e( 'Excluding tax', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </option>
                                    <option value="including"
										<?php 
            selected( $pricesInShop, 'including' );
            ?>>
										<?php 
            esc_html_e( 'Including tax', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </option>
                                </select>
                            </div>

                            <div>
                                <div>
                                    <label for="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][prices_in_cart]">
										<?php 
            esc_html_e( 'Display prices during cart and checkout', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </label>
                                </div>
                                <select id="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][prices_in_cart]"
                                        name="<?php 
            echo esc_attr( $value['id'] );
            ?>[<?php 
            echo esc_attr( $slug );
            ?>][prices_in_cart]">
                                    <option value="default"
										<?php 
            selected( $pricesInCart, 'default' );
            ?>>
										<?php 
            esc_html_e( 'Default WooCommerce settings', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </option>
                                    <option value="excluding"
										<?php 
            selected( $pricesInCart, 'excluding' );
            ?>>
										<?php 
            esc_html_e( 'Excluding tax', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </option>
                                    <option value="including"
										<?php 
            selected( $pricesInCart, 'including' );
            ?>>
										<?php 
            esc_html_e( 'Including tax', 'role-and-customer-based-pricing-for-woocommerce' );
            ?>
                                    </option>
                                </select>
                            </div>

                        </div>

                    </fieldset>
				<?php 
        }
        ?>
            </td>
        </tr>
		<?php 
    }

}
