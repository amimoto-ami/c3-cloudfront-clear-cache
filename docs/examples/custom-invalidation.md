# Custom Invalidation Examples

This page provides advanced examples for customizing cache invalidation behavior in C3 CloudFront Cache Controller.

## Content-Specific Invalidation

### E-commerce Product Updates

Handle product invalidation with related pages:

```php
class WooCommerce_C3_Integration {
    public function __construct() {
        add_filter('c3_invalidation_items', [$this, 'product_invalidation'], 10, 2);
        add_action('woocommerce_product_set_stock_status', [$this, 'stock_change_invalidation'], 10, 2);
        add_action('woocommerce_update_product', [$this, 'product_update_invalidation']);
    }
    
    public function product_invalidation($items, $post) {
        if ($post && $post->post_type === 'product') {
            // Add shop and category pages
            $items[] = '/shop/';
            $items[] = '/cart/';
            
            // Add product categories
            $categories = wp_get_post_terms($post->ID, 'product_cat');
            foreach ($categories as $category) {
                $items[] = get_term_link($category);
                
                // Clear parent categories too
                $parent_categories = get_ancestors($category->term_id, 'product_cat');
                foreach ($parent_categories as $parent_id) {
                    $parent_term = get_term($parent_id, 'product_cat');
                    $items[] = get_term_link($parent_term);
                }
            }
            
            // Add product tags
            $tags = wp_get_post_terms($post->ID, 'product_tag');
            foreach ($tags as $tag) {
                $items[] = get_term_link($tag);
            }
            
            // If featured product, clear homepage
            if (get_post_meta($post->ID, '_featured', true) === 'yes') {
                $items[] = '/';
            }
            
            // Clear API endpoints
            $items[] = '/wp-json/wc/v3/products';
            $items[] = '/wp-json/wc/v3/products/' . $post->ID;
        }
        
        return $items;
    }
    
    public function stock_change_invalidation($product_id, $stock_status) {
        $product = wc_get_product($product_id);
        if (!$product) return;
        
        $paths = [
            get_permalink($product_id),
            '/shop/',
            '/wp-json/wc/v3/products/' . $product_id
        ];
        
        // If out of stock, also clear wishlist pages
        if ($stock_status === 'outofstock') {
            $paths[] = '/wishlist/';
        }
        
        // Manual invalidation trigger
        do_action('c3_invalidate_cache', $paths);
    }
    
    public function product_update_invalidation($product_id) {
        // Get product
        $product = wc_get_product($product_id);
        if (!$product) return;
        
        $paths = [get_permalink($product_id)];
        
        // If price changed, clear price comparison pages
        if ($this->price_changed($product_id)) {
            $paths[] = '/price-comparison/';
            $paths[] = '/deals/';
        }
        
        do_action('c3_invalidate_cache', $paths);
    }
    
    private function price_changed($product_id) {
        // Compare current price with cached price
        $current_price = get_post_meta($product_id, '_price', true);
        $cached_price = get_transient('c3_product_price_' . $product_id);
        
        if ($cached_price !== false && $cached_price !== $current_price) {
            set_transient('c3_product_price_' . $product_id, $current_price, HOUR_IN_SECONDS);
            return true;
        }
        
        return false;
    }
}

new WooCommerce_C3_Integration();
```

### Event Management

Custom invalidation for events and bookings:

```php
class Event_C3_Integration {
    public function __construct() {
        add_filter('c3_invalidation_items', [$this, 'event_invalidation'], 10, 2);
        add_action('event_booking_created', [$this, 'booking_invalidation']);
        add_action('event_status_changed', [$this, 'status_change_invalidation'], 10, 2);
    }
    
    public function event_invalidation($items, $post) {
        if ($post && $post->post_type === 'event') {
            // Add event listing pages
            $items[] = '/events/';
            $items[] = '/calendar/';
            
            // Add date-based pages
            $event_date = get_post_meta($post->ID, '_event_date', true);
            if ($event_date) {
                $date = new DateTime($event_date);
                $items[] = '/events/' . $date->format('Y') . '/';
                $items[] = '/events/' . $date->format('Y/m') . '/';
            }
            
            // Add venue pages
            $venue_id = get_post_meta($post->ID, '_event_venue', true);
            if ($venue_id) {
                $items[] = get_permalink($venue_id);
                $items[] = '/venues/';
            }
            
            // Add category pages
            $categories = wp_get_post_terms($post->ID, 'event_category');
            foreach ($categories as $category) {
                $items[] = get_term_link($category);
            }
            
            // Clear ICS feed
            $items[] = '/events/feed.ics';
            $items[] = '/wp-json/events/v1/calendar';
        }
        
        return $items;
    }
    
    public function booking_invalidation($booking_id) {
        $event_id = get_post_meta($booking_id, '_event_id', true);
        if (!$event_id) return;
        
        $paths = [
            get_permalink($event_id),
            '/events/',
            '/my-bookings/',
            '/wp-json/events/v1/events/' . $event_id . '/availability'
        ];
        
        // Check if event is now sold out
        $available_tickets = get_post_meta($event_id, '_available_tickets', true);
        if ($available_tickets <= 0) {
            $paths[] = '/events/available/';
        }
        
        do_action('c3_invalidate_cache', $paths);
    }
    
    public function status_change_invalidation($event_id, $new_status) {
        $paths = [
            get_permalink($event_id),
            '/events/'
        ];
        
        switch ($new_status) {
            case 'cancelled':
                $paths[] = '/events/cancelled/';
                break;
            case 'postponed':
                $paths[] = '/events/postponed/';
                break;
            case 'sold_out':
                $paths[] = '/events/sold-out/';
                break;
        }
        
        do_action('c3_invalidate_cache', $paths);
    }
}

new Event_C3_Integration();
```

