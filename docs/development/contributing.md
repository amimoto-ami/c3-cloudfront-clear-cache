# Contributing

Thank you for your interest in contributing to C3 CloudFront Cache Controller! This guide will help you get started with development and contributing to the project.

## Getting Started

### Prerequisites

- **PHP**: 7.4 or higher
- **WordPress**: 5.0 or higher
- **Node.js**: 16 or higher (for development tools)
- **Composer**: For dependency management
- **Git**: For version control

### Development Environment Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/amimoto-ami/c3-cloudfront-clear-cache.git
   cd c3-cloudfront-clear-cache
   ```

2. **Install dependencies:**
   ```bash
   # PHP dependencies
   composer install
   
   # Node.js dependencies (for testing and docs)
   npm install
   ```

3. **Set up WordPress environment:**
   ```bash
   # Using wp-env (recommended)
   npm run dev
   
   # Or set up manually in your preferred WordPress environment
   ```

4. **Configure environment variables:**
   ```bash
   # Copy example environment file
   cp .env.example .env
   
   # Edit .env with your AWS credentials (for testing)
   ```

## Development Workflow

### Branch Strategy

- `master` - Main production branch
- `develop` - Development branch for new features
- `feature/*` - Feature branches
- `bugfix/*` - Bug fix branches
- `docs/*` - Documentation branches

### Creating a Feature Branch

```bash
# Start from develop branch
git checkout develop
git pull origin develop

# Create feature branch
git checkout -b feature/your-feature-name

# Make your changes and commit
git add .
git commit -m "feat: add your feature description"

# Push and create pull request
git push origin feature/your-feature-name
```

## Code Standards

### PHP Coding Standards

We follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) with some modifications:

```php
<?php
/**
 * Class documentation
 *
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller;

class Example_Class {
    /**
     * Method documentation
     *
     * @param string $param Parameter description.
     * @return bool Return description.
     */
    public function example_method( $param ) {
        // Implementation
        return true;
    }
}
```

### Code Quality Tools

We use several tools to maintain code quality:

```bash
# PHP CodeSniffer
composer run phpcs

# PHP Code Beautifier and Fixer
composer run phpcbf

# PHPStan (static analysis)
composer run phpstan

# Run all quality checks
composer run quality
```

### Naming Conventions

- **Classes**: `PascalCase` with namespace
- **Methods**: `snake_case`
- **Variables**: `snake_case`
- **Constants**: `UPPER_SNAKE_CASE`
- **Hooks**: `c3_` prefix for actions/filters

Example:
```php
namespace C3_CloudFront_Cache_Controller\AWS;

class CloudFront_Service {
    const DEFAULT_TIMEOUT = 30;
    
    private $api_client;
    
    public function create_invalidation( $distribution_id, $paths ) {
        do_action( 'c3_before_invalidation', $paths );
        // Implementation
    }
}
```

## Testing

### Running Tests

```bash
# Run all tests
npm run test

# Run specific test suite
composer run test:unit
composer run test:integration

# Run tests with coverage
composer run test:coverage
```

### Writing Tests

#### Unit Tests

```php
<?php
class CloudFront_Service_Test extends WP_UnitTestCase {
    
    private $service;
    
    public function setUp(): void {
        parent::setUp();
        $this->service = new C3_CloudFront_Cache_Controller\AWS\CloudFront_Service();
    }
    
    public function test_create_invalidation_with_valid_params() {
        $result = $this->service->create_invalidation( 'E123456789', [ '/' ] );
        
        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'invalidation_id', $result );
    }
}
```

#### Integration Tests

```php
<?php
class Invalidation_Integration_Test extends WP_UnitTestCase {
    
    public function test_post_publish_triggers_invalidation() {
        // Create a post
        $post_id = $this->factory->post->create( [
            'post_title' => 'Test Post',
            'post_status' => 'publish'
        ] );
        
        // Assert invalidation was triggered
        $this->assertTrue( did_action( 'c3_invalidate_cache' ) > 0 );
    }
}
```

### Test Environment Configuration

```bash
# Set up test database
bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Configure AWS credentials for testing
export AWS_ACCESS_KEY_ID=test_key
export AWS_SECRET_ACCESS_KEY=test_secret
export C3_DISTRIBUTION_ID=test_distribution
```

## Documentation

### Code Documentation

All classes and methods should be documented using PHPDoc:

```php
/**
 * Service for managing CloudFront invalidations
 *
 * Handles the creation and management of CloudFront cache invalidation
 * requests through the AWS API.
 *
 * @since 1.0.0
 * @package C3_CloudFront_Cache_Controller\AWS
 */
class CloudFront_Service {
    
