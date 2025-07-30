# Integration Examples

This page provides real-world integration examples for C3 CloudFront Cache Controller with various WordPress setups and hosting environments.

## Hosting Platform Integrations

### AWS Infrastructure

#### EC2 with Auto Scaling

```php
class AWS_EC2_C3_Integration {
    public function __construct() {
        add_filter('c3_credential', [$this, 'use_iam_roles']);
        add_filter('c3_invalidation_items', [$this, 'multi_region_invalidation'], 10, 2);
    }
    
    public function use_iam_roles($credentials) {
        // Use IAM roles instead of access keys in EC2
        if ($this->is_ec2_instance()) {
            return [
                'use_iam_role' => true,
                'distribution_id' => getenv('C3_DISTRIBUTION_ID'),
                'timeout' => 60
            ];
        }
        
        return $credentials;
    }
    
    public function multi_region_invalidation($items, $post) {
        if ($post) {
            // Add region-specific endpoints for global content
            $regions = ['us-east-1', 'eu-west-1', 'ap-southeast-1'];
            
            foreach ($regions as $region) {
                $items[] = '/api/' . $region . '/content.json';
            }
        }
        
        return $items;
    }
    
    private function is_ec2_instance() {
        // Check if running on EC2 by trying to access metadata service
        $context = stream_context_create([
            'http' => [
                'timeout' => 1,
                'method' => 'GET'
            ]
        ]);
        
        return @file_get_contents('http://169.254.169.254/latest/meta-data/instance-id', false, $context) !== false;
    }
}

new AWS_EC2_C3_Integration();
```

#### ECS/Fargate Deployment

```yaml
# docker-compose.yml for ECS
version: '3.8'
services:
  wordpress:
    image: wordpress:latest
    environment:
      - WORDPRESS_DB_HOST=database.cluster.amazonaws.com
      - AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
      - AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
      - C3_DISTRIBUTION_ID=${C3_DISTRIBUTION_ID}
    volumes:
      - ./c3-cloudfront-clear-cache:/var/www/html/wp-content/plugins/c3-cloudfront-clear-cache
```

### WP Engine

```php
class WPEngine_C3_Integration {
    public function __construct() {
        add_filter('c3_credential', [$this, 'wpengine_config']);
        add_action('wpe_heartbeat', [$this, 'process_queue']);
    }
    
    public function wpengine_config($credentials) {
        // Use WP Engine specific environment variables
        return [
            'key' => getenv('WPE_AWS_ACCESS_KEY_ID'),
            'secret' => getenv('WPE_AWS_SECRET_ACCESS_KEY'),
            'distribution_id' => getenv('WPE_C3_DISTRIBUTION_ID'),
            'timeout' => 30
        ];
    }
    
    public function process_queue() {
        // Process invalidation queue during WP Engine heartbeat
        do_action('c3_process_queue');
    }
}

if (class_exists('WpeCommon')) {
    new WPEngine_C3_Integration();
}
```

### Kinsta

```php
class Kinsta_C3_Integration {
    public function __construct() {
        add_filter('c3_credential', [$this, 'kinsta_config']);
        add_filter('c3_invalidation_items', [$this, 'kinsta_cdn_paths'], 10, 2);
    }
    
    public function kinsta_config($credentials) {
        // Use Kinsta environment variables
        if (defined('KINSTA_CACHE_ZONE')) {
            return [
                'key' => getenv('KINSTA_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('KINSTA_AWS_SECRET_ACCESS_KEY'),
                'distribution_id' => getenv('KINSTA_C3_DISTRIBUTION_ID'),
                'timeout' => 45
            ];
        }
        
        return $credentials;
    }
    
    public function kinsta_cdn_paths($items, $post) {
        if ($post) {
            // Clear Kinsta's CDN cache as well
            do_action('kinsta_cache_purge', get_permalink($post->ID));
        }
        
        return $items;
    }
}

if (defined('KINSTA_CACHE_ZONE')) {
    new Kinsta_C3_Integration();
}
```

