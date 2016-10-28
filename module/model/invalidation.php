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
		$query = $this->_merge_transient_invalidation_query( $query );
		set_transient( self::C3_CRON_INDALITATION_TARGET , $query , 5 * 60 );
		$time = time() + MINUTE_IN_SECONDS * 5;
		wp_schedule_single_event( $time, 'c3_cron_invalidation');
	}

	/**
	 * Do invalidation as cron event
	 *
	 * @since 4.3.0
	 * @access public
	 * @param array $query
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
			if ( 10 < $item_count ) {
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

		$options = $this->get_c3_options();
		if ( ! isset( $options['distribution_id'] ) || ! $options['distribution_id'] ) {
			return new WP_Error( 'C3 Invalidation Error', 'Distribution ID is not defined.' );
		}
		$query = $sdk->create_invalidation_query( $options, $post );
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
	 * @return boolean | WP_Error
	 **/
	private function _do_invalidation( $cf_client, $query ) {
		try {
			set_transient( self::C3_INVALIDATION_KEY , true , 5 * 60 );
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
