# Basic Usage Examples

This page provides practical examples for common use cases of C3 CloudFront Cache Controller.

## Simple Blog Setup

### Automatic Post Invalidation

For a basic blog, the plugin works out of the box:

```php
// No additional code needed for basic post invalidation
// The plugin automatically invalidates:
// - Post permalink
// - Homepage
// - Category archives
// - Tag archives
// - Author archives
// - Date archives
```

### Custom Post Type Support

Add support for custom post types:

```php
add_filter('c3_post_types', function($post_types) {
    $post_types[] = 'portfolio';
    $post_types[] = 'testimonial';
    return $post_types;
});
```

## E-commerce Site (WooCommerce)

### Product Invalidation

Invalidate shop pages when products change:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_type === 'product') {
        // Add shop pages
        $items[] = '/shop/';
        $items[] = '/cart/';
        $items[] = '/my-account/';
        
        // Add product category pages
        $categories = wp_get_post_terms($post->ID, 'product_cat');
        foreach ($categories as $category) {
            $items[] = get_term_link($category);
        }
        
        // Add homepage if product is featured
        if (get_post_meta($post->ID, '_featured', true) === 'yes') {
            $items[] = '/';
        }
    }
    return $items;
}, 10, 2);
```

### Inventory Updates

Clear cache when stock status changes:

```php
add_action('woocommerce_product_set_stock_status', function($product_id, $stock_status) {
    if ($stock_status === 'outofstock') {
        // Clear product page and shop pages
        $permalink = get_permalink($product_id);
        
        // Manual invalidation
        do_action('c3_invalidate_cache', [$permalink, '/shop/']);
    }
}, 10, 2);
```

## News/Media Site

### Multiple Content Types

Handle different content types with specific invalidation:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if (!$post) return $items;
    
    switch ($post->post_type) {
        case 'article':
            $items[] = '/news/';
            $items[] = '/latest/';
            // Add category-specific pages
            $categories = wp_get_post_terms($post->ID, 'article_category');
            foreach ($categories as $category) {
                $items[] = '/news/' . $category->slug . '/';
            }
            break;
            
        case 'video':
            $items[] = '/videos/';
            $items[] = '/media/';
            break;
            
        case 'podcast':
            $items[] = '/podcasts/';
            $items[] = '/feed/podcast/';
            break;
    }
    
    return $items;
}, 10, 2);
```

### Breaking News

Implement priority invalidation for urgent content:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && has_term('breaking-news', 'category', $post)) {
        // Clear everything for breaking news
        return ['/*'];
    }
    return $items;
}, 10, 2);
```

## Corporate Website

### Page Hierarchy Invalidation

Clear parent pages when child pages update:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_type === 'page') {
        // Add parent page
        if ($post->post_parent) {
            $items[] = get_permalink($post->post_parent);
        }
        
        // Add child pages
        $children = get_children([
            'post_parent' => $post->ID,
            'post_type' => 'page'
        ]);
        
        foreach ($children as $child) {
            $items[] = get_permalink($child->ID);
        }
    }
    
    return $items;
}, 10, 2);
```

### Menu Updates

Clear navigation-related cache when menus change:

```php
add_action('wp_update_nav_menu', function($menu_id) {
    // Clear pages that likely contain navigation
    $nav_pages = ['/', '/about/', '/services/', '/contact/'];
    
    foreach ($nav_pages as $page) {
        // Manual invalidation
        do_action('c3_invalidate_cache', [$page]);
    }
});
```

## Multilingual Site (WPML/Polylang)

