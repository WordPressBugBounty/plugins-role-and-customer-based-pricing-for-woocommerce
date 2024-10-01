<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Services\Import;

use MeowCrew\RoleAndCustomerBasedPricing\Admin\ProductPage\PricingRulesManager;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\PricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\Utils\Strings;
use function \extract as allowedExtract;

use PMXI_API;
use XmlImportParser;

class   WPAllImport {

	public $name;

	public $slug;

	public $fields;

	public $options = array();

	public $notice_text;

	public $logger = null;

	protected $isWizard = false;

	private $active_post_types;

	public function __construct() {

		$this->name = __( 'Role and Customer based pricing', 'role-and-customer-based-pricing-for-woocommerce' );

		$this->slug = 'role-customer-based-pricing';

		foreach ( $this->getFieldsToImport() as $slug => $field ) {

			if ( empty( $field ) ) {
				continue;
			}

			$this->add_field( $slug, $field['title'], $field['type'], null, $field['description'], true, '',
				isset( $field['role'] ) ? $field['role'] : '' );
		}

		$this->add_option( 'update_role_customer_based_pricing', '1' );

		$this->run();
	}

	public function processImport( $post_id, $data, $import_options, $article ) {

		$this->log( '<b>------------- Role and Customer Based Pricing Import -------------</b>' );

		if ( $this->needUpdateRoleBase( $import_options ) ) {
			$roleBasedRules = array();

			foreach ( $this->getFieldsToImport() as $fieldSlug => $fieldData ) {

				// system key
				if ( Strings::startsWith( $fieldSlug, '__' ) ) {
					continue;
				}

				$roleKey = $fieldData['role'];

				// role is not enabled
				if ( $roleKey && empty( $data[ '__rcbp_' . $roleKey . '_enabled' ] ) ) {
					continue;
				}

				$fieldValue    = $fieldData['unSerialize']( $data[ $fieldSlug ] );
				$importedKey   = $fieldSlug;
				$importedValue = $fieldValue;

				// Pricing type
				if ( Strings::endsWith( $importedKey, 'pricing_type' ) ) {
					$roleBasedRules[ $roleKey ]['pricing_type'] = in_array( $importedValue, array(
						'flat',
						'percentage',
					) ) ? $importedValue : 'fixed';

					$this->log( sprintf( '<b>%s:</b> Pricing type was imported with "<b>%s</b>" value', $roleKey,
						is_null( $importedValue ) ? 'null' : $importedValue ) );
				}

				// Discount
				if ( Strings::endsWith( $importedKey, 'discount' ) ) {

					$roleBasedRules[ $roleKey ]['discount'] = $importedValue;

					$this->log( sprintf( '<b>%s:</b> Discount was imported with "<b>%s</b>" value', $roleKey,
						is_null( $importedValue ) ? 'null' : $importedValue ) );
				}

				// Regular price
				if ( Strings::endsWith( $importedKey, 'regular_price' ) ) {

					$roleBasedRules[ $roleKey ]['regular_price'] = $importedValue;

					$this->log( sprintf( '<b>%s:</b> Regular price was imported with "<b>%s</b>" value', $roleKey,
						is_null( $importedValue ) ? 'null' : $importedValue ) );
				}

				// Sale price
				if ( Strings::endsWith( $importedKey, 'sale_price' ) ) {
					$roleBasedRules[ $roleKey ]['sale_price'] = $importedValue;

					$this->log( sprintf( '<b>%s:</b> Sale price was imported with "<b>%s</b>" value', $roleKey,
						is_null( $importedValue ) ? 'null' : $importedValue ) );
				}

				// Minimum quantity
				if ( Strings::endsWith( $importedKey, 'minimum' ) ) {
					$roleBasedRules[ $roleKey ]['minimum'] = $importedValue;

					$this->log( sprintf( '<b>%s:</b> Minimum order quantity was imported with "<b>%s</b>" value',
						$roleKey, is_null( $importedValue ) ? 'null' : $importedValue ) );
				}

				// Maximum quantity
				if ( Strings::endsWith( $importedKey, 'maximum' ) ) {
					$roleBasedRules[ $roleKey ]['maximum'] = $importedValue;

					$this->log( sprintf( '<b>%s:</b> Maximum order quantity was imported with "<b>%s</b>" value',
						$roleKey, is_null( $importedValue ) ? 'null' : $importedValue ) );
				}

				// Maximum quantity
				if ( Strings::endsWith( $importedKey, 'group_of' ) ) {
					$roleBasedRules[ $roleKey ]['group_of'] = $importedValue;

					$this->log( sprintf( '<b>%s:</b> Quantity step was imported with "<b>%s</b>" value', $roleKey,
						is_null( $importedValue ) ? 'null' : $importedValue ) );
				}
			}

			$requiredDataKeysToBeValidRule = array(
				'sale_price',
				'regular_price',
				'discount',
				'minimum',
				'maximum',
				'group_of',
			);

			$roleBasedRules = array_filter( $roleBasedRules, function ( $rule ) use ( $requiredDataKeysToBeValidRule ) {
				foreach ( $requiredDataKeysToBeValidRule as $requiredDataKey ) {
					if ( isset( $rule[ $requiredDataKey ] ) ) {
						return true;
					}
				}

				return false;
			} );

			$roleBasedRules = array_map( function ( $rule ) {
				return PricingRule::fromArray( $rule );
			}, $roleBasedRules );

			PricingRulesManager::updateProductRoleSpecificPricingRules( $post_id, $roleBasedRules );
		} else {
			$this->log( 'Skipped' );
		}

		$this->log( '<b>------------- Role and Customer Based Pricing Import End -------------</b>' );
	}

