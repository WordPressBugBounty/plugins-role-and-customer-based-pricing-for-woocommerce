<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Core;

trait ServiceContainerTrait {

	public function getContainer() {
		return ServiceContainer::getInstance();
	}

}
