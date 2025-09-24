# Filters & Hooks API Reference

C3 CloudFront Cache Controller provides powerful filters and hooks to customize invalidation behavior. This comprehensive reference covers all available customization options for developers.

## Core Filters

### `c3_invalidation_items`

Customize which paths get invalidated when content changes.

**Hook Type:** Filter  
**Since:** 1.0.0  
**Parameters:**
- `$items` (array): Array of paths to invalidate
- `$post` (WP_Post|null): The post object that triggered the invalidation

**Return:** `array` - Modified array of paths to invalidate

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

### `c3_invalidation_post_batch_home_path`

Customize the home path when invalidating a single post.

**Hook Type:** Filter  
**Since:** 7.2.0  
**Parameters:**
- `$home_path` (string): The home URL/path to be invalidated
- `$post` (WP_Post|null): The post object that triggered the invalidation

**Return:** `string` - Modified home path

**Examples:**

```php
// Use different home path for specific post types
add_filter('c3_invalidation_post_batch_home_path', function($home_path, $post) {
    if ($post && $post->post_type === 'product') {
        return '/shop/'; // Invalidate shop page instead of home
    }
    return $home_path;
}, 10, 2);

// Skip home invalidation for draft posts
add_filter('c3_invalidation_post_batch_home_path', function($home_path, $post) {
    if ($post && $post->post_status === 'draft') {
        return null; // Skip home invalidation
    }
    return $home_path;
}, 10, 2);
```

### `c3_invalidation_posts_batch_home_path`

Customize the home path when invalidating multiple posts.

**Hook Type:** Filter  
**Since:** 7.2.0  
**Parameters:**
- `$home_path` (string): The home URL/path to be invalidated
- `$posts` (array): Array of WP_Post objects being invalidated

**Return:** `string` - Modified home path

**Examples:**

```php
// Use different home path for bulk operations
add_filter('c3_invalidation_posts_batch_home_path', function($home_path, $posts) {
    if (count($posts) > 5) {
        return '/'; // Use root path for large bulk operations
    }
    return $home_path;
}, 10, 2);

// Custom path based on post types in batch
add_filter('c3_invalidation_posts_batch_home_path', function($home_path, $posts) {
    $post_types = array_unique(array_column($posts, 'post_type'));
    if (in_array('product', $post_types)) {
        return '/shop/';
    }
    return $home_path;
}, 10, 2);
```

### `c3_invalidation_manual_batch_all_path`

Customize the path for manual "clear all cache" operations.

**Hook Type:** Filter  
**Since:** 7.2.0  
**Parameters:**
- `$all_path` (string): The path pattern for clearing all cache (default: '/*')

**Return:** `string` - Modified path pattern

**Examples:**

```php
// Use more specific path for manual clear all
add_filter('c3_invalidation_manual_batch_all_path', function($all_path) {
    // Only clear content directories instead of everything
    return '/content/*';
});

// Environment-specific clear all behavior
add_filter('c3_invalidation_manual_batch_all_path', function($all_path) {
    if (wp_get_environment_type() === 'staging') {
        return '/staging/*';
    }
    return $all_path;
});
```

### `c3_credential`

Override AWS credentials programmatically.

**Hook Type:** Filter  
**Since:** 1.0.0  
**Parameters:**
- `$credentials` (array): Current credential configuration

**Return:** `array` - Modified credential configuration

**Credential Array Structure:**
```php
[
    'key' => 'AWS_ACCESS_KEY_ID',
    'secret' => 'AWS_SECRET_ACCESS_KEY',
    'token' => 'AWS_SESSION_TOKEN' // Optional
]
```

**Example:**

```php
add_filter('c3_credential', function($credentials) {
    // Use different credentials for staging
    if (wp_get_environment_type() === 'staging') {
        return [
            'key' => getenv('STAGING_AWS_ACCESS_KEY_ID'),
            'secret' => getenv('STAGING_AWS_SECRET_ACCESS_KEY'),
            'token' => getenv('STAGING_AWS_SESSION_TOKEN')
        ];
    }
    return $credentials;
});
```

## Performance Filters

### `c3_invalidation_interval`

Control how often invalidation batches are processed.

**Hook Type:** Filter  
**Since:** 1.0.0  
**Parameters:**
- `$interval_minutes` (int): Interval in minutes (default: 1)

**Return:** `int` - Modified interval in minutes

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

