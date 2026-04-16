# MeowSEO Admin Interface Documentation

Complete guide to the MeowSEO admin interface, including all pages, functionality, REST API endpoints, WooCommerce integration, and troubleshooting.

## Table of Contents

1. [Admin Menu Structure](#admin-menu-structure)
2. [Dashboard Page](#dashboard-page)
3. [Settings Page](#settings-page)
4. [Tools Page](#tools-page)
5. [REST API Endpoints](#rest-api-endpoints)
6. [WooCommerce Integration](#woocommerce-integration)
7. [Troubleshooting Guide](#troubleshooting-guide)

---

## Admin Menu Structure

The MeowSEO admin interface is organized under a top-level "MeowSEO" menu with the following submenu pages:

### Menu Hierarchy

```
MeowSEO (Top-level menu)
├── Dashboard
├── Settings
├── Redirects
├── 404 Monitor
├── Search Console
└── Tools
```

### Access Requirements

All admin pages require the `manage_options` capability. Users without this capability will see a "You do not have sufficient permissions" message.

### Menu Icon

The MeowSEO menu uses the WordPress dashicons-cat icon (🐱).

---

## Dashboard Page

The Dashboard provides a quick overview of your site's SEO status with asynchronously-loaded widgets.

### URL

`/wp-admin/admin.php?page=meowseo-dashboard`

### Features

#### 1. Content Health Widget

Displays the percentage of posts with complete SEO metadata.

**Metrics:**
- Total posts on site
- Posts missing title
- Posts missing description
- Posts missing focus keyword
- Overall completion percentage

**Example:**
```
Content Health: 85% Complete
├── Total Posts: 150
├── Missing Title: 10
├── Missing Description: 15
└── Missing Focus Keyword: 8
```

#### 2. Sitemap Status Widget

Shows the current status of XML sitemap generation.

**Metrics:**
- Sitemap enabled/disabled
- Last generation time
- Total URLs in sitemap
- Post type breakdown
- Cache status (fresh/stale)

**Example:**
```
Sitemap Status: Enabled
├── Last Generated: 2024-01-15 10:30 AM
├── Total URLs: 245
├── Post Types:
│   ├── Posts: 150
│   └── Pages: 95
└── Cache Status: Fresh
```

#### 3. Top 404 Errors Widget

Lists the most frequently occurring 404 errors from the last 30 days.

**Metrics:**
- URL that returned 404
- Number of times encountered
- Last seen timestamp
- Whether a redirect exists

**Example:**
```
Top 404 Errors (Last 30 Days)
1. /old-post-slug/ (45 hits, Last: 2024-01-15)
2. /typo-page/ (23 hits, Last: 2024-01-14)
3. /missing-file.pdf (12 hits, Last: 2024-01-13)
```

#### 4. Google Search Console Summary Widget

Aggregates key metrics from Google Search Console.

**Metrics:**
- Total clicks
- Total impressions
- Click-through rate (CTR)
- Average position
- Date range
- Last sync time

**Example:**
```
GSC Summary (Last 28 Days)
├── Clicks: 1,234
├── Impressions: 45,678
├── CTR: 2.7%
├── Avg Position: 12.3
└── Last Synced: 2024-01-15 09:00 AM
```

#### 5. Discover Performance Widget

Shows performance metrics from Google Discover (if available).

**Metrics:**
- Impressions from Discover
- Clicks from Discover
- Click-through rate
- Availability status

**Example:**
```
Discover Performance
├── Impressions: 5,432
├── Clicks: 234
├── CTR: 4.3%
└── Available: Yes
```

#### 6. Index Queue Status Widget

Displays the status of pending indexing requests.

**Metrics:**
- Pending requests
- Currently processing
- Completed requests
- Failed requests
- Last processed time

**Example:**
```
Index Queue Status
├── Pending: 12
├── Processing: 2
├── Completed: 1,234
├── Failed: 3
└── Last Processed: 2024-01-15 10:15 AM
```

### Widget Loading

- Widgets load asynchronously after the page renders
- Each widget has a loading indicator while data is being fetched
- If a widget fails to load, an error message is displayed without affecting other widgets
- Widget data is cached for 5 minutes to reduce database load

### Performance

- Initial page render: < 500ms
- Widget data retrieval: < 1 second per widget
- Zero database queries during initial page load

---

## Settings Page

The Settings page provides comprehensive configuration options for the MeowSEO plugin.

### URL

`/wp-admin/admin.php?page=meowseo-settings`

### Tab Structure

Settings are organized into five tabs:

#### 1. General Tab

Configure homepage SEO and title patterns.

**Fields:**

- **Homepage Title** - Custom title for your homepage
  - Default: Site title
  - Max length: 60 characters
  - Example: "My Awesome Website | SEO Tips & Tricks"

- **Homepage Description** - Custom meta description for homepage
  - Default: Site tagline
  - Max length: 160 characters
  - Example: "Learn SEO best practices and improve your search rankings."

- **Title Separator** - Character used to separate title parts
  - Options: `|`, `-`, `–`, `—`, `·`, `•`
  - Default: `|`
  - Example: "Post Title | Site Name"

- **Title Patterns** - Templates for automatic title generation
  - Available variables:
    - `%title%` - Post/page title
    - `%sitename%` - Site name
    - `%sep%` - Title separator
    - `%page%` - Page number (for paginated content)
    - `%category%` - Category name
    - `%date%` - Post date
  
  - Pattern fields:
    - Post Title Pattern
    - Page Title Pattern
    - Category Title Pattern
    - Tag Title Pattern
    - Archive Title Pattern
    - Search Results Title Pattern

**Example Patterns:**
```
Post: %title% %sep% %sitename%
Page: %title% %sep% %sitename%
Category: %category% %sep% %sitename%
Tag: %title% %sep% %sitename%
Archive: %title% %sep% %sitename%
Search: Search: %title% %sep% %sitename%
```

**Real-time Preview:**
- Each pattern field shows a live preview of how the title will appear
- Preview updates as you type
- Shows example with sample data

#### 2. Social Profiles Tab

Configure social media profiles for schema markup.

**Fields:**

- **Facebook URL** - Your Facebook page URL
  - Format: `https://facebook.com/yourpage`
  - Validation: Must be valid URL
  - Example: `https://facebook.com/mycompany`

- **Twitter Username** - Your Twitter handle (without @ symbol)
  - Format: `username` (not `@username`)
  - Example: `mycompany`

- **Instagram URL** - Your Instagram profile URL
  - Format: `https://instagram.com/yourprofile`
  - Example: `https://instagram.com/mycompany`

- **LinkedIn URL** - Your LinkedIn company page URL
  - Format: `https://linkedin.com/company/yourcompany`
  - Example: `https://linkedin.com/company/mycompany`

- **YouTube URL** - Your YouTube channel URL
  - Format: `https://youtube.com/c/yourchannel`
  - Example: `https://youtube.com/c/mycompany`

**Validation:**
- All URLs must be properly formatted
- Invalid URLs will show an error message
- URLs are sanitized using WordPress security functions

#### 3. Modules Tab

Enable or disable plugin modules.

**Available Modules:**

- **Meta** - Meta tags and title/description generation
- **Schema** - Structured data (JSON-LD) generation
- **Sitemap** - XML sitemap generation
- **Redirects** - Redirect management
- **404 Monitor** - 404 error tracking
- **Internal Links** - Internal linking suggestions
- **GSC** - Google Search Console integration
- **Social** - Social media integration
- **WooCommerce** - WooCommerce SEO (only shown if WooCommerce is installed)

**Module Descriptions:**

Each module has a description explaining its functionality. Disabling a module prevents it from loading on the next page load.

**Example:**
```
✓ Meta - Generates meta tags and title/description for posts
✓ Schema - Creates structured data markup for search engines
✓ Sitemap - Generates XML sitemaps for search engines
✓ Redirects - Manages URL redirects
✓ 404 Monitor - Tracks 404 errors
✓ Internal Links - Suggests relevant internal links
✓ GSC - Integrates with Google Search Console
✓ Social - Adds social media integration
✓ WooCommerce - Optimizes WooCommerce products
```

#### 4. Advanced Tab

Configure advanced SEO options.

**Fields:**

- **Noindex Settings** - Prevent indexing of specific content
  - Post types to noindex (e.g., attachment)
  - Taxonomies to noindex
  - Archives to noindex (author, date)

- **Canonical URL Settings**
  - Force trailing slash: Add trailing slash to all URLs
  - Force HTTPS: Convert all URLs to HTTPS

- **RSS Feed Settings**
  - Content before feed items: HTML to add before each post in RSS
  - Content after feed items: HTML to add after each post in RSS

- **Delete on Uninstall**
  - When enabled, all plugin data is deleted when plugin is uninstalled
  - Warning: This action cannot be undone

**Example Configuration:**
```
Noindex Settings:
├── Post Types: attachment
├── Taxonomies: (none)
└── Archives: author, date

Canonical Settings:
├── Force Trailing Slash: Yes
└── Force HTTPS: Yes

RSS Settings:
├── Before: <p>Read the full article on our site:</p>
└── After: <p>Subscribe to our RSS feed for more content.</p>

Delete on Uninstall: No
```

#### 5. Breadcrumbs Tab

Configure breadcrumb display and formatting.

**Fields:**

- **Enable Breadcrumbs** - Toggle breadcrumb display
  - When disabled, breadcrumbs are not shown anywhere

- **Breadcrumb Separator** - Character between breadcrumb items
  - Options: `>`, `/`, `»`, `|`, `-`
  - Default: `>`
  - Example: "Home > Category > Post"

- **Home Label** - Text for the home breadcrumb
  - Default: "Home"
  - Example: "Home", "Start", "Main"

- **Breadcrumb Prefix** - Text before breadcrumb trail
  - Default: (empty)
  - Example: "You are here:", "Navigation:"

- **Breadcrumb Position** - Where breadcrumbs appear
  - Options:
    - Before content
    - After content
    - Manual (use shortcode)

- **Show on Post Types** - Which post types display breadcrumbs
  - Checkboxes for: post, page, custom post types

- **Show on Taxonomies** - Which taxonomies display breadcrumbs
  - Checkboxes for: category, tag, custom taxonomies

**Example Breadcrumb Output:**
```
You are here: Home > Blog > WordPress > SEO Tips
```

### Settings Validation

- All fields are validated before saving
- Invalid entries show field-specific error messages
- Valid settings are saved successfully
- A success notice is displayed after saving

### Settings Storage

- Settings are stored in WordPress options table
- Settings are encrypted for sensitive data
- Settings changes are logged for audit trail

---

## Tools Page

The Tools page provides import/export functionality, database maintenance, and bulk SEO operations.

### URL

`/wp-admin/admin.php?page=meowseo-tools`

### Sections

#### 1. Import/Export Section

**Export Settings**
- Button: "Export Settings as JSON"
- Downloads all plugin settings as a JSON file
- Filename: `meowseo-settings-{date}.json`
- Use case: Backup settings or migrate to another site

**Export Redirects**
- Button: "Export Redirects as CSV"
- Downloads all redirects as a CSV file
- Filename: `meowseo-redirects-{date}.csv`
- CSV format: `source_url,destination_url,status_code`
- Use case: Backup redirects or import into another tool

**Import Settings**
- File upload field for JSON files
- Validates JSON structure before importing
- Shows error message if JSON is invalid
- Preserves existing settings if import fails

**Import Redirects**
- File upload field for CSV files
- Expected CSV format: `source_url,destination_url,status_code`
- Validates CSV structure before importing
- Shows error message if CSV is invalid

**Example CSV Format:**
```csv
source_url,destination_url,status_code
/old-page/,/new-page/,301
/typo-page/,/correct-page/,301
/legacy-url/,/modern-url/,301
```

#### 2. Database Maintenance Section

**Clear Old Logs**
- Button: "Clear Logs Older Than 90 Days"
- Deletes 404 log entries older than 90 days
- Deletes GSC log entries older than 90 days
- Shows confirmation dialog before executing
- Displays number of entries deleted

**Repair Tables**
- Button: "Repair Database Tables"
- Runs REPAIR TABLE on all plugin database tables
- Shows confirmation dialog before executing
- Displays repair results

**Flush Caches**
- Button: "Flush All Caches"
- Deletes all transients with meowseo prefix
- Clears object cache entries
- Shows confirmation dialog before executing
- Useful after making bulk changes

#### 3. SEO Data Section

**Bulk Generate Descriptions**
- Button: "Generate Missing Descriptions"
- Generates meta descriptions for posts missing them
- Uses post excerpt or first 160 characters of content
- Processes in batches of 50 posts
- Shows progress bar during operation
- Displays number of descriptions generated

**Scan for Missing SEO Data**
- Button: "Scan for Missing Data"
- Identifies posts missing SEO fields:
  - Title
  - Description
  - Focus keyword
- Displays report with:
  - Post ID
  - Post title
  - Missing fields
  - Edit link
- Useful for identifying content that needs SEO optimization

### Confirmation Dialogs

All destructive operations show a confirmation dialog:

```
Are you sure you want to clear logs older than 90 days?
This action cannot be undone.

[Cancel] [Confirm]
```

### Progress Indicators

Bulk operations display a progress bar:

```
Processing: 45/150 posts (30%)
[████████░░░░░░░░░░░░░░░░░░░░]
```

### Error Handling

- File upload errors show specific reasons (file too large, invalid format)
- Database operation errors are logged and displayed
- Bulk operations continue even if individual items fail
- Failed items are reported in the results

---

## REST API Endpoints

All REST endpoints are registered under the `meowseo/v1` namespace.

### Base URL

```
https://yoursite.com/wp-json/meowseo/v1
```

### Dashboard Widget Endpoints

#### Get Content Health Data

```http
GET /meowseo/v1/dashboard/content-health
```

**Authentication:** Requires `manage_options` capability and valid nonce

**Response:**
```json
{
  "total_posts": 150,
  "missing_title": 10,
  "missing_description": 15,
  "missing_focus_keyword": 8,
  "percentage_complete": 85.33
}
```

#### Get Sitemap Status

```http
GET /meowseo/v1/dashboard/sitemap-status
```

**Response:**
```json
{
  "enabled": true,
  "last_generated": "2024-01-15T10:30:00Z",
  "total_urls": 245,
  "post_types": {
    "post": 150,
    "page": 95
  },
  "cache_status": "fresh"
}
```

#### Get Top 404 Errors

```http
GET /meowseo/v1/dashboard/top-404s
```

**Response:**
```json
[
  {
    "url": "/old-post-slug/",
    "count": 45,
    "last_seen": "2024-01-15T14:30:00Z",
    "has_redirect": false
  },
  {
    "url": "/typo-page/",
    "count": 23,
    "last_seen": "2024-01-14T09:15:00Z",
    "has_redirect": true
  }
]
```

#### Get GSC Summary

```http
GET /meowseo/v1/dashboard/gsc-summary
```

**Response:**
```json
{
  "clicks": 1234,
  "impressions": 45678,
  "ctr": 2.7,
  "position": 12.3,
  "date_range": {
    "start": "2024-01-01",
    "end": "2024-01-28"
  },
  "last_synced": "2024-01-15T09:00:00Z"
}
```

#### Get Discover Performance

```http
GET /meowseo/v1/dashboard/discover-performance
```

**Response:**
```json
{
  "impressions": 5432,
  "clicks": 234,
  "ctr": 4.3,
  "available": true,
  "date_range": {
    "start": "2024-01-01",
    "end": "2024-01-28"
  }
}
```

#### Get Index Queue Status

```http
GET /meowseo/v1/dashboard/index-queue
```

**Response:**
```json
{
  "pending": 12,
  "processing": 2,
  "completed": 1234,
  "failed": 3,
  "last_processed": "2024-01-15T10:15:00Z"
}
```

### Internal Link Suggestion Endpoint

#### Get Link Suggestions

```http
POST /meowseo/v1/internal-links/suggest
```

**Authentication:** Requires `edit_posts` capability and valid nonce

**Rate Limiting:** 1 request per 2 seconds per user

**Request Body:**
```json
{
  "content": "This is the post content...",
  "post_id": 123
}
```

**Response:**
```json
[
  {
    "post_id": 456,
    "title": "Related Post Title",
    "url": "https://example.com/related-post/",
    "score": 85
  },
  {
    "post_id": 789,
    "title": "Another Related Post",
    "url": "https://example.com/another-post/",
    "score": 72
  }
]
```

**Rate Limit Response (429):**
```json
{
  "code": "rate_limit_exceeded",
  "message": "Too many requests. Please wait before trying again.",
  "data": {
    "status": 429
  }
}
```

Headers:
```
Retry-After: 2
```

### Public SEO Endpoints

#### Get SEO Data for Post

```http
GET /meowseo/v1/seo/post/{id}
```

**Authentication:** Public (no authentication required for published posts)

**Response:**
```json
{
  "post_id": 123,
  "title": "Custom SEO Title",
  "description": "Custom meta description",
  "robots": "index,follow",
  "canonical": "https://example.com/post/",
  "openGraph": {
    "title": "OG Title",
    "description": "OG Description",
    "image": "https://example.com/image.jpg",
    "type": "article",
    "url": "https://example.com/post/"
  },
  "twitterCard": {
    "card": "summary_large_image",
    "title": "Twitter Title",
    "description": "Twitter Description",
    "image": "https://example.com/image.jpg"
  },
  "schemaJsonLd": {
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "Article Title",
    "description": "Article description",
    "image": "https://example.com/image.jpg"
  }
}
```

**Cache Headers:**
```
Cache-Control: public, max-age=300
ETag: "abc123def456"
Vary: Accept
```

#### Get SEO Data by URL

```http
GET /meowseo/v1/seo?url={url}
```

**Parameters:**
- `url` (required) - Full URL to check

**Response:** Same as `/seo/post/{id}`

#### Get Schema for Post

```http
GET /meowseo/v1/schema/post/{id}
```

**Response:**
```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Article",
      "headline": "Article Title",
      "description": "Article description"
    },
    {
      "@type": "BreadcrumbList",
      "itemListElement": [...]
    }
  ]
}
```

#### Get Breadcrumbs

```http
GET /meowseo/v1/breadcrumbs?url={url}
```

**Response:**
```json
[
  {
    "name": "Home",
    "url": "https://example.com/"
  },
  {
    "name": "Blog",
    "url": "https://example.com/blog/"
  },
  {
    "name": "Article Title",
    "url": "https://example.com/blog/article/"
  }
]
```

#### Check for Redirects

```http
GET /meowseo/v1/redirects/check?url={url}
```

**Response:**
```json
{
  "url": "/old-page/",
  "has_redirect": true,
  "redirect_to": "/new-page/",
  "status_code": 301
}
```

### Error Responses

All endpoints return consistent error responses:

**400 Bad Request:**
```json
{
  "code": "invalid_parameter",
  "message": "Invalid parameter: url",
  "data": {
    "status": 400
  }
}
```

**403 Forbidden:**
```json
{
  "code": "rest_forbidden",
  "message": "You do not have permission to access this resource.",
  "data": {
    "status": 403
  }
}
```

**404 Not Found:**
```json
{
  "code": "post_not_found",
  "message": "Post not found.",
  "data": {
    "status": 404
  }
}
```

**429 Too Many Requests:**
```json
{
  "code": "rate_limit_exceeded",
  "message": "Too many requests. Please wait before trying again.",
  "data": {
    "status": 429
  }
}
```

---

## WooCommerce Integration

The WooCommerce module extends MeowSEO functionality for e-commerce sites.

### Module Activation

The WooCommerce module:
- Loads automatically when WooCommerce is installed and enabled
- Can be disabled in Settings > Modules
- Only appears in the Modules tab when WooCommerce is active

### Product Schema

Product pages automatically include structured data:

**Schema Type:** Product

**Fields:**
- name - Product title
- description - Product description
- image - Product image URL
- sku - Product SKU
- brand - Product brand
- offers - Price, currency, availability
- aggregateRating - Rating and review count (if reviews exist)
- review - Individual reviews

**Example Schema:**
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Product Name",
  "description": "Product description",
  "image": "https://example.com/product-image.jpg",
  "sku": "PROD-123",
  "brand": {
    "@type": "Brand",
    "name": "Brand Name"
  },
  "offers": {
    "@type": "Offer",
    "price": "99.99",
    "priceCurrency": "USD",
    "availability": "https://schema.org/InStock",
    "url": "https://example.com/product/"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.5",
    "reviewCount": "24"
  }
}
```

### Product Sitemaps

Products are automatically included in XML sitemaps:

**Sitemap Settings:**
- Priority: 0.8
- Change frequency: weekly
- Last modified: Product modified date
- Out-of-stock exclusion: Can be enabled in settings

**Example Sitemap Entry:**
```xml
<url>
  <loc>https://example.com/product/awesome-product/</loc>
  <lastmod>2024-01-15</lastmod>
  <changefreq>weekly</changefreq>
  <priority>0.8</priority>
</url>
```

### Product Category SEO

Product categories automatically get:
- Meta tags (title, description)
- Category description as meta description
- Auto-generated description if empty: "Products in [Category Name]"
- Breadcrumb integration

### Product Breadcrumbs

Breadcrumbs on product pages include:
- Home
- Shop page
- Category hierarchy
- Product name

**Example:**
```
Home > Shop > Electronics > Computers > Laptop Pro 15"
```

### Shop Page SEO

The shop page (product archive) includes:
- Custom title and description
- Product schema markup
- Breadcrumbs

---

## Troubleshooting Guide

### Common Issues and Solutions

#### Dashboard Widgets Not Loading

**Problem:** Widgets show loading indicator but never populate

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify REST API is enabled: Settings > Permalinks > Check "REST API"
3. Clear browser cache and reload
4. Check WordPress error log for REST API errors
5. Verify user has `manage_options` capability

**Debug:**
```javascript
// In browser console
fetch('/wp-json/meowseo/v1/dashboard/content-health', {
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
.then(r => r.json())
.then(d => console.log(d))
```

#### Settings Not Saving

**Problem:** Settings form shows success message but changes don't persist

**Solutions:**
1. Check WordPress error log for database errors
2. Verify user has `manage_options` capability
3. Check that nonce is being sent with request
4. Verify database tables are not corrupted
5. Check disk space on server

**Debug:**
```php
// In wp-config.php, enable debugging
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

#### Import/Export Not Working

**Problem:** File upload fails or import doesn't work

**Solutions:**
1. Check file format (JSON for settings, CSV for redirects)
2. Verify file size is under server limit (usually 2MB)
3. Check file permissions
4. Verify file encoding is UTF-8
5. Check WordPress error log

**File Format Validation:**

Settings JSON:
```json
{
  "homepage_title": "...",
  "homepage_description": "...",
  "separator": "|"
}
```

Redirects CSV:
```csv
source_url,destination_url,status_code
/old/,/new/,301
```

#### Suggestion Engine Not Working

**Problem:** Internal link suggestions not appearing

**Solutions:**
1. Verify post has sufficient content (at least 3 keywords after stopword filtering)
2. Check rate limiting (1 request per 2 seconds)
3. Verify database has other posts to suggest
4. Check browser console for errors
5. Verify user has `edit_posts` capability

**Debug:**
```javascript
// Check rate limit
const lastRequest = localStorage.getItem('meowseo_last_suggestion_request');
const now = Date.now();
if (lastRequest && (now - lastRequest) < 2000) {
  console.log('Rate limited. Wait', 2000 - (now - lastRequest), 'ms');
}
```

#### WooCommerce Products Not Appearing in Sitemap

**Problem:** WooCommerce products missing from XML sitemap

**Solutions:**
1. Verify WooCommerce module is enabled: Settings > Modules > WooCommerce
2. Check if products are published
3. Verify products are not marked as noindex
4. Check if out-of-stock exclusion is enabled
5. Regenerate sitemap: Tools > Flush Caches

#### Performance Issues

**Problem:** Dashboard loads slowly or suggestion engine is slow

**Solutions:**
1. Clear old logs: Tools > Clear Logs Older Than 90 Days
2. Flush caches: Tools > Flush All Caches
3. Repair tables: Tools > Repair Database Tables
4. Check for database indexes on post_title and post_content
5. Verify server has sufficient resources

**Performance Benchmarks:**
- Dashboard load: < 500ms
- Widget data retrieval: < 1 second
- Suggestion engine: < 1 second for 5,000-word posts

#### REST API Errors

**Problem:** REST endpoints return 403 or 401 errors

**Solutions:**
1. Verify user is logged in
2. Check user has required capability (manage_options or edit_posts)
3. Verify nonce is valid and being sent
4. Check WordPress error log
5. Verify REST API is enabled

**Common Error Codes:**
- `rest_forbidden` (403) - User lacks capability
- `rest_invalid_nonce` (403) - Nonce verification failed
- `rest_not_logged_in` (401) - User not authenticated
- `rate_limit_exceeded` (429) - Too many requests

### Getting Help

If you encounter issues not covered in this guide:

1. Check the WordPress error log: `/wp-content/debug.log`
2. Enable WordPress debugging in `wp-config.php`
3. Check browser console for JavaScript errors
4. Review plugin documentation
5. Contact plugin support with error details

### Enabling Debug Mode

Add to `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

This creates a debug log at `/wp-content/debug.log`

---

## Additional Resources

- [API Documentation](API_DOCUMENTATION.md)
- [REST API Reference](includes/REST_API_IMPLEMENTATION.md)
- [Security Documentation](includes/SECURITY.md)
- [Performance Optimizations](includes/PERFORMANCE_OPTIMIZATIONS.md)

---

**Last Updated:** January 2024
**Version:** 1.0
