# Filters & Hooks

C3 CloudFront Cache Controller provides powerful filters and hooks to customize invalidation behavior. This guide covers all available customization options.

## Core Filters

### `c3_invalidation_items`

Customize which paths get invalidated when content changes.

**Parameters:**
- `$items` (array): Array of paths to invalidate
- `$post` (WP_Post|null): The post object that triggered the invalidation

**Examples:**

```php
// Replace all invalidation paths
add_filter('c3_invalidation_items', function($items) {
    return array('/*'); // Clear entire cache
});

// Add custom paths
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_type === 'product') {
        $items[] = '/shop/';
        $items[] = '/products/featured/';
    }
    return $items;
}, 10, 2);

// Conditional invalidation
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_name === 'homepage-hero') {
        // Only invalidate homepage for this specific post
        return ['/'];
    }
    return $items;
}, 10, 2);
```

### `c3_credential`

Override AWS credentials programmatically.

**Parameters:**
- `$credentials` (array): Current credential configuration

**Example:**

```php
add_filter('c3_credential', function($credentials) {
    // Use different credentials for staging
    if (wp_get_environment_type() === 'staging') {
        return [
            'key' => 'staging_access_key',
            'secret' => 'staging_secret_key',
            'distribution_id' => 'staging_distribution_id',
            'timeout' => 30
        ];
    }
    return $credentials;
});
```

## Performance Filters

### `c3_invalidation_interval`

Control how often invalidation batches are processed.

**Parameters:**
- `$interval_minutes` (int): Interval in minutes (default: 1)

**Example:**

```php
// Process invalidations every 5 minutes
add_filter('c3_invalidation_interval', function($interval_minutes) {
    return 5;
});

// Dynamic interval based on site traffic
add_filter('c3_invalidation_interval', function($interval_minutes) {
    $current_hour = (int) current_time('H');
    
    // More frequent during business hours
    if ($current_hour >= 9 && $current_hour <= 17) {
        return 1; // Every minute
    } else {
        return 5; // Every 5 minutes
    }
});
```

### `c3_invalidation_cron_interval`

Control retry interval for failed invalidations.

**Parameters:**
- `$interval_minutes` (int): Retry interval in minutes (default: 1)

**Example:**

```php
// Retry failed invalidations every 3 minutes
add_filter('c3_invalidation_cron_interval', function($interval_minutes) {
    return 3;
});
```

### `c3_invalidation_item_limits`

Control how many paths are processed per invalidation batch.

**Parameters:**
- `$limits` (int): Number of paths per batch (default: 100)

**Example:**

```php
// Increase batch size for better performance
add_filter('c3_invalidation_item_limits', function($limits) {
    return 500;
});

// Dynamic limits based on server resources
add_filter('c3_invalidation_item_limits', function($limits) {
    $server_load = sys_getloadavg()[0];
    
    if ($server_load > 2.0) {
        return 50; // Smaller batches under high load
    } else {
        return 200; // Larger batches under normal load
    }
});
```

## Logging Filters

### `c3_log_invalidation_list`

Enable comprehensive invalidation logging.

**Example:**

```php
// Enable logging for all environments
add_filter('c3_log_invalidation_list', '__return_true');

// Enable logging only for staging/development
add_filter('c3_log_invalidation_list', function() {
    return in_array(wp_get_environment_type(), ['staging', 'development']);
});
```

### `c3_log_cron_invalidation_task`

Enable logging for cron-based invalidations (legacy).

**Example:**

```php
// Enable cron logging
add_filter('c3_log_cron_invalidation_task', '__return_true');
```

::: info Logging Recommendation
Use `c3_log_invalidation_list` instead of `c3_log_cron_invalidation_task` for comprehensive logging that covers all invalidation types.
:::

## Advanced Use Cases

### Multi-Environment Configuration

```php
add_filter('c3_credential', function($credentials) {
    $env = wp_get_environment_type();
    
    switch ($env) {
        case 'production':
            return [
                'key' => getenv('PROD_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('PROD_AWS_SECRET_ACCESS_KEY'),
                'distribution_id' => getenv('PROD_C3_DISTRIBUTION_ID'),
                'timeout' => 60
            ];
            
        case 'staging':
            return [
                'key' => getenv('STAGING_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('STAGING_AWS_SECRET_ACCESS_KEY'),
                'distribution_id' => getenv('STAGING_C3_DISTRIBUTION_ID'),
                'timeout' => 30
            ];
            
        default:
            // Development - disable invalidation
            return null;
    }
});
```

