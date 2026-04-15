# Meta Module

## Overview

The Meta Module is the core component of MeowSEO responsible for managing and outputting all SEO meta tags on the frontend. It implements a sophisticated, maintainable architecture inspired by Yoast's Presenter pattern and RankMath's title pattern system, with proper separation of concerns across 7 specialized classes.

The module handles:
- **Title tags** with pattern-based generation and fallback chains
- **Meta descriptions** with intelligent excerpt/content fallbacks
- **Robots directives** with automatic rules and custom overrides
- **Canonical URLs** with pagination stripping
- **Open Graph tags** for social media sharing
- **Twitter Card tags** with independent configuration
- **Hreflang alternates** for multilingual sites
- **Virtual robots.txt** management via filter

## Architecture

### Component Overview

The Meta Module consists of 7 specialized classes, each with a single responsibility:

```
Meta_Module (Entry Point)
├── Meta_Output (Tag Output)
│   └── Meta_Resolver (Fallback Chains)
│       └── Title_Patterns (Pattern System)
├── Meta_Postmeta (Field Registration)
├── Global_SEO (Non-Singular Pages)
└── Robots_Txt (Virtual robots.txt)
```

### Data Flow

When WordPress calls `wp_head`, the following sequence occurs:

1. **Meta_Module** receives the hook and delegates to **Meta_Output**
2. **Meta_Output** calls **Meta_Resolver** for each meta field value
3. **Meta_Resolver** follows fallback chains to find the best value
4. For title fields, **Meta_Resolver** delegates to **Title_Patterns** for variable substitution
5. **Meta_Output** formats and escapes all values, then outputs tags in the correct order
6. For non-singular pages, **Global_SEO** provides context-specific values

## Classes and Responsibilities

### 1. Meta_Module

**File**: `class-meta-module.php`

**Responsibility**: Module entry point and hook coordination

**Key Methods**:
- `boot()` - Registers all WordPress hooks
- `get_id()` - Returns module identifier ('meta')

**Hooks Registered**:
- `wp_head` (priority 1) - Outputs all meta tags
- `document_title_parts` (priority 10) - Controls title tag generation
- `save_post` (priority 10) - Handles classic editor meta saves
- `rest_api_init` (priority 10) - Registers postmeta fields
- `enqueue_block_editor_assets` (priority 10) - Loads Gutenberg sidebar

**Key Features**:
- Removes WordPress's default title tag via `remove_theme_support('title-tag')`
- Instantiates and wires all 6 other classes via dependency injection
- Ensures priority 1 on wp_head to output before other plugins

### 2. Meta_Output

**File**: `class-meta-output.php`

**Responsibility**: Output all meta tags in correct order

**Key Methods**:
- `output_head_tags()` - Main output method (hooked to wp_head)
- `output_title()` - Group A: Title tag
- `output_description()` - Group B: Meta description
- `output_robots()` - Group C: Robots directives
- `output_canonical()` - Group D: Canonical link
- `output_open_graph()` - Group E: Open Graph tags
- `output_twitter_card()` - Group F: Twitter Card tags
- `output_hreflang()` - Group G: Hreflang alternates

**Output Order** (Requirement 2.1):
1. **Group A**: `<title>` tag
2. **Group B**: `<meta name="description">`
3. **Group C**: `<meta name="robots">`
4. **Group D**: `<link rel="canonical">`
5. **Group E**: Open Graph tags (og:type, og:title, og:description, og:url, og:image, og:site_name, article:published_time, article:modified_time)
6. **Group F**: Twitter Card tags (twitter:card, twitter:title, twitter:description, twitter:image)
7. **Group G**: Hreflang alternates (only if WPML/Polylang active)

**Escaping**:
- Title: `esc_html()`
- Meta content: `esc_attr()`
- URLs: `esc_url()`
- ISO 8601 dates: No escaping (validated format)

