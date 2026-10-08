<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT;

use Exception;
use Automattic\WooCommerce\Admin\PageController;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\Core\AdminNotifier;
use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\PricingRulesDispatcher;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Actions\ReactivateAction;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Actions\SuspendAction;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\AppliedQuantityRules;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\Pricing;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\AppliedCustomers;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\AppliedProducts;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Columns\Status;
use WP_Post;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\Form;

class RoleSpecificPricingCPT {

	use ServiceContainerTrait;

	const SLUG = 'rcbp-rule';
	
	const SAVE_NONCE_ACTION = 'rcbp_save_global_rule';
	
	const SAVE_NONCE_NAME = '_rcbp_global_rule_nonce';

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
		new Form();

		add_action( 'init', array( $this, 'register' ) );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'renderBlankState' ) );

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

		// Refresh cached rules and variable product price transients when rules change
		add_action( 'save_post_' . self::SLUG, array( __CLASS__, 'flushCaches' ) );
		
		add_action( 'before_delete_post', function ( $postId ) {
			if ( self::SLUG === get_post_type( $postId ) ) {
				self::flushCaches();
			}
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

	public function savePricingRule( $ruleId ) {
		
		// Only handle submissions of the rule form. Trash, untrash, bulk edit, autosave and programmatic updates skip this.
		$nonce = isset( $_POST[ self::SAVE_NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::SAVE_NONCE_NAME ] ) ) : '';
		
		if ( ! wp_verify_nonce( $nonce, self::getSaveNonceAction( $ruleId ) ) ) {
			return;
		}
		
		if ( wp_is_post_autosave( $ruleId ) || wp_is_post_revision( $ruleId ) ) {
			return;
		}
		
		if ( ! current_user_can( 'edit_post', $ruleId ) ) {
			return;
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
			$data[ $field ] = isset( $_POST[ $field ]['global'] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ]['global'] ) ) : '';
		}
		
		$pricingRule = new GlobalPricingRule(
			$data['_rcbp_global_pricing_type'],
			wc_format_decimal( $data['_rcbp_global_regular_price'] ),
			wc_format_decimal( $data['_rcbp_global_sale_price'] ),
			! empty( $data['_rcbp_global_discount'] ) ? floatval( $data['_rcbp_global_discount'] ) : null,
			$data['_rcbp_global_minimum'],
			$data['_rcbp_global_maximum'],
			$data['_rcbp_global_group_of']
		);
		
		$existingRoles = wp_roles()->roles;
		
		$includedUsersRole = isset( $_POST['_rps_included_user_roles'] ) ? array_filter( array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['_rps_included_user_roles'] ) ), function ( $role ) use ( $existingRoles ) {
			return array_key_exists( $role, $existingRoles );
		} ) : array();
		
		$pricingRule->setIncludedProductCategories( $this->getPostedIds( '_rps_included_categories' ) );
		$pricingRule->setIncludedProductTags( $this->getPostedIds( '_rps_included_tags' ) );
		$pricingRule->setIncludedProductBrands( $this->getPostedIds( '_rps_included_brands' ) );
		$pricingRule->setIncludedProducts( $this->getPostedIds( '_rps_included_products' ) );
		$pricingRule->setIncludedUsers( $this->getPostedIds( '_rps_included_users' ) );
		$pricingRule->setIncludedUsersRole( $includedUsersRole );
		
		// Suspension is changed only through the Suspend and Reactivate actions, so keep the stored state
		$pricingRule->setIsSuspended( GlobalPricingRule::build( $ruleId )->isSuspended() );
		
		try {
			GlobalPricingRule::save( $pricingRule, $ruleId );
		} catch ( Exception $exception ) {
			$this->getContainer()->getAdminNotifier()->flash( 'Role specific pricing: ' . $exception->getMessage(), AdminNotifier::ERROR );
		}
	}
	
	/**
	 * Nonce action for the rule form, bound to the rule
	 *
	 * @param  int  $ruleId
	 *
	 * @return string
	 */
	public static function getSaveNonceAction( $ruleId ) {
		return self::SAVE_NONCE_ACTION . '_' . intval( $ruleId );
	}
	
	/**
	 * Posted list of ids, cleaned. The nonce is verified in savePricingRule().
	 *
	 * @param  string  $key
	 *
	 * @return int[]
	 */
	protected function getPostedIds( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $key ] ) ) {
			return array();
		}
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return array_filter( array_map( 'intval', (array) wp_unslash( $_POST[ $key ] ) ) );
	}

	public function renderBlankState( $which ) {
		global $post_type;

		if ( self::SLUG === $post_type && 'top' === $which ) {
			$counts = (array) wp_count_posts( $post_type );
			unset( $counts['auto-draft'] );
			$count = array_sum( $counts );

			if ( 0 < $count ) {
				return;
			}

			?>

			<div class="rcbp-blank-state">
				<div class="rcbp-blank-state__inner">
					<h2 class="rcbp-blank-state__title">
						<?php esc_html_e( 'Create Your First Global Pricing Rule', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
					</h2>
					
					<p class="rcbp-blank-state__description">
						<?php esc_html_e( 'There are no pricing rules yet. To create pricing dependencies on user roles or specific customers, click on the button below.', 'role-and-customer-based-pricing-for-woocommerce' ); ?>
					</p>

					<div class="rcbp-blank-state__actions">
						<a class="rcbp-button-primary button button-primary button-large"
						   href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::SLUG ) ); ?>">
							<span style="line-height: 1; padding-top: 2px;"><?php esc_html_e( 'Create a pricing rule', 'role-and-customer-based-pricing-for-woocommerce' ); ?></span>
						</a>
					</div>
				</div>
			</div>

			<style>
				.rcbp-blank-state {
					background: #ffffff;
					border: 1px solid #e2e4e7;
					border-radius: 8px;
					box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
					text-align: center;
					margin: 40px auto;
					max-width: 600px;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					overflow: hidden;
				}
				
				.rcbp-blank-state__inner {
					padding: 50px 40px;
				}

				.rcbp-blank-state__title {
					font-size: 24px;
					font-weight: 600;
					color: #1d2327;
					margin: 0 0 12px 0;
					line-height: 1.3;
				}
				
				.rcbp-blank-state__description {
					font-size: 15px;
					color: #646970;
					line-height: 1.6;
					margin: 0 0 32px 0;
					max-width: 480px;
					margin-left: auto;
					margin-right: auto;
				}
				
				.rcbp-button-primary {
					display: inline-flex;
					align-items: center;
					justify-content: center;
					padding: 0 24px !important;
					height: 42px !important;
					font-size: 14px !important;
					font-weight: 600 !important;
					border-radius: 4px !important;
					transition: all 0.2s ease;
				}
				
				.rcbp-button-primary:hover {
					transform: translateY(-1px);
					box-shadow: 0 4px 8px rgba(0, 112, 188, 0.2);
				}

				#posts-filter .wp-list-table,
				#posts-filter .tablenav.bottom,
				.tablenav.top .actions,
				.wrap .subsubsub {
					display: none;
				}

				#posts-filter .tablenav.top {
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

	/**
	 * Drop cached rules and WooCommerce price transients so rule changes show up immediately
	 */
	public static function flushCaches() {
		self::$globalRules = null;
		
		PricingRulesDispatcher::resetCache();
		
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}
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