## Advanced Filtering Strategies

### Geo-Location Based Invalidation

```php
class Geo_C3_Integration {
    public function __construct() {
        add_filter('c3_invalidation_items', [$this, 'geo_invalidation'], 10, 2);
    }
    
    public function geo_invalidation($items, $post) {
        if (!$post) return $items;
        
        // Get post's target regions
        $target_regions = get_post_meta($post->ID, '_target_regions', true);
        if (!$target_regions) return $items;
        
        // Add region-specific paths
        foreach ($target_regions as $region) {
            $items[] = '/' . $region . '/';
            $items[] = '/' . $region . '/' . $post->post_type . '/';
        }
        
        // Add API endpoints with region filters
        foreach ($target_regions as $region) {
            $items[] = '/api/content/' . $region . '.json';
        }
        
        return $items;
    }
}

new Geo_C3_Integration();
```

### A/B Testing Integration

```php
class AB_Testing_C3_Integration {
    public function __construct() {
        add_filter('c3_invalidation_items', [$this, 'ab_test_invalidation'], 10, 2);
        add_action('ab_test_variant_updated', [$this, 'variant_invalidation']);
    }
    
    public function ab_test_invalidation($items, $post) {
        if (!$post) return $items;
        
        // Check if post is part of A/B test
        $test_variants = get_post_meta($post->ID, '_ab_test_variants', true);
        if (!$test_variants) return $items;
        
        // Clear all variant URLs
        foreach ($test_variants as $variant) {
            $items[] = get_permalink($post->ID) . '?variant=' . $variant;
            $items[] = '/api/content/' . $post->ID . '/' . $variant . '.json';
        }
        
        // Clear test configuration
        $items[] = '/api/ab-tests/' . get_post_meta($post->ID, '_test_id', true) . '.json';
        
        return $items;
    }
    
    public function variant_invalidation($test_id) {
        // Get all posts in this test
        $posts = get_posts([
            'meta_key' => '_test_id',
            'meta_value' => $test_id,
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        $paths = [];
        foreach ($posts as $post) {
            $paths[] = get_permalink($post->ID);
        }
        
        // Add test-specific paths
        $paths[] = '/api/ab-tests/' . $test_id . '.json';
        $paths[] = '/ab-test-results/' . $test_id . '/';
        
        do_action('c3_invalidate_cache', $paths);
    }
}

new AB_Testing_C3_Integration();
```

## Performance Optimization

### Smart Path Consolidation

```php
class Smart_Path_Consolidation {
    public function __construct() {
        add_filter('c3_invalidation_items', [$this, 'consolidate_paths'], 20, 2);
    }
    
    public function consolidate_paths($items, $post) {
        if (count($items) < 10) return $items;
        
        // Group paths by base directory
        $path_groups = [];
        foreach ($items as $item) {
            $base_path = $this->get_base_path($item);
            if (!isset($path_groups[$base_path])) {
                $path_groups[$base_path] = [];
            }
            $path_groups[$base_path][] = $item;
        }
        
        $optimized_items = [];
        foreach ($path_groups as $base => $paths) {
            if (count($paths) > 5) {
                // Use wildcard for groups with many paths
                $optimized_items[] = $base . '*';
            } else {
                $optimized_items = array_merge($optimized_items, $paths);
            }
        }
        
        return array_unique($optimized_items);
    }
    
    private function get_base_path($path) {
        $parts = explode('/', trim($path, '/'));
        return '/' . $parts[0] . '/';
    }
}

new Smart_Path_Consolidation();
```

