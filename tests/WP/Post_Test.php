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

    /**
     * @dataProvider provide_get_the_post_term_links_test_case
     */
    public function test_get_post_term_link( $post, $expected ) {
        $target = new WP\Post();
        $target->set_post( $post );
        $result = $target->get_the_post_term_links();
        $this->assertEquals( $expected, $result );
    }

    public function provide_get_the_post_term_links_test_case() {
        return [
            [
                $this->factory->post->create_and_get( array(
                    'post_status' => 'publish',
                    'post_name' => 'hello-world',
                ) ),
                []
            ],
        ];
    }
    
    /**
     * @dataProvider provide_parse_url_test_case
     */
    public function test_parse_url( $url, $expected ) {
        $post = new WP\Post();
        $result = $post->parse_url( $url );
        $this->assertEquals( $expected, $result );
    }

    public function provide_parse_url_test_case() {
        return [
            [ 'http://localhost', 'http://localhost' ],
            [ 'http://localhost:8888', 'http://localhost' ],
            [ 'http://example.com', 'http://example.com' ],
            [ 'http://example.com/', 'http://example.com/' ],
            [ 'http://example.com/?q=123', 'http://example.com/' ],
            [ 'http://example.com/hello-world', 'http://example.com/hello-world' ],
        ];
    }
}