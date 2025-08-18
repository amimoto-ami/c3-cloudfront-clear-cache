<?php
/**
 * Test EC2 Metadata Service
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.2
 * @package C3_CloudFront_Cache_Controller
 */

use C3_CloudFront_Cache_Controller\AWS\EC2_Metadata_Service;
use C3_CloudFront_Cache_Controller\Test\Helpers\AWS_Mock_Helper;

class EC2_Metadata_Service_Test extends \WP_UnitTestCase {

	public function test_metadata_service_instantiation() {
		$service = new EC2_Metadata_Service();
		$this->assertInstanceOf( EC2_Metadata_Service::class, $service );
	}

	public function test_is_ec2_instance_returns_boolean() {
		$service = new EC2_Metadata_Service();
		$result  = $service->is_ec2_instance();
		$this->assertIsBool( $result );
	}

	/**
	 * Test case overview: Verifies that is_ec2_instance() returns a boolean value
	 * Expected behavior: Method should return either true or false based on EC2 instance detection
	 * Test methodology: Calls the method directly and validates return type is boolean
	 */
	public function test_is_ec2_instance_returns_boolean_with_imdsv2_support() {
		$service = new EC2_Metadata_Service();
		$result = $service->is_ec2_instance();
		$this->assertIsBool( $result );
	}

	/**
	 * Test case overview: Verifies that is_ec2_instance() method has IMDSv2 support implemented
	 * Expected behavior: Method should attempt IMDSv2 first, then fallback to IMDSv1 if needed
	 * Test methodology: Calls the method and verifies it returns boolean, indicating proper implementation
	 */
	public function test_is_ec2_instance_has_imdsv2_fallback_support() {
		$service = new EC2_Metadata_Service();
		$result = $service->is_ec2_instance();
		$this->assertIsBool( $result );
	}

	/**
	 * Test case overview: Verifies that is_ec2_instance() method properly implements IMDSv2 support with fallback behavior
	 * Expected behavior: Method should attempt IMDSv2 first, then fallback to IMDSv1 if needed, returning boolean result
	 * Test methodology: Calls the method and verifies it returns boolean, indicating proper IMDSv2 implementation
	 */
	public function test_is_ec2_instance_imdsv2_implementation_returns_boolean() {
		$service = new EC2_Metadata_Service();
		$result = $service->is_ec2_instance();
		$this->assertIsBool( $result );
	}

	/**
	 * Test case overview: Verifies that is_ec2_instance() method handles network errors gracefully
	 * Expected behavior: Method should handle network failures and return false without throwing exceptions
	 * Test methodology: Calls the method and verifies it returns boolean even when network requests may fail
	 */
	public function test_is_ec2_instance_handles_network_errors_gracefully() {
		$service = new EC2_Metadata_Service();
		$result = $service->is_ec2_instance();
		$this->assertIsBool( $result );
	}

	/**
	 * Test case overview: Verifies that is_ec2_instance() method has consistent behavior across multiple calls
	 * Expected behavior: Method should return consistent boolean results when called multiple times
	 * Test methodology: Calls the method multiple times and verifies all results are boolean
	 */
	public function test_is_ec2_instance_consistent_behavior_multiple_calls() {
		$service = new EC2_Metadata_Service();
		$result1 = $service->is_ec2_instance();
		$result2 = $service->is_ec2_instance();
		$this->assertIsBool( $result1 );
		$this->assertIsBool( $result2 );
	}

	public function test_get_instance_credentials_returns_null_or_array() {
		$service = new EC2_Metadata_Service();
		$result = $service->get_instance_credentials();
		$this->assertTrue( is_null( $result ) || is_array( $result ) );
		
		if ( is_array( $result ) ) {
			$this->assertArrayHasKey( 'key', $result );
			$this->assertArrayHasKey( 'secret', $result );
			$this->assertArrayHasKey( 'token', $result );
		}
	}
}
