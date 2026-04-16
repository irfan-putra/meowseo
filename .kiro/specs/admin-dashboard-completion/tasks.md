# Implementation Plan: Admin Dashboard Completion

## Overview

This implementation plan covers the completion of the MeowSEO WordPress plugin admin interface. The feature includes six major components: async-loaded dashboard widgets, comprehensive tabbed settings system, tools page with import/export and maintenance functions, internal linking suggestion engine with Indonesian stopwords, public REST API for headless WordPress, and WooCommerce integration module.

The implementation follows WordPress best practices, leverages the existing module architecture, and prioritizes performance through async loading, caching, and rate limiting. All admin pages require `manage_options` capability, all forms use nonce verification, and all input is sanitized.

## Tasks

- [x] 1. Set up admin menu structure and base classes
  - Extend `includes/class-admin.php` to register top-level "MeowSEO" menu with cat icon (dashicons-cat)
  - Register submenu pages: Dashboard, Settings, Redirects, 404 Monitor, Search Console, Tools
  - Add capability checks (manage_options) for all menu pages
  - Create base render methods for Dashboard, Settings, and Tools pages
  - Set up admin asset enqueuing (CSS/JS) with proper hook suffixes
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 2. Implement dashboard widgets infrastructure
  - [x] 2.1 Create Dashboard_Widgets class (includes/admin/class-dashboard-widgets.php)
    - Implement render_widgets() method to output empty widget containers
    - Add data attributes for widget IDs and REST endpoints
    - Include loading indicators and error state templates
    - _Requirements: 2.1, 2.2, 2.4_
  
  - [x] 2.2 Implement widget data methods
    - Create get_content_health_data() - query posts missing SEO data
    - Create get_sitemap_status_data() - check sitemap generation status
    - Create get_top_404s_data() - query 404 logs for top errors (last 30 days)
    - Create get_gsc_summary_data() - aggregate GSC metrics (clicks, impressions, CTR, position)
    - Create get_discover_performance_data() - query Discover metrics if available
    - Create get_index_queue_data() - count pending/processing/completed/failed indexing requests
    - _Requirements: 2.5, 3.4_
  
  - [x] 2.3 Add widget caching with transients
    - Implement 5-minute TTL caching for all widget data
    - Use transient keys: meowseo_dashboard_{widget_name}
    - Add cache invalidation on relevant data changes (post save, 404 log, GSC sync)
    - _Requirements: 2.4, 25.4, 25.5_
  
  - [x] 2.4 Create dashboard JavaScript for async loading
    - Write admin/js/dashboard.js to fetch widget data via REST API
    - Implement loading state management and error handling
    - Ensure widgets load independently without blocking each other
    - _Requirements: 2.3, 2.4, 2.6_

- [x] 3. Implement dashboard REST endpoints
  - [x] 3.1 Register dashboard widget endpoints in REST_API class
    - Add /meowseo/v1/dashboard/content-health endpoint
    - Add /meowseo/v1/dashboard/sitemap-status endpoint
    - Add /meowseo/v1/dashboard/top-404s endpoint
    - Add /meowseo/v1/dashboard/gsc-summary endpoint
    - Add /meowseo/v1/dashboard/discover-performance endpoint
    - Add /meowseo/v1/dashboard/index-queue endpoint
    - _Requirements: 3.1, 3.4_
  
  - [x] 3.2 Add authentication and authorization to dashboard endpoints
    - Verify manage_options capability for all dashboard endpoints
    - Verify WordPress nonce for all requests
    - Return HTTP 403 for unauthorized requests
    - _Requirements: 3.2, 3.3, 3.5, 3.6, 29.4_
  
  - [ ]* 3.3 Write integration tests for dashboard endpoints
    - Test capability checks (manage_options required)
    - Test nonce verification
    - Test widget data retrieval with caching
    - Test error handling when widget data fails
    - _Requirements: 3.2, 3.3, 3.5, 3.6_

- [x] 4. Checkpoint - Verify dashboard functionality
  - Ensure dashboard page loads in <500ms with empty widgets
  - Ensure widgets populate asynchronously via REST API
  - Ensure widget errors don't affect other widgets
  - Ensure all tests pass, ask the user if questions arise

