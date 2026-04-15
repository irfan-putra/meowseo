# Integration Testing Guide for Meta Module

This document provides instructions for running integration tests for the MeowSEO Meta Module rebuild.

## Overview

Integration tests verify that the Meta Module works correctly in a real WordPress environment with actual themes, plugins, and database interactions. These tests complement the unit tests by testing the full integration stack.

## Test Categories

### 1. Theme Compatibility Tests (`ThemeCompatibilityTest.php`)
Tests that verify the Meta Module works correctly with popular WordPress themes:
- **Twenty Twenty-Four**: No duplicate title tags
- **Astra**: No duplicate meta description tags
- **GeneratePress**: Correct hook priorities
- **All Themes**: Meta tag output order verification

### 2. Plugin Compatibility Tests (`PluginCompatibilityTest.php`)
Tests that verify the Meta Module works correctly with other plugins:
- **WPML**: Hreflang alternate links output
- **Polylang**: Hreflang alternate links output
- **No Multilingual Plugin**: No hreflang output
- **Yoast SEO**: No conflicts when deactivated
- **RankMath**: No conflicts when deactivated

### 3. Performance Benchmark Tests (`PerformanceBenchmarkTest.php`)
Tests that verify the Meta Module meets performance requirements:
- **Database Queries**: 0 queries with cache
- **Memory Usage**: < 1MB per request
- **Execution Time**: < 10ms for output_head_tags
- **Cache Hit Rate**: > 95%
- **Large Content**: Performance with 10,000 word posts
- **Many Meta Fields**: Performance with all meta fields set

## Prerequisites

### 1. WordPress Test Suite Installation

Install the WordPress test suite using the provided script:

```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

Parameters:
- `wordpress_test`: Database name for tests
- `root`: Database user
- `''`: Database password (empty for local development)
- `localhost`: Database host
- `latest`: WordPress version (or specific version like `6.4`)

### 2. Test Database

Create a separate MySQL database for testing:

```sql
CREATE DATABASE wordpress_test;
GRANT ALL PRIVILEGES ON wordpress_test.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Required Plugins (for compatibility tests)

Download and place in `wp-content/plugins/`:
- **WPML**: [https://wpml.org/](https://wpml.org/)
- **Polylang**: [https://wordpress.org/plugins/polylang/](https://wordpress.org/plugins/polylang/)

### 4. Required Themes (for theme compatibility tests)

Download and place in `wp-content/themes/`:
- **Twenty Twenty-Four**: Included with WordPress
- **Astra**: [https://wordpress.org/themes/astra/](https://wordpress.org/themes/astra/)
- **GeneratePress**: [https://wordpress.org/themes/generatepress/](https://wordpress.org/themes/generatepress/)

## Running Integration Tests

### Run All Integration Tests

```bash
phpunit tests/integration/
```

### Run Specific Test Suite

```bash
# Theme compatibility
phpunit tests/integration/ThemeCompatibilityTest.php

# Plugin compatibility
phpunit tests/integration/PluginCompatibilityTest.php

# Performance benchmarks
phpunit tests/integration/PerformanceBenchmarkTest.php
```

### Run Specific Test Method

```bash
phpunit --filter test_no_duplicate_title_tags_twentytwentyfour tests/integration/ThemeCompatibilityTest.php
```

## Expected Results

### Theme Compatibility Tests
- ✅ All tests should pass with no duplicate meta tags
- ✅ Meta tags should appear in correct order
- ✅ Hook priorities should be correct (wp_head at priority 1)

### Plugin Compatibility Tests
- ✅ Hreflang alternates should output with WPML/Polylang
- ✅ No hreflang alternates without multilingual plugins
- ✅ No conflicts with other SEO plugins when deactivated

### Performance Benchmark Tests
- ✅ 0 database queries with cache
- ✅ < 1MB memory usage per request
- ✅ < 10ms execution time for output_head_tags
- ✅ > 95% cache hit rate

## Troubleshooting

### Tests Skipped

If tests are skipped with messages like "Theme X is not installed" or "Plugin Y is not installed", install the required theme/plugin and run the tests again.

### Database Connection Errors

Verify your database credentials in `wp-tests-config.php`:
```php
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
```

### Performance Test Failures

Performance tests may fail on slow systems. Adjust the thresholds in the test file if needed:
- Increase execution time limit (currently 10ms)
- Increase memory limit (currently 1MB)
- Decrease cache hit rate requirement (currently 95%)

## CI/CD Integration

### GitHub Actions Example

Create `.github/workflows/integration-tests.yml`:

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
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
          extensions: mysqli, mbstring
          coverage: none
      
      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Install WordPress Test Suite
        run: bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest
      
      - name: Run Integration Tests
        run: vendor/bin/phpunit tests/integration/
```

## Manual Testing Checklist

For manual verification of theme and plugin compatibility:

### Theme Compatibility
- [ ] Install and activate test theme
- [ ] Create a test post
- [ ] View page source
- [ ] Verify single `<title>` tag
- [ ] Verify meta tags in correct order
- [ ] Verify no duplicate meta tags
- [ ] Check browser console for errors

### Plugin Compatibility
- [ ] Install and activate test plugin
- [ ] Create a test post
- [ ] View page source
- [ ] Verify expected behavior (e.g., hreflang with WPML)
- [ ] Verify no conflicts or duplicate tags
- [ ] Check browser console for errors

### Performance Testing
- [ ] Install Query Monitor plugin
- [ ] View a test post
- [ ] Check Query Monitor for:
  - Database queries (should be minimal with cache)
  - Memory usage (should be < 1MB)
  - Execution time (should be < 10ms)

## Notes

- Integration tests are slower than unit tests (expect 30-60 seconds)
- Tests require a real WordPress installation and database
- Tests may modify the test database (use a separate database)
- Some tests require specific plugins/themes to be installed
- Performance tests are sensitive to system resources
- Run integration tests in CI/CD for consistent results

## Support

For issues with integration tests:
1. Check that WordPress test suite is installed correctly
2. Verify database credentials
3. Ensure required plugins/themes are installed
4. Check PHP error logs for detailed error messages
5. Run tests with `--debug` flag for verbose output