### WPML Integration

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && function_exists('icl_get_languages')) {
        // Get all language versions
        $translations = icl_get_languages('skip_missing=0');
        
        foreach ($translations as $lang) {
            $translated_id = icl_object_id(
                $post->ID, 
                $post->post_type, 
                false, 
                $lang['code']
            );
            
            if ($translated_id && $translated_id !== $post->ID) {
                $items[] = get_permalink($translated_id);
            }
        }
        
        // Add language-specific home pages
        foreach ($translations as $lang) {
            $home_url = apply_filters('wpml_home_url', home_url(), $lang['code']);
            $items[] = parse_url($home_url, PHP_URL_PATH) ?: '/';
        }
    }
    
    return $items;
}, 10, 2);
```

### Polylang Integration

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && function_exists('pll_get_post_translations')) {
        // Get all translations
        $translations = pll_get_post_translations($post->ID);
        
        foreach ($translations as $lang => $translation_id) {
            if ($translation_id && $translation_id !== $post->ID) {
                $items[] = get_permalink($translation_id);
            }
        }
        
        // Add language home pages
        $languages = pll_languages_list();
        foreach ($languages as $lang) {
            $home_url = pll_home_url($lang);
            $items[] = parse_url($home_url, PHP_URL_PATH) ?: '/';
        }
    }
    
    return $items;
}, 10, 2);
```

## API and Feed Integration

### REST API Endpoints

Clear API cache when content updates:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post) {
        // Add REST API endpoints
        $items[] = '/wp-json/wp/v2/posts';
        $items[] = '/wp-json/wp/v2/pages';
        $items[] = '/wp-json/wp/v2/' . $post->post_type;
        
        // Add custom API endpoints
        $items[] = '/api/v1/content.json';
        $items[] = '/api/v1/' . $post->post_type . '.json';
    }
    
    return $items;
}, 10, 2);
```

### RSS Feeds

Clear feed cache automatically:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_status === 'publish') {
        // Standard WordPress feeds
        $items[] = '/feed/';
        $items[] = '/comments/feed/';
        
        // Category feeds
        $categories = wp_get_post_categories($post->ID);
        foreach ($categories as $cat_id) {
            $items[] = get_category_feed_link($cat_id);
        }
        
        // Custom feeds
        $items[] = '/feed/json/';
        $items[] = '/sitemap.xml';
    }
    
    return $items;
}, 10, 2);
```

## CDN Asset Management

### Theme and Plugin Assets

Clear static assets when themes/plugins update:

```php
add_action('upgrader_process_complete', function($upgrader, $hook_extra) {
    if (isset($hook_extra['type'])) {
        $paths = [];
        
        if ($hook_extra['type'] === 'theme') {
            // Clear theme assets
            $theme_uri = get_template_directory_uri();
            $paths[] = str_replace(home_url(), '', $theme_uri) . '/style.css';
            $paths[] = str_replace(home_url(), '', $theme_uri) . '/js/*.js';
        }
        
        if ($hook_extra['type'] === 'plugin') {
            // Clear common plugin asset paths
            $paths[] = '/wp-content/plugins/*/assets/*';
            $paths[] = '/wp-content/cache/*';
        }
        
        if (!empty($paths)) {
            do_action('c3_invalidate_cache', $paths);
        }
    }
}, 10, 2);
```

### Version-Based Cache Busting

Invalidate versioned assets:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    // Add versioned assets
    $theme_version = wp_get_theme()->get('Version');
    $items[] = '/wp-content/themes/mytheme/style.css?v=' . $theme_version;
    $items[] = '/wp-content/themes/mytheme/script.js?v=' . $theme_version;
    
    return $items;
}, 10, 2);
```

## Performance Optimization

### Conditional Invalidation

Only invalidate during business hours:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    $current_hour = (int) current_time('H');
    $is_business_hours = ($current_hour >= 9 && $current_hour <= 17);
    
    if (!$is_business_hours) {
        // Queue for later processing instead of immediate invalidation
        wp_schedule_single_event(
            strtotime('tomorrow 9:00 AM'), 
            'c3_delayed_invalidation', 
            [$items]
        );
        return []; // Don't invalidate now
    }
    
    return $items;
}, 10, 2);

// Handle delayed invalidation
add_action('c3_delayed_invalidation', function($paths) {
    do_action('c3_invalidate_cache', $paths);
});
```

### Smart Batching

