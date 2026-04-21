# GitHub Auto-Update System Documentation

## Overview

The MeowSEO GitHub Auto-Update System enables automatic update checks and one-click installations directly from the WordPress dashboard. The system uses Git commit IDs for versioning instead of traditional semantic versioning releases.

## Features

- **Automatic Update Checks**: Checks for new commits on GitHub every 12 hours (configurable)
- **One-Click Installation**: Install updates directly from the WordPress Plugins page
- **Changelog Display**: View recent commits in the update details modal
- **Rate Limit Handling**: Respects GitHub API rate limits (60 requests/hour)
- **Error Handling**: User-friendly error messages for common issues
- **Logging**: Comprehensive logging of all update operations
- **Security**: Input validation, output escaping, nonce verification

## Getting Started

### Installation

The GitHub Auto-Update System is built into MeowSEO and initializes automatically when the plugin is activated.

### Configuration

1. Go to **Settings > GitHub Updates** in the WordPress admin
2. Configure the following options:
   - **Branch to Track**: Select which branch to check for updates (main, master, develop)
   - **Enable Automatic Updates**: Toggle automatic update checks on/off
   - **Check Frequency**: Set how often to check for updates (1, 6, 12, or 24 hours)

### Manual Update Check

To check for updates immediately:

1. Go to **Settings > GitHub Updates**
2. Click the **"Check for Updates Now"** button
3. The system will fetch the latest commit from GitHub and display the result

## How It Works

### Version Format

The plugin version uses the format: `{semantic_version}-{commit_id}`

Example: `1.0.0-b1b0d0d`

- **Semantic Version**: `1.0.0` (major.minor.patch)
- **Commit ID**: `b1b0d0d` (first 7 characters of Git commit SHA)

### Update Check Process

1. **Check Frequency**: System checks if enough time has passed since the last check
2. **Fetch Latest Commit**: Queries GitHub API for the latest commit on the configured branch
3. **Compare Versions**: Compares the installed commit ID with the latest commit ID
4. **Display Notification**: If a new commit is available, shows an update notification on the Plugins page
5. **Cache Results**: Caches the result for 12 hours to minimize API calls

### Update Installation

1. **Download**: WordPress downloads the plugin ZIP from GitHub's archive endpoint
2. **Validate**: System validates the ZIP file structure and contents
3. **Extract**: WordPress extracts the ZIP to the plugins directory
4. **Activate**: Plugin is automatically activated after installation
5. **Preserve Settings**: All plugin settings and data are preserved

## Settings Page

The GitHub Updates settings page displays:

### Status Section

- **Current Version**: Currently installed version with commit ID
- **Latest Version**: Latest available version from GitHub
- **Update Available**: Whether an update is available
- **Last Check Time**: When the last update check was performed
- **Next Check Time**: When the next automatic check is scheduled
- **GitHub Rate Limit**: Current API request usage (remaining/limit)

### Configuration Section

- **Repository Owner**: GitHub username (read-only: akbarbahaulloh)
- **Repository Name**: GitHub repository name (read-only: meowseo)
- **Branch to Track**: Select which branch to monitor for updates
- **Enable Automatic Updates**: Toggle automatic checking on/off
- **Check Frequency**: How often to check for updates

### Action Buttons

- **Check for Updates Now**: Manually trigger an immediate update check
- **Clear Cache**: Clear all cached update data and force a fresh check

### Logs Section

Displays recent update-related events including:
- Update checks (success/failure)
- API requests (endpoint, response code, rate limit status)
- Update installations (success/failure)
- Configuration changes

## GitHub API Integration

### API Endpoints Used

The system uses the following GitHub API endpoints:

```
GET https://api.github.com/repos/{owner}/{repo}/commits/{branch}
GET https://api.github.com/repos/{owner}/{repo}/commits?sha={branch}&per_page=20
```

### Rate Limiting

GitHub allows 60 unauthenticated API requests per hour per IP address.

- **Default Check Frequency**: 12 hours (5 requests per day)
- **Rate Limit Status**: Displayed in the settings page
- **Rate Limit Handling**: If rate limited, the system waits until the limit resets

### No Authentication Required

The system works with public repositories and does not require GitHub authentication tokens.

## Troubleshooting

### Update Check Failed

**Error**: "Unable to check for updates. Please try again later."

**Causes**:
- Network connectivity issue
- GitHub API is temporarily unavailable
- Repository is not accessible

**Solution**:
1. Check your internet connection
2. Verify the repository is public and accessible
3. Try again in a few minutes
4. Check the logs for more details

### Rate Limit Exceeded

**Error**: "GitHub rate limit exceeded. Updates will resume at [time]."

**Causes**:
- Too many API requests from your IP address
- Other applications on your server are using the GitHub API

**Solution**:
1. Wait until the rate limit resets (usually 1 hour)
2. Reduce the update check frequency
3. Check if other applications are using the GitHub API

### Repository Not Found

**Error**: "GitHub repository not found. Please check your repository settings."

**Causes**:
- Repository has been deleted or renamed
- Repository is private and not accessible
- Incorrect repository owner or name

**Solution**:
1. Verify the repository exists and is public
2. Check the repository owner and name in the settings
3. Contact the plugin developer if the issue persists

### Invalid Update Package

**Error**: "Downloaded update file is invalid."

**Causes**:
- Network error during download
- GitHub archive endpoint is temporarily unavailable
- Corrupted download

**Solution**:
1. Try the update again
2. Check your internet connection
3. Try again in a few minutes

## Logs

The system maintains comprehensive logs of all update operations. Logs are stored in the WordPress database and include:

- **Timestamp**: When the event occurred
- **Level**: info, warning, or error
- **Type**: check, api_request, installation, config_change
- **Message**: Description of the event
- **Context**: Additional data (commit IDs, API endpoints, error messages)

