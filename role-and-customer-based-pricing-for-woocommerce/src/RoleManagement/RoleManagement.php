<?php namespace MeowCrew\RoleAndCustomerBasedPricing\RoleManagement;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;

class RoleManagement {

	use ServiceContainerTrait;

	public function __construct() {
		new RoleManagementPage();
	}

	public static function getStandardRoles() {
		return apply_filters( 'role_customer_specific_pricing/role_management/default_roles', array(
			'administrator',
			'editor',
			'author',
			'contributor',
			'subscriber',
			'customer',
			'shop_manager',
		) );
	}
}