- [x] 5. Implement settings manager infrastructure
  - [x] 5.1 Create Settings_Manager class (includes/admin/class-settings-manager.php)
    - Implement render_settings_tabs() to output tab navigation
    - Add JavaScript for tab switching without page reload
    - Set up WordPress Settings API integration
    - _Requirements: 4.1, 4.2, 4.3, 4.4_
  
  - [x] 5.2 Implement settings validation and sanitization
    - Create validate_settings() method returning array or WP_Error
    - Add sanitize_social_url() for URL validation
    - Implement field-specific error message handling
    - Add success notice display on save
    - _Requirements: 4.5, 4.6, 4.7, 30.1, 30.2, 30.3, 30.4_
  
  - [x] 5.3 Add settings save handler with logging
    - Integrate with Options class for settings storage
    - Log settings changes with user ID and changed fields
    - Verify nonce before saving
    - _Requirements: 28.1, 33.1_

- [x] 6. Implement General settings tab
  - [x] 6.1 Create render_general_tab() method
    - Add fields for homepage_title, homepage_description, separator
    - Add title pattern fields for post types, taxonomies, archives, search
    - Support pattern variables: %title%, %sitename%, %sep%, %page%, %category%, %date%
    - _Requirements: 5.1, 5.2, 5.3_
  
  - [x] 6.2 Add real-time title pattern preview
    - Write JavaScript to update preview on input change
    - Replace pattern variables with example values
    - Display preview below each pattern field
    - _Requirements: 5.4, 5.5_

- [x] 7. Implement Social Profiles settings tab
  - [x] 7.1 Create render_social_profiles_tab() method
    - Add URL fields for Facebook, Twitter, Instagram, LinkedIn, YouTube
    - Add Twitter username field (without @ symbol)
    - _Requirements: 6.1, 6.2_
  
  - [x] 7.2 Add social URL validation
    - Validate URLs using filter_var with FILTER_VALIDATE_URL
    - Display validation errors for invalid URLs
    - Sanitize all URLs with esc_url_raw
    - _Requirements: 6.3, 6.4, 6.5_

- [x] 8. Implement Modules settings tab
  - [x] 8.1 Create render_modules_tab() method
    - Display toggle switches for each module (Meta, Schema, Sitemap, Redirects, 404 Monitor, Internal Links, GSC, Social, WooCommerce)
    - Add module descriptions explaining functionality
    - Hide WooCommerce toggle when WooCommerce is not installed
    - _Requirements: 7.1, 7.2, 7.3, 7.5_
  
  - [x] 8.2 Implement module enable/disable logic
    - Update enabled_modules array in settings
    - Prevent disabled modules from loading on next page load
    - _Requirements: 7.4_

- [x] 9. Implement Advanced settings tab
  - Create render_advanced_tab() method
  - Add noindex settings for post types, taxonomies, archives
  - Add canonical URL settings (force trailing slash, force HTTPS)
  - Add RSS feed settings (content before/after feed items)
  - Add "delete on uninstall" setting with warning message
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [x] 10. Implement Breadcrumbs settings tab
  - Create render_breadcrumbs_tab() method
  - Add enable/disable toggle for breadcrumbs
  - Add fields for separator, home label, prefix, position
  - Add show/hide options for post types and taxonomies
  - Display example breadcrumb output based on current settings
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ]* 11. Write unit tests for settings validation
  - Test social URL validation (valid/invalid URLs)
  - Test title pattern variable replacement
  - Test input sanitization for all field types
  - Test settings save/load cycle
  - _Requirements: 6.3, 6.4, 30.1, 30.2, 30.3, 30.4_

- [x] 12. Checkpoint - Verify settings functionality
  - Ensure all tabs display correctly
  - Ensure tab switching works without page reload
  - Ensure validation errors display correctly
  - Ensure settings save successfully
  - Ensure all tests pass, ask the user if questions arise

- [x] 13. Implement tools page infrastructure
  - [x] 13.1 Create Tools_Manager class (includes/admin/class-tools-manager.php)
    - Implement render_tools_page() with sections for Import/Export, Database Maintenance, SEO Data
    - Add capability check (manage_options)
    - Add nonce verification for all form submissions
    - _Requirements: 10.1, 10.2, 10.3, 10.4_
  
  - [x] 13.2 Add AJAX handlers for tools operations
    - Set up AJAX endpoints for async operations
    - Implement progress tracking for batch operations
    - Add error handling and user feedback
    - _Requirements: 13.5, 13.6_

