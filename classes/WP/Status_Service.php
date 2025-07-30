<?php
/**
 * Cache status tracking service
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
 * Status Service
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Status_Service {
	/**
	 * Transient service.
	 *
	 * @var Transient
	 */
	private $transient;

	/**
	 * Hook service
	 *
	 * @var Hooks
	 */
	private $hook_service;

	/**
	 * Inject a external services
	 *
	 * @param mixed ...$args Inject class.
	 */
	function __construct( ...$args ) {
		$this->transient = new Transient();
		$this->hook_service = new Hooks();

		if ( $args && ! empty( $args ) ) {
			foreach ( $args as $value ) {
				if ( $value instanceof Transient ) {
					$this->transient = $value;
				} elseif ( $value instanceof Hooks ) {
					$this->hook_service = $value;
				}
			}
		}
	}

	/**
	 * Get comprehensive cache status
	 *
	 * @return array
	 */
	public function get_cache_status() {
		return array(
			'current_status' => $this->get_current_status(),
			'next_scheduled' => $this->get_next_scheduled_purge(),
			'last_successful' => $this->get_last_successful_purge(),
			'last_error' => $this->get_last_error(),
		);
	}

	/**
	 * Get current cache status
	 *
	 * @return string
	 */
	private function get_current_status() {
		$status = $this->transient->get_current_status();
		if ( ! $status ) {
			return wp_next_scheduled( 'c3_cron_invalidation' ) ? 'scheduled' : 'idle';
		}
		return $status;
	}

	/**
	 * Get next scheduled purge time
	 *
	 * @return string|null
	 */
	private function get_next_scheduled_purge() {
		$timestamp = wp_next_scheduled( 'c3_cron_invalidation' );
		return $timestamp ? date_i18n( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	/**
	 * Get last successful purge data
	 *
	 * @return array|null
	 */
	private function get_last_successful_purge() {
		return $this->transient->get_last_successful_purge();
	}

	/**
	 * Get last error data
	 *
	 * @return array|null
	 */
	private function get_last_error() {
		return $this->transient->get_last_error();
	}

	/**
	 * Set status to processing
	 */
	public function set_status_processing() {
		$this->transient->set_current_status( 'processing', 300 );
	}

	/**
	 * Set status to completed
	 *
	 * @param string|null $invalidation_id CloudFront invalidation ID.
	 */
	public function set_status_completed( $invalidation_id = null ) {
		$this->transient->set_current_status( 'idle', 60 );
		$this->transient->set_last_successful_purge( array(
			'timestamp' => current_time( 'mysql' ),
			'invalidation_id' => $invalidation_id,
		), DAY_IN_SECONDS );
		$this->transient->delete_transient( Transient::C3_LAST_ERROR );
	}

	/**
	 * Set status to error
	 *
	 * @param string $error_message Error message.
	 */
	public function set_status_error( $error_message ) {
		$this->transient->set_current_status( 'error', 300 );
		$this->transient->set_last_error( array(
			'timestamp' => current_time( 'mysql' ),
			'message' => $error_message,
		), DAY_IN_SECONDS );
	}
}