## Content Management Integrations

### Advanced Custom Fields (ACF)

```php
class ACF_C3_Integration {
    public function __construct() {
        add_action('acf/save_post', [$this, 'acf_field_invalidation']);
        add_filter('c3_invalidation_items', [$this, 'acf_related_content'], 10, 2);
    }
    
    public function acf_field_invalidation($post_id) {
        // Get the post
        $post = get_post($post_id);
        if (!$post) return;
        
        $paths = [get_permalink($post_id)];
        
        // Check for flexible content fields
        if (have_rows('page_content', $post_id)) {
            while (have_rows('page_content', $post_id)) {
                the_row();
                
                switch (get_row_layout()) {
                    case 'hero_section':
                        $paths[] = '/';  // Hero changes affect homepage
                        break;
                    case 'product_gallery':
                        $paths[] = '/products/';
                        break;
                    case 'testimonials':
                        $paths[] = '/testimonials/';
                        break;
                }
            }
        }
        
        // Check for relationship fields
        $related_posts = get_field('related_posts', $post_id);
        if ($related_posts) {
            foreach ($related_posts as $related_post) {
                $paths[] = get_permalink($related_post->ID);
            }
        }
        
        do_action('c3_invalidate_cache', $paths);
    }
    
    public function acf_related_content($items, $post) {
        if (!$post) return $items;
        
        // Find posts that reference this post
        $referencing_posts = get_posts([
            'meta_query' => [
                [
                    'key' => 'related_posts',
                    'value' => '"' . $post->ID . '"',
                    'compare' => 'LIKE'
                ]
            ],
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        foreach ($referencing_posts as $ref_post) {
            $items[] = get_permalink($ref_post->ID);
        }
        
        return $items;
    }
}

if (class_exists('ACF')) {
    new ACF_C3_Integration();
}
```

### Elementor

```php
class Elementor_C3_Integration {
    public function __construct() {
        add_action('elementor/editor/after_save', [$this, 'elementor_save_invalidation'], 10, 2);
        add_filter('c3_invalidation_items', [$this, 'elementor_global_widgets'], 10, 2);
    }
    
    public function elementor_save_invalidation($post_id, $editor_data) {
        $paths = [get_permalink($post_id)];
        
        // Check for global widgets that might be used elsewhere
        foreach ($editor_data as $element) {
            if (isset($element['widgetType']) && $element['widgetType'] === 'global') {
                // This is a global widget, clear all pages that might use it
                $paths[] = '/';  // Homepage
                $paths = array_merge($paths, $this->get_pages_using_global_widget($element['id']));
            }
        }
        
        do_action('c3_invalidate_cache', $paths);
    }
    
    public function elementor_global_widgets($items, $post) {
        if ($post && get_post_meta($post->ID, '_elementor_edit_mode', true)) {
            // If this post uses Elementor, check for global elements
            $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
            
            if ($elementor_data) {
                $data = json_decode($elementor_data, true);
                if ($this->has_global_elements($data)) {
                    // Clear all pages if global elements are used
                    $items[] = '/sitemap.xml';  // Ensure sitemap is updated
                }
            }
        }
        
        return $items;
    }
    
    private function get_pages_using_global_widget($widget_id) {
        // Query pages that use this global widget
        $pages = get_posts([
            'meta_query' => [
                [
                    'key' => '_elementor_data',
                    'value' => $widget_id,
                    'compare' => 'LIKE'
                ]
            ],
            'post_type' => 'any',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        return array_map('get_permalink', wp_list_pluck($pages, 'ID'));
    }
    
    private function has_global_elements($data) {
        foreach ($data as $element) {
            if (isset($element['widgetType']) && $element['widgetType'] === 'global') {
                return true;
            }
            if (isset($element['elements']) && $this->has_global_elements($element['elements'])) {
                return true;
            }
        }
        return false;
    }
}

if (defined('ELEMENTOR_VERSION')) {
    new Elementor_C3_Integration();
}
```

