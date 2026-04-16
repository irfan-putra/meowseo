# Requirements Document

## Introduction

This document specifies the requirements for completing the MeowSEO WordPress plugin admin interface. The feature encompasses the admin dashboard with async-loaded widgets, comprehensive settings system, internal linking suggestion engine, headless REST API, and WooCommerce integration. This completes the plugin by providing administrators with a performant, user-friendly interface for managing all SEO functionality.

## Glossary

- **Admin_Dashboard**: The main administrative interface page displaying SEO metrics and status widgets
- **Settings_Page**: The configuration interface with tabbed sections for plugin options
- **Tools_Page**: Administrative page providing import/export, database maintenance, and bulk SEO operations
- **Widget**: An asynchronous UI component that loads data independently after page render
- **Suggestion_Engine**: The internal linking recommendation system that analyzes content and suggests relevant links
- **REST_SEO_API**: Public REST endpoints providing SEO data for headless and external integrations
- **WooCommerce_Module**: Integration module that extends SEO functionality for WooCommerce products
- **Stopwords**: Common words filtered out during keyword extraction (e.g., "the", "and", "is")
- **SERP**: Search Engine Results Page
- **GSC**: Google Search Console
- **Nonce**: WordPress security token for verifying request authenticity

## Requirements

### Requirement 1: Admin Menu Structure

**User Story:** As a site administrator, I want a centralized MeowSEO menu with organized submenu pages, so that I can easily access all plugin functionality.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL register a top-level WordPress admin menu with the label "MeowSEO"
2. THE Admin_Dashboard SHALL use a cat icon (dashicons-cat) for the menu item
3. THE Admin_Dashboard SHALL register submenu pages for Dashboard, Settings, Redirects, 404 Monitor, Search Console, and Tools
4. THE Admin_Dashboard SHALL require manage_options capability for all menu pages
5. WHEN a user without manage_options capability attempts to access any menu page, THEN THE Admin_Dashboard SHALL display a permission denied message

### Requirement 2: Async Dashboard Widgets

**User Story:** As a site administrator, I want the dashboard to load quickly with widgets that populate asynchronously, so that I can see the interface immediately even on large sites.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL render empty widget containers on initial page load without executing database queries
2. THE Admin_Dashboard SHALL complete initial page render within 2 seconds on sites with 10,000+ posts
3. WHEN the dashboard page loads, THE Admin_Dashboard SHALL trigger REST API calls to populate each widget independently
4. THE Admin_Dashboard SHALL display loading indicators in widget containers until data arrives
5. THE Admin_Dashboard SHALL provide widgets for Content Health, Sitemap Status, Top 404s, Search Console Summary, Discover Performance, and Index Queue Status
6. WHEN a widget REST endpoint fails, THEN THE Admin_Dashboard SHALL display an error message in that widget without affecting other widgets

### Requirement 3: Dashboard REST Endpoints

**User Story:** As a developer, I want secure REST endpoints for dashboard widgets, so that widget data loads asynchronously with proper authentication.

#### Acceptance Criteria

1. THE REST_SEO_API SHALL register all dashboard widget endpoints under the meowseo/v1 namespace
2. THE REST_SEO_API SHALL require manage_options capability for all dashboard widget endpoints
3. THE REST_SEO_API SHALL verify WordPress nonce for all dashboard widget requests
4. THE REST_SEO_API SHALL provide endpoints for /dashboard/content-health, /dashboard/sitemap-status, /dashboard/top-404s, /dashboard/gsc-summary, /dashboard/discover-performance, and /dashboard/index-queue
5. WHEN a dashboard widget endpoint receives an unauthenticated request, THEN THE REST_SEO_API SHALL return HTTP 403 status
6. WHEN a dashboard widget endpoint receives a request with invalid nonce, THEN THE REST_SEO_API SHALL return HTTP 403 status

### Requirement 4: Settings Page with Tabs

**User Story:** As a site administrator, I want a tabbed settings interface, so that I can configure plugin options organized by category.

#### Acceptance Criteria