**Key Features**:
- Conditional output (e.g., description only if non-empty)
- Proper escaping for all output contexts
- ISO 8601 date formatting for article timestamps
- Hreflang detection for multilingual sites

### 3. Meta_Resolver

**File**: `class-meta-resolver.php`

**Responsibility**: Resolve all meta field values through fallback chains

**Key Methods**:
- `resolve_title(post_id)` - Title with fallback chain
- `resolve_description(post_id)` - Description with fallback chain
- `resolve_og_image(post_id)` - OG image with fallback chain
- `resolve_canonical(post_id)` - Canonical URL with fallback chain
- `resolve_robots(post_id)` - Robots directives with merging
- `resolve_twitter_title(post_id)` - Twitter-specific title
- `resolve_twitter_description(post_id)` - Twitter-specific description
- `resolve_twitter_image(post_id)` - Twitter-specific image
- `get_hreflang_alternates()` - Language alternates

**Fallback Chains**:

#### Title Fallback Chain
1. `_meowseo_title` postmeta (custom SEO title)
2. Title pattern for post type (via Title_Patterns)
3. `post_title + separator + site_name` (fallback)
4. **Never returns empty string**

#### Description Fallback Chain
1. `_meowseo_description` postmeta (custom description)
2. Post excerpt (first 160 chars, HTML stripped)
3. Post content (first 160 chars, HTML stripped)
4. Empty string (allows Meta_Output to skip tag)

#### OG Image Fallback Chain
1. `_meowseo_og_image` postmeta (attachment ID)
2. Featured image (if width >= 1200px)
3. First content image (if width >= 1200px)
4. Global default from settings
5. Empty string

#### Canonical URL Fallback Chain
1. `_meowseo_canonical` postmeta (custom canonical)
2. `get_permalink()` for singular posts
3. `get_term_link()` for term archives
4. `home_url()` for homepage
5. **Always strips pagination params**: `/page/N/`, `?paged=N`, `?page=N`
6. **Never returns empty string**

#### Robots Directives Merging
- **Base**: `index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1`
- **Post overrides**: `_meowseo_robots_noindex`, `_meowseo_robots_nofollow` postmeta
- **Automatic rules**:
  - `noindex` for search pages (`is_search()`)
  - `noindex` for attachment pages (`is_attachment()`)
  - `noindex` for date archives (if enabled in settings)
- **Google Discover directives always present**

**Key Features**:
- Pure functions with no side effects
- Comprehensive fallback chains ensure no empty output
- HTML stripping for text truncation
- Image dimension validation (1200px minimum)
- Pagination parameter stripping
- WPML/Polylang detection for hreflang

### 4. Title_Patterns

**File**: `class-title-patterns.php`

**Responsibility**: Parse, validate, and resolve title patterns with variable substitution

**Key Methods**:
- `resolve(pattern, context)` - Replace variables in pattern
- `parse(pattern)` - Parse pattern into structured representation
- `print(structured)` - Convert structured pattern back to string
- `validate(pattern)` - Validate pattern syntax
- `get_pattern_for_post_type(post_type)` - Get pattern for post type
- `get_pattern_for_page_type(page_type)` - Get pattern for page type
- `get_default_patterns()` - Get all default patterns

**Supported Variables**:
- `{title}` - Post/page title
- `{sep}` - Separator from settings (default: `|`)
- `{site_name}` - Site name from `get_bloginfo('name')`
- `{tagline}` - Site tagline from `get_bloginfo('description')`
- `{page}` - "Page N" for paginated content
- `{term_name}` - Category/tag name
- `{term_description}` - Category/tag description
- `{author_name}` - Author display name
- `{current_year}` - Current year (4 digits)
- `{current_month}` - Current month name

