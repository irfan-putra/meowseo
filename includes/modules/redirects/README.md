# Redirects Module

## Overview

The Redirects module provides URL redirect management with database-level matching for optimal performance. It implements a two-tier matching strategy:

1. **Exact Match** (O(log n)): Uses indexed database query on `source_url` column
2. **Regex Fallback**: Only loads and evaluates regex rules when needed

## Key Features

- **Database-Level Matching**: Never loads all redirect rules into PHP memory
- **Performance Optimization**: Uses `has_regex_rules` flag to skip regex matching when no regex rules exist
- **Hit Tracking**: Logs redirect hit counts and last-accessed timestamps
- **Multiple Redirect Types**: Supports 301, 302, 307, and 410 (Gone) status codes
- **REST API**: Full CRUD operations via `meowseo/v1/redirects` endpoints
- **Security**: All endpoints verify `manage_options` capability and nonce

## Architecture

### Redirect Matching Algorithm

```
1. Get current request URL
2. Normalize URL (strip query string if configured)
3. Try exact match query:
   SELECT * FROM meowseo_redirects 
   WHERE source_url = %s AND status = 'active' 
   LIMIT 1
4. If found → Execute redirect and exit
5. If not found → Check has_regex_rules flag
6. If has_regex_rules = true:
   - Load only regex rules (is_regex = 1)
   - Evaluate each pattern with preg_match
   - If match found → Execute redirect and exit
7. Continue normal WordPress request
```

### Database Schema

```sql
CREATE TABLE {prefix}meowseo_redirects (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_url    VARCHAR(2048)   NOT NULL,
    target_url    VARCHAR(2048)   NOT NULL,
    redirect_type SMALLINT        NOT NULL DEFAULT 301,
    is_regex      TINYINT(1)      NOT NULL DEFAULT 0,
    status        VARCHAR(10)     NOT NULL DEFAULT 'active',
    hit_count     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_accessed DATETIME        NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_source_url (source_url(191)),
    KEY idx_is_regex_status (is_regex, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Index Strategy**:
- `idx_source_url`: Enables O(log n) exact-match lookups
- `idx_is_regex_status`: Efficient filtering for regex rules

## REST API Endpoints

### GET /meowseo/v1/redirects

List all redirect rules with pagination.

**Query Parameters**:
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 50, max: 100)

**Response Headers**:
- `X-WP-Total`: Total number of redirects
- `X-WP-TotalPages`: Total number of pages
- `Cache-Control`: public, max-age=300

**Example**:
```bash
curl -X GET "https://example.com/wp-json/meowseo/v1/redirects?page=1&per_page=50" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### POST /meowseo/v1/redirects

Create a new redirect rule.

**Required Headers**:
- `X-WP-Nonce`: WordPress REST API nonce

**Body Parameters**:
- `source_url` (string, required): Source URL to match
- `target_url` (string, required): Target URL to redirect to
- `redirect_type` (int, optional): HTTP status code (301, 302, 307, 410) - default: 301
- `is_regex` (bool, optional): Whether source_url is a regex pattern - default: false
- `status` (string, optional): Rule status ('active' or 'inactive') - default: 'active'

**Example**:
```bash
curl -X POST "https://example.com/wp-json/meowseo/v1/redirects" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -d '{
    "source_url": "https://example.com/old-page",
    "target_url": "https://example.com/new-page",
    "redirect_type": 301,
    "is_regex": false,
    "status": "active"
  }'
```

### PUT /meowseo/v1/redirects/{id}

Update an existing redirect rule.

**Required Headers**:
- `X-WP-Nonce`: WordPress REST API nonce

**Body Parameters**: Same as POST (all optional)

**Example**:
```bash
curl -X PUT "https://example.com/wp-json/meowseo/v1/redirects/123" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -d '{
    "target_url": "https://example.com/updated-page",
    "redirect_type": 302
  }'
```

### DELETE /meowseo/v1/redirects/{id}

Delete a redirect rule.

**Required Headers**:
- `X-WP-Nonce`: WordPress REST API nonce

