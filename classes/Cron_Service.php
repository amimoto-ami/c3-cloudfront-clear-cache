<?php
namespace C3_CloudFront_Cache_Controller;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cron_Service {
    private $hook_service;
    private $transient_service;

    function __construct( ...$args ) {
        $this->hook_service = new WP\Hooks();
        $this->transient_service = new WP\Transient_Service();
        $this->cf_service = new AWS\CloudFront_Service();

        if ( $args && ! empty( $args ) ) {
            foreach ( $args as $key => $value ) {
                if ( $value instanceof WP\Hooks ) {
                    $this->hook_service = $value;
                } else if ( $value instanceof WP\Transient_Service) {
                    $this->transient_service = $value;
                } else if ( $value instanceof AWS\CloudFront_Service ) {
                    $this->cf_service = $value;
                }
            }
        }
		$this->hook_service->add_action(
            'c3_cron_invalidation',
            array(
                $this, 
                'run_schedule_invalidate'
            )
        );
    }

    public function run_schedule_invalidate() {
		if ( $this->hook_service->apply_filters( 'c3_disabled_cron_retry', false ) ) {
			return;
		}
        $query = $this->transient_service->load_invalidation_query();
        if ( ! $query || empty( $query ) ) {
            return;
        }
		error_log('cron works');
        // Invalidation
        $result = $this->cf_service->create_invalidation( $query );
        error_log( print_r( $result, true ) );
        $this->transient_service->delete_invalidation_query();
    }
}