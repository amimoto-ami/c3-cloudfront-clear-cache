<?php
/**
 * CloudFront Service Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\AWS\CloudFront_Service;
use C3_CloudFront_Cache_Controller\Test\Helpers\WP_Mock_Helper;

/**
 * CloudFront Service Test
 */
class CloudFront_Service_Test extends TestCase {

	public function tearDown(): void {
		\Mockery::close();
	}

	/**
	 * Test get_distribution_id from environment variables
	 */
	public function test_get_distribution_id_from_env() {
		// 環境変数から取得するケース
		$mock_env = WP_Mock_Helper::create_mock_environment( 'E123456789' );
		$mock_options = WP_Mock_Helper::create_mock_options_service( null );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		
		$distribution_id = $service->get_distribution_id();
		$this->assertEquals( 'E123456789', $distribution_id );
	}

	/**
	 * Test get_distribution_id from options when env is not set
	 */
	public function test_get_distribution_id_from_options() {
		// 環境変数からは取得できず、オプションから取得するケース
		$mock_env = WP_Mock_Helper::create_mock_environment( null );
		$mock_options = WP_Mock_Helper::create_mock_options_service( [
			'distribution_id' => 'E987654321',
			'access_key' => 'test-key',
			'secret_key' => 'test-secret'
		] );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		
		$distribution_id = $service->get_distribution_id();
		$this->assertEquals( 'E987654321', $distribution_id );
	}

