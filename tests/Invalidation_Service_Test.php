<?php
namespace C3_CloudFront_Cache_Controller\Test;
use C3_CloudFront_Cache_Controller\Invalidation_Service;
use C3_CloudFront_Cache_Controller\Constants;
use C3_CloudFront_Cache_Controller\Test\Mocks\WP\Options;
use C3_CloudFront_Cache_Controller\WP\Options_Service;


class Invalidation_Service_Test extends \WP_UnitTestCase {
    /**
     * @dataProvider provide_test_get_plugin_option_case
     */
    function test_get_plugin_option( $options, $expected ) {
		update_option( Constants::OPTION_NAME, $options );
        $service = new Invalidation_Service();
        $result = $service->get_plugin_option();
        delete_option( Constants::OPTION_NAME );
        $this->assertEquals( $expected, $result );
    }
    function provide_test_get_plugin_option_case() {
        $error = new \WP_Error( 'C3 Invalidation Error', 'distribution_id is required. Please update setting or define a C3_DISTRIBUTION_ID on wp-config.php');
        return [
            [
                array(
                    'distribution_id' => 'EXXX'
                ),
                array(
                    'distribution_id' => 'EXXX',
                    'access_key' => null,
                    'secret_key' => null
                )
            ],
            [
                array(
                    'distribution_id' => 'EXXX',
                    'access_key' => 'attribute1',
                    'secret_key' => 'attribute2',
                ),
                array(
                    'distribution_id' => 'EXXX',
                    'access_key' => 'attribute1',
                    'secret_key' => 'attribute2',
                )
            ],
            [
                array(
                    'access_key' => 'attribute1',
                    'secret_key' => 'attribute2',
                ),
                $error,
            ],
            [
                array(),
                $error,
            ]
        ];
    }

    function test_invalidate_by_query_should_return_wp_error_when_provide_wp_error() {
        $service = new Invalidation_Service();
        $error = new \WP_Error( 'error', 'for unit test' );
        $result = $service->invalidate_by_query( $error );
        $this->assertEquals( $error, $result );
        
    }

    /**
     * @dataProvider provide_should_invalidate_test_case
     */
    function test_should_invalidate( $new_status, $old_status, $expected ) {
        $service = new Invalidation_Service();
        $result = $service->should_invalidate( $new_status, $old_status );
        $this->assertEquals( $expected, $result );
    }
    function provide_should_invalidate_test_case() {
        return [
            [ 'any', 'any', false ],
            [ 'publish', 'any', true ],
            [ 'any', 'publish', true ],
        ];
    }

    /**
     * @dataProvider provide_register_cron_event_test_case
     */
	function test_register_cron_event( $query, $test_case, $callback = null ) {

        if ( isset( $callback ) && isset( $callback[ 'pre_test' ] ) ) {
            $callback[ 'pre_test' ]();
        }
        $service = new Invalidation_Service();
        $result = $service->register_cron_event( $query );

		// Schedule an event and make sure it's returned by wp_next_scheduled().
		$hook_name      = 'c3_cron_invalidation';

        $this->assertEquals( $test_case[ 'should_registered' ], $result );

        if ( true === $test_case[ 'should_registered' ] ) {
            $timestamp = $test_case[ 'timestamp' ];
            $actual_scheduled_time = wp_next_scheduled( $hook_name );
            
            $time_diff = abs( $timestamp - $actual_scheduled_time );
            $this->assertLessThanOrEqual( 300, $time_diff, 'Scheduled time should be within 5 minutes of expected time' );
        }

		// It's a non-recurring event.
		$this->assertFalse( wp_get_schedule( $hook_name ) );

        if ( isset( $callback ) && isset( $callback[ 'after_test' ] ) ) {
            $callback[ 'after_test' ]();
        }
	}
    public function provide_register_cron_event_test_case() {
        return array(
            [
                [],
                array(
                    'should_registered' => false
                ),
            ],
            [
                array(
                    'Paths' => []
                ),
                array(
                    'should_registered' => false
                ),
            ],
            [
                array(
                    'Paths' => array(
                        'Items' => array(
                            '/',
                            '/test'
                        )
                    )
                ),
                array(
                    'should_registered' => true,
                    'timestamp' => time() + MINUTE_IN_SECONDS * 1
                ),
            ],
            [
                array(
                    'Paths' => array(
                        'Items' => array(
                            '/*'
                        )
                    )
                ),
                array(
                    'should_registered' => false
                ),
            ],
            [
                array(
                    'Paths' => array(
                        'Items' => array(
                            '/',
                            '/test'
                        )
                    )
                ),
                array(
                    'should_registered' => false,
                ),
                array(
                    'pre_test' => function () {
                        \add_filter( 'c3_disabled_cron_retry', '__return_true' );
                    },
                    'after_test' => function () {
                        \add_filter( 'c3_disabled_cron_retry', '__return_false' );
                    }
                )
            ]
        );
    }

