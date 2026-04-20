# Bulk Editor Module

## Overview

The Bulk Editor module provides bulk SEO operations on multiple posts simultaneously, including bulk actions for SEO metadata and CSV export functionality for data analysis.

## Features

### Bulk Actions

The module registers the following bulk actions in the WordPress post list table:

- **Set noindex** - Mark posts as noindex (not to be indexed by search engines)
- **Set index** - Remove noindex flag from posts
- **Set nofollow** - Mark posts with nofollow (don't follow links)
- **Set follow** - Remove nofollow flag from posts
- **Remove canonical URL** - Clear canonical URL metadata
- **Set schema to Article** - Set schema type to Article
- **Set schema to None** - Remove schema type

### CSV Export

Export post data to RFC 4180 compliant CSV format with the following columns:

- ID
- Title
- URL
- Focus Keyword
- Meta Description
- SEO Score
- Noindex
- Nofollow
- Canonical URL
- Schema Type

### Supported Post Types

By default, the module supports:
- Posts
- Pages

Additional post types can be added programmatically using `add_supported_post_type()`.

## Architecture

### Classes

#### Bulk_Editor

Main module class implementing the Module interface.

**Public Methods:**
- `boot()` - Initialize module hooks
- `get_id()` - Return module ID ('bulk')
- `register_bulk_actions(array $bulk_actions)` - Register bulk actions
- `handle_bulk_action(string $redirect_url, string $action, array $post_ids)` - Execute bulk action
- `export_to_csv(array $posts)` - Export posts to CSV
- `get_supported_post_types()` - Get supported post types
- `add_supported_post_type(string $post_type)` - Add custom post type

#### CSV_Generator

Utility class for generating RFC 4180 compliant CSV output.

**Public Methods:**
- `add_row(array $row)` - Add a row to the CSV
- `generate()` - Generate CSV string

## Usage

### Bulk Actions

1. Navigate to Posts or Pages list in WordPress admin
2. Select posts to modify
3. Choose a bulk action from the dropdown
4. Click "Apply"
5. Admin notice displays count of modified posts

### CSV Export

CSV export is handled via the `handle_csv_export()` method which:
1. Checks user capability (`meowseo_bulk_edit`)
2. Verifies nonce
3. Retrieves posts by post type
4. Generates CSV
5. Sends download headers
6. Outputs CSV file

## Security

- All bulk actions require `meowseo_bulk_edit` capability
- Post edit capability is verified for each post
- Nonce verification for CSV export
- User input sanitization for post IDs and post types

## Requirements

- WordPress 6.0+
- PHP 8.0+
- MeowSEO Role Manager module (for capability checks)

## Testing

Run tests with:

```bash
php vendor/bin/phpunit tests/modules/bulk/ --no-coverage
```

Test coverage includes:
- Module initialization
- Bulk action registration
- CSV generation with RFC 4180 compliance
- Post type support
- CSV escaping for special characters

## Implementation Notes

### RFC 4180 CSV Compliance

The CSV_Generator class implements proper RFC 4180 escaping:
- Fields containing commas, quotes, or newlines are enclosed in double quotes
- Double quotes within fields are escaped by doubling them
- Line endings use CRLF (\r\n)

### SEO Score Calculation

The SEO score is calculated based on:
- Focus keyword presence (20 points)
- Meta description presence (20 points)
- Post title presence (20 points)
- Content length > 300 characters (20 points)
- Schema type presence (20 points)

Maximum score is 100 points.

### Activity Logging

All bulk operations are logged to the WordPress activity log via the Logger helper with:
- Action name
- Post count
- Post IDs
- Timestamp

## Future Enhancements

- Bulk action for setting focus keywords
- Bulk action for setting meta descriptions
- Advanced filtering options for CSV export
- Scheduled bulk operations
- Bulk operation history/audit log