	public function getFieldsToImport() {

		$fields = array();

		foreach ( wp_roles()->roles as $role => $roleData ) {

			$fields[ '__rcbp_' . $role . '_enabled' ] = array(
				'title'        => '',
				'description'  => '',
				'type'         => 'is_enabled_role_field',
				'role'         => $role,
				'is_sub_field' => true,
			);

			$fields[ '_rcbp_' . $role . '_pricing_type' ] = array(
				'title'        => __( 'Pricing type', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'         => 'role_field',
				'role'         => $role,
				'is_sub_field' => true,
				'unSerialize'  => array( $this, 'unSerializePricingType' ),
				'description'  => __( 'Use \'flat\' or \'percentage\' to specify the pricing type for the role',
					'role-and-customer-based-pricing-for-woocommerce' ),
			);

			$fields[ '_rcbp_' . $role . '_discount' ] = array(
				'title'        => __( 'Discount amount', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'         => 'role_field',
				'is_sub_field' => true,
				'role'         => $role,
				'unSerialize'  => array( $this, 'unSerializeDiscount' ),
				'description'  => __( 'Specify number of discount (\'50\', not \'50%\') if using percentage pricing type.',
					'role-and-customer-based-pricing-for-woocommerce' ),
			);

			$fields[ '_rcbp_' . $role . '_regular_price' ] = array(
				'title'        => __( 'Regular price', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'         => 'role_field',
				'is_sub_field' => true,
				'role'         => $role,
				'unSerialize'  => function ( $price ) {

					if ( $this->isEmptyValue( $price ) ) {
						return null;
					}

					return wc_format_decimal( $price );
				},
				'description'  => __( 'Specify exact regular price expressed in numerical value (without currency symbol)',
					'role-and-customer-based-pricing-for-woocommerce' ),
			);

			$fields[ '_rcbp_' . $role . '_sale_price' ] = array(
				'title'        => __( 'Sale price', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'         => 'role_field',
				'is_sub_field' => true,
				'role'         => $role,
				'unSerialize'  => function ( $price ) {

					if ( $this->isEmptyValue( $price ) ) {
						return null;
					}

					return wc_format_decimal( $price );
				},
				'description'  => __( 'Specify exact sale price (crossed out price) expressed in numerical value (without currency symbol)',
					'role-and-customer-based-pricing-for-woocommerce' ),
			);

			$fields[ '_rcbp_' . $role . '_minimum' ] = array(
				'title'        => __( 'Minimum order quantity', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'         => 'role_field',
				'is_sub_field' => true,
				'role'         => $role,
				'unSerialize'  => function ( $min ) {

					if ( $this->isEmptyValue( $min ) ) {
						return null;
					}

					return intval( $min );
				},
				'description'  => __( 'Specify the minimal amount of products that one user can purchase for this role.',
					'role-and-customer-based-pricing-for-woocommerce' ),
			);

			$fields[ '_rcbp_' . $role . '_maximum' ] = array(
				'title'        => __( 'Maximum order quantity', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'         => 'role_field',
				'is_sub_field' => true,
				'role'         => $role,
				'unSerialize'  => function ( $min ) {

					if ( $this->isEmptyValue( $min ) ) {
						return null;
					}

					return intval( $min );
				},
				'description'  => __( 'Specify the maximum number of products available for purchase by this role in one order.',
					'role-and-customer-based-pricing-for-woocommerce' ),
			);

			$fields[ '_rcbp_' . $role . '_group_of' ] = array(
				'title'        => __( 'Quantity step', 'role-and-customer-based-pricing-for-woocommerce' ),
				'type'         => 'role_field',
				'is_sub_field' => true,
				'role'         => $role,
				'unSerialize'  => function ( $min ) {

					if ( $this->isEmptyValue( $min ) ) {
						return null;
					}

					return intval( $min );
				},
				'description'  => __( 'Specify by how many product quantity will increase or decrease when a customer adds the product to the cart',
					'role-and-customer-based-pricing-for-woocommerce' ),
			);

		}

		return $fields;
	}

	public function unSerializePricingType( $type ) {
		return in_array( $type, array( 'flat', 'percentage' ) ) ? $type : 'fixed';
	}

	public function unSerializeDiscount( $discount ) {
		if ( $this->isEmptyValue( $discount ) ) {
			return null;
		}

		return min( 100, floatval( $discount ) );
	}

	protected function isEmptyValue( $value ) {
		return $value === '' || $value === null || $value === false;
	}

	public function is_active_addon( $post_type = null ) {

		if ( ! is_plugin_active( 'wp-all-import-pro/wp-all-import-pro.php' ) && ! is_plugin_active( 'wp-all-import/plugin.php' ) ) {
			return false;
		}

		if ( null !== $post_type ) {
			if ( @in_array( $post_type, $this->active_post_types ) || empty( $this->active_post_types ) ) {
				return true;
			}

			return false;
		}

		return true;
	}

	public function run() {

		$this->active_post_types = array( 'product', 'variation' );

		add_filter( 'pmxi_addons', array( $this, 'wpai_api_register' ) );
		add_filter( 'wp_all_import_addon_parse', array( $this, 'wpai_api_parse' ) );
		add_filter( 'wp_all_import_addon_import', array( $this, 'wpai_api_import' ) );
		add_filter( 'pmxi_options_options', array( $this, 'wpai_api_options' ) );
		add_action( 'pmxi_extend_options_featured', array( $this, 'wpai_api_metabox' ), 10, 2 );


		add_action( 'pmxi_reimport', function ( $post_type, $post ) {
			?>
			<div class="input">
				<input type="hidden" name="update_role_customer_based_pricing" value="0"/>
				<input type="checkbox" id="update_role_customer_based_pricing" name="update_role_customer_based_pricing"
				       value="1" <?php echo $post['update_role_customer_based_pricing'] ? 'checked="checked"' : '' ?> />
				<label for="update_role_customer_based_pricing"><?php _e( 'Role/Customer Based Pricing',
						'wp_all_import_plugin' ) ?></label>
			</div>
			<?php
		}, 10, 2 );

		add_action( 'admin_init', array( $this, 'admin_notice_ignore' ) );
	}

	public function parse( $data ) {

		if ( ! $this->is_active_addon( $data['import']->options['custom_type'] ) ) {
			return false;
		}

		return $this->helper_parse( $data, $this->options_array() );
	}


	public function add_field(
		$field_slug,
		$field_name,
		$field_type,
		$enum_values = null,
		$tooltip = '',
		$is_html = true,
		$default_text = '',
		$role = ''
	) {

		$field = array(
			'name'          => $field_name,
			'type'          => $field_type,
			'enum_values'   => $enum_values,
			'tooltip'       => $tooltip,
			'is_sub_field'  => false,
			'is_main_field' => false,
			'is_html'       => $is_html,
			'default_text'  => $default_text,
			'role'          => $role,
			'slug'          => $field_slug,
		);

		$this->fields[ $field_slug ] = $field;

		if ( ! empty( $enum_values ) ) {
			foreach ( $enum_values as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( 'accordion' === $field['type'] ) {
						$this->fields[ $value['slug'] ]['is_sub_field'] = true;
					} else {
						foreach ( $value as $n => $param ) {
							if ( is_array( $param ) && ! empty( $this->fields[ $param['slug'] ] ) ) {
								$this->fields[ $param['slug'] ]['is_sub_field'] = true;
							}
						}
					}
				}
			}
		}

		return $field;
	}

	/**
	 *
	 * Add an option to WP All Import options list
	 *
	 * @param  string  $slug  - option name
	 * @param  string  $default_value  - default option value
	 *
	 */
	public function add_option( $slug, $default_value = '' ) {
		$this->options[ $slug ] = $default_value;
	}

	public function options_array() {

		$options_list = array();

		if ( ! empty( $this->fields ) ) {

			foreach ( $this->fields as $field_slug => $field_params ) {
				if ( in_array( $field_params['type'], array( 'title', 'plain_text', 'acf' ) ) ) {
					continue;
				}
				$default_value = '';
				if ( ! empty( $field_params['enum_values'] ) ) {
					foreach ( $field_params['enum_values'] as $key => $value ) {
						$default_value = $key;
						break;
					}
				}
				$options_list[ $field_slug ] = $default_value;
			}

		}

		if ( ! empty( $this->options ) ) {
			foreach ( $this->options as $slug => $value ) {
				$options_arr[ $slug ] = $value;
			}
		}

		$options_arr[ $this->slug ] = $options_list;
		$options_arr['rapid_addon'] = plugin_basename( __FILE__ );

		return $options_arr;

	}

	public function wpai_api_options( $all_options ) {

		$all_options = $all_options + $this->options_array();

		return $all_options;

	}

	public function wpai_api_register( $addons ) {

		if ( empty( $addons[ $this->slug ] ) ) {
			$addons[ $this->slug ] = 1;
		}

		return $addons;
	}

	public function wpai_api_parse( $functions ) {

		$functions[ $this->slug ] = array( $this, 'parse' );

		return $functions;

	}

	public function wpai_api_import( $functions ) {

		$functions[ $this->slug ] = array( $this, 'import' );

		return $functions;

	}

	public function import( $importData, $parsedData ) {

		if ( ! $this->is_active_addon( $importData['post_type'] ) ) {
			return;
		}

		$import_options = $importData['import']['options'][ $this->slug ];

		if ( ! empty( $parsedData ) ) {

			$this->logger = $importData['logger'];

			$post_id = $importData['pid'];
			$index   = $importData['i'];
			$data    = array();
			if ( ! empty( $this->fields ) ) {
				foreach ( $this->fields as $field_slug => $field_params ) {
					if ( in_array( $field_params['type'], array( 'title', 'plain_text' ) ) ) {
						continue;
					}

					$data[ $field_slug ] = $parsedData[ $field_slug ][ $index ];

					// apply mapping rules if they exist
					if ( ! empty( $import_options['mapping'][ $field_slug ] ) ) {
						$mapping_rules = json_decode( $import_options['mapping'][ $field_slug ], true );

						if ( ! empty( $mapping_rules ) && is_array( $mapping_rules ) ) {
							foreach ( $mapping_rules as $rule_number => $map_to ) {
								if ( isset( $map_to[ trim( $data[ $field_slug ] ) ] ) ) {
									$data[ $field_slug ] = trim( $map_to[ trim( $data[ $field_slug ] ) ] );
									break;
								}
							}
						}
					}
					// --------------------
				}
			}

			$this->processImport( $post_id, $data, $importData['import'], $importData['articleData'] );
		}

	}


	public function wpai_api_metabox( $post_type, $current_values ) {

		if ( ! $this->is_active_addon( $post_type ) ) {
			return;
		}

		$this->helper_metabox_top( $this->name );

		$visible_fields = 0;

		foreach ( $this->fields as $field_slug => $field_params ) {

			if ( $field_params['is_sub_field'] ) {
				continue;
			}

			$visible_fields ++;
		}


		$counter = 0;

		foreach ( $this->fields as $field_slug => $field_params ) {

			// do not render sub fields
			if ( $field_params['is_sub_field'] || 'role_field' === $field_params['type'] || 'is_enabled_role_field' === $field_params['type'] ) {
				continue;
			}

			$counter ++;

			$this->render_field( $field_params, $field_slug, $current_values, $visible_fields == $counter );
		}

		?>

		<div>
			<?php foreach ( wp_roles()->roles as $WPRole => $role_data ) : ?>

				<?php

				global $wp_roles;
				$roleName = isset( $wp_roles->role_names[ $WPRole ] ) ? translate_user_role( $wp_roles->role_names[ $WPRole ] ) : $WPRole;
				?>

				<div class="rcbp-import-role-block">
					<div class="rcbp-import-role-block__header">
						<h4>
							<label for="<?php echo esc_attr( $WPRole . '__enabled' ); ?>">
								<input type="checkbox" data-role-based-import

									<?php checked( ! empty( $current_values[ $this->slug ][ '__rcbp_' . $WPRole . '_enabled' ] ) ); ?>

									   id="<?php echo esc_attr( '__rcbp_' . $WPRole . '__enabled' ); ?>"
									   name="<?php echo esc_attr( $this->slug . '[__rcbp_' . $WPRole . '_enabled]' ); ?>">
								<?php echo esc_attr( $roleName ); ?>
							</label>
						</h4>
					</div>
					<div class="rcbp-import-role-block__body">
						<?php
						foreach ( $this->fields as $field_slug => $field_params ) {

							if ( 'role_field' === $field_params['type'] && $field_params['role'] === $WPRole ) {
								$this->render_field( $field_params, $field_slug, $current_values,
									$visible_fields == $counter, $WPRole );
							}
						}

						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php

		$this->helper_metabox_bottom();
	}

	public function render_field( $field_params, $field_slug, $current_values, $in_the_bottom = false, $role = false ) {

		if ( ! isset( $current_values[ $this->slug ][ $field_slug ] ) ) {
			$current_values[ $this->slug ][ $field_slug ] = isset( $field_params['default_text'] ) ? $field_params['default_text'] : '';
		}

		PMXI_API::add_field( 'simple', $field_params['name'], array(
			'tooltip'     => $field_params['tooltip'],
			'field_name'  => $this->slug . '[' . $field_slug . ']',
			'field_value' => ( '' == $current_values[ $this->slug ][ $field_slug ] && $this->isWizard ) ? $field_params['default_text'] : $current_values[ $this->slug ][ $field_slug ],
		) );
	}

public function helper_metabox_top( $name ) {
	?>
	<style type="text/css">

        .wpallimport-plugin .rcbp-import-role-block__header {
            padding: 0 20px;
            cursor: pointer;
            border: 1px solid #f5f5f5;
        }

        .wpallimport-plugin .rcbp-import-role-block__body {
            padding: 20px;
            background: #f5f5f5;
            display: none;
        }

        .wpallimport-plugin .wpallimport-addon div.input {
            margin-bottom: 15px;
        }

        .wpallimport-plugin .wpallimport-addon .custom-params tr td.action {
            width: auto !important;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-custom-fields-actions {
            right: 0 !important;
        }

        .wpallimport-plugin .wpallimport-addon table tr td.wpallimport-enum-input-wrapper {
            width: 80%;
        }

        .wpallimport-plugin .wpallimport-addon table tr td.wpallimport-enum-input-wrapper input {
            width: 100%;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-custom-fields-actions {
            float: right;
            right: 30px;
            position: relative;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-sub-options {
            margin-bottom: 15px;
            margin-top: -16px;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-sub-options .wpallimport-content-section {
            padding-bottom: 8px;
            margin: 0;
            border: none;
            padding-top: 1px;
            background: #f1f2f2;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-sub-options .wpallimport-collapsed-header {
            padding-left: 13px;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-sub-options .wpallimport-collapsed-header h3 {
            font-size: 14px;
            margin: 6px 0;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-sub-options-full-width {
            bottom: -40px;
            margin-bottom: 0;
            margin-left: -25px;
            margin-right: -25px;
            position: relative;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-sub-options-full-width .wpallimport-content-section {
            margin: 0;
            border-top: 1px solid #ddd;
            border-bottom: none;
            border-right: none;
            border-left: none;
            background: #f1f2f2;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-sub-options-full-width .wpallimport-collapsed-header h3 {
            margin: 14px 0;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-dependent-options {
            margin-left: 1px;
            margin-right: -1px;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-dependent-options .wpallimport-content-section {
            border: 1px solid #ddd;
            border-top: none;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-full-with-bottom {
            margin-left: -25px;
            margin-right: -25px;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-full-with-not-bottom {
            margin: 25px -1px 25px 1px;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-full-with-not-bottom .wpallimport-content-section {
            border: 1px solid #ddd;
        }

        .wpallimport-plugin .wpallimport-addon .wpallimport-add-on-options-title {
            font-size: 14px;
            margin: 45px 0 15px 0;
        }
	</style>
	<script>
        jQuery(document).ready(function ($) {
            $('.rcbp-import-role-block__header').click(function (e) {

                if (e && e.target.hasAttribute('data-role-based-import')) {
                    return;
                }

                var checkbox = $(this).find('[data-role-based-import]');

                checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
            });

            $('[data-role-based-import]').change(function (e) {
                if ($(e.target).is(':checked')) {
                    $(this).closest('.rcbp-import-role-block').find('.rcbp-import-role-block__body').show();
                } else {
                    $(this).closest('.rcbp-import-role-block').find('.rcbp-import-role-block__body').hide();
                }
            }).trigger('change');
        });

	</script>

	<div class="wpallimport-collapsed wpallimport-section wpallimport-addon ' . $this->slug . ' closed">
		<div class="wpallimport-content-section">
			<div class="wpallimport-collapsed-header">
				<h3><?php esc_attr_e( $name, 'pmxi_plugin' ); ?></h3>
			</div>
			<div class="wpallimport-collapsed-content" style="padding: 0;">
				<div class="wpallimport-collapsed-content-inner">
					<table class="form-table" style="max-width:none;">
						<tr>
							<td colspan="3">
								<?php
								}

								public function helper_metabox_bottom() {
								?>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
	<?php
}

	public function helper_parse( $parsingData, $options ) {
		$data = array(); // parsed data

		/**
		 * Extracted vars
		 *
		 * @var $import
		 * @var $xml
		 * @var string $xpath_prefix
		 * @var int $count
		 */

		allowedExtract( $parsingData );

		if ( ! empty( $import->options[ $this->slug ] ) ) {

			$this->logger = $parsingData['logger'];

			$cxpath = $xpath_prefix . $import->xpath;

			$tmp_files = array();

			foreach ( $options[ $this->slug ] as $option_name => $option_value ) {
				if ( isset( $import->options[ $this->slug ][ $option_name ] ) && '' != $import->options[ $this->slug ][ $option_name ] ) {
					if ( 'xpath' == $import->options[ $this->slug ][ $option_name ] ) {
						if ( '' == $import->options[ $this->slug ]['xpaths'][ $option_name ] ) {
							if ( $count ) {
								$data[ $option_name ] = array_fill( 0, $count, '' );
							}
						} else {
							$data[ $option_name ] = XmlImportParser::factory( $xml, $cxpath,
								(string) $import->options[ $this->slug ]['xpaths'][ $option_name ], $file )->parse();
							$tmp_files[]          = $file;
						}
					} else {
						$data[ $option_name ] = XmlImportParser::factory( $xml, $cxpath,
							(string) $import->options[ $this->slug ][ $option_name ], $file )->parse();
						$tmp_files[]          = $file;
					}


				} else {
					$data[ $option_name ] = array_fill( 0, $count, '' );
				}

			}

			foreach ( $tmp_files as $file ) { // remove all temporary files created
				unlink( $file );
			}

		}

		return $data;
	}

	public function needUpdateRoleBase( $import_options ) {

		$meta_key = '_role_based_pricing_rules';

		$import_options = $import_options['options'];

		if ( $import_options['update_role_customer_based_pricing'] ) {
			return true;
		}

		if ( 'yes' == $import_options['update_all_data'] ) {
			return true;
		}

		if ( 'full_update' == $import_options['update_custom_fields_logic'] ) {
			return true;
		}

		if ( 'only' == $import_options['update_custom_fields_logic'] && ! empty( $import_options['custom_fields_list'] ) && is_array( $import_options['custom_fields_list'] ) && in_array( $meta_key,
				$import_options['custom_fields_list'] ) ) {
			return true;
		}

		if ( 'all_except' == $import_options['update_custom_fields_logic'] && ( empty( $import_options['custom_fields_list'] ) || ! in_array( $meta_key,
					$import_options['custom_fields_list'] ) ) ) {
			return true;
		}

		return false;

	}

	public function admin_notice_ignore() {
		if ( isset( $_GET[ $this->slug . '_ignore' ] ) && '0' == $_GET[ $this->slug . '_ignore' ] ) {
			update_option( $this->slug . '_ignore', 'true' );
		}
	}

	public function log( $m = false ) {
		$m && $this->logger && call_user_func( $this->logger, $m );
	}

}
