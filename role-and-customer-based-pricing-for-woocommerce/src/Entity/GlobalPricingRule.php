<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Entity;

use Exception;
use WC_Product;
use WP_User;

class GlobalPricingRule extends PricingRule {

	/**
	 * Included categories
	 *
	 * @var array
	 */
	public $includedProductCategories = array();

	/**
	 * Included products
	 *
	 * @var array
	 */
	public $includedProducts = array();

	/**
	 * Included product roles
	 *
	 * @var array
	 */
	public $includedUsersRole = array();

	/**
	 * Included users
	 *
	 * @var array
	 */
	public $includedUsers = array();

	/**
	 * Is suspended
	 *
	 * @var bool
	 */
	private $isSuspended;

	/**
	 * Related product id
	 *
	 * @var int
	 */
	private $appliedProductId;

	public function getRuleId() {
		return parent::getProductId();
	}

	public function setAppliedProductId( $productId ) {
		$this->appliedProductId = $productId;
	}

	public function getProductId() {
		return $this->appliedProductId;
	}

	/**
	 * Get included categories
	 *
	 * @return array
	 */
	public function getIncludedProductCategories() {
		return $this->includedProductCategories;
	}

	/**
	 * Set included categories
	 *
	 * @param array $includedProductCategories
	 */
	public function setIncludedProductCategories( array $includedProductCategories ) {
		$this->includedProductCategories = $includedProductCategories;
	}

	/**
	 * Get included products
	 *
	 * @return array
	 */
	public function getIncludedProducts() {
		return $this->includedProducts;
	}

	/**
	 * Set included products
	 *
	 * @param array $includedProducts
	 */
	public function setIncludedProducts( array $includedProducts ) {
		$this->includedProducts = $includedProducts;
	}

	/**
	 * Get included user roles
	 *
	 * @return array
	 */
	public function getIncludedUserRoles() {
		return $this->includedUsersRole;
	}

	/**
	 * Set included user roles
	 *
	 * @param array $includedUsersRole
	 */
	public function setIncludedUsersRole( array $includedUsersRole ) {
		$this->includedUsersRole = $includedUsersRole;
	}

	/**
	 * Get included users
	 *
	 * @return array
	 */
	public function getIncludedUsers() {
		return $this->includedUsers;
	}

	/**
	 * Set included users
	 *
	 * @param array $includedUsers
	 */
	public function setIncludedUsers( array $includedUsers ) {
		$this->includedUsers = $includedUsers;
	}

	public function asArray() {
		return array_merge( parent::asArray(), array(
			'included_categories' => $this->getIncludedProductCategories(),
			'included_products'   => $this->getIncludedProducts(),
			'included_users'      => $this->getIncludedUsers(),
			'included_users_role' => $this->getIncludedUserRoles(),
			'rule_id'             => $this->getRuleId(),
			'is_suspended'        => $this->isSuspended()
		) );
	}

	/**
	 * Save global price instance
	 *
	 * @param GlobalPricingRule $rule
	 * @param $ruleId
	 *
	 * @throws Exception
	 */
	public static function save( GlobalPricingRule $rule, $ruleId ) {

		$dataToUpdate = array(
			'_rcbp_pricing_type'  => $rule->getPriceType(),
			'_rcbp_regular_price' => $rule->getRegularPrice(),
			'_rcbp_sale_price'    => $rule->getSalePrice(),
			'_rcbp_discount'      => $rule->getDiscount(),
			'_rcbp_minimum'       => $rule->getMinimum(),
			'_rcbp_maximum'       => $rule->getMaximum(),
			'_rcbp_group_of'      => $rule->getGroupOf(),

			'_rps_included_categories' => $rule->getIncludedProductCategories(),
			'_rps_included_products'   => $rule->getIncludedProducts(),
			'_rps_included_users'      => $rule->getIncludedUsers(),
			'_rps_included_user_roles' => $rule->getIncludedUserRoles(),
			'_rps_is_suspended'        => $rule->isSuspended() ? 'yes' : 'no',
		);

		foreach ( $dataToUpdate as $key => $value ) {
			update_post_meta( $ruleId, $key, $value );
		}
	}

