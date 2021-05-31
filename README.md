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

### Change or Stop loading bundled AWS SDK (Since v6.0.0)

Use `c3_aws_sdk_path` filter, to relace the AWS SDK library path.

```
add_filter( 'c3_aws_sdk_path', function () {
	return 'YOUR/CUSTOM/AWS/SDK/ENTRY/POINT.php';
} );
```

If return null, the plugin will not load bundled AWS SDK.

```
add_filter( 'c3_aws_sdk_path', function () {
	return null;
} );
```

### Logging cron job history(Since v6.0.0)

```
add_filter( 'c3_log_cron_invalidation_task', '__return_true' );
```

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
