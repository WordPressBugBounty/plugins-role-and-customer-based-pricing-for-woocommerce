<?php namespace MeowCrew\RoleAndCustomerBasedPricing\RoleManagement;

use MeowCrew\RoleAndCustomerBasedPricing\RoleManagement\Actions\DeleteRoleAction;
use MeowCrew\RoleAndCustomerBasedPricing\RoleManagement\Actions\RoleManagementPageAction;
use WP_List_Table;

class RolesTable extends WP_List_Table {

	/**
	 * Delete action
	 *
	 * @var DeleteRoleAction|mixed
	 */
	private $deleteAction;

	public function __construct( $args = array(), RoleManagementPageAction $deleteAction ) {
		parent::__construct( $args );
		$this->deleteAction = $deleteAction;
	}

	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns() );
		$this->items           = $this->table_data();
	}

	public function get_columns() {
		return array(
			'name'         => __( 'Name', 'role-and-customer-based-pricing-for-woocommerce' ),
			'capabilities' => __( 'Capabilities', 'role-and-customer-based-pricing-for-woocommerce' ),
		);
	}

	private function table_data() {
		$roles = array();

		foreach ( wp_roles()->roles as $roleSlug => $roleData ) {
			$roles[] = array_merge( array( 'slug' => $roleSlug ), $roleData );
		}

		return $roles;
	}

	protected function handle_row_actions( $role, $column_name, $primary ) {

		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();

		if ( ! $this->isDefaultRole( $role['slug'] ) ) {
			$actions[ $this->deleteAction->getActionSlug() ] = sprintf( '<a href="%s" style="color: #b32d2e;">%s</a>', $this->deleteAction->getURL( $role['slug'] ), __( 'Delete', 'role-and-customer-based-pricing-for-woocommerce' ) );
		} else {
			$actions['cannot_modify_role'] = sprintf( '<span>%s</span>', __( 'You cannot delete standard roles', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}

		return $this->row_actions( $actions );
	}

	public function isDefaultRole( $roleSlug ) {
		return in_array( $roleSlug, RoleManagement::getStandardRoles() );
	}

	public function column_default( $item, $column_name ) {

	}

	public function column_name( $role ) {
		echo esc_attr( $role['name'] );
	}

	public function column_capabilities( $role ) {
		$capabilities         = array_filter( $role['capabilities'] );
		$capabilitiesCount    = count( $capabilities );
		$tenFirstCapabilities = array_keys( array_slice( $capabilities, 0, 10 ) );
		$lastCapabilities     = array_keys( array_slice( $capabilities, 10 ) );

		// translators: %s: count of capabilities
		$ending = $capabilitiesCount > 10 ? sprintf( '<b style="cursor:help" title="%s">' . __( 'and %d more...', 'role-and-customer-based-pricing-for-woocommerce' ) . '</b>', implode( ',', $lastCapabilities ), $capabilitiesCount - 10 ) : '';

		echo wp_kses_post( implode( ', ', $tenFirstCapabilities ) . ' ' . $ending );

	}
}
