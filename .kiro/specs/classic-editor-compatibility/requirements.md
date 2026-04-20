# Requirements Document

## Introduction

The MeowSEO plugin currently provides a minimal Classic Editor meta box with only 5 basic fields (SEO Title, Meta Description, Focus Keyword, Canonical URL, and Robots checkboxes). This feature will bring the Classic Editor experience to full parity with both the existing Gutenberg sidebar implementation and what competitors (Yoast SEO Premium and RankMath Pro) offer in their classic editor meta boxes.

The Classic Editor does not load React or WordPress block editor packages, so the implementation must be built with PHP-rendered HTML and vanilla JavaScript (jQuery). All data operations will go through existing MeowSEO REST API endpoints. No new PHP business logic is needed—only the UI layer.

## Glossary

- **Classic_Editor**: The WordPress Classic Editor interface for editing posts and pages
- **Meta_Box**: A WordPress admin UI component that displays additional fields in the post editor
- **SERP_Preview**: A visual simulation of how a page will appear in Google search results
- **Character_Counter**: A UI component that displays the current character count of a text field with color-coded feedback
- **Tab_Navigation**: A UI pattern that organizes content into multiple panels accessible via tabs
- **Analysis_Engine**: The MeowSEO component that performs SEO and readability checks
- **REST_API**: The MeowSEO REST API endpoints for data operations
- **Media_Picker**: WordPress media library interface for selecting images
- **Schema_Type**: A structured data type (Article, FAQ, HowTo, LocalBusiness, Product)
- **AI_Generator**: The MeowSEO AI module that generates SEO content
- **GSC**: Google Search Console
- **OG**: Open Graph protocol for social media sharing
- **Twitter_Card**: Twitter's metadata format for rich social media previews

## Requirements

### Requirement 1: Tab Navigation Structure

**User Story:** As a content editor using Classic Editor, I want to access all SEO features through an organized tabbed interface, so that I can efficiently manage different aspects of SEO without being overwhelmed by a single long form.

#### Acceptance Criteria

1. THE Meta_Box SHALL render four tabs: General, Social, Schema, and Advanced
2. WHEN a user clicks a tab button, THE Meta_Box SHALL display the corresponding tab panel and hide all other panels
3. THE Meta_Box SHALL store the active tab selection in browser localStorage
4. WHEN the post editor page loads, THE Meta_Box SHALL restore the previously active tab from localStorage
5. THE Meta_Box SHALL mark the active tab button with a visual indicator (CSS class "active")

### Requirement 2: SEO Title Field with Character Counter

**User Story:** As a content editor, I want to see real-time character count feedback on my SEO title, so that I can optimize it for search engine display limits.

#### Acceptance Criteria

1. THE General_Tab SHALL display an SEO Title input field
2. WHEN a user types in the SEO Title field, THE Character_Counter SHALL update the displayed character count in real-time
3. WHEN the character count is between 30 and 60, THE Character_Counter SHALL display green feedback (CSS class "meowseo-ok")
4. WHEN the character count is less than 30 or between 61 and 70, THE Character_Counter SHALL display orange feedback (CSS class "meowseo-warn")
5. WHEN the character count is greater than 70, THE Character_Counter SHALL display red feedback (CSS class "meowseo-bad")
6. WHEN the post is saved, THE Meta_Box SHALL save the SEO Title value to the "_meowseo_title" postmeta field

### Requirement 3: Meta Description Field with Character Counter

**User Story:** As a content editor, I want to see real-time character count feedback on my meta description, so that I can optimize it for search engine display limits.

#### Acceptance Criteria

1. THE General_Tab SHALL display a Meta Description textarea field
2. WHEN a user types in the Meta Description field, THE Character_Counter SHALL update the displayed character count in real-time
3. WHEN the character count is between 120 and 155, THE Character_Counter SHALL display green feedback (CSS class "meowseo-ok")
4. WHEN the character count is less than 120 or between 156 and 170, THE Character_Counter SHALL display orange feedback (CSS class "meowseo-warn")
5. WHEN the character count is greater than 170, THE Character_Counter SHALL display red feedback (CSS class "meowseo-bad")
6. WHEN the post is saved, THE Meta_Box SHALL save the Meta Description value to the "_meowseo_description" postmeta field

### Requirement 4: Real-Time SERP Preview

**User Story:** As a content editor, I want to see a live preview of how my page will appear in Google search results, so that I can visualize the impact of my SEO title and description changes.

#### Acceptance Criteria

