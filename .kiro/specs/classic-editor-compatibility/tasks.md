# Implementation Plan: Classic Editor Compatibility

## Overview

This implementation brings the MeowSEO Classic Editor meta box to full feature parity with the Gutenberg sidebar. The work involves extending the PHP meta box renderer, building a JavaScript controller for client-side interactions, and adding CSS styling. All data operations use existing REST API endpoints.

**Technology Stack**: PHP-rendered HTML, Vanilla JavaScript (jQuery), CSS

**Key Files**:
- `includes/modules/meta/class-classic-editor.php` - PHP Meta Box Renderer
- `assets/js/classic-editor.js` - JavaScript Controller
- `assets/css/classic-editor.css` - CSS Styling

## Tasks

- [x] 1. Set up PHP Meta Box foundation
  - Extend `includes/modules/meta/class-classic-editor.php` with tabbed HTML structure
  - Add nonce field and form wrapper for data persistence
  - Register meta box for all public post types
  - _Requirements: 1.1, 25.5_

- [x] 2. Implement script and style enqueuing
  - [x] 2.1 Enqueue JavaScript and CSS assets on post edit screens only
    - Hook `admin_enqueue_scripts` to enqueue on `post.php` and `post-new.php` only
    - Enqueue `classic-editor.js` with jQuery dependency
    - Enqueue `classic-editor.css` stylesheet
    - Call `wp_enqueue_media()` for media library support
    - _Requirements: 25.1, 25.2, 25.3_

  - [x] 2.2 Localize script with required data
    - Pass `postId`, `nonce`, `restUrl`, `postTitle`, `postExcerpt`, `siteUrl`
    - _Requirements: 25.4, 26.1_

- [x] 3. Implement tab navigation structure
  - [x] 3.1 Render tab navigation HTML in PHP
    - Create tab button container with four tabs: General, Social, Schema, Advanced
    - Create corresponding tab panel containers
    - Add CSS classes for active state styling
    - _Requirements: 1.1_

  - [x] 3.2 Implement JavaScript tab switching
    - Add click handlers to tab buttons
    - Show/hide corresponding tab panels on click
    - Add/remove active CSS class on tab buttons
    - _Requirements: 1.2, 1.5_

  - [x] 3.3 Implement tab state persistence
    - Save active tab to localStorage on switch
    - Restore active tab from localStorage on page load
    - _Requirements: 1.3, 1.4_

- [x] 4. Implement General Tab fields
  - [x] 4.1 Add SEO Title field with character counter
    - Render text input for SEO Title
    - Add character counter display element
    - Implement real-time counter update on input
    - Apply color-coded CSS classes based on thresholds (ok: 30-60, warn: <30 or 61-70, bad: >70)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 4.2 Add Meta Description field with character counter
    - Render textarea for Meta Description
    - Add character counter display element
    - Implement real-time counter update on input
    - Apply color-coded CSS classes based on thresholds (ok: 120-155, warn: <120 or 156-170, bad: >170)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 4.3 Add Focus Keyword field
    - Render text input for Focus Keyword
    - _Requirements: 6.1, 6.3_

  - [x] 4.4 Add Direct Answer field
    - Render textarea for Direct Answer snippet
    - Position above SERP Preview component
    - _Requirements: 5.1, 5.2, 5.4_

- [x] 5. Implement SERP Preview component
  - [x] 5.1 Render SERP Preview HTML structure
    - Create container with URL, title, and description elements
    - Display URL in "domain.com › slug" format
    - _Requirements: 4.1, 4.6_

  - [x] 5.2 Implement real-time SERP Preview updates
    - Update title display when SEO Title changes
    - Update description display when Meta Description changes
    - Truncate title at 60 characters with ellipsis
    - Truncate description at 155 characters with ellipsis
    - Debounce updates by 100ms for performance
    - _Requirements: 4.2, 4.3, 4.4, 4.5, 30.2_

