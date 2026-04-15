# Integration Testing Guide

This directory contains integration tests that require a real WordPress environment. These tests cannot be run with mocked WordPress functions and require a full WordPress installation.

## Prerequisites

1. **WordPress Test Suite**: Install the WordPress test suite
   ```bash
   bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

2. **Test Database**: A separate MySQL database for testing

3. **WordPress Plugins**: For plugin compatibility tests
   - WPML (for hreflang testing)
   - Polylang (for hreflang testing)

4. **WordPress Themes**: For theme compatibility tests
   - Twenty Twenty-Four
   - Astra
   - GeneratePress

## Running Integration Tests

### All Integration Tests
```bash
phpunit tests/integration/
```

### Specific Test Suites
```bash
# Theme compatibility
phpunit tests/integration/ThemeCompatibilityTest.php

# Plugin compatibility
phpunit tests/integration/PluginCompatibilityTest.php

# Performance benchmarks
phpunit tests/integration/PerformanceBenchmarkTest.php
```

## Test Categories

### 1. Theme Compatibility Tests
Tests that the Meta Module works correctly with popular WordPress themes:
- No duplicate meta tags
- Correct hook priorities
- Title tag control
- Theme-specific edge cases

### 2. Plugin Compatibility Tests
Tests that the Meta Module works correctly with other plugins:
- WPML integration (hreflang alternates)
- Polylang integration (hreflang alternates)
- No conflicts with other SEO plugins

### 3. Performance Benchmarks
Tests that the Meta Module meets performance requirements:
- Database queries (should be 0 with cache)
- Memory usage (< 1MB per request)
- Execution time (< 10ms for output_head_tags)
- Cache hit rate (> 95%)

## CI/CD Integration

These tests should be run in a CI/CD pipeline with a full WordPress installation. Example GitHub Actions workflow:

```yaml
name: Integration Tests

on: [push, pull_request]

jobs:
  integration-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
          extensions: mysqli
      
      - name: Install WordPress Test Suite
        run: bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest
      
      - name: Install Dependencies
        run: composer install
      
      - name: Run Integration Tests
        run: phpunit tests/integration/
```

## Manual Testing

For manual testing of theme and plugin compatibility:

1. Install WordPress locally
2. Install MeowSEO plugin
3. Activate test theme/plugin
4. Verify:
   - View page source and check meta tags
   - No duplicate title tags
   - All meta tags present in correct order
   - No PHP errors in debug.log

## Notes

- Integration tests are slower than unit tests
- They require a real database and WordPress installation
- They should be run in a separate CI/CD pipeline
- Manual testing is recommended for theme/plugin compatibility
