<?php
namespace C3_CloudFront_Cache_Controller\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Transient {
	const C3_INVALIDATION = 'c3_invalidation';
    const C3_CRON_INDALITATION_TARGET = "c3_cron_invalidation_target";

    public function get_transient( string $key ) {
        return get_transient( $key );
    }

    /**
     * @see https://developer.wordpress.org/reference/functions/set_transient/
     */
    public function set_transient( string $transient, $value, int $expiration = null ) {
        return set_transient( $transient, $value, $expiration );
    }

    public function get_invalidation_transient() {
        return $this->get_transient( self::C3_INVALIDATION );
    }

    public function set_invalidation_transient( $value, int $expiration = null ) {
        return $this->set_transient( self::C3_INVALIDATION, $value, $expiration );
    }

    public function get_invalidation_target() {
        return $this->get_transient( self::C3_CRON_INDALITATION_TARGET );
    }

    public function set_invalidation_target( $value, int $expiration = null ) {
        return $this->set_transient( self::C3_CRON_INDALITATION_TARGET, $value, $expiration );
    }
    public function delete_invalidation_target() {
        return $this->delete_transient( self::C3_CRON_INDALITATION_TARGET );
    }

}