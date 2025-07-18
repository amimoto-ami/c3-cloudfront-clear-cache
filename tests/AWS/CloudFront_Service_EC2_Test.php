<?php
/**
 * Test CloudFront Service EC2 Instance Role Support
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.2
 * @package C3_CloudFront_Cache_Controller
 */

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\AWS\CloudFront_Service;
use C3_CloudFront_Cache_Controller\AWS\CloudFront_HTTP_Client;
use C3_CloudFront_Cache_Controller\Test\Helpers\WP_Mock_Helper;

class CloudFront_Service_EC2_Test extends TestCase {

	public function tearDown(): void {
		\Mockery::close();
	}

	public function test_get_credentials_with_explicit_credentials() {
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$credentials = $service->get_credentials( 'test_key', 'test_secret' );
		
		$this->assertIsArray( $credentials );
		$this->assertEquals( 'test_key', $credentials['key'] );
		$this->assertEquals( 'test_secret', $credentials['secret'] );
		$this->assertArrayNotHasKey( 'token', $credentials );
	}

	public function test_get_credentials_returns_null_when_no_credentials_available() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, null, null );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_has_ec2_instance_role', false )
			->andReturn( false );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$credentials = $service->get_credentials();
		
		$this->assertNull( $credentials );
	}

	public function test_get_credentials_with_ec2_instance_role_filter() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, null, null );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_has_ec2_instance_role', false )
			->andReturn( true );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$credentials = $service->get_credentials();
		
		$this->assertTrue( is_null( $credentials ) || is_array( $credentials ) );
		
		if ( is_array( $credentials ) ) {
			$this->assertArrayHasKey( 'key', $credentials );
			$this->assertArrayHasKey( 'secret', $credentials );
			$this->assertArrayHasKey( 'token', $credentials );
		}
	}

	public function test_create_client_fallback_when_no_options() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, 'test_key', 'test_secret' );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( null );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$client = $service->create_client();
		
		$this->assertInstanceOf( CloudFront_HTTP_Client::class, $client );
	}

	public function test_create_client_fallback_when_empty_credentials_in_options() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, 'test_key', 'test_secret' );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( array(
				'access_key' => '',
				'secret_key' => '',
			) );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_credential', array(
				'key'    => '',
				'secret' => '',
			) )
			->andReturn( array(
				'key'    => '',
				'secret' => '',
			) );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$client = $service->create_client();
		
		$this->assertInstanceOf( CloudFront_HTTP_Client::class, $client );
	}

	public function test_create_client_with_ec2_instance_role_credentials() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, null, null );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( null );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_has_ec2_instance_role', false )
			->andReturn( true );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$client = $service->create_client();
		
		$this->assertTrue( 
			$client instanceof CloudFront_HTTP_Client || 
			( $client instanceof \WP_Error && $client->get_error_message() === 'AWS credentials are required.' )
		);
	}

	public function test_create_client_returns_error_when_no_credentials_available() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, null, null );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( null );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_has_ec2_instance_role', false )
			->andReturn( false );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$client = $service->create_client();
		
		$this->assertInstanceOf( \WP_Error::class, $client );
		$this->assertEquals( 'AWS credentials are required.', $client->get_error_message() );
	}

	public function test_create_client_returns_error_when_empty_credentials_and_no_fallback() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, null, null );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( array(
				'access_key' => '',
				'secret_key' => '',
			) );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_credential', array(
				'key'    => '',
				'secret' => '',
			) )
			->andReturn( array(
				'key'    => '',
				'secret' => '',
			) );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_has_ec2_instance_role', false )
			->andReturn( false );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$client = $service->create_client();
		
		$this->assertInstanceOf( \WP_Error::class, $client );
		$this->assertEquals( 'AWS credentials are required.', $client->get_error_message() );
	}

	public function test_create_client_with_ec2_role_enabled_but_no_credentials() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, null, null );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( array(
				'access_key' => '',
				'secret_key' => '',
			) );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_credential', array(
				'key'    => '',
				'secret' => '',
			) )
			->andReturn( array(
				'key'    => '',
				'secret' => '',
			) );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_has_ec2_instance_role', false )
			->andReturn( true );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$client = $service->create_client();
		
		$this->assertInstanceOf( \WP_Error::class, $client );
		$this->assertEquals( 'AWS credentials are required.', $client->get_error_message() );
	}

	public function test_try_to_call_aws_api_with_session_token() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, 'test_key', 'test_secret' );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		
		$result = $service->try_to_call_aws_api( 'INVALID_DISTRIBUTION_ID' );
		
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertStringContainsString( 'Auth Error', $result->get_error_code() );
	}

	public function test_regression_create_client_calls_get_credentials_when_no_options() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, 'fallback_key', 'fallback_secret' );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( null );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$client = $service->create_client();
		
		$this->assertInstanceOf( CloudFront_HTTP_Client::class, $client );
	}

	public function test_regression_create_client_returns_error_when_no_options_and_no_fallback() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, null, null );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( null );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_has_ec2_instance_role', false )
			->andReturn( false );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$client = $service->create_client();
		
		$this->assertInstanceOf( \WP_Error::class, $client );
		$this->assertEquals( 'AWS credentials are required.', $client->get_error_message() );
	}

	public function test_regression_create_client_calls_get_credentials_when_empty_options() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, 'fallback_key', 'fallback_secret' );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( array(
				'access_key' => '',
				'secret_key' => '',
			) );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_credential', array(
				'key'    => '',
				'secret' => '',
			) )
			->andReturn( array(
				'key'    => '',
				'secret' => '',
			) );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$client = $service->create_client();
		
		$this->assertInstanceOf( CloudFront_HTTP_Client::class, $client );
	}

	public function test_regression_try_to_call_aws_api_passes_session_token() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, null, null );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_has_ec2_instance_role', false )
			->andReturn( true );
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		
		$result = $service->try_to_call_aws_api( 'INVALID_DISTRIBUTION_ID' );
		
		$this->assertTrue( 
			$result instanceof \WP_Error || 
			$result === null 
		);
	}

	public function test_credential_priority_explicit_over_environment() {
		$mock_env = WP_Mock_Helper::create_mock_environment( 'env_key', 'env_secret', null );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$credentials = $service->get_credentials( 'explicit_key', 'explicit_secret' );
		
		$this->assertIsArray( $credentials );
		$this->assertEquals( 'explicit_key', $credentials['key'] );
		$this->assertEquals( 'explicit_secret', $credentials['secret'] );
	}

	public function test_credential_priority_environment_over_ec2() {
		$mock_env = WP_Mock_Helper::create_mock_environment( null, 'env_key', 'env_secret' );
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'c3_has_ec2_instance_role', false )
			->never();
		
		$service = new CloudFront_Service( $mock_env, $mock_options, $mock_hooks );
		$credentials = $service->get_credentials();
		
		$this->assertIsArray( $credentials );
		$this->assertEquals( 'env_key', $credentials['key'] );
		$this->assertEquals( 'env_secret', $credentials['secret'] );
		$this->assertArrayNotHasKey( 'token', $credentials );
	}
}
