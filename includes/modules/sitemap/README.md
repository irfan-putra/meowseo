# Sitemap Module

The Sitemap Module provides high-performance XML sitemap generation with filesystem caching and lock pattern to prevent cache stampede.

## Features

- **Filesystem Caching**: Sitemaps stored as XML files for direct serving
- **Lock Pattern**: Prevents cache stampede on high-traffic sites
- **Stale-While-Revalidate**: Serves stale content during regeneration
- **Automatic Pagination**: 1,000 URLs per sitemap file
- **Image Extension**: Includes featured images
- **News Sitemap**: Google News sitemap for recent posts
- **Video Sitemap**: Google Video sitemap for embedded videos
- **Search Engine Ping**: Automatic notifications to Google and Bing
- **Performance Optimized**: Direct database queries, batch postmeta loading

## Architecture

### Core Components

1. **Sitemap_Module**: Module entry point, registers rewrite rules
2. **Sitemap_Cache**: Filesystem cache manager with lock pattern
3. **Sitemap_Builder**: Generates sitemap XML content
4. **Sitemap_Ping**: Notifies search engines of updates

### Cache Directory Structure

```
wp-content/uploads/meowseo-sitemaps/
├── .htaccess (deny direct access)
├── index.xml
├── posts-1.xml
├── posts-2.xml
├── pages-1.xml
├── news.xml
└── video.xml
```

## Usage

### Accessing Sitemaps

**Sitemap Index:**
```
https://yoursite.com/sitemap.xml
```

**Post Type Sitemaps:**
```
https://yoursite.com/sitemap-posts.xml
https://yoursite.com/sitemap-pages.xml
https://yoursite.com/sitemap-{post_type}.xml
```

**Paginated Sitemaps:**
```
https://yoursite.com/sitemap-posts-1.xml
https://yoursite.com/sitemap-posts-2.xml
```

**Special Sitemaps:**
```
https://yoursite.com/sitemap-news.xml
https://yoursite.com/sitemap-video.xml
```

### Submit to Search Engines

**Google Search Console:**
1. Go to https://search.google.com/search-console
2. Select your property
3. Navigate to Sitemaps
4. Submit: `https://yoursite.com/sitemap.xml`

**Bing Webmaster Tools:**
1. Go to https://www.bing.com/webmasters
2. Select your site
3. Navigate to Sitemaps
4. Submit: `https://yoursite.com/sitemap.xml`

## Configuration

### Enable/Disable Sitemaps

Navigate to **MeowSEO > Settings > Sitemap**:

- Enable/disable sitemap generation
- Select post types to include
- Enable/disable news sitemap
- Enable/disable video sitemap

### Exclude Posts from Sitemap

**Per Post:**
In Gutenberg sidebar, set "Robots" to "noindex"

**Programmatically:**
```php
update_post_meta( $post_id, '_meowseo_noindex', '1' );
```

## WP-CLI Commands

### Generate Sitemaps

Generate all sitemaps:
```bash
wp meowseo sitemap generate
```

Generate specific sitemap:
```bash
wp meowseo sitemap generate posts
wp meowseo sitemap generate pages
wp meowseo sitemap generate news
wp meowseo sitemap generate video
```

Generate specific page:
```bash
wp meowseo sitemap generate posts --page=2
```

### Clear Cache

Clear all sitemap cache:
```bash
wp meowseo sitemap clear-cache
```

Clear specific sitemap:
```bash
wp meowseo sitemap clear-cache --type=posts
```

### Ping Search Engines

Manually ping search engines:
```bash
wp meowseo sitemap ping
```

## Caching Strategy

### Lock Pattern

Prevents cache stampede when multiple requests arrive simultaneously:

1. First request acquires lock
2. Subsequent requests serve stale file
3. First request regenerates sitemap
4. Lock is released
5. Next request serves fresh file

**Lock Timeout:** 60 seconds
**Stale File Max Age:** 7 days

### Cache Invalidation

Sitemaps are automatically invalidated on:

- Post publish/update/delete
- Term create/edit/delete
- Daily cron job (pre-generation)

### Freshness Check

Files are considered fresh for 24 hours. After that:
- Stale files are served while regenerating
- If no stale file exists, returns HTTP 503

## Performance

### Optimizations

1. **Direct Database Queries**: Bypasses WP_Query overhead
2. **Batch Postmeta Loading**: Calls `update_post_meta_cache()` before loops
3. **LEFT JOIN Exclusion**: Excludes noindex posts in single query
4. **Pagination**: Limits to 1,000 URLs per file
5. **Filesystem Storage**: Serves files directly, bypasses WordPress

### Benchmarks

- Sitemap generation (1,000 URLs): < 100ms
- Sitemap generation (50,000 URLs): < 5 seconds
- Cache hit: < 1ms (direct file read)
- Memory usage: < 10MB per generation

### Database Query Example

```php
SELECT p.ID, p.post_modified_gmt
FROM wp_posts p
LEFT JOIN wp_postmeta pm 
    ON p.ID = pm.post_id 
    AND pm.meta_key = '_meowseo_noindex'
WHERE p.post_type = 'post'
AND p.post_status = 'publish'
AND (pm.meta_value IS NULL OR pm.meta_value != '1')
ORDER BY p.post_modified_gmt DESC
LIMIT 1000 OFFSET 0
```

## Special Sitemaps

### News Sitemap

Google News sitemap for recent posts (last 48 hours):

**Requirements:**
- Post published within last 48 hours
- Post status is "publish"
- Post not marked as noindex

