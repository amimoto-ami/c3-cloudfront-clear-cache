# WP-CLI Commands

C3 CloudFront Cache Controller provides comprehensive WP-CLI support for managing cache invalidation from the command line. This is especially useful for automation, CI/CD pipelines, and server management.

## Available Commands

### `wp c3 invalidate`

Invalidate specific paths in CloudFront cache.

**Syntax:**
```bash
wp c3 invalidate <path> [<path>...] [--distribution-id=<id>]
```

**Parameters:**
- `<path>`: One or more paths to invalidate
- `--distribution-id=<id>`: Override the configured distribution ID

**Examples:**

```bash
# Invalidate a single path
wp c3 invalidate /

# Invalidate multiple paths
wp c3 invalidate / /about/ /contact/

# Invalidate with specific distribution ID
wp c3 invalidate / --distribution-id=E1234567890123

# Invalidate all cache (wildcard)
wp c3 invalidate "/*"

# Invalidate CSS and JS files
wp c3 invalidate /wp-content/themes/mytheme/style.css /wp-content/themes/mytheme/script.js
```

### `wp c3 flush`

Clear all CloudFront cache using wildcard invalidation.

**Syntax:**
```bash
wp c3 flush [--distribution-id=<id>]
```

**Examples:**

```bash
# Flush all cache
wp c3 flush

# Flush with specific distribution ID
wp c3 flush --distribution-id=E1234567890123
```

### `wp c3 status`

Check the current configuration and connection status.

**Syntax:**
```bash
wp c3 status
```

**Example output:**
```
AWS Access Key ID: AKIA****************
Distribution ID: E1234567890123
Connection: OK
Last Invalidation: 2024-01-15 10:30:00
Queued Invalidations: 0
```

### `wp c3 config`

Display or update configuration settings.

**Syntax:**
```bash
wp c3 config [--get=<setting>] [--set=<setting>=<value>]
```

**Examples:**

```bash
# Show all configuration
wp c3 config

# Get specific setting
wp c3 config --get=distribution_id

# Set configuration value
wp c3 config --set=timeout=60
```

### `wp c3 queue`

Manage the invalidation queue.

**Syntax:**
```bash
wp c3 queue [--list] [--clear] [--process]
```

**Examples:**

```bash
# List queued invalidations
wp c3 queue --list

# Clear the queue
wp c3 queue --clear

# Process queued invalidations immediately
wp c3 queue --process
```

## Common Use Cases

### Deployment Automation

Invalidate cache after deployment:

```bash
#!/bin/bash
# deployment-script.sh

# Deploy your application
./deploy.sh

# Clear specific cache paths
wp c3 invalidate / /wp-content/themes/mytheme/style.css /wp-content/themes/mytheme/script.js

# Or clear everything
wp c3 flush
```

### Content Publishing Pipeline

Invalidate specific content after publishing:

```bash
#!/bin/bash
# publish-content.sh

POST_ID=$1
POST_URL=$(wp post url $POST_ID)

# Invalidate the post and related pages
wp c3 invalidate "$POST_URL" / /blog/

echo "Cache invalidated for post: $POST_URL"
```

### Maintenance Scripts

Regular cache maintenance:

```bash
#!/bin/bash
# maintenance.sh

# Check status
echo "Checking C3 status..."
wp c3 status

# Process any queued invalidations
echo "Processing queued invalidations..."
wp c3 queue --process

# Clear old static assets
wp c3 invalidate /wp-content/cache/* /wp-content/uploads/*.css /wp-content/uploads/*.js
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
          wp c3 flush
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
    - wp c3 flush
  environment:
    name: production
  variables:
    AWS_ACCESS_KEY_ID: $AWS_ACCESS_KEY_ID
    AWS_SECRET_ACCESS_KEY: $AWS_SECRET_ACCESS_KEY
    C3_DISTRIBUTION_ID: $C3_DISTRIBUTION_ID
```

### Monitoring and Alerts

Check invalidation status in monitoring scripts:

