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

### Method 1: EC2 Instance Role (Recommended for AWS EC2)

If your WordPress site is running on an EC2 instance, using an EC2 instance role is the **most secure and recommended method**. This approach eliminates the need to store AWS credentials in your application.

#### Prerequisites

1. **EC2 Instance Role**: Your EC2 instance must have an IAM role attached with the required CloudFront permissions
2. **Instance Metadata Service**: Ensure the instance metadata service is accessible (enabled by default)

#### Setting up EC2 Instance Role

1. **Create IAM Role**:
   - Go to AWS IAM Console
   - Create a new role with the "EC2" trusted entity
   - Attach the following policy (or create a custom one):

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

2. **Attach Role to EC2 Instance**:
   - Go to EC2 Console
   - Select your instance
   - Actions → Security → Modify IAM role
   - Select the created role

3. **Configure Plugin**:
   - Go to **Settings > C3 CloudFront Cache**
   - Enter only the **CloudFront Distribution ID**
   - Leave AWS credentials fields empty
   - Click **Save Changes**

The plugin will automatically detect and use the EC2 instance role credentials.

::: tip Security Benefits
- No credentials stored in application code or database
- Automatic credential rotation
- No risk of credential exposure
- Follows AWS security best practices
:::

### Method 2: WordPress Admin Interface

1. Go to **Settings > C3 CloudFront Cache**
2. Fill in the required fields:
   - **CloudFront Distribution ID**
   - **AWS Access Key ID**
   - **AWS Secret Access Key**
3. Click **Save Changes**

The plugin will test your credentials and save them securely in the WordPress options table.

### Method 3: wp-config.php Constants (Advanced)

For enhanced security, you can define constants in your `wp-config.php` file:

```php
// Add to wp-config.php (before the "That's all, stop editing!" line)
define( 'AWS_ACCESS_KEY_ID', 'your_access_key_here' );
define( 'AWS_SECRET_ACCESS_KEY', 'your_secret_key_here' );
define( 'C3_DISTRIBUTION_ID', 'your_cloudfront_distribution_id' );
```

#### For Different Hosting Environments

以下の設定は、Shared Hosting、VPS/Dedicated Server、Dockerなど、どの環境でも同じように使用できます：

```php
// Add to wp-config.php
define( 'AWS_ACCESS_KEY_ID', 'your_access_key_here' );
define( 'AWS_SECRET_ACCESS_KEY', 'your_secret_key_here' );
define( 'C3_DISTRIBUTION_ID', 'your_cloudfront_distribution_id' );
```

::: tip Environment-Specific Considerations
- **AWS EC2**: **Strongly recommended** to use EC2 instance roles for maximum security
- **Shared Hosting**: Consider using the WordPress admin interface as file system access may be limited
- **VPS/Dedicated Server**: Consider using environment variables for enhanced security
- **Docker**: Consider using environment variables or IAM roles for containerized environments
:::

::: info Security Note
Using `wp-config.php` constants provides enhanced security by keeping credentials out of the database. However, the WordPress admin interface is perfectly suitable for most use cases and provides a user-friendly configuration experience.
:::

### Method 4: AWS Systems Manager / Secrets Manager

For enhanced security in production environments, you can use AWS Systems Manager Parameter Store or Secrets Manager to manage your credentials.

#### Using Parameter Store

Store credentials in Parameter Store and retrieve them programmatically:

```php
add_filter('c3_credential', function($credentials) {
    try {
        $ssm = new Aws\Ssm\SsmClient(['version' => 'latest', 'region' => 'us-east-1']);
        $result = $ssm->getParameters([
            'Names' => ['/c3-cloudfront/aws-access-key', '/c3-cloudfront/aws-secret-key', '/c3-cloudfront/distribution-id'],
            'WithDecryption' => true
        ]);
        
        $params = [];
        foreach ($result['Parameters'] as $param) {
            $params[$param['Name']] = $param['Value'];
        }
        
        return [
            'key' => $params['/c3-cloudfront/aws-access-key'],
            'secret' => $params['/c3-cloudfront/aws-secret-key'],
            'distribution_id' => $params['/c3-cloudfront/distribution-id'],
            'timeout' => 30
        ];
    } catch (Exception $e) {
        error_log('C3 CloudFront: Failed to retrieve credentials from SSM: ' . $e->getMessage());
        return $credentials;
    }
});
```

#### Using Secrets Manager

Store credentials as a JSON secret and retrieve them:

```php
add_filter('c3_credential', function($credentials) {
    try {
        $secretsManager = new Aws\SecretsManager\SecretsManagerClient(['version' => 'latest', 'region' => 'us-east-1']);
        $result = $secretsManager->getSecretValue(['SecretId' => 'c3-cloudfront-credentials']);
        $secret = json_decode($result['SecretString'], true);
        
        return [
            'key' => $secret['aws_access_key_id'],
            'secret' => $secret['aws_secret_access_key'],
            'distribution_id' => $secret['distribution_id'],
            'timeout' => 30
        ];
    } catch (Exception $e) {
        error_log('C3 CloudFront: Failed to retrieve credentials from Secrets Manager: ' . $e->getMessage());
        return $credentials;
    }
});
```

::: info Security Note
Remember to configure appropriate IAM permissions for accessing Parameter Store or Secrets Manager, and use SecureString type for sensitive parameters.
:::

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
# Test configuration by flushing cache for a specific post
wp c3 flush 1

# Test full cache clear
wp c3 flush all
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
- **For EC2 Instance Role**: Verify the IAM role is attached to the instance and has the required CloudFront permissions

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