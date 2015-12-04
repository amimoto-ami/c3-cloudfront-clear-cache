<?php
class C3_Create_Invalidation_Url_test extends CloudFront_Clear_Cache_Test {

	function test_c3_make_args_if_no_post_params() {
		$res = $this->get_c3_make_args();

		$this->assertArrayHasKey( 'DistributionId', $res );
		$this->assertArrayHasKey( 'Paths', $res );
		$this->assertArrayHasKey( 'Quantity', $res['Paths'] );
		$this->assertArrayHasKey( 'Items', $res['Paths'] );
		$this->assertArrayHasKey( 'CallerReference', $res );
		$this->assertEquals( '/*' ,	$res['Paths']['Items'][0] );
		$this->assertCount( $res['Paths']['Quantity'], $res['Paths']['Items'] );
	}

	function get_c3_make_args( $posts = null ) {
		$c3_settings_param = array(
			'distribution_id' => 'SOME_DISTRIBUTION',
			'access_key'			=> 'SOME_ACCESS_KEY',
			'secret_key'			=> 'SOME_SECRET_KEY',
		);
		$reflection = new \ReflectionClass( $this->C3 );
		$method = $reflection->getMethod( 'c3_make_args' );
		$method->setAccessible( true );
		$res = $method->invoke( $this->C3 , $c3_settings_param, $posts );
		return $res;
	}

	function test_invalidation_post_url() {
		$respected_url = [ '/' , '/2007/10/31/3/*' ];
		$post_id = $this->test_create_posts();
		$res = $this->get_c3_make_args( $post_id );

		$this->assertArrayHasKey( 'DistributionId', $res );
		$this->assertArrayHasKey( 'Paths', $res );
		$this->assertArrayHasKey( 'Quantity', $res['Paths'] );
		$this->assertArrayHasKey( 'Items', $res['Paths'] );
		$this->assertArrayHasKey( 'CallerReference', $res );
		$this->assertEquals( $respected_url, $res['Paths']['Items'] );
		$this->assertCount( $res['Paths']['Quantity'], $res['Paths']['Items'] );
	}

	function test_create_posts( $status = 'publish' ) {
		parent::setUp();
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		$post = array(
			'post_author' => 1,
			'post_status' => $status,
			'post_content' => rand_str(),
			'post_title' => '',
			'post_date' => '2007-10-31 06:15:00',
		);
		return wp_insert_post( $post );
	}
}
