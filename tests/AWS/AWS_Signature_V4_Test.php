<?php
/**
 * AWS Signature V4 Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test\AWS;

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\AWS\AWS_Signature_V4;
use C3_CloudFront_Cache_Controller\Test\Helpers\AWS_Mock_Helper;

/**
 * AWS Signature V4 Test
 *
 * @group aws-signature
 */
class AWS_Signature_V4_Test extends TestCase {

	public function tearDown(): void {
		\Mockery::close();
	}

	/**
	 * Test signature generation for GET request
	 */
	public function test_sign_get_request() {
		$signature_service = new AWS_Signature_V4(
			'test-access-key',
			'test-secret-key',
			'us-east-1',
			'cloudfront'
		);

		$signed_headers = $signature_service->sign_request(
			'GET',
			'cloudfront.amazonaws.com',
			'/2020-05-31/distribution/E123456789/invalidation'
		);

		$this->assertArrayHasKey( 'Authorization', $signed_headers );
		if ( isset( $signed_headers['X-Amz-Date'] ) ) {
			$this->assertMatchesRegularExpression( '/^\d{8}T\d{6}Z$/', $signed_headers['X-Amz-Date'] );
		} elseif ( isset( $signed_headers['x-amz-date'] ) ) {
			$this->assertMatchesRegularExpression( '/^\d{8}T\d{6}Z$/', $signed_headers['x-amz-date'] );
		}
		$this->assertStringContainsString( 'AWS4-HMAC-SHA256', $signed_headers['Authorization'] );
		$this->assertStringContainsString( 'Credential=test-access-key', $signed_headers['Authorization'] );
		$this->assertStringContainsString( 'SignedHeaders=', $signed_headers['Authorization'] );
		$this->assertStringContainsString( 'Signature=', $signed_headers['Authorization'] );
	}

	/**
	 * Test signature generation for POST request with body
	 */
	public function test_sign_post_request_with_body() {
		$signature_service = new AWS_Signature_V4(
			'test-access-key',
			'test-secret-key',
			'us-east-1',
			'cloudfront'
		);

		$xml_body = '<?xml version="1.0" encoding="UTF-8"?><InvalidationBatch><CallerReference>test</CallerReference></InvalidationBatch>';
		$headers = array( 'Content-Type' => 'application/xml' );

		$signed_headers = $signature_service->sign_request(
			'POST',
			'cloudfront.amazonaws.com',
			'/2020-05-31/distribution/E123456789/invalidation',
			$xml_body,
			$headers
		);

		$this->assertArrayHasKey( 'Authorization', $signed_headers );
		$this->assertArrayHasKey( 'Content-Type', $signed_headers );
		$this->assertEquals( 'application/xml', $signed_headers['Content-Type'] );
		$this->assertStringContainsString( 'content-type', strtolower( $signed_headers['Authorization'] ) );
	}

	/**
	 * Test signature with special characters in path
	 */
	public function test_sign_request_with_special_characters() {
		$signature_service = new AWS_Signature_V4(
			'test-access-key',
			'test-secret-key',
			'us-east-1',
			'cloudfront'
		);

		$signed_headers = $signature_service->sign_request(
			'GET',
			'cloudfront.amazonaws.com',
			'/2020-05-31/distribution/E123456789/invalidation?MaxItems=25'
		);

		$this->assertArrayHasKey( 'Authorization', $signed_headers );
		$this->assertStringContainsString( 'AWS4-HMAC-SHA256', $signed_headers['Authorization'] );
	}

	/**
	 * Test signature validation using mock helper
	 */
	public function test_signature_validation() {
		$signature_service = new AWS_Signature_V4(
			'test-access-key',
			'test-secret-key',
			'us-east-1',
			'cloudfront'
		);

		$signed_headers = $signature_service->sign_request(
			'GET',
			'cloudfront.amazonaws.com',
			'/2020-05-31/distribution/E123456789'
		);

		$this->assertArrayHasKey( 'Authorization', $signed_headers );
		
		$validation = AWS_Mock_Helper::validate_aws_signature(
			$signed_headers['Authorization'],
			'test-access-key'
		);

		$this->assertTrue( $validation['algorithm'], 'Algorithm validation failed' );
		$this->assertEquals( 1, $validation['credential'], 'Credential validation failed' );
		$this->assertTrue( $validation['signed_headers'], 'Signed headers validation failed' );
		$this->assertTrue( $validation['signature'], 'Signature validation failed' );
	}

	/**
	 * Test signature headers are properly formatted
	 */
	public function test_signature_headers_format() {
		$signature_service = new AWS_Signature_V4(
			'test-access-key',
			'test-secret-key',
			'us-east-1',
			'cloudfront'
		);

		$signed_headers = $signature_service->sign_request(
			'GET',
			'cloudfront.amazonaws.com',
			'/2020-05-31/distribution/E123456789'
		);

		$this->assertArrayHasKey( 'Authorization', $signed_headers );
		$this->assertStringStartsWith( 'AWS4-HMAC-SHA256', $signed_headers['Authorization'] );
		
		if ( isset( $signed_headers['X-Amz-Date'] ) ) {
			$this->assertMatchesRegularExpression( '/^\d{8}T\d{6}Z$/', $signed_headers['X-Amz-Date'] );
		}
	}
}
