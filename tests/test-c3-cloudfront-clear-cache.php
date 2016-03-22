<?php
require_once( 'c3-cloudfront-clear-cache.php' );
class CloudFront_Clear_Cache_Test extends WP_UnitTestCase
{
	protected $C3;
	const VERSION = '2.4.0';
	function __construct() {
		$this->C3 = CloudFront_Clear_Cache::get_instance();
		$this->C3->add_hook();
	}

	function test_if_c3_settings_is_no_params() {
		$param =  array(
			'distribution_id' => '',
			'access_key'      => '',
			'secret_key'      => '',
		);
		update_option( 'c3_settings', $param );
		$res = $this->get_c3_settings();
		$this->assertFalse( $res );
	}

	function test_if_c3_settings_is_has_all_params() {
		$param =  array(
			'distribution_id' => 'SOME_DISTRIBUTION',
			'access_key'      => 'SOME_ACCESS_KEY',
			'secret_key'      => 'SOME_SECRET_KEY',
		);
		update_option( 'c3_settings', $param );
		$res = $this->get_c3_settings();
		$this->assertArrayHasKey( 'distribution_id' , $res );
		$this->assertArrayHasKey( 'access_key' , $res );
		$this->assertArrayHasKey( 'secret_key' , $res );
	}

	function test_if_c3_settings_is_has_lost_distribution_id_param() {
		$param =  array(
			'distribution_id' => '',
			'access_key'      => 'SOME_ACCESS_KEY',
			'secret_key'      => 'SOME_SECRET_KEY',
		);
		update_option( 'c3_settings', $param );
		$res = $this->get_c3_settings();
		$this->assertFalse( $res );
	}

	function test_if_c3_settings_is_has_lost_access_key_param() {
		$param =  array(
			'distribution_id' => 'SOME_DISTRIBUTION',
			'access_key'      => '',
			'secret_key'      => 'SOME_SECRET_KEY',
		);
		update_option( 'c3_settings', $param );
		$res = $this->get_c3_settings();
		$this->assertFalse( $res );
	}

	function test_if_c3_settings_is_has_lost_secret_key_param() {
		$param =  array(
			'distribution_id' => 'SOME_DISTRIBUTION',
			'access_key'      => 'SOME_ACCESS_KEY',
			'secret_key'      => '',
		);
		update_option( 'c3_settings', $param );
		$res = $this->get_c3_settings();
		$this->assertFalse( $res );
	}

	function test_check_invalidation_status_unpublish_to_publish() {
		//should be true
		$res = $this->get_c3_is_invalidation( 'unpublish' , 'publish' );
		$this->assertTrue( $res );
	}

	function test_check_invalidation_status_publish_to_unpublish() {
		//should be true
		$res = $this->get_c3_is_invalidation( 'publish' , 'unpublish' );
		$this->assertTrue( $res );
	}

	function test_check_invalidation_status_unpublish_to_unpublish() {
		//should be false
		$res = $this->get_c3_is_invalidation( 'unpublish' , 'unpublish' );
		$this->assertFalse( $res );
	}

	function test_check_invalidation_status_() {
		//should be true
		$res = $this->get_c3_is_invalidation( 'publish' , 'publish' );
		$this->assertTrue( $res );
	}

	function test_check_invalidation_status_filter_custome_true() {
		add_filter( 'c3_is_invalidation' , array( $this, 'init_return_true' ) );
		$res = $this->get_c3_is_invalidation( 'unpublish' , 'unpublish' );
		$this->assertTrue( $res );
	}

	function test_check_invalidation_status_filter_custome_false() {
		add_filter( 'c3_is_invalidation' , array( $this, 'init_return_false' ) );
		$res = $this->get_c3_is_invalidation( 'publish' , 'publish' );
		$this->assertFalse( $res );
	}

	function get_c3_settings() {
		$reflection = new \ReflectionClass( $this->C3 );
		$method = $reflection->getMethod( 'c3_get_settings' );
		$method->setAccessible( true );
		$res = $method->invoke( $this->C3 );
		return $res;
	}

	function get_c3_is_invalidation( $new_status, $old_status ) {
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
