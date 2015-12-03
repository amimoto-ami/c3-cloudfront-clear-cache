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

	function test_check_invalidation_status_unpublish_to_publish() {
		//should be true
		$res = $this->init_get_c3_is_invalidation( 'unpublish' , 'publish' );
		$this->assertTrue( $res );
	}

	function test_check_invalidation_status_publish_to_unpublish() {
		//should be true
		$res = $this->init_get_c3_is_invalidation( 'publish' , 'unpublish' );
		$this->assertTrue( $res );
	}

	function test_check_invalidation_status_unpublish_to_unpublish() {
		//should be false
		$res = $this->init_get_c3_is_invalidation( 'unpublish' , 'unpublish' );
		$this->assertFalse( $res );
	}

	function test_check_invalidation_status_() {
		//should be true
		$res = $this->init_get_c3_is_invalidation( 'publish' , 'publish' );
		$this->assertTrue( $res );
	}

	function test_check_invalidation_status_filter_custome_true() {
		add_filter( 'c3_is_invalidation' , array( $this, 'init_return_true' ) );
		$res = $this->init_get_c3_is_invalidation( 'unpublish' , 'unpublish' );
		$this->assertTrue( $res );
	}

	function test_check_invalidation_status_filter_custome_false() {
		add_filter( 'c3_is_invalidation' , array( $this, 'init_return_false' ) );
		$res = $this->init_get_c3_is_invalidation( 'publish' , 'publish' );
		$this->assertFalse( $res );
	}

	function init_get_c3_is_invalidation( $new_status, $old_status ) {
		$reflection = new \ReflectionClass( $this->C3 );
		$method = $reflection->getMethod( 'c3_is_invalidation' );
		$method->setAccessible( true );
		$res = $method->invoke( $this->C3 , $new_status, $old_status );
		return $res;
	}

	function init_return_true( $result = null ) {
		return true;
	}

	function init_return_false( $result = null ) {
		return false;
	}

}
