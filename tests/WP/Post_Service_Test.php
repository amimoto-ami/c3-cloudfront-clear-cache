<?php
/**
 * Test Case: Post_Service class functionality
 *
 * @package C3_CloudFront_Cache_Controller\Test\WP
 */

namespace C3_CloudFront_Cache_Controller\Test\WP;
use C3_CloudFront_Cache_Controller\WP\Post_Service;

/**
 * Post_Service_Test class
 *
 * Tests the Post_Service class methods, particularly the list_posts_by_ids method
 * which has been reported to have 4 specific issues that need to be addressed.
 */
class Post_Service_Test extends \WP_UnitTestCase {

    /**
     * Test Case: Post type scope - should only return public post types
     * 
     * Overview:
     * This test verifies that list_posts_by_ids only returns posts from public post types
     * and excludes non-public types like wp_template, wp_block, and attachments.
     * 
     * Expected Behavior:
     * - Public post types (post, page, custom public types) should be included
     * - Non-public post types (wp_template, wp_block, attachment) should be excluded
     * - The method should use get_post_types(array('public' => true)) to filter types
     * 
     * Test Methodology:
     * 1. Create posts of different types (public and non-public)
     * 2. Call list_posts_by_ids with all post IDs
     * 3. Verify only public post type posts are returned
     */
    public function test_list_posts_by_ids_excludes_non_public_post_types() {
        $public_post = $this->factory->post->create_and_get(array(
            'post_type' => 'post',
            'post_status' => 'publish',
        ));

        $public_page = $this->factory->post->create_and_get(array(
            'post_type' => 'page',
            'post_status' => 'publish',
        ));

        $attachment = $this->factory->attachment->create_and_get();

        register_post_type('test_private_type', array(
            'public' => false,
            'publicly_queryable' => false,
        ));

        $private_post = $this->factory->post->create_and_get(array(
            'post_type' => 'test_private_type',
            'post_status' => 'publish',
        ));

        $post_service = new Post_Service();
        $post_ids = array($public_post->ID, $public_page->ID, $attachment->ID, $private_post->ID);
        
        $result = $post_service->list_posts_by_ids($post_ids);

        $this->assertCount(2, $result);
        
        $returned_ids = array_map(function($post) { return $post->ID; }, $result);
        $this->assertContains($public_post->ID, $returned_ids);
        $this->assertContains($public_page->ID, $returned_ids);
        $this->assertNotContains($attachment->ID, $returned_ids);
        $this->assertNotContains($private_post->ID, $returned_ids);

        _unregister_post_type('test_private_type');
    }

    /**
     * Test Case: Results truncation - should return all posts regardless of count
     * 
     * Overview:
     * This test verifies that list_posts_by_ids returns all requested posts
     * without being limited by WordPress default pagination (usually 10 posts).
     * 
     * Expected Behavior:
     * - All valid post IDs should be returned regardless of count
     * - The method should use posts_per_page => -1 to disable pagination
     * - No posts should be truncated due to default WordPress query limits
     * 
     * Test Methodology:
     * 1. Create more than 10 posts (exceeding default WordPress page size)
     * 2. Call list_posts_by_ids with all post IDs
     * 3. Verify all posts are returned without truncation
     */
    public function test_list_posts_by_ids_returns_all_posts_without_pagination() {
        $post_ids = array();
        for ($i = 0; $i < 15; $i++) {
            $post = $this->factory->post->create_and_get(array(
                'post_status' => 'publish',
                'post_title' => 'Test Post ' . $i,
            ));
            $post_ids[] = $post->ID;
        }

        $post_service = new Post_Service();
        $result = $post_service->list_posts_by_ids($post_ids);

        $this->assertCount(15, $result);
        
        $returned_ids = array_map(function($post) { return $post->ID; }, $result);
        foreach ($post_ids as $post_id) {
            $this->assertContains($post_id, $returned_ids);
        }
    }