**Hook Type:** Filter  
**Since:** 6.0.0  
**Parameters:**
- `$interval_minutes` (int): Retry interval in minutes (default: 1 for invalidation, 10 for cron)

**Return:** `int` - Modified retry interval in minutes

**Example:**

```php
// Retry failed invalidations every 15 minutes
add_filter('c3_invalidation_cron_interval', function($interval_minutes) {
    return 15;
});
```

## Advanced Filters

### `c3_invalidation_batch_size`

Control the maximum number of paths per invalidation batch.

**Hook Type:** Filter  
**Since:** 3.0.0  
**Parameters:**
- `$batch_size` (int): Maximum paths per batch (default: 1000)

**Return:** `int` - Modified batch size

**Example:**

```php
// Reduce batch size for faster processing
add_filter('c3_invalidation_cron_interval', function($batch_size) {
    return 500;
});
```

### `c3_invalidation_timeout`

Control AWS API request timeout.

**Hook Type:** Filter  
**Since:** 4.0.0  
**Parameters:**
- `$timeout` (int): Timeout in seconds (default: 30)

**Return:** `int` - Modified timeout

**Example:**

```php
// Increase timeout for slow connections
add_filter('c3_invalidation_timeout', function($timeout) {
    return 60;
});
```

### `c3_invalidation_retry_attempts`

Control the number of retry attempts for failed invalidations.

**Hook Type:** Filter  
**Since:** 5.0.0  
**Parameters:**
- `$retry_attempts` (int): Number of retry attempts (default: 3)

**Return:** `int` - Modified retry attempts

**Example:**

```php
// Increase retry attempts for reliability
add_filter('c3_invalidation_retry_attempts', function($retry_attempts) {
    return 5;
});
```

## Action Hooks

### `c3_before_invalidation`

Fired before an invalidation request is sent to CloudFront.

**Hook Type:** Action  
**Since:** 2.0.0  
**Parameters:**
- `$paths` (array): Array of paths to invalidate
- `$post_id` (int|null): Post ID that triggered invalidation

**Example:**

```php
add_action('c3_before_invalidation', function($paths, $post_id) {
    // Log invalidation request
    error_log("Invalidating paths: " . implode(', ', $paths));
    
    // Send notification
    if ($post_id) {
        wp_mail('admin@example.com', 'Cache Invalidation', "Invalidating cache for post ID: $post_id");
    }
}, 10, 2);
```

### `c3_after_invalidation`

Fired after an invalidation request is successfully sent to CloudFront.

**Hook Type:** Action  
**Since:** 2.0.0  
**Parameters:**
- `$paths` (array): Array of paths that were invalidated
- `$invalidation_id` (string): CloudFront invalidation ID
- `$post_id` (int|null): Post ID that triggered invalidation

**Example:**

```php
add_action('c3_after_invalidation', function($paths, $invalidation_id, $post_id) {
    // Log successful invalidation
    error_log("Invalidation successful. ID: $invalidation_id");
    
    // Update custom tracking
    update_option('last_cache_invalidation', [
        'timestamp' => current_time('mysql'),
        'invalidation_id' => $invalidation_id,
        'paths_count' => count($paths)
    ]);
}, 10, 3);
```

### `c3_invalidation_failed`

Fired when an invalidation request fails.

**Hook Type:** Action  
**Since:** 2.0.0  
**Parameters:**
- `$paths` (array): Array of paths that failed to invalidate
- `$error` (string): Error message
- `$post_id` (int|null): Post ID that triggered invalidation

**Example:**

```php
add_action('c3_invalidation_failed', function($paths, $error, $post_id) {
    // Log error
    error_log("Cache invalidation failed: $error");
    
    // Send alert
    wp_mail('admin@example.com', 'Cache Invalidation Failed', 
        "Failed to invalidate cache for paths: " . implode(', ', $paths) . "\nError: $error");
}, 10, 3);
```

## WordPress Subdirectory Installation Support

The new path adjustment hooks (`c3_invalidation_post_batch_home_path`, `c3_invalidation_posts_batch_home_path`, and `c3_invalidation_manual_batch_all_path`) fully support WordPress installations in subdirectories.

### How Subdirectory Support Works

When WordPress is installed in a subdirectory (e.g., `https://example.com/blog/`), the plugin automatically handles subdirectory paths through WordPress's standard `home_url()` function:

**Normal Installation:**
```
WordPress URL: https://example.com/
home_url('/') → https://example.com/
```

