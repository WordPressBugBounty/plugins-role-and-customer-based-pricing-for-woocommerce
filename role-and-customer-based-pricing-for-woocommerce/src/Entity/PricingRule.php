<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Entity;

use Exception;
use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;
use MeowCrew\RoleAndCustomerBasedPricing\Utils\Strings;
use WC_Product;

class PricingRule {
	
	use ServiceContainerTrait;
	
	/**
	 * Product id
	 *
	 * @var int
	 */
	private $productId;
	
	/**
	 * Pricing Type
	 *
	 * @var string
	 */
	private $priceType;
	
	/**
	 * Regular price
	 *
	 * @var float
	 */
	private $regularPrice;
	
	/**
	 * Sale price
	 *
	 * @var float
	 */
	private $salePrice;
	
	/**
	 * Discount
	 *
	 * @var float
	 */
	private $discount;
	
	/**
	 * Product minimum purchase quantity
	 *
	 * @var int
	 */
	private $minimum;
	
	/**
	 * Product maximum purchase quantity
	 *
	 * @var int|null
	 */
	private $maximum;
	
	/**
	 * Group of product quantity
	 *
	 * @var int|null
	 */
	private $groupOf;
	
	/**
	 * Original product price
	 *
	 * @var float
	 */
	private $originalProductPrice;
	
	/**
	 * PricingRule constructor.
	 *
	 * @param  string  $priceType
	 * @param  string  $regularPrice
	 * @param  string  $salePrice
	 * @param  int  $discount
	 * @param  int  $minimum
	 * @param  int  $maximum
	 * @param  int  $groupOf
	 * @param  null  $productId
	 */
	public function __construct(
		$priceType,
		$regularPrice = null,
		$salePrice = null,
		$discount = null,
		$minimum = null,
		$maximum = null,
		$groupOf = null,
		$productId = null
	) {
		$this->setPriceType( $priceType );
		$this->setRegularPrice( $regularPrice );
		$this->setSalePrice( $salePrice );
		$this->setDiscount( $discount );
		
		$this->setMinimum( $minimum );
		$this->setMaximum( $maximum );
		$this->setGroupOf( $groupOf );
		$this->setProductId( $productId );
	}
	
	public function isPurchasable() {
		return $this->getPrice();
	}
	
	/**
	 * Set original product price
	 *
	 * @param $price
	 */
	public function setOriginalProductPrice( $price ) {
		$this->originalProductPrice = $price;
	}
	
	public function getOriginalProductPrice() {
		
		if ( is_null( $this->originalProductPrice ) ) {
			
			if ( $this->getContainer()->getSettings()->useRegularPriceToCalculateDiscounts() ) {
				$this->originalProductPrice = (float) $this->getProduct()->get_regular_price( 'edit' );
			} else {
				$this->originalProductPrice = (float) $this->getProduct()->get_price( 'edit' );
			}
		}
		
		return $this->originalProductPrice;
	}
	
	public function getPrice( WC_Product $product = null ) {
		
		if ( $this->getPriceType() === 'percentage' ) {
			
			$discount = $this->getDiscount();
			
			$productPrice = $this->getOriginalProductPrice();
			
			$price = ( $productPrice * ( ( 100 - $discount ) / 100 ) );
			
		} else {
			$price = $this->getRegularPrice();
			
			if ( ! Strings::IsNullOrEmpty( $this->getSalePrice() ) ) {
				$price = $this->getSalePrice();
			}
		}
		
		return apply_filters( 'role_customer_specific_pricing/pricing_rule/price', $price, $this );
	}
	
	/**
	 * Get discount
	 *
	 * @return int
	 */
	public function getDiscount() {
		return $this->discount;
	}
	
	/**
	 * Set discount
	 *
	 * @param  int  $discount
	 */
	public function setDiscount( $discount ) {
		$this->discount = $discount ? min( 100, floatval( $discount ) ) : null;
	}
	
	/**
	 * Get product id
	 *
	 * @return int
	 */
	public function getProductId() {
		return $this->productId;
	}
	
	/**
	 * Set product id
	 *
	 * @param  int  $productId
	 */
	public function setProductId( $productId ) {
		$this->productId = $productId ? intval( $productId ) : null;
	}
	
	/**
	 * Get price type
	 *
	 * @return string
	 */
	public function getPriceType() {
		return $this->priceType;
	}
	
	/**
	 * Set price type
	 *
	 * @param  string  $priceType
	 */
	public function setPriceType( $priceType ) {
		
		if ( ! racbpfw_fs()->can_use_premium_code() ) {
			$priceType = 'flat';
		}
		
		$this->priceType = in_array( $priceType, array( 'percentage', 'flat' ) ) ? $priceType : 'flat';
	}
	
