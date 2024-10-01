<?php namespace MeowCrew\RoleAndCustomerBasedPricing\RoleManagement\Actions;

use Exception;
use MeowCrew\RoleAndCustomerBasedPricing\Core\AdminNotifier;
use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;

abstract class RoleManagementPageAction {

	use ServiceContainerTrait;

	abstract public function handle();

	abstract public function getActionSlug();

	public function __construct() {
		add_action( 'admin_post_' . $this->getActionSlug(), array( $this, 'execute' ) );
	}

	public function getURL( $role = '' ) {
		return wp_nonce_url( add_query_arg( array(
			'action' => $this->getActionSlug(),
			'role'   => $role,
		), admin_url( 'admin-post.php' ) ), $this->getActionSlug() );
	}

	public function execute() {
		try {
			$this->validate();

			$this->handle();

		} catch ( Exception $exception ) {
			$this->getContainer()->getAdminNotifier()->flash( $exception->getMessage(), AdminNotifier::ERROR, true );

			return wp_redirect( wp_get_referer() );
		}
	}

	/**
	 * Validate request
	 *
	 * @throws Exception
	 */
	public function validate() {
		$this->validateNonce();
	}

	/**
	 * Validate nonce
	 *
	 * @throws Exception
	 */
	public function validateNonce() {
		$nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : null;

		if ( ! wp_verify_nonce( $nonce, $this->getActionSlug() ) ) {
			throw new Exception( __( 'Invalid Nonce', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}
	}

}