1. THE Settings_Page SHALL display tabs for General, Social Profiles, Modules, Advanced, and Breadcrumbs
2. THE Settings_Page SHALL use WordPress Settings API for all form fields
3. THE Settings_Page SHALL display the General tab by default when the page loads
4. WHEN a user clicks a tab, THE Settings_Page SHALL display that tab's content without page reload
5. THE Settings_Page SHALL validate all input fields before saving
6. WHEN a user submits invalid settings, THEN THE Settings_Page SHALL display field-specific error messages
7. WHEN a user successfully saves settings, THEN THE Settings_Page SHALL display a success notice

### Requirement 5: General Settings Tab

**User Story:** As a site administrator, I want to configure homepage SEO and title patterns, so that I can control how my site appears in search results.

#### Acceptance Criteria

1. THE Settings_Page SHALL provide fields for homepage title, homepage description, and title separator in the General tab
2. THE Settings_Page SHALL provide title pattern fields for post types, taxonomies, archives, and search results
3. THE Settings_Page SHALL support pattern variables including %title%, %sitename%, %sep%, %page%, %category%, and %date%
4. THE Settings_Page SHALL display a preview of the rendered title pattern below each pattern field
5. WHEN a user enters a title pattern, THE Settings_Page SHALL update the preview in real-time

### Requirement 6: Social Profiles Tab

**User Story:** As a site administrator, I want to configure social media profiles, so that they appear in schema markup for enhanced search results.

#### Acceptance Criteria

1. THE Settings_Page SHALL provide URL fields for Facebook, Twitter, Instagram, LinkedIn, and YouTube in the Social Profiles tab
2. THE Settings_Page SHALL provide a field for Twitter username (without @ symbol)
3. THE Settings_Page SHALL validate that social profile URLs are properly formatted
4. WHEN a user enters an invalid URL, THEN THE Settings_Page SHALL display a validation error
5. THE Settings_Page SHALL sanitize all social profile URLs using esc_url_raw

### Requirement 7: Modules Tab

**User Story:** As a site administrator, I want to enable or disable plugin modules, so that I can control which features are active.

#### Acceptance Criteria

1. THE Settings_Page SHALL display toggle switches for each available module in the Modules tab
2. THE Settings_Page SHALL display modules for Meta, Schema, Sitemap, Redirects, 404 Monitor, Internal Links, GSC, Social, and WooCommerce
3. WHEN WooCommerce is not installed, THE Settings_Page SHALL hide the WooCommerce module toggle
4. WHEN a user disables a module, THE Settings_Page SHALL prevent that module from loading on next page load
5. THE Settings_Page SHALL display a description for each module explaining its functionality

### Requirement 8: Advanced Settings Tab

**User Story:** As a site administrator, I want to configure advanced SEO options, so that I can fine-tune plugin behavior.

#### Acceptance Criteria

1. THE Settings_Page SHALL provide noindex settings for post types, taxonomies, and archives in the Advanced tab
2. THE Settings_Page SHALL provide canonical URL settings including force trailing slash and force HTTPS
3. THE Settings_Page SHALL provide RSS feed settings for adding content before and after feed items
4. THE Settings_Page SHALL provide a setting to delete all plugin data on uninstall
5. WHEN a user enables "delete on uninstall", THE Settings_Page SHALL display a warning message

### Requirement 9: Breadcrumbs Tab

**User Story:** As a site administrator, I want to configure breadcrumb settings, so that I can control breadcrumb display and formatting.

#### Acceptance Criteria

1. THE Settings_Page SHALL provide an enable/disable toggle for breadcrumbs in the Breadcrumbs tab
2. THE Settings_Page SHALL provide fields for breadcrumb separator, home label, and prefix
3. THE Settings_Page SHALL provide options to show/hide breadcrumbs on post types and taxonomies
4. THE Settings_Page SHALL provide a setting for breadcrumb position (before content, after content, manual)
5. THE Settings_Page SHALL display example breadcrumb output based on current settings

### Requirement 10: Tools Page Structure

**User Story:** As a site administrator, I want a tools page with import/export and maintenance functions, so that I can manage plugin data and perform bulk operations.

#### Acceptance Criteria

1. THE Tools_Page SHALL display sections for Import/Export, Database Maintenance, and SEO Data
2. THE Tools_Page SHALL require manage_options capability for access
3. THE Tools_Page SHALL verify nonce for all form submissions
4. WHEN a user without manage_options capability attempts to access the Tools page, THEN THE Tools_Page SHALL display a permission denied message

