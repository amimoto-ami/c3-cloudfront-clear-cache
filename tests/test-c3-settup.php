<?php
class C3_Settup_test extends CloudFront_Clear_Cache_Test {

	function test_load_text_domain() {
		$text_domain = $this->C3->text_domain();
		$this->assertEquals( 'c3_cloudfront_clear_cache' , $text_domain );
	}

	function test_load_current_version() {
		$version = $this->C3->version();
		$this->assertEquals( self::VERSION , $version );
	}

	function test_if_c3_settings_is_no_settings() {
		$res = $this->get_c3_settings();
		$this->assertFalse( $res );
	}

}
