<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Actions;

use MeowCrew\RoleAndCustomerBasedPricing\Core\AdminNotifier;
use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;
use WP_Post;

class SuspendAction {

	const ACTION = 'rcbp_suspend_global_rule';

	use ServiceContainerTrait;

	public function __construct() {

		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );

		add_filter( 'post_row_actions', function ( $actions, WP_Post $post ) {
			$rule = GlobalPricingRule::build( $post->ID );

			if ( ! $rule->isSuspended() ) {
				$actions['suspend'] = sprintf( '<a href="%s">%s</a>', $this->getRunLink( $post->ID ), $this->getName() );
			}

			return $actions;
		}, 10, 2 );
	}

	public function getName() {
		return __( 'Suspend', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function handle() {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( $_GET['_wpnonce'] ) : false;

		if ( wp_verify_nonce( $nonce, self::ACTION ) ) {
			$ruleId = isset( $_GET['rule_id'] ) ? intval( $_GET['rule_id'] ) : false;

			if ( $ruleId ) {
				$rule = GlobalPricingRule::build( $ruleId );

				if ( false !== get_post_status( $ruleId ) ) {
					$rule->suspend();

					try {
						GlobalPricingRule::save( $rule, $ruleId );

						$this->getContainer()->getAdminNotifier()->flash( __( 'The rule suspended successfully.', 'role-and-customer-based-pricing-for-woocommerce' ), AdminNotifier::SUCCESS, true );

					} catch ( \Exception $e ) {
						wp_die( 'Invalid rule to suspend.' );
					}
				}

			}

		} else {
			wp_die( 'You\'re not allowed to run this action' );
		}

		return wp_safe_redirect( wp_get_referer() );
	}

	public function getRunLink( $id ) {
		return add_query_arg( array(
			'rule_id' => $id,
			'action'  => self::ACTION,
		), wp_nonce_url( admin_url( 'admin-post.php' ), self::ACTION ) );
	}
}
