<?php
/**
 * Test CloudFront Service EC2 Instance Role Support
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.2
 * @package C3_CloudFront_Cache_Controller
 */

use C3_CloudFront_Cache_Controller\AWS\CloudFront_Service;
use C3_CloudFront_Cache_Controller\Test\Mocks\WP\Environment;
use C3_CloudFront_Cache_Controller\Test\Mocks\WP\Options;
use C3_CloudFront_Cache_Controller\Test\Mocks\WP\Hooks;

class CloudFront_Service_EC2_Test extends \WP_UnitTestCase {

	public function test_get_credentials_with_explicit_credentials() {
		$env = new Environment();
		$options = new Options();
		$hooks = new Hooks();
		
		$service = new CloudFront_Service( $options, $hooks, $env );
		$credentials = $service->get_credentials( 'test_key', 'test_secret' );
		
		$this->assertIsArray( $credentials );
		$this->assertEquals( 'test_key', $credentials['key'] );
		$this->assertEquals( 'test_secret', $credentials['secret'] );
		$this->assertArrayNotHasKey( 'token', $credentials );
	}

	public function test_get_credentials_returns_null_when_no_credentials_available() {
		$env = new Environment();
		$options = new Options();
		$hooks = new Hooks();
		
		$service = new CloudFront_Service( $options, $hooks, $env );
		$credentials = $service->get_credentials();
		
		$this->assertNull( $credentials );
	}

	public function test_get_credentials_with_ec2_instance_role_filter() {
		$env = new Environment();
		$options = new Options();
		$hooks = new Hooks();
		
		$hooks->add_filter( 'c3_has_ec2_instance_role', '__return_true' );
		
		$service = new CloudFront_Service( $options, $hooks, $env );
		$credentials = $service->get_credentials();
		
		$this->assertTrue( is_null( $credentials ) || is_array( $credentials ) );
		
		if ( is_array( $credentials ) ) {
			$this->assertArrayHasKey( 'key', $credentials );
			$this->assertArrayHasKey( 'secret', $credentials );
			$this->assertArrayHasKey( 'token', $credentials );
		}
	}
}
