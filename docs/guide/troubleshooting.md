# Troubleshooting

This guide helps you diagnose and resolve common issues with C3 CloudFront Cache Controller.

## Common Issues

### Plugin Not Working

#### Symptoms
- No cache invalidation occurs when publishing posts
- Admin interface shows no activity
- No error messages

#### Diagnosis
1. Check plugin activation:
   ```bash
   wp plugin list | grep c3-cloudfront
   ```

2. Verify WordPress permalink structure:
   - Go to **Settings > Permalinks**
   - Ensure it's NOT set to "Plain"

3. Check PHP error logs:
   ```bash
   tail -f /path/to/wp-content/debug.log
   ```

#### Solutions

**Permalink Structure Issue:**
```php
// Add this to wp-config.php to check current structure
echo get_option('permalink_structure');

// If empty, the issue is plain permalinks
```

**Plugin Conflicts:**
```bash
# Test with minimal plugins
wp plugin deactivate --all
wp plugin activate c3-cloudfront-clear-cache
```

**PHP Version:**
```bash
# Check PHP version (minimum 7.4 required, supports up to 8.2)
php -v
```

::: info PHP 8.2 Support
The plugin includes enhanced security features and XML parsing improvements that are fully compatible with PHP 8.2. If you encounter any XML-related issues, ensure you're using the latest version (7.0.1+).
:::

### AWS Credential Issues

#### Symptoms
- "Invalid credentials" error messages
- 403 Forbidden errors
- Authentication failures

#### Diagnosis

1. Check credential configuration:
   ```bash
   wp c3 flush 1
   ```

2. Verify environment variables:
   ```bash
   echo $AWS_ACCESS_KEY_ID
   echo $AWS_SECRET_ACCESS_KEY
   echo $C3_DISTRIBUTION_ID
   ```

3. Test AWS CLI access (if available):
   ```bash
   aws sts get-caller-identity
   aws cloudfront list-distributions
   ```

#### Solutions

**Environment Variables Not Set:**
```bash
# Add to .bashrc, .zshrc, or server config
export AWS_ACCESS_KEY_ID="your_access_key"
export AWS_SECRET_ACCESS_KEY="your_secret_key"
export C3_DISTRIBUTION_ID="your_distribution_id"
```

**IAM Permission Issues:**

Ensure your IAM user/role has this policy:
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "cloudfront:CreateInvalidation",
                "cloudfront:GetInvalidation",
                "cloudfront:ListInvalidations"
            ],
            "Resource": "arn:aws:cloudfront::*:distribution/YOUR_DISTRIBUTION_ID"
        }
    ]
}
```

**Credential Override:**
```php
// Temporary fix: override credentials in wp-config.php
add_filter('c3_credential', function($credentials) {
    return [
        'key' => 'your_access_key',
        'secret' => 'your_secret_key',
        'distribution_id' => 'your_distribution_id'
    ];
});
```

### CloudFront Distribution Issues

#### Symptoms
- "Distribution not found" errors
- Invalidations not appearing in CloudFront console
- Wrong distribution being targeted

#### Diagnosis

1. Verify distribution ID:
   ```bash
   # List all distributions
   aws cloudfront list-distributions --query 'DistributionList.Items[*].[Id,DomainName]'
   ```

2. Check distribution status:
   ```bash
   aws cloudfront get-distribution --id YOUR_DISTRIBUTION_ID
   ```

#### Solutions

**Wrong Distribution ID:**
- Copy the correct ID from AWS CloudFront console
- Update configuration in WordPress admin or environment variables

**Distribution Not Deployed:**
- Wait for distribution status to change from "In Progress" to "Deployed"
- This can take 15-20 minutes for new distributions

### Network and Timeout Issues

#### Symptoms
- "Connection timeout" errors
- "cURL error 28" messages
- Slow or hanging requests

#### Diagnosis

1. Test network connectivity:
   ```bash
   curl -I https://cloudfront.amazonaws.com
   ping cloudfront.amazonaws.com
   ```

2. Check firewall rules:
   ```bash
   # Ensure outbound HTTPS (443) is allowed
   telnet cloudfront.amazonaws.com 443
   ```

#### Solutions

**Increase Timeout:**
```php
// Add to wp-config.php
add_filter('c3_credential', function($credentials) {
    $credentials['timeout'] = 120; // 2 minutes
    return $credentials;
});
```

**Firewall Configuration:**
- Allow outbound connections to `*.amazonaws.com` on port 443
- Whitelist CloudFront IP ranges if necessary

**Server Resource Limits:**
```php
// Increase PHP limits in wp-config.php
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
```

### Cron Job Issues

#### Symptoms
- Invalidations stuck in queue
- Manual invalidation works but automatic doesn't
- Cron-related error messages

#### Diagnosis

1. Check WordPress cron:
   ```bash
   wp cron event list
   wp cron test
   ```

2. Check for c3-specific cron jobs:
   ```bash
   wp cron event list | grep c3
   ```

#### Solutions

**WordPress Cron Disabled:**
```php
// Check wp-config.php for this line and remove it
// define('DISABLE_WP_CRON', true);

