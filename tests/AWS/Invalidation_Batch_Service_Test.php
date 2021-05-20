<?php
namespace C3_CloudFront_Cache_Controller\Test\AWS;
use C3_CloudFront_Cache_Controller\AWS;

class Invalidation_Batch_Service_Test extends \WP_UnitTestCase {
    private $cat_id = 1;
    private $category;
    public function setUp() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

        parent::setUp();

        /**
         * Change the permalink structure
         */
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules();

        $category = get_category( 1 );
        if ( ! isset( $category ) ) {
            $this->factory->category->create_and_get( array(
            ) );
        }

    }

    public function test_get_the_published_post_invalidation_paths() {
        $post = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'hello-world',
        ) );

		$target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_by_post( 'localhost', 'EXXX', $post );
        $this->assertEquals( $result[ 'InvalidationBatch' ][ 'Paths'], array(
            'Items' => array(
                'localhost',
                '/hello-world/*',
                '/'
            ),
            'Quantity' => 3
        ) );
    }
/*
    public function test_get_the_un_published_post_invalidation_paths() {
        $post = $this->factory->post->create_and_get( array(
            'post_status' => 'trash',
            'post_name' => 'hello-world',
            'post_type' => 'post',
        ) );
		$target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_by_post( 'localhost', 'EXXX', $post );
        $this->assertEquals( $result[ 'InvalidationBatch' ][ 'Paths'], array(
            'Items' => array(
                'localhost',
                '/hello-world/*',
                '/'
            ),
            'Quantity' => 3
        ) );
    }
*/
    
}