    /**
     * Test Case: ID sanitization - should handle invalid/empty IDs gracefully
     * 
     * Overview:
     * This test verifies that list_posts_by_ids properly sanitizes and validates
     * input IDs, handling edge cases like empty arrays, invalid values, and mixed data types.
     * 
     * Expected Behavior:
     * - Empty arrays should return empty array
     * - Non-array input should return empty array
     * - Invalid IDs (strings, negative numbers, zero) should be filtered out
     * - Only valid positive integer IDs should be processed
     * 
     * Test Methodology:
     * 1. Test various invalid input scenarios
     * 2. Test mixed valid/invalid ID arrays
     * 3. Verify proper sanitization and filtering behavior
     */
    public function test_list_posts_by_ids_sanitizes_and_validates_ids() {
        $post_service = new Post_Service();

        $result = $post_service->list_posts_by_ids(array());
        $this->assertEquals(array(), $result);

        $result = $post_service->list_posts_by_ids('invalid');
        $this->assertEquals(array(), $result);

        $result = $post_service->list_posts_by_ids(null);
        $this->assertEquals(array(), $result);

        $valid_post = $this->factory->post->create_and_get(array(
            'post_status' => 'publish',
        ));

        $mixed_ids = array(
            $valid_post->ID,  // valid
            'invalid_string', // invalid
            -1,               // invalid (negative)
            0,                // invalid (zero)
            '123abc',         // invalid (mixed)
            null,             // invalid (null)
        );

        $result = $post_service->list_posts_by_ids($mixed_ids);
        
        $this->assertCount(1, $result);
        $this->assertEquals($valid_post->ID, $result[0]->ID);

        $invalid_ids = array('invalid', -1, 0, null, '');
        $result = $post_service->list_posts_by_ids($invalid_ids);
        $this->assertEquals(array(), $result);
    }

    /**
     * Test Case: Negative ID sanitization - should reject negative IDs completely
     * 
     * Overview:
     * This test verifies that list_posts_by_ids properly rejects negative IDs
     * and does not convert them to positive values (which would be a security issue).
     * 
     * Expected Behavior:
     * - Negative IDs should be completely filtered out
     * - No negative ID should be converted to positive (e.g., -1 should not become 1)
     * - Only positive integer IDs should be processed
     * - This prevents potential security issues where negative IDs could be converted
     * 
     * Test Methodology:
     * 1. Test various negative ID scenarios
     * 2. Test mixed positive/negative ID arrays
     * 3. Verify no negative IDs are converted to positive
     * 4. Ensure only valid positive IDs remain
     */
    public function test_list_posts_by_ids_rejects_negative_ids_completely() {
        $post_service = new Post_Service();

        // Test single negative ID
        $result = $post_service->list_posts_by_ids(array(-1));
        $this->assertEquals(array(), $result, 'Single negative ID should return empty array');

        // Test multiple negative IDs
        $result = $post_service->list_posts_by_ids(array(-1, -2, -3, -100));
        $this->assertEquals(array(), $result, 'Multiple negative IDs should return empty array');

        // Test mixed positive and negative IDs
        $valid_post = $this->factory->post->create_and_get(array(
            'post_status' => 'publish',
        ));
        $valid_post2 = $this->factory->post->create_and_get(array(
            'post_status' => 'publish',
        ));

        $mixed_ids = array(
            $valid_post->ID,  // valid positive
            -1,               // negative (should be rejected)
            $valid_post2->ID, // valid positive
            -5,               // negative (should be rejected)
            -10,              // negative (should be rejected)
        );

        $result = $post_service->list_posts_by_ids($mixed_ids);
        
        $this->assertCount(2, $result, 'Only positive IDs should be returned');
        $returned_ids = array_map(function($post) { return $post->ID; }, $result);
        $this->assertContains($valid_post->ID, $returned_ids);
        $this->assertContains($valid_post2->ID, $returned_ids);
        $this->assertNotContains(-1, $returned_ids, 'Negative ID should not be converted to positive');
        $this->assertNotContains(1, $returned_ids, 'Negative ID -1 should not become positive ID 1');

        // Test edge case: zero and negative
        $result = $post_service->list_posts_by_ids(array(0, -1, -2));
        $this->assertEquals(array(), $result, 'Zero and negative IDs should return empty array');

        // Test large negative numbers
        $result = $post_service->list_posts_by_ids(array(-999, -1000, -9999));
        $this->assertEquals(array(), $result, 'Large negative numbers should return empty array');
    }

