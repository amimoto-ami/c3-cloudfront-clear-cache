<?php
/**
 * WordPress Transient API service
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
 * Transient API Service
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Transient_Service {
	/**
	 * WP Transiend API Adapter.
	 *
	 * @var Transient Transient API class.
	 */
	private $transient;

	/**
	 * WP Hook Adapter.
	 *
	 * @var Hooks WP Hook Adapter class.
	 */
	private $hook_service;

	/**
	 * Inject a external services
	 *
	 * @param mixed ...$args Inject class.
	 */
	function __construct( ...$args ) {
		if ( $args && ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				if ( $value instanceof Hooks ) {
					$this->hook_service = $value;
				} elseif ( $value instanceof Transient ) {
					$this->transient = $value;
				}
			}
		}
		if ( ! $this->hook_service ) {
			$this->hook_service = new Hooks();
		}
		if ( ! $this->transient ) {
			$this->transient = new Transient();
		}
	}

	/**
	 * Load and detect the cron job target
	 */
	public function should_regist_cron_job() {
		$data = $this->transient->get_invalidation_transient();
		return $this->hook_service->apply_filters( 'c3_invalidation_flag', $data );
	}

	/**
	 * Set the last invalidation time
	 */
	public function set_invalidation_time() {
		return $this->transient->set_invalidation_transient(
			true,
			$this->hook_service->apply_filters( 'c3_invalidation_interval', 1 ) * 60
		);
	}

	/**
	 * Normalize invalidation query
	 *
	 * @param mixed $query Invalidation query.
	 * @since 5.3.4
	 * @return array
	 */
	public function query_normalize( $query ) {
		$default_query = array(
			'Paths' => array(
				'Quantity' => 0,
				'Items'    => array(),
			),
		);
		if ( ! is_array( $query ) || ! isset( $query['Paths'] ) ) {
			return $default_query;
		}

		if ( ! isset( $query['Paths']['Items'] ) && ! isset( $query['Paths']['Quantity'] ) ) {
			return $default_query;
		}
		if ( ! isset( $query['Paths']['Items'] ) || ! is_array( $query['Paths']['Items'] ) ) {
			$query['Paths']['Items'] = $default_query['Paths']['Items'];
		}
		$query['Paths']['Quantity'] = count( $query['Paths']['Items'] );
		return $query;
	}

	/**
	 * Merge transiented invalidation query
	 *
	 * @param array      $query Merge target query.
	 * @param array|null $current_transient Saved invalidation query.
	 * @return array $query
	 * @access public
	 * @since 4.3.0
	 **/
	public function merge_transient_invalidation_query( $query, $current_transient = null ) {
		$query = $this->query_normalize( $query );

		if ( $current_transient ) {
			$current_transient = $this->query_normalize( $current_transient );

			$query_items             = $query['Paths']['Items'];
			$current_items           = $current_transient['Paths']['Items'];
			$query['Paths']['Items'] = array_merge( $query_items, $current_items );
			$query['Paths']['Items'] = array_merge( array_unique( $query['Paths']['Items'] ) );
			$item_count              = count( $query['Paths']['Items'] );
			if ( $this->hook_service->apply_filters( 'c3_invalidation_item_limits', 100 ) < $item_count ) {
				$query['Paths'] = array(
					'Quantity' => 1,
					'Items'    => array( '/*' ),
				);
			} else {
				$query['Paths']['Quantity'] = $item_count;
			}
		}
		return $query;
	}

	/**
	 * Save the invalidation query to transient API.
	 *
	 * @param array $query Invalidation query.
	 * @return void
	 */
	public function save_invalidation_query( $query ) {
		$transiented_query = $this->load_invalidation_query();
		$merged_query      = $this->merge_transient_invalidation_query( $query, $transiented_query );
		$this->set_invalidation_query( $merged_query );
	}

	/**
	 * Set transient object for record the invalidation query
	 *
	 * @param array $query Invalidation query.
	 * @return void
	 */
	public function set_invalidation_query( $query ) {
		$interval_minutes = $this->hook_service->apply_filters( 'c3_invalidation_cron_interval', 10 );
		$this->transient->set_invalidation_target( $query, $interval_minutes * MINUTE_IN_SECONDS * 1.5 );
	}

	/**
	 * Delete the saved invalidation query
	 */
	public function delete_invalidation_query() {
		$this->transient->delete_invalidation_target();
	}

	/**
	 * Load invalidation query from transient
	 */
	public function load_invalidation_query() {
		$result = $this->transient->get_invalidation_target();
		return $result;
	}

}