- [x] 6. Implement SEO and Readability Analysis integration
  - [x] 6.1 Add Analysis panel HTML structure
    - Create container for SEO analysis results
    - Create container for Readability analysis results
    - Add "Run Analysis" button
    - Add composite score badge display
    - _Requirements: 7.3, 7.5, 8.3, 8.5_

  - [x] 6.2 Implement analysis REST API integration
    - Call GET `/meowseo/v1/analysis/{post_id}` endpoint
    - Include nonce in X-WP-Nonce header
    - Trigger analysis on field changes with 1-second debounce
    - Trigger analysis on "Run Analysis" button click
    - _Requirements: 7.1, 7.2, 7.6, 8.1, 8.2, 26.2, 30.1_

  - [x] 6.3 Render analysis results
    - Display all 11 SEO checks with colored indicators
    - Display all 5 readability checks with colored indicators
    - Apply green/orange/red indicators based on pass/warn/fail
    - Display composite scores as colored badges
    - _Requirements: 7.4, 8.4_

- [x] 7. Implement AI Generation buttons
  - [x] 7.1 Add AI Generate button for SEO Title
    - Render button next to SEO Title field
    - Call POST `/meowseo/v1/ai/generate` with `{post_id, type: 'title'}`
    - Show loading spinner and disable button during request
    - Populate SEO Title field with generated content on success
    - Display error message on failure
    - _Requirements: 22.1, 22.2, 22.3, 22.4, 22.6_

  - [x] 7.2 Add AI Generate button for Meta Description
    - Render button next to Meta Description field
    - Call POST `/meowseo/v1/ai/generate` with `{post_id, type: 'description'}`
    - Show loading spinner and disable button during request
    - Populate Meta Description field with generated content on success
    - Update SERP Preview after generation
    - Display error message on failure
    - _Requirements: 23.1, 23.2, 23.3, 23.4, 23.5, 23.6_

- [x] 8. Implement Social Tab fields
  - [x] 8.1 Add Open Graph fields
    - Render OG Title text input
    - Render OG Description textarea
    - Render OG Image selector with preview, select button, and remove button
    - Add hidden input for OG Image ID
    - _Requirements: 9.1, 9.2, 9.3_

  - [x] 8.2 Implement OG Image media picker
    - Open WordPress media library on "Select Image" click
    - Write attachment ID to hidden input on selection
    - Display image preview (max-width: 200px) on selection
    - Clear image ID and hide preview on "Remove" click
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [x] 8.3 Add Twitter Card fields
    - Render Twitter Title text input
    - Render Twitter Description textarea
    - Render Twitter Image selector with preview, select button, and remove button
    - Add hidden input for Twitter Image ID
    - _Requirements: 11.1, 11.2, 11.3_

  - [x] 8.4 Implement Twitter Image media picker
    - Open WordPress media library on "Select Image" click
    - Write attachment ID to hidden input on selection
    - Display image preview (max-width: 200px) on selection
    - Clear image ID and hide preview on "Remove" click
    - _Requirements: 12.1, 12.2, 12.3, 12.4_

  - [x] 8.5 Implement "Use OG for Twitter" toggle
    - Render checkbox for "Use OG data for Twitter"
    - Disable Twitter fields when checkbox is checked
    - Copy OG values to Twitter fields when checked and saved
    - _Requirements: 13.1, 13.2, 13.3_

- [x] 9. Implement Schema Tab fields
  - [x] 9.1 Add Schema Type selector
    - Render dropdown with options: None, Article, FAQ Page, HowTo, Local Business, Product
    - Show corresponding field group on selection
    - Hide all other field groups on selection
    - _Requirements: 14.1, 14.2, 14.3, 14.4_

  - [x] 9.2 Implement Article schema fields
    - Render Article Type selector (Article, NewsArticle, BlogPosting)
    - Show fields only when "Article" schema type is selected
    - _Requirements: 15.1, 15.2_

  - [x] 9.3 Implement FAQ schema fields
    - Render repeating question-answer pair fields
    - Add "Add Question" button to add new pairs
    - Add "Remove" button for each pair
    - Show fields only when "FAQPage" schema type is selected
    - _Requirements: 16.1, 16.2, 16.3, 16.4_

  - [x] 9.4 Implement HowTo schema fields
    - Render Name and Description fields
    - Render repeating step fields (step name and step text)
    - Add "Add Step" button to add new steps
    - Add "Remove" button for each step
    - Show fields only when "HowTo" schema type is selected
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_

  - [x] 9.5 Implement LocalBusiness schema fields
    - Render Business Name, Business Type, Address, Phone, and Hours fields
    - Show fields only when "Local Business" schema type is selected
    - _Requirements: 18.1, 18.2_

  - [x] 9.6 Implement Product schema fields
    - Render Name, Description, SKU, Price, Currency, and Availability fields
    - Show fields only when "Product" schema type is selected
    - _Requirements: 19.1, 19.2_