	public static function build( $ruleId ) {
		$dataToRead = array(
			'_rcbp_pricing_type'  => 'pricing_type',
			'_rcbp_regular_price' => 'regular_price',
			'_rcbp_sale_price'    => 'sale_price',
			'_rcbp_discount'      => 'discount',
			'_rcbp_minimum'       => 'minimum',
			'_rcbp_maximum'       => 'maximum',
			'_rcbp_group_of'      => 'group_of',
			'_rps_is_suspended'   => 'is_suspended',
		);

		$data = array();

		foreach ( $dataToRead as $key => $name ) {
			$data[ $name ] = get_post_meta( $ruleId, $key, true );
		}

		$priceRule = self::fromArray( $data );

		$existingRoles = wp_roles()->roles;

		$includedCategoriesIds = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_rps_included_categories', true ) ) );
		$includedProductsIds   = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_rps_included_products', true ) ) );

		$includedUsersRole = array_filter( (array) get_post_meta( $ruleId, '_rps_included_user_roles', true ), function ( $role ) use ( $existingRoles ) {
			return array_key_exists( $role, $existingRoles );
		} );

		$includedUsers = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_rps_included_users', true ) ) );

		$isSuspended = get_post_meta( $ruleId, '_rps_is_suspended', true ) === 'yes';

		$priceRule->setIncludedProductCategories( $includedCategoriesIds );
		$priceRule->setIncludedUsers( $includedUsers );
		$priceRule->setIncludedUsersRole( $includedUsersRole );
		$priceRule->setIncludedProducts( $includedProductsIds );
		$priceRule->setIsSuspended( $isSuspended );

		return $priceRule;
	}

	/**
	 * Set price type
	 *
	 * @override
	 *
	 * @param string $priceType
	 */
	public function setPriceType( $priceType ) {
		parent::setPriceType( in_array( $priceType, array( 'percentage', 'flat' ) ) ? $priceType : 'percentage' );
	}

	public function setIsSuspended( $isSuspended ) {
		$this->isSuspended = (bool) $isSuspended;
	}

	public function suspend() {
		$this->setIsSuspended( true );
	}

	public function reactivate() {
		$this->setIsSuspended( false );
	}

	public function isSuspended() {
		return $this->isSuspended;
	}

	/**
	 * Wrapper for the main "match" function to provide the hook for 3rd party devs
	 *
	 * @param WP_User $user
	 * @param WC_Product $product
	 *
	 * @return mixed|void
	 */
	public function matchRequirements( WP_User $user, WC_Product $product ) {
		$matched = $this->_matchRequirements( $user, $product );

		return apply_filters( 'role_customer_specific_pricing/pricing_rule/match_requirements', $matched, $user, $product );
	}

	protected function _matchRequirements( WP_User $user, WC_Product $product ) {

		$parentProduct = $product->is_type( array(
			'variation',
			'subscription-variation'
		) ) ? wc_get_product( $product->get_parent_id() ) : $product;

		$productMatched     = false;
		$productLimitations = false;

		// There are rule limitation for specific products
		if ( ! empty( $this->getIncludedProducts() ) ) {
			$productLimitations = true;

			if ( in_array( $product->get_id(), $this->getIncludedProducts() ) || in_array( $parentProduct->get_id(), $this->getIncludedProducts() ) ) {
				$productMatched = true;
			}
		}

		if ( ! empty( $this->getIncludedProductCategories() ) ) {
			$productLimitations = true;

			if ( ! empty( array_intersect( $parentProduct->get_category_ids(), $this->getIncludedProductCategories() ) ) ) {
				$productMatched = true;
			}
		}

		// There is product limitation and the product/category does not match the rule
		if ( $productLimitations && ! $productMatched ) {
			return false;
		}

		// Applied to everyone
		if ( empty( $this->getIncludedUserRoles() ) && empty( $this->getIncludedUsers() ) ) {
			return true;
		}

		if ( in_array( $user->ID, $this->getIncludedUsers() ) ) {
			return true;
		}

		foreach ( $this->getIncludedUserRoles() as $role ) {
			if ( in_array( $role, $user->roles ) ) {
				return true;
			}
		}

		return false;
	}

}