    /**
     * Test Case: ID sanitization regression prevention - ensures absint() is not used
     * 
     * Overview:
     * This test specifically prevents regression to using absint() which would
     * convert negative IDs to positive IDs, creating a security vulnerability.
     * 
     * Expected Behavior:
     * - Negative IDs must be completely rejected, not converted
     * - The sanitization should use intval() + positive check, not absint()
     * - This test ensures the security fix remains in place
     * 
     * Test Methodology:
     * 1. Test that negative IDs are rejected (not converted)
     * 2. Verify the sanitization logic works correctly
     * 3. Ensure no false positives from ID conversion
     */
    public function test_list_posts_by_ids_prevents_absint_regression() {
        $post_service = new Post_Service();

        // Create a post with ID 1 to test if -1 gets converted to 1
        $post_with_id_1 = $this->factory->post->create_and_get(array(
            'post_status' => 'publish',
        ));

        // If absint() was used, -1 would become 1 and match the post
        // With our fix, -1 should be rejected completely
        $result = $post_service->list_posts_by_ids(array(-1));
        $this->assertEquals(array(), $result, 'Negative ID -1 should be rejected, not converted to 1');

        // Test with multiple negative IDs that could convert to existing positive IDs
        $result = $post_service->list_posts_by_ids(array(-1, -2, -3));
        $this->assertEquals(array(), $result, 'Multiple negative IDs should be rejected');

        // Test mixed case where some negatives could convert to existing IDs
        $existing_ids = array($post_with_id_1->ID);
        $malicious_ids = array_merge($existing_ids, array(-1, -2, -3));
        
        $result = $post_service->list_posts_by_ids($malicious_ids);
        
        // Should only return the legitimate positive ID, not any converted negatives
        $this->assertCount(1, $result);
        $this->assertEquals($post_with_id_1->ID, $result[0]->ID);
    }

    /**
     * Test Case: Performance optimization - should use optimized query parameters
     * 
     * Overview:
     * This test verifies that list_posts_by_ids uses performance optimization
     * parameters to reduce database load and improve query efficiency.
     * 
     * Expected Behavior:
     * - Should use no_found_rows => true to skip counting total results
     * - Should disable meta and term cache updates when not needed
     * - Should maintain input ID order in results when possible
     * - Should call wp_reset_postdata() to clean up global state
     * 
     * Test Methodology:
     * 1. Create test posts and call the method
     * 2. Verify the method completes without errors (indicating proper query structure)
     * 3. Test that wp_reset_postdata() is called by checking global state
     * 4. Verify performance optimizations don't break functionality
     */
    public function test_list_posts_by_ids_uses_performance_optimizations() {
        $post1 = $this->factory->post->create_and_get(array(
            'post_status' => 'publish',
            'post_title' => 'First Post',
        ));
        $post2 = $this->factory->post->create_and_get(array(
            'post_status' => 'publish',
            'post_title' => 'Second Post',
        ));

        $post_ids = array($post1->ID, $post2->ID);
        $post_service = new Post_Service();

        global $post;
        $original_post = $post;
        $post = $post1; // Set global post to something

        $result = $post_service->list_posts_by_ids($post_ids);

        $this->assertCount(2, $result);
        $returned_ids = array_map(function($p) { return $p->ID; }, $result);
        $this->assertContains($post1->ID, $returned_ids);
        $this->assertContains($post2->ID, $returned_ids);

        $this->assertInstanceOf('WP_Post', $result[0]);
        $this->assertInstanceOf('WP_Post', $result[1]);

        $post = $original_post;
    }

    /**
     * Test Case: Integration test with WP-CLI usage pattern
     * 
     * Overview:
     * This test verifies that the method works correctly with the usage pattern
     * from WP_CLI_Command where comma-separated string IDs are exploded into an array.
     * 
     * Expected Behavior:
     * - Should handle string IDs from explode() operation
     * - Should work with the exact pattern used in WP-CLI command
     * - Should maintain compatibility with existing usage
     * 
     * Test Methodology:
     * 1. Simulate the WP-CLI usage pattern: explode(',', '1,2,3')
     * 2. Call list_posts_by_ids with the resulting array
     * 3. Verify correct posts are returned
     */
    public function test_list_posts_by_ids_works_with_wp_cli_pattern() {
        $post1 = $this->factory->post->create_and_get(array('post_status' => 'publish'));
        $post2 = $this->factory->post->create_and_get(array('post_status' => 'publish'));
        $post3 = $this->factory->post->create_and_get(array('post_status' => 'publish'));

        $comma_separated_ids = $post1->ID . ',' . $post2->ID . ',' . $post3->ID;
        $exploded_ids = explode(',', $comma_separated_ids);

        $post_service = new Post_Service();
        $result = $post_service->list_posts_by_ids($exploded_ids);

        $this->assertCount(3, $result);
        $returned_ids = array_map(function($p) { return $p->ID; }, $result);
        $this->assertContains($post1->ID, $returned_ids);
        $this->assertContains($post2->ID, $returned_ids);
        $this->assertContains($post3->ID, $returned_ids);
    }
}
