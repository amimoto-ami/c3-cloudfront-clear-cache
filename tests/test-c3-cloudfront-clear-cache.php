<?php
require_once( 'c3-cloudfront-clear-cache.php' );
class CloudFront_Clear_Cache_Test extends WP_UnitTestCase
{
	private $C3;
	const VERSION = '2.1.0';
	function __construct() {
		$this->C3 = CloudFront_Clear_Cache::get_instance();
		$this->C3->add_hook();
	}

	function test_load_text_domain() {
		$text_domain = $this->C3->text_domain();
		$this->assertEquals( 'c3_cloudfront_clear_cache' , $text_domain );
	}

	function test_load_current_version() {
		$version = $this->C3->version();
		$this->assertEquals( self::VERSION , $version );
	}

	function test_if_c3_settings_is_no_params() {
		$reflection = new \ReflectionClass( $this->C3 );
		$method = $reflection->getMethod( 'c3_get_settings' );
		$method->setAccessible( true );
		$res = $method->invoke( $this->C3 );
		$this->assertFalse( $res );
	}

}