### Conditional Invalidation Based on Content

```php
class Content_Based_Invalidation {
    public function __construct() {
        add_filter('c3_invalidation_items', [$this, 'content_based_invalidation'], 10, 2);
    }
    
    public function content_based_invalidation($items, $post) {
        if (!$post) return $items;
        
        // Check content for specific patterns
        $content = $post->post_content;
        
        // If content contains shortcodes, clear related pages
        if (has_shortcode($content, 'product_gallery')) {
            $items[] = '/products/';
            $items[] = '/gallery/';
        }
        
        if (has_shortcode($content, 'event_list')) {
            $items[] = '/events/';
            $items[] = '/calendar/';
        }
        
        // If content mentions specific pages, clear them
        $linked_pages = $this->extract_internal_links($content);
        foreach ($linked_pages as $page_url) {
            $items[] = parse_url($page_url, PHP_URL_PATH);
        }
        
        // If content contains specific keywords, clear related sections
        $keywords = ['sale', 'discount', 'promotion'];
        foreach ($keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $items[] = '/promotions/';
                $items[] = '/deals/';
                break;
            }
        }
        
        return $items;
    }
    
    private function extract_internal_links($content) {
        $home_url = home_url();
        $pattern = '/href=["\'](' . preg_quote($home_url, '/') . '[^"\']*)["\']*/i';
        
        preg_match_all($pattern, $content, $matches);
        return isset($matches[1]) ? $matches[1] : [];
    }
}

new Content_Based_Invalidation();
```

## API Integration Examples

### REST API Cache Management

```php
class REST_API_C3_Integration {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_endpoints']);
        add_filter('c3_invalidation_items', [$this, 'api_invalidation'], 10, 2);
    }
    
    public function register_endpoints() {
        register_rest_route('c3/v1', '/invalidate', [
            'methods' => 'POST',
            'callback' => [$this, 'api_invalidate'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        
        register_rest_route('c3/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    public function api_invalidate($request) {
        $paths = $request->get_param('paths');
        if (!$paths || !is_array($paths)) {
            return new WP_Error('invalid_paths', 'Paths parameter is required and must be an array');
        }
        
        // Trigger invalidation
        do_action('c3_invalidate_cache', $paths);
        
        return [
            'success' => true,
            'paths' => $paths,
            'message' => 'Invalidation triggered'
        ];
    }
    
    public function get_status($request) {
        $queue_count = $this->get_queue_count();
        $last_invalidation = get_option('c3_last_invalidation_time');
        
        return [
            'queue_count' => $queue_count,
            'last_invalidation' => $last_invalidation,
            'configured' => $this->is_configured()
        ];
    }
    
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    public function api_invalidation($items, $post) {
        if ($post) {
            // Add API endpoints for this post
            $items[] = '/wp-json/wp/v2/' . $post->post_type . '/' . $post->ID;
            $items[] = '/wp-json/wp/v2/' . $post->post_type;
            
            // Add custom API endpoints
            $items[] = '/api/v1/posts/' . $post->ID . '.json';
            $items[] = '/api/v1/posts.json';
        }
        
        return $items;
    }
    
    private function get_queue_count() {
        // Implementation depends on your queue storage method
        return get_option('c3_queue_count', 0);
    }
    
    private function is_configured() {
        $credentials = (new C3_CloudFront_Cache_Controller\WP\Options_Service())->get_credentials();
        return !empty($credentials['distribution_id']);
    }
}

new REST_API_C3_Integration();
```

### GraphQL Integration

```php
class GraphQL_C3_Integration {
    public function __construct() {
        add_action('graphql_register_types', [$this, 'register_types']);
        add_filter('c3_invalidation_items', [$this, 'graphql_invalidation'], 10, 2);
    }
    
    public function register_types() {
        register_graphql_mutation('invalidateCache', [
            'inputFields' => [
                'paths' => [
                    'type' => ['list_of' => 'String'],
                    'description' => 'Paths to invalidate'
                ]
            ],
            'outputFields' => [
                'success' => [
                    'type' => 'Boolean',
                    'description' => 'Whether invalidation was successful'
                ],
                'message' => [
                    'type' => 'String',
                    'description' => 'Result message'
                ]
            ],
            'mutateAndGetPayload' => [$this, 'invalidate_mutation']
        ]);
    }
    
    public function invalidate_mutation($input) {
        if (!current_user_can('manage_options')) {
            throw new Exception('Insufficient permissions');
        }
        
        $paths = $input['paths'] ?? [];
        if (empty($paths)) {
            return [
                'success' => false,
                'message' => 'No paths provided'
            ];
        }
        
        do_action('c3_invalidate_cache', $paths);
        
        return [
            'success' => true,
            'message' => 'Cache invalidation triggered for ' . count($paths) . ' paths'
        ];
    }
    
    public function graphql_invalidation($items, $post) {
        if ($post) {
            // Add GraphQL endpoints
            $items[] = '/graphql';
            $items[] = '/wp-json/graphql';
        }
        
        return $items;
    }
}

new GraphQL_C3_Integration();
```

