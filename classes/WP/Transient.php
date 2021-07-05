<?php
/**
 * WordPress Transient API class
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
 * Transient Adapter class
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Transient {
	const C3_INVALIDATION             = 'c3_invalidation';
	const C3_CRON_INDALITATION_TARGET = 'c3_cron_invalidation_target';

	/**
	 * Get transiend by key name
	 *
	 * @param string $key Target transient key name.
	 */
	public function get_transient( string $key ) {
		return get_transient( $key );
	}

	/**
	 * Delete transiend by key name
	 *
	 * @param string $key Target transient key name.
	 */
	public function delete_transient( string $key ) {
		return delete_transient( $key );
	}

	/**
	 * Set the transient
	 *
	 * @see https://developer.wordpress.org/reference/functions/set_transient/
	 * @param string $transient Transient key name.
	 * @param mixed  $value Saved value.
	 * @param int    $expiration Expiration of the transient.
	 */
	public function set_transient( string $transient, $value, int $expiration = null ) {
		return set_transient( $transient, $value, $expiration );
	}

	/**
	 * Get the invalidation transient data
	 */
	public function get_invalidation_transient() {
		return $this->get_transient( self::C3_INVALIDATION );
	}

	/**
	 * Set the invalidation transient
	 *
	 * @param mixed $value Saved value.
	 * @param int   $expiration Expiration of the transient.
	 */
	public function set_invalidation_transient( $value, int $expiration = null ) {
		return $this->set_transient( self::C3_INVALIDATION, $value, $expiration );
	}

	/**
	 * Get the invalidation target data
	 */
	public function get_invalidation_target() {
		return $this->get_transient( self::C3_CRON_INDALITATION_TARGET );
	}

	/**
	 * Set the invalidation targets
	 *
	 * @param mixed $value Saved value.
	 * @param int   $expiration Expiration of the transient.
	 */
	public function set_invalidation_target( $value, int $expiration = null ) {
		return $this->set_transient( self::C3_CRON_INDALITATION_TARGET, $value, $expiration );
	}

	/**
	 * Delete the transient target data
	 */
	public function delete_invalidation_target() {
		return $this->delete_transient( self::C3_CRON_INDALITATION_TARGET );
	}

}
