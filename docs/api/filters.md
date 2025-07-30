# Filters API Reference

This page provides a comprehensive reference for all filters available in C3 CloudFront Cache Controller.

## Core Filters

### `c3_invalidation_items`

Customize the paths that get invalidated when content changes.

**Hook Type:** Filter  
**Since:** 1.0.0  
**Parameters:**
- `$items` (array) - Array of paths to invalidate
- `$post` (WP_Post|null) - The post object that triggered invalidation (if applicable)

**Return:** `array` - Modified array of paths to invalidate

**Example:**
```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_type === 'product') {
        $items[] = '/shop/';
        $items[] = '/products/';
    }
    return $items;
}, 10, 2);
```

---

### `c3_credential`

Override AWS credentials and settings programmatically.

**Hook Type:** Filter  
**Since:** 1.0.0  
**Parameters:**
- `$credentials` (array) - Current credential configuration

**Return:** `array` - Modified credential configuration

**Credential Array Structure:**
```php
[
    'key' => 'AWS_ACCESS_KEY_ID',
    'secret' => 'AWS_SECRET_ACCESS_KEY',
    'distribution_id' => 'CLOUDFRONT_DISTRIBUTION_ID',
    'timeout' => 30 // HTTP timeout in seconds
]
```

**Example:**
```php
add_filter('c3_credential', function($credentials) {
    if (wp_get_environment_type() === 'staging') {
        return [
            'key' => getenv('STAGING_AWS_ACCESS_KEY_ID'),
            'secret' => getenv('STAGING_AWS_SECRET_ACCESS_KEY'),
            'distribution_id' => getenv('STAGING_C3_DISTRIBUTION_ID'),
            'timeout' => 60
        ];
    }
    return $credentials;
});
```

---

### `c3_invalidation_interval`

Control the interval for processing invalidation batches.

**Hook Type:** Filter  
**Since:** 1.0.0  
**Parameters:**
- `$interval_minutes` (int) - Interval in minutes (default: 1)

**Return:** `int` - Modified interval in minutes

**Example:**
```php
add_filter('c3_invalidation_interval', function($interval_minutes) {
    // Process invalidations every 5 minutes
    return 5;
});
```

---

### `c3_invalidation_cron_interval`

Control the retry interval for failed invalidations.

**Hook Type:** Filter  
**Since:** 6.0.0  
**Parameters:**
- `$interval_minutes` (int) - Retry interval in minutes (default: 1)

**Return:** `int` - Modified retry interval in minutes

**Example:**
```php
add_filter('c3_invalidation_cron_interval', function($interval_minutes) {
    // Retry failed invalidations every 3 minutes
    return 3;
});
```

---

### `c3_invalidation_item_limits`

Control the number of paths processed per invalidation batch.

**Hook Type:** Filter  
**Since:** 1.0.0  
**Parameters:**
- `$limits` (int) - Number of paths per batch (default: 100)

**Return:** `int` - Modified batch size limit

**Note:** CloudFront has a maximum of 1000 paths per invalidation request.

**Example:**
```php
add_filter('c3_invalidation_item_limits', function($limits) {
    // Increase batch size for better performance
    return 500;
});
```

---

## Logging Filters

### `c3_log_invalidation_list`

Enable comprehensive invalidation logging.

**Hook Type:** Filter  
**Since:** 7.0.0  
**Parameters:**
- `$enable_logging` (bool) - Whether to enable logging (default: false)

**Return:** `bool` - Whether to enable logging

**Example:**
```php
// Enable logging for all environments
add_filter('c3_log_invalidation_list', '__return_true');

// Enable logging only for non-production
add_filter('c3_log_invalidation_list', function() {
    return wp_get_environment_type() !== 'production';
});
```

---

### `c3_log_cron_invalidation_task`

Enable logging for cron-based invalidation tasks.

**Hook Type:** Filter  
**Since:** 6.0.0  
**Parameters:**
- `$enable_logging` (bool) - Whether to enable cron logging (default: false)

**Return:** `bool` - Whether to enable cron logging

**Note:** This is a legacy filter. Use `c3_log_invalidation_list` for comprehensive logging.

**Example:**
```php
add_filter('c3_log_cron_invalidation_task', '__return_true');
```

---

## Post-Related Filters

### `c3_post_types`

Control which post types trigger automatic invalidation.

**Hook Type:** Filter  
**Since:** 2.0.0  
**Parameters:**
- `$post_types` (array) - Array of post type names (default: ['post', 'page'])

**Return:** `array` - Modified array of post type names

