# Requirements Document

## Introduction

This document specifies requirements for the Schema Generator and XML Sitemap System for the MeowSEO WordPress plugin. The system consists of three major components:

1. **Schema System**: JSON-LD structured data generator using @graph array approach for Google Knowledge Graph resolution
2. **XML Sitemap System**: High-performance sitemap generator with filesystem caching and lock pattern to prevent cache stampede
3. **Breadcrumbs System**: Semantic breadcrumb trail generator with Schema.org microdata

The system builds upon the existing Meta module and Gutenberg sidebar integration to provide comprehensive SEO structured data and sitemap functionality.

## Glossary

- **Schema_Builder**: Core schema engine that assembles JSON-LD @graph arrays from individual node builders
- **Schema_Module**: Module implementing Module_Interface that hooks schema output into wp_head and registers REST endpoints
- **Sitemap_Module**: Module that registers rewrite rules, intercepts sitemap requests, and manages cache invalidation
- **Sitemap_Cache**: Filesystem-based cache manager storing XML files in wp-content/uploads/meowseo-sitemaps/
- **Sitemap_Builder**: Generates sitemap XML content and routes through Sitemap_Cache with lock pattern
- **Sitemap_Ping**: Notifies search engines of sitemap updates with rate limiting
- **Breadcrumbs**: Generates semantic breadcrumb trails with Schema.org microdata
- **@graph**: JSON-LD array containing multiple schema nodes with consistent @id references
- **Cache_Stampede**: Performance problem where multiple processes simultaneously regenerate expired cache
- **Lock_Pattern**: Concurrency control using transient locks to prevent cache stampede
- **Stale_Cache**: Serving outdated cache during regeneration to maintain performance

## Requirements

### Requirement 1: Schema Builder Core Engine

**User Story:** As a developer, I want a core schema engine that builds complete JSON-LD @graph arrays, so that Google can resolve entities in the Knowledge Graph.

#### Acceptance Criteria