	/**
	 * Get regular price
	 *
	 * @return string
	 */
	public function getRegularPrice() {
		return $this->regularPrice;
	}
	
	/**
	 * Set regular price
	 *
	 * @param  string  $regularPrice
	 */
	public function setRegularPrice( $regularPrice ) {
		$this->regularPrice = ! Strings::IsNullOrEmpty( $regularPrice ) ? floatval( $regularPrice ) : null;
	}
	
	/**
	 * Get sale price
	 *
	 * @return string
	 */
	public function getSalePrice() {
		return $this->salePrice;
	}
	
	/**
	 * Set sale price
	 *
	 * @param  string  $salePrice
	 */
	public function setSalePrice( $salePrice ) {
		$this->salePrice = ! Strings::IsNullOrEmpty( $salePrice ) ? floatval( $salePrice ) : null;
	}
	
	public function asArray() {
		return array(
			'pricing_type'  => $this->getPriceType(),
			'regular_price' => $this->getRegularPrice(),
			'sale_price'    => $this->getSalePrice(),
			'discount'      => $this->getDiscount(),
			'minimum'       => $this->getMinimum(),
			'maximum'       => $this->getMaximum(),
			'group_of'      => $this->getGroupOf(),
			'product_id'    => $this->getProductId(),
		);
	}
	
	/**
	 * Create instance from array
	 *
	 * @param  array  $data
	 * @param  null|int  $productId
	 *
	 * @return static
	 */
	public static function fromArray( $data, $productId = null ) {
		
		$pricingType = isset( $data['pricing_type'] ) ? $data['pricing_type'] : 'flat';
		$pricingType = in_array( $pricingType, array( 'flat', 'percentage' ) ) ? $pricingType : 'flat';
		
		$regularPrice = isset( $data['regular_price'] ) ? $data['regular_price'] : null;
		$salePrice    = isset( $data['sale_price'] ) ? (string) $data['sale_price'] : false;
		$discount     = isset( $data['discount'] ) ? $data['discount'] : null;
		
		$minimum = isset( $data['minimum'] ) ? $data['minimum'] : null;
		$maximum = isset( $data['maximum'] ) ? $data['maximum'] : null;
		$groupOf = isset( $data['group_of'] ) ? $data['group_of'] : null;
		
		$productId = $productId ? $productId : ( isset( $data['product_id'] ) ? (int) $data['product_id'] : null );
		
		return new static( $pricingType, $regularPrice, $salePrice, $discount, $minimum, $maximum, $groupOf,
			$productId );
	}
	
	public function calculateDiscount() {
		
		if ( $this->getPriceType() === 'percentage' ) {
			return $this->getDiscount();
		}
		
		$product = $this->getProduct();
		
		if ( $product ) {
			$price        = $this->getPrice();
			$productPrice = $product->get_price();
			
			if ( $productPrice > $price ) {
				return 100 - ( $price / $productPrice * 100 );
			}
		}
		
		return 0;
	}
	
	/**
	 * Validate
	 *
	 * @throws Exception
	 */
	public function validatePricing() {
		
		if ( $this->getPriceType() === 'flat' && $this->getPrice() === null ) {
			throw new Exception( __( 'The pricing fields must be filled.',
				'role-and-customer-based-pricing-for-woocommerce' ) );
		}
		
		if ( $this->getPriceType() === 'percentage' && ! $this->getDiscount() ) {
			throw new Exception( __( 'The discount is not set.', 'role-and-customer-based-pricing-for-woocommerce' ) );
		}
		
	}
	
	public function isValidPricing() {
		try {
			$this->validatePricing();
		} catch ( Exception $e ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Get minimum
	 *
	 * @return int
	 */
	public function getMinimum() {
		return $this->minimum;
	}
	
	/**
	 * Set minimum
	 *
	 * @param  int  $minimum
	 */
	public function setMinimum( $minimum ) {
		$this->minimum = $minimum ? intval( $minimum ) : null;
	}
	
	/**
	 * Get maximum
	 *
	 * @return int|null
	 */
	public function getMaximum() {
		return $this->maximum;
	}
	
	/**
	 * Set maximum
	 *
	 * @param  int|null  $maximum
	 */
	public function setMaximum( $maximum ) {
		$this->maximum = $maximum ? intval( $maximum ) : null;
	}
	
	/**
	 * Get group of
	 *
	 * @return int|null
	 */
	public function getGroupOf() {
		return $this->groupOf;
	}
	
	/**
	 * Set group of
	 *
	 * @param  int|null  $groupOf
	 */
	public function setGroupOf( $groupOf ) {
		$this->groupOf = $groupOf ? intval( $groupOf ) : null;
	}
	
	/**
	 * Get product
	 *
	 * @return false|WC_Product
	 */
	public function getProduct() {
		return wc_get_product( $this->getProductId() );
	}
}