**Default Patterns**:
```php
[
    'post' => '{title} {sep} {site_name}',
    'page' => '{title} {sep} {site_name}',
    'homepage' => '{site_name} {sep} {tagline}',
    'category' => '{term_name} Archives {sep} {site_name}',
    'tag' => '{term_name} Tag {sep} {site_name}',
    'author' => '{author_name} {sep} {site_name}',
    'date' => '{current_month} {current_year} Archives {sep} {site_name}',
    'search' => 'Search Results {sep} {site_name}',
    '404' => 'Page Not Found {sep} {site_name}',
    'attachment' => '{title} {sep} {site_name}'
]
```

**Parser Structure**:
```php
// Input: "{title} {sep} {site_name}"
// Output:
[
    ['type' => 'variable', 'name' => 'title'],
    ['type' => 'literal', 'value' => ' '],
    ['type' => 'variable', 'name' => 'sep'],
    ['type' => 'literal', 'value' => ' '],
    ['type' => 'variable', 'name' => 'site_name']
]
```

**Validation Rules**:
- All variables must be from supported list
- Curly braces must be balanced
- Invalid syntax returns error object: `['error' => true, 'message' => 'Description']`

**Round-Trip Property**:
```php
$pattern = "{title} {sep} {site_name}";
$parsed = Title_Patterns::parse($pattern);
$printed = Title_Patterns::print($parsed);
$reparsed = Title_Patterns::parse($printed);
// $parsed === $reparsed (equivalent structure)
```

**Key Features**:
- Variable substitution with context array
- Parser validates patterns before storage
- Pretty printer enables round-trip consistency
- Default patterns for all page types
- Missing variables replaced with empty strings

### 5. Meta_Postmeta

**File**: `class-meta-postmeta.php`

**Responsibility**: Register all SEO postmeta fields with WordPress

**Key Methods**:
- `register()` - Register all postmeta fields

**Registered Fields** (all prefixed with `_meowseo_`):
- `title` (string) - Custom SEO title
- `description` (string) - Custom meta description
- `robots_noindex` (boolean) - Noindex flag
- `robots_nofollow` (boolean) - Nofollow flag
- `canonical` (string) - Custom canonical URL
- `og_title` (string) - Open Graph title override
- `og_description` (string) - Open Graph description override
- `og_image` (integer) - Open Graph image attachment ID
- `twitter_title` (string) - Twitter Card title override
- `twitter_description` (string) - Twitter Card description override
- `twitter_image` (integer) - Twitter Card image attachment ID
- `focus_keyword` (string) - Primary focus keyword
- `direct_answer` (string) - Direct answer for featured snippets
- `schema_type` (string) - Schema type override
- `schema_config` (string) - Schema configuration JSON
- `gsc_last_submit` (integer) - Last GSC submit timestamp

**Registration Details**:
- Registered for all public post types
- All fields have `show_in_rest: true` for Gutenberg access
- Type mapping:
  - `string`: `'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'`
  - `boolean`: `'type' => 'boolean'`
  - `integer`: `'type' => 'integer'`

**Key Features**:
- REST API exposure for Gutenberg editor
- Proper type and sanitization for each field
- Automatic registration for all public post types

### 6. Global_SEO

**File**: `class-global-seo.php`

**Responsibility**: Handle SEO for archives, homepage, search, 404, and other non-singular pages

**Key Methods**:
- `get_current_page_type()` - Detect current page type
- `get_title()` - Get title for non-singular page
- `get_description()` - Get description for non-singular page
- `get_robots()` - Get robots directives for non-singular page
- `get_canonical()` - Get canonical URL for non-singular page

**Supported Page Types**:
1. **Homepage** - Uses homepage pattern, tagline as description fallback
2. **Blog Index** - Uses blog index pattern
3. **Category Archives** - Uses category name in pattern, category description as meta description
4. **Tag Archives** - Uses tag name in pattern, tag description as meta description
5. **Custom Taxonomy Archives** - Uses taxonomy term name and description
6. **Author Pages** - Uses author name in pattern, author bio as description
7. **Date Archives** - Uses date pattern (month/year)
8. **Search Results** - Always applies noindex
9. **404 Pages** - Uses 404 pattern
10. **Post Type Archives** - Uses post type name in pattern

