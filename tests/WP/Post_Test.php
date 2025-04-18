<?php
namespace C3_CloudFront_Cache_Controller\Test\WP;
use C3_CloudFront_Cache_Controller\WP;

class Post_Test extends \WP_UnitTestCase {
    protected function setUp(): void {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

        parent::setUp();

        /**
         * Change the permalink structure
         */
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
    }

    public function test_get_post_term_link() {
        $post = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'hello-world',
        ) );
        
        $target = new WP\Post();
        $target->set_post( $post );
        $result = $target->get_the_post_term_links();
        $this->assertEquals( [], $result );
    }
    
    public function test_parse_url() {
        $post = new WP\Post();
        
        // ケース1
        $result = $post->parse_url( 'http://localhost' );
        $this->assertEquals( 'http://localhost', $result );
        
        // ケース2
        $result = $post->parse_url( 'http://localhost:8888' );
        $this->assertEquals( 'http://localhost', $result );
        
        // ケース3
        $result = $post->parse_url( 'http://example.com' );
        $this->assertEquals( 'http://example.com', $result );
        
        // ケース4
        $result = $post->parse_url( 'http://example.com/' );
        $this->assertEquals( 'http://example.com/', $result );
        
        // ケース5
        $result = $post->parse_url( 'http://example.com/?q=123' );
        $this->assertEquals( 'http://example.com/', $result );
        
        // ケース6
        $result = $post->parse_url( 'http://example.com/hello-world' );
        $this->assertEquals( 'http://example.com/hello-world', $result );
    }
}