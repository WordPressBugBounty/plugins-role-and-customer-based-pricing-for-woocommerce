<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form;

use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\Tabs\ProductAndCategories;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\Tabs\Pricing;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\Tabs\UsersAndRoles;
use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\RoleSpecificPricingCPT;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;
use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use WP_Post;

class Form {

	use ServiceContainerTrait;

	/**
	 * Tabs
	 *
	 * @var FormTab[]
	 */
	protected $tabs;

	protected $defaultTab = 'pricing';

	protected $pricingRuleInstance = null;

	public function __construct() {

		add_action( 'init', function () {
			$this->tabs = apply_filters( 'role_and_customer_based_pricing/global_pricing/form_tabs', array(
					new Pricing( $this ),
					new ProductAndCategories( $this ),
					new UsersAndRoles( $this ),
			) );
		} );

		add_action( 'edit_form_after_title', function ( WP_Post $post ) {
			if ( RoleSpecificPricingCPT::SLUG !== $post->post_type ) {
				return;
			}

			$this->render( $post );
		} );
	}

	protected function includeAssets() {
		?>
		<style>
			/**
			* Externals
			 */
			/* do not display any notices on rule creation */
			.wrap .notice:not(.notice-success) {
				display: none
			}

			.rcbp-global-pricing-rule-form .woocommerce-help-tip {
				margin-left: 5px;
			}

			.rcbp-global-pricing-rule-hint {
				display: flex;
				align-items: center;
				padding: 10px 10px;
				border: 1px solid #c3c4c7;
				background: #f6f7f7;
				color: var(--wp-admin-theme-color, #2271b1) !important;
				margin-bottom: 20px;
			}

			.rcbp-global-pricing-rule-hint--top-level {
				margin-top: 10px;
				border: 1px solid #888;
			}

			.rcbp-global-pricing-rule-hint__icon {
				margin-right: 10px;
			}

			.rcbp-global-pricing-rule-form {
				margin: 20px 0;
				display: flex;
				overflow: hidden;
				border-radius: 3px;
				flex-wrap: nowrap;
			}

			.rcbp-global-pricing-rule-form__tabs {
				width: 30%;
				max-width: 300px;
				min-width: 250px;
			}

			.rcbp-global-pricing-rule-form-tab {
				background: #fff;
				border-bottom: 1px solid #e8e8e8;
				border-left: 1px solid #e8e8e8;
				overflow: hidden;
				cursor: pointer;
				display: flex;
				align-items: center;
				padding: 15px 10px;
			}

			.rcbp-global-pricing-rule-form-tab:first-child {
				border-top: 1px solid #e8e8e8;
			}

			.rcbp-global-pricing-rule-form-tab:hover:not(.rcbp-global-pricing-rule-form-tab--active) {
				background: #fbfbfb;
			}

			.rcbp-global-pricing-rule-form-tab--active {
				cursor: default;
				background: #f6f7f7;
			}

			.rcbp-global-pricing-rule-form-tab__icon {
				transition: all .1s;
				margin-right: 10px;
				height: 40px;
				aspect-ratio: 1/1;
				border-radius: 50%;
				background: #f6f7f7;
				text-align: center;
				color: var(--wp-admin-theme-color, #2271b1);
				font-size: 20px;
				font-weight: bold;
				display: flex;
				justify-content: center;
				align-items: center;
			}

			.rcbp-global-pricing-rule-form-tab--active h3,
			.rcbp-global-pricing-rule-form-tab--active div {
				color: var(--wp-admin-theme-color, #2271b1) !important;
			}

			.rcbp-global-pricing-rule-form-tab--active .rcbp-global-pricing-rule-form-tab__icon {
				background: #fff;
			}

			.rcbp-global-pricing-rule-form-tab__title h3 {
				font-size: 1.1em;
				margin: 0;
			}

			.rcbp-global-pricing-rule-form-tab__title div {
				margin-top: 5px;
				color: #777;
			}

			.rcbp-global-pricing-rule-form-tab-content {
				display: none;
			}

			.rcbp-global-pricing-rule-form-tab-content--active {
				display: block;
			}

			.rcbp-global-pricing-rule-form__content {
				width: 70%;
				background: #fff;
				flex-grow: 1;
				padding: 20px;
				border: 1px solid #e8e8e8;
				box-shadow: 0 0 8px rgba(0, 0, 0, .1);
			}

			.rcbp-global-pricing-rule-form__content.woocommerce_options_panel {
				display: block !important;
				margin: 0;
			}

			.woocommerce_options_panel .rcbp-custom-field label {
				line-height: initial !important;
			}

			.rcbp-global-pricing-rule-form input[type="text"],
			.rcbp-global-pricing-rule-form input[type="number"] {
				width: 50% !important;
			}
			
			.rcbp-global-pricing-title {
				font-size: 18px;
				margin-bottom: 15px;
				margin-top: 10px;
				padding-bottom: 15px;
				border-bottom: 1px solid #eee;
				display: flex;
				align-items:center;
			}

			@media screen and (max-width: 1248px) {

				.rcbp-global-pricing-rule-form input[type="text"],
				.rcbp-global-pricing-rule-form input[type="number"] {
					width: 100% !important;
				}

				.rcbp-global-pricing-rule-form {
					flex-wrap: wrap;
				}

				.rcbp-global-pricing-rule-form__tabs {
					display: flex;
					max-width: 100%;
					width: 100%;
				}

				.rcbp-global-pricing-rule-form-tab__icon {
					display: none;
				}

				.rcbp-global-pricing-rule-form-tab--active {
					border-bottom: 3px solid var(--wp-admin-theme-color, #2271b1);
				}
			}
		</style>
		<script>
			jQuery(document).ready(function () {
				let tabs = jQuery('.rcbp-global-pricing-rule-form-tab');
				let tabsContent = jQuery('.rcbp-global-pricing-rule-form-tab-content');

				tabs.click(function (e) {
					e.preventDefault();

					tabsContent.removeClass('rcbp-global-pricing-rule-form-tab-content--active');
					tabs.removeClass('rcbp-global-pricing-rule-form-tab--active');

					jQuery(this).addClass('rcbp-global-pricing-rule-form-tab--active');

					const target = jQuery(this).data('target');

					jQuery('#' + target).addClass('rcbp-global-pricing-rule-form-tab-content--active');
				});
			});
		</script>
		<?php
	}

	protected function render( WP_Post $post ) {

		$this->includeAssets();

		if ( ! $this->isNewRule() && ! $this->getPricingRuleInstance( $post )->isValidPricing() ) {
			$this->tabs[0]->renderHint( __( 'The pricing rule does not affect prices. The rule will be skipped.',
					'role-and-customer-based-pricing-for-woocommerce' ), array( 'custom_class' => 'rcbp-global-pricing-rule-hint--top-level' ) );
		}

		?>
		<div class="rcbp-global-pricing-rule-form">

			<nav class="rcbp-global-pricing-rule-form__tabs">
				<?php foreach ( $this->tabs as $tab ) : ?>
					<div class="rcbp-global-pricing-rule-form-tab <?php echo esc_attr( $tab->getId() === $this->defaultTab ? 'rcbp-global-pricing-rule-form-tab--active' : '' ); ?>"
						 data-target="rcbp-global-pricing-rule-form-tab-<?php echo esc_attr( $tab->getId() ); ?>">

						<div class="rcbp-global-pricing-rule-form-tab__icon" style="">
							<?php if ( $tab->getIcon() === '$' ) : ?>
								<span>$</span>
							<?php else : ?>
								<span class="dashicons <?php echo esc_attr( $tab->getIcon() ); ?>"></span>
							<?php endif; ?>
						</div>

						<div class="rcbp-global-pricing-rule-form-tab__title">
							<h3>
								<?php echo esc_html( $tab->getTitle() ); ?>
							</h3>
							<div><?php echo esc_html( $tab->getDescription() ); ?></div>
						</div>
					</div>
				<?php endforeach; ?>
			</nav>

			<section class="rcbp-global-pricing-rule-form__content woocommerce_options_panel">
				<?php foreach ( $this->tabs as $tab ) : ?>
					<div class="rcbp-global-pricing-rule-form-tab-content <?php echo esc_attr( $tab->getId() === $this->defaultTab ? 'rcbp-global-pricing-rule-form-tab-content--active' : '' ); ?>"
						 id="rcbp-global-pricing-rule-form-tab-<?php echo esc_attr( $tab->getId() ); ?>">
						<?php
							$tab->render( $this->getPricingRuleInstance( $post ) );
						?>
					</div>
				<?php endforeach; ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Get pricing rule instance
	 *
	 * @param  WP_Post  $post
	 *
	 * @return GlobalPricingRule
	 */
	public function getPricingRuleInstance( WP_Post $post ): GlobalPricingRule {
		if ( empty( $this->pricingRuleInstance ) ) {
			$this->pricingRuleInstance = GlobalPricingRule::build( $post->ID );
		}

		return $this->pricingRuleInstance;
	}

	public function isNewRule(): bool {
		global $pagenow;

		return 'post-new.php' == $pagenow;
	}
}