### Viewing Logs

1. Go to **Settings > GitHub Updates**
2. Scroll to the **Logs** section
3. Recent logs are displayed in a table format

### Clearing Old Logs

1. Go to **Settings > GitHub Updates**
2. Scroll to the **Logs** section
3. Click **"Clear Old Logs"** to remove logs older than 30 days

### Debug Logging

If `WP_DEBUG` is enabled in `wp-config.php`, additional debug information is logged to the WordPress debug log:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Debug logs are written to: `wp-content/debug.log`

## Security

### Input Validation

All user inputs are validated:
- **Branch Name**: Must match Git branch name format
- **Repository Owner**: Must match GitHub username format
- **Repository Name**: Must match GitHub repository name format
- **Commit IDs**: Must be 7-40 hexadecimal characters

### Output Escaping

All outputs are properly escaped:
- HTML content: `esc_html()`
- URLs: `esc_url()`
- HTML attributes: `esc_attr()`

### Nonce Verification

All form submissions are protected with WordPress nonces:
- Settings form: `meowseo_update_settings`
- Manual check: `meowseo_check_update_now`
- Cache clear: `meowseo_clear_cache`
- Clear logs: `meowseo_clear_old_logs`

### User Capabilities

All admin actions require the `manage_options` capability:
- Viewing settings page
- Changing configuration
- Manual update checks
- Clearing cache
- Clearing logs

### HTTPS Enforcement

All GitHub API requests use HTTPS with SSL verification enabled.

### ZIP File Validation

Downloaded ZIP files are validated:
- File exists and is readable
- File is a valid ZIP archive
- ZIP contains expected plugin files (meowseo.php)
- ZIP structure matches expected format (nested directory)

## Performance

### Caching

The system caches API responses to minimize requests:

| Cache Key | Purpose | Expiration |
|-----------|---------|------------|
| `meowseo_github_update_info` | Update check results | 12 hours |
| `meowseo_github_changelog` | Commit history | 12 hours |
| `meowseo_github_rate_limit` | Rate limit status | 1 hour |

### Performance Impact

- **Update Checks**: < 100ms (cached) or < 5 seconds (API call)
- **Page Load Impact**: Minimal (checks run asynchronously)
- **Database Queries**: 1-2 queries per check (cached)

## Compatibility

### WordPress Versions

- WordPress 6.0 and later
- Tested with WordPress 6.0, 6.1, 6.2, 6.3, 6.4

### PHP Versions

- PHP 8.0 and later
- Tested with PHP 8.0, 8.1, 8.2, 8.3

### WordPress Multisite

The system works with WordPress multisite installations:
- Each site has its own update configuration
- Logs are stored per site
- Rate limit status is shared across sites

### WordPress in Subdirectory

The system works with WordPress installed in a subdirectory:
- Plugin paths are correctly resolved
- Update checks work normally
- No special configuration needed

## Advanced Configuration

### Changing the Check Frequency

To change the update check frequency programmatically:

```php
$config = new \MeowSEO\Updater\Update_Config();
$config->save( array(
    'check_frequency' => 3600, // 1 hour in seconds
) );
```

### Manually Triggering an Update Check

To manually trigger an update check:

```php
$config = new \MeowSEO\Updater\Update_Config();
$logger = new \MeowSEO\Updater\Update_Logger();
$checker = new \MeowSEO\Updater\GitHub_Update_Checker( MEOWSEO_FILE, $config, $logger );

// Clear cache to force fresh check.
$checker->clear_cache();

// Trigger update check.
$transient = get_site_transient( 'update_plugins' );
$transient = $checker->check_for_update( $transient );
set_site_transient( 'update_plugins', $transient );
```

### Accessing Update Logs Programmatically

To retrieve update logs:

```php
$logger = new \MeowSEO\Updater\Update_Logger();
$logs = $logger->get_recent_logs( 50 ); // Get 50 most recent logs

foreach ( $logs as $log ) {
    echo $log['timestamp'] . ' - ' . $log['message'] . PHP_EOL;
}
```

## Frequently Asked Questions

### Q: How often does the system check for updates?

A: By default, the system checks for updates every 12 hours. You can change this in the settings page to 1, 6, 12, or 24 hours.

### Q: Does the system require a GitHub token?

A: No, the system works with public repositories and does not require authentication.

### Q: What happens if GitHub is down?

A: The system fails gracefully and displays a user-friendly error message. It will try again at the next scheduled check.

### Q: Can I disable automatic updates?

A: Yes, you can disable automatic updates in the settings page. You can still manually check for updates using the "Check for Updates Now" button.

### Q: How are plugin settings preserved during updates?

A: WordPress automatically preserves plugin settings during updates. The plugin database tables and options are not affected by the update process.

### Q: Can I rollback to a previous version?

A: Yes, WordPress provides a rollback feature. You can use the "Rollback" link on the Plugins page to revert to a previous version.

### Q: How do I report a bug or request a feature?

A: Please visit the GitHub repository and create an issue: https://github.com/akbarbahaulloh/meowseo/issues

## Support

For support and questions:

- **GitHub Issues**: https://github.com/akbarbahaulloh/meowseo/issues
- **Documentation**: https://github.com/akbarbahaulloh/meowseo/wiki
- **Email**: [contact information]

## Version History

### Version 1.0.0

- Initial release
- Automatic update checks from GitHub
- One-click installation
- Changelog display
- Rate limit handling
- Error handling and logging
- Security features (validation, escaping, nonce verification)
- Comprehensive documentation

## License

This plugin is licensed under the GPL v2 or later. See LICENSE file for details.

## Credits

Developed by [Author Name]

## Changelog

See CHANGELOG.md for detailed version history.
