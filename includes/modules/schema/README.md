# Schema Module

The Schema Module provides automatic JSON-LD structured data generation for rich search results in Google, Bing, and other search engines.

## Features

- **Automatic Schema Generation**: Generates valid Schema.org JSON-LD markup
- **@graph Array Approach**: Uses Google's recommended @graph format for Knowledge Graph resolution
- **Multiple Schema Types**: Supports Article, WebPage, FAQPage, Product, Organization, WebSite, and more
- **Breadcrumbs Integration**: Automatic BreadcrumbList schema generation
- **WooCommerce Support**: Product schema with price, availability, and reviews
- **Caching**: 1-hour Object Cache for performance
- **REST API**: Headless WordPress support via REST endpoints
- **WPGraphQL**: GraphQL integration when WPGraphQL is active

## Architecture

### Core Components

1. **Schema_Module**: Module entry point, hooks into WordPress
2. **Schema_Builder**: Core engine that assembles @graph arrays
3. **Abstract_Schema_Node**: Base class for all schema node builders
4. **Node Builders**: Individual classes for each schema type

### Schema Node Builders

Located in `includes/helpers/schema-nodes/`:

- `WebSite_Node`: Site-level schema with SearchAction
- `Organization_Node`: Organization/publisher information
- `WebPage_Node`: Page-level schema (varies by context)
- `Article_Node`: Article schema for blog posts
- `Product_Node`: WooCommerce product schema
- `FAQ_Node`: FAQPage schema for FAQ content
- `Breadcrumb_Node`: BreadcrumbList schema

## Usage

### Automatic Output

Schema is automatically output in `wp_head` for all public posts and pages. No configuration required.

### Gutenberg Integration

Configure schema type per post in the Gutenberg sidebar:

1. Open post in block editor
2. Find "MeowSEO" panel in sidebar
3. Select schema type (Article, WebPage, FAQPage, etc.)
4. Configure type-specific fields (FAQ items, HowTo steps, etc.)

### Template Function

Display breadcrumbs in theme templates:

```php
<?php
if ( function_exists( 'meowseo_breadcrumbs' ) ) {
    meowseo_breadcrumbs( 'my-breadcrumbs', ' > ' );
}
?>
```

### Shortcode

Display breadcrumbs in content:

```
[meowseo_breadcrumbs]
```

## REST API

### Get Schema for Post

```
GET /wp-json/meowseo/v1/schema/post/{id}
```

**Response:**
```json
{
  "post_id": 123,
  "schema_jsonld": {
    "@context": "https://schema.org",
    "@graph": [...]
  }
}
```

## WPGraphQL

Query schema via GraphQL:

```graphql
query GetPost {
  post(id: "1", idType: DATABASE_ID) {
    title
    schemaJsonLd
  }
}
```

## Schema Types

### Article

Automatically included for blog posts. Includes:
- Headline, author, publisher
- Publication and modification dates
- Word count, comment count
- Categories and tags
- Speakable content for voice assistants

### WebPage

Base schema for all pages. Varies by context:
- `WebPage`: Standard pages
- `CollectionPage`: Archive pages
- `SearchResultsPage`: Search results

### FAQPage

For FAQ content. Configure in Gutenberg sidebar:
- Add question/answer pairs
- Automatically generates FAQPage schema
- Coexists with Article schema

### Product

For WooCommerce products. Includes:
- Name, SKU, description
- Price, currency, availability
- Product images
- Aggregate ratings and reviews

### Organization

Site-level organization information:
- Organization name and logo
- Social media profiles
- Contact information

### WebSite

Site-level schema with SearchAction:
- Site name and description
- Search functionality
- Language information

## Configuration

### Postmeta Fields

**Schema Type:**
```
_meowseo_schema_type: string
Values: 'Article', 'WebPage', 'FAQPage', 'HowTo', 'LocalBusiness', 'Product'
```

**Schema Configuration:**
```
_meowseo_schema_config: JSON string
Structure varies by schema type
```

**Example FAQPage:**
```json
{
  "faq_items": [
    {
      "question": "What is the question?",
      "answer": "This is the answer."
    }
  ]
}
```

