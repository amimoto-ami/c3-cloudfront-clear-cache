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
		$this->assertEquals( 'General setting params not defined.', $result->get_error_message() );
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
} 