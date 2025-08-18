<?php
/**
 * AWS SDK Mock Helper for testing
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test\Helpers;

/**
 * AWS Mock Helper class
 */
class AWS_Mock_Helper {
	
	/**
	 * Create mock CloudFront client that succeeds
	 */
	public static function create_successful_cloudfront_client() {
		$mock_client = \Mockery::mock( 'CloudFrontClient' );
		
		$mock_client->shouldReceive( 'getDistribution' )
			->andReturn( [
				'Distribution' => [
					'Id' => 'E123456789',
					'Status' => 'Deployed'
				]
			] );
		
		$mock_client->shouldReceive( 'createInvalidation' )
			->andReturn( [
				'Invalidation' => [
					'Id' => 'I123456789',
					'Status' => 'InProgress'
				]
			] );
		
		return $mock_client;
	}
	
	/**
	 * Create mock CloudFront client that fails with distribution not found
	 */
	public static function create_distribution_not_found_client() {
		$mock_client = \Mockery::mock( 'CloudFrontClient' );
		
		$mock_client->shouldReceive( 'getDistribution' )
			->andThrow( new \Exception( 'Distribution not found: NoSuchDistribution' ) );
		
		return $mock_client;
	}
	
	/**
	 * Create mock CloudFront client that fails with invalid credentials
	 */
	public static function create_invalid_credentials_client() {
		$mock_client = \Mockery::mock( 'CloudFrontClient' );
		
		$mock_client->shouldReceive( 'getDistribution' )
			->andThrow( new \Exception( 'Invalid credentials: InvalidClientTokenId' ) );
		
		return $mock_client;
	}
	
	/**
	 * Create mock AWS credentials
	 */
	public static function create_mock_credentials() {
		return array(
			'key' => 'test-key',
			'secret' => 'test-secret'
		);
	}

	public static function create_mock_imdsv2_token_success_response() {
		return array(
			'response' => array(
				'code' => 200,
			),
			'body' => 'AQAAANpzGgoAAAABAAZ1c2Utd2VzdC0yAKABOpguwwKSABVhc2lhbi1wYWNpZmljLXNvdXRoZWFzdC0xAKABOpguwwKSAAVhc2lhbi1wYWNpZmljLXNvdXRoZWFzdC0xAKABOpguwwKSAAVhc2lhbi1wYWNpZmljLXNvdXRoZWFzdC0x',
		);
	}

	public static function create_mock_imdsv2_token_failure_response() {
		return array(
			'response' => array(
				'code' => 401,
			),
			'body' => '',
		);
	}

	public static function create_mock_metadata_success_response() {
		return array(
			'response' => array(
				'code' => 200,
			),
			'body' => 'ami-id',
		);
	}

	public static function create_mock_metadata_failure_response( $code = 404 ) {
		return array(
			'response' => array(
				'code' => $code,
			),
			'body' => '',
		);
	}

	public static function create_mock_wp_error_response( $message = 'Connection timeout' ) {
		return new \WP_Error( 'http_request_failed', $message );
	}
}      