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

## Local testing

### wp-env

#### Unit test

```bash
$ yarn dev
$ yarn test
```

### GitHub Action(WIP)
Using act to execute the workflow in your local.

```bash
$ act -P ubuntu-latest=shivammathur/node:latest
```
