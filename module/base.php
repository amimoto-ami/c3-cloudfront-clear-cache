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
	private static $text_domain;
	private static $version;

	//Panel key
	const MENU_ID = 'c3-admin-menu';
	const OPTION_NAME = 'c3_settings';

	// Action key
	const C3_AUTHENTICATION = 'c3_auth';
	const C3_INVALIDATION = 'c3_invalidation';
	const C3_INVALIDATION_KEY = "c3_invalidation_key";
	const C3_CRON_INDALITATION_TARGET = "c3_cron_invalidation_target";
	/**
	 * Get Plugin version
	 *
	 * @return string
	 * @since 4.0.0
	 */
	public static function version() {
		static $version;

		if ( ! $version ) {
			$data = get_file_data( C3_PLUGIN_ROOT , array( 'version' => 'Version' ) );
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
			$data = get_file_data( C3_PLUGIN_ROOT , array( 'text_domain' => 'Text Domain' ) );
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

	/**
	 * Get C3 option data
	 *
	 * @return array ( from wp_options )
	 * @since 4.0.0
	 * @access public
	 */
	public function get_c3_options() {
		$c3_settings = get_option( self::OPTION_NAME );
		if ( ! $c3_settings ) {
			$c3_settings = array(
				'distribution_id' => '',
				'access_key'      => '',
				'secret_key'      => '',
			);
		}
		return apply_filters( 'c3_setting', $c3_settings );
	}

	/**
	 * Get C3 option data
	 *
	 * @return array ( from wp_options )
	 * @since 4.0.0
	 * @access public
	 */
	public function get_c3_options_name() {
		$c3_settings_keys = array(
			'distribution_id' => __( 'CloudFront Distribution ID', self::$text_domain ),
			'access_key'      => __( 'AWS Access Key', self::$text_domain ),
			'secret_key'      => __( 'AWS Secret Key', self::$text_domain ),
		);
		return apply_filters( 'c3_setting_keys', $c3_settings_keys );
	}

}
