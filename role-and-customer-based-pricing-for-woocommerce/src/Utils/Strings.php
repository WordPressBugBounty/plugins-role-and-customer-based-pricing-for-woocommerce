<?php namespace MeowCrew\RoleAndCustomerBasedPricing\Utils;

class Strings {

	public static function startsWith( $haystack, $needle ) {
		$length = strlen( $needle );

		return substr( $haystack, 0, $length ) === $needle;
	}

	public static function endsWith( $haystack, $needle ) {
		$length = strlen( $needle );
		if ( ! $length ) {
			return true;
		}

		return substr( $haystack, - $length ) === $needle;
	}

	public static function IsNullOrEmpty( $str ) {
		return ( $str === null || trim( $str ) === '' );
	}
}
