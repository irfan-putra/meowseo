# MeowSEO WP-CLI Commands

This directory contains WP-CLI command implementations for MeowSEO schema and sitemap operations.

## Available Commands

### Schema Commands

#### Generate Schema
Generate schema JSON-LD for a specific post:
```bash
wp meowseo schema generate <post_id>
```

Example:
```bash
wp meowseo schema generate 123
```

#### Validate Schema
Validate schema JSON-LD for a specific post:
```bash
wp meowseo schema validate <post_id>
```

Example:
```bash
wp meowseo schema validate 123
```

#### Clear Schema Cache
Clear schema cache for a specific post or all posts:
```bash
# Clear cache for specific post
wp meowseo schema clear-cache --post_id=123

# Clear all schema cache
wp meowseo schema clear-cache
```

### Sitemap Commands

#### Generate Sitemaps
Generate all sitemaps or a specific sitemap:
```bash
# Generate all sitemaps
wp meowseo sitemap generate

# Generate posts sitemap
wp meowseo sitemap generate post

# Generate specific page of posts sitemap
wp meowseo sitemap generate post --page=2

# Generate pages sitemap
wp meowseo sitemap generate page

# Generate news sitemap
wp meowseo sitemap generate news

# Generate video sitemap
wp meowseo sitemap generate video

# Generate custom post type sitemap
wp meowseo sitemap generate product
```

#### Clear Sitemap Cache
Clear sitemap cache for a specific type or all sitemaps:
```bash
# Clear cache for specific sitemap type
wp meowseo sitemap clear-cache --type=post

# Clear all sitemap cache
wp meowseo sitemap clear-cache
```

#### Ping Search Engines
Notify Google and Bing about sitemap updates:
```bash
wp meowseo sitemap ping
```

## Implementation Details

### Files
- `class-schema-cli.php` - Schema WP-CLI commands
- `class-sitemap-cli.php` - Sitemap WP-CLI commands
- `class-cli-commands.php` - CLI commands registration

### Registration
Commands are registered in `meowseo.php` on the `plugins_loaded` hook at priority 20 to ensure the plugin is fully initialized before command registration.

### Error Handling
All commands include comprehensive error handling and validation:
- Invalid post IDs are rejected with clear error messages
- Missing posts are detected and reported
- Schema validation checks for required properties and valid formats
- Sitemap generation handles empty results gracefully

### Output
Commands provide clear, informative output:
- Success messages confirm operations completed
- Warning messages indicate non-critical issues
- Error messages explain what went wrong
- Progress indicators show what's happening during long operations

## Requirements Validation

These commands satisfy the following requirements from the design document:

**Schema Commands** (Design section "WP-CLI Commands"):
- ✓ `wp meowseo schema generate <post_id>` - Generate schema for a post
- ✓ `wp meowseo schema validate <post_id>` - Validate schema for a post
- ✓ `wp meowseo schema clear-cache [--post_id=<id>]` - Clear schema cache

**Sitemap Commands** (Design section "WP-CLI Commands"):
- ✓ `wp meowseo sitemap generate` - Generate all sitemaps
- ✓ `wp meowseo sitemap generate <type> [--page=<page>]` - Generate specific sitemap
- ✓ `wp meowseo sitemap clear-cache [--type=<type>]` - Clear sitemap cache
- ✓ `wp meowseo sitemap ping` - Ping search engines

## Usage Examples

### Debugging Schema Issues
```bash
# Generate and view schema for a post
wp meowseo schema generate 123

# Validate schema to check for errors
wp meowseo schema validate 123

# Clear cache and regenerate
wp meowseo schema clear-cache --post_id=123
wp meowseo schema generate 123
```

### Sitemap Maintenance
```bash
# Regenerate all sitemaps
wp meowseo sitemap generate

# Clear cache and regenerate specific sitemap
wp meowseo sitemap clear-cache --type=post
wp meowseo sitemap generate post

# Notify search engines after updates
wp meowseo sitemap ping
```

### Bulk Operations
```bash
# Clear all schema cache
wp meowseo schema clear-cache

# Clear all sitemap cache
wp meowseo sitemap clear-cache

# Regenerate everything
wp meowseo sitemap generate
wp meowseo sitemap ping
```
