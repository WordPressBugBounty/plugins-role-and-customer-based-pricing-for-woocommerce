<?php namespace MeowCrew\RoleAndCustomerBasedPricing\RoleManagement;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\RoleManagement\Actions\DeleteRoleAction;
use MeowCrew\RoleAndCustomerBasedPricing\RoleManagement\Actions\NewRoleAction;

class RoleManagementPage {

	use ServiceContainerTrait;

	const PAGE_SLUG = 'rcbp_role_management';

	/**
	 * DeleteRoleAction
	 *
	 * @var DeleteRoleAction
	 */
	private $deleteAction;

	/**
	 * NewRoleAction
	 *
	 * @var NewRoleAction
	 */
	private $newRoleAction;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'registerPage' ) );
		$this->deleteAction  = new DeleteRoleAction();
		$this->newRoleAction = new NewRoleAction();
	}

	public function registerPage() {
		add_submenu_page(
			'users.php',
			__( 'Roles Management', 'role-and-customer-based-pricing-for-woocommerce' ),
			__( 'Roles Management', 'role-and-customer-based-pricing-for-woocommerce' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'renderPage' )
		);
	}

	public function renderPage() {
		global $wp_roles;

		$rolesTable = new RolesTable( array(), $this->deleteAction );

		$rolesTable->prepare_items();

		$this->getContainer()->getFileManager()->includeTemplate( 'admin/role-management/list.php', array(
			'roles'           => $wp_roles->roles,
			'roles_table'     => $rolesTable,
			'new_role_action' => $this->newRoleAction
		) );
	}

}
