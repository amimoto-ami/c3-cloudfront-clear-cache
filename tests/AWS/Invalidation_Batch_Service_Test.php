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

    /**
     * Clean up after each test
     */
    protected function tearDown(): void {
        parent::tearDown();
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

    /**
     * Test Case: c3_invalidation_post_batch_home_path filter hook functionality
     * 
     * Overview:
     * This test verifies that the c3_invalidation_post_batch_home_path filter hook
     * correctly allows customization of the home path during single post invalidation.
     * 
     * Expected Behavior:
     * - The filter should receive the original home path and post object as parameters
     * - When the post slug matches 'custom-home', the filter should return '/custom-homepage/'
     * - The invalidation batch should contain both the custom home path and the post-specific path
     * - Other posts should not be affected by this filter condition
     * 
     * Test Method:
     * 1. Register a filter that modifies home path for posts with slug 'custom-home'
     * 2. Create a test post with the matching slug 'custom-home'
     * 3. Call create_batch_by_post() method with the test post
     * 4. Verify the resulting invalidation paths contain the custom home path '/custom-homepage/'
     * 5. Confirm the post-specific path '/custom-home/*' is also included
     * 6. Assert the total quantity of paths is 2
     */
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

    /**
     * Test Case: c3_invalidation_posts_batch_home_path filter hook functionality
     * 
     * Overview:
     * This test verifies that the c3_invalidation_posts_batch_home_path filter hook
     * correctly allows customization of the home path during multiple posts invalidation.
     * 
     * Expected Behavior:
     * - The filter should receive the original home path and array of post objects as parameters
     * - When more than 1 post is being processed, the filter should return '/bulk-update-homepage/'
     * - The invalidation batch should contain the custom home path plus individual post paths
     * - Single post batches should not trigger this filter condition
     * 
     * Test Method:
     * 1. Register a filter that modifies home path when processing multiple posts (count > 1)
     * 2. Create two test posts with different slugs ('post-one' and 'post-two')
     * 3. Call create_batch_by_posts() method with both posts in an array
     * 4. Verify the resulting invalidation paths contain the custom bulk home path '/bulk-update-homepage/'
     * 5. Confirm both individual post paths '/post-one/*' and '/post-two/*' are included
     * 6. Assert the total quantity of paths is 3 (1 custom home + 2 post paths)
     */
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

    /**
     * Test Case: c3_invalidation_manual_batch_all_path filter hook functionality
     * 
     * Overview:
     * This test verifies that the c3_invalidation_manual_batch_all_path filter hook
     * correctly allows customization of the path pattern used for manual "clear all cache" operations.
     * 
     * Expected Behavior:
     * - The filter should receive the default all-clear path pattern ('/*') as a parameter
     * - The filter should be able to return a custom path pattern for clearing all cache
     * - The invalidation batch should contain only the custom path pattern
     * - This provides more granular control over manual cache clearing operations
     * 
     * Test Method:
     * 1. Register a filter that replaces the default '/*' pattern with '/custom-all-path/*'
     * 2. Call create_batch_for_all() method to trigger manual all-cache clearing
     * 3. Verify the resulting invalidation paths contain the custom pattern '/custom-all-path/*'
     * 4. Confirm the default '/*' pattern is not present in the results
     * 5. Assert the total quantity of paths is 1 (only the custom pattern)
     * 6. Validate that developers can restrict "clear all" operations to specific directories
     */
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

    /**
     * Test Case: WordPress subdirectory installation support
     * 
     * Overview:
     * This test verifies that the new path adjustment hooks correctly handle WordPress
     * installations in subdirectories by automatically including subdirectory paths
     * in invalidation requests through WordPress's standard home_url() function.
     * 
     * Expected Behavior:
     * - When WordPress is installed in a subdirectory (e.g., /blog/), home_url() should return the subdirectory path
     * - The c3_invalidation_post_batch_home_path filter should receive the subdirectory path automatically
     * - Invalidation batches should include both the subdirectory home path and post-specific paths
     * - The plugin should work seamlessly without manual configuration for subdirectory installations
     * 
     * Test Method:
     * 1. Mock WordPress home_url() to simulate a subdirectory installation (/blog/)
     * 2. Register a filter to verify the subdirectory path is correctly passed to the hook
     * 3. Create a test post and generate an invalidation batch
     * 4. Verify that the resulting invalidation paths include the subdirectory home path '/blog/'
     * 5. Confirm that post-specific paths also include the subdirectory structure
     * 6. Assert that no manual configuration is needed for subdirectory support
     */
    public function test_subdirectory_installation_support() {
        add_filter( 'home_url', function( $url, $path ) {
            if ( $path === '/' ) {
                return 'https://example.com/blog/';
            }
            return $url;
        }, 10, 2 );
        
        $received_home_path = null;
        add_filter( 'c3_invalidation_post_batch_home_path', function( $home_path, $post ) use ( &$received_home_path ) {
            $received_home_path = $home_path;
            return $home_path; // Return unchanged to test default behavior
        }, 10, 2 );
        
        $post = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'test-subdirectory-post',
        ) );
        
        $target = new AWS\Invalidation_Batch_Service();
        $result = $target->create_batch_by_post( 'https://example.com/blog/', 'EXXXX', $post );
        
        $this->assertEquals( 'https://example.com/blog/', $received_home_path );
        
        $paths = $result[ 'InvalidationBatch' ][ 'Paths' ][ 'Items' ];
        $this->assertThat( $paths, $this->contains( 'https://example.com/blog/' ) );
        $this->assertThat( $paths, $this->contains( '/test-subdirectory-post/*' ) );
        $this->assertEquals( 2, $result[ 'InvalidationBatch' ][ 'Paths' ][ 'Quantity' ] );
    }

    /**
     * Test Case: Subdirectory-specific path customization
     * 
     * Overview:
     * This test verifies that developers can customize invalidation paths specifically
     * for subdirectory installations using the new hooks, allowing for environment-specific
     * or subdirectory-specific cache invalidation strategies.
     * 
     * Expected Behavior:
     * - Developers should be able to detect subdirectory installations in their filters
     * - Custom paths can be returned that are specific to the subdirectory environment
     * - The hooks should work correctly with both subdirectory and root installations
     * 
     * Test Method:
     * 1. Create a filter that detects subdirectory installations and returns custom paths
     * 2. Test with both subdirectory and root installation scenarios
     * 3. Verify that custom subdirectory-specific paths are correctly applied
     * 4. Confirm that root installations are not affected by subdirectory-specific logic
     */
    public function test_subdirectory_specific_path_customization() {
        add_filter( 'c3_invalidation_post_batch_home_path', function( $home_path, $post ) {
            if ( strpos( $home_path, '/blog/' ) !== false ) {
                return '/blog/custom-home/'; // Custom path for blog subdirectory
            }
            return $home_path; // Default for root installations
        }, 10, 2 );
        
        $post = $this->factory->post->create_and_get( array(
            'post_status' => 'publish',
            'post_name' => 'subdirectory-custom-post',
        ) );
        
        $target = new AWS\Invalidation_Batch_Service();
        
        $result_subdirectory = $target->create_batch_by_post( 'https://example.com/blog/', 'EXXXX', $post );
        $paths_subdirectory = $result_subdirectory[ 'InvalidationBatch' ][ 'Paths' ][ 'Items' ];
        $this->assertThat( $paths_subdirectory, $this->contains( '/blog/custom-home/' ) );
        
        $result_root = $target->create_batch_by_post( 'https://example.com/', 'EXXXX', $post );
        $paths_root = $result_root[ 'InvalidationBatch' ][ 'Paths' ][ 'Items' ];
        $this->assertThat( $paths_root, $this->contains( 'https://example.com/' ) );
        $this->assertThat( $paths_root, $this->logicalNot( $this->contains( '/blog/custom-home/' ) ) );
    }
}
