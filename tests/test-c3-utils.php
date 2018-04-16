<?php
require_once( 'module/utils.php' );
class C3_Utils_Test extends WP_UnitTestCase {
	/**
	 * @test
	 */
	function test_should_return_v3_when_defined_AWS_v3_const() {
		$constants = array(
			'user' => array(
				'AWS-3.x.x.PHAR_PHAR' => true
			)
		);
		$version = c3_check_aws_sdk_version($constants);
		$this->assertSame( 'v3', $version );
	}

	/**
	 * @test
	 */
	function test_should_return_v2_when_defined_AWS_v2_const() {
		$constants = array(
			'user' => array(
				'AWS-2.x.x.PHAR_PHAR' => true
			)
		);
		$version = c3_check_aws_sdk_version($constants);
		$this->assertSame( 'v2', $version );
	}
}