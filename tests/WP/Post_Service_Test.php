<?php
namespace C3_CloudFront_Cache_Controller\Test\WP;
use C3_CloudFront_Cache_Controller\WP\Post_Service;
use C3_CloudFront_Cache_Controller\WP\Hooks;

class Post_Service_Test extends \WP_UnitTestCase {
    
    /**
     * Test case overview: Verify that Post_Service uses WordPress default behavior (post type only)
     * Expected behavior: WP_Query should use WordPress default behavior when no post_type is specified
     * Test methodology: Create posts of different types and verify only 'post' type is found by default
     */
    public function test_list_posts_by_ids_default_wordpress_behavior() {
        $post_service = new Post_Service();
        
        $regular_post = $this->factory->post->create(array(
            'post_type' => 'post',
            'post_status' => 'publish'
        ));
        
        $page = $this->factory->post->create(array(
            'post_type' => 'page',
            'post_status' => 'publish'
        ));
        
        $post_ids = array($regular_post, $page);
        $results = $post_service->list_posts_by_ids($post_ids);
        
        $this->assertEquals(1, count($results));
        $this->assertEquals('post', $results[0]->post_type);
        $this->assertEquals($regular_post, $results[0]->ID);
    }
    
    /**
     * Test case overview: Verify that c3_post_service_query_args filter allows enabling custom post type support (Issue #84 fix)
     * Expected behavior: Filter should allow adding post_type => 'any' to include all post types when needed
     * Test methodology: Add filter to set post_type to 'any' and verify both post and page are found
     */
    public function test_c3_post_service_query_args_filter_enable_any_post_type_for_issue_84() {
        add_filter('c3_post_service_query_args', function($args, $post_ids) {
            $args['post_type'] = 'any';
            return $args;
        }, 10, 2);
        
        $post_service = new Post_Service();
        
        $regular_post = $this->factory->post->create(array(
            'post_type' => 'post',
            'post_status' => 'publish'
        ));
        
        $page = $this->factory->post->create(array(
            'post_type' => 'page',
            'post_status' => 'publish'
        ));
        
        $post_ids = array($regular_post, $page);
        $results = $post_service->list_posts_by_ids($post_ids);
        
        $this->assertEquals(2, count($results));
        
        $found_types = array();
        foreach ($results as $post) {
            $found_types[] = $post->post_type;
        }
        
        $this->assertContains('post', $found_types);
        $this->assertContains('page', $found_types);
        
        remove_all_filters('c3_post_service_query_args');
    }
    
    /**
     * Test case overview: Verify that Post_Service accepts Hooks service via dependency injection
     * Expected behavior: Should use injected Hooks service instead of creating new instance
     * Test methodology: Inject mock Hooks service and verify it's used for apply_filters
     */
    public function test_post_service_hooks_dependency_injection() {
        $mock_hooks = $this->createMock(Hooks::class);
        
        $mock_hooks->expects($this->once())
                   ->method('apply_filters')
                   ->with(
                       $this->equalTo('c3_post_service_query_args'),
                       $this->callback(function($args) {
                           return isset($args['post__in']) && !isset($args['post_type']);
                       }),
                       $this->isType('array')
                   )
                   ->willReturnArgument(1);
        
        $post_service = new Post_Service($mock_hooks);
        
        $post_id = $this->factory->post->create(array(
            'post_type' => 'post',
            'post_status' => 'publish'
        ));
        
        $results = $post_service->list_posts_by_ids(array($post_id));
        
        $this->assertNotEmpty($results);
    }
    
    /**
     * Test case overview: Verify backward compatibility with empty post IDs array
     * Expected behavior: Should return empty array without errors
     * Test methodology: Pass empty array and verify no exceptions and empty result
     */
    public function test_list_posts_by_ids_empty_array() {
        $post_service = new Post_Service();
        $results = $post_service->list_posts_by_ids(array());
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
}
