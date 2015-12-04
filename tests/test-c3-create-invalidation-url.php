<?php
class C3_Create_Invalidation_Url_test extends CloudFront_Clear_Cache_Test {

	function test_c3_make_args_if_no_post_params() {
		 $c3_settings_param =	array(
			'distribution_id' => 'SOME_DISTRIBUTION',
			'access_key'			=> 'SOME_ACCESS_KEY',
			'secret_key'			=> 'SOME_SECRET_KEY',
		);
		$res = $this->get_c3_make_args( $c3_settings_param );

		$this->assertArrayHasKey( 'DistributionId', $res );
		$this->assertArrayHasKey( 'Paths', $res );
		$this->assertArrayHasKey( 'Quantity', $res['Paths'] );
		$this->assertArrayHasKey( 'Items', $res['Paths'] );
		$this->assertArrayHasKey( 'CallerReference', $res );
		$this->assertEquals( '/*' ,	$res['Paths']['Items'][0] );
		$this->assertCount( $res['Paths']['Quantity'], $res['Paths']['Items'] );
	}

	function get_c3_make_args( $c3_settings_param, $posts = null ) {
		$reflection = new \ReflectionClass( $this->C3 );
		$method = $reflection->getMethod( 'c3_make_args' );
		$method->setAccessible( true );
		$res = $method->invoke( $this->C3 , $c3_settings_param, $posts );
		return $res;
	}
}