## E-commerce Integrations

### WooCommerce Advanced

```php
class WooCommerce_Advanced_C3_Integration {
    public function __construct() {
        // Inventory management
        add_action('woocommerce_product_set_stock', [$this, 'stock_level_invalidation'], 10, 3);
        add_action('woocommerce_variation_set_stock', [$this, 'variation_stock_invalidation'], 10, 2);
        
        // Price changes
        add_action('woocommerce_product_object_updated_props', [$this, 'price_change_invalidation'], 10, 2);
        
        // Order status changes
        add_action('woocommerce_order_status_changed', [$this, 'order_status_invalidation'], 10, 4);
        
        // Reviews
        add_action('comment_post', [$this, 'review_invalidation']);
    }
    
    public function stock_level_invalidation($product_id, $stock_quantity, $operation) {
        $product = wc_get_product($product_id);
        if (!$product) return;
        
        $paths = [
            get_permalink($product_id),
            '/shop/',
            '/wp-json/wc/v3/products/' . $product_id
        ];
        
        // If product went out of stock or back in stock
        if ($stock_quantity <= 0 || ($operation === 'increase' && $stock_quantity === 1)) {
            $paths[] = '/shop/in-stock/';
            $paths[] = '/shop/out-of-stock/';
        }
        
        // Clear category pages
        $categories = wp_get_post_terms($product_id, 'product_cat');
        foreach ($categories as $category) {
            $paths[] = get_term_link($category);
        }
        
        do_action('c3_invalidate_cache', $paths);
    }
    
    public function price_change_invalidation($product, $updated_props) {
        if (array_intersect(['regular_price', 'sale_price', 'price'], $updated_props)) {
            $paths = [
                get_permalink($product->get_id()),
                '/shop/',
                '/shop/sale/',
                '/api/prices.json'
            ];
            
            // Clear comparison pages
            $paths[] = '/compare/';
            $paths[] = '/deals/';
            
            do_action('c3_invalidate_cache', $paths);
        }
    }
    
    public function order_status_invalidation($order_id, $old_status, $new_status, $order) {
        // Clear inventory-related pages for completed orders
        if ($new_status === 'completed') {
            $paths = ['/shop/', '/inventory-report/'];
            
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $paths[] = get_permalink($product_id);
            }
            
            do_action('c3_invalidate_cache', $paths);
        }
    }
    
    public function review_invalidation($comment_id) {
        $comment = get_comment($comment_id);
        if ($comment && $comment->comment_type === 'review') {
            $product_id = $comment->comment_post_ID;
            
            $paths = [
                get_permalink($product_id),
                '/shop/',
                '/reviews/',
                '/wp-json/wc/v3/products/' . $product_id . '/reviews'
            ];
            
            do_action('c3_invalidate_cache', $paths);
        }
    }
}

if (class_exists('WooCommerce')) {
    new WooCommerce_Advanced_C3_Integration();
}
```

## CDN and Performance Integrations

### Cloudflare Integration

```php
class Cloudflare_C3_Integration {
    public function __construct() {
        add_action('c3_after_invalidation', [$this, 'cloudflare_purge'], 10, 2);
    }
    
    public function cloudflare_purge($paths, $result) {
        if (is_wp_error($result)) return;
        
        $cf_zone = getenv('CLOUDFLARE_ZONE_ID');
        $cf_token = getenv('CLOUDFLARE_API_TOKEN');
        
        if (!$cf_zone || !$cf_token) return;
        
        // Convert paths to full URLs for Cloudflare
        $urls = array_map(function($path) {
            return home_url($path);
        }, $paths);
        
        $this->purge_cloudflare_cache($cf_zone, $cf_token, $urls);
    }
    
    private function purge_cloudflare_cache($zone_id, $token, $urls) {
        $response = wp_remote_post("https://api.cloudflare.com/client/v4/zones/{$zone_id}/purge_cache", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['files' => $urls]),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            error_log('Cloudflare purge failed: ' . $response->get_error_message());
        }
    }
}

if (getenv('CLOUDFLARE_ZONE_ID')) {
    new Cloudflare_C3_Integration();
}
```

