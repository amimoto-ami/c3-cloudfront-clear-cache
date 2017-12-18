<?php
/**
 * C3_Client_V3
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-plugin-dashboard
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Aws\CloudFront\CloudFrontClient;

/**
 * CloudFront Client (Version3)
 *
 * @class C3_Client_V3
 * @since 4.0.0
 */
class C3_Client_V3 extends C3_Client_Base {
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
	public function create_cloudfront_client( $c3_settings = false ) {
		$credential = $this->create_credentials( $c3_settings );
		$credential = apply_filters( 'c3_credential', $credential );
		if ( is_wp_error( $credential ) ) {
			return $credential;
		}
		$param = array(
			'version' => '2016-01-28',
			'region'  => 'us-east-1',
		);
		if ( $credential ) {
			$param = array_merge( $param, $credential );
		}
		$cf_client = new CloudFrontClient( $param );
		return $cf_client;
	}

	/**
	 * Create Credentials Params
	 *
	 * @return array
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
		/*
		if ( ! isset( $c3_settings['access_key'] ) || ! $c3_settings['access_key'] ) {
			$e = new WP_Error( 'C3 Create Client Error', 'AWS Access Key is not found.' );
		}
		if ( ! isset( $c3_settings['secret_key'] ) || ! $c3_settings['secret_key'] ) {
			$e = new WP_Error( 'C3 Create Client Error', 'AWS Secret Key is not found.' );
		}
		*/
		if ( is_wp_error( $e ) ) {
			return $e;
		}
		$credentials = array(
			'credentials' => array(
				'key'    => esc_attr( $c3_settings['access_key'] ),
				'secret' => esc_attr( $c3_settings['secret_key'] ),
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
	public function create_invalidation_query( $dist_id, $options, $post = false ) {
		$items = $this->get_invalidation_items( $options, $post );

		return array(
			'DistributionId' => esc_attr( $dist_id ),
			'InvalidationBatch' => array(
				'CallerReference' => uniqid(),
				'Paths' => array(
					'Items' => $items,
					'Quantity' => count( $items ),
				),
			)
		);
	}
}
