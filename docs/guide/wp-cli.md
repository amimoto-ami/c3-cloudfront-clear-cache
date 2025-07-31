# WP-CLI Commands

C3 CloudFront Cache Controller provides WP-CLI support for managing cache invalidation from the command line. This comprehensive guide covers all available commands, use cases, and best practices for automation, CI/CD pipelines, and server management.

## Command Overview

| Command | Description | Since |
|---------|-------------|-------|
| `wp c3 flush` | Clear CloudFront cache for specific posts or all | 2.3.0 |
| `wp c3 update` | Update plugin configuration settings | 2.4.0 |

## Available Commands

### `wp c3 flush`

Clear CloudFront cache for specific posts or all content.

**Syntax:**
```bash
wp c3 flush <post_id|all> [--force]
```

**Parameters:**

#### Required
- `<post_id|all>`: Post ID (numeric), comma-separated list of post IDs, or 'all' for complete cache flush

#### Optional
- `--force`: Activate Force Clear Mode (bypasses invalidation flag)

**Response:**

**Success:**
```
Success: Create Invalidation Request. Please wait few minutes to finished clear CloudFront Cache.
```

**Error:**
```
Error: Please input parameter:post_id(numeric) or all
```

**Exit Codes:**
- `0` - Success
- `1` - General error (invalid parameters, AWS API error, etc.)

**Examples:**

```bash
# Flush cache for specific post
wp c3 flush 1

# Flush cache for multiple posts
wp c3 flush 1,2,4

# Flush all CloudFront cache
wp c3 flush all

# Force flush all cache
wp c3 flush all --force
```

### `wp c3 update`

Update C3 CloudFront Cache Controller configuration settings.

**Syntax:**
```bash
wp c3 update <setting_type> <value>
```

**Parameters:**

#### Required
- `<setting_type>`: Type of setting to update: `distribution_id`, `access_key`, or `secret_key`
- `<value>`: New value for the setting

**Available Settings:**
- `distribution_id` - CloudFront Distribution ID
- `access_key` - AWS Access Key ID
- `secret_key` - AWS Secret Access Key

**Response:**

**Success:**
```
Success: Update Option
```

**Error:**
```
Error: No type selected
Error: No value defined
Error: No Match Setting Type.
```

**Security Considerations:**
- Access keys and secret keys are stored in WordPress options table
- Consider using environment variables for production environments
- Values are escaped using `esc_attr()` before storage

**Examples:**

```bash
# Update distribution ID
wp c3 update distribution_id E1234567890123

# Update access key
wp c3 update access_key AKIAIOSFODNN7EXAMPLE

# Update secret key
wp c3 update secret_key wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
```

## Common Use Cases

### Deployment Automation

Invalidate cache after deployment:

```bash
#!/bin/bash
# deployment-script.sh

# Deploy your application
./deploy.sh

# Clear all cache after deployment
wp c3 flush all
```

### Content Publishing Pipeline

Invalidate specific content after publishing:

```bash
#!/bin/bash
# publish-content.sh

POST_ID=$1

# Invalidate the specific post
wp c3 flush $POST_ID

echo "Cache invalidated for post ID: $POST_ID"
```

### CI/CD Integration

#### GitHub Actions Example

```yaml
name: Deploy and Clear Cache
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Deploy to server
        run: ./deploy.sh
        
      - name: Clear CloudFront Cache
        run: |
          wp c3 flush all
        env:
          AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
          AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          C3_DISTRIBUTION_ID: ${{ secrets.C3_DISTRIBUTION_ID }}
```

#### GitLab CI Example

```yaml
deploy:
  stage: deploy
  script:
    - ./deploy.sh
    - wp c3 flush all
  environment:
    name: production
  variables:
    AWS_ACCESS_KEY_ID: $AWS_ACCESS_KEY_ID
    AWS_SECRET_ACCESS_KEY: $AWS_SECRET_ACCESS_KEY
    C3_DISTRIBUTION_ID: $C3_DISTRIBUTION_ID
```

### Configuration Management

Set up C3 configuration via command line:

```bash
#!/bin/bash
# configure-c3.sh

# Set up C3 configuration
DISTRIBUTION_ID="E1234567890123"
ACCESS_KEY="AKIAIOSFODNN7EXAMPLE"
SECRET_KEY="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"

echo "Configuring C3 CloudFront Cache Controller..."

# Update distribution ID
wp c3 update distribution_id $DISTRIBUTION_ID

# Update access key
wp c3 update access_key $ACCESS_KEY

# Update secret key
wp c3 update secret_key $SECRET_KEY

echo "Configuration complete"
```

## Advanced Usage

### Batch Operations

Process multiple posts:

```bash
#!/bin/bash
# batch-invalidate.sh

# Get all published posts from last 24 hours
RECENT_POST_IDS=$(wp post list --post_status=publish --after="24 hours ago" --field=ID)

# Convert to comma-separated list
POST_IDS=$(echo $RECENT_POST_IDS | tr ' ' ',')

if [ -n "$POST_IDS" ]; then
    echo "Invalidating posts: $POST_IDS"
    wp c3 flush $POST_IDS
else
    echo "No recent posts to invalidate"
fi
```

### Environment-Specific Operations

```bash
#!/bin/bash
# env-specific-invalidation.sh

ENVIRONMENT=$(wp option get environment_type)

case $ENVIRONMENT in
    "production")
        # More conservative invalidation for production
        echo "Production environment - skipping full cache clear"
        ;;
    "staging")
        # Full cache clear for staging
        wp c3 flush all
        ;;
    "development")
        echo "Skipping cache invalidation in development"
        exit 0
        ;;
esac
```

