# Installation

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **AWS CloudFront**: Active distribution
- **Permalink Structure**: Must be set to anything other than "Plain"
- **WP-CLI**: Optional but recommended for command-line installation

::: warning Important
The plugin requires WordPress permalink settings to be set to something other than "Plain" for proper operation. This is because plain permalinks use query parameters (`?p=123`) which cannot be effectively invalidated in CloudFront.
:::

## Installation Methods

### Method 1: WordPress Admin (Recommended)

1. Go to your WordPress admin dashboard
2. Navigate to **Plugins > Add New**
3. Search for "C3 CloudFront Clear Cache"
4. Click **Install Now** and then **Activate**

### Method 2: WP-CLI

Install and activate the plugin using WP-CLI:

```bash
# Install and activate in one command
wp plugin install c3-cloudfront-clear-cache --activate

# Or install first, then activate separately
wp plugin install c3-cloudfront-clear-cache
wp plugin activate c3-cloudfront-clear-cache
```

### Method 3: Manual Upload

1. Download the plugin from [WordPress.org](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)
2. Upload the zip file via **Plugins > Add New > Upload Plugin**
3. Activate the plugin

## Development Installation

For developers and AI-assisted workflows only:

### Git Clone (Development)

```bash
cd /path/to/wordpress/wp-content/plugins
git clone https://github.com/amimoto-ami/c3-cloudfront-clear-cache.git
cd c3-cloudfront-clear-cache
```

### Composer (Development)

```bash
composer require amimoto-ami/c3-cloudfront-clear-cache
```

## Verification

After installation, verify the plugin is working:

1. Go to **Settings > C3 CloudFront Cache** in your WordPress admin
2. You should see the configuration page
3. Check that all required fields are displayed

### WP-CLI Verification

You can also verify the installation using WP-CLI:

```bash
# Check if plugin is active
wp plugin list | grep c3-cloudfront-clear-cache

# Check plugin status
wp plugin status c3-cloudfront-clear-cache
```

## Next Steps

Once installed, you'll need to [configure the plugin](/guide/configuration) with your AWS credentials and CloudFront distribution ID.

## Troubleshooting Installation

### Plugin Not Appearing

If the plugin doesn't appear in your admin:

1. Check file permissions (directories should be `755`, files should be `644`)
2. Ensure all plugin files were uploaded correctly
3. Check for PHP errors in your error logs

### Activation Errors

If you encounter errors during activation:

1. Verify your PHP version meets the minimum requirement (7.4+)
2. Check for conflicting plugins
3. Ensure WordPress meets the minimum version requirement (5.0+)

### Permalink Structure Warning

If you see warnings about permalink structure:

1. Go to **Settings > Permalinks**
2. Select any structure other than "Plain"
3. Click "Save Changes"

For more detailed troubleshooting, see the [Troubleshooting Guide](/guide/troubleshooting).