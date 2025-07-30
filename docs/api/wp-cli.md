# WP-CLI API Reference

Complete reference for all WP-CLI commands provided by C3 CloudFront Cache Controller.

## Command Overview

| Command | Description | Since |
|---------|-------------|-------|
| `wp c3 invalidate` | Invalidate specific paths | 1.0.0 |
| `wp c3 flush` | Clear all cache | 1.0.0 |
| `wp c3 status` | Check configuration status | 2.0.0 |
| `wp c3 config` | Manage configuration | 3.0.0 |
| `wp c3 queue` | Manage invalidation queue | 4.0.0 |
| `wp c3 log` | View invalidation logs | 5.0.0 |
| `wp c3 test` | Test connectivity | 6.0.0 |

---

## `wp c3 invalidate`

Invalidate specific paths in CloudFront cache.

### Syntax

```bash
wp c3 invalidate <path>... [--distribution-id=<id>] [--async] [--timeout=<seconds>]
```

### Parameters

#### Required
- `<path>` - One or more paths to invalidate

#### Optional
- `--distribution-id=<id>` - Override default distribution ID
- `--async` - Process invalidation asynchronously
- `--timeout=<seconds>` - HTTP timeout (default: 30)

### Examples

```bash
# Single path
wp c3 invalidate /

# Multiple paths
wp c3 invalidate / /about/ /contact/

# With custom distribution
wp c3 invalidate / --distribution-id=E1234567890123

# Async processing
wp c3 invalidate /* --async

# Custom timeout
wp c3 invalidate / --timeout=60
```

### Response Format

**Success:**
```json
{
  "success": true,
  "invalidation_id": "I1234567890123",
  "status": "InProgress",
  "paths": ["/", "/about/"],
  "message": "Invalidation created successfully"
}
```

**Error:**
```json
{
  "success": false,
  "error_code": "InvalidCredentials",
  "message": "Invalid AWS credentials",
  "paths": ["/"]
}
```

### Exit Codes

- `0` - Success
- `1` - General error
- `2` - Invalid arguments
- `3` - AWS API error
- `4` - Network error

---

## `wp c3 flush`

Clear all CloudFront cache using wildcard invalidation.

### Syntax

```bash
wp c3 flush [--distribution-id=<id>] [--confirm] [--timeout=<seconds>]
```

### Parameters

#### Optional
- `--distribution-id=<id>` - Override default distribution ID
- `--confirm` - Skip confirmation prompt
- `--timeout=<seconds>` - HTTP timeout (default: 30)

### Examples

```bash
# Flush with confirmation
wp c3 flush

# Skip confirmation
wp c3 flush --confirm

# Custom distribution
wp c3 flush --distribution-id=E1234567890123 --confirm
```

### Interactive Mode

Without `--confirm`, the command prompts for confirmation:

```
Warning: This will invalidate ALL cached content in CloudFront.
This may result in increased origin server load.
Are you sure? [y/N]
```

### Response Format

```json
{
  "success": true,
  "invalidation_id": "I1234567890123",
  "status": "InProgress",
  "paths": ["/*"],
  "message": "Full cache flush initiated"
}
```

---

## `wp c3 status`

Display current configuration and connection status.

### Syntax

```bash
wp c3 status [--format=<format>] [--field=<field>] [--check-connection]
```

### Parameters

#### Optional
- `--format=<format>` - Output format: table, json, csv, yaml (default: table)
- `--field=<field>` - Display specific field only
- `--check-connection` - Test actual connectivity to AWS

### Examples

```bash
# Default table format
wp c3 status

# JSON format
wp c3 status --format=json

# Specific field
wp c3 status --field=distribution_id

# With connectivity check
wp c3 status --check-connection
```

### Output Fields

- `aws_access_key_id` - AWS Access Key ID (masked)
- `distribution_id` - CloudFront Distribution ID
- `timeout` - HTTP timeout setting
- `last_invalidation` - Timestamp of last invalidation
- `queued_invalidations` - Number of queued invalidations
- `connection_status` - Connection test result (if --check-connection used)

### Example Output

**Table Format:**
```
+---------------------+------------------+
| Field               | Value            |
+---------------------+------------------+
| aws_access_key_id   | AKIA****123      |
| distribution_id     | E1234567890123   |
| timeout             | 30               |
| last_invalidation   | 2024-01-15 10:30 |
| queued_invalidations| 0                |
+---------------------+------------------+
```

**JSON Format:**
```json
{
  "aws_access_key_id": "AKIA****123",
  "distribution_id": "E1234567890123",
  "timeout": 30,
  "last_invalidation": "2024-01-15 10:30:00",
  "queued_invalidations": 0,
  "connection_status": "OK"
}
```

---

## `wp c3 config`

Manage plugin configuration settings.

### Syntax

```bash
wp c3 config [--list] [--get=<setting>] [--set=<setting>=<value>] [--delete=<setting>] [--reset]
```

### Parameters