## Error Handling

### Basic Error Handling

```bash
#!/bin/bash
set -e  # Exit on any error

if wp c3 flush all; then
    echo "Cache invalidation successful"
else
    echo "Cache invalidation failed"
    exit 1
fi
```

### Advanced Error Handling

```bash
#!/bin/bash
# robust-invalidation.sh

flush_with_retry() {
    local post_id=$1
    local max_attempts=3
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        echo "Attempt $attempt: Flushing cache for post $post_id"
        
        if wp c3 flush $post_id; then
            echo "Success: Post $post_id cache flushed"
            return 0
        else
            echo "Failed: Attempt $attempt for post $post_id"
            attempt=$((attempt + 1))
            sleep 5
        fi
    done
    
    echo "Error: Failed to flush cache for post $post_id after $max_attempts attempts"
    return 1
}

# Usage
flush_with_retry "1"
flush_with_retry "all"
```

## Configuration Management

### Environment Variables

Set up different configurations for different environments:

```bash
# production.env
export AWS_ACCESS_KEY_ID="prod_access_key"
export AWS_SECRET_ACCESS_KEY="prod_secret_key"
export C3_DISTRIBUTION_ID="prod_distribution_id"

# staging.env
export AWS_ACCESS_KEY_ID="staging_access_key"
export AWS_SECRET_ACCESS_KEY="staging_secret_key"
export C3_DISTRIBUTION_ID="staging_distribution_id"

# Load environment-specific config
source "${ENVIRONMENT}.env"
wp c3 flush all
```

### Configuration Validation

```bash
#!/bin/bash
# validate-config.sh

echo "Validating C3 configuration..."

# Check if WP-CLI is available
if ! command -v wp &> /dev/null; then
    echo "Error: WP-CLI not found"
    exit 1
fi

# Test configuration by attempting a flush operation
if wp c3 flush 1 &> /dev/null; then
    echo "✓ C3 configuration is valid"
else
    echo "✗ C3 configuration error"
    exit 1
fi
```

## Performance Optimization

### Batch Post Invalidation

For multiple post invalidations, use comma-separated lists:

```bash
#!/bin/bash
# batch-post-invalidation.sh

# Instead of individual calls
# wp c3 flush 1
# wp c3 flush 2
# wp c3 flush 3

# Use single command with comma-separated list
wp c3 flush 1,2,3
```

### Force Mode Usage

The `--force` flag bypasses the invalidation flag filter:

```bash
#!/bin/bash
# force-clear.sh

# Normal flush (respects invalidation flag)
wp c3 flush all

# Force flush (ignores invalidation flag)
wp c3 flush all --force
```

## Troubleshooting

### Debug Mode

Enable verbose output:

```bash
# Add --debug flag for detailed output
wp c3 flush all --debug

# Check WordPress debug log
wp c3 flush all && tail -f /path/to/wp-content/debug.log
```

### Common Issues

**Permission Denied:**
```bash
# Check AWS credentials by testing a flush operation
wp c3 flush 1

# Verify IAM permissions
aws sts get-caller-identity
```

**Network Timeouts:**
```bash
# Test connectivity
curl -I https://cloudfront.amazonaws.com
```

**Invalid Parameters:**
```bash
# Check parameter format
wp c3 flush 1,2,3  # Correct: comma-separated
wp c3 flush "1 2 3"  # Incorrect: space-separated
```

## Best Practices

1. **Use specific post IDs** when possible instead of clearing all cache
2. **Monitor AWS CloudFront costs** - invalidations have associated costs
3. **Test in staging** before running in production
4. **Use force mode sparingly** - only when necessary
5. **Consider timing** - avoid clearing cache during peak traffic periods
6. **Use comma-separated lists** for multiple post IDs
7. **Handle errors gracefully** in automation scripts

## Global Options

All C3 commands support these global WP-CLI options:

### Environment
- `--path=<path>` - Path to WordPress installation
- `--url=<url>` - WordPress site URL
- `--ssh=<ssh>` - SSH connection string

### Output
- `--quiet` - Suppress informational messages
- `--debug` - Enable debug output

**Examples:**
```bash
# Remote WordPress installation
wp --ssh=user@server.com --path=/var/www/html c3 flush all

# Quiet mode
wp c3 flush 1 --quiet

# Debug mode
wp c3 update distribution_id E1234567890123 --debug
```

## Error Handling

### Common Error Scenarios

| Scenario | Error Message | Resolution |
|----------|---------------|------------|
| No parameters provided | `Please input parameter:post_id(numeric) or all` | Provide required post ID or 'all' |
| Invalid setting type | `No Match Setting Type.` | Use valid setting: distribution_id, access_key, secret_key |
| Missing setting value | `No value defined` | Provide value for the setting |
| Invalid post ID | `Please input parameter:post_id(numeric) or all` | Use numeric post ID or 'all' |

### AWS API Errors

When AWS API calls fail, the error message from AWS will be displayed:

```
Error: [AWS Error Message]
```

Common AWS errors include:
- Invalid credentials
- Distribution not found
- Network connectivity issues
- Rate limiting

## Next Steps

- Learn about [troubleshooting common issues](/guide/troubleshooting)
- Explore [filters and hooks](/development/filters) for advanced customization