### Requirement 11: Import/Export Tools

**User Story:** As a site administrator, I want to import and export plugin data, so that I can backup settings or migrate to another site.

#### Acceptance Criteria

1. THE Tools_Page SHALL provide a button to export all plugin settings as JSON
2. THE Tools_Page SHALL provide a button to export all redirects as CSV
3. THE Tools_Page SHALL provide a file upload field to import settings from JSON
4. THE Tools_Page SHALL provide a file upload field to import redirects from CSV
5. WHEN a user exports settings, THE Tools_Page SHALL generate a JSON file with all plugin options
6. WHEN a user imports settings, THE Tools_Page SHALL validate the JSON structure before applying
7. WHEN a user imports invalid data, THEN THE Tools_Page SHALL display an error message without modifying existing settings

### Requirement 12: Database Maintenance Tools

**User Story:** As a site administrator, I want database maintenance tools, so that I can optimize plugin performance and clean up old data.

#### Acceptance Criteria

1. THE Tools_Page SHALL provide a button to clear old log entries (older than 90 days)
2. THE Tools_Page SHALL provide a button to repair database tables
3. THE Tools_Page SHALL provide a button to flush all plugin caches
4. WHEN a user clicks "Clear Old Logs", THE Tools_Page SHALL delete 404 log entries and GSC log entries older than 90 days
5. WHEN a user clicks "Repair Tables", THE Tools_Page SHALL run REPAIR TABLE on all plugin database tables
6. WHEN a user clicks "Flush Caches", THE Tools_Page SHALL delete all transients and object cache entries with meowseo prefix
7. THE Tools_Page SHALL display a confirmation dialog before executing any destructive maintenance operation

### Requirement 13: SEO Data Tools

**User Story:** As a site administrator, I want bulk SEO data tools, so that I can generate missing metadata and identify content issues.

#### Acceptance Criteria

1. THE Tools_Page SHALL provide a button to bulk generate meta descriptions for posts missing descriptions
2. THE Tools_Page SHALL provide a button to scan for missing SEO data (title, description, focus keyword)
3. WHEN a user clicks "Bulk Generate Descriptions", THE Tools_Page SHALL create descriptions from post excerpts or first 160 characters of content
4. WHEN a user clicks "Scan for Missing Data", THE Tools_Page SHALL display a report showing posts with missing SEO fields
5. THE Tools_Page SHALL process bulk operations in batches of 50 posts to avoid timeouts
6. THE Tools_Page SHALL display a progress indicator during bulk operations

### Requirement 14: Internal Linking Suggestion Engine

**User Story:** As a content editor, I want internal link suggestions while editing, so that I can easily add relevant links to improve SEO.

#### Acceptance Criteria

1. THE Suggestion_Engine SHALL extract keywords from post content by removing Stopwords
2. THE Suggestion_Engine SHALL query for relevant posts using extracted keywords
3. THE Suggestion_Engine SHALL score results by counting keyword matches in title and content
4. THE Suggestion_Engine SHALL return up to 10 suggestions ordered by relevance score
5. THE Suggestion_Engine SHALL exclude the current post from suggestions
6. THE Suggestion_Engine SHALL only suggest published posts
7. WHEN content contains fewer than 3 keywords after stopword removal, THEN THE Suggestion_Engine SHALL return an empty result set

### Requirement 15: Suggestion Engine REST Endpoint

**User Story:** As a developer, I want a REST endpoint for internal link suggestions, so that the Gutenberg editor can request suggestions asynchronously.

#### Acceptance Criteria

1. THE REST_SEO_API SHALL register a POST endpoint at /internal-links/suggest under meowseo/v1 namespace
2. THE REST_SEO_API SHALL require edit_posts capability for the suggestions endpoint
3. THE REST_SEO_API SHALL accept parameters for content (string) and post_id (integer)
4. THE REST_SEO_API SHALL implement rate limiting of 1 request per 2 seconds per user
5. WHEN a user exceeds the rate limit, THEN THE REST_SEO_API SHALL return HTTP 429 status with Retry-After header
6. THE REST_SEO_API SHALL return suggestions as JSON array with fields for post_id, title, url, and score

### Requirement 16: Indonesian Stopwords

**User Story:** As a developer, I want an Indonesian stopwords list, so that the suggestion engine works effectively for Indonesian content.

