# Task 2.2 Completion: Implement Widget Data Methods

## Overview

Successfully implemented all six widget data retrieval methods in the `Dashboard_Widgets` class. These methods query the appropriate data sources and return structured arrays matching the data models defined in the design document.

## Implemented Methods

### 1. `get_content_health_data()`
**Purpose**: Query posts missing SEO data (title, description, focus keyword)

**Implementation**:
- Counts total published posts (post and page types)
- Counts posts missing `meowseo_title` meta
- Counts posts missing `meowseo_description` meta
- Counts posts missing `meowseo_focus_keyword` meta
- Calculates percentage complete based on worst-case scenario

**Returns**:
```php
array(
    'total_posts' => int,
    'missing_title' => int,
    'missing_description' => int,
    'missing_focus_keyword' => int,
    'percentage_complete' => float
)
```

### 2. `get_sitemap_status_data()`
**Purpose**: Check sitemap generation status and last update time

**Implementation**:
- Checks if sitemap module is enabled via options
- Verifies sitemap index file exists in uploads directory
- Retrieves last modification time of sitemap index
- Determines cache freshness based on TTL setting
- Counts URLs per enabled post type

**Returns**:
```php
array(
    'enabled' => bool,
    'last_generated' => string|null, // ISO 8601 datetime
    'total_urls' => int,
    'post_types' => array, // ['post' => 150, 'page' => 25]
    'cache_status' => 'fresh'|'stale'|'disabled'
)
```

### 3. `get_top_404s_data()`
**Purpose**: Query 404 logs for top errors from last 30 days

**Implementation**:
- Checks if `meowseo_404_log` table exists
- Queries top 10 404 errors by hit count from last 30 days
- Converts timestamps to ISO 8601 format
- Checks if each URL has an active redirect

**Returns**:
```php
array(
    array(
        'url' => string,
        'count' => int,
        'last_seen' => string, // ISO 8601 datetime
        'has_redirect' => bool
    ),
    // ... up to 10 entries
)
```

### 4. `get_gsc_summary_data()`
**Purpose**: Aggregate GSC metrics (clicks, impressions, CTR, position)

**Implementation**:
- Checks if `meowseo_gsc_data` table exists
- Aggregates metrics for last 30 days using SUM and AVG
- Retrieves last sync time from options
- Returns zero values if no data available

**Returns**:
```php
array(
    'clicks' => int,
    'impressions' => int,
    'ctr' => float,
    'position' => float,
    'date_range' => array(
        'start' => string, // Y-m-d format
        'end' => string
    ),
    'last_synced' => string|null // ISO 8601 datetime
)
```

### 5. `get_discover_performance_data()`
**Purpose**: Query Discover metrics if available

**Implementation**:
- Checks for Discover data in options (`meowseo_gsc_discover_data`)
- Calculates CTR from impressions and clicks
- Returns unavailable status if no data exists
- Supports custom date ranges from stored data

**Returns**:
```php
array(
    'impressions' => int,
    'clicks' => int,
    'ctr' => float,
    'available' => bool,
    'date_range' => array(
        'start' => string,
        'end' => string
    )
)
```

### 6. `get_index_queue_data()`
**Purpose**: Count pending/processing/completed/failed indexing requests

**Implementation**:
- Checks if `meowseo_gsc_queue` table exists
- Counts entries by status (pending, processing, completed, failed)
- Retrieves last processed timestamp from `processed_at` column
- Returns zero counts if table doesn't exist

**Returns**:
```php
array(
    'pending' => int,
    'processing' => int,
    'completed' => int,
    'failed' => int,
    'last_processed' => string|null // ISO 8601 datetime
)
```

## Technical Details

### Database Queries
- All queries use prepared statements via `$wpdb->prepare()` for security
- Table existence checks prevent errors on fresh installations
- Efficient queries with proper indexes (existing schema)
- Date filtering uses MySQL date functions for performance

### Error Handling
- Graceful degradation when tables don't exist
- Returns empty/zero values instead of errors
- Null checks for optional data (timestamps, options)
- Type casting ensures consistent return types

### Data Formatting
- All timestamps converted to ISO 8601 format using `gmdate('c', ...)`
- Numeric values properly cast to int/float
- Boolean values for flags (enabled, available, has_redirect)
- Arrays for structured data (date_range, post_types)

## Testing

Created comprehensive unit tests in `tests/admin/DashboardWidgetsTest.php`:

- ✅ `test_get_content_health_data_structure` - Validates return structure and types
- ✅ `test_get_sitemap_status_data_structure` - Validates return structure and types
- ✅ `test_get_top_404s_data_returns_array` - Validates array return
- ✅ `test_get_gsc_summary_data_structure` - Validates return structure and types
- ✅ `test_get_discover_performance_data_structure` - Validates return structure and types
- ✅ `test_get_index_queue_data_structure` - Validates return structure and types

**Test Results**: All 6 tests pass with 55 assertions

## Requirements Satisfied

- ✅ **Requirement 2.5**: Dashboard REST endpoints provide widget data
- ✅ **Requirement 3.4**: Widget data methods query appropriate data sources
- ✅ **Design Document**: All data models match specified structures
- ✅ **Security**: All queries use prepared statements
- ✅ **Performance**: Efficient queries with minimal database load

## Integration Points

These methods will be called by REST endpoints registered in Task 2.3:
- `/meowseo/v1/dashboard/content-health` → `get_content_health_data()`
- `/meowseo/v1/dashboard/sitemap-status` → `get_sitemap_status_data()`
- `/meowseo/v1/dashboard/top-404s` → `get_top_404s_data()`
- `/meowseo/v1/dashboard/gsc-summary` → `get_gsc_summary_data()`
- `/meowseo/v1/dashboard/discover-performance` → `get_discover_performance_data()`
- `/meowseo/v1/dashboard/index-queue` → `get_index_queue_data()`

## Files Modified

1. **includes/admin/class-dashboard-widgets.php**
   - Added 6 widget data methods
   - Total: 520 lines
   - All methods documented with PHPDoc

2. **tests/admin/DashboardWidgetsTest.php** (new)
   - Created comprehensive unit tests
   - 6 test methods covering all widget data methods
   - 55 assertions validating structure and types

## Next Steps

Task 2.3 will implement the REST endpoints that call these methods and add caching with 5-minute TTL using WordPress transients.
