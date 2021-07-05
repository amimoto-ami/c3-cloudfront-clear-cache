<?php
/**
 * Environment
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Environment class
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Environment {

	/**
	 * Check is AMIMOTO Managed mode
	 *
	 * @return bool
	 * @since 6.0.0
	 */
	public function is_amimoto_managed() {
		if ( isset( $_SERVER['HTTP_X_AMIMOTO_MANAGED'] ) && $_SERVER['HTTP_X_AMIMOTO_MANAGED'] ) {
			return true;
		}
		return false;
	}

	/**
	 * Detect AMIMOTO server
	 *
	 * @return bool
	 */
	public function is_amimoto() {
		return defined( 'IS_AMIMOTO' );
	}

	/**
	 *  Has managed cdn dist id
	 *
	 * @return bool
	 * @since 6.0.0
	 */
	public function has_managed_cdn() {
		return defined( 'AMIMOTO_CDN_ID' );
	}

	/**
	 *  Check is WP-CLI
	 *
	 * @return bool
	 * @since 6.0.0
	 */
	public function is_wp_cli() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the distribution id if defined
	 *
	 * @return string|null
	 */
	public function get_distribution_id() {
		if ( $this->has_managed_cdn() ) {
			return AMIMOTO_CDN_ID;
		}
		if ( defined( 'C3_DISTRIBUTION_ID' ) ) {
			return C3_DISTRIBUTION_ID;
		}
		return null;
	}

	/**
	 * Get the access_key field
	 *
	 * @return string
	 * @since 6.0.0
	 * @access public
	 */
	public function get_aws_access_key() {
		if ( defined( 'AWS_ACCESS_KEY_ID' ) ) {
			return AWS_ACCESS_KEY_ID;
		}
		return null;
	}

	/**
	 * Get the secret_key field
	 *
	 * @return string
	 * @since 6.0.0
	 * @access public
	 */
	public function get_aws_secret_key() {
		if ( defined( 'AWS_SECRET_ACCESS_KEY' ) ) {
			return AWS_SECRET_ACCESS_KEY;
		}
		return null;
	}

	/**
	 * Compare php version
	 *
	 * @param string $supported_version PHP version that will support at least.
	 * @param string $version Current php version.
	 */
	public function is_supported_version( string $supported_version, $version = null ) {
		if ( ! isset( $version ) ) {
			$version = phpversion();
		}
		return version_compare( $supported_version, $version, '<=' );
	}

}
