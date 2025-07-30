# Contributing

Thank you for your interest in contributing to C3 CloudFront Cache Controller!

## Development Environment Setup

### Prerequisites

- **PHP**: 7.4 or higher
- **Node.js**: 20 or higher
- **Git**: For version control

### Setup Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/amimoto-ami/c3-cloudfront-clear-cache.git
   cd c3-cloudfront-clear-cache
   ```

2. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

3. **Start development environment with wp-env:**
   ```bash
   npm run dev
   ```

4. **Install PHP dependencies**
   ```bash
   npm run composer:install
   ```

## Development Workflow

### Branch Strategy

- `master` - Production branch
- `develop` - Development branch
- `feature/*` - Feature branches
- `bugfix/*` - Bug fix branches

### Creating a Feature Branch

```bash
git checkout develop
git pull origin develop
git checkout -b feature/your-feature-name

# Make changes and commit
git add .
git commit -m "feat: add your feature description"

# Create pull request
git push origin feature/your-feature-name
```

## Testing

### Running Tests

```bash
# Run all tests
npm run test

# Run specific test suites
wp-env run tests composer run test:unit
wp-env run tests composer run test:integration
```

## Code Quality

### Code Style Checks

```bash
# PHP CodeSniffer
wp-env run tests composer run phpcs

# Auto-fix code style
wp-env run tests composer run phpcbf
```

## Pull Requests

### Pre-submission Checklist

- [ ] All tests pass
- [ ] Code follows style guidelines
- [ ] Documentation updated (if needed)

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
- [ ] Manual testing completed
```

## Resources

- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [AWS CloudFront API Documentation](https://docs.aws.amazon.com/cloudfront/latest/APIReference/)

Thank you for contributing!