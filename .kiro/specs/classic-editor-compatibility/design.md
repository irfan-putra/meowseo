# Design Document: Classic Editor Compatibility

## Overview

The Classic Editor Compatibility feature brings the MeowSEO Classic Editor meta box to full feature parity with the Gutenberg sidebar implementation and competitive SEO plugins (Yoast SEO Premium, RankMath Pro). The current implementation provides only 5 basic fields; this design extends it to include tabbed navigation, real-time SERP preview, character counters, SEO/readability analysis, social media fields, schema configuration, AI generation, and Google Search Console integration.

### Architecture Constraints

The Classic Editor does not load React or WordPress block editor packages. The implementation must use:
- **PHP-rendered HTML** for all UI components
- **Vanilla JavaScript with jQuery** for client-side interactions
- **Existing REST API endpoints** for all data operations
- **Standard WordPress `$_POST` handling** for form submission and data persistence

No new PHP business logic or REST endpoints are required—this is purely a UI/UX layer built on top of the existing MeowSEO REST API.

### Design Goals

1. **Feature Parity**: Match all capabilities available in the Gutenberg sidebar
2. **User Experience**: Provide intuitive tabbed navigation with persistent state
3. **Real-Time Feedback**: Character counters, SERP preview, and analysis results update as users type
4. **Performance**: Debounced API calls and efficient DOM manipulation
5. **Maintainability**: Clean separation between PHP rendering, JavaScript behavior, and REST API communication

## Architecture

### Component Hierarchy

```
Classic Editor Meta Box (PHP)
├── Tab Navigation (JavaScript)
│   ├── General Tab
│   │   ├── SERP Preview (JavaScript)
│   │   ├── SEO Title + Character Counter (JavaScript)
│   │   ├── Meta Description + Character Counter (JavaScript)
│   │   ├── Focus Keyword
│   │   ├── Direct Answer
│   │   ├── AI Generation Buttons (REST API)
│   │   └── Analysis Panel (REST API)
│   ├── Social Tab
│   │   ├── Open Graph Fields
│   │   ├── Twitter Card Fields
│   │   ├── Media Picker (wp.media API)
│   │   └── Use OG for Twitter Toggle
│   ├── Schema Tab
│   │   ├── Schema Type Selector
│   │   └── Conditional Field Groups (JavaScript)
│   └── Advanced Tab
│       ├── Canonical URL
│       ├── Robots Meta Tags
│       └── GSC Submit Button (REST API)
└── Form Submission Handler (PHP)
```

### Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                     User Interaction                         │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              JavaScript Event Handlers                       │
│  • Tab switching (localStorage)                             │
│  • Character counters (debounced)                           │
│  • SERP preview (debounced)                                 │
│  • Media picker (wp.media)                                  │
│  • Schema field toggling                                    │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    REST API Calls                            │
│  • GET /meowseo/v1/analysis/{post_id}                       │
│  • POST /meowseo/v1/ai/generate                             │
│  • POST /meowseo/v1/gsc/submit                              │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              WordPress Post Save (PHP)                       │
│  • Nonce verification                                       │
│  • Field sanitization                                       │
│  • Postmeta updates                                         │
└─────────────────────────────────────────────────────────────┘
```

### File Structure

```
includes/modules/meta/
└── class-classic-editor.php      ← Extended with full UI

assets/
├── js/
│   └── classic-editor.js         ← All client-side behavior
└── css/
    └── classic-editor.css        ← All styling
