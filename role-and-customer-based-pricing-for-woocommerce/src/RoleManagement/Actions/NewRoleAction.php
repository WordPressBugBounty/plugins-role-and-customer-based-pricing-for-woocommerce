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

		add_role( $this->getRoleSlug(), $roleName, $newCapabilities );

		$this->getContainer()->getAdminNotifier()->flash( esc_html__( 'The role has been added successfully.', 'role-and-customer-based-pricing-for-woocommerce' ), 'success', true );

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	public function validate() {

		if ( ! $this->getRoleName() || ! $this->getRoleSlug() ) {
			throw new Exception( esc_html__( 'Role name is required.', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}

		$roles = wp_roles()->roles;

		if ( array_key_exists( $this->getRoleSlug(), $roles ) ) {
			throw new Exception( esc_html__( 'A role with this name already exists.', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}

		if ( $this->getInheritedRole() && ! array_key_exists( $this->getInheritedRole(), $roles ) ) {
			throw new Exception( esc_html__( 'Invalid inherited role.', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}

		parent::validate();
	}

	public function getRoleName() {
		return isset( $_REQUEST['role_name'] ) ? sanitize_text_field( $_REQUEST['role_name'] ) : false;
	}

	/**
	 * Role key: lowercase, underscores instead of spaces, no other special characters.
	 * Falls back to a URL-safe title for names without Latin characters.
	 *
	 * @return string
	 */
	public function getRoleSlug() {
		$roleName = $this->getRoleName();

		if ( ! $roleName ) {
			return '';
		}

		$slug = sanitize_key( str_replace( ' ', '_', $roleName ) );

		return $slug ? $slug : sanitize_title( $roleName );
	}

	public function getInheritedRole() {
		return isset( $_REQUEST['inherited_role'] ) ? sanitize_text_field( $_REQUEST['inherited_role'] ) : false;
	}

	public function getActionSlug() {
		return 'rcbp_new_role__action';
	}
}