**XML Format:**
```xml
<url>
  <loc>https://example.com/post/</loc>
  <news:news>
    <news:publication>
      <news:name>Site Name</news:name>
      <news:language>en</news:language>
    </news:publication>
    <news:publication_date>2024-01-01T12:00:00+00:00</news:publication_date>
    <news:title>Post Title</news:title>
  </news:news>
</url>
```

### Video Sitemap

Google Video sitemap for posts with embedded videos:

**Supported Platforms:**
- YouTube
- Vimeo

**Detection:**
- Scans post content for video embeds
- Uses oEmbed API to extract metadata
- Caches video metadata for 24 hours

**XML Format:**
```xml
<url>
  <loc>https://example.com/post/</loc>
  <video:video>
    <video:title>Video Title</video:title>
    <video:description>Video Description</video:description>
    <video:thumbnail_loc>https://img.youtube.com/vi/ID/maxresdefault.jpg</video:thumbnail_loc>
    <video:content_loc>https://www.youtube.com/watch?v=ID</video:content_loc>
  </video:video>
</url>
```

## Filter Hooks

### meowseo_sitemap_post_types

Modify included post types:

```php
add_filter( 'meowseo_sitemap_post_types', function( $post_types ) {
    $post_types[] = 'custom_post_type';
    return $post_types;
} );
```

### meowseo_sitemap_exclude_post

Exclude specific posts:

```php
add_filter( 'meowseo_sitemap_exclude_post', function( $exclude, $post_id ) {
    // Exclude posts in specific category
    if ( has_category( 'private', $post_id ) ) {
        return true;
    }
    return $exclude;
}, 10, 2 );
```

### meowseo_sitemap_url_entry

Modify URL entry before output:

```php
add_filter( 'meowseo_sitemap_url_entry', function( $entry, $post_id ) {
    // Add custom priority
    $entry['priority'] = '0.8';
    return $entry;
}, 10, 2 );
```

### meowseo_sitemap_xml

Modify final XML output:

```php
add_filter( 'meowseo_sitemap_xml', function( $xml, $type ) {
    // Add custom XML processing instruction
    return $xml;
}, 10, 2 );
```

## Action Hooks

### meowseo_before_sitemap_generation

Fires before sitemap generation:

```php
add_action( 'meowseo_before_sitemap_generation', function( $type ) {
    // Do something before generation
} );
```

### meowseo_after_sitemap_generation

Fires after sitemap generation:

```php
add_action( 'meowseo_after_sitemap_generation', function( $type, $xml ) {
    // Do something after generation
}, 10, 2 );
```

### meowseo_sitemap_cache_invalidated

Fires when sitemap cache is invalidated:

```php
add_action( 'meowseo_sitemap_cache_invalidated', function( $type ) {
    // Do something when cache is cleared
} );
```

### meowseo_sitemap_ping_sent

Fires after pinging search engines:

```php
add_action( 'meowseo_sitemap_ping_sent', function( $sitemap_url, $results ) {
    // Log ping results
}, 10, 2 );
```

## Search Engine Ping

### Automatic Pinging

Sitemaps are automatically pinged on:

- Daily cron job
- New post publication

**Rate Limiting:** Maximum once per hour

### Manual Ping

Ping search engines manually:

```bash
wp meowseo sitemap ping
```

Or programmatically:

```php
$sitemap_ping = new \MeowSEO\Modules\Sitemap\Sitemap_Ping();
$sitemap_ping->ping( home_url( '/sitemap.xml' ) );
```

### Ping Endpoints

- **Google:** `https://www.google.com/ping?sitemap={url}`
- **Bing:** `https://www.bing.com/ping?sitemap={url}`

## Troubleshooting

### Sitemap Not Generating

1. Check that Sitemap module is enabled
2. Verify `wp-content/uploads/meowseo-sitemaps/` is writable
3. Check for PHP errors in debug log
4. Try manual generation: `wp meowseo sitemap generate`

### 404 on Sitemap URL

1. Flush rewrite rules: Go to **Settings > Permalinks** and click Save
2. Or via WP-CLI: `wp rewrite flush`
3. Verify `.htaccess` is writable (if using Apache)

### Sitemap Not Updating

1. Check cache invalidation hooks are firing
2. Manually clear cache: `wp meowseo sitemap clear-cache`
3. Verify WP-Cron is running
4. Check Object Cache is working

### Performance Issues

1. Enable Object Cache (Redis/Memcached)
2. Reduce number of post types in sitemap
3. Disable video sitemap if not needed
4. Use real cron instead of WP-Cron

### Lock Timeout Errors

If you see "Lock timeout" errors:

1. Increase lock timeout in code
2. Check for slow database queries
3. Optimize database indexes
4. Consider increasing PHP memory limit

## Security

### Direct Access Prevention

The `.htaccess` file in the cache directory prevents direct access:

```apache
Order deny,allow
Deny from all
```

Files are only served through WordPress rewrite rules.

### Path Validation

All file paths are validated to prevent directory traversal:

```php
$file_path = realpath( $this->cache_dir . '/' . $name . '.xml' );
if ( strpos( $file_path, $this->cache_dir ) !== 0 ) {
    return false; // Invalid path
}
```

### Capability Checks

Cache clearing requires `manage_options` capability.

## Requirements

- PHP 7.4+
- WordPress 6.0+
- Writable `wp-content/uploads/` directory
- Mod_rewrite enabled (for clean URLs)

## Related Documentation

- [Design Document](../../../.kiro/specs/schema-sitemap-system/design.md)
- [Requirements Document](../../../.kiro/specs/schema-sitemap-system/requirements.md)
- [API Documentation](../../../API_DOCUMENTATION.md)
