# Basic Usage

Once configured, C3 CloudFront Cache Controller automatically manages cache invalidation for your WordPress site. This guide covers the basic functionality and common use cases.

## Automatic Invalidation

The plugin automatically invalidates CloudFront cache in these scenarios:

### Post Status Changes

- **Publishing a post**: Invalidates the post URL and related archive pages
- **Updating a published post**: Invalidates the post URL
- **Trashing a post**: Invalidates the post URL and archives
- **Changing post status**: Invalidates relevant URLs based on the transition

### Supported Post Types

By default, the plugin works with:
- Posts (`post`)
- Pages (`page`)
- Custom post types (configurable)

## Manual Invalidation

### Via WordPress Admin

1. Go to **Settings > C3 CloudFront Cache**
2. Find the **Manual Invalidation** section
3. Enter paths to invalidate (one per line):
   ```
   /
   /about/
   /contact/
   /wp-content/themes/mytheme/style.css
   ```
4. Click **Invalidate Now**

### Wildcard Invalidation

To clear all cache:
```
/*
```

::: warning Cost Consideration
Wildcard invalidations (`/*`) count as clearing your entire cache and use more of your free invalidation quota. Use specific paths when possible.
:::

## What Gets Invalidated

When a post is published or updated, the plugin invalidates:

### Single Post
- The post permalink (e.g., `/my-post/`)
- The home page (`/`)
- Category archive pages
- Tag archive pages
- Author archive pages
- Date-based archive pages

### Example Invalidation Paths

For a post titled "My First Post" in category "News":
```
/my-first-post/
/
/category/news/
/author/john-doe/
/2024/
/2024/01/
/2024/01/15/
```

## Customizing Invalidation Paths

### Override All Paths

Replace all automatic invalidation paths:

```php
add_filter('c3_invalidation_items', function($items) {
    // Clear everything
    return array('/*');
});
```

### Add Custom Paths

Add additional paths to the automatic invalidation:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    // Add custom paths for specific posts
    if ($post && $post->post_type === 'product') {
        $items[] = '/shop/';
        $items[] = '/products/';
    }
    return $items;
}, 10, 2);
```

### Conditional Invalidation

Customize invalidation based on post properties:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_name === 'special-page') {
        // Only invalidate specific paths for this post
        return ['/special-page/', '/'];
    }
    return $items;
}, 10, 2);
```

## Batch Processing

The plugin handles large invalidation requests efficiently:

### How It Works

1. **Immediate Processing**: Up to 100 paths (configurable) are invalidated immediately
2. **Batch Processing**: Additional paths are queued for background processing
3. **Cron Jobs**: WordPress cron processes queued invalidations every minute

### Monitoring Batches

Check invalidation status:

1. Go to **Settings > C3 CloudFront Cache**
2. View the **Invalidation Logs** section
3. See recent invalidation requests and their status

## Performance Considerations

### Invalidation Limits

- **AWS CloudFront**: 1,000 paths per invalidation request
- **Plugin Default**: 100 paths per batch (configurable)
- **Free Tier**: 1,000 invalidations per month

### Optimizing Performance

```php
// Increase batch size for high-traffic sites
add_filter('c3_invalidation_item_limits', function($limits) {
    return 500; // Process more paths per batch
});

// Adjust invalidation frequency
add_filter('c3_invalidation_interval', function($interval_minutes) {
    return 5; // Process every 5 minutes instead of 1
});
```

## Common Use Cases

### E-commerce Sites

For WooCommerce or other e-commerce platforms:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_type === 'product') {
        $items[] = '/shop/';
        $items[] = '/cart/';
        $items[] = '/checkout/';
        
        // Clear category pages
        $categories = wp_get_post_terms($post->ID, 'product_cat');
        foreach ($categories as $category) {
            $items[] = get_term_link($category);
        }
    }
    return $items;
}, 10, 2);
```

### News/Blog Sites

For content-heavy sites with complex archive structures:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_type === 'post') {
        // Always clear homepage and main blog page
        $items[] = '/';
        $items[] = '/blog/';
        
        // Clear RSS feeds
        $items[] = '/feed/';
        $items[] = '/comments/feed/';
    }
    return $items;
}, 10, 2);
```

### Multilingual Sites

For WPML or Polylang sites:

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post) {
        // Get translations and invalidate all language versions
        $translations = pll_get_post_translations($post->ID);
        foreach ($translations as $lang => $translation_id) {
            if ($translation_id) {
                $items[] = get_permalink($translation_id);
            }
        }
    }
    return $items;
}, 10, 2);
```

## Debugging

### Enable Logging

```php
// Add to wp-config.php or theme functions.php
add_filter('c3_log_invalidation_list', '__return_true');
```

### Check Logs

1. Go to **Settings > C3 CloudFront Cache**
2. View the **Invalidation Logs** section
3. Look for error messages or failed invalidations

### Manual Testing

Test invalidation with WP-CLI:

```bash
wp c3 flush 1
```

## Next Steps

- Learn about [advanced filters and hooks](/guide/filters)
- Explore [WP-CLI commands](/guide/wp-cli)
- See [integration examples](/examples/integration)