```

## Components and Interfaces

### 1. PHP Meta Box Renderer

**File**: `includes/modules/meta/class-classic-editor.php`

**Responsibilities**:
- Render tabbed HTML structure
- Fetch and populate all postmeta values
- Enqueue JavaScript and CSS assets
- Localize script data (postId, nonce, restUrl)
- Handle form submission and data persistence

**Key Methods**:

```php
class Classic_Editor {
    public function init(): void
    public function enqueue_editor_scripts( string $hook ): void
    public function register_meta_box(): void
    public function render_meta_box( \WP_Post $post ): void
    public function save_meta( int $post_id, \WP_Post $post ): void
}
```

**Localized Script Data**:

```php
wp_localize_script( 'meowseo-classic-editor', 'meowseoClassic', [
    'postId'      => get_the_ID(),
    'nonce'       => wp_create_nonce( 'wp_rest' ),
    'restUrl'     => rest_url( 'meowseo/v1' ),
    'postTitle'   => get_the_title(),
    'postExcerpt' => get_the_excerpt(),
    'siteUrl'     => home_url(),
] );
```

### 2. JavaScript Controller

**File**: `assets/js/classic-editor.js`

**Responsibilities**:
- Tab navigation with localStorage persistence
- Character counter updates with color-coded thresholds
- Real-time SERP preview updates
- Debounced analysis triggers
- AI generation button handlers
- Media library integration
- Schema field group toggling
- GSC submit button handler

**Module Structure**:

```javascript
(function($) {
    'use strict';
    
    // Tab Management
    function initTabs() { /* ... */ }
    
    // Character Counters
    function initCounters() { /* ... */ }
    function updateCounter($input, $counter, thresholds) { /* ... */ }
    function getCounterClass(len, thresholds) { /* ... */ }
    
    // SERP Preview
    function initSerpPreview() { /* ... */ }
    function updateSerpPreview() { /* ... */ }
    function truncate(str, max) { /* ... */ }
    
    // Media Picker
    function initMediaPickers() { /* ... */ }
    
    // Social Tab
    function initOgTwitterToggle() { /* ... */ }
    
    // Schema Tab
    function initSchemaFields() { /* ... */ }
    
    // Analysis
    function runAnalysis() { /* ... */ }
    function renderAnalysis($panel, data) { /* ... */ }
    
    // AI Generation
    function initAiButtons() { /* ... */ }
    
    // GSC Submit
    function initGscSubmit() { /* ... */ }
    
    // Bootstrap
    $(function() {
        initTabs();
        initCounters();
        initSerpPreview();
        initMediaPickers();
        initOgTwitterToggle();
        initSchemaFields();
        initAiButtons();
        initGscSubmit();
    });
    
})(jQuery);
```

### 3. CSS Styling

**File**: `assets/css/classic-editor.css`

**Responsibilities**:
- Tab navigation styles
- Character counter color coding
- SERP preview styling
- Image picker layout
- Schema field group visibility
- Responsive layout adjustments

**Key Style Classes**:

```css
/* Tab Navigation */
#meowseo-tab-nav { /* ... */ }
.meowseo-tab-panel { /* ... */ }
.meowseo-active { /* ... */ }

