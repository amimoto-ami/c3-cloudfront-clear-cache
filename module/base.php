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
	 * Check is AMIMOTO Managed mode
	 *
	 * @return bool
	 * @since 4.4.0
	 */
	public static function is_amimoto_managed() {
		if ( isset( $_SERVER['HTTP_X_AMIMOTO_MANAGED'] ) && $_SERVER['HTTP_X_AMIMOTO_MANAGED'] ) {
			return true;
		}
		return false;
	}

	/**
	 *  Has managed cdn dist id
	 *
	 * @return bool
	 * @since 5.2.1
	 */
	public static function has_managed_cdn() {
		return defined( 'AMIMOTO_CDN_ID' );
	}

	/**
	 *  Check is WP-CLI
	 *
	 * @return bool
	 * @since 5.2.1
	 */
	public static function is_wp_cli() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		return false;
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
	public static function get_c3_options() {

		$distribution_id = self::get_distribution_id();
		$access_key = self::get_access_key();
		$secret_key = self::get_secret_key();
		$c3_settings = array(
			'distribution_id' => $distribution_id,
			'access_key'      => $access_key,
			'secret_key'      => $secret_key,
		);

		return apply_filters( 'c3_setting', $c3_settings );
	}

	/**
	 * Get the key of the settings field
	 * @return string
	 * @since 4.5.0
	 * @access public
	 */
	public static function get_c3_option($key) {
		$options = get_option( self::OPTION_NAME );
		return $options[$key] ?: '';
	}

	/**
	 * Get the distribution_id field
	 * @return string
	 * @since 4.5.0
	 * @access public
	 */
	public static function get_distribution_id() {
		if(defined('C3_DISTRIBUTION_ID')) {
			return C3_DISTRIBUTION_ID;
		} else {
			$option = self::get_c3_option('distribution_id');
			return $option;
		}
	}

	/**
	 * Get the access_key field
	 * @return string
	 * @since 4.5.0
	 * @access public
	 */
	public static function get_access_key() {
		if(defined('AWS_ACCESS_KEY_ID')) {
			return AWS_ACCESS_KEY_ID;
		} else {
			$option = self::get_c3_option('access_key');
			return $option;
		}
	}

	/**
	 * Get the secret_key field
	 * @return string
	 * @since 4.5.0
	 * @access public
	 */
	public static function get_secret_key() {
		if(defined('AWS_SECRET_ACCESS_KEY')) {
			return AWS_SECRET_ACCESS_KEY;
		} else {
			$option = self::get_c3_option('secret_key');
			return $option;
		}
	}

	/**
	 * Returns true or false if all settings are from env vars
	 * @return bool
	 * @since 4.5.0
	 * @access public
	 */
	public static function are_key_constants_set() {
		return defined( 'C3_DISTRIBUTION_ID' ) && defined( 'AWS_ACCESS_KEY_ID' ) && defined( 'AWS_SECRET_ACCESS_KEY' );
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
