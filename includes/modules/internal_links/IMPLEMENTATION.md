# Internal Links Module - Implementation Notes

## Architecture

The Internal Links Module follows the MeowSEO modular architecture pattern:

```
includes/modules/internal_links/
├── class-internal-links.php      # Main module class (implements Module interface)
├── class-internal-links-rest.php # REST API endpoints
├── README.md                      # User-facing documentation
└── IMPLEMENTATION.md              # This file
```

## Key Design Decisions

### 1. Asynchronous Processing (Requirement 9.2)

**Problem**: Scanning links and checking HTTP status synchronously during post save would cause unacceptable delays.

**Solution**: Two-stage WP-Cron pipeline:
1. `save_post` hook schedules `meowseo_scan_links_cron` (10 seconds delay)
2. Link scan schedules `meowseo_check_link_status_cron` for each link (60 seconds delay)

**Benefits**:
- Post save completes immediately
- Link scanning happens in background
- HTTP checks are batched and rate-limited

### 2. DOMDocument for HTML Parsing

**Problem**: Regex-based HTML parsing is fragile and error-prone.

**Solution**: Use PHP's DOMDocument with XPath queries:
```php
$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
$xpath = new DOMXPath($dom);
$anchors = $xpath->query('//a[@href]');
```

**Benefits**:
- Handles malformed HTML gracefully
- Extracts both href and anchor text reliably
- Standard library (no dependencies)

### 3. Internal Link Filtering

**Problem**: Need to identify internal links without false positives.

**Solution**: Three-step filtering:
1. Skip anchors (`#`), mailto, tel links
2. Convert relative URLs to absolute using `site_url()`
3. Compare parsed host with `site_url()` host

**Edge cases handled**:
- Relative URLs (`/page`)
- Protocol-relative URLs (`//example.com/page`)
- Subdomain variations (exact host match required)

### 4. Link Deduplication

**Problem**: Same link may appear multiple times in a post.

**Solution**: Database UNIQUE constraint on `(source_post_id, target_url_hash)`:
```sql
UNIQUE KEY idx_source_target (source_post_id, target_url_hash(64))
```

**Benefits**:
- Prevents duplicate entries at database level
- `upsert_link_check()` updates existing entries
- SHA-256 hash handles long URLs (up to 2048 chars)

### 5. Link Suggestion Algorithm (Requirement 9.4)

**Problem**: Need to suggest relevant internal links based on content similarity.

**Solution**: Keyword overlap scoring:
- Title match: +50 points
- Meta description match: +30 points
- Excerpt match: +20 points

**Limitations**:
- Simple keyword matching (not semantic analysis)
- Case-insensitive substring search
- Returns top 5 suggestions only

**Future improvements**:
- TF-IDF scoring
- Semantic similarity (embeddings)
- Consider existing links to avoid duplicates

### 6. HTTP Status Checking

**Problem**: Need to check link status without blocking or overwhelming servers.

**Solution**: WordPress `wp_remote_head()` with conservative settings:
```php
wp_remote_head($url, [
    'timeout'     => 10,
    'redirection' => 5,
    'user-agent'  => 'MeowSEO Link Checker/1.0',
]);
```

**Benefits**:
- HEAD request (no body download)
- 10-second timeout prevents hanging
- Follows redirects automatically
- Custom user-agent for identification

**Error handling**:
- `is_wp_error()` check for network failures
- NULL status stored if check fails
- Can be retried on next scan

## Database Queries

### Link Retrieval (Read)
```sql
SELECT * FROM {prefix}meowseo_link_checks 
WHERE source_post_id = %d 
ORDER BY id ASC
```
- Uses `idx_source_post` index
- O(log n) lookup by post ID

### Link Upsert (Write)
```sql
-- Check existence
SELECT id FROM {prefix}meowseo_link_checks 
WHERE source_post_id = %d AND target_url_hash = %s 
LIMIT 1

-- Update or Insert
UPDATE ... WHERE id = %d
-- OR
INSERT INTO ... VALUES (...)
```
- Uses `idx_source_target` unique index
- Prevents duplicate entries

### Status Check Query (Read)
```sql
SELECT * FROM {prefix}meowseo_link_checks 
WHERE source_post_id = %d AND target_url_hash = %s 
LIMIT 1
```
- Uses `idx_source_target` unique index
- O(log n) lookup by post ID + URL hash

## REST API Design

### Endpoint: GET /meowseo/v1/internal-links