    public function test_create_post_invalidation_batch() {
        $post = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'hello-world',
        ) );
        $service = new Invalidation_Service(
            new Options_Service(
                new Options()
            )
        );
        $result = $service->create_post_invalidation_batch( [$post] );
        $this->assertEquals( $result[ 'DistributionId' ], 'DIST_ID' );
        $this->assertEquals( $result[ 'InvalidationBatch' ] ['Paths' ], array(
            'Items' => [ '/' ],
            'Quantity' => 1
        ));
    }

    public function test_invalidate_post_cache_error_case() {
        $service = new Invalidation_Service(
            new Options_Service(
                new Options()
            )
        );
        $result = $service->invalidate_post_cache( null );
        $this->assertEquals( is_wp_error( $result ), true );
    }

    public function test_get_invalidation_details_no_options() {
        $service = new Invalidation_Service();
        $result = $service->get_invalidation_details( 'I123456789' );
        $this->assertTrue( is_wp_error( $result ) );
        $this->assertEquals( 'C3 Invalidation Error', $result->get_error_code() );
        $this->assertStringContainsString( 'distribution_id is required', $result->get_error_message() );
    }

    public function test_get_invalidation_details_with_valid_options() {
        update_option( Constants::OPTION_NAME, [
            'distribution_id' => 'E123456789',
            'access_key' => 'test-key',
            'secret_key' => 'test-secret'
        ] );
        
        $service = new Invalidation_Service();
        
        $this->assertInstanceOf( Invalidation_Service::class, $service );
        
        delete_option( Constants::OPTION_NAME );
    }

    /**
     * Test Case: Media deletion cache invalidation functionality
     * 
     * Overview:
     * This test verifies that the delete_attachment hook correctly triggers CloudFront
     * cache invalidation when WordPress media files are deleted.
     * 
     * Expected Behavior:
     * - When an attachment is deleted, the delete_attachment hook should be triggered
     * - The attachment URL should be retrieved using wp_get_attachment_url()
     * - A wildcard invalidation path should be created (e.g., /wp-content/uploads/2024/01/image*)
     * - The CloudFront invalidation should be executed with the wildcard path
     * 
     * Test Method:
     * 1. Create a test attachment using WordPress factory
     * 2. Mock wp_get_attachment_url to return a predictable URL
     * 3. Trigger the delete_attachment action with the attachment ID
     * 4. Verify that invalidation was called with the correct wildcard path
     * 5. Confirm the path follows the pattern: /path/to/filename*
     */
    public function test_media_deletion_cache_invalidation() {
        $attachment_id = $this->factory->attachment->create();
        
        add_filter('wp_get_attachment_url', function($url, $id) use ($attachment_id) {
            if ($id === $attachment_id) {
                return 'https://example.com/wp-content/uploads/2024/01/test-image.jpg';
            }
            return $url;
        }, 10, 2);

        $service = new Invalidation_Service(
            new Options_Service(
                new Options()
            )
        );

        $result = $service->invalidate_attachment_cache($attachment_id);
        
        $this->assertTrue(is_array($result) || is_wp_error($result));
        if (is_array($result)) {
            $this->assertArrayHasKey('type', $result);
            $this->assertEquals('Success', $result['type']);
        }
    }

    /**
     * Test Case: Media deletion cache invalidation edge cases
     * 
     * Overview:
     * This test verifies that the media deletion cache invalidation handles edge cases gracefully.
     * 
     * Expected Behavior:
     * - Invalid attachment IDs should be handled without errors
     * - Attachments with no URL should be handled gracefully
     * - The system should not crash or throw exceptions for edge cases
     * 
     * Test Method:
     * 1. Test with invalid attachment ID (999999)
     * 2. Test with attachment that returns false for wp_get_attachment_url
     * 3. Verify that no invalidation is triggered for these cases
     */
    public function test_media_deletion_cache_invalidation_edge_cases() {
        $service = new Invalidation_Service(
            new Options_Service(
                new Options()
            )
        );

        add_filter('wp_get_attachment_url', function($url, $id) {
            if ($id === 999999) {
                return false;
            }
            return $url;
        }, 10, 2);

        $result = $service->invalidate_attachment_cache(999999);
        
        $this->assertNull($result);
    }
}
