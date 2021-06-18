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
            $this->assertSame( date( "Y/m/d H:i" , $timestamp ), date( "Y/m/d H:i" , wp_next_scheduled( $hook_name ) ) );
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
}