### Global Settings

Configure in **MeowSEO > Settings > Schema**:

- Organization name
- Organization logo
- Social media profiles (Facebook, Twitter, LinkedIn, etc.)

## Caching

Schema JSON-LD is cached for 1 hour in Object Cache:

**Cache Key:** `meowseo_schema_{post_id}`
**TTL:** 3600 seconds

**Invalidation Triggers:**
- Post save/update
- Schema type change
- Schema configuration change
- Global settings change

## Filter Hooks

### meowseo_schema_graph

Modify the complete @graph array:

```php
add_filter( 'meowseo_schema_graph', function( $graph, $post_id ) {
    // Add custom node
    $graph[] = [
        '@type' => 'CustomType',
        '@id' => get_permalink( $post_id ) . '#custom',
        'name' => 'Custom Node',
    ];
    return $graph;
}, 10, 2 );
```

### meowseo_schema_node_{type}

Modify individual schema nodes:

```php
add_filter( 'meowseo_schema_node_article', function( $node, $post_id ) {
    // Modify article node
    $node['customProperty'] = 'custom value';
    return $node;
}, 10, 2 );
```

Available filters:
- `meowseo_schema_node_website`
- `meowseo_schema_node_organization`
- `meowseo_schema_node_webpage`
- `meowseo_schema_node_article`
- `meowseo_schema_node_product`
- `meowseo_schema_node_faq`
- `meowseo_schema_node_breadcrumb`

### meowseo_schema_type

Override schema type detection:

```php
add_filter( 'meowseo_schema_type', function( $type, $post_id ) {
    if ( has_tag( 'faq', $post_id ) ) {
        return 'FAQPage';
    }
    return $type;
}, 10, 2 );
```

### meowseo_schema_social_profiles

Modify social media profiles:

```php
add_filter( 'meowseo_schema_social_profiles', function( $profiles ) {
    $profiles[] = 'https://instagram.com/yourprofile';
    return $profiles;
} );
```

## Action Hooks

### meowseo_before_schema_output

Fires before schema output:

```php
add_action( 'meowseo_before_schema_output', function( $post_id ) {
    // Do something before schema output
} );
```

### meowseo_after_schema_output

Fires after schema output:

```php
add_action( 'meowseo_after_schema_output', function( $post_id ) {
    // Do something after schema output
} );
```

### meowseo_schema_cache_invalidated

Fires when schema cache is invalidated:

```php
add_action( 'meowseo_schema_cache_invalidated', function( $post_id ) {
    // Do something when cache is cleared
} );
```

## Validation

### Google Rich Results Test

Test your schema markup:
https://search.google.com/test/rich-results

### Schema.org Validator

Validate against Schema.org specification:
https://validator.schema.org/

## Troubleshooting

### Schema Not Appearing

1. Check that Schema module is enabled in **MeowSEO > Settings**
2. Verify post is published (not draft)
3. Check for JavaScript errors in browser console
4. Clear Object Cache

### Invalid Schema

1. Test with Google Rich Results Test
2. Check for missing required properties
3. Verify @id format is correct (URL + #fragment)
4. Enable WP_DEBUG to see validation errors

### Cache Issues

Clear schema cache:

```bash
wp meowseo schema clear-cache
```

Or clear for specific post:

```bash
wp meowseo schema clear-cache --post_id=123
```

## Performance

### Benchmarks

- Schema generation: < 5ms per post
- Cache hit: < 1ms
- Memory usage: < 1MB per request

### Optimization Tips

1. **Enable Object Cache**: Use Redis or Memcached
2. **Use CDN**: Cache REST API responses
3. **Minimize Custom Nodes**: Each custom node adds processing time

## Requirements

- PHP 7.4+
- WordPress 6.0+
- Optional: WooCommerce (for Product schema)
- Optional: WPGraphQL (for GraphQL support)

## Related Documentation

- [Design Document](../../../.kiro/specs/schema-sitemap-system/design.md)
- [Requirements Document](../../../.kiro/specs/schema-sitemap-system/requirements.md)
- [API Documentation](../../../API_DOCUMENTATION.md)
