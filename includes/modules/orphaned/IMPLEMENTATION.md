# Orphaned Detector Module Implementation

## Overview

The Orphaned Detector module identifies content with no internal links and provides suggestions for linking opportunities. This implementation completes task 11 of Sprint 4.

## Components

### 1. Orphaned_Detector Class (`class-orphaned-detector.php`)

Core scanning and detection logic.

**Key Methods:**
- `scan_all_content()` - Scans all published posts/pages for orphaned content
- `get_inbound_link_count(int $post_id)` - Counts inbound links to a post
- `get_orphaned_posts(array $filters)` - Retrieves orphaned posts with filtering
- `get_orphaned_count()` - Returns count of orphaned posts
- `suggest_linking_opportunities(int $orphaned_post_id)` - Suggests posts to link from
- `schedule_weekly_scan()` - Schedules WP-Cron job for weekly scanning

**Database Table:**
- `meowseo_orphaned_content` - Stores orphaned content records with:
  - `post_id` - Post ID
  - `inbound_link_count` - Number of inbound links
  - `last_scanned` - Last scan timestamp

### 2. Orphaned_Admin Class (`class-orphaned-admin.php`)

Admin interface for viewing and managing orphaned content.

**Features:**
- Admin page listing orphaned posts with title, URL, publish date
- Filters for post type and date range
- Dashboard widget showing orphaned post count
- "Suggest Links" action for each orphaned post

### 3. Orphaned_Module Class (`class-orphaned-module.php`)

Bootstrap class that initializes the module.

## Requirements Validation

### Requirement 8.1: Scan all published content
✓ `scan_all_content()` queries all published posts/pages and processes in batches of 100

### Requirement 8.2: Query Internal_Link_Scanner table
✓ `get_inbound_link_count()` queries `meowseo_link_checks` table to count inbound links

### Requirement 8.3: Mark posts with zero inbound links as orphaned
✓ Posts with zero inbound links are marked as orphaned in `meowseo_orphaned_content` table

### Requirement 8.4: Admin page listing orphaned content
✓ `Orphaned_Admin::render_page()` displays list with title, URL, publish date

### Requirement 8.5: Filter by post type and date range
✓ `get_orphaned_posts()` supports filtering by post_type, date_from, date_to

### Requirement 8.6: Guided workflow for fixing orphaned content
✓ "Suggest Links" action provides linking suggestions

### Requirement 8.7: Suggest 5 posts to link from
✓ `suggest_linking_opportunities()` returns up to 5 suggestions based on content similarity

### Requirement 8.8: Weekly WP-Cron job
✓ `schedule_weekly_scan()` schedules weekly scan via `wp_schedule_event()`

### Requirement 8.9: Dashboard widget showing orphaned post count
✓ `Orphaned_Admin::register_dashboard_widget()` displays orphaned count

## Algorithm Details

### Link Counting
- Queries `meowseo_link_checks` table for links with `target_url` matching post permalink
- Counts all inbound links regardless of source

### Orphaned Detection
- Posts with `inbound_link_count = 0` are marked as orphaned
- Scans all published posts and pages
- Processes in batches of 100 to avoid timeouts

### Linking Suggestions
- Analyzes content similarity using:
  - Keyword overlap in title (40 points)
  - Keyword overlap in content (20 points)
  - Category overlap (20 points)
  - Tag overlap (20 points)
- Returns top 5 posts by similarity score
- Considers focus keyword from post metadata

## Integration

### Module Registration
- Registered in `Module_Manager::$module_registry` as `'orphaned' => 'Modules\Orphaned\Orphaned_Module'`
- Added to default enabled modules in `Options::set_defaults()`
- Autoloader path registered in `includes/modules/autoloader.php`

### Database
- Table created in `Installer::get_schema()`
- Table dropped on uninstall if configured

### Hooks
- `save_post` - Updates orphaned status when post is saved
- `delete_post` - Cleans up orphaned records when post is deleted
- `meowseo_scan_orphaned_content` - Weekly WP-Cron job

## Testing

Unit tests validate:
- Inbound link counting
- Orphaned post identification
- Filtering by post type
- Orphaned count calculation
- Linking suggestion generation
- Hook registration

All tests pass successfully.

## Performance Considerations

- Batch processing of 100 posts per iteration to avoid timeouts
- Database indexes on `post_id`, `inbound_link_count`, `last_scanned`
- Caching of orphaned count in dashboard widget
- Weekly scan via WP-Cron to minimize impact

## Security

- Capability checks for admin pages (`meowseo_view_link_suggestions`)
- Prepared statements for all database queries
- Proper escaping of output in admin interface
- Nonce verification for AJAX requests (if added)

## Future Enhancements

- Batch linking suggestions UI
- Automatic linking recommendations
- Orphaned content cleanup actions
- Integration with internal link scanner for real-time updates
