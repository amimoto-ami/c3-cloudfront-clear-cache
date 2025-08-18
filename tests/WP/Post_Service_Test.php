<?php
namespace C3_CloudFront_Cache_Controller\Test\WP;
use C3_CloudFront_Cache_Controller\WP\Post_Service;
use C3_CloudFront_Cache_Controller\WP\Hooks;

class Post_Service_Test extends \WP_UnitTestCase {
    
    /**
     * Test case overview: Verify that Post_Service uses 'post_type' => 'any' by default
     * Expected behavior: WP_Query should include all post types when searching by post IDs
     * Test methodology: Mock WP_Query and verify the arguments passed include 'post_type' => 'any'
     */
    public function test_list_posts_by_ids_default_post_type_any() {
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
    }
    
    /**
     * Test case overview: Verify that c3_post_service_query_args filter works correctly
     * Expected behavior: Filter should allow customization of WP_Query arguments
     * Test methodology: Add filter to modify query args and verify the behavior changes
     */
    public function test_c3_post_service_query_args_filter() {
        add_filter('c3_post_service_query_args', function($args, $post_ids) {
            $args['post_type'] = 'post';
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
        
        $this->assertEquals(1, count($results));
        $this->assertEquals('post', $results[0]->post_type);
        
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
                           return isset($args['post_type']) && $args['post_type'] === 'any';
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
