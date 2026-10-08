<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Core;

class Logger {

	use ServiceContainerTrait;

	const ERROR__LEVEL = 'error';
	const NOTICE__LEVEL = 'notice';
	const TRACKING__LEVEL = 'info';

	public function log( $message, $level = self::NOTICE__LEVEL ) {
		if ( $this->getContainer()->getSettings()->isDebugEnabled() ) {
			wc_get_logger()->log( $level, $message, array( 'source' => 'role-and-customer-based-pricing' ) );
		}
	}
}
