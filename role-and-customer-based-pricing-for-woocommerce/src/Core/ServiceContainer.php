<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Core;

use Exception;

use MeowCrew\RoleAndCustomerBasedPricing\Settings\Settings;

class ServiceContainer {

	private $services = array();

	private static $instance;

	private function __construct() {
	}

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function add( $name, $instance ) {

		$instance = apply_filters( 'role_customer_specific_pricing/container/service_instance', $instance, $name );

		$this->services[ $name ] = $instance;
	}

	/**
	 * Get service
	 *
	 * @param $name
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get( $name ) {
		if ( ! empty( $this->services[ $name ] ) ) {
			return $this->services[ $name ];
		}

		throw new Exception( 'Undefined service' );
	}

	/**
	 * Get fileManager
	 *
	 * @return FileManager
	 */
	public function getFileManager() {
		try {
			return $this->get( 'fileManager' );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Get Settings
	 *
	 * @return Settings
	 */
	public function getSettings() {
		try {
			return $this->get( 'settings' );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Get AdminNotifier
	 *
	 * @return AdminNotifier
	 */
	public function getAdminNotifier() {
		try {
			return $this->get( 'adminNotifier' );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Get Logger
	 *
	 * @return Logger|null
	 */
	public function getLogger() {
		try {
			return $this->get( 'logger' );
		} catch ( Exception $e ) {
			return null;
		}
	}

}