    /**
     * Create a new CloudFront invalidation
     *
     * @since 1.0.0
     *
     * @param string $distribution_id CloudFront distribution ID.
     * @param array  $paths          Array of paths to invalidate.
     * @return array|WP_Error        Invalidation result or error.
     */
    public function create_invalidation( $distribution_id, $paths ) {
        // Implementation
    }
}
```

### Inline Documentation

```php
// Check if credentials are valid
if ( ! $this->validate_credentials( $credentials ) ) {
    return new WP_Error( 'invalid_credentials', 'AWS credentials are invalid' );
}

/**
 * Note: CloudFront has a limit of 1000 paths per invalidation request.
 * We batch requests to stay within this limit.
 */
$batches = array_chunk( $paths, 1000 );
```

### User Documentation

Documentation is built with VitePress. To work on documentation:

```bash
# Start development server
npm run docs:dev

# Build documentation
npm run docs:build

# Preview built documentation
npm run docs:preview
```

## Architecture

### Plugin Structure

```
c3-cloudfront-clear-cache/
├── classes/                 # Core classes
│   ├── AWS/                # AWS-specific classes
│   ├── WP/                 # WordPress integration classes
│   └── Views/              # Admin interface classes
├── templates/              # HTML templates
├── tests/                  # Test files
├── docs/                   # Documentation
├── bin/                    # Build scripts
└── c3-cloudfront-clear-cache.php  # Main plugin file
```

### Class Organization

- **Core Services**: `Invalidation_Service`, `Cron_Service`, `Settings_Service`
- **AWS Integration**: `CloudFront_Service`, `Invalidation_Batch_Service`
- **WordPress Integration**: `Hooks`, `Options_Service`, `Post_Service`
- **Utilities**: `Class_Loader`, `Constants`

### Design Patterns

- **Service Layer**: Business logic separated into service classes
- **Dependency Injection**: Services receive dependencies through constructor
- **Observer Pattern**: WordPress hooks for extensibility
- **Factory Pattern**: For creating AWS service instances

## Pull Request Process

### Before Submitting

1. **Run tests**: Ensure all tests pass
2. **Code quality**: Run linting and static analysis
3. **Documentation**: Update docs if needed
4. **Changelog**: Add entry to CHANGELOG.md

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] Manual testing completed

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or marked as such)
```

### Review Process

1. **Automated checks**: CI/CD runs tests and quality checks
2. **Code review**: Maintainers review code and provide feedback
3. **Testing**: Changes are tested in staging environment
4. **Approval**: At least one maintainer approval required
5. **Merge**: Squash and merge to maintain clean history

## Release Process

### Version Management

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Creating a Release

1. **Update version numbers:**
   ```bash
   # Update plugin header
   # Update package.json
   # Update README files
   ```

2. **Update changelog:**
   ```markdown
   ## [1.2.0] - 2024-01-15
   ### Added
   - New feature description
   
   ### Changed
   - Changed feature description
   
   ### Fixed
   - Bug fix description
   ```

3. **Create release:**
   ```bash
   git tag -a v1.2.0 -m "Release version 1.2.0"
   git push origin v1.2.0
   ```

## Security

### Reporting Vulnerabilities

Please report security vulnerabilities privately to [security@yourproject.com](mailto:security@yourproject.com).

### Security Guidelines

- **Input validation**: Always validate and sanitize user input
- **Output escaping**: Escape output appropriately
- **Capabilities**: Check user capabilities before sensitive operations
- **Nonces**: Use nonces for form submissions
- **Secrets**: Never commit credentials or secrets

Example:
```php
// Input validation
$paths = array_map( 'sanitize_text_field', $_POST['paths'] ?? [] );

// Capability check
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Insufficient permissions' );
}

// Nonce verification
if ( ! wp_verify_nonce( $_POST['nonce'], 'c3_invalidate' ) ) {
    wp_die( 'Invalid nonce' );
}

// Output escaping
echo esc_html( $message );
```

## Community

### Communication Channels

- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: General questions and community discussion
- **WordPress.org Support**: User support and troubleshooting

### Code of Conduct

We are committed to providing a welcoming and inclusive environment. All contributors are expected to follow our community guidelines and maintain respectful, professional communication.

### Recognition

Contributors are recognized in:
- CONTRIBUTORS.md file
- Release notes
- Plugin credits

## Resources

### Development Resources

- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [AWS CloudFront API Documentation](https://docs.aws.amazon.com/cloudfront/latest/APIReference/)

### Tools and Libraries

- [WP-CLI](https://wp-cli.org/) - Command line tool for WordPress
- [PHPUnit](https://phpunit.de/) - Testing framework
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) - Code style checker
- [VitePress](https://vitepress.dev/) - Documentation generator

Thank you for contributing to C3 CloudFront Cache Controller!