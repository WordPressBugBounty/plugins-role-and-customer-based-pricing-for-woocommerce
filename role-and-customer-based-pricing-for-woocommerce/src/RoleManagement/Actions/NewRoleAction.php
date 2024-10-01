<?php namespace MeowCrew\RoleAndCustomerBasedPricing\RoleManagement\Actions;

use Exception;

class NewRoleAction extends RoleManagementPageAction {

	public function handle() {
		$roleName    = $this->getRoleName();
		$inheritRole = $this->getInheritedRole();

		$newCapabilities = array();

		if ( $inheritRole ) {
			$roles = wp_roles()->roles;

			$role = array_key_exists( $inheritRole, $roles ) ? $roles[ $inheritRole ] : false;

			if ( ! empty( $role ) ) {
				$newCapabilities = $role['capabilities'];
			}
		}

		add_role( $roleName, $roleName, $newCapabilities );

		$this->getContainer()->getAdminNotifier()->flash( __( 'The role has been added successfully.', 'role-and-customer-based-pricing-for-woocommerce' ), 'success', true );

		wp_redirect( wp_get_referer() );
	}

	public function validate() {

		if ( ! $this->getRoleName() ) {
			throw new Exception( __( 'Role name is required.', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}

		$roles = wp_roles()->roles;

		if ( $this->getInheritedRole() && ! array_key_exists( $this->getInheritedRole(), $roles ) ) {
			throw new Exception( __( 'Invalid inherited role.', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}

		parent::validate();
	}

	public function getRoleName() {
		return isset( $_REQUEST['role_name'] ) ? sanitize_text_field( $_REQUEST['role_name'] ) : false;
	}

	public function getInheritedRole() {
		return isset( $_REQUEST['inherited_role'] ) ? sanitize_text_field( $_REQUEST['inherited_role'] ) : false;
	}

	public function getActionSlug() {
		return 'rcbp_new_role__action';
	}
}