1. THE General_Tab SHALL display a SERP_Preview component showing URL, title, and description
2. WHEN a user types in the SEO Title field, THE SERP_Preview SHALL update the title display in real-time
3. WHEN a user types in the Meta Description field, THE SERP_Preview SHALL update the description display in real-time
4. WHEN the SEO Title exceeds 60 characters, THE SERP_Preview SHALL truncate the title display with an ellipsis
5. WHEN the Meta Description exceeds 155 characters, THE SERP_Preview SHALL truncate the description display with an ellipsis
6. THE SERP_Preview SHALL display the post URL in the format "domain.com › slug"

### Requirement 5: Direct Answer Field

**User Story:** As a content editor, I want to provide a direct answer snippet for featured snippet optimization, so that my content has a better chance of appearing in Google's featured snippets.

#### Acceptance Criteria

1. THE General_Tab SHALL display a Direct Answer textarea field
2. THE Direct_Answer_Field SHALL be positioned above the SERP_Preview component
3. WHEN the post is saved, THE Meta_Box SHALL save the Direct Answer value to the "_meowseo_direct_answer" postmeta field
4. THE Direct_Answer_Field SHALL accept multi-line text input

### Requirement 6: Focus Keyword Field

**User Story:** As a content editor, I want to specify a focus keyword for SEO analysis, so that the analysis engine can evaluate my content optimization.

#### Acceptance Criteria

1. THE General_Tab SHALL display a Focus Keyword input field
2. WHEN the post is saved, THE Meta_Box SHALL save the Focus Keyword value to the "_meowseo_focus_keyword" postmeta field
3. THE Focus_Keyword_Field SHALL accept a single keyword or phrase

### Requirement 7: SEO Analysis Engine Integration

**User Story:** As a content editor, I want to see real-time SEO analysis results with color-coded feedback, so that I can identify and fix SEO issues before publishing.

#### Acceptance Criteria

1. WHEN a user modifies any field in the General_Tab, THE Meta_Box SHALL trigger SEO analysis after a 1-second debounce
2. THE Meta_Box SHALL call the REST_API endpoint GET /meowseo/v1/analysis/{post_id} to retrieve analysis results
3. THE General_Tab SHALL display an SEO Analysis panel showing all 11 SEO checks
4. FOR EACH SEO check result, THE Analysis_Panel SHALL display a colored indicator (green for pass, orange for warning, red for fail)
5. THE Analysis_Panel SHALL display a composite SEO score (0-100) as a colored badge
6. THE General_Tab SHALL display a "Run Analysis" button that triggers immediate analysis when clicked

### Requirement 8: Readability Analysis Engine Integration

**User Story:** As a content editor, I want to see real-time readability analysis results, so that I can ensure my content is accessible and easy to read.

#### Acceptance Criteria

1. WHEN a user modifies any field in the General_Tab, THE Meta_Box SHALL trigger readability analysis after a 1-second debounce
2. THE Meta_Box SHALL call the REST_API endpoint GET /meowseo/v1/analysis/{post_id} to retrieve analysis results
3. THE General_Tab SHALL display a Readability Analysis panel showing all 5 readability checks
4. FOR EACH readability check result, THE Analysis_Panel SHALL display a colored indicator (green for pass, orange for warning, red for fail)
5. THE Analysis_Panel SHALL display a composite readability score (0-100) as a colored badge

### Requirement 9: Open Graph Fields

**User Story:** As a content editor, I want to customize Open Graph metadata for Facebook sharing, so that my content displays correctly when shared on Facebook.

#### Acceptance Criteria

1. THE Social_Tab SHALL display an OG Title input field
2. THE Social_Tab SHALL display an OG Description textarea field
3. THE Social_Tab SHALL display an OG Image selector with preview, select button, and remove button
4. WHEN the post is saved, THE Meta_Box SHALL save OG Title to "_meowseo_og_title" postmeta field
5. WHEN the post is saved, THE Meta_Box SHALL save OG Description to "_meowseo_og_description" postmeta field
6. WHEN the post is saved, THE Meta_Box SHALL save OG Image ID to "_meowseo_og_image_id" postmeta field

### Requirement 10: Open Graph Image Picker

**User Story:** As a content editor, I want to select an Open Graph image from the WordPress media library, so that I can control the image displayed when my content is shared on Facebook.

#### Acceptance Criteria

