<?php namespace MeowCrew\RoleAndCustomerBasedPricing\RoleManagement\Actions;

use Exception;
use MeowCrew\RoleAndCustomerBasedPricing\RoleManagement\RoleManagement;

class DeleteRoleAction extends RoleManagementPageAction {

	public function handle() {
		$roleName = $this->getRoleName();

		if ( ! in_array( $roleName, RoleManagement::getStandardRoles() ) ) {
			remove_role( $roleName );

			$this->getContainer()->getAdminNotifier()->flash( __( 'Role deleted successfully.', 'role-and-customer-based-pricing-for-woocommerce' ) );

		} else {
			$this->getContainer()->getAdminNotifier()->flash( __( 'Standard roles cannot be deleted or modified.', 'role-and-customer-based-pricing-for-woocommerce' ),
				'error', true );
		}

		wp_redirect( wp_get_referer() );

	}

	public function validate() {

		$roles = wp_roles()->roles;

		if ( ! $this->getRoleName() || ! array_key_exists( $this->getRoleName(), $roles ) ) {
			throw new Exception( __( 'Invalid role name', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}

		parent::validate();
	}

	public function getRoleName() {
		return isset( $_REQUEST['role'] ) ? sanitize_text_field( $_REQUEST['role'] ) : false;
	}

	public function getActionSlug() {
		return 'rcbp_delete_role__action';
	}
}
