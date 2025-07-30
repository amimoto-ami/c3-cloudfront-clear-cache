# Configuration

After installing the plugin, you need to configure it with your AWS credentials and CloudFront distribution settings.

## AWS Prerequisites

Before configuring the plugin, ensure you have:

1. **AWS Account** with CloudFront access
2. **CloudFront Distribution** configured for your WordPress site
3. **IAM User** or **IAM Role** with appropriate permissions

### Required IAM Permissions

Create an IAM policy with these minimum permissions:

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

## Configuration Methods

### Method 1: WordPress Admin Interface (Recommended)

1. Go to **Settings > C3 CloudFront Cache**
2. Fill in the required fields:
   - **CloudFront Distribution ID**
   - **AWS Access Key ID**
   - **AWS Secret Access Key**
3. Click **Save Changes**

The plugin will test your credentials and save them securely in the WordPress options table.

### Method 2: wp-config.php Constants (Advanced)

For enhanced security, you can define constants in your `wp-config.php` file:

```php
// Add to wp-config.php (before the "That's all, stop editing!" line)
define( 'AWS_ACCESS_KEY_ID', 'your_access_key_here' );
define( 'AWS_SECRET_ACCESS_KEY', 'your_secret_key_here' );
define( 'C3_DISTRIBUTION_ID', 'your_cloudfront_distribution_id' );
```

#### For Different Hosting Environments

**Shared Hosting**
```php
// Add to wp-config.php
define( 'AWS_ACCESS_KEY_ID', 'your_access_key_here' );
define( 'AWS_SECRET_ACCESS_KEY', 'your_secret_key_here' );
define( 'C3_DISTRIBUTION_ID', 'your_cloudfront_distribution_id' );
```

**VPS/Dedicated Server**
```php
// Add to wp-config.php
define( 'AWS_ACCESS_KEY_ID', 'your_access_key_here' );
define( 'AWS_SECRET_ACCESS_KEY', 'your_secret_key_here' );
define( 'C3_DISTRIBUTION_ID', 'your_cloudfront_distribution_id' );
```

**Docker**
```php
// Add to wp-config.php
define( 'AWS_ACCESS_KEY_ID', 'your_access_key_here' );
define( 'AWS_SECRET_ACCESS_KEY', 'your_secret_key_here' );
define( 'C3_DISTRIBUTION_ID', 'your_cloudfront_distribution_id' );
```

::: info Security Note
Using `wp-config.php` constants provides enhanced security by keeping credentials out of the database. However, the WordPress admin interface is perfectly suitable for most use cases and provides a user-friendly configuration experience.
:::

### Method 3: Programmatic Configuration

You can override settings programmatically using the `c3_credential` filter:

```php
add_filter('c3_credential', function($credentials) {
    return [
        'key'    => 'your_access_key_here',
        'secret' => 'your_secret_key_here',
        'distribution_id' => 'your_distribution_id',
        'timeout' => 30
    ];
});
```

## Finding Your CloudFront Distribution ID

1. Log in to the [AWS Console](https://console.aws.amazon.com/)
2. Navigate to **CloudFront**
3. Find your distribution in the list
4. Copy the **ID** (e.g., `E1234567890123`)

## Testing Configuration

After configuration, test that everything works:

1. Go to **Settings > C3 CloudFront Cache**
2. Click **Manual Invalidation**
3. Enter a test path (e.g., `/`)
4. Click **Invalidate**

If successful, you should see a confirmation message and the invalidation should appear in your CloudFront console.

### WP-CLI Testing

You can also test configuration using WP-CLI:

```bash
# Check configuration status
wp c3 status

# Test invalidation
wp c3 invalidate /
```

## Advanced Configuration Options

### Custom Invalidation Intervals

```php
// Change invalidation interval (default: 1 minute)
add_filter('c3_invalidation_interval', function($interval_minutes) {
    return 5; // 5 minutes
});

// Change retry interval for failed invalidations (default: 1 minute)
add_filter('c3_invalidation_cron_interval', function($interval_minutes) {
    return 2; // 2 minutes
});
```

### Invalidation Limits

```php
// Change the number of paths per invalidation batch (default: 100)
add_filter('c3_invalidation_item_limits', function($limits) {
    return 300; // 300 paths per batch
});
```

### Logging Configuration

```php
// Enable comprehensive invalidation logging
add_filter('c3_log_invalidation_list', '__return_true');

// Enable cron job logging (legacy, use above for comprehensive logging)
add_filter('c3_log_cron_invalidation_task', '__return_true');
```

## Troubleshooting Configuration

### Common Issues

**Invalid Credentials**
- Verify your AWS Access Key ID and Secret Access Key in the WordPress admin
- Check that the IAM user has necessary permissions
- Ensure credentials are properly URL-encoded if special characters are present
- If using wp-config.php constants, verify they are correctly defined

**Distribution Not Found**
- Verify the CloudFront Distribution ID is correct
- Ensure the distribution exists and is not deleted
- Check that you're using the distribution ID, not the domain name

**Permission Denied**
- Review IAM policy permissions
- Ensure the policy is attached to the correct user/role
- Check CloudFormation resource limits

**Timeout Errors**
- Increase the `C3_HTTP_TIMEOUT` value
- Check network connectivity to AWS services
- Verify firewall settings allow outbound HTTPS traffic

For more troubleshooting help, see the [Troubleshooting Guide](/guide/troubleshooting).

## Next Steps

With configuration complete, learn about [basic usage](/guide/basic-usage) and explore [advanced features](/guide/filters).