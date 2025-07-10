<?php
/**
 * Invalidation Service Integration Test with Mock Server
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test;

use C3_CloudFront_Cache_Controller\Invalidation_Service;
use C3_CloudFront_Cache_Controller\Test\Helpers\AWS_Mock_Helper;
use C3_CloudFront_Cache_Controller\Test\Helpers\WP_Mock_Helper;

/**
 * @group invalidation
 * @group mock-integration
 */
class Invalidation_Service_Mock_Integration_Test extends \WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		AWS_Mock_Helper::start_http_interception();
	}

	public function tearDown(): void {
		AWS_Mock_Helper::stop_http_interception();
		\Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test complete invalidation workflow with request validation
	 */
	public function test_invalidate_all_with_request_validation() {
		update_option( 'c3_settings', array(
			'distribution_id' => 'E123456789ABCDEF',
			'access_key' => 'test-access-key',
			'secret_key' => 'test-secret-key',
		) );

		$service = new Invalidation_Service();
		$result = $service->invalidate_all();

		$this->assertIsArray( $result );
		$this->assertEquals( 'Success', $result['type'] );

		$requests = AWS_Mock_Helper::get_intercepted_requests();
		$this->assertCount( 1, $requests );

		$request = $requests[0];
		$this->assertEquals( 'POST', $request['method'] );
		$this->assertStringContainsString( '/invalidation', $request['url'] );
		$this->assertStringContainsString( 'E123456789ABCDEF', $request['url'] );
		$this->assertStringContainsString( '<Path>/*</Path>', $request['body'] );
	}

	/**
	 * Test post invalidation with request validation
	 */
	public function test_invalidate_post_with_request_validation() {
		$post = $this->factory->post->create_and_get( array(
			'post_status' => 'publish',
			'post_name' => 'test-post',
		) );

		update_option( 'c3_settings', array(
			'distribution_id' => 'E123456789ABCDEF',
			'access_key' => 'test-access-key',
			'secret_key' => 'test-secret-key',
		) );

		$service = new Invalidation_Service();
		$result = $service->invalidate_post_cache( $post, true );

		$this->assertIsArray( $result );
		$this->assertEquals( 'Success', $result['type'] );

		$requests = AWS_Mock_Helper::get_intercepted_requests();
		$this->assertCount( 1, $requests );

		$request = $requests[0];
		$this->assertStringContainsString( '<Path>/', $request['body'] );
		$this->assertStringContainsString( '<Quantity>1</Quantity>', $request['body'] );
	}

	/**
	 * Test error handling with mock error responses
	 */
	public function test_error_handling_with_mock_responses() {
		AWS_Mock_Helper::set_mock_error_response( 403, 'AccessDenied', 'Access denied' );

		update_option( 'c3_settings', array(
			'distribution_id' => 'E123456789ABCDEF',
			'access_key' => 'invalid-key',
			'secret_key' => 'invalid-secret',
		) );

		$service = new Invalidation_Service();
		$result = $service->invalidate_all();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'cloudfront_api_error', $result->get_error_code() );

		AWS_Mock_Helper::clear_mock_error_response();
	}

	/**
	 * Test AWS signature validation in real workflow
	 */
	public function test_aws_signature_validation_in_workflow() {
		update_option( 'c3_settings', array(
			'distribution_id' => 'E123456789ABCDEF',
			'access_key' => 'test-access-key',
			'secret_key' => 'test-secret-key',
		) );

		$service = new Invalidation_Service();
		$service->invalidate_all();

		$request = AWS_Mock_Helper::get_last_request();
		$this->assertArrayHasKey( 'Authorization', $request['headers'] );
		
		$validation = AWS_Mock_Helper::validate_aws_signature(
			$request['headers']['Authorization'],
			'test-access-key'
		);

		$this->assertTrue( $validation['algorithm'], 'Algorithm validation failed' );
		$this->assertEquals( 1, $validation['credential'], 'Credential validation failed' );
		$this->assertTrue( $validation['signed_headers'], 'Signed headers validation failed' );
		$this->assertTrue( $validation['signature'], 'Signature validation failed' );
	}

	/**
	 * Test multiple invalidation requests
	 */
	public function test_multiple_invalidation_requests() {
		update_option( 'c3_settings', array(
			'distribution_id' => 'E123456789ABCDEF',
			'access_key' => 'test-access-key',
			'secret_key' => 'test-secret-key',
		) );

		$post1 = $this->factory->post->create_and_get( array(
			'post_status' => 'publish',
			'post_name' => 'post-one',
		) );

		$post2 = $this->factory->post->create_and_get( array(
			'post_status' => 'publish',
			'post_name' => 'post-two',
		) );

		$service = new Invalidation_Service();
		$service->invalidate_post_cache( $post1, true );
		$service->invalidate_post_cache( $post2, true );

		$requests = AWS_Mock_Helper::get_intercepted_requests();
		$this->assertCount( 2, $requests );

		$this->assertStringContainsString( '<Path>/', $requests[0]['body'] );
		$this->assertStringContainsString( '<Path>/', $requests[1]['body'] );
	}

	/**
	 * Test request timeout configuration
	 */
	public function test_request_timeout_configuration() {
		update_option( 'c3_settings', array(
			'distribution_id' => 'E123456789ABCDEF',
			'access_key' => 'test-access-key',
			'secret_key' => 'test-secret-key',
		) );

		$service = new Invalidation_Service();
		$service->invalidate_all();

		$request = AWS_Mock_Helper::get_last_request();
		$this->assertEquals( 30, $request['timeout'] );
	}
}
