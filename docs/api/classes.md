# Classes API Reference

This page documents the main classes provided by C3 CloudFront Cache Controller for developers who want to integrate with or extend the plugin.

## Core Classes

### `C3_CloudFront_Cache_Controller\Invalidation_Service`

Main service class that handles cache invalidation logic.

**Namespace:** `C3_CloudFront_Cache_Controller`  
**File:** `classes/Invalidation_Service.php`

#### Methods

##### `invalidate($paths, $async = false)`

Invalidate specific paths in CloudFront cache.

**Parameters:**
- `$paths` (array|string) - Path(s) to invalidate
- `$async` (bool) - Whether to process asynchronously (default: false)

**Returns:** `array|WP_Error` - Invalidation result or error

**Example:**
```php
$service = new C3_CloudFront_Cache_Controller\Invalidation_Service();
$result = $service->invalidate(['/'], false);

if (is_wp_error($result)) {
    echo 'Error: ' . $result->get_error_message();
} else {
    echo 'Invalidation ID: ' . $result['invalidation_id'];
}
```

##### `get_invalidation_paths($post)`

Generate invalidation paths for a given post.

**Parameters:**
- `$post` (WP_Post) - Post object

**Returns:** `array` - Array of paths to invalidate

**Example:**
```php
$service = new C3_CloudFront_Cache_Controller\Invalidation_Service();
$paths = $service->get_invalidation_paths($post);
foreach ($paths as $path) {
    echo "Will invalidate: $path\n";
}
```

---

### `C3_CloudFront_Cache_Controller\AWS\CloudFront_Service`

Service class for CloudFront API interactions.

**Namespace:** `C3_CloudFront_Cache_Controller\AWS`  
**File:** `classes/AWS/CloudFront_Service.php`

#### Methods

##### `create_invalidation($distribution_id, $paths)`

Create a CloudFront invalidation request.

**Parameters:**
- `$distribution_id` (string) - CloudFront distribution ID
- `$paths` (array) - Array of paths to invalidate

**Returns:** `array|WP_Error` - API response or error

**Example:**
```php
$service = new C3_CloudFront_Cache_Controller\AWS\CloudFront_Service();
$result = $service->create_invalidation('E1234567890123', ['/']);

if (!is_wp_error($result)) {
    echo 'Invalidation created: ' . $result['Invalidation']['Id'];
}
```

##### `get_invalidation($distribution_id, $invalidation_id)`

Get the status of an invalidation request.

**Parameters:**
- `$distribution_id` (string) - CloudFront distribution ID
- `$invalidation_id` (string) - Invalidation ID

**Returns:** `array|WP_Error` - Invalidation status or error

---

### `C3_CloudFront_Cache_Controller\AWS\Invalidation_Batch_Service`

Handles batching of invalidation requests.

**Namespace:** `C3_CloudFront_Cache_Controller\AWS`  
**File:** `classes/AWS/Invalidation_Batch_Service.php`

#### Methods

##### `create_batch($paths, $distribution_id)`

Create an invalidation batch.

**Parameters:**
- `$paths` (array) - Paths to invalidate
- `$distribution_id` (string) - CloudFront distribution ID

**Returns:** `C3_CloudFront_Cache_Controller\AWS\Invalidation_Batch` - Batch object

##### `process_batch($batch)`

Process an invalidation batch.

**Parameters:**
- `$batch` (Invalidation_Batch) - Batch to process

**Returns:** `array|WP_Error` - Processing result or error

---

### `C3_CloudFront_Cache_Controller\AWS\Invalidation_Batch`

Represents a batch of invalidation paths.

**Namespace:** `C3_CloudFront_Cache_Controller\AWS`  
**File:** `classes/AWS/Invalidation_Batch.php`

#### Methods

##### `add_path($path)`

Add a path to the batch.

**Parameters:**
- `$path` (string) - Path to add

**Returns:** `bool` - True if added successfully

##### `get_paths()`

Get all paths in the batch.

**Returns:** `array` - Array of paths

##### `get_path_count()`