#### Acceptance Criteria

1. THE Suggestion_Engine SHALL load stopwords from includes/data/stopwords-id.php
2. THE Suggestion_Engine SHALL support both English and Indonesian stopwords
3. THE Suggestion_Engine SHALL filter stopwords case-insensitively
4. THE Suggestion_Engine SHALL include common Indonesian stopwords including "yang", "dan", "di", "ke", "dari", "untuk", "pada", "adalah", "dengan", "ini", "itu", "atau", "juga", "akan", "telah", "dapat", "ada", "tidak", "dalam", "oleh", "sebagai", "antara", "karena", "saat", "setelah", "sebelum", "jika", "maka", "tetapi", "namun", "bahwa", "saya", "kami", "kita", "mereka", "dia", "ia", "anda", "kamu", "nya", "mu", "ku"

### Requirement 17: Public SEO REST Endpoints

**User Story:** As a headless frontend developer, I want public REST endpoints for SEO data, so that I can retrieve meta tags and schema for any URL.

#### Acceptance Criteria

1. THE REST_SEO_API SHALL register a GET endpoint at /seo/post/{id} returning all SEO data for a post
2. THE REST_SEO_API SHALL register a GET endpoint at /seo?url={url} returning SEO data by URL
3. THE REST_SEO_API SHALL register a GET endpoint at /schema/post/{id} returning only the schema @graph array
4. THE REST_SEO_API SHALL register a GET endpoint at /breadcrumbs?url={url} returning breadcrumb trail
5. THE REST_SEO_API SHALL register a GET endpoint at /redirects/check?url={url} checking for redirects
6. THE REST_SEO_API SHALL allow public access to all SEO data endpoints for published posts
7. THE REST_SEO_API SHALL return HTTP 404 for unpublished or non-existent posts

### Requirement 18: SEO Data Response Format

**User Story:** As a headless frontend developer, I want consistent JSON response format, so that I can reliably parse SEO data.

#### Acceptance Criteria

1. THE REST_SEO_API SHALL return SEO data with fields for title, description, robots, canonical, og_title, og_description, og_image, twitter_card, twitter_title, twitter_description, twitter_image, and schema_json
2. THE REST_SEO_API SHALL return schema_json as a parsed JSON object (not string)
3. THE REST_SEO_API SHALL include Cache-Control headers with max-age=300 for GET requests
4. THE REST_SEO_API SHALL return HTTP 200 for successful requests
5. THE REST_SEO_API SHALL return HTTP 400 for invalid parameters
6. THE REST_SEO_API SHALL return HTTP 404 for non-existent resources

### Requirement 19: WPGraphQL Integration

**User Story:** As a GraphQL developer, I want SEO data available in WPGraphQL, so that I can query it alongside other WordPress data.

#### Acceptance Criteria

1. WHEN WPGraphQL plugin is active, THE REST_SEO_API SHALL register SEO fields in the WPGraphQL schema
2. THE REST_SEO_API SHALL add seo field to Post, Page, and custom post type nodes
3. THE REST_SEO_API SHALL add seo field to Category, Tag, and custom taxonomy nodes
4. THE REST_SEO_API SHALL provide GraphQL fields for title, description, robots, canonical, openGraph, twitterCard, and schemaJson
5. THE REST_SEO_API SHALL resolve SEO fields using the same logic as REST endpoints

### Requirement 20: WooCommerce Module Activation

**User Story:** As a WooCommerce store owner, I want automatic WooCommerce SEO integration, so that my products have proper schema and sitemap inclusion.

#### Acceptance Criteria

1. WHEN WooCommerce plugin is active, THE WooCommerce_Module SHALL load automatically if enabled in settings
2. WHEN WooCommerce plugin is not active, THE WooCommerce_Module SHALL not load
3. THE WooCommerce_Module SHALL register hooks only after WooCommerce has initialized
4. THE WooCommerce_Module SHALL implement the Module interface
5. THE WooCommerce_Module SHALL return "woocommerce" as its module ID

### Requirement 21: Product Schema

**User Story:** As a WooCommerce store owner, I want Product schema on product pages, so that my products appear in rich results with price and availability.

#### Acceptance Criteria