- [x] 14. Implement import/export tools
  - [x] 14.1 Create export functionality
    - Implement export_settings() returning JSON of all plugin options
    - Implement export_redirects() returning CSV of all redirects
    - Add download buttons with proper file headers
    - _Requirements: 11.1, 11.2, 11.5_
  
  - [x] 14.2 Create import functionality
    - Implement import_settings() with JSON validation
    - Implement import_redirects() with CSV parsing
    - Add file upload fields with proper validation
    - Display error messages for invalid data without modifying existing settings
    - _Requirements: 11.3, 11.4, 11.6, 11.7_

- [x] 15. Implement database maintenance tools
  - Create clear_old_logs() method to delete entries older than 90 days
  - Create repair_tables() method to run REPAIR TABLE on plugin tables
  - Create flush_caches() method to delete all meowseo transients and object cache entries
  - Add confirmation dialogs before destructive operations
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7_

- [x] 16. Implement SEO data tools
  - [x] 16.1 Create bulk description generator
    - Implement bulk_generate_descriptions() processing 50 posts per batch
    - Generate descriptions from post excerpts or first 160 characters
    - Display progress indicator during operation
    - _Requirements: 13.1, 13.3, 13.5_
  
  - [x] 16.2 Create missing SEO data scanner
    - Implement scan_missing_seo_data() to identify posts missing title, description, or focus keyword
    - Display report showing posts with missing fields
    - _Requirements: 13.2, 13.4_
  
  - [ ]* 16.3 Write integration tests for tools operations
    - Test export/import cycle for settings and redirects
    - Test database maintenance operations
    - Test bulk operations with batch processing
    - _Requirements: 11.6, 12.4, 12.5, 12.6, 13.3, 13.4_

- [x] 17. Checkpoint - Verify tools functionality
  - Ensure import/export works correctly
  - Ensure database maintenance operations execute successfully
  - Ensure bulk operations process in batches
  - Ensure all tests pass, ask the user if questions arise

- [x] 18. Implement internal linking suggestion engine
  - [x] 18.1 Create Suggestion_Engine class (includes/admin/class-suggestion-engine.php)
    - Implement get_suggestions(content, post_id) as main entry point
    - Add caching with 10-minute TTL per post (meowseo_suggestions_{post_id})
    - _Requirements: 14.4, 14.5, 26.4_
  
  - [x] 18.2 Implement keyword extraction
    - Create extract_keywords() to tokenize content (limit to first 2,000 words)
    - Create filter_stopwords() to remove common words case-insensitively
    - Load stopwords from includes/data/stopwords-id.php and includes/data/stopwords-en.php
    - Return empty result if fewer than 3 keywords remain
    - _Requirements: 14.1, 14.7, 16.1, 16.2, 16.3, 26.3_
  
  - [x] 18.3 Create stopwords data files
    - Create includes/data/stopwords-en.php with English stopwords
    - Create includes/data/stopwords-id.php with Indonesian stopwords (yang, dan, di, ke, dari, untuk, pada, adalah, dengan, ini, itu, atau, juga, akan, telah, dapat, ada, tidak, dalam, oleh, sebagai, antara, karena, saat, setelah, sebelum, jika, maka, tetapi, namun, bahwa, saya, kami, kita, mereka, dia, ia, anda, kamu, nya, mu, ku)
    - _Requirements: 16.1, 16.4_
  
  - [x] 18.4 Implement post querying and scoring
    - Create query_relevant_posts() using database indexes on post_title and post_content
    - Create score_post() algorithm: title match +50, content match +10 (max 50 per keyword), meta description match +30
    - Exclude current post from results
    - Only include published posts
    - Return top 10 results ordered by score descending
    - _Requirements: 14.2, 14.3, 14.4, 14.5, 14.6, 26.2_
  
  - [ ]* 18.5 Write unit tests for suggestion engine
    - Test stopword filtering for English and Indonesian
    - Test keyword extraction from content
    - Test scoring algorithm with various keyword matches
    - Test empty result when fewer than 3 keywords
    - _Requirements: 14.1, 14.7, 16.2, 16.3, 16.4_