**Parameters**:
- `post_id` (required, integer) - Post ID to get link health for

**Response**:
```json
{
  "post_id": 123,
  "links": [...],
  "stats": {
    "total": 10,
    "checked": 8,
    "healthy": 7,
    "broken": 1,
    "redirects": 0,
    "pending": 2
  }
}
```

**Authorization**: `edit_posts` capability (Requirement 9.5)

**Caching**: `Cache-Control: public, max-age=300`

### Endpoint: GET /meowseo/v1/internal-links/suggestions

**Parameters**:
- `post_id` (required, integer) - Post ID to get suggestions for

**Response**:
```json
{
  "post_id": 123,
  "suggestions": [
    {
      "post_id": 456,
      "title": "Related Article",
      "url": "https://example.com/related-article",
      "relevance": 80
    }
  ]
}
```

**Authorization**: `edit_posts` capability

**Caching**: `Cache-Control: public, max-age=300`

## WP-Cron Events

### meowseo_scan_links_cron

**Trigger**: Scheduled on `save_post` (10 seconds delay)

**Arguments**: `[$post_id]`

**Action**:
1. Get post content
2. Parse HTML with DOMDocument
3. Extract all `<a href>` elements
4. Filter to internal URLs
5. Upsert to `meowseo_link_checks` table
6. Schedule status checks for each link

**Frequency**: Single event per post save

### meowseo_check_link_status_cron

**Trigger**: Scheduled by link scan (60 seconds delay)

**Arguments**: `[$post_id, $url_hash]`

**Action**:
1. Get link data from database
2. Perform HTTP HEAD request
3. Extract status code
4. Update `http_status` and `last_checked` fields

**Frequency**: Single event per link

## Performance Characteristics

### Time Complexity
- Link scan: O(n) where n = number of links in post
- Link retrieval: O(log m) where m = total links in database
- Link upsert: O(log m) per link
- Status check: O(1) HTTP request per link

### Space Complexity
- Link storage: O(n) where n = total unique links across all posts
- SHA-256 hash: 64 bytes per link (fixed)
- Anchor text: Up to 512 bytes per link

### Database Impact
- Indexed queries only (no table scans)
- Unique constraint prevents unbounded growth
- No foreign key constraints (performance optimization)

## Security Considerations

### SQL Injection Prevention
All queries use `$wpdb->prepare()`:
```php
$wpdb->prepare(
    "SELECT * FROM {$table} WHERE source_post_id = %d AND target_url_hash = %s",
    $post_id,
    $url_hash
);
```

### XSS Prevention
Anchor text is limited to 512 characters and stored as-is (no HTML allowed in database).

### SSRF Prevention
- Only internal URLs are checked (same host as `site_url()`)
- External URLs are filtered out during link scan
- No user-controlled URLs in HTTP requests

### Capability Checks
REST endpoints verify `edit_posts` capability:
```php
public function check_edit_posts(): bool {
    return current_user_can('edit_posts');
}
```

## Testing Recommendations

### Unit Tests
- `extract_links_from_html()` - Test with various HTML structures
- `filter_internal_links()` - Test with relative/absolute URLs
- `calculate_keyword_overlap()` - Test scoring algorithm

### Integration Tests
- Link scan workflow (save_post → cron → database)
- HTTP status check workflow (scan → cron → status update)
- REST API endpoints (authorization, response format)

### Edge Cases
- Malformed HTML (missing closing tags)
- Very long URLs (2048 character limit)
- Duplicate links in same post
- Links to non-existent internal pages
- Redirected internal links

## Known Limitations

1. **No link context**: Doesn't track which paragraph/section contains the link
2. **No link removal detection**: Old links remain in database if removed from post
3. **Simple keyword matching**: Suggestion algorithm is basic (no semantic analysis)
4. **No rate limiting**: HTTP checks could overwhelm target server if many links
5. **No retry logic**: Failed HTTP checks are not automatically retried

## Future Enhancements

1. **Link context tracking**: Store surrounding text for better suggestions
2. **Orphaned link cleanup**: Remove links that no longer exist in post content
3. **Advanced suggestions**: Use TF-IDF or embeddings for semantic similarity
4. **Rate limiting**: Throttle HTTP checks to prevent server overload
5. **Retry mechanism**: Automatically retry failed HTTP checks with exponential backoff
6. **Link health dashboard**: Admin page showing site-wide link health statistics
7. **Broken link notifications**: Email alerts when broken links are detected