Get the number of paths in the batch.

**Returns:** `int` - Number of paths

##### `is_full()`

Check if the batch is full (reached limit).

**Returns:** `bool` - True if batch is full

---

## WordPress Integration Classes

### `C3_CloudFront_Cache_Controller\WP\Hooks`

Manages WordPress hooks and filters.

**Namespace:** `C3_CloudFront_Cache_Controller\WP`  
**File:** `classes/WP/Hooks.php`

#### Methods

##### `register_hooks()`

Register all WordPress hooks.

**Example:**
```php
$hooks = new C3_CloudFront_Cache_Controller\WP\Hooks();
$hooks->register_hooks();
```

---

### `C3_CloudFront_Cache_Controller\WP\Options_Service`

Handles WordPress options management.

**Namespace:** `C3_CloudFront_Cache_Controller\WP`  
**File:** `classes/WP/Options_Service.php`

#### Methods

##### `get_option($key, $default = null)`

Get a plugin option.

**Parameters:**
- `$key` (string) - Option key
- `$default` (mixed) - Default value

**Returns:** `mixed` - Option value

##### `update_option($key, $value)`

Update a plugin option.

**Parameters:**
- `$key` (string) - Option key
- `$value` (mixed) - Option value

**Returns:** `bool` - True if updated successfully

##### `get_credentials()`

Get AWS credentials from options or environment.

**Returns:** `array|null` - Credentials array or null if not configured

**Example:**
```php
$options = new C3_CloudFront_Cache_Controller\WP\Options_Service();
$credentials = $options->get_credentials();

if ($credentials) {
    echo 'Distribution ID: ' . $credentials['distribution_id'];
}
```

---

### `C3_CloudFront_Cache_Controller\WP\Post_Service`

Handles post-related operations.

**Namespace:** `C3_CloudFront_Cache_Controller\WP`  
**File:** `classes/WP/Post_Service.php`

#### Methods

##### `get_post_paths($post)`

Get invalidation paths for a post.

**Parameters:**
- `$post` (WP_Post) - Post object

**Returns:** `array` - Array of paths

##### `should_invalidate_post($post)`

Check if a post should trigger invalidation.

**Parameters:**
- `$post` (WP_Post) - Post object

**Returns:** `bool` - True if should invalidate

---

## Utility Classes

### `C3_CloudFront_Cache_Controller\AWS\AWS_Signature_V4`

Handles AWS Signature Version 4 authentication.

**Namespace:** `C3_CloudFront_Cache_Controller\AWS`  
**File:** `classes/AWS/AWS_Signature_V4.php`

#### Methods

##### `sign_request($request, $credentials, $service, $region)`

Sign an AWS API request.

**Parameters:**
- `$request` (array) - HTTP request array
- `$credentials` (array) - AWS credentials
- `$service` (string) - AWS service name
- `$region` (string) - AWS region

**Returns:** `array` - Signed request headers

---

### `C3_CloudFront_Cache_Controller\AWS\CloudFront_HTTP_Client`

HTTP client for CloudFront API requests.

**Namespace:** `C3_CloudFront_Cache_Controller\AWS`  
**File:** `classes/AWS/CloudFront_HTTP_Client.php`

#### Methods

##### `request($method, $url, $args = [])`

Make an HTTP request to CloudFront API.

**Parameters:**
- `$method` (string) - HTTP method
- `$url` (string) - Request URL
- `$args` (array) - Request arguments

**Returns:** `array|WP_Error` - Response or error

---

## Usage Examples

### Custom Invalidation Service

```php
class My_Custom_Invalidation {
    private $invalidation_service;
    private $options_service;
    
    public function __construct() {
        $this->invalidation_service = new C3_CloudFront_Cache_Controller\Invalidation_Service();
        $this->options_service = new C3_CloudFront_Cache_Controller\WP\Options_Service();
        
        add_action('my_custom_event', [$this, 'handle_custom_invalidation']);
    }
    
    public function handle_custom_invalidation($data) {
        // Custom logic for determining paths
        $paths = $this->get_custom_paths($data);
        
        // Use the invalidation service
        $result = $this->invalidation_service->invalidate($paths);
        
        if (is_wp_error($result)) {
            error_log('Custom invalidation failed: ' . $result->get_error_message());
        }
    }
    
    private function get_custom_paths($data) {
        // Your custom path generation logic
        return ['/custom-page/', '/api/data.json'];
    }
}

new My_Custom_Invalidation();
```