**Example:**
```php
add_filter('c3_post_types', function($post_types) {
    // Add custom post types
    $post_types[] = 'product';
    $post_types[] = 'event';
    
    return $post_types;
});
```

---

### `c3_post_status_transitions`

Control which post status transitions trigger invalidation.

**Hook Type:** Filter  
**Since:** 3.0.0  
**Parameters:**
- `$transitions` (array) - Array of status transition rules

**Return:** `array` - Modified transition rules

**Default Transitions:**
- `publish` → `*` (any status)
- `*` → `publish`
- `*` → `trash`

**Example:**
```php
add_filter('c3_post_status_transitions', function($transitions) {
    // Add custom transition for drafts
    $transitions['draft'] = ['publish', 'private'];
    
    return $transitions;
});
```

---

## HTTP and Network Filters

### `c3_http_request_args`

Modify HTTP request arguments for CloudFront API calls.

**Hook Type:** Filter  
**Since:** 7.0.0  
**Parameters:**
- `$args` (array) - HTTP request arguments
- `$url` (string) - Request URL
- `$operation` (string) - API operation name

**Return:** `array` - Modified HTTP request arguments

**Example:**
```php
add_filter('c3_http_request_args', function($args, $url, $operation) {
    // Increase timeout for specific operations
    if ($operation === 'create_invalidation') {
        $args['timeout'] = 120;
    }
    
    // Add custom headers
    $args['headers']['X-Custom-Header'] = 'value';
    
    return $args;
}, 10, 3);
```

---

### `c3_http_response_handler`

Custom handling of HTTP responses from CloudFront API.

**Hook Type:** Filter  
**Since:** 7.0.0  
**Parameters:**
- `$response_data` (array) - Parsed response data
- `$raw_response` (WP_HTTP_Response) - Raw HTTP response
- `$operation` (string) - API operation name

**Return:** `array` - Modified response data

**Example:**
```php
add_filter('c3_http_response_handler', function($response_data, $raw_response, $operation) {
    // Custom error handling
    if ($operation === 'create_invalidation' && isset($response_data['error'])) {
        // Log custom error details
        error_log('C3 Invalidation Error: ' . $response_data['error']);
    }
    
    return $response_data;
}, 10, 3);
```

---

## Error Handling Filters

### `c3_error_handler`

Custom error handling for CloudFront operations.

**Hook Type:** Filter  
**Since:** 4.0.0  
**Parameters:**
- `$handled` (bool) - Whether the error was handled
- `$error` (WP_Error) - Error object
- `$context` (array) - Error context information

**Return:** `bool` - Whether the error was handled

**Example:**
```php
add_filter('c3_error_handler', function($handled, $error, $context) {
    // Custom error logging
    if ($error->get_error_code() === 'aws_throttle') {
        // Handle throttling errors
        wp_schedule_single_event(time() + 300, 'c3_retry_invalidation', [$context['paths']]);
        return true; // Mark as handled
    }
    
    return $handled;
}, 10, 3);
```

---

### `c3_retry_logic`

Customize retry logic for failed invalidations.

**Hook Type:** Filter  
**Since:** 5.0.0  
**Parameters:**
- `$should_retry` (bool) - Whether to retry the operation
- `$error_code` (string) - Error code
- `$attempt_count` (int) - Current attempt number

**Return:** `bool` - Whether to retry the operation

**Example:**
```php
add_filter('c3_retry_logic', function($should_retry, $error_code, $attempt_count) {
    // Don't retry certain errors
    $no_retry_codes = ['invalid_credentials', 'distribution_not_found'];
    if (in_array($error_code, $no_retry_codes)) {
        return false;
    }
    
    // Limit retry attempts
    if ($attempt_count >= 5) {
        return false;
    }
    
    return $should_retry;
}, 10, 3);
```

---

## Cache and Performance Filters

### `c3_cache_invalidation_paths`

Pre-process invalidation paths before sending to CloudFront.

**Hook Type:** Filter  
**Since:** 6.0.0  
**Parameters:**
- `$paths` (array) - Array of paths to invalidate
- `$context` (string) - Context ('manual', 'automatic', 'cron')

**Return:** `array` - Processed array of paths

**Example:**
```php
add_filter('c3_cache_invalidation_paths', function($paths, $context) {
    // Remove duplicate paths
    $paths = array_unique($paths);
    
    // Normalize paths
    $paths = array_map(function($path) {
        return rtrim($path, '/') . '/';
    }, $paths);
    
    // Limit paths for automatic invalidations
    if ($context === 'automatic' && count($paths) > 50) {
        return ['/*']; // Use wildcard for large sets
    }
    
    return $paths;
}, 10, 2);
```

