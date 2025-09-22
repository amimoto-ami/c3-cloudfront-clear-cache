# C3 Cloudfront Cache Controller

[![](https://img.shields.io/wordpress/plugin/dt/c3-cloudfront-clear-cache.svg)](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)
[![](https://img.shields.io/wordpress/v/c3-cloudfront-clear-cache.svg)](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)
[![](https://img.shields.io/wordpress/plugin/r/c3-cloudfront-clear-cache.svg)](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)

This is simple plugin that clear all cloudfront cache if you publish posts.

## How to install
```
$ cd /path/to/wordpress/wp-content/plugins
$ git clone git@github.com:amimoto-ami/c3-cloudfront-clear-cache.git
$ cd c3-cloudfront-clear-cache
```

## Adding your configuration through env vars

The plugin can be configured by defining the following environment variables:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `C3_DISTRIBUTION_ID`
- `C3_HTTP_TIMEOUT` (optional) - HTTP timeout in seconds (default: 30)

## Filters

### Change Invalidation interval

Default interval is 1 minutes.

```
add_filter('c3_invalidation_interval', function( $interval_minutes ) {
    $custom_interval = 1;
    return $custom_interval;
} );
```

### Change Invalidation retry interval

Default interval is 1 minutes.

```
add_filter('c3_invalidation_cron_interval', function( $interval_minutes ) {
    $custom_interval = 1;
    return $custom_interval;
} );
```

### Change Invalidation items limit

Default limit is 100.

```
add_filter( 'c3_invalidation_item_limits', function( $limits ) {
    $custom_limit = 300;
    return $custom_limit;
} );
```

### Customize/Overwrite the invalidation path

Using the `c3_invalidation_items` filter, we can update the invalidation path.

```php
add_filter( 'c3_invalidation_items', function($items){  
        return array('/*'); 
});
```

```php
add_filter( 'c3_invalidation_items', function( $items, $post ) {
    if ( 'should-overwritten' === $post->post_name) {
        return ['/slug-overwritten'];
    }
    return $items;
}, 10, 2 );
```

### New Path Adjustment Hooks (v7.2.0+)

For more specific control over path invalidation, use these new hooks:

#### Customize home path for single post invalidation
```php
add_filter( 'c3_invalidation_post_batch_home_path', function( $home_path, $post ) {
    if ( $post && $post->post_type === 'product' ) {
        return '/shop/'; // Invalidate shop page instead of home
    }
    return $home_path;
}, 10, 2 );
```

#### Customize home path for multiple posts invalidation
```php
add_filter( 'c3_invalidation_posts_batch_home_path', function( $home_path, $posts ) {
    if ( count( $posts ) > 5 ) {
        return '/'; // Use root path for large bulk operations
    }
    return $home_path;
}, 10, 2 );
```

#### Customize path for manual "clear all" operations
```php
add_filter( 'c3_invalidation_manual_batch_all_path', function( $all_path ) {
    return '/content/*'; // Only clear content directories
});
```

### Custom Implementation

This plugin now uses a custom AWS CloudFront implementation instead of the official AWS SDK to reduce dependencies and improve performance.

### Logging cron job history(Since v6.0.0)

```
add_filter( 'c3_log_cron_invalidation_task', '__return_true' );
```

### Comprehensive invalidation logging(Since v7.0.0)

Log all invalidation operations (manual, automatic, and cron-based) with detailed information.

```
add_filter( 'c3_log_invalidation_list', '__return_true' );
```

This filter provides more comprehensive logging compared to `c3_log_cron_invalidation_task` and covers all types of invalidation operations.

## Deprecated Features

The following features are deprecated since v7.0.0 due to the removal of AWS SDK dependency:

### AWS SDK related filters
- `c3_aws_sdk_path` - This filter is no longer needed as the plugin no longer uses AWS SDK
- Any custom AWS SDK path configurations should be removed

### AWS SDK autoloader
- The plugin no longer includes or requires AWS SDK autoloader
- Remove any custom AWS SDK autoloader configurations

## Local Development

### Using wp-env for Development

wp-env provides a Docker-based WordPress development environment that closely mirrors production. This is recommended for comprehensive development, testing, and debugging.

#### Prerequisites
- Node.js (v14 or higher)
- Docker Desktop

#### Setup and Usage

```bash
# Install dependencies
$ npm install

# Start the development environment
$ npm run dev

# Run unit tests
$ npm run test

# Stop the environment
$ npm run wpenv stop
```

#### Configuration
The wp-env environment uses the configuration in `.wp-env.json`:
- PHP 8.2
- WordPress core (latest)
- Plugin directory: `c3-cloudfront-clear-cache`
- Test environment port: 8889

#### Environment Variables
Set up your AWS credentials for testing:

```bash
# In your shell or .env file
export AWS_ACCESS_KEY_ID="your_access_key"
export AWS_SECRET_ACCESS_KEY="your_secret_key"
export C3_DISTRIBUTION_ID="your_distribution_id"
```

### Quick Testing with wp-now

wp-now is a lightweight WordPress development environment that runs in your browser using WebAssembly. It's perfect for quick plugin testing and basic functionality verification.

#### Prerequisites
- Node.js (v18 or higher)

#### Setup and Usage

```bash
# Install wp-now globally
$ npm install -g @wp-now/wp-now

# Navigate to your plugin directory
$ cd /path/to/c3-cloudfront-clear-cache

# Start wp-now (will create a .env file if it doesn't exist)
$ wp-now start

# Open your browser to http://localhost:8888
```


#### Limitations
- No PHPUnit testing support
- Limited to basic WordPress functionality testing
- AWS API calls may be restricted (use mock data for testing)

#### When to Use wp-now
- Quick plugin activation testing
- Basic functionality verification
- UI/UX testing
- Rapid prototyping

#### When to Use wp-env
- Full development workflow
- Unit testing with PHPUnit
- Production-like environment testing
- Comprehensive debugging

### GitHub Action(WIP)
Using act to execute the workflow in your local.

```bash
$ act -P ubuntu-latest=shivammathur/node:latest
```