**Subdirectory Installation:**
```
WordPress URL: https://example.com/blog/
home_url('/') → https://example.com/blog/
```

### Path Generation Logic

The plugin uses `parse_url()` to extract path components from URLs, which automatically includes subdirectory paths:

```php
// In Invalidation_Batch.php
public function make_invalidate_path( $url ) {
    $parse_url = parse_url( $url );
    return isset( $parse_url['path'] )
        ? $parse_url['path']  // Includes subdirectory
        : preg_replace( array( '#^https?://[^/]*#', '#\?.*$#' ), '', $url );
}
```

### Subdirectory Examples

#### Example 1: Custom Home Path for Subdirectory Installation

```php
// WordPress installed at https://example.com/blog/
add_filter('c3_invalidation_post_batch_home_path', function($home_path, $post) {
    // $home_path automatically contains "/blog/"
    
    if ($post && $post->post_type === 'product') {
        return '/blog/shop/'; // Subdirectory + custom path
    }
    return $home_path; // Default: /blog/
}, 10, 2);
```

#### Example 2: Manual Clear All with Subdirectory Restriction

```php
// Only clear cache within the WordPress subdirectory
add_filter('c3_invalidation_manual_batch_all_path', function($all_path) {
    // Restrict clearing to subdirectory only
    return '/blog/*'; // Only invalidate /blog/* paths
});
```

#### Example 3: Environment-Specific Subdirectory Handling

```php
add_filter('c3_invalidation_posts_batch_home_path', function($home_path, $posts) {
    // Handle different subdirectories per environment
    $environment = wp_get_environment_type();
    
    switch ($environment) {
        case 'staging':
            return '/staging/blog/';
        case 'development':
            return '/dev/blog/';
        default:
            return $home_path; // Production subdirectory
    }
}, 10, 2);
```

### Testing Subdirectory Support

To test subdirectory functionality, you can simulate a subdirectory installation:

```php
// Test case for subdirectory support
public function test_subdirectory_installation_support() {
    // Mock subdirectory home URL
    add_filter('home_url', function($url) {
        return 'https://example.com/blog/';
    });
    
    add_filter('c3_invalidation_post_batch_home_path', function($home_path, $post) {
        // Verify subdirectory path is included
        return $home_path; // Should be /blog/
    }, 10, 2);
    
    $post = $this->factory->post->create_and_get();
    $target = new AWS\Invalidation_Batch_Service();
    $result = $target->create_batch_by_post('https://example.com/blog/', 'EXXXX', $post);
    
    // Assert subdirectory path is present
    $this->assertContains('/blog/', $result['InvalidationBatch']['Paths']['Items']);
}
```

### Key Benefits for Subdirectory Installations

1. **Automatic Path Detection**: No manual configuration needed
2. **Flexible Customization**: Hooks allow fine-tuned control over subdirectory paths
3. **Environment Compatibility**: Works seamlessly across different deployment scenarios
4. **Backward Compatibility**: Existing `c3_invalidation_items` filter continues to work

## Debug Settings

### Debug Settings Migration (v7.3.0)

As of version 7.3.0, debug settings have been moved from filter-based configuration to WordPress admin settings for better user experience and easier management.

#### Before (v7.2.0 and earlier)

Debug settings were controlled via filters:

```php
// Enable cron job logging
add_filter('c3_log_cron_register_task', '__return_true');

// Enable invalidation parameter logging
add_filter('c3_log_invalidation_params', '__return_true');
```

#### After (v7.3.0 and later)

Debug settings are now managed through WordPress admin:

1. Go to **Settings > Reading** in WordPress admin
2. Scroll to **C3 CloudFront Debug Settings**
3. Enable the desired debug options

#### New Constants

The following constants are available for programmatic access to debug settings:

```php
// Debug settings option name
C3_CloudFront_Cache_Controller\Constants::DEBUG_OPTION_NAME

// Cron logging setting key
C3_CloudFront_Cache_Controller\Constants::DEBUG_LOG_CRON_REGISTER_TASK

// Invalidation logging setting key
C3_CloudFront_Cache_Controller\Constants::DEBUG_LOG_INVALIDATION_PARAMS
```

#### Programmatic Debug Settings Access

You can still access debug settings programmatically:

