# Requirements Document

## Introduction

The Meta Module is the core component of the MeowSEO plugin responsible for managing and outputting SEO meta tags on the frontend. The current implementation has a basic structure but lacks the architectural sophistication and feature completeness found in mature SEO plugins like Yoast and RankMath. This rebuild will create a cleaner, more maintainable architecture with proper separation of concerns, comprehensive meta tag output, and robust fallback chains.

The new architecture will be inspired by Yoast's Presenter pattern and RankMath's title pattern system, but implemented in a leaner, more focused manner without unnecessary complexity. The module will handle all frontend meta tag output including title tags, meta descriptions, robots directives, canonical URLs, Open Graph tags, Twitter Card tags, and hreflang alternates.

---

## Glossary

- **Meta_Module**: The main module class implementing the Module interface, responsible for registering hooks and coordinating meta tag output.
- **Meta_Output**: The class responsible for outputting all meta tags in wp_head in the correct order.
- **Meta_Resolver**: The class responsible for resolving meta field values through fallback chains.
- **Title_Patterns**: The class responsible for parsing and resolving title patterns with variable substitution.
- **Meta_Postmeta**: The class responsible for registering all SEO postmeta fields with WordPress.
- **Global_SEO**: The class responsible for handling SEO for non-singular pages (archives, homepage, search, 404, etc.).
- **Robots_Txt**: The class responsible for managing the virtual robots.txt file via the robots_txt filter.
- **Tag_Group**: A logical grouping of related meta tags that must be output together in a specific order.
- **Fallback_Chain**: A sequence of data sources checked in order until a non-empty value is found.
- **Title_Pattern**: A template string containing variables like {title}, {sep}, {site_name} that are replaced with actual values.
- **Pattern_Variable**: A placeholder in a title pattern that gets replaced with dynamic content.
- **Robots_Directive**: A meta tag value controlling search engine crawler behavior (e.g., "noindex,nofollow").
- **Canonical_URL**: The preferred URL for a page to prevent duplicate content issues.
- **OG_Tag**: An Open Graph meta tag for social media sharing (Facebook, LinkedIn, etc.).
- **Twitter_Card**: A Twitter-specific meta tag for enhanced tweet display.
- **Hreflang_Alternate**: A link tag indicating alternate language versions of a page.
- **Google_Discover**: Google's mobile content feed requiring specific meta tag directives.
- **Pagination_Param**: URL query parameters indicating paginated content (e.g., /page/2/, ?paged=2).
- **WPML**: WordPress Multilingual Plugin for managing translated content.
- **Polylang**: Alternative WordPress multilingual plugin.
- **Featured_Image**: The primary image assigned to a post via WordPress's featured image feature.
- **Content_Image**: An image embedded within post content.
- **Global_Default**: A fallback value defined in plugin settings that applies when no post-specific value exists.

---

## Requirements

### Requirement 1: Module Entry Point and Hook Registration

**User Story:** As a developer, I want the Meta Module to follow the plugin's modular architecture, so that it only loads when enabled and registers hooks cleanly.

#### Acceptance Criteria

1. THE Meta_Module SHALL implement the Module interface with boot() and get_id() methods
2. WHEN boot() is called, THE Meta_Module SHALL register the wp_head hook with priority 1
3. WHEN boot() is called, THE Meta_Module SHALL register the document_title_parts filter to control the title tag
4. WHEN boot() is called, THE Meta_Module SHALL remove WordPress's default title tag output via remove_theme_support('title-tag')
5. WHEN boot() is called, THE Meta_Module SHALL register the save_post hook for classic editor meta save handling
6. WHEN boot() is called, THE Meta_Module SHALL register the rest_api_init hook for postmeta exposure
7. WHEN boot() is called, THE Meta_Module SHALL register the enqueue_block_editor_assets hook for Gutenberg sidebar

### Requirement 2: Meta Tag Output in Correct Order

**User Story:** As a site administrator, I want all meta tags output in the correct order in wp_head, so that search engines and social platforms parse them correctly.

#### Acceptance Criteria

