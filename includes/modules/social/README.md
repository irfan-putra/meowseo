# Social Module

The Social Module manages Open Graph and Twitter Card meta tags for social media sharing.

## Features

- **Open Graph Tags**: Outputs og:title, og:description, og:image, og:type, og:url
- **Twitter Card Tags**: Outputs twitter:card, twitter:title, twitter:description, twitter:image
- **Per-Post Overrides**: Custom social title, description, and image via postmeta
- **Fallback Logic**: Intelligent fallback from per-post → featured image → global default
- **REST API**: Full CRUD operations via meowseo/v1/social endpoints
- **Caching**: Object Cache integration for performance

## Architecture

### Files

- `class-social.php` - Main module class implementing Module interface
- `class-social-rest.php` - REST API endpoints for social meta CRUD

### Postmeta Keys

- `meowseo_social_title` (string) - Open Graph / Twitter Card title override
- `meowseo_social_description` (string) - Open Graph / Twitter Card description override
- `meowseo_social_image_id` (int) - Attachment ID for social share image

### Fallback Logic

#### Social Title
1. Per-post social title (`meowseo_social_title`)
2. SEO title (`meowseo_title`)
3. Post title

#### Social Description
1. Per-post social description (`meowseo_social_description`)
2. SEO description (`meowseo_description`)
3. Post excerpt
4. First 155 characters of post content

#### Social Image
1. Per-post social image (`meowseo_social_image_id`)
2. Featured image
3. Global default social image (from Options)

## REST API Endpoints

### GET /meowseo/v1/social/{post_id}

Retrieve social meta for a post.

**Permission**: Post must be publicly viewable

**Response**:
```json
{
  "post_id": 123,
  "social_title": "Custom Social Title",
  "social_description": "Custom social description",
  "social_image_id": 456,
  "social_image_url": "https://example.com/image.jpg"
}
```

### POST /meowseo/v1/social/{post_id}

Update social meta for a post.

**Permission**: User must have `edit_post` capability

**Headers**: `X-WP-Nonce` required

**Body**:
```json
{
  "social_title": "New Social Title",
  "social_description": "New social description",
  "social_image_id": 789
}
```

**Response**:
```json
{
  "success": true,
  "message": "Social meta updated successfully.",
  "post_id": 123
}
```

### DELETE /meowseo/v1/social/{post_id}

Clear all social meta for a post.

**Permission**: User must have `edit_post` capability

**Headers**: `X-WP-Nonce` required

**Response**:
```json
{
  "success": true,
  "message": "Social meta deleted successfully.",
  "post_id": 123
}
```

## Output Example

```html
<!-- Open Graph Tags -->
<meta property="og:title" content="My Awesome Post">
<meta property="og:description" content="This is a great post about...">
<meta property="og:image" content="https://example.com/image.jpg">
<meta property="og:type" content="article">
<meta property="og:url" content="https://example.com/my-awesome-post/">

<!-- Twitter Card Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="My Awesome Post">
<meta name="twitter:description" content="This is a great post about...">
<meta name="twitter:image" content="https://example.com/image.jpg">
```

## Caching

Social data is cached in Object Cache with key pattern: `meowseo_social_{post_id}`

Cache TTL: 1 hour (3600 seconds)

Cache is automatically invalidated on:
- Post save
- Social meta update via REST API
- Social meta deletion via REST API

## Security

- All database queries use prepared statements
- REST endpoints verify nonce for mutations
- REST endpoints verify user capabilities
- All output is properly escaped (esc_attr, esc_url)

## Requirements Satisfied

- **Requirement 11.1**: Outputs Open Graph meta tags in wp_head
- **Requirement 11.2**: Outputs Twitter Card meta tags in wp_head
- **Requirement 11.3**: Per-post overrides via postmeta fields
- **Requirement 11.4**: Fallback logic for social images
- **Requirement 11.5**: REST API exposure via meowseo/v1 namespace

## Testing

Run the verification script:
```php
// In WordPress admin, visit:
// wp-admin/?meowseo_verify_social=1
```

Or manually test:
1. Enable Social module in MeowSEO settings
2. Create/edit a post
3. View page source and verify Open Graph and Twitter Card tags
4. Test REST endpoints with a tool like Postman or curl