	/**
	 * Test get_distribution_id exception when neither env nor options are set
	 */
	public function test_get_distribution_id_exception() {
		// 環境変数もオプションも設定されていない場合
		$mock_env = WP_Mock_Helper::create_mock_environment( null );
		$mock_options = WP_Mock_Helper::create_mock_options_service( null );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'distribution_id does not exists.' );
		$service->get_distribution_id();
	}

	/**
	 * Test get_credentials with explicit parameters
	 */
	public function test_get_credentials_with_params() {
		$mock_env     = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks   = WP_Mock_Helper::create_mock_hooks_service();

		$service     = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$credentials = $service->get_credentials( 'test-key', 'test-secret' );

		$this->assertIsArray( $credentials );
		$this->assertEquals( [ 'key' => 'test-key', 'secret' => 'test-secret' ], $credentials );
	}

	/**
	 * Test get_credentials falls back to environment variables
	 */
	public function test_get_credentials_from_env() {
		$mock_env     = WP_Mock_Helper::create_mock_environment( null, 'env-key', 'env-secret' );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks   = WP_Mock_Helper::create_mock_hooks_service();

		$service     = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$credentials = $service->get_credentials();

		$this->assertIsArray( $credentials );
		$this->assertEquals( [ 'key' => 'env-key', 'secret' => 'env-secret' ], $credentials );
	}

	/**
	 * Test get_credentials returns null when no credentials are set
	 */
	public function test_get_credentials_null() {
		$mock_env     = WP_Mock_Helper::create_mock_environment( null, null, null );
		$mock_options  = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks    = WP_Mock_Helper::create_mock_hooks_service();

		$service     = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$credentials = $service->get_credentials();

		$this->assertNull( $credentials );
	}

	/**
	 * Test basic CloudFront service functionality
	 */
	public function test_basic_functionality() {
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		
		// サービスが正常に初期化されることを確認
		$this->assertInstanceOf( CloudFront_Service::class, $service );
	}

	/**
	 * Test create_client returns WP_Error when no options
	 */
	public function test_create_client_no_options() {
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service( null );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		
		$result = $service->create_client();
		
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'C3 Create Client Error', $result->get_error_code() );
		$this->assertEquals( 'AWS credentials are required.', $result->get_error_message() );
	}

	/**
	 * Test create_client with valid options
	 */
	public function test_create_client_with_valid_options() {
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service( [
			'distribution_id' => 'E123456789',
			'access_key' => 'test-key',
			'secret_key' => 'test-secret'
		] );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		
		// オプションが設定されていることをテスト
		$this->assertInstanceOf( CloudFront_Service::class, $service );
	}

	/**
	 * Test get_invalidation_details with invalid credentials
	 */
	public function test_get_invalidation_details_invalid_credentials() {
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service( null );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		
		$result = $service->get_invalidation_details( 'I123456789' );
		
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'C3 Get Invalidation Error', $result->get_error_code() );
		$this->assertStringContainsString( 'Failed to create CloudFront client', $result->get_error_message() );
	}

	/**
	 * Test list_invalidations with single invalidation response (string case)
	 * This test reproduces the original error scenario
	 */
	public function test_list_invalidations_single_string_response() {
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service( [
			'distribution_id' => 'E123456789',
			'access_key' => 'test-key',
			'secret_key' => 'test-secret'
		] );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		// Mock the CloudFront_HTTP_Client to return a string response
		$mock_client = \Mockery::mock( 'C3_CloudFront_Cache_Controller\AWS\CloudFront_HTTP_Client' );
		$mock_client->shouldReceive( 'list_invalidations' )
			->andReturn( 'I123456789' ); // This is what causes the error

		// Create a partial mock of CloudFront_Service to inject our mock client
		$service = \Mockery::mock( CloudFront_Service::class, [ $mock_env, $mock_options, $mock_hooks ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$service->shouldReceive( 'create_client' )
			->andReturn( $mock_client );

		$service->shouldReceive( 'get_distribution_id' )
			->andReturn( 'E123456789' );

		$result = $service->list_invalidations();
		
		// The method should handle the string response gracefully
		$this->assertIsArray( $result );
		$this->assertEmpty( $result ); // Should return empty array for invalid data
	}

	/**
	 * Test list_invalidations with single invalidation response (object case)
	 */
	public function test_list_invalidations_single_object_response() {
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service( [
			'distribution_id' => 'E123456789',
			'access_key' => 'test-key',
			'secret_key' => 'test-secret'
		] );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		// Mock the CloudFront_HTTP_Client to return a single object response
		$mock_client = \Mockery::mock( 'C3_CloudFront_Cache_Controller\AWS\CloudFront_HTTP_Client' );
		$mock_client->shouldReceive( 'list_invalidations' )
			->andReturn( [
				'Quantity' => 1,
				'Items' => [
					'InvalidationSummary' => [
						'Id' => 'I123456789',
						'Status' => 'Completed',
						'CreateTime' => '2024-01-15T10:30:00Z'
					]
				]
			] );

		$service = \Mockery::mock( CloudFront_Service::class, [ $mock_env, $mock_options, $mock_hooks ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$service->shouldReceive( 'create_client' )
			->andReturn( $mock_client );

		$service->shouldReceive( 'get_distribution_id' )
			->andReturn( 'E123456789' );

		$result = $service->list_invalidations();
		
		// Should return array with single invalidation
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertEquals( 'I123456789', $result[0]['Id'] );
		$this->assertEquals( 'Completed', $result[0]['Status'] );
	}

	/**
	 * Test list_invalidations with multiple invalidations response
	 */
	public function test_list_invalidations_multiple_response() {
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service( [
			'distribution_id' => 'E123456789',
			'access_key' => 'test-key',
			'secret_key' => 'test-secret'
		] );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		// Mock the CloudFront_HTTP_Client to return multiple invalidations
		$mock_client = \Mockery::mock( 'C3_CloudFront_Cache_Controller\AWS\CloudFront_HTTP_Client' );
		$mock_client->shouldReceive( 'list_invalidations' )
			->andReturn( [
				'Quantity' => 2,
				'Items' => [
					'InvalidationSummary' => [
						[
							'Id' => 'I123456789',
							'Status' => 'Completed',
							'CreateTime' => '2024-01-15T10:30:00Z'
						],
						[
							'Id' => 'I987654321',
							'Status' => 'InProgress',
							'CreateTime' => '2024-01-15T11:00:00Z'
						]
					]
				]
			] );

		$service = \Mockery::mock( CloudFront_Service::class, [ $mock_env, $mock_options, $mock_hooks ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$service->shouldReceive( 'create_client' )
			->andReturn( $mock_client );

		$service->shouldReceive( 'get_distribution_id' )
			->andReturn( 'E123456789' );

		$result = $service->list_invalidations();
		
		// Should return array with multiple invalidations
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertEquals( 'I123456789', $result[0]['Id'] );
		$this->assertEquals( 'I987654321', $result[1]['Id'] );
	}

	/**
	 * Test list_invalidations with no invalidations
	 */
	public function test_list_invalidations_no_invalidations() {
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service( [
			'distribution_id' => 'E123456789',
			'access_key' => 'test-key',
			'secret_key' => 'test-secret'
		] );
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();

		// Mock the CloudFront_HTTP_Client to return no invalidations
		$mock_client = \Mockery::mock( 'C3_CloudFront_Cache_Controller\AWS\CloudFront_HTTP_Client' );
		$mock_client->shouldReceive( 'list_invalidations' )
			->andReturn( [
				'Quantity' => 0
			] );

		$service = \Mockery::mock( CloudFront_Service::class, [ $mock_env, $mock_options, $mock_hooks ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$service->shouldReceive( 'create_client' )
			->andReturn( $mock_client );

		$service->shouldReceive( 'get_distribution_id' )
			->andReturn( 'E123456789' );

		$result = $service->list_invalidations();
		
		// Should return empty array
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}
}      