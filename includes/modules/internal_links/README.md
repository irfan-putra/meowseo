# Internal Links Module

## Overview

The Internal Links Module analyzes internal link structure and provides link health reporting for WordPress posts. It scans post content for internal links, schedules HTTP status checks via WP-Cron, and provides link suggestions based on keyword overlap.

## Features

### Link Scanning (Requirement 9.1)
- Parses post content with DOMDocument to extract `<a href>` elements
- Filters to internal URLs only (same host as `site_url()`)
- Stores link data in `meowseo_link_checks` table with:
  - Source post ID
  - Target URL (with SHA-256 hash for deduplication)
  - Anchor text (limited to 512 characters)
  - HTTP status code (NULL until checked)
  - Last checked timestamp

### Asynchronous Processing (Requirement 9.2)
- Link scanning is scheduled via WP-Cron on `save_post` hook
- HTTP status checks are scheduled separately via WP-Cron
- No synchronous HTTP requests during post save operations
- Prevents performance impact on content editing workflow

### HTTP Status Checking (Requirement 9.3)
- Performs HTTP HEAD requests to check link status
- Updates `http_status` field in database when check completes
- Uses WordPress `wp_remote_head()` with 10-second timeout
- Follows up to 5 redirects automatically

### Link Suggestions (Requirement 9.4)
- Surfaces link suggestions in Gutenberg sidebar (via REST API)
- Based on keyword overlap analysis between:
  - Current post's focus keyword
  - Other published posts' titles and meta descriptions
- Returns top 5 suggestions with relevance scores

### REST API (Requirement 9.5)
- `GET /meowseo/v1/internal-links?post_id={id}` - Get link health data
- `GET /meowseo/v1/internal-links/suggestions?post_id={id}` - Get link suggestions
- Accessible to users with `edit_posts` capability
- Includes cache control headers for CDN/edge caching

## Database Schema

```sql
CREATE TABLE {prefix}meowseo_link_checks (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_post_id BIGINT UNSIGNED NOT NULL,
    target_url    VARCHAR(2048)   NOT NULL,
    target_url_hash CHAR(64)      NOT NULL,
    anchor_text   VARCHAR(512)    NULL,
    http_status   SMALLINT        NULL,
    last_checked  DATETIME        NULL,
    PRIMARY KEY (id),
    KEY idx_source_post (source_post_id),
    KEY idx_http_status (http_status),
    UNIQUE KEY idx_source_target (source_post_id, target_url_hash(64))
);
```

## WP-Cron Events

- `meowseo_scan_links_cron` - Scans post content for internal links
- `meowseo_check_link_status_cron` - Checks HTTP status of a link

## Usage

### Enable the Module

Add `'internal_links'` to the enabled modules in plugin options:

```php
$options = new \MeowSEO\Options();
$enabled = $options->get_enabled_modules();
$enabled[] = 'internal_links';
$options->set('enabled_modules', $enabled);
$options->save();
```

### Get Link Health Data

```php
// Via REST API
GET /wp-json/meowseo/v1/internal-links?post_id=123

// Response
{
  "post_id": 123,
  "links": [
    {
      "id": 1,
      "source_post_id": 123,
      "target_url": "https://example.com/page",
      "anchor_text": "Example Page",
      "http_status": 200,
      "last_checked": "2024-01-15 10:30:00"
    }
  ],
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

### Get Link Suggestions

```php
// Via REST API
GET /wp-json/meowseo/v1/internal-links/suggestions?post_id=123

// Response
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

## Performance Considerations

- Link scanning is asynchronous (scheduled 10 seconds after post save)
- HTTP status checks are scheduled 60 seconds after link discovery
- No synchronous database writes during post save
- Uses indexed queries for efficient link lookups
- SHA-256 hash prevents duplicate link entries per post

## Security

- All database queries use `$wpdb->prepare()` with parameterized placeholders
- REST endpoints verify `edit_posts` capability
- User input is sanitized and validated
- HTML parsing uses DOMDocument with error suppression for malformed HTML

## Integration with Gutenberg

The module provides data for the Gutenberg sidebar via REST API:
- Link health statistics
- Broken link warnings
- Link suggestions based on focus keyword

Frontend JavaScript components should:
1. Fetch link health data on post load
2. Display link statistics in sidebar
3. Show link suggestions with relevance scores
4. Allow one-click insertion of suggested links
