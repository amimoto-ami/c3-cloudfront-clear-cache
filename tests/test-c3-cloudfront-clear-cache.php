<?php
require_once( 'c3-cloudfront-clear-cache.php' );
class CloudFront_Clear_Cache_Test extends WP_UnitTestCase
{
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

	function test_should_return_boolean_when_call_c3_is_later_than_php_55() {
		$version = c3_is_later_than_php_55();
		$this->assertInternalType( 'bool', $version);
	}

	function test_should_return_true_when_call_c3_is_later_than_php_55_with_filter() {
		add_filter( 'c3_select_aws_sdk', function() {
			return true;
		} );
		$version = c3_is_later_than_php_55();
		$this->assertSame( true, $version);
	}

	function test_should_return_false_when_call_c3_is_later_than_php_55_with_filter() {
		add_filter( 'c3_select_aws_sdk', function($bool) {
			return false;
		} );
		$version = c3_is_later_than_php_55();
		$this->assertSame( false, $version);
	}

}