/* Character Counters */
.meowseo-counter { /* ... */ }
.meowseo-ok { color: #155724; }
.meowseo-warn { color: #856404; }
.meowseo-bad { color: #721c24; }

/* SERP Preview */
.meowseo-serp-preview { /* ... */ }
.serp-url { /* ... */ }
.serp-title { /* ... */ }
.serp-desc { /* ... */ }

/* Image Picker */
.meowseo-image-picker { /* ... */ }
.meowseo-image-preview { /* ... */ }
.has-image { /* ... */ }

/* Schema Fields */
.meowseo-schema-fields { display: none; }
.meowseo-schema-fields[data-type="Article"] { /* ... */ }
```

## Data Models

### Postmeta Fields

All postmeta fields are already registered in `class-gutenberg-assets.php` with `show_in_rest` enabled. The Classic Editor uses the same postmeta keys:

| Postmeta Key | Type | Sanitization | Description |
|---|---|---|---|
| `_meowseo_title` | string | `sanitize_text_field` | SEO title override |
| `_meowseo_description` | string | `sanitize_textarea_field` | Meta description |
| `_meowseo_focus_keyword` | string | `sanitize_text_field` | Focus keyword |
| `_meowseo_direct_answer` | string | `sanitize_textarea_field` | Direct answer snippet |
| `_meowseo_og_title` | string | `sanitize_text_field` | Open Graph title |
| `_meowseo_og_description` | string | `sanitize_textarea_field` | Open Graph description |
| `_meowseo_og_image` | integer | `absint` | OG image attachment ID |
| `_meowseo_twitter_title` | string | `sanitize_text_field` | Twitter card title |
| `_meowseo_twitter_description` | string | `sanitize_textarea_field` | Twitter card description |
| `_meowseo_twitter_image` | integer | `absint` | Twitter image attachment ID |
| `_meowseo_use_og_for_twitter` | boolean | `rest_sanitize_boolean` | Use OG data for Twitter |
| `_meowseo_schema_type` | string | `sanitize_text_field` | Schema.org type |
| `_meowseo_schema_config` | string | JSON validation | Schema configuration JSON |
| `_meowseo_canonical` | string | `esc_url_raw` | Canonical URL |
| `_meowseo_robots_noindex` | boolean | `rest_sanitize_boolean` | Noindex directive |
| `_meowseo_robots_nofollow` | boolean | `rest_sanitize_boolean` | Nofollow directive |
| `_meowseo_gsc_last_submit` | integer | `absint` | Last GSC submission timestamp |

### Schema Configuration JSON Structure

The `_meowseo_schema_config` field stores type-specific configuration as JSON:

**Article Schema**:
```json
{
  "article_type": "Article|NewsArticle|BlogPosting"
}
```

**FAQ Schema**:
```json
{
  "faq_items": [
    {
      "question": "Question text",
      "answer": "Answer text"
    }
  ]
}
```

**HowTo Schema**:
```json
{
  "howto_name": "Tutorial name",
  "howto_description": "Tutorial description",
  "howto_steps": [
    {
      "name": "Step name",
      "text": "Step instructions"
    }
  ]
}
```

**LocalBusiness Schema**:
```json
{
  "lb_name": "Business name",
  "lb_type": "Business type",
  "lb_address": "Address",
  "lb_phone": "Phone",
  "lb_hours": "Opening hours"
}
```

**Product Schema**:
```json
{
  "product_name": "Product name",
  "product_description": "Description",
  "product_sku": "SKU",
  "product_price": "Price",
  "product_currency": "USD",
  "product_availability": "InStock"
}
```

### REST API Endpoints

The Classic Editor uses existing REST API endpoints—no new endpoints are required:

| Endpoint | Method | Purpose | Parameters |
|---|---|---|---|
| `/meowseo/v1/analysis/{post_id}` | GET | Retrieve SEO/readability analysis | `post_id` |
| `/meowseo/v1/ai/generate` | POST | Generate AI content | `post_id`, `type` (title\|description) |
| `/meowseo/v1/gsc/submit` | POST | Submit URL to Google Search Console | `post_id` |

All endpoints require WordPress REST API nonce authentication via `X-WP-Nonce` header.

## Error Handling

### Client-Side Error Handling

**REST API Failures**:
```javascript
$.ajax({
    url: meowseoClassic.restUrl + '/analysis/' + meowseoClassic.postId,
    method: 'GET',
    beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', meowseoClassic.nonce);
    },
    success: function(data) {
        renderAnalysis($panel, data);
    },
    error: function(xhr, status, error) {
        $panel.html('<p style="color:#721c24">Analysis failed. Save the post first, then try again.</p>');
        console.error('Analysis error:', status, error);
    }
});
```

**Media Library Failures**:
```javascript
if (typeof wp === 'undefined' || !wp.media) {
    alert('Media library is not available. Please refresh the page.');
    return;
}
```

**Invalid JSON in Schema Config**:
```javascript
try {
    var config = JSON.parse($('#meowseo_schema_config').val());
} catch (e) {
    console.error('Invalid schema configuration JSON:', e);
    config = {};
}
```

### Server-Side Error Handling

**Nonce Verification**:
```php
if (!isset($_POST[self::NONCE_FIELD]) ||
    !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])), self::NONCE_ACTION)) {
    return; // Silent fail - WordPress standard
}
```

**Permission Checks**:
```php
if (!current_user_can('edit_post', $post_id)) {
    return; // Silent fail - WordPress standard
}
```

**Autosave/Revision Protection**:
```php
if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    return; // Skip autosaves and revisions
}
```

**Schema Config Sanitization**:
```php
if (isset($_POST['meowseo_schema_config'])) {
    $raw = wp_unslash($_POST['meowseo_schema_config']);
    $decoded = json_decode($raw, true);
    $safe = $decoded ? wp_json_encode($decoded) : '';
    update_post_meta($post_id, '_meowseo_schema_config', $safe);
}
```

### Error Logging

All JavaScript errors are logged to the browser console for debugging:

```javascript
console.error('MeowSEO Classic Editor Error:', error);
```

Server-side errors are handled silently following WordPress conventions (no error messages displayed to users during save operations).

## Testing Strategy

### Unit Testing

**PHP Unit Tests** (PHPUnit):

1. **Meta Box Registration**:
   - Test `register_meta_box()` registers meta box for all public post types
   - Test meta box callback is correctly set

2. **Script Enqueuing**:
   - Test `enqueue_editor_scripts()` only runs on post.php and post-new.php
   - Test JavaScript and CSS assets are enqueued with correct dependencies
   - Test `wp_enqueue_media()` is called
   - Test localized script data contains all required keys

3. **Data Persistence**:
   - Test `save_meta()` correctly saves all string fields
   - Test `save_meta()` correctly saves boolean fields
   - Test `save_meta()` correctly saves integer fields (image IDs)
   - Test `save_meta()` correctly sanitizes all inputs
   - Test `save_meta()` validates and sanitizes schema config JSON
   - Test `save_meta()` respects nonce verification
   - Test `save_meta()` respects permission checks
   - Test `save_meta()` skips autosaves and revisions

4. **Schema Config Sanitization**:
   - Test valid JSON is preserved
   - Test invalid JSON returns empty string
   - Test empty input returns empty string
   - Test malformed JSON is rejected

**JavaScript Unit Tests** (Jest):

1. **Tab Navigation**:
   - Test tab switching updates active class
   - Test tab switching shows/hides correct panels
   - Test active tab is saved to localStorage
   - Test active tab is restored from localStorage on page load

2. **Character Counters**:
   - Test counter updates on input
   - Test counter displays correct character count
   - Test counter applies correct CSS class based on thresholds
   - Test title thresholds: ok (30-60), warn (<30 or 61-70), bad (>70)
   - Test description thresholds: ok (120-155), warn (<120 or 156-170), bad (>170)

3. **SERP Preview**:
   - Test preview updates when title changes
   - Test preview updates when description changes
   - Test preview truncates title at 60 characters
   - Test preview truncates description at 155 characters
   - Test preview falls back to post title when SEO title is empty
   - Test preview falls back to post excerpt when description is empty

4. **Media Picker**:
   - Test clicking "Select Image" opens media library
   - Test selecting image updates hidden input with attachment ID
   - Test selecting image displays image preview
   - Test clicking "Remove" clears image ID and hides preview

5. **Schema Field Toggling**:
   - Test changing schema type shows correct field group
   - Test changing schema type hides other field groups
   - Test selecting "None" hides all field groups

6. **Debouncing**:
   - Test analysis is debounced by 1 second
   - Test SERP preview updates are debounced by 100ms

### Integration Testing

1. **Full Workflow Tests**:
   - Test user can switch tabs and all fields persist
   - Test user can enter SEO title and see character counter update
   - Test user can enter meta description and see SERP preview update
   - Test user can select OG image and see preview
   - Test user can select schema type and see conditional fields
   - Test user can save post and all fields are persisted to postmeta

2. **REST API Integration**:
   - Test analysis button triggers GET /meowseo/v1/analysis/{post_id}
   - Test AI generate button triggers POST /meowseo/v1/ai/generate
   - Test GSC submit button triggers POST /meowseo/v1/gsc/submit
   - Test all REST calls include correct nonce header

3. **WordPress Integration**:
   - Test meta box appears on all public post types
   - Test meta box does not appear on non-public post types
   - Test assets are only enqueued on post edit screens
   - Test media library integration works correctly

### Browser Compatibility Testing

Test in the following browsers:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

Test the following features:
- Tab navigation
- Character counters
- SERP preview
- Media picker
- Schema field toggling
- REST API calls
- Form submission

### Performance Testing

1. **Debouncing Effectiveness**:
   - Verify analysis is not triggered more than once per second
   - Verify SERP preview updates are not triggered more than once per 100ms

2. **DOM Manipulation**:
   - Verify tab switching does not cause layout thrashing
   - Verify character counter updates do not cause reflows

3. **Memory Leaks**:
   - Verify event handlers are properly cleaned up
   - Verify no memory leaks after repeated tab switching

### Accessibility Testing

1. **Keyboard Navigation**:
   - Test tab navigation is keyboard accessible
   - Test all buttons are keyboard accessible
   - Test media picker is keyboard accessible

2. **Screen Reader Compatibility**:
   - Test tab labels are announced correctly
   - Test character counter feedback is announced
   - Test error messages are announced

3. **ARIA Attributes**:
   - Test tab navigation uses correct ARIA roles
   - Test form fields have correct labels
   - Test error messages are associated with fields