// Or set up real cron job
// Add to server crontab:
// */1 * * * * cd /path/to/wordpress && wp cron event run --due-now
```

**Clear Stuck Jobs:**
```bash
# Clear all c3 cron jobs
wp cron event delete c3_invalidation_cron
wp cron event delete c3_process_cron

# Test cache flush manually
wp c3 flush all
```

### Performance Issues

#### Symptoms
- Slow WordPress admin
- High server load during invalidations
- Memory errors

#### Diagnosis

1. Test cache flush functionality:
   ```bash
   wp c3 flush 1
   ```

2. Monitor server resources:
   ```bash
   top
   free -m
   ```

#### Solutions

**Reduce Batch Size:**
```php
add_filter('c3_invalidation_item_limits', function($limits) {
    return 50; // Smaller batches
});
```

**Increase Processing Interval:**
```php
add_filter('c3_invalidation_interval', function($interval) {
    return 5; // Process every 5 minutes instead of 1
});
```

**Memory Optimization:**
```php
// Limit invalidation paths
add_filter('c3_invalidation_items', function($items, $post) {
    // Only invalidate essential paths
    if ($post) {
        return [get_permalink($post), '/'];
    }
    return ['/'];
}, 10, 2);
```

### Logging and Debugging

#### Enable Debug Logging

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Enable C3 logging
add_filter('c3_log_invalidation_list', '__return_true');
```

#### Check Debug Logs

```bash
# Apache error log
tail -f /var/log/apache2/error.log

# Nginx error log
tail -f /var/log/nginx/error.log
```

#### Custom Debug Function

```php
// Add to functions.php for temporary debugging
function c3_debug_log($message, $data = null) {
    if (WP_DEBUG_LOG) {
        $log_message = '[C3 DEBUG] ' . $message;
        if ($data !== null) {
            $log_message .= ' | Data: ' . print_r($data, true);
        }
        error_log($log_message);
    }
}

// Usage
add_action('c3_before_invalidation', function($paths) {
    c3_debug_log('Starting invalidation', $paths);
});
```

## Environment-Specific Issues

### Shared Hosting

#### Common Problems
- Limited cron functionality
- Restricted environment variable access
- File permission issues

#### Solutions

**Alternative Credential Storage:**
```php
// Use WordPress options instead of environment variables
add_filter('c3_credential', function($credentials) {
    return [
        'key' => get_option('c3_aws_access_key'),
        'secret' => get_option('c3_aws_secret_key'),
        'distribution_id' => get_option('c3_distribution_id')
    ];
});
```

**Manual Cron Setup:**
```php
// Disable WP-Cron and use external cron
define('DISABLE_WP_CRON', true);

// Set up external cron job to call:
// curl https://yoursite.com/wp-cron.php
```

### Docker/Kubernetes

#### Common Problems
- Environment variable inheritance
- Network isolation
- Container restart issues

#### Solutions

**Environment Variable Configuration:**
```yaml
# docker-compose.yml
environment:
  - AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
  - AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
  - C3_DISTRIBUTION_ID=${C3_DISTRIBUTION_ID}
```

**Health Checks:**
```bash
# Add to container health check
curl -f http://localhost/wp-admin/admin-ajax.php?action=c3_health_check
```

### Multi-Site Networks