**Automatic Rules**:
- **Author noindex**: Applied if author has < 2 published posts
- **Search noindex**: Always applied to search pages
- **Date archive noindex**: Applied if enabled in settings

**Key Features**:
- Page type detection for all non-singular contexts
- Context-specific title patterns and descriptions
- Automatic noindex rules based on content
- Fallback to Global_SEO when Meta_Resolver is on non-singular page

### 7. Robots_Txt

**File**: `class-robots-txt.php`

**Responsibility**: Manage virtual robots.txt via filter

**Key Methods**:
- `register()` - Hook into robots_txt filter
- `filter_robots_txt(output, public)` - Filter callback

**Output Format**:
```
User-agent: *
Disallow: /wp-admin/
Disallow: /wp-login.php
Disallow: /wp-includes/

[Custom directives from settings]

Sitemap: https://example.com/meowseo-sitemap.xml
```

**Key Features**:
- Hooks into `robots_txt` filter (no physical file written)
- Default directives always included
- Custom directives from settings textarea
- Automatic sitemap URL appending
- Proper formatting with line breaks

## Fallback Chains

### Title Fallback Chain

```
┌─────────────────────────────────────────┐
│ 1. _meowseo_title postmeta              │
│    (Custom SEO title)                   │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 2. Title pattern for post type          │
│    (via Title_Patterns::resolve)        │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 3. post_title + separator + site_name   │
│    (Fallback concatenation)             │
└─────────────────────────────────────────┘
                    ↓
        ✓ Never returns empty
```

### Description Fallback Chain

```
┌─────────────────────────────────────────┐
│ 1. _meowseo_description postmeta        │
│    (Custom description)                 │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 2. Post excerpt (160 chars)             │
│    (HTML stripped)                      │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 3. Post content (160 chars)             │
│    (HTML stripped)                      │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 4. Empty string                         │
│    (Meta_Output skips tag)              │
└─────────────────────────────────────────┘
```

### OG Image Fallback Chain

```
┌─────────────────────────────────────────┐
│ 1. _meowseo_og_image postmeta           │
│    (Attachment ID)                      │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 2. Featured image                       │
│    (if width >= 1200px)                 │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 3. First content image                  │
│    (if width >= 1200px)                 │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 4. Global default from settings         │
│    (Plugin option)                      │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 5. Empty string                         │
│    (Meta_Output skips og:image)         │
└─────────────────────────────────────────┘
```

### Canonical URL Fallback Chain

```
┌─────────────────────────────────────────┐
│ 1. _meowseo_canonical postmeta          │
│    (Custom canonical URL)               │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 2. get_permalink() for singular         │
│    (Post/page URL)                      │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 3. get_term_link() for term archives    │
│    (Category/tag URL)                   │
└─────────────────────────────────────────┘
                    ↓ (if empty)
┌─────────────────────────────────────────┐
│ 4. home_url() for homepage              │
│    (Site home URL)                      │
└─────────────────────────────────────────┘
                    ↓
        ✓ Always strips pagination
        ✓ Never returns empty
```

## Title Pattern System

### Pattern Syntax

Patterns use `{variable}` syntax for substitution:

```
{title} {sep} {site_name}
→ "How to Build a WordPress Plugin | MeowSEO"

{term_name} Archives {sep} {site_name}
→ "WordPress Tutorials Archives | MeowSEO"

{author_name} {sep} {site_name}
→ "John Doe | MeowSEO"
```

### Common Patterns

**Blog Post**:
```
{title} {sep} {site_name}
```

**Homepage**:
```
{site_name} {sep} {tagline}
```

**Category Archive**:
```
{term_name} Archives {sep} {site_name}
```

**Author Page**:
```
{author_name} {sep} {site_name}
```

**Paginated Post**:
```
{title} {page} {sep} {site_name}
```

### Pattern Parsing

The parser converts patterns into a structured representation:

