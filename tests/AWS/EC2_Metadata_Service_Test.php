<?php
/**
 * Test EC2 Metadata Service
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.2
 * @package C3_CloudFront_Cache_Controller
 */

use C3_CloudFront_Cache_Controller\AWS\EC2_Metadata_Service;

class EC2_Metadata_Service_Test extends \WP_UnitTestCase {

	public function test_metadata_service_instantiation() {
		$service = new EC2_Metadata_Service();
		$this->assertInstanceOf( EC2_Metadata_Service::class, $service );
	}

	public function test_is_ec2_instance_returns_boolean() {
		$service = new EC2_Metadata_Service();
		$result = $service->is_ec2_instance();
		$this->assertIsBool( $result );
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
