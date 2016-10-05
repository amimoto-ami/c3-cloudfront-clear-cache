<?php
/**
 * C3_Client_V2
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-plugin-dashboard
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Aws\Common\Credentials\Credentials;
use Aws\CloudFront\CloudFrontClient;

/**
 * CloudFront Client (Version2)
 *
 * @class C3_Client_V2
 * @since 4.0.0
 */
class C3_Client_V2 extends C3_Client_Base {
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
	 * Create CloudFront Client
	 *
	 * @return Object (Aws\Common\Credentials\Credentials)
	 * @since 4.0.0
	 * @access public
	 */
	public function create_cloudfront_client( $options = false ) {
		$credential = $this->create_credentials( $options );
		$credential = apply_filters( 'c3_credential', $credential );
		if ( is_wp_error( $credential ) ) {
			return $credential;
		}
		if( $credential ) {
			$cf_client = CloudFrontClient::factory( $credential );
		} else {
			$cf_client = CloudFrontClient::factory();
		}
		return $cf_client;
	}

	/**
	 * Create Credentials Object
	 *
	 * @return Object (Aws\Common\Credentials\Credentials)
	 * @since 4.0.0
	 * @access public
	 */
	public function create_credentials( $c3_settings = false ) {
		$e = true;
		if ( ! $c3_settings ) {
			$c3_settings = $this->get_c3_options();
		}
		if ( ! $c3_settings ) {
			$e = new WP_Error( 'C3 Create Client Error', 'General setting params not defined.' );
		}
		if ( ! isset( $c3_settings['access_key'] ) || ! $c3_settings['access_key'] ) {
			$e = new WP_Error( 'C3 Create Client Error', 'AWS Access Key is not found.' );
		}
		if ( ! isset( $c3_settings['secret_key'] ) || ! $c3_settings['secret_key'] ) {
			$e = new WP_Error( 'C3 Create Client Error', 'AWS Secret Key is not found.' );
		}
		if ( is_wp_error( $e ) ) {
			return $e;
		}
		$credentials = array(
			'credentials' => new Credentials(
				esc_attr( $c3_settings['access_key'] ) ,
				esc_attr( $c3_settings['secret_key'] )
			),
		);
		return $credentials;
	}

	/**
	 * Create Invalidation Query
	 *
	 * @return array
	 * @since 4.0.0
	 * @access public
	 */
	public function create_invalidation_query( $options, $post = false ) {
		$items = $this->get_invalidation_items( $options, $post );

		return array(
			'DistributionId' => esc_attr( $options['distribution_id'] ),
			'Paths' => array(
				'Quantity' => count( $items ),
				'Items'    => $items,
			),
			'CallerReference' => uniqid(),
		);
	}
}