1. THE WooCommerce_Module SHALL generate Product schema for single product pages
2. THE WooCommerce_Module SHALL include fields for name, description, image, sku, brand, offers, aggregateRating, and review
3. THE WooCommerce_Module SHALL set offers.price to the product's current price
4. THE WooCommerce_Module SHALL set offers.priceCurrency from WooCommerce currency settings
5. THE WooCommerce_Module SHALL set offers.availability to InStock, OutOfStock, or PreOrder based on stock status
6. WHEN a product has reviews, THE WooCommerce_Module SHALL include aggregateRating with ratingValue and reviewCount
7. THE WooCommerce_Module SHALL validate Product schema against schema.org specification

### Requirement 22: Product Sitemaps

**User Story:** As a WooCommerce store owner, I want products in XML sitemaps, so that search engines can discover and index my products.

#### Acceptance Criteria

1. THE WooCommerce_Module SHALL add product post type to sitemap generation
2. THE WooCommerce_Module SHALL respect the "Exclude out-of-stock products" setting
3. WHEN "Exclude out-of-stock products" is enabled, THE WooCommerce_Module SHALL exclude products with stock status "outofstock"
4. THE WooCommerce_Module SHALL set product priority to 0.8 in sitemaps
5. THE WooCommerce_Module SHALL set product changefreq to weekly
6. THE WooCommerce_Module SHALL use product modified date for lastmod

### Requirement 23: WooCommerce Category Handling

**User Story:** As a WooCommerce store owner, I want proper SEO for product categories and shop page, so that category pages rank well in search results.

#### Acceptance Criteria

1. THE WooCommerce_Module SHALL generate meta tags for product_cat taxonomy archives
2. THE WooCommerce_Module SHALL generate meta tags for the shop page
3. THE WooCommerce_Module SHALL use category description as meta description if available
4. WHEN category description is empty, THE WooCommerce_Module SHALL generate description from "Products in [category name]"
5. THE WooCommerce_Module SHALL include product_cat taxonomy in breadcrumbs

### Requirement 24: WooCommerce Breadcrumbs

**User Story:** As a WooCommerce store owner, I want breadcrumbs that reflect product category hierarchy, so that users can navigate my store structure.

#### Acceptance Criteria

1. THE WooCommerce_Module SHALL generate breadcrumbs for product pages including category hierarchy
2. THE WooCommerce_Module SHALL use primary category if product has multiple categories
3. THE WooCommerce_Module SHALL include Shop page in breadcrumb trail before categories
4. THE WooCommerce_Module SHALL format breadcrumbs as: Home > Shop > Category > Subcategory > Product
5. THE WooCommerce_Module SHALL generate BreadcrumbList schema for product pages

### Requirement 25: Performance - Dashboard Load Time

**User Story:** As a site administrator, I want the dashboard to load quickly, so that I can access plugin functionality without delays.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL complete initial HTML render within 500ms on sites with 10,000+ posts
2. THE Admin_Dashboard SHALL execute zero direct database queries during page render
3. THE Admin_Dashboard SHALL defer all data loading to asynchronous REST calls
4. THE Admin_Dashboard SHALL use WordPress transients for widget data with 5-minute TTL
5. WHEN widget data is cached, THE REST_SEO_API SHALL return cached data without database queries

### Requirement 26: Performance - Internal Link Suggestions

**User Story:** As a content editor, I want link suggestions to appear quickly, so that my editing workflow is not interrupted.

#### Acceptance Criteria

1. THE Suggestion_Engine SHALL return results within 1 second for posts with up to 5,000 words
2. THE Suggestion_Engine SHALL use database indexes on post_title and post_content for keyword queries
3. THE Suggestion_Engine SHALL limit keyword extraction to first 2,000 words of content
4. THE Suggestion_Engine SHALL cache suggestion results for 10 minutes per post
5. WHEN cached suggestions exist, THE Suggestion_Engine SHALL return cached results without database queries

### Requirement 27: Performance - REST Endpoint Caching

**User Story:** As a headless frontend developer, I want REST responses to be cacheable, so that my frontend loads quickly.

#### Acceptance Criteria

