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
	 * Register Cron event to invalidate
	 *
	 * @param array $query
	 * @since 4.3.0
	 * @access private
	 **/
	private function _register_cron_event( $query ) {
		if ( $query['Paths']['Items'][0] === '/*') {
			return;
		}
		$interval_minutes = apply_filters( 'c3_invalidation_cron_interval', 1 );
		$query = $this->_merge_transient_invalidation_query( $query );
		set_transient( self::C3_CRON_INDALITATION_TARGET , $query , $interval_minutes * MINUTE_IN_SECONDS * 1.5 );
		$time = time() + MINUTE_IN_SECONDS * $interval_minutes;
		wp_schedule_single_event( $time, 'c3_cron_invalidation');
	}

	/**
	 * Do invalidation as cron event
	 *
	 * @since 4.3.0
	 * @access public
	 **/
	public function cron_invalidation() {
		error_log('cron works');
		$query = get_transient( self::C3_CRON_INDALITATION_TARGET );
		if ( ! $query ) {
			return;
		}
		$cf_client = $this->_create_cf_client();
		$result = $this->_do_invalidation( $cf_client, $query );
		error_log( print_r( $result, true ) );
		delete_transient( self::C3_CRON_INDALITATION_TARGET  );
	}

	/**
	 * Merge transiented invalidation query
	 *
	 * @param array $query
	 * @return array $query
	 * @access private
	 * @since 4.3.0
	 **/
	private function _merge_transient_invalidation_query( $query ) {
		$current_transient = get_transient( self::C3_CRON_INDALITATION_TARGET );
		if ( $current_transient ) {
			$query_items = $query['Paths']['Items'];
			$current_items = $current_transient['Paths']['Items'];
			$query['Paths']['Items'] = array_merge( $query_items, $current_items );
			$query['Paths']['Items'] = array_merge( array_unique( $query['Paths']['Items'] ) );
			$item_count = count( $query['Paths']['Items'] );
			if ( apply_filters( 'c3_invalidation_item_limits', 100) < $item_count ) {
				$query['Paths'] = array(
					'Quantity' => 1,
					'Items' => array( '/* ' ),
				);
			} else {
				$query['Paths']['Quantity'] = $item_count;
			}
		}
		return $query;
	}

	/*
	 * Get CloudFront Distribution Id
	 *
	 * @return string | WP_Error
	 * @since 4.4.0
	 * @access private
	 */
	private function _get_dist_id() {
		if ( $this->is_amimoto_managed() && defined( 'AMIMOTO_CDN_ID' ) ) {
			return AMIMOTO_CDN_ID;
		}
		$options = $this->get_c3_options();
		if ( ! isset( $options['distribution_id'] ) || ! $options['distribution_id'] ) {
			return new WP_Error( 'C3 Invalidation Error', 'Distribution ID is not defined.' );
		}
		return $options['distribution_id'];
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
		if ( c3_is_later_than_php_55() ) {
			$sdk = C3_Client_V3::get_instance();
		} else {
			$sdk = C3_Client_V2::get_instance();
		}

		$dist_id = $this->_get_dist_id();
		if ( is_wp_error( $dist_id ) ) {
			return $dist_id;
		}
		$options = $this->get_c3_options();
		$query = $sdk->create_invalidation_query( $dist_id, $options, $post );
		if ( apply_filters( 'c3_invalidation_flag', get_transient( $key ) ) ) {
			$this->_register_cron_event( $query );
			return;
		}

		$cf_client = $this->_create_cf_client();
		if ( is_wp_error( $cf_client ) ) {
			error_log( print_r( $cf_client, true ) );
			return $cf_client;
		}
		return $this->_do_invalidation( $cf_client, $query );
	}

	/**
	 * Create Client Object
	 * @access private
	 * @since 4.3.0
	 * @return object
	 **/
	private function _create_cf_client() {
		$options = $this->get_c3_options();
		if ( c3_is_later_than_php_55() ) {
			$sdk = C3_Client_V3::get_instance();
		} else {
			$sdk = C3_Client_V2::get_instance();
		}
		$cf_client = $sdk->create_cloudfront_client( $options );
		return $cf_client;
	}

	/**
	 * Invalidation
	 *
	 * @param object $cf_client
	 * @param array $query
	 * @access private
	 * @since 4.3.0
	 * @return array | WP_Error
	 **/
	private function _do_invalidation( $cf_client, $query ) {
		try {
			set_transient( self::C3_INVALIDATION_KEY , true , apply_filters( 'c3_invalidation_interval', 1 ) * 60 );
			$result = $cf_client->createInvalidation( $query );
			return $result;
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
			error_log( print_r( $e->get_error_messages(), true ) , 0 );
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
