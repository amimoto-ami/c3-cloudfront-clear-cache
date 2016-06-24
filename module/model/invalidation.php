<?php
/**
 * C3_Invalidation
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-plugin-dashboard
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Controle Invalidation
 *
 * @class C3_Invalidation
 * @since 4.0.0
 */
class C3_Invalidation extends C3_Base {
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
	 * Create Invalidation Request
	 *
	 * @return boolean | WP_Error
	 * @since 4.0.0
	 * @access public
	 */
	public function invalidation( $post = false ) {
		$key = self::C3_INVALIDATION_KEY;
		if ( apply_filters( 'c3_invalidation_flag', get_transient( $key ) ) ) {
			return;
		}

		$options = $this->get_c3_options();
		if ( ! isset( $options['distribution_id'] ) || ! $options['distribution_id'] ) {
			return new WP_Error( 'C3 Invalidation Error', 'Distribution ID is not defined.' );
		}

		if ( c3_is_later_than_php_55() ) {
			$sdk = C3_Client_V3::get_instance();
		} else {
			$sdk = C3_Client_V2::get_instance();
		}
		$cf_client = $sdk->create_cloudfront_client( $options );
		if ( is_wp_error( $cf_client ) ) {
			return $cf_client;
		}

		try {
			$query = $sdk->create_invalidation_query( $options, $post );
			set_transient( $key , true , 5 * 60 );
			$result = $cf_client->createInvalidation( $query );
			return true;
		} catch ( Aws\CloudFront\Exception\TooManyInvalidationsInProgressException $e ) {
			error_log( $e->__toString( ) , 0 );
			$e = new WP_Error( 'C3 Invalidation Error', $e->__toString() );
			return $e;
		} catch ( Aws\CloudFront\Exception\AccessDeniedException $e ) {
			error_log( $e->__toString( ) , 0 );
			$e = new WP_Error( 'C3 Invalidation Error', $e->__toString() );
			return $e;
		} catch ( Exception $e ) {
			$e = new WP_Error( 'C3 Invalidation Error', $e->getMessage() );
			error_log( $e->get_error_messages() , 0 );
			return $e;
		}
	}

	private function _is_invalidation ( $new_status, $old_status ) {
		if ( 'publish' === $new_status ) {
			//if publish or update posts.
			$result = true;
		} elseif ( 'publish' === $old_status && $new_status !== $old_status ) {
			//if un-published post.
			$result = true;
		} else {
			$result = false;
		}
		$result = apply_filters( 'c3_is_invalidation' , $result );
		return $result;
	}

	public function post_invalidation( $new_status, $old_status, $post ) {
		if ( ! $this->_is_invalidation( $new_status , $old_status ) ) {
			return;
		}
		$this->invalidation( $post );
	}


}