1. THE REST_SEO_API SHALL include Cache-Control: public, max-age=300 header for all GET endpoints
2. THE REST_SEO_API SHALL include ETag header based on content hash for GET endpoints
3. THE REST_SEO_API SHALL support If-None-Match header and return HTTP 304 when content unchanged
4. THE REST_SEO_API SHALL include Vary: Accept header for content negotiation
5. THE REST_SEO_API SHALL include Cache-Control: no-store header for all POST/PUT/DELETE endpoints

### Requirement 28: Security - Nonce Verification

**User Story:** As a security-conscious administrator, I want all form submissions protected by nonces, so that CSRF attacks are prevented.

#### Acceptance Criteria

1. THE Settings_Page SHALL verify nonce for all settings form submissions
2. THE Tools_Page SHALL verify nonce for all tool actions
3. THE Admin_Dashboard SHALL verify nonce for all AJAX requests
4. WHEN nonce verification fails, THE Admin_Dashboard SHALL return HTTP 403 status
5. THE Admin_Dashboard SHALL generate unique nonces for each admin page

### Requirement 29: Security - Capability Checks

**User Story:** As a security-conscious administrator, I want all admin pages protected by capability checks, so that unauthorized users cannot access plugin settings.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL verify manage_options capability before rendering any admin page
2. THE REST_SEO_API SHALL verify appropriate capabilities for all authenticated endpoints
3. THE REST_SEO_API SHALL verify edit_posts capability for internal link suggestions
4. THE REST_SEO_API SHALL verify manage_options capability for dashboard widgets
5. WHEN capability check fails, THE Admin_Dashboard SHALL display "You do not have sufficient permissions" message

### Requirement 30: Security - Input Sanitization

**User Story:** As a security-conscious administrator, I want all user input sanitized, so that XSS and SQL injection attacks are prevented.

#### Acceptance Criteria

1. THE Settings_Page SHALL sanitize all text fields using sanitize_text_field
2. THE Settings_Page SHALL sanitize all textarea fields using sanitize_textarea_field
3. THE Settings_Page SHALL sanitize all URL fields using esc_url_raw
4. THE Settings_Page SHALL sanitize all HTML fields using wp_kses_post
5. THE REST_SEO_API SHALL use prepared statements for all database queries
6. THE REST_SEO_API SHALL validate and sanitize all REST endpoint parameters

### Requirement 31: Accessibility - WCAG 2.1 AA Compliance

**User Story:** As a user with disabilities, I want the admin interface to be accessible, so that I can use screen readers and keyboard navigation.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL provide ARIA labels for all interactive elements
2. THE Settings_Page SHALL associate all form labels with input fields using for attribute
3. THE Admin_Dashboard SHALL support full keyboard navigation without mouse
4. THE Admin_Dashboard SHALL provide focus indicators for all focusable elements
5. THE Admin_Dashboard SHALL use semantic HTML elements (button, nav, main, aside)
6. THE Admin_Dashboard SHALL provide alt text for all images
7. THE Admin_Dashboard SHALL maintain color contrast ratio of at least 4.5:1 for text

### Requirement 32: Error Handling - User-Friendly Messages

**User Story:** As a site administrator, I want clear error messages, so that I can understand and resolve issues.

#### Acceptance Criteria

1. WHEN a REST endpoint fails, THE REST_SEO_API SHALL return a JSON response with error message and error code
2. WHEN database query fails, THE Admin_Dashboard SHALL log the error and display "An error occurred. Please try again."
3. WHEN file upload fails, THE Tools_Page SHALL display the specific reason (file too large, invalid format, etc.)
4. WHEN settings validation fails, THE Settings_Page SHALL display field-specific error messages next to the invalid field
5. THE Admin_Dashboard SHALL never display raw PHP errors or stack traces to users

### Requirement 33: Logging - Admin Actions

**User Story:** As a site administrator, I want admin actions logged, so that I can audit changes and troubleshoot issues.

#### Acceptance Criteria

1. WHEN settings are saved, THE Settings_Page SHALL log the action with user ID and changed fields
2. WHEN redirects are imported, THE Tools_Page SHALL log the action with number of redirects imported
3. WHEN database maintenance is performed, THE Tools_Page SHALL log the action with operation type and result
4. WHEN bulk SEO operations are performed, THE Tools_Page SHALL log the action with number of posts affected
5. THE Admin_Dashboard SHALL use the Logger helper class for all logging
6. THE Admin_Dashboard SHALL include context data (user_id, timestamp, action) in all log entries
