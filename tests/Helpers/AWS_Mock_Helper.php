<?php
/**
 * AWS SDK Mock Helper for testing
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test\Helpers;

use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;

/**
 * AWS Mock Helper class
 */
class AWS_Mock_Helper {
	
	/**
	 * Create mock CloudFront client that succeeds
	 */
	public static function create_successful_cloudfront_client() {
		$mock_client = \Mockery::mock( CloudFrontClient::class );
		
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
		$mock_client = \Mockery::mock( CloudFrontClient::class );
		
		$mock_client->shouldReceive( 'getDistribution' )
			->andThrow( new AwsException( 'Distribution not found', 
				\Mockery::mock( \Aws\CommandInterface::class ), 
				[ 'code' => 'NoSuchDistribution' ] ) );
		
		return $mock_client;
	}
	
	/**
	 * Create mock CloudFront client that fails with invalid credentials
	 */
	public static function create_invalid_credentials_client() {
		$mock_client = \Mockery::mock( CloudFrontClient::class );
		
		$mock_client->shouldReceive( 'getDistribution' )
			->andThrow( new AwsException( 'Invalid credentials', 
				\Mockery::mock( \Aws\CommandInterface::class ), 
				[ 'code' => 'InvalidClientTokenId' ] ) );
		
		return $mock_client;
	}
	
	/**
	 * Create mock AWS credentials
	 */
	public static function create_mock_credentials() {
		return new Credentials( 'test-key', 'test-secret' );
	}
} 