```bash
#!/bin/bash
# monitor-cache.sh

STATUS=$(wp c3 status --format=json)
QUEUE_COUNT=$(echo $STATUS | jq '.queued_invalidations')

if [ "$QUEUE_COUNT" -gt 100 ]; then
    echo "WARNING: High number of queued invalidations: $QUEUE_COUNT"
    # Send alert to monitoring system
    curl -X POST "https://your-monitoring-system.com/alert" \
         -d "message=High C3 queue count: $QUEUE_COUNT"
fi
```

## Advanced Usage

### Batch Operations

Process multiple posts:

```bash
#!/bin/bash
# batch-invalidate.sh

# Get all published posts from last 24 hours
RECENT_POSTS=$(wp post list --post_status=publish --after="24 hours ago" --field=url)

for POST_URL in $RECENT_POSTS; do
    echo "Invalidating: $POST_URL"
    wp c3 invalidate "$POST_URL"
done
```

### Environment-Specific Operations

```bash
#!/bin/bash
# env-specific-invalidation.sh

ENVIRONMENT=$(wp option get environment_type)

case $ENVIRONMENT in
    "production")
        # More conservative invalidation for production
        wp c3 invalidate / /blog/
        ;;
    "staging")
        # Full cache clear for staging
        wp c3 flush
        ;;
    "development")
        echo "Skipping cache invalidation in development"
        exit 0
        ;;
esac
```

### Custom WordPress Hooks Integration

Trigger invalidation from custom events:

```bash
#!/bin/bash
# custom-hook-handler.sh

# Called from WordPress custom hook
# Example: do_action('custom_content_update', $content_id);

CONTENT_ID=$1
CONTENT_TYPE=$2

case $CONTENT_TYPE in
    "product")
        wp c3 invalidate /shop/ /products/
        ;;
    "news")
        wp c3 invalidate /news/ /
        ;;
    *)
        wp c3 invalidate /
        ;;
esac
```

## Error Handling

### Basic Error Handling

```bash
#!/bin/bash
set -e  # Exit on any error

if wp c3 invalidate /; then
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

invalidate_with_retry() {
    local path=$1
    local max_attempts=3
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        echo "Attempt $attempt: Invalidating $path"
        
        if wp c3 invalidate "$path"; then
            echo "Success: $path invalidated"
            return 0
        else
            echo "Failed: Attempt $attempt for $path"
            attempt=$((attempt + 1))
            sleep 5
        fi
    done
    
    echo "Error: Failed to invalidate $path after $max_attempts attempts"
    return 1
}

# Usage
invalidate_with_retry "/"
invalidate_with_retry "/blog/"
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
wp c3 invalidate /
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

# Check C3 status
if wp c3 status &> /dev/null; then
    echo "✓ C3 configuration is valid"
else
    echo "✗ C3 configuration error"
    wp c3 status
    exit 1
fi
```

## Performance Optimization

### Parallel Processing

Process multiple invalidations in parallel:

```bash
#!/bin/bash
# parallel-invalidation.sh

PATHS=("/" "/blog/" "/shop/" "/about/" "/contact/")

# Function to invalidate a single path
invalidate_path() {
    local path=$1
    echo "Starting invalidation: $path"
    wp c3 invalidate "$path"
    echo "Completed invalidation: $path"
}

# Export function for parallel processing
export -f invalidate_path

# Run invalidations in parallel
printf '%s\n' "${PATHS[@]}" | xargs -I {} -P 5 bash -c 'invalidate_path "$@"' _ {}
```

## Troubleshooting

### Debug Mode

Enable verbose output:

```bash
# Add --debug flag for detailed output
wp c3 invalidate / --debug

# Check WordPress debug log
wp c3 invalidate / && tail -f /path/to/wp-content/debug.log
```

### Common Issues

**Permission Denied:**
```bash
# Check AWS credentials
wp c3 status

# Verify IAM permissions
aws sts get-caller-identity
```

**Network Timeouts:**
```bash
# Increase timeout
wp c3 config --set=timeout=120

# Test connectivity
curl -I https://cloudfront.amazonaws.com
```

**Queue Issues:**
```bash
# Check queue status
wp c3 queue --list

# Clear stuck queue
wp c3 queue --clear

# Process manually
wp c3 queue --process
```

## Next Steps

- Learn about [troubleshooting common issues](/guide/troubleshooting)
- Explore [integration examples](/examples/integration)
- Review [API reference](/api/wp-cli)