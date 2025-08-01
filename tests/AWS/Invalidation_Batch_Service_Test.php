<?php
namespace C3_CloudFront_Cache_Controller\Test\AWS;
use C3_CloudFront_Cache_Controller\AWS;

class Invalidation_Batch_Service_Test extends \WP_UnitTestCase {
    /**
     * Sets up the fixture, for example, open a network connection.
     *
     * This method is called before each test.
     *
     * @return void
     */
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

    public function test_get_the_published_post_invalidation_paths() {
        $post = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'good-bye',
        ) );

        $target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_by_post( 'localhost', 'EXXX', $post );
        $this->assertEquals( array(
            'Items' => array(
                'localhost',
                '/good-bye/*',
            ),
            'Quantity' => 2
        ), $result[ 'InvalidationBatch' ][ 'Paths' ] );
    }

    public function test_overwrite_invalidation_item_by_post_name() {
        add_filter( 'c3_invalidation_items', function( $items, $post ) {
            if ( 'should-overwritten' === $post->post_name) {
                return ['/slug-overwritten'];
            }
            return $items;
        }, 10, 2 );
        
        // ケース1: 上書きされるべきケース
        $post1 = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'should-overwritten',
        ) );
        $target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_by_post( 'localhost', 'EXXX', $post1 );
        $this->assertEquals([
            'Items' => array(
                '/slug-overwritten',
            ),
            'Quantity' => 1
        ], $result[ 'InvalidationBatch' ][ 'Paths' ]);
        
        // ケース2: 上書きされないケース
        $post2 = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'should-not-overwritten',
        ) );
        $result = $target->create_batch_by_post( 'localhost', 'EXXX', $post2 );
        $this->assertEquals([
            'Items' => array(
                'localhost',
                '/should-not-overwritten/*',
            ),
            'Quantity' => 2
        ], $result[ 'InvalidationBatch' ][ 'Paths' ]);
    }

    public function test_get_the_un_published_post_invalidation_paths() {
        $post = $this->factory->post->create_and_get( array(
            'post_status' => 'trash',
            'post_name' => 'ohayou',
            'post_type' => 'post',
        ) );
        $target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_by_post( 'localhost', 'EXXX', $post );
        $this->assertEquals( array(
            'Items' => array(
                'localhost',
                '/ohayou/*',
            ),
            'Quantity' => 2
        ) , $result[ 'InvalidationBatch' ][ 'Paths' ] );
    }

    public function test_get_invalidation_path_for_all() {
        $target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_for_all( 'EXXXX' );
        $this->assertEquals( array(
            'Items' => array(
                '/*'
            ),
            'Quantity' => 1
        ) , $result[ 'InvalidationBatch' ][ 'Paths' ] );
    }

    public function test_create_batch_by_posts() {
        // ケース1: 1つの投稿
        $post1 = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'hello-world',
        ) );
        
        $target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_by_posts( 'localhost', 'EXXXX', [$post1] );
        $this->assertEquals([
            "Items" => [
                "localhost",
                "/hello-world/*"
            ],
            "Quantity" => 2
        ], $result[ 'InvalidationBatch' ][ 'Paths' ]);
        
        // ケース2: 複数の投稿
        $post2 = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'see-you',
        ) );
        $post3 = $this->factory->post->create_and_get( array(
            'post_status' => 'trash',
            'post_name' => 'good-bye',
        ) );
        
        $result = $target->create_batch_by_posts( 'localhost', 'EXXXX', [$post2, $post3] );
        $this->assertEquals([
            "Items" => [
                "localhost",
                "/see-you/*",
                "/good-bye/*"
            ],
            "Quantity" => 3
        ], $result[ 'InvalidationBatch' ][ 'Paths' ]);
    }

    public function test_c3_invalidation_post_batch_home_path_filter() {
        add_filter( 'c3_invalidation_post_batch_home_path', function( $home_path, $post ) {
            if ( $post && 'custom-home' === $post->post_name ) {
                return '/custom-homepage/';
            }
            return $home_path;
        }, 10, 2 );
        
        $post = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'custom-home',
        ) );
        
        $target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_by_post( 'localhost', 'EXXXX', $post );
        $this->assertEquals([
            'Items' => [
                '/custom-homepage/',
                '/custom-home/*'
            ],
            'Quantity' => 2
        ], $result[ 'InvalidationBatch' ][ 'Paths' ]);
    }

    public function test_c3_invalidation_posts_batch_home_path_filter() {
        add_filter( 'c3_invalidation_posts_batch_home_path', function( $home_path, $posts ) {
            if ( count( $posts ) > 1 ) {
                return '/bulk-update-homepage/';
            }
            return $home_path;
        }, 10, 2 );
        
        $post1 = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'post-one',
        ) );
        $post2 = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'post-two',
        ) );
        
        $target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_by_posts( 'localhost', 'EXXXX', [$post1, $post2] );
        $this->assertEquals([
            'Items' => [
                '/bulk-update-homepage/',
                '/post-one/*',
                '/post-two/*'
            ],
            'Quantity' => 3
        ], $result[ 'InvalidationBatch' ][ 'Paths' ]);
    }

    public function test_c3_invalidation_manual_batch_all_path_filter() {
        add_filter( 'c3_invalidation_manual_batch_all_path', function( $all_path ) {
            return '/custom-all-path/*';
        } );
        
        $target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_for_all( 'EXXXX' );
        $this->assertEquals([
            'Items' => [
                '/custom-all-path/*'
            ],
            'Quantity' => 1
        ], $result[ 'InvalidationBatch' ][ 'Paths' ]);
    }
}