1. WHEN a user clicks the "Select Image" button in the OG Image field, THE Media_Picker SHALL open the WordPress media library
2. WHEN a user selects an image from the media library, THE Meta_Box SHALL write the attachment ID to the hidden OG Image ID input field
3. WHEN a user selects an image from the media library, THE Meta_Box SHALL display a preview of the selected image (max-width: 200px)
4. WHEN a user clicks the "Remove" button, THE Meta_Box SHALL clear the OG Image ID field and hide the image preview
5. THE Meta_Box SHALL call wp_enqueue_media() to ensure the media library is available

### Requirement 11: Twitter Card Fields

**User Story:** As a content editor, I want to customize Twitter Card metadata, so that my content displays correctly when shared on Twitter.

#### Acceptance Criteria

1. THE Social_Tab SHALL display a Twitter Title input field
2. THE Social_Tab SHALL display a Twitter Description textarea field
3. THE Social_Tab SHALL display a Twitter Image selector with preview, select button, and remove button
4. WHEN the post is saved, THE Meta_Box SHALL save Twitter Title to "_meowseo_twitter_title" postmeta field
5. WHEN the post is saved, THE Meta_Box SHALL save Twitter Description to "_meowseo_twitter_description" postmeta field
6. WHEN the post is saved, THE Meta_Box SHALL save Twitter Image ID to "_meowseo_twitter_image_id" postmeta field

### Requirement 12: Twitter Card Image Picker

**User Story:** As a content editor, I want to select a Twitter Card image from the WordPress media library, so that I can control the image displayed when my content is shared on Twitter.

#### Acceptance Criteria

1. WHEN a user clicks the "Select Image" button in the Twitter Image field, THE Media_Picker SHALL open the WordPress media library
2. WHEN a user selects an image from the media library, THE Meta_Box SHALL write the attachment ID to the hidden Twitter Image ID input field
3. WHEN a user selects an image from the media library, THE Meta_Box SHALL display a preview of the selected image (max-width: 200px)
4. WHEN a user clicks the "Remove" button, THE Meta_Box SHALL clear the Twitter Image ID field and hide the image preview

### Requirement 13: Use Open Graph Data for Twitter Toggle

**User Story:** As a content editor, I want to automatically use my Open Graph data for Twitter Cards, so that I don't have to duplicate the same information.

#### Acceptance Criteria

1. THE Social_Tab SHALL display a "Use OG data for Twitter" checkbox
2. WHEN the checkbox is checked, THE Meta_Box SHALL disable the Twitter Title, Twitter Description, and Twitter Image fields
3. WHEN the checkbox is checked and the post is saved, THE Meta_Box SHALL copy OG values to Twitter fields
4. WHEN the post is saved, THE Meta_Box SHALL save the checkbox state to "_meowseo_use_og_for_twitter" postmeta field

### Requirement 14: Schema Type Selector

**User Story:** As a content editor, I want to select a schema type for my content, so that search engines can understand the structured data on my page.

#### Acceptance Criteria

1. THE Schema_Tab SHALL display a Schema Type dropdown selector
2. THE Schema_Type_Selector SHALL include options: None, Article, FAQ Page, HowTo, Local Business, Product
3. WHEN a user selects a schema type, THE Schema_Tab SHALL display the corresponding schema field group
4. WHEN a user selects a schema type, THE Schema_Tab SHALL hide all other schema field groups
5. WHEN the post is saved, THE Meta_Box SHALL save the selected schema type to "_meowseo_schema_type" postmeta field

### Requirement 15: Article Schema Fields

**User Story:** As a content editor, I want to configure Article schema fields, so that my articles are properly structured for search engines.

#### Acceptance Criteria

1. WHEN the schema type is "Article", THE Schema_Tab SHALL display an Article Type selector (Article, NewsArticle, BlogPosting)
2. THE Article_Schema_Fields SHALL be hidden when any other schema type is selected
3. WHEN the post is saved, THE Meta_Box SHALL save Article schema configuration to "_meowseo_schema_config" postmeta field as JSON

### Requirement 16: FAQ Schema Fields

**User Story:** As a content editor, I want to configure FAQ schema with multiple question-answer pairs, so that my FAQ content can appear in rich search results.

#### Acceptance Criteria

1. WHEN the schema type is "FAQPage", THE Schema_Tab SHALL display repeating question-answer pair fields
2. THE FAQ_Schema_Fields SHALL include an "Add Question" button that adds a new question-answer pair
3. THE FAQ_Schema_Fields SHALL include a "Remove" button for each question-answer pair
4. THE FAQ_Schema_Fields SHALL be hidden when any other schema type is selected
5. WHEN the post is saved, THE Meta_Box SHALL save FAQ schema configuration to "_meowseo_schema_config" postmeta field as JSON