1. THE Meta_Output SHALL output exactly 7 tag groups in wp_head in the following order: Group A (Title), Group B (Meta Description), Group C (Robots), Group D (Canonical), Group E (Open Graph), Group F (Twitter Card), Group G (Hreflang)
2. THE Meta_Output SHALL output Group A (Title tag) using a controlled <title> element and SHALL NOT use wp_title() function
3. THE Meta_Output SHALL output Group B (Meta description) only when the resolved description value is non-empty
4. THE Meta_Output SHALL output Group C (Robots meta tag) with the directives: max-image-preview:large, max-snippet:-1, max-video-preview:-1 in addition to index/noindex and follow/nofollow directives
5. THE Meta_Output SHALL output Group D (Canonical link) on every page and SHALL strip all pagination parameters from the canonical URL
6. THE Meta_Output SHALL output Group E (Open Graph tags) in the following order: og:type, og:title, og:description, og:url, og:image (with og:image:width and og:image:height), og:site_name, article:published_time, article:modified_time
7. THE Meta_Output SHALL format article:published_time and article:modified_time in ISO 8601 format
8. THE Meta_Output SHALL output Group F (Twitter Card tags) with values independently settable from Open Graph values
9. THE Meta_Output SHALL output Group G (Hreflang alternate links) only when WPML or Polylang is detected as active
10. THE Meta_Output SHALL retrieve all values from Meta_Resolver and SHALL NOT compute any values inline

### Requirement 3: SEO Title Resolution with Fallback Chain

**User Story:** As a content editor, I want SEO titles to fall back gracefully from custom values to patterns to defaults, so that every page has an appropriate title even when I don't set one manually.

#### Acceptance Criteria

1. THE Meta_Resolver SHALL resolve SEO title using the following fallback chain: postmeta value → title pattern → raw title + site name
2. WHEN resolving SEO title for a singular post, THE Meta_Resolver SHALL first check the _meowseo_title postmeta field
3. WHEN the _meowseo_title postmeta is empty, THE Meta_Resolver SHALL apply the title pattern for the post type
4. WHEN applying a title pattern, THE Meta_Resolver SHALL delegate variable replacement to Title_Patterns class
5. WHEN no title pattern is defined for the post type, THE Meta_Resolver SHALL concatenate the raw post title + separator + site name
6. THE Meta_Resolver SHALL never return an empty string for SEO title on singular posts

### Requirement 4: Meta Description Resolution with Fallback Chain

**User Story:** As a content editor, I want meta descriptions to fall back from custom values to excerpts to content, so that every page has a description for search results.

#### Acceptance Criteria

1. THE Meta_Resolver SHALL resolve meta description using the following fallback chain: postmeta value → excerpt (160 chars) → content (160 chars) → empty string
2. WHEN resolving meta description for a singular post, THE Meta_Resolver SHALL first check the _meowseo_description postmeta field
3. WHEN the _meowseo_description postmeta is empty and the post has an excerpt, THE Meta_Resolver SHALL use the first 160 characters of the excerpt
4. WHEN the post has no excerpt, THE Meta_Resolver SHALL use the first 160 characters of the post content with HTML stripped
5. WHEN truncating to 160 characters, THE Meta_Resolver SHALL strip all HTML tags and shortcodes before measuring length
6. THE Meta_Resolver SHALL return an empty string when no description source is available (allowing Meta_Output to skip the meta description tag)

### Requirement 5: Open Graph Image Resolution with Fallback Chain

**User Story:** As a content editor, I want Open Graph images to fall back from custom images to featured images to content images to a global default, so that social shares always have an appropriate image.

#### Acceptance Criteria

1. THE Meta_Resolver SHALL resolve Open Graph image using the following fallback chain: postmeta image → featured image (min 1200px wide) → first content image → global default → empty string
2. WHEN resolving Open Graph image for a singular post, THE Meta_Resolver SHALL first check the _meowseo_og_image postmeta field (attachment ID)
3. WHEN the _meowseo_og_image postmeta is empty and the post has a featured image, THE Meta_Resolver SHALL use the featured image if its width is at least 1200 pixels
4. WHEN the featured image is narrower than 1200 pixels, THE Meta_Resolver SHALL scan the post content for the first image with width at least 1200 pixels
5. WHEN no suitable image is found in postmeta, featured image, or content, THE Meta_Resolver SHALL use the global default image URL from plugin settings
6. WHEN no global default is configured, THE Meta_Resolver SHALL return an empty string
7. THE Meta_Resolver SHALL return both the image URL and dimensions (width, height) as an array for use in og:image:width and og:image:height tags

### Requirement 6: Canonical URL Resolution with Pagination Stripping

