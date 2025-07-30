<?php
namespace C3_CloudFront_Cache_Controller\Test\WP;
use C3_CloudFront_Cache_Controller\WP\Status_Service;
use C3_CloudFront_Cache_Controller\WP\Transient;

class Status_Service_Test extends \WP_UnitTestCase {
    private $target;
    private $transient_mock;

    protected function setUp(): void {
        parent::setUp();
        $this->transient_mock = $this->createMock( Transient::class );
        $this->target = new Status_Service( $this->transient_mock );
    }

    public function test_get_cache_status_returns_array() {
        $this->transient_mock->method( 'get_current_status' )->willReturn( 'idle' );
        $this->transient_mock->method( 'get_last_successful_purge' )->willReturn( null );
        $this->transient_mock->method( 'get_last_error' )->willReturn( null );
        $this->transient_mock->method( 'get_invalidation_target' )->willReturn( null );
        
        $status = $this->target->get_cache_status();
        $this->assertIsArray( $status );
        $this->assertArrayHasKey( 'current_status', $status );
        $this->assertArrayHasKey( 'next_scheduled', $status );
        $this->assertArrayHasKey( 'scheduled_paths', $status );
        $this->assertArrayHasKey( 'last_successful', $status );
        $this->assertArrayHasKey( 'last_error', $status );
    }

    public function test_set_status_processing() {
        $this->transient_mock->expects( $this->once() )
            ->method( 'set_current_status' )
            ->with( 'processing', 300 );
        
        $this->target->set_status_processing();
    }

    public function test_set_status_completed() {
        $this->transient_mock->expects( $this->once() )
            ->method( 'set_current_status' )
            ->with( 'idle', 60 );
        
        $this->transient_mock->expects( $this->once() )
            ->method( 'set_last_successful_purge' )
            ->with( $this->callback( function( $data ) {
                return is_array( $data ) && isset( $data['timestamp'] ) && isset( $data['invalidation_id'] );
            }), DAY_IN_SECONDS );
        
        $this->target->set_status_completed( 'test-id' );
    }

    public function test_set_status_error() {
        $this->transient_mock->expects( $this->once() )
            ->method( 'set_current_status' )
            ->with( 'error', 300 );
        
        $this->transient_mock->expects( $this->once() )
            ->method( 'set_last_error' )
            ->with( $this->callback( function( $data ) {
                return is_array( $data ) && isset( $data['timestamp'] ) && isset( $data['message'] );
            }), DAY_IN_SECONDS );
        
        $this->target->set_status_error( 'Test error message' );
    }

    public function test_get_scheduled_paths_returns_null_when_no_query() {
        $this->transient_mock->method( 'get_invalidation_target' )->willReturn( null );
        
        $status = $this->target->get_cache_status();
        $this->assertNull( $status['scheduled_paths'] );
    }

    public function test_get_scheduled_paths_returns_paths_array() {
        $mock_query = array(
            'Paths' => array(
                'Items' => array( '/path1', '/path2', '/path3' ),
                'Quantity' => 3
            )
        );
        $this->transient_mock->method( 'get_invalidation_target' )->willReturn( $mock_query );
        
        $status = $this->target->get_cache_status();
        $this->assertIsArray( $status['scheduled_paths'] );
        $this->assertEquals( array( '/path1', '/path2', '/path3' ), $status['scheduled_paths'] );
    }

    public function test_get_scheduled_paths_handles_wildcard() {
        $mock_query = array(
            'Paths' => array(
                'Items' => array( '/*' ),
                'Quantity' => 1
            )
        );
        $this->transient_mock->method( 'get_invalidation_target' )->willReturn( $mock_query );
        
        $status = $this->target->get_cache_status();
        $this->assertIsArray( $status['scheduled_paths'] );
        $this->assertEquals( array( '/*' ), $status['scheduled_paths'] );
    }
}
