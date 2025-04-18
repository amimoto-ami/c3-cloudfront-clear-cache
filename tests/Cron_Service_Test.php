<?php
namespace C3_CloudFront_Cache_Controller\Test;
use C3_CloudFront_Cache_Controller\Cron_Service;
use C3_CloudFront_Cache_Controller\AWS\CloudFront_Service;
use C3_CloudFront_Cache_Controller\WP\Transient_Service;

class Cron_Service_Test extends \WP_UnitTestCase {
    /**
     * @dataProvider provide_test_run_schedule_invalidate_test_case
     */
    function test_run_schedule_invalidate( $transient_query, $should_run_the_cron_job ) {

        $transient_stub = $this->createMock(Transient_Service::class);
        $transient_stub->method( 'load_invalidation_query' )
            ->willReturn( $transient_query );

        $cf_mock = $this->createMock( CloudFront_Service::class );
        $cf_mock->method( 'get_distribution_id' )
            ->willReturn( 'Exxxx' );
        $cf_mock->method( 'create_invalidation' )
            ->willReturn( true );

        $service = new Cron_Service( $transient_stub, $cf_mock );
        $result = $service->run_schedule_invalidate();

        $this->assertEquals( $should_run_the_cron_job, $result );

        /**
         * If manually disabled it, should not work the job
         */
        add_filter( 'c3_disabled_cron_retry', '__return_true' );
        $this->assertEquals( false, $service->run_schedule_invalidate() );
        remove_filter( 'c3_disabled_cron_retry', '__return_true' );
    }

    function provide_test_run_schedule_invalidate_test_case() {
        return [
            [
                null,
                false
            ],
            [
                [
                    'Paths' => ['/'],
                    'Quantity' => 1
                ],
                true
            ]
        ];
    }
}