```php
// Get debug settings
$debug_options = get_option(C3_CloudFront_Cache_Controller\Constants::DEBUG_OPTION_NAME, array());

// Check if cron logging is enabled
$cron_logging_enabled = isset($debug_options[C3_CloudFront_Cache_Controller\Constants::DEBUG_LOG_CRON_REGISTER_TASK]) 
    ? $debug_options[C3_CloudFront_Cache_Controller\Constants::DEBUG_LOG_CRON_REGISTER_TASK] 
    : false;

// Check if invalidation logging is enabled
$invalidation_logging_enabled = isset($debug_options[C3_CloudFront_Cache_Controller\Constants::DEBUG_LOG_INVALIDATION_PARAMS]) 
    ? $debug_options[C3_CloudFront_Cache_Controller\Constants::DEBUG_LOG_INVALIDATION_PARAMS] 
    : false;
```

#### Backward Compatibility

The old filter-based debug settings still work for backward compatibility, but the admin settings take precedence:

```php
// This still works but admin settings override it
add_filter('c3_log_cron_register_task', '__return_true');

// Admin setting value takes precedence over filter
$final_value = apply_filters('c3_log_cron_register_task', $admin_setting_value);
```

### Debug Logging Filters

#### `c3_log_cron_register_task`

Control cron job logging (legacy filter, now managed via admin settings).

**Hook Type:** Filter  
**Since:** 1.0.0  
**Parameters:**
- `$enabled` (bool): Whether cron logging is enabled

**Return:** `bool` - Whether to enable cron logging

#### `c3_log_invalidation_params`

Control invalidation parameter logging (legacy filter, now managed via admin settings).

**Hook Type:** Filter  
**Since:** 1.0.0  
**Parameters:**
- `$enabled` (bool): Whether invalidation logging is enabled

**Return:** `bool` - Whether to enable invalidation logging

## Best Practices

### 1. Performance Considerations

- Keep invalidation paths minimal and specific
- Use conditional logic to avoid unnecessary invalidations
- Consider using wildcards (`/*`) sparingly

### 2. Error Handling

- Always implement proper error handling in your filter callbacks
- Use try-catch blocks for external API calls
- Log errors for debugging

### 3. Security

- Validate and sanitize all data in your filters
- Use nonces for admin-only filters
- Implement proper capability checks

### 4. Testing

- Test your filters in a staging environment first
- Monitor CloudFront invalidation costs
- Use the `c3_before_invalidation` and `c3_after_invalidation` hooks for debugging

### 5. Debug Settings

- Use the WordPress admin interface for debug settings when possible
- Programmatic access is available for advanced use cases
- Consider the admin settings as the source of truth for debug configuration

## Complete Example

Here's a complete example showing how to implement custom invalidation logic:

```php
<?php
/**
 * Custom CloudFront cache invalidation for e-commerce site
 */

// Custom invalidation paths for products
add_filter('c3_invalidation_items', function($items, $post) {
    if (!$post) {
        return $items;
    }
    
    // Add category pages for products
    if ($post->post_type === 'product') {
        $categories = get_the_terms($post->ID, 'product_cat');
        if ($categories && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $items[] = '/category/' . $category->slug . '/';
            }
        }
        
        // Add shop page
        $items[] = '/shop/';
    }
    
    // Add homepage for featured posts
    if (has_post_thumbnail($post->ID) && get_post_meta($post->ID, '_featured', true)) {
        $items[] = '/';
    }
    
    return $items;
}, 10, 2);

// Environment-specific credentials
add_filter('c3_credential', function($credentials) {
    $environment = wp_get_environment_type();
    
    switch ($environment) {
        case 'production':
            return [
                'key' => getenv('PROD_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('PROD_AWS_SECRET_ACCESS_KEY'),
                'distribution_id' => getenv('PROD_CLOUDFRONT_DISTRIBUTION_ID')
            ];
        case 'staging':
            return [
                'key' => getenv('STAGING_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('STAGING_AWS_SECRET_ACCESS_KEY'),
                'distribution_id' => getenv('STAGING_CLOUDFRONT_DISTRIBUTION_ID')
            ];
        default:
            return $credentials;
    }
});

// Log all invalidations
add_action('c3_before_invalidation', function($paths, $post_id) {
    $log_entry = [
        'timestamp' => current_time('mysql'),
        'post_id' => $post_id,
        'paths' => $paths,
        'user_id' => get_current_user_id()
    ];
    
    // Store in transient for debugging
    set_transient('c3_invalidation_log_' . time(), $log_entry, HOUR_IN_SECONDS);
}, 10, 2);
```

This comprehensive reference provides all the tools you need to customize C3 CloudFront Cache Controller for your specific use case.    