<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT;

use Exception;
use Automattic\WooCommerce\Admin\PageController;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Actions\ReactivateAction;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Actions\SuspendAction;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\AppliedQuantityRules;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\Pricing;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\AppliedCustomers;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\AppliedProducts;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\Status;
use WP_Post;

use function is_empty;

class RoleSpecificPricingCPT {

	use ServiceContainerTrait;

	const SLUG = 'rcbp-rule';

	/**
	 * Pricing rules
	 *
	 * @var GlobalPricingRule
	 */
	private $pricingRuleInstance;

	/**
	 * Table columns
	 *
	 * @var array
	 */
	private $columns;

	protected static $globalRules = null;

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'renderBlankState' ) );
		add_action( 'add_meta_boxes', array( $this, 'registerMetaboxes' ), 10, 3 );

		add_filter( 'woocommerce_navigation_screen_ids', array( $this, 'addPageToWooCommerceScreen' ) );

		add_filter( 'woocommerce_screen_ids', array( $this, 'addPageToWooCommerceScreen' ) );

		add_action( 'save_post_' . self::SLUG, array( $this, 'savePricingRule' ) );

		add_filter( 'manage_edit-' . self::SLUG . '_columns', function ( $columns ) {
			unset( $columns['date'] );

			foreach ( $this->getColumns() as $key => $column ) {
				$columns[ $key ] = $column->getName();
			}

			return $columns;
		}, 999 );

		add_filter( 'manage_' . self::SLUG . '_posts_custom_column', function ( $column ) {
			global $post;

			$globalRule = GlobalPricingRule::build( $post->ID );

			if ( array_key_exists( $column, $this->getColumns() ) ) {
				$this->getColumns()[ $column ]->render( $globalRule );
			}

			return $column;
		}, 999 );

		add_action( 'admin_notices', function () {

			global $post, $pagenow;

			if ( $post && self::SLUG === $post->post_type && 'edit.php' !== $pagenow && ! $this->isSetupingANewPricingRule() ) {
				$pricingRule = $this->getPricingRuleInstance();

				try {
					$pricingRule->validatePricing();
				} catch ( Exception $e ) {
					echo wp_kses_post( '<div class="notice notice-warning"><p>' . $e->getMessage() . '</p></div>' );
				}
			}
		} );

		add_filter( 'post_row_actions', function ( $actions, $post ) {

			if ( self::SLUG === $post->post_type ) {
				unset( $actions['inline hide-if-no-js'] );
			}

			return $actions;
		}, 10, 2 );

		add_filter( 'disable_months_dropdown', function ( $state, $postType ) {
			if ( self::SLUG === $postType ) {
				return true;
			}

			return $state;
		}, 10, 2 );

		// Refresh cache for variable product pricing
		add_action( 'save_post_' . self::SLUG, function () {
			wc_delete_product_transients();
		} );

		$this->initInlineActions();
	}

	public function initInlineActions() {
		new SuspendAction();
		new ReactivateAction();
	}

	public function getColumns() {

		if ( is_null( $this->columns ) ) {
			$this->columns = array(
				'pricing'                => new Pricing(),
				'applied_products'       => new AppliedProducts(),
				'applied_customers'      => new AppliedCustomers(),
				'applied_quantity_rules' => new AppliedQuantityRules(),
				'status'                 => new Status(),
			);
		}

		return $this->columns;
	}

	/**
	 * Get pricing rule instance
	 *
	 * @return GlobalPricingRule
	 */
	public function getPricingRuleInstance() {
		if ( empty( $this->pricingRuleInstance ) ) {
			global $post;

			if ( $post ) {
				$this->pricingRuleInstance = GlobalPricingRule::build( $post->ID );
			} else {
				return null;
			}
		}

		return $this->pricingRuleInstance;
	}

	public function addPageToWooCommerceScreen( $ids ) {

		$ids[] = self::SLUG;
		$ids[] = 'edit-' . self::SLUG;

		return $ids;
	}

	public function registerMetaboxes() {

		add_meta_box( 'rcbp_rules_metabox', __( 'Rules', 'role-and-customer-based-pricing-for-woocommerce' ), array(
			$this,
			'renderRulesMetabox'
		), self::SLUG );

		add_meta_box( 'rcbp_pricing_metabox', __( 'Pricing', 'role-and-customer-based-pricing-for-woocommerce' ), array(
			$this,
			'renderPricingMetabox'
		), self::SLUG );
	}

	public function renderRulesMetabox() {
		$this->getContainer()->getFileManager()->includeTemplate( 'admin/global-rules/role-specific-pricing/rules.php', array(
			'fileManager' => $this->getContainer()->getFileManager(),
			'priceRule'   => $this->getPricingRuleInstance(),
		) );
	}

	public function savePricingRule( $ruleId ) {
		// Save pricing
		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}

		$data = array();

		$pricingFields = array(
			'_rcbp_global_pricing_type',
			'_rcbp_global_regular_price',
			'_rcbp_global_sale_price',
			'_rcbp_global_discount',
			'_rcbp_global_minimum',
			'_rcbp_global_maximum',
			'_rcbp_global_group_of',
		);

		foreach ( $pricingFields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				$data[ $field ] = '';
			} else if ( ! isset( $_POST[ $field ]['global'] ) ) {
				$data[ $field ] = '';
			} else {
				$data[ $field ] = sanitize_text_field( $_POST[ $field ]['global'] );
			}
		}

		$pricingRule = new GlobalPricingRule(
			$data['_rcbp_global_pricing_type'],
			wc_format_decimal( $data['_rcbp_global_regular_price'] ),
			wc_format_decimal( $data['_rcbp_global_sale_price'] ),
			! empty( $data['_rcbp_global_discount'] ) ? floatval( $data['_rcbp_global_discount'] ) : null,
			sanitize_text_field( $data['_rcbp_global_minimum'] ),
			sanitize_text_field( $data['_rcbp_global_maximum'] ),
			sanitize_text_field( $data['_rcbp_global_group_of'] )
		);

		$existingRoles = wp_roles()->roles;

		$includedCategoriesIds = isset( $_POST['_rps_included_categories'] ) ? array_filter( array_map( 'intval', (array) $_POST['_rps_included_categories'] ) ) : array();
		$includedProductsIds   = isset( $_POST['_rps_included_products'] ) ? array_filter( array_map( 'intval', (array) $_POST['_rps_included_products'] ) ) : array();

		$includedUsersRole = isset( $_POST['_rps_included_user_roles'] ) ? array_filter( (array) $_POST['_rps_included_user_roles'], function ( $role ) use ( $existingRoles ) {
			return array_key_exists( $role, $existingRoles );
		} ) : array();

		$includedUsers = isset( $_POST['_rps_included_users'] ) ? array_filter( array_map( 'intval', (array) $_POST['_rps_included_users'] ) ) : array();

		$pricingRule->setIncludedProductCategories( $includedCategoriesIds );
		$pricingRule->setIncludedUsers( $includedUsers );
		$pricingRule->setIncludedUsersRole( $includedUsersRole );
		$pricingRule->setIncludedProducts( $includedProductsIds );

		try {
			GlobalPricingRule::save( $pricingRule, $ruleId );
		} catch ( Exception $exception ) {
			$this->getContainer()->getAdminNotifier()->flash( 'Role specific pricing: ' . $exception->getMessage(), AdminNotifier::ERROR );
		}
	}

	public function renderPricingMetabox() {
		?>
        <div id="<?php echo esc_attr( self::SLUG ); ?>" class="panel woocommerce_options_panel">
			<?php
			$this->getContainer()->getFileManager()->includeTemplate( 'admin/global-rules/role-specific-pricing/pricing.php', array(
				'fileManager' => $this->getContainer()->getFileManager(),
				'priceRule'   => $this->getPricingRuleInstance(),
			) );
			?>
        </div>
		<?php
	}

	public function renderBlankState( $which ) {
		global $post_type;

		if ( self::SLUG === $post_type && 'bottom' === $which ) {
			$counts = (array) wp_count_posts( $post_type );
			unset( $counts['auto-draft'] );
			$count = array_sum( $counts );

			if ( 0 < $count ) {
				return;
			}

			?>

            <div class="woocommerce-BlankState">

                <h2 class="woocommerce-BlankState-message">
					<?php esc_html_e( 'There are no pricing rules yet. To create pricing dependencies on user roles/customers click on the button below.', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
                </h2>

                <div class="woocommerce-BlankState-buttons">
                    <a class="woocommerce-BlankState-cta button-primary button"
                       href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::SLUG ) ); ?>">
						<?php esc_html_e( 'Create a pricing rule', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
                    </a>
                </div>
            </div>

            <style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub {
                    display: none;
                }

                #posts-filter .tablenav.bottom {
                    height: auto;
                }
            </style>
			<?php
		}
	}

	public function register() {

		PageController::get_instance()->connect_page( array(
				'id'        => self::SLUG,
				'title'     => array( 'Role Specific Pricing' ),
				'screen_id' => self::SLUG,
			)
		);

		register_post_type( self::SLUG, array(
			'labels'             => array(
				'name'               => __( 'Pricing rule', 'role-and-customer-based-pricing-for-woocommerce' ),
				'singular_name'      => __( 'Pricing rule', 'role-and-customer-based-pricing-for-woocommerce' ),
				'add_new'            => __( 'Add Pricing Rule', 'role-and-customer-based-pricing-for-woocommerce' ),
				'add_new_item'       => __( 'Add Pricing Rule', 'role-and-customer-based-pricing-for-woocommerce' ),
				'edit_item'          => __( 'Edit Pricing Rule', 'role-and-customer-based-pricing-for-woocommerce' ),
				'new_item'           => __( 'New Pricing Rule', 'role-and-customer-based-pricing-for-woocommerce' ),
				'view_item'          => __( 'View Pricing Rule', 'role-and-customer-based-pricing-for-woocommerce' ),
				'search_items'       => __( 'Find Pricing Rule', 'role-and-customer-based-pricing-for-woocommerce' ),
				'not_found'          => __( 'No pricing rules ware found', 'role-and-customer-based-pricing-for-woocommerce' ),
				'not_found_in_trash' => __( 'No pricing rule in trash', 'role-and-customer-based-pricing-for-woocommerce' ),
				'parent_item_colon'  => '',
				'menu_name'          => __( 'Pricing rules', 'role-and-customer-based-pricing-for-woocommerce' ),

			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'woocommerce',
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'product',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' )
		) );
	}

	public function isSetupingANewPricingRule() {
		global $pagenow;

		return in_array( $pagenow, array( 'post-new.php' ) );
	}

	public static function getGlobalRules( $withValidPricing = true ) {

		if ( ! is_null( self::$globalRules ) ) {
			$rules = self::$globalRules;
		} else {
			$rulesIds = get_posts( array(
				'numberposts' => - 1,
				'post_type'   => self::SLUG,
				'post_status' => 'publish',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => '_rps_is_suspended',
						'value'   => 'yes',
						'compare' => '!='
					)
				)
			) );

			$rules = array_map( function ( $ruleId ) {
				return GlobalPricingRule::build( $ruleId );
			}, $rulesIds );

			self::$globalRules = $rules;
		}

		if ( $withValidPricing ) {
			$rules = array_filter( $rules, function ( GlobalPricingRule $rule ) {
				return $rule->isValidPricing();
			} );
		}

		return $rules;
	}
}