```php
Input: "{title} {sep} {site_name}"

Parsed:
[
    ['type' => 'variable', 'name' => 'title'],
    ['type' => 'literal', 'value' => ' '],
    ['type' => 'variable', 'name' => 'sep'],
    ['type' => 'literal', 'value' => ' '],
    ['type' => 'variable', 'name' => 'site_name']
]

Pretty Printed: "{title} {sep} {site_name}"
```

### Pattern Validation

Patterns are validated for:
- **Balanced braces**: All `{` must have matching `}`
- **Valid variables**: All variables must be from supported list
- **Error reporting**: Invalid patterns return error object with message

```php
// Valid pattern
$result = Title_Patterns::parse("{title} {sep} {site_name}");
// Returns: array of parsed elements

// Invalid pattern (unbalanced braces)
$result = Title_Patterns::parse("{title {sep} {site_name}");
// Returns: ['error' => true, 'message' => 'Unbalanced curly braces at position 6']

// Invalid pattern (unsupported variable)
$result = Title_Patterns::parse("{title} {invalid_var} {site_name}");
// Returns: ['error' => true, 'message' => 'Unsupported variable: invalid_var']
```

## Usage Examples

### Basic Usage

The Meta Module is automatically loaded when enabled. No manual configuration needed for basic functionality:

```php
// In wp_head, all meta tags are automatically output
// No code needed - just enable the module in MeowSEO settings
```

### Custom Title Pattern

Set a custom title pattern for a post type:

```php
// In plugin settings or via filter
$patterns = [
    'post' => '{title} - {site_name}',  // Custom pattern
    'page' => '{title} | {site_name}',  // Different separator
];

update_option('meowseo_options', array_merge(
    get_option('meowseo_options', []),
    ['title_patterns' => $patterns]
));
```

### Custom SEO Title for a Post

Set a custom SEO title for a specific post:

```php
$post_id = 123;
update_post_meta($post_id, '_meowseo_title', 'My Custom SEO Title');
```

### Custom OG Image

Set a custom Open Graph image for a post:

```php
$post_id = 123;
$attachment_id = 456;  // Image attachment ID
update_post_meta($post_id, '_meowseo_og_image', $attachment_id);
```

### Custom Robots Directives

Set custom robots directives for a post:

```php
$post_id = 123;
update_post_meta($post_id, '_meowseo_robots_noindex', true);
update_post_meta($post_id, '_meowseo_robots_nofollow', false);
```

### Custom Canonical URL

Set a custom canonical URL for a post:

```php
$post_id = 123;
update_post_meta($post_id, '_meowseo_canonical', 'https://example.com/canonical-url/');
```

### Accessing Meta Values via REST API

```javascript
// Get meta for a post
const response = await fetch('/wp-json/wp/v2/posts/123', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});

const post = await response.json();
console.log(post.meta._meowseo_title);
console.log(post.meta._meowseo_description);
console.log(post.meta._meowseo_og_image);

// Update meta for a post
await fetch('/wp-json/wp/v2/posts/123', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        meta: {
            _meowseo_title: 'New SEO Title',
            _meowseo_description: 'New description'
        }
    })
});
```

## Performance Considerations

### Database Queries

- Single `get_post_meta()` call per field (WordPress caches internally)
- No custom queries - uses WordPress functions exclusively
- Cache resolved values in object cache with 1 hour TTL

### Caching Strategy

```php
$cache_key = "meowseo_meta_{$post_id}";
$cached = wp_cache_get($cache_key, 'meowseo');
if ($cached !== false) {
    return $cached;
}
// ... resolve value ...
wp_cache_set($cache_key, $value, 'meowseo', 3600);
```

### Memory Usage

- Pattern arrays kept small (< 1KB)
- Lazy-load Global_SEO only on non-singular pages
- No loading of all postmeta at once

### Execution Time

- `output_head_tags()` completes in < 10ms
- All operations are O(1) or O(n) where n is small
- No N+1 query problems