### Requirement 17: HowTo Schema Fields

**User Story:** As a content editor, I want to configure HowTo schema with multiple steps, so that my tutorial content can appear in rich search results.

#### Acceptance Criteria

1. WHEN the schema type is "HowTo", THE Schema_Tab SHALL display Name and Description fields
2. THE HowTo_Schema_Fields SHALL display repeating step fields (step name and step text)
3. THE HowTo_Schema_Fields SHALL include an "Add Step" button that adds a new step
4. THE HowTo_Schema_Fields SHALL include a "Remove" button for each step
5. THE HowTo_Schema_Fields SHALL be hidden when any other schema type is selected
6. WHEN the post is saved, THE Meta_Box SHALL save HowTo schema configuration to "_meowseo_schema_config" postmeta field as JSON

### Requirement 18: LocalBusiness Schema Fields

**User Story:** As a content editor, I want to configure LocalBusiness schema fields, so that my business information appears correctly in local search results.

#### Acceptance Criteria

1. WHEN the schema type is "Local Business", THE Schema_Tab SHALL display Business Name, Business Type, Address, Phone, and Hours fields
2. THE LocalBusiness_Schema_Fields SHALL be hidden when any other schema type is selected
3. WHEN the post is saved, THE Meta_Box SHALL save LocalBusiness schema configuration to "_meowseo_schema_config" postmeta field as JSON

### Requirement 19: Product Schema Fields

**User Story:** As a content editor, I want to configure Product schema fields, so that my product information appears correctly in search results.

#### Acceptance Criteria

1. WHEN the schema type is "Product", THE Schema_Tab SHALL display Name, Description, SKU, Price, Currency, and Availability fields
2. THE Product_Schema_Fields SHALL be hidden when any other schema type is selected
3. WHEN the post is saved, THE Meta_Box SHALL save Product schema configuration to "_meowseo_schema_config" postmeta field as JSON

### Requirement 20: Canonical URL Field

**User Story:** As a content editor, I want to specify a canonical URL for my content, so that I can prevent duplicate content issues.

#### Acceptance Criteria

1. THE Advanced_Tab SHALL display a Canonical URL input field
2. WHEN the post is saved, THE Meta_Box SHALL save the Canonical URL value to "_meowseo_canonical" postmeta field

### Requirement 21: Robots Meta Tags

**User Story:** As a content editor, I want to control search engine indexing with robots meta tags, so that I can prevent specific pages from being indexed or followed.

#### Acceptance Criteria

1. THE Advanced_Tab SHALL display a "noindex" checkbox
2. THE Advanced_Tab SHALL display a "nofollow" checkbox
3. WHEN the post is saved, THE Meta_Box SHALL save the noindex state to "_meowseo_robots_noindex" postmeta field
4. WHEN the post is saved, THE Meta_Box SHALL save the nofollow state to "_meowseo_robots_nofollow" postmeta field

### Requirement 22: AI Title Generation

**User Story:** As a content editor, I want to generate an SEO title using AI, so that I can quickly create optimized titles without manual effort.

#### Acceptance Criteria

1. THE General_Tab SHALL display an "AI Generate" button next to the SEO Title field
2. WHEN a user clicks the AI Generate button, THE Meta_Box SHALL call the REST_API endpoint POST /meowseo/v1/ai/generate with parameters {post_id, type: 'title'}
3. WHEN the AI generation request is in progress, THE Meta_Box SHALL display a loading spinner and disable the button
4. WHEN the AI generation completes, THE Meta_Box SHALL populate the SEO Title field with the generated content
5. WHEN the AI generation completes, THE SERP_Preview SHALL update to reflect the new title
6. IF the AI generation fails, THEN THE Meta_Box SHALL display an error message to the user

### Requirement 23: AI Description Generation

**User Story:** As a content editor, I want to generate a meta description using AI, so that I can quickly create optimized descriptions without manual effort.

#### Acceptance Criteria

1. THE General_Tab SHALL display an "AI Generate" button next to the Meta Description field
2. WHEN a user clicks the AI Generate button, THE Meta_Box SHALL call the REST_API endpoint POST /meowseo/v1/ai/generate with parameters {post_id, type: 'description'}
3. WHEN the AI generation request is in progress, THE Meta_Box SHALL display a loading spinner and disable the button
4. WHEN the AI generation completes, THE Meta_Box SHALL populate the Meta Description field with the generated content
5. WHEN the AI generation completes, THE SERP_Preview SHALL update to reflect the new description
6. IF the AI generation fails, THEN THE Meta_Box SHALL display an error message to the user