### Batch Processing Integration

```php
class My_Batch_Processor {
    private $batch_service;
    
    public function __construct() {
        $this->batch_service = new C3_CloudFront_Cache_Controller\AWS\Invalidation_Batch_Service();
    }
    
    public function process_large_invalidation($paths) {
        $credentials = wp_options_service->get_credentials();
        
        if (!$credentials) {
            return new WP_Error('no_credentials', 'No AWS credentials configured');
        }
        
        // Create batch
        $batch = $this->batch_service->create_batch($paths, $credentials['distribution_id']);
        
        // Process batch
        $result = $this->batch_service->process_batch($batch);
        
        return $result;
    }
}
```

### Custom Options Management

```php
class My_Options_Manager {
    private $options_service;
    
    public function __construct() {
        $this->options_service = new C3_CloudFront_Cache_Controller\WP\Options_Service();
    }
    
    public function setup_multi_environment_config() {
        $env = wp_get_environment_type();
        
        switch ($env) {
            case 'production':
                $this->options_service->update_option('distribution_id', 'E123PROD456');
                break;
            case 'staging':
                $this->options_service->update_option('distribution_id', 'E123STAGE456');
                break;
        }
    }
    
    public function get_environment_credentials() {
        return $this->options_service->get_credentials();
    }
}
```

## Error Handling

All classes follow consistent error handling patterns:

```php
// Check for WP_Error in responses
$result = $service->some_method();
if (is_wp_error($result)) {
    $error_code = $result->get_error_code();
    $error_message = $result->get_error_message();
    
    // Handle specific error types
    switch ($error_code) {
        case 'invalid_credentials':
            // Handle credential errors
            break;
        case 'network_error':
            // Handle network errors
            break;
        default:
            // Handle generic errors
            break;
    }
}
```

## Class Autoloading

The plugin uses a custom class loader:

```php
// Classes are automatically loaded using PSR-4 style autoloading
// Base namespace: C3_CloudFront_Cache_Controller
// Base directory: classes/

// Example: C3_CloudFront_Cache_Controller\AWS\CloudFront_Service
// Maps to: classes/AWS/CloudFront_Service.php
```

## Extension Points

### Custom Service Classes

You can extend the core services:

```php
class My_Extended_Invalidation_Service extends C3_CloudFront_Cache_Controller\Invalidation_Service {
    
    public function invalidate($paths, $async = false) {
        // Custom pre-processing
        $paths = $this->preprocess_paths($paths);
        
        // Call parent method
        $result = parent::invalidate($paths, $async);
        
        // Custom post-processing
        $this->log_invalidation_result($result);
        
        return $result;
    }
    
    private function preprocess_paths($paths) {
        // Your custom path processing logic
        return array_unique($paths);
    }
    
    private function log_invalidation_result($result) {
        // Your custom logging logic
        if (!is_wp_error($result)) {
            error_log('Invalidation successful: ' . $result['invalidation_id']);
        }
    }
}
```

### Custom AWS Services

```php
class My_Custom_CloudFront_Service extends C3_CloudFront_Cache_Controller\AWS\CloudFront_Service {
    
    public function create_invalidation($distribution_id, $paths) {
        // Add custom logic before creating invalidation
        $this->validate_distribution($distribution_id);
        
        return parent::create_invalidation($distribution_id, $paths);
    }
    
    private function validate_distribution($distribution_id) {
        // Your custom validation logic
        if (!preg_match('/^E[A-Z0-9]+$/', $distribution_id)) {
            throw new InvalidArgumentException('Invalid distribution ID format');
        }
    }
}
```