## Multilingual Support

### WPML Integration

When WPML is active, hreflang alternates are automatically output:

```html
<link rel="alternate" hreflang="en" href="https://example.com/en/page/">
<link rel="alternate" hreflang="es" href="https://example.com/es/page/">
<link rel="alternate" hreflang="fr" href="https://example.com/fr/page/">
```

### Polylang Integration

When Polylang is active, hreflang alternates are automatically output:

```html
<link rel="alternate" hreflang="en" href="https://example.com/en/page/">
<link rel="alternate" hreflang="es" href="https://example.com/es/page/">
```

### Detection Logic

```php
// WPML detection
defined('ICL_SITEPRESS_VERSION') && function_exists('icl_get_languages')

// Polylang detection
function_exists('pll_the_languages') && function_exists('pll_current_language')
```

## Security

### Escaping

All output is properly escaped for its context:

- **HTML content**: `esc_html()` for title tags
- **Attribute values**: `esc_attr()` for meta content
- **URLs**: `esc_url()` for canonical and hreflang
- **JSON**: `wp_json_encode()` for schema JSON-LD

### Sanitization

All input is sanitized:

- **Postmeta**: `sanitize_text_field()` for string fields
- **Options**: Validated before storage
- **User input**: Never trusted without sanitization

### Validation

- Pattern syntax validated before storage
- Image dimensions validated (1200px minimum)
- URLs validated with `wp_http_validate_url()`

## Troubleshooting

### Duplicate Title Tags

**Problem**: Title tag appears twice in page source

**Solution**: Ensure `remove_theme_support('title-tag')` is called in Meta_Module::boot()

### Missing Meta Tags

**Problem**: Expected meta tags not appearing

**Solution**: 
1. Verify Meta Module is enabled in settings
2. Check postmeta values are set correctly
3. Verify no other plugins are removing tags
4. Check WordPress debug log for errors

### Incorrect Title Pattern

**Problem**: Title pattern not resolving correctly

**Solution**:
1. Verify pattern syntax is valid (balanced braces)
2. Verify all variables are supported
3. Check context array contains required variables
4. Test pattern with Title_Patterns::parse()

### WPML/Polylang Not Working

**Problem**: Hreflang tags not appearing

**Solution**:
1. Verify WPML or Polylang is active
2. Check plugin functions are callable
3. Verify language configuration in multilingual plugin
4. Test with `is_wpml_active()` or `is_polylang_active()`

## Requirements Mapping

This module satisfies all requirements from the Meta Module Rebuild specification:

- **Requirement 1**: Module entry point and hook registration
- **Requirement 2**: Meta tag output in correct order
- **Requirement 3**: SEO title resolution with fallback chain
- **Requirement 4**: Meta description resolution with fallback chain
- **Requirement 5**: Open Graph image resolution with fallback chain
- **Requirement 6**: Canonical URL resolution with pagination stripping
- **Requirement 7**: Robots directive resolution with automatic rules
- **Requirement 8**: Title pattern system with variable replacement
- **Requirement 9**: Postmeta registration for all SEO fields
- **Requirement 10**: Global SEO for non-singular pages
- **Requirement 11**: Robots.txt virtual file management
- **Requirement 12**: Parser and pretty printer for title patterns

## Testing

The Meta Module includes comprehensive test coverage:

- **Unit Tests**: Specific examples and edge cases
- **Property-Based Tests**: 29 correctness properties validated
- **Integration Tests**: WordPress hook integration and theme compatibility

Run tests with:

```bash
# All tests
phpunit tests/modules/meta/

# Unit tests only
phpunit tests/modules/meta/ --filter "Unit"

# Property tests only
phpunit tests/properties/ --filter "Meta"
```

## Future Enhancements

- Custom variable support via filters
- Pattern inheritance/nesting
- Advanced pattern editor UI
- Schema.org structured data integration
- Automatic image optimization for OG images
- Bulk meta tag editing interface
