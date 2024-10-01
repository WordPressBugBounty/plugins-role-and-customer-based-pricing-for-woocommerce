<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Settings\Sections;

use MeowCrew\RoleAndCustomerBasedPricing\Core\ServiceContainerTrait;

abstract class AbstractSection {
	use ServiceContainerTrait;

	abstract public function getTitle();
	abstract public function getName();
	abstract public function getDescription();
	abstract public function getSettings();
}