- [x] 19. Implement suggestion REST endpoint with rate limiting
  - [x] 19.1 Register suggestion endpoint
    - Add POST /meowseo/v1/internal-links/suggest endpoint
    - Require edit_posts capability
    - Accept content (string) and post_id (integer) parameters
    - _Requirements: 15.1, 15.2, 15.3_
  
  - [x] 19.2 Implement rate limiting
    - Create check_rate_limit() using transient meowseo_suggest_ratelimit_{user_id} with 2-second TTL
    - Return HTTP 429 with Retry-After: 2 header when limit exceeded
    - _Requirements: 15.4, 15.5_
  
  - [x] 19.3 Format suggestion response
    - Return JSON array with post_id, title, url, score fields
    - _Requirements: 15.6_
  
  - [ ]* 19.4 Write integration tests for suggestion endpoint
    - Test rate limiting enforcement (1 request per 2 seconds)
    - Test capability checks (edit_posts required)
    - Test suggestion caching
    - Test response format
    - _Requirements: 15.4, 15.5, 26.1, 26.4, 26.5_

- [x] 20. Checkpoint - Verify suggestion engine functionality
  - Ensure suggestions return within 1 second for 5,000-word posts
  - Ensure rate limiting works correctly
  - Ensure caching reduces database queries
  - Ensure all tests pass, ask the user if questions arise

- [x] 21. Implement public SEO REST endpoints
  - [x] 21.1 Register public SEO endpoints
    - Add GET /meowseo/v1/seo/post/{id} returning all SEO data
    - Add GET /meowseo/v1/seo?url={url} returning SEO data by URL
    - Add GET /meowseo/v1/schema/post/{id} returning schema @graph array
    - Add GET /meowseo/v1/breadcrumbs?url={url} returning breadcrumb trail
    - Add GET /meowseo/v1/redirects/check?url={url} checking for redirects
    - Allow public access for published posts
    - Return HTTP 404 for unpublished or non-existent posts
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7_
  
  - [x] 21.2 Implement SEO data response format
    - Return fields: title, description, robots, canonical, og_title, og_description, og_image, twitter_card, twitter_title, twitter_description, twitter_image, schema_json
    - Parse schema_json as JSON object (not string)
    - Return HTTP 200 for success, 400 for invalid parameters, 404 for non-existent resources
    - _Requirements: 18.1, 18.2, 18.4, 18.5, 18.6_
  
  - [x] 21.3 Add caching headers and ETag support
    - Include Cache-Control: public, max-age=300 for GET requests
    - Generate ETag from MD5 hash of response content
    - Support If-None-Match header and return HTTP 304 when unchanged
    - Include Vary: Accept header
    - Include Cache-Control: no-store for POST/PUT/DELETE
    - _Requirements: 18.3, 27.1, 27.2, 27.3, 27.4, 27.5_
  
  - [ ]* 21.4 Write integration tests for public SEO endpoints
    - Test public access to published posts
    - Test 404 for unpublished posts
    - Test response format consistency
    - Test ETag generation and 304 responses
    - Test cache headers
    - _Requirements: 17.6, 17.7, 18.3, 18.4, 27.2, 27.3_

- [x] 22. Implement WPGraphQL integration (conditional)
  - Register SEO fields in WPGraphQL schema when WPGraphQL is active
  - Add seo field to Post, Page, and custom post type nodes
  - Add seo field to Category, Tag, and custom taxonomy nodes
  - Provide GraphQL fields: title, description, robots, canonical, openGraph, twitterCard, schemaJson
  - Resolve SEO fields using same logic as REST endpoints
  - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5_

- [x] 23. Checkpoint - Verify public API functionality
  - Ensure all endpoints return correct data format
  - Ensure caching headers work correctly
  - Ensure ETag support returns 304 when appropriate
  - Ensure all tests pass, ask the user if questions arise

- [x] 24. Implement WooCommerce module infrastructure
  - [x] 24.1 Create WooCommerce module class (includes/modules/woocommerce/class-woocommerce.php)
    - Implement Module interface
    - Return 'woocommerce' as module ID
    - Add conditional loading (only when WooCommerce is active and module enabled)
    - Register hooks after WooCommerce initialization
    - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.5_
  
  - [x] 24.2 Create WooCommerce module README
    - Document module functionality and integration points
    - Add usage examples for Product schema and sitemaps
    - _Requirements: 20.1_