- [x] 10. Implement Advanced Tab fields
  - [x] 10.1 Add Canonical URL field
    - Render text input for Canonical URL
    - _Requirements: 20.1_

  - [x] 10.2 Add Robots Meta Tag checkboxes
    - Render "noindex" checkbox
    - Render "nofollow" checkbox
    - _Requirements: 21.1, 21.2_

  - [x] 10.3 Implement GSC Submit button
    - Render "Submit to Google" button
    - Display last submitted date (or "Never")
    - Call POST `/meowseo/v1/gsc/submit` on button click
    - Show loading spinner and disable button during request
    - Update last submitted date on success
    - Display error message on failure
    - _Requirements: 24.1, 24.2, 24.3, 24.4, 24.5, 24.6_

- [x] 11. Implement data persistence (PHP save handler)
  - [x] 11.1 Add save_meta method to handle form submission
    - Verify nonce before processing
    - Check user permissions
    - Skip autosaves and revisions
    - _Requirements: 26.1, 27.1_

  - [x] 11.2 Sanitize and save all string fields
    - Save SEO Title to `_meowseo_title` with `sanitize_text_field`
    - Save Meta Description to `_meowseo_description` with `sanitize_textarea_field`
    - Save Focus Keyword to `_meowseo_focus_keyword` with `sanitize_text_field`
    - Save Direct Answer to `_meowseo_direct_answer` with `sanitize_textarea_field`
    - Save OG Title to `_meowseo_og_title` with `sanitize_text_field`
    - Save OG Description to `_meowseo_og_description` with `sanitize_textarea_field`
    - Save Twitter Title to `_meowseo_twitter_title` with `sanitize_text_field`
    - Save Twitter Description to `_meowseo_twitter_description` with `sanitize_textarea_field`
    - Save Canonical URL to `_meowseo_canonical` with `esc_url_raw`
    - Save Schema Type to `_meowseo_schema_type` with `sanitize_text_field`
    - _Requirements: 2.6, 3.6, 6.2, 5.3, 9.4, 9.5, 11.4, 11.5, 20.2, 14.5, 27.2_

  - [x] 11.3 Sanitize and save integer and boolean fields
    - Save OG Image ID to `_meowseo_og_image_id` with `absint`
    - Save Twitter Image ID to `_meowseo_twitter_image_id` with `absint`
    - Save "Use OG for Twitter" to `_meowseo_use_og_for_twitter` with `rest_sanitize_boolean`
    - Save noindex to `_meowseo_robots_noindex` with `rest_sanitize_boolean`
    - Save nofollow to `_meowseo_robots_nofollow` with `rest_sanitize_boolean`
    - _Requirements: 9.6, 11.6, 13.4, 21.3, 21.4, 27.3_

  - [x] 11.4 Sanitize and save schema configuration JSON
    - Validate JSON structure before saving
    - Save to `_meowseo_schema_config` postmeta field
    - Handle Article, FAQ, HowTo, LocalBusiness, and Product configurations
    - _Requirements: 15.3, 16.5, 17.6, 18.3, 19.3, 27.4_

  - [x] 11.5 Populate fields with saved postmeta values on load
    - Retrieve all postmeta values in render_meta_box
    - Populate all form fields with existing values
    - _Requirements: 27.5_

✅ **COMPLETED** - All data persistence functionality implemented with proper nonce verification, field sanitization, and postmeta persistence.

- [x] 12. Implement CSS styling
  - [x] 12.1 Add tab navigation styles
    - Style tab button container and buttons
    - Style active tab state
    - Style tab panel visibility
    - _Requirements: 1.5_

  - [x] 12.2 Add character counter styles
    - Style counter display
    - Add color coding for ok (green), warn (orange), bad (red)
    - _Requirements: 2.3, 2.4, 2.5, 3.3, 3.4, 3.5_

  - [x] 12.3 Add SERP Preview styles
    - Style preview container
    - Style URL, title, and description elements
    - Match Google SERP appearance
    - _Requirements: 4.1_

  - [x] 12.4 Add image picker styles
    - Style image preview container
    - Style select and remove buttons
    - Handle has-image state
    - _Requirements: 10.3, 12.3_

  - [x] 12.5 Add schema field styles
    - Style schema type selector
    - Style conditional field groups
    - Style repeating field buttons
    - _Requirements: 14.2, 14.3, 14.4_

  - [x] 12.6 Add analysis panel styles
    - Style analysis result items
    - Style colored indicators
    - Style score badges
    - _Requirements: 7.4, 7.5, 8.4, 8.5_