#### Optional
- `--list` - List all configuration settings
- `--get=<setting>` - Get specific setting value
- `--set=<setting>=<value>` - Set configuration value
- `--delete=<setting>` - Delete configuration setting
- `--reset` - Reset all configuration to defaults

### Available Settings

- `aws_access_key_id` - AWS Access Key ID
- `aws_secret_access_key` - AWS Secret Access Key
- `distribution_id` - CloudFront Distribution ID
- `timeout` - HTTP timeout in seconds
- `batch_size` - Invalidation batch size
- `interval` - Processing interval in minutes

### Examples

```bash
# List all settings
wp c3 config --list

# Get specific setting
wp c3 config --get=distribution_id

# Set configuration
wp c3 config --set=timeout=60

# Set multiple values
wp c3 config --set=timeout=60 --set=batch_size=200

# Delete setting
wp c3 config --delete=aws_access_key_id

# Reset all configuration
wp c3 config --reset
```

### Security Considerations

- Sensitive values (access keys) are masked in output
- Use environment variables instead of storing in database for production
- The `--reset` command will delete all stored credentials

---

## `wp c3 queue`

Manage the invalidation queue for batched processing.

### Syntax

```bash
wp c3 queue [--list] [--add=<path>] [--remove=<id>] [--clear] [--process] [--status] [--format=<format>]
```

### Parameters

#### Optional
- `--list` - List queued invalidations
- `--add=<path>` - Add path to queue
- `--remove=<id>` - Remove specific queue item
- `--clear` - Clear entire queue
- `--process` - Process queue immediately
- `--status` - Show queue statistics
- `--format=<format>` - Output format (table, json, csv)

### Examples

```bash
# List queue
wp c3 queue --list

# Add to queue
wp c3 queue --add=/new-page/

# Remove from queue
wp c3 queue --remove=123

# Clear queue
wp c3 queue --clear

# Process queue
wp c3 queue --process

# Queue statistics
wp c3 queue --status --format=json
```

### Queue Item Structure

```json
{
  "id": 123,
  "path": "/example-page/",
  "created": "2024-01-15 10:30:00",
  "attempts": 0,
  "status": "pending"
}
```

### Queue Status Fields

- `total_items` - Total queued items
- `pending_items` - Items waiting to be processed
- `processing_items` - Items currently being processed
- `failed_items` - Items that failed processing
- `next_run` - Next scheduled processing time

---

## `wp c3 log`

View and manage invalidation logs.

### Syntax

```bash
wp c3 log [--list] [--clear] [--filter=<type>] [--limit=<number>] [--format=<format>] [--since=<date>]
```

### Parameters

#### Optional
- `--list` - List recent logs (default action)
- `--clear` - Clear all logs
- `--filter=<type>` - Filter by log type: success, error, warning
- `--limit=<number>` - Limit number of results (default: 50)
- `--format=<format>` - Output format (table, json, csv)
- `--since=<date>` - Show logs since date (YYYY-MM-DD format)

### Examples

```bash
# List recent logs
wp c3 log

# Show only errors
wp c3 log --filter=error

# Show last 10 entries
wp c3 log --limit=10

# Clear logs
wp c3 log --clear

# Show logs since specific date
wp c3 log --since=2024-01-01
```

### Log Entry Structure

```json
{
  "id": 456,
  "timestamp": "2024-01-15 10:30:00",
  "type": "success",
  "operation": "invalidate",
  "paths": ["/", "/about/"],
  "invalidation_id": "I1234567890123",
  "duration": 2.5,
  "message": "Invalidation completed successfully"
}
```

---

## `wp c3 test`

Test connectivity and configuration.

### Syntax

```bash
wp c3 test [--connection] [--credentials] [--distribution] [--all] [--format=<format>]
```

### Parameters

#### Optional
- `--connection` - Test network connectivity
- `--credentials` - Test AWS credentials
- `--distribution` - Test distribution access
- `--all` - Run all tests (default)
- `--format=<format>` - Output format (table, json)

### Examples

```bash
# Run all tests
wp c3 test

# Test specific component
wp c3 test --credentials

# JSON output
wp c3 test --format=json
```

### Test Results

```json
{
  "connection": {
    "status": "pass",
    "message": "Successfully connected to CloudFront API",
    "duration": 0.5
  },
  "credentials": {
    "status": "pass",
    "message": "AWS credentials are valid",
    "duration": 1.2
  },
  "distribution": {
    "status": "pass",
    "message": "Distribution E1234567890123 is accessible",
    "duration": 0.8
  },
  "overall": "pass"
}
```

---

## Global Options

All C3 commands support these global WP-CLI options:

### Environment
- `--path=<path>` - Path to WordPress installation
- `--url=<url>` - WordPress site URL
- `--ssh=<ssh>` - SSH connection string

### Output
- `--quiet` - Suppress informational messages
- `--debug` - Enable debug output
- `--format=<format>` - Output format (where applicable)

### Examples

