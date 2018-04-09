<?php
require_once( 'c3-cloudfront-clear-cache.php' );
class CloudFront_Clear_Cache_Test extends WP_UnitTestCase
{
	protected $C3;
	function setUp() {
		$this->C3 = C3_Controller::get_instance();
		$this->C3->init();
	}

	function test_should_defined_c3_plugin_path() {
		$result = defined('C3_PLUGIN_PATH');
		$this->assertTrue( $result );
	}
	function test_should_defined_c3_plugin_url() {
		$result = defined('C3_PLUGIN_URL');
		$this->assertTrue( $result );
	}
	function test_should_defined_c3_plugin_root() {
		$result = defined('C3_PLUGIN_ROOT');
		$this->assertTrue( $result );
	}

}
