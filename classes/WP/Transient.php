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
	 * Set transient
	 *
	 * @param string $transient_key Key name.
	 * @param mixed  $value Value.
	 * @param mixed  $expiration Cache expiration.
	 */
	public function set_transient( string $transient_key, $value, ?int $expiration = null ) {
		return set_transient( $transient_key, $value, $expiration );
	}

	/**
	 * Get the invalidation transient data
	 */
	public function get_invalidation_transient() {
		return $this->get_transient( self::C3_INVALIDATION );
	}

	/**
	 * Set invalidation flag
	 *
	 * @param boolean $flag Flag value.
	 * @param integer $expiration Cache expiration.
	 */
	public function set_invalidation_transient( bool $flag, ?int $expiration = null ) {
		return $this->set_transient( self::C3_INVALIDATION, $flag, $expiration );
	}

	/**
	 * Get the invalidation target data
	 */
	public function get_invalidation_target() {
		return $this->get_transient( self::C3_CRON_INDALITATION_TARGET );
	}

	/**
	 * Set the invalidation target
	 *
	 * @param mixed $target Invalidation target.
	 * @param mixed $expiration Cache expiration.
	 */
	public function set_invalidation_target( $target, ?int $expiration = null ) {
		return $this->set_transient( self::C3_CRON_INDALITATION_TARGET, $target, $expiration );
	}

	/**
	 * Delete the transient target data
	 */
	public function delete_invalidation_target() {
		return $this->delete_transient( self::C3_CRON_INDALITATION_TARGET );
	}
}