## Custom Cache Strategies

### Time-Based Invalidation

```php
class Time_Based_C3_Strategy {
    public function __construct() {
        add_filter('c3_invalidation_items', [$this, 'time_based_invalidation'], 10, 2);
        add_action('wp', [$this, 'schedule_time_based_clears']);
    }
    
    public function time_based_invalidation($items, $post) {
        if (!$post) return $items;
        
        // Check if post has scheduled content updates
        $scheduled_updates = get_post_meta($post->ID, '_scheduled_updates', true);
        if ($scheduled_updates) {
            foreach ($scheduled_updates as $update) {
                if ($update['time'] > time()) {
                    // Schedule future invalidation
                    wp_schedule_single_event(
                        $update['time'],
                        'c3_scheduled_invalidation',
                        [get_permalink($post->ID)]
                    );
                }
            }
        }
        
        return $items;
    }
    
    public function schedule_time_based_clears() {
        // Schedule daily cache clear for time-sensitive content
        if (!wp_next_scheduled('c3_daily_time_sensitive_clear')) {
            wp_schedule_event(strtotime('tomorrow 1:00 AM'), 'daily', 'c3_daily_time_sensitive_clear');
        }
    }
}

add_action('c3_scheduled_invalidation', function($path) {
    do_action('c3_invalidate_cache', [$path]);
});

add_action('c3_daily_time_sensitive_clear', function() {
    // Clear time-sensitive pages daily
    $paths = [
        '/events/',
        '/deals/',
        '/news/',
        '/api/time-sensitive.json'
    ];
    
    do_action('c3_invalidate_cache', $paths);
});

new Time_Based_C3_Strategy();
```

### User-Driven Invalidation

```php
class User_Driven_C3_Integration {
    public function __construct() {
        add_action('wp_ajax_c3_manual_invalidate', [$this, 'ajax_manual_invalidate']);
        add_action('admin_footer', [$this, 'add_invalidation_buttons']);
    }
    
    public function ajax_manual_invalidate() {
        check_ajax_referer('c3_manual_invalidate');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $clear_all = !empty($_POST['clear_all']);
        
        if ($clear_all) {
            $paths = ['/*'];
        } else {
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Invalid post ID');
            }
            
            $paths = apply_filters('c3_invalidation_items', [], $post);
        }
        
        do_action('c3_invalidate_cache', $paths);
        
        wp_send_json_success([
            'message' => 'Cache invalidated for ' . count($paths) . ' paths',
            'paths' => $paths
        ]);
    }
    
    public function add_invalidation_buttons() {
        global $post, $pagenow;
        
        if ($pagenow !== 'post.php' || !$post || !current_user_can('manage_options')) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Add buttons to post edit screen
            $('#submitdiv').append(`
                <div id="c3-invalidation-controls" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                    <h4>CloudFront Cache</h4>
                    <button type="button" class="button" id="c3-invalidate-post">Clear This Post</button>
                    <button type="button" class="button" id="c3-invalidate-all">Clear All Cache</button>
                </div>
            `);
            
            $('#c3-invalidate-post').click(function() {
                c3InvalidateCache(<?php echo $post->ID; ?>, false);
            });
            
            $('#c3-invalidate-all').click(function() {
                if (confirm('Are you sure you want to clear ALL cache?')) {
                    c3InvalidateCache(0, true);
                }
            });
            
            function c3InvalidateCache(postId, clearAll) {
                $.post(ajaxurl, {
                    action: 'c3_manual_invalidate',
                    post_id: postId,
                    clear_all: clearAll ? 1 : 0,
                    _wpnonce: '<?php echo wp_create_nonce('c3_manual_invalidate'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Cache invalidated successfully!');
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
        });
        </script>
        <?php
    }
}

new User_Driven_C3_Integration();
```

These examples demonstrate advanced customization techniques for C3 CloudFront Cache Controller. Remember to test all customizations in a staging environment before deploying to production, and consider the impact on your CloudFront invalidation quota when implementing automated invalidation strategies.