**User Story:** As a site administrator, I want canonical URLs to always point to the unpaginated version of a page, so that search engines consolidate ranking signals to the primary URL.

#### Acceptance Criteria

1. THE Meta_Resolver SHALL resolve canonical URL using the following fallback chain: postmeta override → get_permalink() → get_term_link() → home_url()
2. WHEN resolving canonical URL for a singular post, THE Meta_Resolver SHALL first check the _meowseo_canonical postmeta field
3. WHEN the _meowseo_canonical postmeta is empty, THE Meta_Resolver SHALL use get_permalink() for singular posts
4. WHEN resolving canonical URL for a term archive, THE Meta_Resolver SHALL use get_term_link()
5. WHEN resolving canonical URL for the homepage, THE Meta_Resolver SHALL use home_url()
6. THE Meta_Resolver SHALL strip all pagination parameters from the canonical URL including /page/N/, ?paged=N, and ?page=N
7. THE Meta_Resolver SHALL always return a non-empty canonical URL for every page type

### Requirement 7: Robots Directive Resolution with Automatic Rules

**User Story:** As a site administrator, I want robots directives to merge global defaults with post overrides and automatic rules, so that search engines index the right pages without manual configuration.

#### Acceptance Criteria

1. THE Meta_Resolver SHALL resolve robots directives by merging global defaults + post overrides + automatic rules
2. THE Meta_Resolver SHALL start with global default directives: index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1
3. WHEN resolving robots for a singular post, THE Meta_Resolver SHALL check _meowseo_robots_noindex and _meowseo_robots_nofollow postmeta fields and override the index/follow directives accordingly
4. THE Meta_Resolver SHALL automatically apply noindex to search result pages (is_search())
5. THE Meta_Resolver SHALL automatically apply noindex to attachment pages (is_attachment())
6. THE Meta_Resolver SHALL automatically apply noindex to date archives when the "noindex date archives" option is enabled in plugin settings
7. THE Meta_Resolver SHALL always include the Google Discover directives (max-image-preview:large, max-snippet:-1, max-video-preview:-1) in the final robots string

### Requirement 8: Title Pattern System with Variable Replacement

**User Story:** As a site administrator, I want to define title patterns with variables for different page types, so that titles follow a consistent format across my site.

#### Acceptance Criteria

1. THE Title_Patterns SHALL support the following variables: {title}, {sep}, {site_name}, {tagline}, {page}, {term_name}, {term_description}, {author_name}, {current_year}, {current_month}
2. THE Title_Patterns SHALL define default patterns for the following page types: single post, single page, homepage, category, tag, author, date archive, search, 404, attachment, custom post types
3. THE Title_Patterns SHALL provide a resolve(pattern, context) method that accepts a pattern string and a context array and returns the resolved title
4. WHEN resolving a pattern, THE Title_Patterns SHALL replace all variables with their corresponding values from the context array
5. WHEN a variable is not present in the context array, THE Title_Patterns SHALL replace it with an empty string
6. THE Title_Patterns SHALL replace {sep} with the separator character defined in plugin settings (default: "|")
7. THE Title_Patterns SHALL replace {page} with "Page N" when the current page is paginated (e.g., /page/2/)

### Requirement 9: Postmeta Registration for All SEO Fields

**User Story:** As a developer, I want all SEO postmeta fields registered with WordPress, so that they are available in the REST API and Gutenberg editor.

#### Acceptance Criteria

1. THE Meta_Postmeta SHALL register all SEO postmeta keys using register_post_meta() for all public post types
2. THE Meta_Postmeta SHALL set show_in_rest: true for all registered postmeta keys to enable Gutenberg access
3. THE Meta_Postmeta SHALL register the following postmeta keys: _meowseo_title, _meowseo_description, _meowseo_robots_noindex, _meowseo_robots_nofollow, _meowseo_canonical, _meowseo_og_title, _meowseo_og_description, _meowseo_og_image, _meowseo_twitter_title, _meowseo_twitter_description, _meowseo_twitter_image, _meowseo_focus_keyword, _meowseo_direct_answer, _meowseo_schema_type, _meowseo_schema_config, _meowseo_gsc_last_submit
4. THE Meta_Postmeta SHALL register _meowseo_og_image and _meowseo_twitter_image as integer type (attachment ID)
5. THE Meta_Postmeta SHALL register _meowseo_robots_noindex and _meowseo_robots_nofollow as boolean type
6. THE Meta_Postmeta SHALL register _meowseo_schema_config as string type (JSON)
7. THE Meta_Postmeta SHALL register _meowseo_gsc_last_submit as integer type (Unix timestamp)