### Redis Cache Integration

```php
class Redis_C3_Integration {
    private $redis;
    
    public function __construct() {
        if (class_exists('Redis')) {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
            
            add_action('c3_before_invalidation', [$this, 'clear_redis_cache']);
        }
    }
    
    public function clear_redis_cache($paths) {
        foreach ($paths as $path) {
            $cache_key = 'page_cache:' . md5($path);
            $this->redis->del($cache_key);
        }
        
        // Clear related cache tags
        $this->redis->del('cache_tags:content');
        $this->redis->del('cache_tags:navigation');
    }
}

new Redis_C3_Integration();
```

## Monitoring and Analytics

### Application Performance Monitoring

```php
class APM_C3_Integration {
    public function __construct() {
        add_action('c3_before_invalidation', [$this, 'start_invalidation_trace']);
        add_action('c3_after_invalidation', [$this, 'end_invalidation_trace'], 10, 2);
    }
    
    public function start_invalidation_trace($paths) {
        if (function_exists('newrelic_start_transaction')) {
            newrelic_start_transaction('C3 Cache Invalidation');
            newrelic_add_custom_parameter('paths_count', count($paths));
        }
        
        // Store start time
        set_transient('c3_invalidation_start', microtime(true), 300);
    }
    
    public function end_invalidation_trace($paths, $result) {
        $start_time = get_transient('c3_invalidation_start');
        if ($start_time) {
            $duration = microtime(true) - $start_time;
            
            if (function_exists('newrelic_custom_metric')) {
                newrelic_custom_metric('Custom/C3/InvalidationDuration', $duration);
                newrelic_add_custom_parameter('success', !is_wp_error($result));
            }
            
            delete_transient('c3_invalidation_start');
        }
        
        // Log to analytics
        $this->log_to_analytics([
            'event' => 'cache_invalidation',
            'paths_count' => count($paths),
            'success' => !is_wp_error($result),
            'duration' => $duration ?? 0
        ]);
    }
    
    private function log_to_analytics($data) {
        // Send to your analytics service
        wp_remote_post('https://analytics.yoursite.com/events', [
            'body' => json_encode($data),
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }
}

new APM_C3_Integration();
```

## Multi-Site Network

```php
class Multisite_C3_Integration {
    public function __construct() {
        add_filter('c3_credential', [$this, 'site_specific_credentials']);
        add_filter('c3_invalidation_items', [$this, 'network_wide_invalidation'], 10, 2);
    }
    
    public function site_specific_credentials($credentials) {
        $site_id = get_current_blog_id();
        
        // Different distributions for different sites
        $site_distributions = [
            1 => 'E123MAIN456',      // Main site
            2 => 'E123BLOG456',      // Blog subdomain
            3 => 'E123SHOP456',      // Shop subdomain
        ];
        
        if (isset($site_distributions[$site_id])) {
            $credentials['distribution_id'] = $site_distributions[$site_id];
        }
        
        return $credentials;
    }
    
    public function network_wide_invalidation($items, $post) {
        if ($post && is_main_site()) {
            // Main site changes affect all subsites
            $sites = get_sites(['number' => 100]);
            
            foreach ($sites as $site) {
                if ($site->blog_id !== get_current_blog_id()) {
                    switch_to_blog($site->blog_id);
                    $items[] = get_home_url();
                    restore_current_blog();
                }
            }
        }
        
        return $items;
    }
}

if (is_multisite()) {
    new Multisite_C3_Integration();
}
```

These integration examples demonstrate how to adapt C3 CloudFront Cache Controller to work seamlessly with various WordPress setups, hosting environments, and third-party services. Choose the patterns that match your specific infrastructure and requirements.