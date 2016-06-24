<?php
/**
 * C3_Base Class file
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package c3-cloudfront-clear-cache
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Define C3 plugin's basic function and parameters
 *
 * @class C3_Base
 * @since 4.0.0
 */
class C3_Base {
	private static $instance;
	private static $text_domain;
	private static $version;

	//Panel key

	// Action key

	private function __construct() {
	}

	/**
	 * Get Instance Class
	 *
	 * @return C3_Base
	 * @since 4.0.0
	 * @access public
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	/**
	 * Get Plugin version
	 *
	 * @return string
	 * @since 4.0.0
	 */
	public static function version() {
		static $version;

		if ( ! $version ) {
			$data = get_file_data( AMI_DASH_ROOT , array( 'version' => 'Version' ) );
			$version = $data['version'];
		}
		return $version;
	}

	/**
	 * Get Plugin text_domain
	 *
	 * @return string
	 * @since 4.0.0
	 */
	public static function text_domain() {
		static $text_domain;

		if ( ! $text_domain ) {
			$data = get_file_data( AMI_DASH_ROOT , array( 'text_domain' => 'Text Domain' ) );
			$text_domain = $data['text_domain'];
		}
		return $text_domain;
	}

	/**
	 * Check is multisite
	 *
	 * @return boolean
	 * @since 4.0.0
	 * @access public
	 */
	public function is_multisite() {
		return function_exists('is_multisite') && is_multisite();
	}

}