```bash
# Remote WordPress installation
wp --ssh=user@server.com --path=/var/www/html c3 status

# Quiet mode
wp c3 invalidate / --quiet

# Debug mode
wp c3 flush --debug
```

---

## Error Handling

### Common Error Codes

| Code | Description | Resolution |
|------|-------------|------------|
| `invalid_credentials` | AWS credentials invalid | Check access key and secret |
| `distribution_not_found` | Distribution ID invalid | Verify distribution ID |
| `network_error` | Network connectivity issue | Check internet connection |
| `permission_denied` | Insufficient AWS permissions | Review IAM policy |
| `rate_limit` | AWS API rate limit exceeded | Wait and retry |
| `invalid_path` | Invalid invalidation path | Check path format |

### Error Response Format

```json
{
  "success": false,
  "error_code": "invalid_credentials",
  "message": "The AWS Access Key ID you provided does not exist in our records",
  "suggestion": "Verify your AWS_ACCESS_KEY_ID environment variable or plugin settings",
  "documentation": "https://docs.aws.amazon.com/cloudfront/latest/APIReference/API_CreateInvalidation.html"
}
```

---

## Scripting and Automation

### Basic Scripting

```bash
#!/bin/bash
# invalidate-deployment.sh

# Check if C3 is configured
if ! wp c3 status --field=distribution_id > /dev/null 2>&1; then
    echo "Error: C3 not configured"
    exit 1
fi

# Invalidate common paths
PATHS=("/" "/wp-content/themes/mytheme/style.css" "/sitemap.xml")

for path in "${PATHS[@]}"; do
    echo "Invalidating: $path"
    if ! wp c3 invalidate "$path" --quiet; then
        echo "Warning: Failed to invalidate $path"
    fi
done

echo "Deployment cache invalidation complete"
```

### Advanced Automation

```bash
#!/bin/bash
# comprehensive-cache-management.sh

# Function to check C3 status
check_c3_status() {
    local status=$(wp c3 status --field=connection_status --check-connection 2>/dev/null)
    if [[ "$status" != "OK" ]]; then
        echo "Error: C3 not properly configured"
        return 1
    fi
}

# Function to process with retry
invalidate_with_retry() {
    local path="$1"
    local max_attempts=3
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if wp c3 invalidate "$path" --quiet; then
            echo "✓ Invalidated: $path"
            return 0
        else
            echo "⚠ Attempt $attempt failed for: $path"
            ((attempt++))
            sleep 5
        fi
    done
    
    echo "✗ Failed to invalidate: $path"
    return 1
}

# Main execution
if check_c3_status; then
    # Process queue first
    wp c3 queue --process --quiet
    
    # Invalidate new paths
    invalidate_with_retry "/"
    invalidate_with_retry "/feed/"
    
    echo "Cache management complete"
else
    exit 1
fi
```

---

## Integration Examples

### Docker Container

```dockerfile
FROM wordpress:cli

# Install C3 plugin
RUN wp plugin install c3-cloudfront-clear-cache --activate

# Add cache management script
COPY scripts/cache-manager.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/cache-manager.sh

# Set environment variables
ENV AWS_ACCESS_KEY_ID=""
ENV AWS_SECRET_ACCESS_KEY=""
ENV C3_DISTRIBUTION_ID=""

ENTRYPOINT ["cache-manager.sh"]
```

### Kubernetes CronJob

```yaml
apiVersion: batch/v1
kind: CronJob
metadata:
  name: c3-cache-manager
spec:
  schedule: "*/5 * * * *"
  jobTemplate:
    spec:
      template:
        spec:
          containers:
          - name: wp-cli
            image: wordpress:cli
            command:
            - /bin/bash
            - -c
            - wp c3 queue --process
            env:
            - name: AWS_ACCESS_KEY_ID
              valueFrom:
                secretKeyRef:
                  name: aws-credentials
                  key: access-key-id
            - name: AWS_SECRET_ACCESS_KEY
              valueFrom:
                secretKeyRef:
                  name: aws-credentials
                  key: secret-access-key
            - name: C3_DISTRIBUTION_ID
              value: "E1234567890123"
          restartPolicy: Never
```

---

## Performance Considerations

### Batch Operations

For large invalidations, use batching:

```bash
# Instead of individual calls
wp c3 invalidate /page1/ /page2/ /page3/ ... /page100/

# Use file input for large lists
echo "/page1/
/page2/
/page3/" | xargs wp c3 invalidate
```

### Parallel Processing

```bash
# Process multiple invalidations in parallel
echo "/path1/ /path2/ /path3/" | xargs -n1 -P3 wp c3 invalidate
```

### Resource Monitoring

```bash
# Monitor queue size before processing
QUEUE_SIZE=$(wp c3 queue --status --format=json | jq '.total_items')
if [[ $QUEUE_SIZE -gt 1000 ]]; then
    echo "Warning: Large queue detected ($QUEUE_SIZE items)"
fi
```