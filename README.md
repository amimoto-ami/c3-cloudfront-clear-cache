# C3 Cloudfront Cache Controller

[![Build Status](https://travis-ci.org/amimoto-ami/c3-cloudfront-clear-cache.svg)](https://travis-ci.org/amimoto-ami/c3-cloudfront-clear-cache)
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
