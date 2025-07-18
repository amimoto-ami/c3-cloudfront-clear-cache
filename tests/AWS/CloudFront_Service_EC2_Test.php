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
}
