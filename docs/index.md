---
layout: home

hero:
  name: "C3 CloudFront Cache Controller"
  text: "WordPress Plugin"
  tagline: "Efficiently manage CloudFront cache invalidation for your WordPress site"
  image:
    src: /logo.svg
    alt: C3 CloudFront Cache Controller
  actions:
    - theme: brand
      text: Get Started
      link: /guide/installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/amimoto-ami/c3-cloudfront-clear-cache

features:
  - icon: ⚡
    title: Automatic Invalidation
    details: Automatically invalidate CloudFront cache when posts are published or updated
  - icon: 🎯
    title: Targeted Invalidation
    details: Invalidate specific paths instead of clearing entire cache
  - icon: ⚙️
    title: Highly Configurable
    details: Customize invalidation behavior with filters and hooks
  - icon: 📱
    title: WP-CLI Support
    details: Manage cache invalidation from command line
  - icon: 🔒
    title: Secure
    details: Support for IAM roles, environment variables, and custom AWS implementations
  - icon: 📊
    title: Logging & Monitoring
    details: Comprehensive logging for debugging and monitoring
---

## Quick Start

Install the plugin and configure your AWS CloudFront settings:

### Installation

```bash
# Using WP-CLI (recommended)
wp plugin install c3-cloudfront-clear-cache --activate

# Or install via WordPress Admin: Plugins > Add New > Search "C3 CloudFront Clear Cache"
```

### Configuration

After installation, configure the plugin through the WordPress admin:

1. Go to **Settings > C3 CloudFront Cache**
2. Enter your CloudFront Distribution ID
3. Enter your AWS Access Key and Secret Key
4. Click **Save Changes**

Alternatively, you can set environment variables for enhanced security:

```bash
# Set environment variables (optional)
export AWS_ACCESS_KEY_ID=your_access_key
export AWS_SECRET_ACCESS_KEY=your_secret_key
export C3_DISTRIBUTION_ID=your_distribution_id
```

## Why C3 CloudFront Cache Controller?

When running WordPress behind AWS CloudFront, you need a reliable way to invalidate cached content when your site updates. This plugin provides:

- **Automatic cache invalidation** when content changes
- **Batch processing** for efficient CloudFront API usage
- **Custom invalidation paths** through powerful filters
- **Production-ready** with comprehensive error handling

## WordPress.org Plugin

[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/c3-cloudfront-clear-cache.svg)](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)
[![WordPress Plugin Version](https://img.shields.io/wordpress/v/c3-cloudfront-clear-cache.svg)](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/c3-cloudfront-clear-cache.svg)](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)

The plugin is available on the [WordPress Plugin Directory](https://wordpress.org/plugins/c3-cloudfront-clear-cache/).

## Community

- **GitHub Issues**: [Report bugs and request features](https://github.com/amimoto-ami/c3-cloudfront-clear-cache/issues)
- **WordPress Support**: [Get help on WordPress.org](https://wordpress.org/support/plugin/c3-cloudfront-clear-cache/)