- [x] 13. Implement error handling
  - [x] 13.1 Add client-side error handling
    - Display user-friendly error messages for REST API failures
    - Display error when media library is unavailable
    - Display validation error for invalid schema JSON
    - Log JavaScript errors to browser console
    - _Requirements: 29.1, 29.2, 29.3, 29.4_

  - [x] 13.2 Add REST API authentication error handling
    - Display error message for authentication failures
    - Include nonce in all REST API requests
    - _Requirements: 26.2, 26.3_

- [x] 14. Checkpoint - Ensure all core functionality works
  - Ensure all tests pass, ask the user if questions arise.
  - Verify tab navigation works with persistence
  - Verify character counters display correct colors
  - Verify SERP preview updates in real-time
  - Verify media pickers work correctly
  - Verify schema field toggling works
  - Verify form submission saves all fields

- [x] 15. Write PHP unit tests
  - [x] 15.1 Write tests for meta box registration
    - Test meta box is registered for all public post types
    - Test meta box callback is correctly set
    - _Requirements: 25.5_

  - [x] 15.2 Write tests for script enqueuing
    - Test scripts are enqueued only on post.php and post-new.php
    - Test CSS is enqueued only on post edit screens
    - Test wp_enqueue_media is called
    - Test localized script data contains all required keys
    - _Requirements: 25.1, 25.2, 25.3, 25.4_

  - [x] 15.3 Write tests for data persistence
    - Test all string fields are saved correctly
    - Test all boolean fields are saved correctly
    - Test all integer fields are saved correctly
    - Test all inputs are properly sanitized
    - Test nonce verification is enforced
    - Test permission checks are enforced
    - Test autosaves and revisions are skipped
    - _Requirements: 27.1, 27.2, 27.3, 27.4, 27.5_

  - [x] 15.4 Write tests for schema config sanitization
    - Test valid JSON is preserved
    - Test invalid JSON returns empty string
    - Test empty input returns empty string
    - _Requirements: 27.4_

- [x] 16. Write JavaScript unit tests
  - [x] 16.1 Write tests for tab navigation
    - Test tab switching updates active class
    - Test tab switching shows/hides correct panels
    - Test active tab is saved to localStorage
    - Test active tab is restored from localStorage
    - _Requirements: 1.2, 1.3, 1.4, 1.5_

  - [x] 16.2 Write tests for character counters
    - Test counter updates on input
    - Test counter displays correct character count
    - Test title threshold classes (ok, warn, bad)
    - Test description threshold classes (ok, warn, bad)
    - _Requirements: 2.2, 2.3, 2.4, 2.5, 3.2, 3.3, 3.4, 3.5_

  - [x] 16.3 Write tests for SERP Preview
    - Test preview updates when title changes
    - Test preview updates when description changes
    - Test title truncation at 60 characters
    - Test description truncation at 155 characters
    - _Requirements: 4.2, 4.3, 4.4, 4.5_

  - [x] 16.4 Write tests for media picker
    - Test "Select Image" opens media library
    - Test selecting image updates hidden input
    - Test selecting image displays preview
    - Test "Remove" clears image and hides preview
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 12.1, 12.2, 12.3, 12.4_

  - [x] 16.5 Write tests for schema field toggling
    - Test changing schema type shows correct field group
    - Test changing schema type hides other field groups
    - Test selecting "None" hides all field groups
    - _Requirements: 14.2, 14.3, 14.4_

  - [x] 16.6 Write tests for debouncing
    - Test analysis is debounced by 1 second
    - Test SERP preview updates are debounced by 100ms
    - _Requirements: 7.1, 8.1, 30.1, 30.2_

- [x] 17. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
  - Run full PHPUnit test suite
  - Run full Jest test suite
  - Verify browser compatibility in Chrome, Firefox, Safari, Edge
  - _Requirements: 28.1, 28.2, 28.3, 28.4, 28.5_

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- All REST API endpoints already exist—no backend work required
- Implementation uses PHP-rendered HTML and vanilla JavaScript (jQuery)—no React
- All postmeta fields are already registered with `show_in_rest` enabled
