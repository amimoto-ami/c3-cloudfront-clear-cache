<?php
/**
 * C3_Auth
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-plugin-dashboard
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Authenication
 *
 * @class C3_Auth
 * @since 4.0.0
 */
class C3_Auth extends C3_Base {
	private static $instance;
	private static $text_domain;

	private function __construct() {
		self::$text_domain = C3_Base::text_domain();
	}

	/**
	 * Get Instance Class
	 *
	 * @return C3_Auth
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
	 * Authenication for Amazon CloudFront
	 *
	 * @return boolean | WP_Error
	 * @since 4.0.0
	 * @access public
	 */
	public function auth( $options ) {
		if ( ! isset( $options['distribution_id'] ) || ! $options['distribution_id'] ) {
			return new WP_Error( 'C3 Notice', "CloudFront Distribution ID is not defined." );
		}
		if ( c3_is_later_than_php_55() ) {
			$sdk = C3_Client_V3::get_instance();
		} else {
			$sdk = C3_Client_V2::get_instance();
			//@TODO: for php ~5.4, do not Authenication now.
			return true;
		}
		$cf_client = $sdk->create_cloudfront_client( $options );
		if ( is_wp_error( $cf_client ) ) {
			return $cf_client;
		}

		try {
			$result = $cf_client->getDistribution( array(
				'Id' => $options['distribution_id'],
			));
			return true;
		} catch ( Exception $e ) {
			if ( 'NoSuchDistribution' === $e->getAwsErrorCode() ) {
				$e = new WP_Error( 'C3 Auth Error', "Can not find CloudFront Distribution ID: {$options['distribution_id']} is not found." );
			} elseif ( 'InvalidClientTokenId' == $e->getAwsErrorCode() ) {
				$e = new WP_Error( 'C3 Auth Error', "AWS AWS Access Key or AWS Secret Key is invalid." );
			} else {
				$e = new WP_Error( 'C3 Auth Error', $e->getMessage() );
			}
			error_log( $e->get_error_messages() , 0 );
			return $e;
		}
	}


}