### Requirement 10: Global SEO for Non-Singular Pages

**User Story:** As a site administrator, I want SEO meta tags on archive pages, the homepage, search results, and error pages, so that all pages have appropriate meta tags.

#### Acceptance Criteria

1. THE Global_SEO SHALL handle SEO for the following page types: homepage, blog index, category archives, tag archives, custom taxonomy archives, author pages, date archives, search results, 404 pages, custom post type archives
2. WHEN handling the homepage, THE Global_SEO SHALL apply the homepage title pattern and use the site tagline as the meta description fallback
3. WHEN handling category archives, THE Global_SEO SHALL use the category name in the title pattern and the category description as the meta description
4. WHEN handling author pages, THE Global_SEO SHALL use the author name in the title pattern and the author bio as the meta description
5. THE Global_SEO SHALL automatically apply noindex to author pages where the author has fewer than 2 published posts
6. THE Global_SEO SHALL always apply noindex to search result pages
7. THE Global_SEO SHALL apply the correct title pattern for each page type using Title_Patterns class
8. THE Global_SEO SHALL remove duplicate meta tags output by themes or other plugins by unhooking conflicting actions

### Requirement 11: Robots.txt Virtual File Management

**User Story:** As a site administrator, I want to manage robots.txt directives through the plugin settings, so that I don't need to edit server files or worry about file permissions.

#### Acceptance Criteria

1. THE Robots_Txt SHALL hook into the robots_txt filter and SHALL NOT write a physical robots.txt file to the filesystem
2. THE Robots_Txt SHALL automatically append the sitemap index URL to the robots.txt output
3. THE Robots_Txt SHALL provide a settings textarea for custom robots.txt directives that are appended to the output
4. THE Robots_Txt SHALL include sensible default directives: Disallow: /wp-admin/, Disallow: /wp-login.php, Disallow: /wp-includes/
5. THE Robots_Txt SHALL format the robots.txt output with proper line breaks and User-agent: * declaration
6. WHEN custom directives are provided in settings, THE Robots_Txt SHALL append them after the default directives and before the sitemap URL

### Requirement 12: Parser and Pretty Printer for Title Patterns

**User Story:** As a developer, I want a parser and pretty printer for title patterns, so that I can validate patterns and ensure round-trip consistency.

#### Acceptance Criteria

1. THE Title_Patterns SHALL provide a parse(pattern) method that validates a title pattern string and returns a structured representation
2. THE Title_Patterns SHALL provide a print(structured) method that formats a structured pattern representation back into a pattern string
3. FOR ALL valid title pattern strings, parsing then printing then parsing SHALL produce an equivalent structured representation (round-trip property)
4. WHEN a title pattern contains invalid syntax, THE Title_Patterns parse() method SHALL return an error object with a descriptive message
5. THE Title_Patterns SHALL validate that all variables in a pattern are from the supported variable list
6. THE Title_Patterns SHALL validate that curly braces are properly balanced in a pattern

---

## Special Requirements Guidance

### Parser and Pretty Printer Requirements

The Title_Patterns class includes both a parser and a pretty printer for title pattern strings. This is ESSENTIAL for:
- Validating user-entered patterns in the settings UI
- Ensuring patterns can be safely stored and retrieved
- Testing round-trip consistency (parse → print → parse)

**Example Title Pattern Parsing**:
```
Input pattern: "{title} {sep} {site_name}"
Parsed structure: [
  { type: "variable", name: "title" },
  { type: "literal", value: " " },
  { type: "variable", name: "sep" },
  { type: "literal", value: " " },
  { type: "variable", name: "site_name" }
]
Pretty printed: "{title} {sep} {site_name}"
```

**Round-Trip Requirement**:
```php
$pattern = "{title} {sep} {site_name}";
$parsed = Title_Patterns::parse($pattern);
$printed = Title_Patterns::print($parsed);
$reparsed = Title_Patterns::parse($printed);
// $parsed === $reparsed (equivalent structure)
```

This round-trip property MUST be tested with property-based testing to ensure all valid patterns can be safely stored and retrieved.