---

### `c3_batch_optimization`

Optimize invalidation batching logic.

**Hook Type:** Filter  
**Since:** 6.0.0  
**Parameters:**
- `$batches` (array) - Array of invalidation batches
- `$total_paths` (int) - Total number of paths to invalidate

**Return:** `array` - Optimized batch configuration

**Example:**
```php
add_filter('c3_batch_optimization', function($batches, $total_paths) {
    // Optimize batching based on total paths
    if ($total_paths > 1000) {
        // Use fewer, larger batches for big invalidations
        $batch_size = min(1000, ceil($total_paths / 5));
        return array_chunk($batches, $batch_size);
    }
    
    return $batches;
}, 10, 2);
```

---

## Deprecated Filters

::: warning Deprecated
These filters were deprecated in version 7.0.0 due to the removal of AWS SDK dependency.
:::

### `c3_aws_sdk_path` (Deprecated)

Previously used to customize AWS SDK path.

**Status:** Deprecated since 7.0.0  
**Replacement:** None (custom AWS implementation used)

### `c3_aws_client_config` (Deprecated)

Previously used to configure AWS SDK client.

**Status:** Deprecated since 7.0.0  
**Replacement:** Use `c3_credential` and `c3_http_request_args`

---

## Filter Usage Examples

### Complex Multi-Environment Setup

```php
class C3_Environment_Config {
    public function __construct() {
        add_filter('c3_credential', [$this, 'set_environment_credentials']);
        add_filter('c3_invalidation_items', [$this, 'customize_invalidation_paths'], 10, 2);
        add_filter('c3_log_invalidation_list', [$this, 'enable_logging']);
    }
    
    public function set_environment_credentials($credentials) {
        $env = wp_get_environment_type();
        
        $config = [
            'production' => [
                'key' => getenv('PROD_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('PROD_AWS_SECRET_ACCESS_KEY'),
                'distribution_id' => getenv('PROD_C3_DISTRIBUTION_ID'),
                'timeout' => 60
            ],
            'staging' => [
                'key' => getenv('STAGING_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('STAGING_AWS_SECRET_ACCESS_KEY'),
                'distribution_id' => getenv('STAGING_C3_DISTRIBUTION_ID'),
                'timeout' => 30
            ]
        ];
        
        return $config[$env] ?? $credentials;
    }
    
    public function customize_invalidation_paths($items, $post) {
        if (!$post) return $items;
        
        // Add environment-specific paths
        if (wp_get_environment_type() === 'production') {
            $items[] = '/sitemap.xml';
            $items[] = '/robots.txt';
        }
        
        return $items;
    }
    
    public function enable_logging() {
        return wp_get_environment_type() !== 'production';
    }
}

new C3_Environment_Config();
```

### Performance Optimization Suite

```php
class C3_Performance_Optimizer {
    public function __construct() {
        add_filter('c3_invalidation_item_limits', [$this, 'dynamic_batch_size']);
        add_filter('c3_invalidation_interval', [$this, 'adaptive_interval']);
        add_filter('c3_cache_invalidation_paths', [$this, 'optimize_paths'], 10, 2);
    }
    
    public function dynamic_batch_size($limits) {
        $server_load = sys_getloadavg()[0];
        
        if ($server_load > 2.0) {
            return 50; // Smaller batches under high load
        } elseif ($server_load < 0.5) {
            return 300; // Larger batches under low load
        }
        
        return $limits;
    }
    
    public function adaptive_interval($interval) {
        $hour = (int) current_time('H');
        $is_peak = ($hour >= 9 && $hour <= 17);
        
        return $is_peak ? 1 : 5; // More frequent during business hours
    }
    
    public function optimize_paths($paths, $context) {
        // Remove redundant paths
        if (in_array('/*', $paths)) {
            return ['/*']; // If wildcard exists, use only that
        }
        
        // Convert many specific paths to wildcard
        if (count($paths) > 100 && $context === 'automatic') {
            return ['/*'];
        }
        
        return array_unique($paths);
    }
}

new C3_Performance_Optimizer();
```

---

## Filter Priority Guidelines

When using multiple filters, consider execution order:

- **Priority 5**: Early modification (before other plugins)
- **Priority 10**: Default priority (most common)
- **Priority 15**: Late modification (after other plugins)
- **Priority 20**: Final processing

Example:
```php
// Early processing
add_filter('c3_invalidation_items', 'early_path_processor', 5);

// Default processing
add_filter('c3_invalidation_items', 'standard_path_processor', 10);

// Late processing
add_filter('c3_invalidation_items', 'final_path_processor', 20);
```