1. THE Schema_Builder SHALL provide a build(post_id) method that returns a complete script tag with JSON-LD @graph array
2. THE Schema_Builder SHALL assemble @graph by collecting nodes from individual node builder methods
3. THE Schema_Builder SHALL always include WebSite, Organization, WebPage, and BreadcrumbList nodes in the @graph
4. WHEN the post type is "post" OR schema type is "Article", THE Schema_Builder SHALL include an Article node
5. WHEN the post type is "product" AND WooCommerce is active, THE Schema_Builder SHALL include a Product node
6. WHEN schema type is "FAQPage" AND FAQ items exist, THE Schema_Builder SHALL include a FAQPage node
7. THE Schema_Builder SHALL use consistent @id format (URL + #fragment) for all nodes to enable Knowledge Graph resolution
8. THE WebSite node SHALL include a SearchAction with urlTemplate and query-input properties
9. THE Organization node SHALL include logo ImageObject and sameAs array from social profiles
10. THE WebPage node SHALL vary by context (WebPage for pages, CollectionPage for archives, SearchResultsPage for search)
11. WHEN an Article node is present, THE Schema_Builder SHALL include a speakable property with cssSelector "#meowseo-direct-answer"
12. THE Schema_Builder SHALL read schema type from _meowseo_schema_type postmeta
13. THE Schema_Builder SHALL read schema configuration from _meowseo_schema_config postmeta

### Requirement 2: Schema Module Integration

**User Story:** As a WordPress site owner, I want schema markup automatically output in my page head, so that search engines can understand my content structure.

#### Acceptance Criteria

1. THE Schema_Module SHALL implement Module_Interface
2. THE Schema_Module SHALL hook schema output into wp_head at priority 5
3. THE Schema_Module SHALL output a single script tag with type "application/ld+json"
4. THE Schema_Module SHALL register REST endpoint GET /meowseo/v1/schema/post/{id} for headless frontends
5. THE Schema_Module SHALL return JSON response with post_id and schema_jsonld fields
6. THE Schema_Module SHALL cache generated schema JSON for 1 hour to eliminate repeated DB queries
7. WHEN a post is saved, THE Schema_Module SHALL invalidate the schema cache for that post

### Requirement 3: Sitemap Module Rewrite Rules

**User Story:** As a WordPress site owner, I want clean sitemap URLs, so that search engines can easily discover my content.

#### Acceptance Criteria

1. THE Sitemap_Module SHALL register rewrite rule for /sitemap.xml mapping to sitemap index
2. THE Sitemap_Module SHALL register rewrite rule for /sitemap-posts.xml mapping to posts sitemap
3. THE Sitemap_Module SHALL register rewrite rule for /sitemap-pages.xml mapping to pages sitemap
4. THE Sitemap_Module SHALL register rewrite rule for /sitemap-{post_type}.xml mapping to custom post type sitemaps
5. THE Sitemap_Module SHALL register rewrite rule for /sitemap-news.xml mapping to Google News sitemap
6. THE Sitemap_Module SHALL register rewrite rule for /sitemap-video.xml mapping to Google Video sitemap
7. THE Sitemap_Module SHALL intercept sitemap requests via template_redirect hook
8. THE Sitemap_Module SHALL serve XML with Content-Type "application/xml; charset=utf-8"
9. THE Sitemap_Module SHALL flush rewrite rules on module initialization if needed

### Requirement 4: Sitemap Cache Management

**User Story:** As a developer, I want filesystem-based sitemap caching, so that sitemap requests don't trigger expensive database queries.

#### Acceptance Criteria

1. THE Sitemap_Cache SHALL store XML files in wp-content/uploads/meowseo-sitemaps/ directory
2. THE Sitemap_Cache SHALL store file paths only in Object Cache, not XML content
3. THE Sitemap_Cache SHALL provide get(name) method returning cached XML content
4. THE Sitemap_Cache SHALL provide set(name, xml_content) method writing XML to filesystem
5. THE Sitemap_Cache SHALL provide invalidate(name) method deleting specific sitemap file
6. THE Sitemap_Cache SHALL provide invalidate_all() method deleting all sitemap files
7. THE Sitemap_Cache SHALL provide get_or_generate(name, generator_callable) method with lock pattern
8. WHEN a lock cannot be acquired, THE Sitemap_Cache SHALL serve stale files during regeneration
9. WHEN no stale file exists AND lock cannot be acquired, THE Sitemap_Cache SHALL return HTTP 503 with Retry-After header

### Requirement 5: Sitemap Builder Generation

**User Story:** As a WordPress site owner, I want automatically generated sitemaps for all my content, so that search engines can discover and index my pages.

#### Acceptance Criteria

1. THE Sitemap_Builder SHALL route all generation through Sitemap_Cache get_or_generate method
2. THE Sitemap_Builder SHALL provide build_index() method generating sitemap index listing all sub-sitemaps
3. THE Sitemap_Builder SHALL provide build_posts(post_type, page) method generating sitemap for specific post type
4. THE Sitemap_Builder SHALL exclude posts with _meowseo_noindex postmeta set to true
5. THE Sitemap_Builder SHALL paginate sitemaps at 1000 URLs per file
6. THE Sitemap_Builder SHALL call update_post_meta_cache() before loops for performance optimization
7. THE Sitemap_Builder SHALL provide build_news() method for Google News Sitemap with posts from last 48 hours
8. THE Sitemap_Builder SHALL provide build_video() method for Google Video Sitemap scanning YouTube and Vimeo embeds
9. THE Sitemap_Builder SHALL use oEmbed API to detect video embeds in post content
10. THE Sitemap_Builder SHALL include lastmod timestamp in ISO 8601 format for all URLs

### Requirement 6: Sitemap Cache Invalidation

**User Story:** As a content editor, I want sitemaps to update when I publish content, so that search engines discover my new pages quickly.

#### Acceptance Criteria

1. WHEN a post is saved, THE Sitemap_Module SHALL invalidate cache for that post type's sitemap
2. WHEN a post is deleted, THE Sitemap_Module SHALL invalidate cache for that post type's sitemap
3. WHEN a term is created, THE Sitemap_Module SHALL invalidate cache for affected post type sitemaps
4. WHEN a term is edited, THE Sitemap_Module SHALL invalidate cache for affected post type sitemaps
5. THE Sitemap_Module SHALL schedule WP-Cron event meowseo_regenerate_sitemaps to run daily
6. THE WP-Cron event SHALL pre-generate all sitemaps to ensure fresh cache
7. WHEN cache is invalidated, THE Sitemap_Module SHALL serve stale files until regeneration completes

### Requirement 7: Sitemap Ping Notifications

**User Story:** As a WordPress site owner, I want search engines notified when my sitemap updates, so that my content is indexed faster.

#### Acceptance Criteria

1. THE Sitemap_Ping SHALL provide ping(sitemap_url) method notifying Google and Bing
2. THE Sitemap_Ping SHALL hook into daily regeneration cron event
3. THE Sitemap_Ping SHALL hook into new post publication event
4. WHEN a ping was sent less than 1 hour ago, THE Sitemap_Ping SHALL skip the ping (rate limiting)
5. THE Sitemap_Ping SHALL store last ping timestamp in wp_options table
6. THE Sitemap_Ping SHALL use wp_remote_get() for HTTP requests to search engine ping endpoints

### Requirement 8: Breadcrumbs Trail Generation

**User Story:** As a theme developer, I want a breadcrumb trail generator, so that I can display navigation paths to users.

#### Acceptance Criteria

1. THE Breadcrumbs SHALL provide get_trail() method returning array of items with label and URL
2. THE Breadcrumbs SHALL generate correct trails for single posts with Home → Category → Post
3. THE Breadcrumbs SHALL generate correct trails for hierarchical pages with Home → Parent → Child
4. THE Breadcrumbs SHALL generate correct trails for archives with Home → Archive
5. THE Breadcrumbs SHALL generate correct trails for search results with Home → Search Results
6. THE Breadcrumbs SHALL generate correct trails for 404 pages with Home → Page Not Found
7. THE Breadcrumbs SHALL provide render() method outputting semantic HTML with Schema.org microdata
8. THE Breadcrumbs SHALL register shortcode [meowseo_breadcrumbs] for use in content
9. THE Breadcrumbs SHALL provide template function meowseo_breadcrumbs() for use in theme files
10. THE Schema_Builder SHALL call Breadcrumbs get_trail() method for BreadcrumbList node generation

### Requirement 9: Schema Type Configuration UI

**User Story:** As a content editor, I want to configure schema types per post, so that I can control how my content appears in search results.

#### Acceptance Criteria

1. THE Gutenberg sidebar SHALL display schema type selector with options: Article, WebPage, FAQPage, HowTo, LocalBusiness, Product
2. WHEN schema type is FAQPage, THE Gutenberg sidebar SHALL display FAQ item editor with question and answer fields
3. WHEN schema type is HowTo, THE Gutenberg sidebar SHALL display step editor with name, text, and image fields
4. WHEN schema type is LocalBusiness, THE Gutenberg sidebar SHALL display business information fields
5. THE Gutenberg sidebar SHALL save schema type to _meowseo_schema_type postmeta
6. THE Gutenberg sidebar SHALL save schema configuration to _meowseo_schema_config postmeta as JSON
7. THE Schema_Builder SHALL read schema configuration and generate appropriate nodes

### Requirement 10: Multiple Schema Types Support

**User Story:** As a content creator, I want to combine multiple schema types on one page, so that I can provide rich structured data for complex content.

#### Acceptance Criteria

1. WHEN schema type is FAQPage, THE Schema_Builder SHALL include both Article and FAQPage nodes in @graph
2. WHEN schema type is HowTo, THE Schema_Builder SHALL include both Article and HowTo nodes in @graph
3. THE Schema_Builder SHALL maintain consistent @id references between related nodes
4. THE Article node SHALL reference FAQPage or HowTo node via mainEntity property when applicable
5. THE Schema_Builder SHALL validate that all required properties are present before including a node

### Requirement 11: WooCommerce Product Schema

**User Story:** As a WooCommerce store owner, I want product schema automatically generated, so that my products appear in Google Shopping results.

#### Acceptance Criteria

1. WHEN post type is "product" AND WooCommerce is active, THE Schema_Builder SHALL include Product node
2. THE Product node SHALL include name, url, description, sku, image, and offers properties
3. THE offers property SHALL include @type Offer, url, priceCurrency, price, and availability
4. WHEN product has reviews, THE Product node SHALL include aggregateRating with ratingValue and reviewCount
5. THE Product node SHALL use consistent @id format (permalink + #product)
6. THE Schema_Builder SHALL handle variable products by using the base product price

### Requirement 12: Performance Optimization

**User Story:** As a site administrator, I want sitemap generation to be performant, so that my site remains fast under high traffic.

#### Acceptance Criteria

1. THE Sitemap_Builder SHALL use direct database queries with LEFT JOIN to exclude noindex posts in single query
2. THE Sitemap_Builder SHALL call update_post_meta_cache() before loops to batch-load postmeta
3. THE Sitemap_Builder SHALL limit queries to 50,000 posts per sitemap to prevent memory exhaustion
4. THE Sitemap_Cache SHALL implement lock pattern using wp_cache_add() to prevent cache stampede
5. THE Sitemap_Cache SHALL serve stale files during regeneration to maintain performance
6. THE Schema_Module SHALL cache generated JSON-LD for 1 hour to eliminate repeated generation
7. THE Sitemap_Module SHALL serve files directly from filesystem using readfile() to bypass WordPress template loading

### Requirement 13: Error Handling and Logging

**User Story:** As a site administrator, I want comprehensive error logging, so that I can diagnose sitemap and schema issues.

#### Acceptance Criteria

1. WHEN sitemap generation fails, THE Sitemap_Module SHALL log error with post_type and error message
2. WHEN sitemap file write fails, THE Sitemap_Cache SHALL log error with file_path and error message
3. WHEN schema generation fails, THE Schema_Module SHALL log error with post_id and error message
4. WHEN directory creation fails, THE Sitemap_Cache SHALL log error with directory path and error message
5. THE error logs SHALL be accessible via the MeowSEO admin log viewer
6. THE error logs SHALL include timestamp, severity level, and context data

### Requirement 14: REST API and Headless Support

**User Story:** As a headless WordPress developer, I want REST API endpoints for schema and sitemaps, so that I can use MeowSEO with decoupled frontends.

#### Acceptance Criteria

1. THE Schema_Module SHALL register GET /meowseo/v1/schema/post/{id} endpoint
2. THE REST endpoint SHALL return JSON with post_id and schema_jsonld fields
3. THE REST endpoint SHALL require post to be publicly viewable for access
4. THE REST endpoint SHALL include Cache-Control header with max-age=300 for CDN caching
5. THE Sitemap_Module SHALL serve XML files with X-Robots-Tag: noindex, follow header
6. THE Schema_Module SHALL register WPGraphQL field schemaJsonLd when WPGraphQL is active

### Requirement 15: Sitemap Image and Video Extensions

**User Story:** As a content creator, I want my images and videos included in sitemaps, so that they appear in Google Image and Video search.

#### Acceptance Criteria

1. WHEN a post has a featured image, THE Sitemap_Builder SHALL include image:image element with image:loc
2. THE Sitemap_Builder SHALL scan post content for YouTube embeds using regex pattern
3. THE Sitemap_Builder SHALL scan post content for Vimeo embeds using regex pattern
4. WHEN video embeds are found, THE Sitemap_Builder SHALL include video:video element with video:content_loc
5. THE Sitemap_Builder SHALL use oEmbed API to extract video metadata (title, description, thumbnail)
6. THE video sitemap SHALL include video:title, video:description, video:thumbnail_loc, and video:content_loc
7. THE Sitemap_Builder SHALL limit video sitemap to posts with at least one video embed

### Requirement 16: Google News Sitemap

**User Story:** As a news publisher, I want a Google News sitemap, so that my articles appear in Google News search results.

#### Acceptance Criteria

1. THE Sitemap_Builder SHALL generate news sitemap including only posts from last 48 hours
2. THE news sitemap SHALL use news:news element with news:publication and news:publication_date
3. THE news:publication element SHALL include news:name and news:language
4. THE news sitemap SHALL include news:title for each article
5. THE Sitemap_Builder SHALL exclude posts with _meowseo_noindex postmeta from news sitemap
6. THE news sitemap SHALL be accessible at /sitemap-news.xml

### Requirement 17: Schema Validation and Testing

**User Story:** As a developer, I want schema validation, so that I can ensure my structured data is correct.

#### Acceptance Criteria

1. THE Schema_Builder SHALL validate that all required properties are present before outputting a node
2. WHEN a required property is missing, THE Schema_Builder SHALL log a warning and skip that node
3. THE Schema_Builder SHALL ensure all @id values are valid URLs
4. THE Schema_Builder SHALL ensure all date properties use ISO 8601 format
5. THE Schema_Builder SHALL provide a test mode that outputs validation errors as HTML comments
6. THE test mode SHALL be enabled via WP_DEBUG constant

### Requirement 18: Breadcrumbs Customization

**User Story:** As a theme developer, I want to customize breadcrumb output, so that I can match my site's design.

#### Acceptance Criteria

1. THE Breadcrumbs render() method SHALL accept optional CSS class parameter
2. THE Breadcrumbs render() method SHALL accept optional separator parameter (default: " › ")
3. THE Breadcrumbs SHALL provide filter hook meowseo_breadcrumb_trail for modifying trail array
4. THE Breadcrumbs SHALL provide filter hook meowseo_breadcrumb_html for modifying output HTML
5. THE Breadcrumbs SHALL use semantic HTML5 nav element with aria-label="Breadcrumb"
6. THE Breadcrumbs SHALL include Schema.org microdata using itemscope and itemprop attributes

### Requirement 19: Sitemap Exclusion Rules

**User Story:** As a site administrator, I want to exclude specific content from sitemaps, so that I can control what search engines index.

#### Acceptance Criteria

1. THE Sitemap_Builder SHALL exclude posts with _meowseo_noindex postmeta set to "1"
2. THE Sitemap_Builder SHALL exclude posts with post_status other than "publish"
3. THE Sitemap_Builder SHALL exclude attachment post type from sitemap index
4. THE Sitemap_Builder SHALL provide filter hook meowseo_sitemap_exclude_post for custom exclusion logic
5. THE Sitemap_Builder SHALL provide filter hook meowseo_sitemap_post_types for modifying included post types
6. WHEN a post type has no published posts, THE Sitemap_Builder SHALL exclude it from sitemap index

### Requirement 20: Schema Speakable Property

**User Story:** As a content creator, I want speakable markup for voice assistants, so that my content works with Google Assistant and Alexa.

#### Acceptance Criteria

1. WHEN an Article node is present, THE Schema_Builder SHALL include speakable property
2. THE speakable property SHALL use cssSelector type with value "#meowseo-direct-answer"
3. THE content editor SHALL be able to designate a content block as the direct answer
4. THE Gutenberg sidebar SHALL provide a toggle to mark a block as speakable content
5. WHEN speakable content is marked, THE Schema_Builder SHALL add id="meowseo-direct-answer" to that block
6. THE speakable property SHALL follow Google's speakable specification