- [x] 25. Implement WooCommerce Product schema
  - [x] 25.1 Create generate_product_schema() method
    - Generate Product schema for single product pages
    - Include fields: name, description, image, sku, brand, offers, aggregateRating, review
    - Set offers.price to current product price
    - Set offers.priceCurrency from WooCommerce currency settings
    - Set offers.availability based on stock status (InStock, OutOfStock, PreOrder)
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5_
  
  - [x] 25.2 Add product reviews to schema
    - Include aggregateRating with ratingValue and reviewCount when reviews exist
    - Validate Product schema against schema.org specification
    - _Requirements: 21.6, 21.7_

- [x] 26. Implement WooCommerce sitemap integration
  - Create add_products_to_sitemap() method
  - Add product post type to sitemap generation
  - Respect "Exclude out-of-stock products" setting
  - Set product priority to 0.8, changefreq to weekly
  - Use product modified date for lastmod
  - _Requirements: 22.1, 22.2, 22.3, 22.4, 22.5, 22.6_

- [x] 27. Implement WooCommerce category and shop page handling
  - [x] 27.1 Create get_product_meta() method
    - Generate meta tags for product_cat taxonomy archives
    - Generate meta tags for shop page
    - Use category description as meta description if available
    - Generate description from "Products in [category name]" when empty
    - _Requirements: 23.1, 23.2, 23.3, 23.4_
  
  - [x] 27.2 Add product categories to breadcrumbs
    - Include product_cat taxonomy in breadcrumb generation
    - _Requirements: 23.5_

- [x] 28. Implement WooCommerce breadcrumbs
  - Create generate_product_breadcrumbs() method
  - Generate breadcrumbs including category hierarchy
  - Use primary category if product has multiple categories
  - Include Shop page before categories
  - Format as: Home > Shop > Category > Subcategory > Product
  - Generate BreadcrumbList schema for product pages
  - _Requirements: 24.1, 24.2, 24.3, 24.4, 24.5_

- [ ]* 29. Write integration tests for WooCommerce module
  - Test module loads only when WooCommerce is active
  - Test Product schema generation with all fields
  - Test sitemap inclusion with out-of-stock exclusion
  - Test category meta tag generation
  - Test breadcrumb generation with category hierarchy
  - _Requirements: 20.1, 20.2, 21.1, 22.1, 22.3, 23.1, 24.1_

- [x] 30. Checkpoint - Verify WooCommerce integration
  - Ensure Product schema validates against schema.org
  - Ensure products appear in sitemaps correctly
  - Ensure breadcrumbs include category hierarchy
  - Ensure all tests pass, ask the user if questions arise

- [x] 31. Implement security hardening
  - [x] 31.1 Add nonce verification to all forms
    - Verify nonce in Settings_Page for all form submissions
    - Verify nonce in Tools_Page for all tool actions
    - Verify nonce in Admin_Dashboard for all AJAX requests
    - Return HTTP 403 when nonce verification fails
    - Generate unique nonces for each admin page
    - _Requirements: 28.1, 28.2, 28.3, 28.4, 28.5_
  
  - [x] 31.2 Add capability checks to all endpoints
    - Verify manage_options for admin pages
    - Verify edit_posts for suggestion endpoint
    - Verify manage_options for dashboard widgets
    - Display "You do not have sufficient permissions" when checks fail
    - _Requirements: 29.1, 29.2, 29.3, 29.4, 29.5_
  
  - [x] 31.3 Implement input sanitization
    - Sanitize text fields with sanitize_text_field
    - Sanitize textarea fields with sanitize_textarea_field
    - Sanitize URL fields with esc_url_raw
    - Sanitize HTML fields with wp_kses_post
    - Use prepared statements for all database queries
    - Validate and sanitize all REST endpoint parameters
    - _Requirements: 30.1, 30.2, 30.3, 30.4, 30.5, 30.6_