**Example**:
```bash
curl -X DELETE "https://example.com/wp-json/meowseo/v1/redirects/123" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

## Redirect Types

### 301 - Permanent Redirect
Use for permanently moved content. Search engines transfer link equity to the new URL.

### 302 - Temporary Redirect
Use for temporarily moved content. Search engines don't transfer link equity.

### 307 - Temporary Redirect (HTTP/1.1)
Similar to 302 but guarantees the request method won't change.

### 410 - Gone
Indicates the resource is permanently deleted and won't return. Better than 404 for SEO.

## Regex Patterns

When `is_regex` is true, the `source_url` is treated as a regular expression pattern.

**Pattern Requirements**:
- Must include delimiters (e.g., `#pattern#`, `/pattern/`)
- If no delimiters provided, `#pattern#i` is automatically added
- Supports backreferences in `target_url` using `$1`, `$2`, etc.

**Examples**:

1. **Match blog posts by year**:
   - Source: `#^https://example\.com/(\d{4})/(.+)$#`
   - Target: `https://example.com/blog/$1/$2`

2. **Match old category structure**:
   - Source: `#^https://example\.com/category/(.+)$#`
   - Target: `https://example.com/cat/$1`

3. **Case-insensitive matching**:
   - Source: `#^https://example\.com/OLD-PAGE$#i`
   - Target: `https://example.com/new-page`

## Performance Considerations

### has_regex_rules Flag

The module maintains a `has_regex_rules` boolean flag in the options table. This flag is automatically updated on every CRUD operation:

- **When true**: Regex matching is performed after exact-match fails
- **When false**: Regex matching is skipped entirely (performance optimization)

This prevents unnecessary database queries when no regex rules exist.

### Memory Usage

The module **never** loads all redirect rules into PHP memory. Only the matched rule (exact match) or regex rules (small subset) are loaded.

### Database Indexes

- `idx_source_url`: Enables fast exact-match lookups (O(log n))
- `idx_is_regex_status`: Enables efficient filtering of regex rules

## URL Normalization

The module normalizes URLs before matching:

1. **Query String Stripping** (optional): Controlled by `redirect_strip_query` option
2. **Trailing Slash Removal**: Ensures consistent matching

Example:
- Input: `https://example.com/page/?utm_source=google`
- Normalized: `https://example.com/page`

## Hit Tracking

Every successful redirect logs:
- `hit_count`: Incremented by 1
- `last_accessed`: Updated to current timestamp

This data is useful for:
- Identifying popular redirects
- Finding unused redirects for cleanup
- Analytics and reporting

## Security

### Capability Checks
All REST endpoints verify `manage_options` capability before processing requests.

### Nonce Verification
All mutation endpoints (POST, PUT, DELETE) verify WordPress REST API nonce.

### SQL Injection Prevention
All database queries use `$wpdb->prepare()` with parameterized placeholders.

### Output Escaping
All user-supplied values are properly escaped before output.

## Integration

### Module Registration

The module is registered in `Module_Manager::$module_registry`:

```php
'redirects' => 'Modules\Redirects\Redirects',
```

### Enabling the Module

Add 'redirects' to the enabled modules array in plugin options:

```php
$options = new Options();
$enabled = $options->get_enabled_modules();
$enabled[] = 'redirects';
$options->set( 'enabled_modules', $enabled );
$options->save();
```

### Hook Priority

The redirect check hooks into `wp` action with priority 1 (early execution) to intercept requests before template loading.

## Testing

Run the module tests:

```bash
./vendor/bin/phpunit tests/modules/redirects/
```

## Requirements Satisfied

This module satisfies the following requirements from the specification:

- **7.1**: Store redirect rules in indexed database table
- **7.2**: Database-level exact-match query on indexed source_url
- **7.3**: Regex fallback without loading all rules into memory
- **7.4**: Never load full redirect rule set into PHP array
- **7.5**: Support redirect types 301, 302, 307, 410
- **7.6**: Log hit count and last-accessed timestamp
- **7.7**: REST endpoints for CRUD operations with proper authentication

## Future Enhancements

Potential improvements for future versions:

1. **Bulk Import/Export**: CSV import/export for redirect rules
2. **Redirect Chains Detection**: Identify and warn about redirect chains
3. **Automatic 404 to Redirect**: Suggest redirects based on 404 log
4. **Redirect Testing**: Test redirects before activating
5. **Redirect Groups**: Organize redirects into groups for easier management
6. **Wildcard Matching**: Support wildcard patterns without full regex
7. **Redirect History**: Track changes to redirect rules over time
