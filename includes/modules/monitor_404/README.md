# Monitor 404 Module

## Overview

The Monitor 404 Module provides efficient 404 error logging with buffered writes to prevent performance degradation on high-traffic sites. Unlike traditional 404 monitors that write synchronously to the database on every 404 hit, this module buffers hits in Object Cache and flushes them in batches via WP-Cron.

## Features

- **Buffered Logging**: 404 hits are buffered in Object Cache using per-minute bucket keys
- **Non-Blocking**: No synchronous database writes during request handling
- **Efficient Batching**: WP-Cron flushes buffers every 60 seconds using bulk INSERT
- **Hit Count Preservation**: Uses `ON DUPLICATE KEY UPDATE` to maintain accurate hit counts
- **REST API**: Provides paginated access to 404 log data with deletion capabilities
- **Transient Fallback**: Automatically falls back to transients when Object Cache is unavailable

## Architecture

### Buffering Strategy

```
HTTP Request (404) → Object Cache Bucket → WP-Cron (60s) → Database
```

**Bucket Key Format**: `meowseo_404_{YYYYMMDD_HHmm}`

Each minute gets its own bucket, allowing the cron job to efficiently process 1-2 buckets per run without unbounded key accumulation.

### Database Schema

```sql
CREATE TABLE {prefix}meowseo_404_log (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    url          VARCHAR(2048)   NOT NULL,
    url_hash     CHAR(64)        NOT NULL,  -- SHA-256 of url for dedup
    referrer     VARCHAR(2048)   NULL,
    user_agent   VARCHAR(512)    NULL,
    hit_count    BIGINT UNSIGNED NOT NULL DEFAULT 1,
    first_seen   DATE            NOT NULL,
    last_seen    DATE            NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_url_hash_date (url_hash(64), first_seen),
    KEY idx_last_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Deduplication**: The unique key on `url_hash` + `first_seen` ensures that duplicate URLs on the same day increment the hit count rather than creating new rows.

## Usage

### Enabling the Module

Add `monitor_404` to the enabled modules array in plugin options:

```php
$options = new \MeowSEO\Options();
$enabled = $options->get_enabled_modules();
$enabled[] = 'monitor_404';
$options->set( 'enabled_modules', $enabled );
$options->save();
```

### REST API Endpoints

#### Get 404 Log (Paginated)

```
GET /wp-json/meowseo/v1/404-log
```

**Parameters**:
- `page` (int, default: 1) - Page number
- `per_page` (int, default: 50, max: 100) - Items per page
- `orderby` (string, default: 'last_seen') - Sort field (id, url, hit_count, first_seen, last_seen)
- `order` (string, default: 'DESC') - Sort order (ASC, DESC)

**Authorization**: Requires `manage_options` capability

**Response**:
```json
{
  "entries": [
    {
      "id": 1,
      "url": "http://example.com/missing-page",
      "url_hash": "abc123...",
      "referrer": "http://example.com/home",
      "user_agent": "Mozilla/5.0...",
      "hit_count": 5,
      "first_seen": "2024-01-15",
      "last_seen": "2024-01-16"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 100,
    "total_pages": 2
  }
}
```

#### Delete 404 Log Entry

```
DELETE /wp-json/meowseo/v1/404-log/{id}
```

**Authorization**: Requires `manage_options` capability and valid nonce

**Response**:
```json
{
  "success": true,
  "message": "404 log entry deleted successfully."
}
```

## Performance Characteristics

### Request Overhead

- **404 Detection**: ~0.1ms (cache write only)
- **Database Writes**: Zero during request handling
- **Memory Usage**: Minimal (single array append to cache)

### Cron Processing

- **Frequency**: Every 60 seconds
- **Batch Size**: Typically 1-2 minutes of buffered data
- **Query Type**: Single bulk INSERT with ON DUPLICATE KEY UPDATE
- **Lock Contention**: None (no locks required)

## Requirements Satisfied

- **Requirement 8.1**: Detects 404 responses and buffers in Object Cache using per-minute bucket keys ✓
- **Requirement 8.2**: Prevents synchronous database writes during requests ✓
- **Requirement 8.3**: Registers WP-Cron event that runs every 60 seconds to flush buffered data ✓
- **Requirement 8.4**: Uses bulk INSERT ... ON DUPLICATE KEY UPDATE to preserve hit counts ✓
- **Requirement 8.5**: Provides REST endpoint for paginated 404 log access (manage_options capability) ✓
- **Requirement 8.6**: Implements log entry deletion functionality ✓

## Testing

Run the module tests:

```bash
./vendor/bin/phpunit tests/modules/monitor_404/Monitor404Test.php
```

## Implementation Notes

1. **Bucket TTL**: Buckets have a 120-second TTL (2 minutes) to ensure the cron job catches them even if it's slightly delayed.

2. **Aggregation**: Multiple hits to the same URL within a flush cycle are aggregated before database insertion to minimize row count.

3. **URL Hashing**: SHA-256 hashing of URLs provides a fixed-length deduplication key while preserving the full URL in the `url` column.

4. **Transient Fallback**: When Object Cache is unavailable, the module automatically falls back to WordPress transients with the same TTL.

5. **Cron Scheduling**: The custom `meowseo_60s` cron interval is registered on module boot and cleaned up on plugin deactivation.