- [x] 32. Implement accessibility features
  - Add ARIA labels for all interactive elements
  - Associate all form labels with input fields using for attribute
  - Ensure full keyboard navigation support
  - Add focus indicators for all focusable elements
  - Use semantic HTML elements (button, nav, main, aside)
  - Add alt text for all images
  - Maintain color contrast ratio of at least 4.5:1 for text
  - _Requirements: 31.1, 31.2, 31.3, 31.4, 31.5, 31.6, 31.7_

- [ ]* 33. Run accessibility audit
  - Use axe-core to test WCAG 2.1 AA compliance
  - Test keyboard navigation through all admin pages
  - Test screen reader compatibility
  - Fix any violations found
  - _Requirements: 31.1, 31.2, 31.3, 31.4, 31.5, 31.6, 31.7_

- [x] 34. Implement error handling and logging
  - [x] 34.1 Add user-friendly error messages
    - Return JSON responses with error message and code for REST failures
    - Log errors and display generic message for database failures
    - Display specific reasons for file upload failures
    - Display field-specific errors for validation failures
    - Never display raw PHP errors or stack traces
    - _Requirements: 32.1, 32.2, 32.3, 32.4, 32.5_
  
  - [x] 34.2 Implement admin action logging
    - Log settings saves with user ID and changed fields
    - Log redirect imports with count
    - Log database maintenance operations with type and result
    - Log bulk SEO operations with affected post count
    - Use Logger helper class with context data (user_id, timestamp, action)
    - _Requirements: 33.1, 33.2, 33.3, 33.4, 33.5, 33.6_

- [-] 35. Performance optimization and verification
  - [x] 35.1 Optimize dashboard load time
    - Ensure initial HTML render completes in <500ms
    - Verify zero direct database queries during page render
    - Confirm all data loading deferred to async REST calls
    - _Requirements: 25.1, 25.2, 25.3_
  
  - [x] 35.2 Verify caching effectiveness
    - Confirm widget data cached with 5-minute TTL
    - Confirm cached data returned without database queries
    - Confirm suggestion results cached for 10 minutes
    - _Requirements: 25.4, 25.5, 26.4, 26.5_
  
  - [x] 35.3 Optimize suggestion engine performance
    - Ensure results return within 1 second for 5,000-word posts
    - Verify database indexes on post_title and post_content
    - Confirm keyword extraction limited to first 2,000 words
    - _Requirements: 26.1, 26.2, 26.3_

- [ ]* 36. Run performance benchmarks
  - Benchmark dashboard load time with 10,000+ posts
  - Benchmark suggestion engine with 5,000-word content
  - Verify all performance requirements met
  - _Requirements: 25.1, 25.2, 26.1_

- [-] 37. Final integration and testing
  - [x] 37.1 Integration testing
    - Test complete workflow: dashboard load → widget population → settings save → tools operations
    - Test WooCommerce module with products, categories, and shop page
    - Test public API endpoints with various post types
    - Test error handling across all components
    - _Requirements: All_
  
  - [x] 37.2 Create admin interface documentation
    - Document all admin pages and their functionality
    - Document REST API endpoints with examples
    - Document WooCommerce integration features
    - Add troubleshooting guide for common issues
    - _Requirements: All_

- [x] 38. Final checkpoint - Complete feature verification
  - Ensure all admin pages load correctly and perform as expected
  - Ensure all REST endpoints return correct data with proper caching
  - Ensure WooCommerce integration works when WooCommerce is active
  - Ensure all security measures (nonce, capability, sanitization) are in place
  - Ensure accessibility compliance (WCAG 2.1 AA)
  - Ensure performance benchmarks met (dashboard <500ms, suggestions <1s)
  - Ensure all tests pass, ask the user if questions arise

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at logical breaks
- Unit tests validate specific logic (stopwords, scoring, validation)
- Integration tests validate component interactions (REST endpoints, caching, database operations)
- Accessibility audit ensures WCAG 2.1 AA compliance
- Performance benchmarks verify load time and response time requirements
- All admin pages require `manage_options` capability
- All forms use nonce verification for CSRF protection
- All input is sanitized to prevent XSS and SQL injection
- Widget data is cached for 5 minutes to reduce database load
- Suggestion endpoint is rate-limited to 1 request per 2 seconds per user
- WooCommerce module only loads when WooCommerce is active and enabled in settings
