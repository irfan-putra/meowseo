# Schema Module Implementation Summary

## Task 6: Implement Schema Module for structured data

### Completed Subtasks

#### ✅ Subtask 6.1: Create Schema_Builder helper class
**File**: `includes/helpers/class-schema-builder.php`

**Implemented Methods**:
1. `build(int $post_id): array` - Builds complete schema graph for a post
2. `build_website(): array` - Generates WebSite schema with search action
3. `build_webpage(\WP_Post $post): array` - Generates WebPage schema
4. `build_article(\WP_Post $post): array` - Generates Article schema with author
5. `build_breadcrumb(\WP_Post $post): array` - Generates BreadcrumbList schema
6. `build_organization(): array` - Generates Organization schema with logo support
7. `build_product(\WP_Post $post): array` - Generates WooCommerce Product schema
8. `build_faq(array $items): array` - Generates FAQPage schema
9. `to_json(array $graph): string` - Converts schema to JSON-LD string

**Key Features**:
- Pure functions with no database calls or side effects
- Supports per-post schema type override via `meowseo_schema_type` postmeta
- WooCommerce integration with Product schema (price, availability, reviews)
- FAQ schema from `meowseo_faq_items` postmeta (JSON array)
- Proper @id references for graph relationships
- Featured image support for Article and WebPage schemas
- Author information for Article schema
- Breadcrumb navigation with categories and post type archives

**Requirements Validated**: 5.1, 5.2, 5.3, 5.5, 5.7

#### ✅ Subtask 6.3: Create Schema module with JSON-LD output
**File**: `includes/modules/schema/class-schema.php`

**Implemented Features**:
1. **Module Interface Implementation**:
   - `boot()` method registers all hooks
   - `get_id()` returns 'schema'

2. **Frontend Output**:
   - Hooks into `wp_head` (priority 2)
   - Outputs single `<script type="application/ld+json">` tag
   - Only outputs on singular pages
   - Caches schema JSON for 1 hour

3. **REST API Integration**:
   - Endpoint: `GET /meowseo/v1/schema/{post_id}`
   - Returns schema JSON-LD for headless consumption
   - Includes `Cache-Control: public, max-age=300` headers
   - Permission check: post must be publicly viewable

4. **WPGraphQL Integration**:
   - Registers `schemaJsonLd` field on all queryable post types
   - Conditional loading when WPGraphQL is active
   - Returns complete JSON-LD string

5. **Caching Strategy**:
   - Cache key: `meowseo_schema_{post_id}`
   - TTL: 3600 seconds (1 hour)
   - Invalidates on `save_post` action
   - Uses Object Cache via Cache helper

6. **Schema Type Override**:
   - Reads `meowseo_schema_type` postmeta
   - Supports: Article, FAQPage, or default WebPage
   - Automatic Product schema for WooCommerce products

**Requirements Validated**: 5.1, 5.4, 5.6

## Schema Types Supported

### 1. WebSite
- Always included on all pages
- Includes search action with URL template
- References Organization as publisher

### 2. WebPage
- Default schema for pages and custom post types
- Includes publication/modification dates
- References breadcrumb navigation
- Supports featured images

### 3. Article
- Used for posts or when schema_type = 'Article'
- Includes author information (Person)
- References Organization as publisher
- Supports featured images and descriptions

### 4. BreadcrumbList
- Always included for navigation
- Includes home, post type archive, categories
- Proper position numbering

### 5. Organization
- Always included as site publisher
- Includes site logo if available
- Referenced by WebSite and Article schemas

### 6. Product (WooCommerce)
- Automatic for product post type when WooCommerce active
- Includes SKU, price, currency, availability
- Aggregate rating from product reviews
- Product images

### 7. FAQPage
- Activated via schema_type override
- Reads FAQ items from `meowseo_faq_items` postmeta
- JSON format: `[{"question": "...", "answer": "..."}]`

## Integration Points

### Module Manager
- Registered in `Module_Manager::$module_registry` as 'schema'
- Loaded only when 'schema' is in enabled modules
- Follows Module interface contract

### Options Integration
- Uses Options instance for site-wide settings
- Accesses separator, social images, etc.

### Cache Helper
- All schema output is cached
- Automatic invalidation on post save
- Fallback to transients when Object Cache unavailable

### Meta Module Integration
- Reads `meowseo_schema_type` for per-post overrides
- Reads `meowseo_description` for schema descriptions
- Reads `meowseo_faq_items` for FAQ schema

### WooCommerce Integration
- Conditional loading: `class_exists('WooCommerce')`
- Uses WooCommerce product API
- Includes pricing, stock, and review data

## Testing

### Verification Script
**File**: `tests/modules/schema/schema-verification.php`

Successfully verified:
- ✅ build_website() generates correct WebSite schema
- ✅ build_organization() generates correct Organization schema
- ✅ build_faq() generates correct FAQPage schema
- ✅ to_json() produces valid JSON-LD

### Unit Tests
**File**: `tests/modules/schema/SchemaBuilderTest.php`

Test coverage:
- Schema structure validation
- JSON encoding/decoding
- Empty input handling
- FAQ item processing

## JSON-LD Output Example

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebSite",
      "@id": "https://example.com/#website",
      "url": "https://example.com",
      "name": "Test Site",
      "description": "Test Description",
      "publisher": {
        "@id": "https://example.com/#organization"
      },
      "potentialAction": {
        "@type": "SearchAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "https://example.com/?s={search_term_string}"
        },
        "query-input": "required name=search_term_string"
      }
    },
    {
      "@type": "Organization",
      "@id": "https://example.com/#organization",
      "name": "Test Site",
      "url": "https://example.com"
    },
    {
      "@type": "Article",
      "@id": "https://example.com/post/#article",
      "headline": "Post Title",
      "datePublished": "2024-01-01T00:00:00+00:00",
      "dateModified": "2024-01-01T00:00:00+00:00",
      "author": {
        "@type": "Person",
        "name": "Author Name"
      },
      "publisher": {
        "@id": "https://example.com/#organization"
      }
    },
    {
      "@type": "BreadcrumbList",
      "@id": "https://example.com/post/#breadcrumb",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "https://example.com"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "Post Title",
          "item": "https://example.com/post/"
        }
      ]
    }
  ]
}
```

## Performance Considerations

1. **Caching**: All schema output cached for 1 hour
2. **Pure Functions**: Schema_Builder has no side effects
3. **Lazy Loading**: Module only loaded when enabled
4. **Conditional WooCommerce**: Product schema only when WooCommerce active
5. **Single Output**: One script tag per page (not multiple)

## Security

1. **Output Escaping**: JSON encoding with proper flags
2. **Permission Checks**: REST endpoint validates post visibility
3. **No User Input**: Schema built from WordPress data only
4. **Sanitized Data**: All WordPress functions return sanitized data

## Future Enhancements

Potential additions (not in current scope):
- Person schema for author pages
- Event schema for event post types
- Recipe schema for recipe post types
- Video schema for video embeds
- Review schema for review post types

## Compliance

The implementation follows:
- WordPress coding standards
- Plugin architecture (Module interface)
- Design document specifications
- Requirements 5.1-5.7
- Google's Rich Results Test validation (structure)