### Content-Type Specific Invalidation

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if (!$post) return $items;
    
    switch ($post->post_type) {
        case 'product':
            // E-commerce invalidation
            $items[] = '/shop/';
            $items[] = '/cart/';
            $items[] = '/api/products.json';
            
            // Clear related category pages
            $categories = wp_get_post_terms($post->ID, 'product_cat');
            foreach ($categories as $category) {
                $items[] = get_term_link($category);
            }
            break;
            
        case 'event':
            // Event invalidation
            $items[] = '/events/';
            $items[] = '/calendar/';
            $items[] = '/api/events.json';
            break;
            
        case 'portfolio':
            // Portfolio invalidation
            $items[] = '/portfolio/';
            $items[] = '/work/';
            break;
    }
    
    return $items;
}, 10, 2);
```

### API Endpoint Invalidation

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if (!$post) return $items;
    
    // Always invalidate API endpoints
    $api_endpoints = [
        '/wp-json/wp/v2/posts',
        '/wp-json/wp/v2/pages',
        '/api/v1/content.json',
        '/sitemap.xml',
        '/feed/'
    ];
    
    $items = array_merge($items, $api_endpoints);
    
    return $items;
}, 10, 2);
```

### Conditional Invalidation Based on Post Meta

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if (!$post) return $items;
    
    // Check if post should trigger full cache clear
    $clear_all_cache = get_post_meta($post->ID, '_c3_clear_all_cache', true);
    if ($clear_all_cache === 'yes') {
        return ['/*'];
    }
    
    // Check for custom invalidation paths
    $custom_paths = get_post_meta($post->ID, '_c3_custom_paths', true);
    if (!empty($custom_paths)) {
        $custom_paths = explode("\n", $custom_paths);
        $custom_paths = array_map('trim', $custom_paths);
        $items = array_merge($items, $custom_paths);
    }
    
    return $items;
}, 10, 2);
```

### Multilingual Site Support

```php
// For WPML
add_filter('c3_invalidation_items', function($items, $post) {
    if (!$post || !function_exists('icl_get_languages')) return $items;
    
    // Get all language versions
    $translations = icl_get_languages('skip_missing=0');
    foreach ($translations as $lang) {
        $translated_id = icl_object_id($post->ID, $post->post_type, false, $lang['code']);
        if ($translated_id && $translated_id !== $post->ID) {
            $items[] = get_permalink($translated_id);
        }
    }
    
    return $items;
}, 10, 2);

// For Polylang
add_filter('c3_invalidation_items', function($items, $post) {
    if (!$post || !function_exists('pll_get_post_translations')) return $items;
    
    // Get all translations
    $translations = pll_get_post_translations($post->ID);
    foreach ($translations as $lang => $translation_id) {
        if ($translation_id && $translation_id !== $post->ID) {
            $items[] = get_permalink($translation_id);
        }
    }
    
    return $items;
}, 10, 2);
```

## Performance Optimization

### Smart Batching

```php
add_filter('c3_invalidation_item_limits', function($limits) {
    // Adjust batch size based on server performance
    $memory_limit = ini_get('memory_limit');
    $memory_mb = (int) $memory_limit;
    
    if ($memory_mb >= 512) {
        return 300; // Larger batches for high-memory servers
    } elseif ($memory_mb >= 256) {
        return 200;
    } else {
        return 100; // Default for smaller servers
    }
});
```

### Time-Based Intervals

```php
add_filter('c3_invalidation_interval', function($interval_minutes) {
    $current_time = current_time('timestamp');
    $is_peak_hours = (
        date('H', $current_time) >= 9 && 
        date('H', $current_time) <= 17 && 
        date('N', $current_time) < 6 // Weekdays
    );
    
    return $is_peak_hours ? 1 : 5; // More frequent during business hours
});
```

## Debugging and Monitoring

### Enhanced Logging

```php
add_filter('c3_log_invalidation_list', function() {
    // Enable detailed logging in non-production
    return wp_get_environment_type() !== 'production';
});

// Custom logging function
add_action('c3_after_invalidation', function($paths, $result) {
    if (function_exists('error_log')) {
        error_log(sprintf(
            '[C3] Invalidated %d paths. Success: %s',
            count($paths),
            $result ? 'Yes' : 'No'
        ));
    }
}, 10, 2);
```

## Filter Priority and Execution Order

When using multiple filters, consider execution order:

```php
// High priority - executes first
add_filter('c3_invalidation_items', 'my_early_filter', 5);

// Default priority
add_filter('c3_invalidation_items', 'my_normal_filter', 10);

// Low priority - executes last
add_filter('c3_invalidation_items', 'my_late_filter', 20);
```

## Migration from Deprecated Filters

If you're upgrading from an older version:

```php
// OLD (deprecated since v7.0.0)
add_filter('c3_aws_sdk_path', function($path) {
    return '/custom/aws-sdk/path';
});

// NEW (no longer needed - custom AWS implementation)
// Remove the above filter - it's no longer used
```

## Best Practices

1. **Test in staging** before deploying filter changes to production
2. **Use specific paths** instead of wildcards when possible
3. **Monitor CloudFront quotas** to avoid unexpected charges
4. **Enable logging** during development and debugging
5. **Consider performance impact** of complex filter logic
6. **Use appropriate priorities** when combining multiple filters

## Next Steps

- Learn about [WP-CLI commands](/guide/wp-cli)
- See [practical examples](/examples/custom-invalidation)
- Review [troubleshooting tips](/guide/troubleshooting)