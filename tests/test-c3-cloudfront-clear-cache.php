<?php
require_once( 'c3-cloudfront-clear-cache.php' );
class CloudFront_Clear_Cache_Test extends WP_UnitTestCase
{
	protected $C3;
	function setUp() {
		$this->C3 = C3_Controller::get_instance();
		$this->C3->init();
	}

	function test_check_phpversion() {
		$result = c3_is_later_than_php_55();
		$this->assertFalse( $result );
		/*
		if ( 5.5 > (float) phpversion() ) {
			$this->assertFalse( $result );
		} else {
			$this->assertTrue( $result );
		}
		*/
	}

	function test_is_load_aws_sdk_version() {
		// PHP5.5 or later should be load AWS SDK Version3
		$result = defined('AWS-2.8.22.PHAR_PHAR');
		$this->assertTrue( $result );
		/*
		if ( 5.5 > (float) phpversion() ) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}
		*/
	}

}