Optimize invalidation for high-traffic sites:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    // If too many paths, use wildcard
    if (count($items) > 50) {
        return ['/*'];
    }
    
    // Group similar paths
    $optimized = [];
    $path_groups = [];
    
    foreach ($items as $item) {
        $path_parts = explode('/', trim($item, '/'));
        $base_path = '/' . $path_parts[0] . '/';
        
        if (!isset($path_groups[$base_path])) {
            $path_groups[$base_path] = [];
        }
        $path_groups[$base_path][] = $item;
    }
    
    // Use wildcard for groups with many paths
    foreach ($path_groups as $base => $paths) {
        if (count($paths) > 10) {
            $optimized[] = $base . '*';
        } else {
            $optimized = array_merge($optimized, $paths);
        }
    }
    
    return array_unique($optimized);
}, 10, 2);
```

## Manual Invalidation Triggers

### Admin Interface Integration

Add custom invalidation buttons:

```php
add_action('post_submitbox_misc_actions', function() {
    global $post;
    if ($post && current_user_can('manage_options')) {
        ?>
        <div class="misc-pub-section">
            <label>
                <input type="checkbox" name="c3_force_full_invalidation" value="1">
                Clear all CloudFront cache
            </label>
        </div>
        <?php
    }
});

add_action('save_post', function($post_id) {
    if (isset($_POST['c3_force_full_invalidation']) && $_POST['c3_force_full_invalidation']) {
        do_action('c3_invalidate_cache', ['/*']);
    }
});
```

### Custom Meta Box

```php
add_action('add_meta_boxes', function() {
    add_meta_box(
        'c3-cache-control',
        'CloudFront Cache Control',
        'c3_cache_meta_box',
        ['post', 'page']
    );
});

function c3_cache_meta_box($post) {
    $custom_paths = get_post_meta($post->ID, '_c3_custom_paths', true);
    ?>
    <p>
        <label for="c3_custom_paths">Custom invalidation paths (one per line):</label><br>
        <textarea id="c3_custom_paths" name="c3_custom_paths" rows="5" cols="50"><?php echo esc_textarea($custom_paths); ?></textarea>
    </p>
    <p>
        <label>
            <input type="checkbox" name="c3_clear_all" value="1">
            Clear all cache when this post is updated
        </label>
    </p>
    <?php
}

add_action('save_post', function($post_id) {
    if (isset($_POST['c3_custom_paths'])) {
        update_post_meta($post_id, '_c3_custom_paths', sanitize_textarea_field($_POST['c3_custom_paths']));
    }
    
    if (isset($_POST['c3_clear_all']) && $_POST['c3_clear_all']) {
        update_post_meta($post_id, '_c3_clear_all_cache', 'yes');
    } else {
        delete_post_meta($post_id, '_c3_clear_all_cache');
    }
});
```

## Error Handling and Monitoring

### Custom Error Logging

```php
add_action('c3_invalidation_error', function($error, $paths) {
    // Log to custom log file
    error_log(sprintf(
        '[%s] C3 Invalidation Error: %s | Paths: %s',
        current_time('mysql'),
        $error->get_error_message(),
        implode(', ', $paths)
    ), 3, WP_CONTENT_DIR . '/c3-errors.log');
    
    // Send email alert for critical errors
    if ($error->get_error_code() === 'invalid_credentials') {
        wp_mail(
            get_option('admin_email'),
            'C3 CloudFront Error: Invalid Credentials',
            'The C3 plugin failed to invalidate cache due to invalid AWS credentials.'
        );
    }
}, 10, 2);
```

### Success Monitoring

```php
add_action('c3_invalidation_success', function($invalidation_id, $paths) {
    // Log successful invalidations
    error_log(sprintf(
        '[%s] C3 Invalidation Success: %s | Paths: %d | ID: %s',
        current_time('mysql'),
        'Cache invalidated successfully',
        count($paths),
        $invalidation_id
    ), 3, WP_CONTENT_DIR . '/c3-success.log');
}, 10, 2);
```

These examples provide a solid foundation for implementing C3 CloudFront Cache Controller in various scenarios. Remember to test any customizations in a staging environment before deploying to production.