<?php namespace MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\Tabs;

use MeowCrew\RoleAndCustomerBasedPricing\GlobalRoleSpecificPricing\CPT\Form\FormTab;
use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;

class UsersAndRoles extends FormTab {

	public function getId(): string {
		return 'user-and-roles';
	}

	public function getTitle(): string {
		return __( 'Users & Roles', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function getDescription(): string {
		return __( 'Select users or user roles that the rule will apply to.', 'role-and-customer-based-pricing-for-woocommerce' );
	}

	public function render( GlobalPricingRule $pricingRule ) {

		$this->renderSectionTitle( __( 'Included Users', 'role-and-customer-based-pricing-for-woocommerce' ), array(
				'description' => __( 'Choose the user role and/or customers\' accounts to apply the pricing rule.', 'role-and-customer-based-pricing-for-woocommerce' ),
		) );

		if ( empty( $pricingRule->getIncludedUserRoles() ) && empty( $pricingRule->getIncludedUsers() ) ) {
			$this->renderHint( __( 'The rule will apply to all users if you do not specify user roles or specific customers.', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}

		$this->renderSelect2( array(
				'id'            => '_rps_included_user_roles',
				'label'         => __( 'Include user roles', 'role-and-customer-based-pricing-for-woocommerce' ),
				'options'       => ( function () {
					return array_map( function ( $WPRole ) {
						return $WPRole['name'];
					}, wp_roles()->roles );
				} )(),
				'value'         => $pricingRule->getIncludedUserRoles(),
				'placeholder'   => __( 'Select for a customer role&hellip;', 'role-and-customer-based-pricing-for-woocommerce' ),
				'search_action' => '',
				'css_class'     => 'wc-enhanced-select',
				'description'   => __( 'Choose to what user roles this rule will be relevant. Applies to all users with those roles.', 'role-and-customer-based-pricing-for-woocommerce' )
		) );

		$this->renderSelect2( array(
				'id'            => '_rps_included_users',
				'label'         => __( 'Include specific customers', 'role-and-customer-based-pricing-for-woocommerce' ),
				'options'       => ( function () use ( $pricingRule ) {
					$users = [];
					foreach ( $pricingRule->getIncludedUsers() as $userId ) {
						$user = get_user_by( 'id', $userId );

						if ( $user ) {
							$users[ $userId ] = $user->first_name . ' ' . $user->last_name . ' (' . $user->user_email . ')';
						}
					}

					return $users;
				} )(),
				'value'         => $pricingRule->getIncludedUsers(),
				'placeholder'   => __( 'Select for a customer&hellip;', 'role-and-customer-based-pricing-for-woocommerce' ),
				'search_action' => 'woocommerce_json_search_rcbp_customers',
				'css_class'     => 'rbp-select-woo wc-product-search',
				'description'   => __( 'Pick up separate user accounts, which will be affected by this rule.', 'role-and-customer-based-pricing-for-woocommerce' )
		) );
	}

	public function getIcon(): string {
		return 'dashicons-admin-users';
	}
}