#### Common Problems
- Different configurations per site
- Shared invalidation conflicts
- Network admin restrictions

#### Solutions

**Site-Specific Configuration:**
```php
add_filter('c3_credential', function($credentials) {
    $blog_id = get_current_blog_id();
    
    switch ($blog_id) {
        case 1: // Main site
            return [
                'key' => 'main_site_key',
                'secret' => 'main_site_secret',
                'distribution_id' => 'main_distribution_id'
            ];
        case 2: // Subsite
            return [
                'key' => 'subsite_key',
                'secret' => 'subsite_secret',
                'distribution_id' => 'subsite_distribution_id'
            ];
    }
    
    return $credentials;
});
```

## Quick Diagnostic Commands

### Health Check Script

```bash
#!/bin/bash
# c3-health-check.sh

echo "=== C3 CloudFront Cache Controller Health Check ==="

# Check WP-CLI
if ! command -v wp &> /dev/null; then
    echo "❌ WP-CLI not found"
    exit 1
fi

# Check plugin status
if wp plugin is-active c3-cloudfront-clear-cache; then
    echo "✅ Plugin is active"
else
    echo "❌ Plugin is not active"
    exit 1
fi

# Check configuration
echo "🔧 Configuration:"
wp c3 flush 1

# Check cron
echo "⏰ Cron jobs:"
wp cron event list | grep c3

echo "=== Health check complete ==="
```

### Reset Configuration

```bash
#!/bin/bash
# reset-c3-config.sh

echo "Resetting C3 configuration..."

# Clear WordPress options
wp option delete c3_aws_access_key
wp option delete c3_aws_secret_key
wp option delete c3_distribution_id
wp option delete c3_invalidation_settings

# Clear transients
wp transient delete c3_credential_check
wp transient delete c3_last_invalidation

# Clear cron jobs
wp cron event delete c3_invalidation_cron
wp cron event delete c3_process_cron

echo "Configuration reset complete. Please reconfigure the plugin."
```

## Getting Help

### Information to Provide

When seeking help, include:

1. **Plugin version**: `wp plugin list | grep c3`
2. **WordPress version**: `wp core version`
3. **PHP version**: `php -v`
4. **Server environment**: Apache/Nginx, hosting provider
5. **Error messages**: From debug logs
6. **Configuration**: Test with `wp c3 flush 1` (redact sensitive info)

### XML Security and Parsing Issues

#### Symptoms
- XML parsing errors in CloudFront responses
- Security warnings related to XML processing
- Issues with PHP 8.1+ compatibility

#### Background
Since version 7.0.1, the plugin includes enhanced secure XML parsing that prevents XXE (XML External Entity) attacks and improves PHP 8.2 compatibility.

#### Solutions

**Update to Latest Version:**
```bash
# Ensure you're using version 7.0.1 or later
wp plugin update c3-cloudfront-clear-cache
```

**Check XML Processing:**
```php
// Add to wp-config.php for debugging XML issues
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

```

**Verify libxml Settings:**
```bash
# Check libxml version (should support secure parsing)
php -m | grep libxml
php -r "echo libxml_version();"
```

### Support Channels

- **GitHub Issues**: [Report bugs](https://github.com/amimoto-ami/c3-cloudfront-clear-cache/issues)
- **WordPress Support**: [WordPress.org forums](https://wordpress.org/support/plugin/c3-cloudfront-clear-cache/)
- **Documentation**: [Plugin documentation](https://github.com/amimoto-ami/c3-cloudfront-clear-cache)

### Creating Bug Reports

Include this information in bug reports:

```bash
# Generate debug report
echo "=== C3 Debug Report ==="
echo "Plugin Version: $(wp plugin get c3-cloudfront-clear-cache --field=version)"
echo "WordPress Version: $(wp core version)"
echo "PHP Version: $(php -v | head -n1)"
echo "Server: $(uname -a)"
echo "Configuration:"
wp c3 flush 1
echo "Recent Errors:"
tail -n 50 /path/to/wp-content/debug.log | grep -i c3
```

## Next Steps

- Review [filter documentation](/development/filters) for advanced customization
- Check [basic usage guide](/guide/basic-usage) for implementation patterns
- Learn about [development setup](/development/contributing)
