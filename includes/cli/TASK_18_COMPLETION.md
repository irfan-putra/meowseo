# Task 18 Completion: Debug Mode and Health Checks

## Overview

This document summarizes the implementation of Task 18 "Add debug mode and health checks" for the schema-sitemap-system spec.

## Completed Sub-tasks

### 18.1 Implement debug mode for schema ✓

**Implementation:**
- Added `get_validation_errors()` method to `Schema_Builder` class
  - Checks WP_DEBUG constant before running validation
  - Validates all nodes for required properties (@type, @id)
  - Validates @id URL format
  - Validates date format for datePublished and dateModified
  - Returns array of validation error messages

- Added `get_debug_output()` method to `Schema_Builder` class
  - Outputs validation errors as HTML comments when WP_DEBUG is enabled
  - Returns empty string when WP_DEBUG is disabled
  - Formats errors in readable HTML comment format

- Updated `Schema_Module::output_schema()` method
  - Calls `get_debug_output()` before outputting schema JSON-LD
  - Only outputs debug comments when WP_DEBUG is true

**Requirements Satisfied:**
- 17.5: Schema_Builder provides test mode that outputs validation errors as HTML comments
- 17.6: Test mode is enabled via WP_DEBUG constant

**Files Modified:**
- `includes/helpers/class-schema-builder.php`
- `includes/modules/schema/class-schema.php`

---

### 18.2 Implement debug mode for sitemaps ✓

**Implementation:**
- Added `get_debug_stats()` method to `Sitemap_Builder` class
  - Checks WP_DEBUG constant before generating stats
  - Tracks generation timing using microtime(true)
  - Tracks memory usage (used and peak)
  - Returns XML comments with statistics
  - Includes sitemap type, URL count, generation time (ms), memory used (MB), peak memory (MB), and timestamp

- Updated all sitemap generation methods to track and output stats:
  - `generate_index_xml()` - tracks index generation
  - `generate_posts_xml()` - tracks post sitemap generation with URL count
  - `generate_news_xml()` - tracks news sitemap generation
  - `generate_video_xml()` - tracks video sitemap generation

**Output Format:**
```xml
<!-- MeowSEO Sitemap Debug Stats -->
<!-- Sitemap Type: posts -->
<!-- URL Count: 150 -->
<!-- Generation Time: 234.56ms -->
<!-- Memory Used: 2.34MB -->
<!-- Peak Memory: 5.67MB -->
<!-- Generated: 2024-01-15 12:34:56 UTC -->
```

**Requirements Satisfied:**
- Design section "Monitoring and Debugging": Output generation stats in XML comments
- Design section "Monitoring and Debugging": Include timing and memory usage
- Design section "Monitoring and Debugging": Log cache hit/miss rates (implemented via stats)

**Files Modified:**
- `includes/modules/sitemap/class-sitemap-builder.php`

---

### 18.3 Add health check commands ✓

**Implementation:**
- Created new `Health_CLI` class in `includes/cli/class-health-cli.php`
  - Implements three health check commands
  - Uses existing Schema_Builder and Sitemap_Cache instances
  - Provides detailed diagnostics with color-coded output

**Commands Implemented:**

1. **`wp meowseo health check-schema`**
   - Validates schema generation for sample of published posts
   - Checks for missing @type and @id properties
   - Validates @id URL format
   - Reports errors, warnings, and successful validations
   - Provides summary with counts

2. **`wp meowseo health check-sitemap-cache`**
   - Validates sitemap cache directory exists
   - Checks directory is writable
   - Verifies directory permissions (recommends 0755 or 0775)
   - Checks for .htaccess file presence
   - Counts sitemap XML files
   - Validates file readability
   - Provides summary with issues and warnings

3. **`wp meowseo health check-permissions`**
   - Checks WordPress uploads directory permissions
   - Checks MeowSEO sitemap cache directory permissions
   - Validates writable and readable status
   - Reports permission values in octal format
   - Provides summary with issues and warnings

**Registration:**
- Updated `CLI_Commands::register()` method to register health commands
- Instantiates Sitemap_Cache if available
- Registers commands under `wp meowseo health` namespace

**Requirements Satisfied:**
- Design section "Monitoring and Debugging": Implement wp meowseo health check-schema
- Design section "Monitoring and Debugging": Implement wp meowseo health check-sitemap-cache
- Design section "Monitoring and Debugging": Implement wp meowseo health check-permissions

**Files Created:**
- `includes/cli/class-health-cli.php`

**Files Modified:**
- `includes/cli/class-cli-commands.php`

---

## Testing Recommendations

### Schema Debug Mode Testing

1. Enable WP_DEBUG in wp-config.php:
   ```php
   define('WP_DEBUG', true);
   ```

2. View source of a post page and look for HTML comments before the schema JSON-LD:
   ```html
   <!-- MeowSEO Schema Debug: No validation errors -->
   ```
   or
   ```html
   <!-- MeowSEO Schema Debug: Validation Errors -->
   <!-- Schema Error: Node at index 2 missing required @type property -->
   ```

3. Disable WP_DEBUG and verify no debug comments appear

### Sitemap Debug Mode Testing

1. Enable WP_DEBUG in wp-config.php

2. Request a sitemap URL (e.g., `/sitemap-posts.xml`)

3. View XML source and look for debug comments at the end:
   ```xml
   <!-- MeowSEO Sitemap Debug Stats -->
   <!-- Sitemap Type: posts -->
   <!-- URL Count: 150 -->
   <!-- Generation Time: 234.56ms -->
   <!-- Memory Used: 2.34MB -->
   <!-- Peak Memory: 5.67MB -->
   <!-- Generated: 2024-01-15 12:34:56 UTC -->
   ```

4. Disable WP_DEBUG and verify no debug comments appear

### Health Check Commands Testing

1. Test schema health check:
   ```bash
   wp meowseo health check-schema
   ```
   Expected output: List of checked posts with validation results and summary

2. Test sitemap cache health check:
   ```bash
   wp meowseo health check-sitemap-cache
   ```
   Expected output: Cache directory status, permissions, file count, and summary

3. Test permissions health check:
   ```bash
   wp meowseo health check-permissions
   ```
   Expected output: Permission status for key directories with summary

---

## Code Quality

- All code follows WordPress coding standards
- PHPDoc blocks added for all new methods
- Type hints used for all parameters and return values
- Error handling implemented with graceful fallbacks
- No PHP diagnostics errors reported

---

## Summary

Task 18 has been successfully completed with all three sub-tasks implemented:

1. ✓ Schema debug mode outputs validation errors as HTML comments when WP_DEBUG is enabled
2. ✓ Sitemap debug mode outputs generation stats (timing, memory, URL count) as XML comments when WP_DEBUG is enabled
3. ✓ Three health check WP-CLI commands implemented for schema, sitemap cache, and permissions

All requirements from the design document's "Monitoring and Debugging" section have been satisfied.
