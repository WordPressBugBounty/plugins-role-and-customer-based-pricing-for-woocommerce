<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Core;

class Logger {

	use ServiceContainerTrait;

	const ERROR__LEVEL = 'role_and_customer_based_pricing__errors';
	const NOTICE__LEVEL = 'role_and_customer_based_pricing__notices';
	const TRACKING__LEVEL = 'role_and_customer_based_pricing__tracking';

	public function log( $message, $level = self::NOTICE__LEVEL ) {
		if ( $this->getContainer()->getSettings()->isDebugEnabled() ) {
			wc_get_logger()->log( $level, $message );
		}
	}
}
