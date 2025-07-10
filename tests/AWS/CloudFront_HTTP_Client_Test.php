<?php
/**
 * CloudFront HTTP Client Test with Mock Server
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test\AWS;

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\AWS\CloudFront_HTTP_Client;
use C3_CloudFront_Cache_Controller\Test\Helpers\AWS_Mock_Helper;

/**
 * CloudFront HTTP Client Test
 *
 * @group mock-integration
 */
class CloudFront_HTTP_Client_Test extends TestCase {

	public function setUp(): void {
		AWS_Mock_Helper::start_http_interception();
	}

	public function tearDown(): void {
		AWS_Mock_Helper::stop_http_interception();
		\Mockery::close();
	}

	/**
	 * Test create invalidation request format
	 */
	public function test_create_invalidation_request_format() {
		$client = new CloudFront_HTTP_Client(
			'test-access-key',
			'test-secret-key',
			'us-east-1'
		);

		$result = $client->create_invalidation( 'E123456789', array( '/*', '/path1', '/path2' ) );

		$requests = AWS_Mock_Helper::get_intercepted_requests();
		$this->assertCount( 1, $requests );

		$request = $requests[0];
		
		$this->assertStringContainsString( 'cloudfront.amazonaws.com', $request['url'] );
		$this->assertStringContainsString( '/2020-05-31/distribution/E123456789/invalidation', $request['url'] );
		
		$this->assertEquals( 'POST', $request['method'] );
		
		$this->assertArrayHasKey( 'Authorization', $request['headers'] );
		$this->assertTrue( 
			isset( $request['headers']['X-Amz-Date'] ) || isset( $request['headers']['x-amz-date'] ),
			'X-Amz-Date header not found in request headers'
		);
		$this->assertArrayHasKey( 'Content-Type', $request['headers'] );
		$this->assertEquals( 'application/xml', $request['headers']['Content-Type'] );
		
		$this->assertStringContainsString( '<InvalidationBatch>', $request['body'] );
		$this->assertStringContainsString( '<CallerReference>', $request['body'] );
		$this->assertStringContainsString( '<Paths>', $request['body'] );
		$this->assertStringContainsString( '<Quantity>3</Quantity>', $request['body'] );
		$this->assertStringContainsString( '<Path>/*</Path>', $request['body'] );
		$this->assertStringContainsString( '<Path>/path1</Path>', $request['body'] );
		$this->assertStringContainsString( '<Path>/path2</Path>', $request['body'] );
	}

	/**
	 * Test list invalidations request format
	 */
	public function test_list_invalidations_request_format() {
		$client = new CloudFront_HTTP_Client(
			'test-access-key',
			'test-secret-key',
			'us-east-1'
		);

		$result = $client->list_invalidations( 'E123456789', 10 );

		$requests = AWS_Mock_Helper::get_intercepted_requests();
		$this->assertCount( 1, $requests );

		$request = $requests[0];
		
		$this->assertStringContainsString( 'cloudfront.amazonaws.com', $request['url'] );
		$this->assertStringContainsString( '/2020-05-31/distribution/E123456789/invalidation', $request['url'] );
		$this->assertStringContainsString( 'MaxItems=10', $request['url'] );
		
		$this->assertEquals( 'GET', $request['method'] );
		
		$this->assertArrayHasKey( 'Authorization', $request['headers'] );
		$this->assertTrue( 
			isset( $request['headers']['X-Amz-Date'] ) || isset( $request['headers']['x-amz-date'] ),
			'X-Amz-Date header not found in request headers'
		);
		$this->assertEmpty( $request['body'] );
	}

	/**
	 * Test get distribution request format
	 */
	public function test_get_distribution_request_format() {
		$client = new CloudFront_HTTP_Client(
			'test-access-key',
			'test-secret-key',
			'us-east-1'
		);

		$result = $client->get_distribution( 'E123456789' );

		$requests = AWS_Mock_Helper::get_intercepted_requests();
		$this->assertCount( 1, $requests );

		$request = $requests[0];
		
		$this->assertStringContainsString( 'cloudfront.amazonaws.com', $request['url'] );
		$this->assertStringContainsString( '/2020-05-31/distribution/E123456789', $request['url'] );
		$this->assertStringNotContainsString( '/invalidation', $request['url'] );
		
		$this->assertEquals( 'GET', $request['method'] );
		
		$this->assertArrayHasKey( 'Authorization', $request['headers'] );
		$this->assertTrue( 
			isset( $request['headers']['X-Amz-Date'] ) || isset( $request['headers']['x-amz-date'] ),
			'X-Amz-Date header not found in request headers'
		);
		$this->assertEmpty( $request['body'] );
	}

	/**
	 * Test AWS signature format in requests
	 */
	public function test_aws_signature_format() {
		$client = new CloudFront_HTTP_Client(
			'test-access-key',
			'test-secret-key',
			'us-east-1'
		);

		$client->create_invalidation( 'E123456789', array( '/*' ) );

		$request = AWS_Mock_Helper::get_last_request();
		$auth_header = $request['headers']['Authorization'];
		
		$this->assertStringStartsWith( 'AWS4-HMAC-SHA256', $auth_header );
		$this->assertStringContainsString( 'Credential=test-access-key/', $auth_header );
		$this->assertStringContainsString( '/us-east-1/cloudfront/aws4_request', $auth_header );
		$this->assertStringContainsString( 'SignedHeaders=', $auth_header );
		$this->assertStringContainsString( 'Signature=', $auth_header );
		
		if ( isset( $request['headers']['X-Amz-Date'] ) ) {
			$this->assertMatchesRegularExpression( '/^\d{8}T\d{6}Z$/', $request['headers']['X-Amz-Date'] );
		} elseif ( isset( $request['headers']['x-amz-date'] ) ) {
			$this->assertMatchesRegularExpression( '/^\d{8}T\d{6}Z$/', $request['headers']['x-amz-date'] );
		}
	}

	/**
	 * Test XML validation
	 */
	public function test_xml_validation() {
		$client = new CloudFront_HTTP_Client(
			'test-access-key',
			'test-secret-key',
			'us-east-1'
		);

		$client->create_invalidation( 'E123456789', array( '/*', '/test' ) );

		$request = AWS_Mock_Helper::get_last_request();
		$validation = AWS_Mock_Helper::validate_invalidation_xml( $request['body'] );

		$this->assertTrue( $validation['valid_xml'] );
		$this->assertTrue( $validation['has_caller_reference'] );
		$this->assertTrue( $validation['has_paths'] );
		$this->assertTrue( $validation['has_quantity'] );
		$this->assertTrue( $validation['has_items'] );
	}

	/**
	 * Test error handling
	 */
	public function test_error_handling() {
		AWS_Mock_Helper::set_mock_error_response( 403, 'AccessDenied', 'Access denied' );

		$client = new CloudFront_HTTP_Client(
			'invalid-key',
			'invalid-secret',
			'us-east-1'
		);

		$result = $client->create_invalidation( 'E123456789', array( '/*' ) );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'cloudfront_api_error', $result->get_error_code() );

		AWS_Mock_Helper::clear_mock_error_response();
	}
}