### Requirement 24: Google Search Console Submit

**User Story:** As a content editor, I want to submit my page to Google Search Console for indexing, so that my content appears in search results faster.

#### Acceptance Criteria

1. THE Advanced_Tab SHALL display a "Submit to Google" button
2. THE Advanced_Tab SHALL display the last submitted date (or "Never" if not previously submitted)
3. WHEN a user clicks the Submit to Google button, THE Meta_Box SHALL call the REST_API endpoint POST /meowseo/v1/gsc/submit with the post_id parameter
4. WHEN the GSC submit request is in progress, THE Meta_Box SHALL display a loading spinner and disable the button
5. WHEN the GSC submit completes successfully, THE Meta_Box SHALL update the last submitted date display
6. IF the GSC submit fails, THEN THE Meta_Box SHALL display an error message to the user

### Requirement 25: Script and Style Enqueuing

**User Story:** As a developer, I want all JavaScript and CSS assets to be properly enqueued only on post edit screens, so that the Classic Editor meta box functions correctly without affecting other admin pages.

#### Acceptance Criteria

1. THE Classic_Editor SHALL enqueue the classic-editor.js script only on post.php and post-new.php admin pages
2. THE Classic_Editor SHALL enqueue the classic-editor.css stylesheet only on post.php and post-new.php admin pages
3. THE Classic_Editor SHALL call wp_enqueue_media() to ensure the WordPress media library is available
4. THE Classic_Editor SHALL localize the script with postId, nonce, and restUrl variables
5. THE Classic_Editor SHALL register the enqueue callback via the admin_enqueue_scripts action hook

### Requirement 26: REST API Authentication

**User Story:** As a developer, I want all REST API calls to be properly authenticated with WordPress nonces, so that the Classic Editor meta box can securely communicate with the backend.

#### Acceptance Criteria

1. THE Meta_Box SHALL include a WordPress REST API nonce in all AJAX requests
2. THE Meta_Box SHALL pass the nonce in the request headers or parameters as required by the REST_API
3. IF a REST API call returns an authentication error, THEN THE Meta_Box SHALL display an error message to the user

### Requirement 27: Data Persistence

**User Story:** As a content editor, I want all my SEO metadata to be saved when I save or publish a post, so that my SEO configuration is preserved.

#### Acceptance Criteria

1. WHEN a user saves or publishes a post, THE Meta_Box SHALL save all field values to their corresponding postmeta fields
2. THE Meta_Box SHALL sanitize all text input fields before saving
3. THE Meta_Box SHALL sanitize all integer fields (image IDs) using absint before saving
4. THE Meta_Box SHALL sanitize schema configuration JSON before saving
5. WHEN the post editor loads, THE Meta_Box SHALL populate all fields with their saved postmeta values

### Requirement 28: Browser Compatibility

**User Story:** As a content editor, I want the Classic Editor meta box to work in all modern browsers, so that I can use my preferred browser without issues.

#### Acceptance Criteria

1. THE Meta_Box SHALL function correctly in Chrome (latest version)
2. THE Meta_Box SHALL function correctly in Firefox (latest version)
3. THE Meta_Box SHALL function correctly in Safari (latest version)
4. THE Meta_Box SHALL function correctly in Edge (latest version)
5. THE Meta_Box SHALL use standard JavaScript APIs compatible with ES5 or provide appropriate polyfills

### Requirement 29: Error Handling

**User Story:** As a content editor, I want to see clear error messages when something goes wrong, so that I can understand and resolve issues.

#### Acceptance Criteria

1. IF a REST API call fails, THEN THE Meta_Box SHALL display a user-friendly error message
2. IF the media library fails to load, THEN THE Meta_Box SHALL display an error message when the user attempts to select an image
3. IF schema configuration JSON is invalid, THEN THE Meta_Box SHALL display a validation error message
4. THE Meta_Box SHALL log JavaScript errors to the browser console for debugging purposes

### Requirement 30: Performance Optimization

**User Story:** As a content editor, I want the Classic Editor meta box to respond quickly to my actions, so that my editing workflow is not slowed down.

#### Acceptance Criteria

1. THE Meta_Box SHALL debounce analysis triggers by 1 second to prevent excessive API calls
2. THE Meta_Box SHALL debounce SERP preview updates by 100 milliseconds to prevent excessive DOM updates
3. THE Meta_Box SHALL cache analysis results in memory to avoid redundant API calls for unchanged content
4. THE Meta_Box SHALL minimize DOM manipulation by batching updates where possible
