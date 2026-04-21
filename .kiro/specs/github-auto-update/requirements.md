# Requirements Document: GitHub Auto-Update System

## Introduction

This specification defines the requirements for implementing a GitHub-based auto-update system for the MeowSEO WordPress plugin. The system will enable users to check for updates and install them directly from the WordPress admin dashboard, using the GitHub repository as the update source. Version tracking will be based on Git commit IDs rather than traditional semantic versioning releases.

## Current State

### Existing Configuration

- **Plugin Name:** MeowSEO
- **Current Version:** 1.0.0 (hardcoded in plugin header)
- **GitHub Repository:** https://github.com/akbarbahaulloh/meowseo (Public)
- **Repository Owner:** akbarbahaulloh
- **Repository Name:** meowseo
- **Update Source:** No automatic updates currently implemented
- **Versioning Strategy:** Git commit IDs (no GitHub releases)
- **Authentication:** Not required (public repository)

### Limitations

1. Users cannot check for updates from WordPress dashboard
2. No automatic update notifications
3. Manual plugin updates required (download ZIP, upload, activate)
4. No version comparison mechanism
5. No update changelog display

## Glossary

- **GitHub API**: RESTful API provided by GitHub for accessing repository data
- **Commit ID (SHA)**: A unique 40-character hexadecimal string identifying a Git commit
- **Short Commit ID**: First 7 characters of the full commit ID
- **Plugin Updater**: WordPress component that checks for and installs plugin updates
- **Transient**: WordPress temporary cached data with expiration time
- **Update Checker**: Component that queries GitHub API for new commits
- **Update Package**: ZIP file containing the plugin code from a specific commit
- **WordPress Plugin API**: WordPress hooks and filters for plugin update management
- **Rate Limiting**: GitHub API request limits (60 requests/hour for unauthenticated public repo access)
- **Semantic Versioning**: Version numbering scheme (MAJOR.MINOR.PATCH) - not used in this implementation
- **Branch**: Git branch to track for updates (default: main or master)
- **Public Repository**: GitHub repository accessible without authentication

## Requirements

### Requirement 1: Update Check Integration

**User Story:** As a WordPress administrator, I want to see update notifications for MeowSEO in the WordPress dashboard, so that I know when new versions are available.

#### Acceptance Criteria

1. THE Update_Checker SHALL integrate with WordPress plugin update system using `pre_set_site_transient_update_plugins` filter
2. THE Update_Checker SHALL check for updates when WordPress checks for plugin updates (typically every 12 hours)
3. WHEN a new commit is available on GitHub, THE system SHALL display an update notification on the Plugins page
4. THE update notification SHALL show the commit ID and commit message
5. THE update notification SHALL include a "View details" link to see the changelog
6. THE system SHALL cache update check results for 12 hours using WordPress transients
7. THE system SHALL allow manual update checks via "Check for updates" button
8. THE Update_Checker SHALL handle GitHub API rate limits gracefully
9. IF GitHub API is unavailable, THE system SHALL fail silently without breaking WordPress
10. THE system SHALL log update check attempts and results for debugging

### Requirement 2: Version Comparison Using Commit IDs

**User Story:** As a WordPress administrator, I want the system to accurately detect when a new version is available, so that I don't miss important updates.

#### Acceptance Criteria

1. THE system SHALL use Git commit IDs (SHA) for version tracking instead of semantic versioning
2. THE current installed version SHALL be stored as a commit ID in the plugin header or database
3. THE system SHALL query GitHub API to get the latest commit on the specified branch
4. WHEN comparing versions, THE system SHALL compare commit IDs to determine if an update is available
5. IF the latest GitHub commit ID differs from the installed commit ID, THE system SHALL mark an update as available
6. THE system SHALL store the current commit ID in the plugin header as `Version: 1.0.0-{short_commit_id}`
7. THE system SHALL extract the commit ID from the version string for comparison
8. THE system SHALL handle cases where the installed version doesn't have a commit ID (initial installation)
9. THE system SHALL support checking a specific branch (configurable, default: main)
10. THE system SHALL validate commit IDs before comparison

### Requirement 3: GitHub API Integration

**User Story:** As a WordPress administrator, I want the plugin to securely communicate with GitHub to fetch update information, so that updates are reliable and secure.

#### Acceptance Criteria

1. THE system SHALL use GitHub REST API v3 for fetching repository information
2. THE system SHALL make API requests to `https://api.github.com/repos/{owner}/{repo}/commits/{branch}`
3. THE system SHALL make unauthenticated API requests (no authentication required for public repos)
4. THE system SHALL respect GitHub API rate limits (60 requests/hour for unauthenticated access)
5. THE system SHALL cache API responses to minimize API calls (12-hour cache)
6. THE system SHALL handle API errors gracefully (404, 403, 500, etc.)
7. THE system SHALL use WordPress HTTP API (`wp_remote_get`) for all API requests
8. THE system SHALL set appropriate User-Agent header identifying the plugin
9. THE system SHALL validate API responses before processing
10. THE system SHALL timeout API requests after 10 seconds
11. THE system SHALL log API errors for debugging
12. THE system SHALL display rate limit status in admin (requests remaining/limit)

### Requirement 4: Update Package Download

**User Story:** As a WordPress administrator, I want to download and install updates directly from the WordPress dashboard, so that I don't need to manually download and upload files.

#### Acceptance Criteria

1. THE system SHALL provide a download URL for the update package
2. THE download URL SHALL point to GitHub's archive endpoint: `https://github.com/{owner}/{repo}/archive/{commit_id}.zip`
3. THE system SHALL integrate with WordPress plugin installer using `plugins_api` filter
4. WHEN user clicks "Update Now", THE system SHALL download the ZIP file from GitHub
5. THE system SHALL verify the downloaded ZIP file is valid before installation
6. THE system SHALL extract the ZIP file to the plugins directory
7. THE system SHALL handle the nested directory structure from GitHub archives (removes the top-level directory)
8. THE system SHALL preserve plugin settings and data during update
9. THE system SHALL activate the plugin after successful update
10. IF update fails, THE system SHALL restore the previous version (WordPress handles this)
11. THE system SHALL display success/error messages after update attempt
12. THE system SHALL log update installation attempts and results

### Requirement 5: Changelog and Update Details

**User Story:** As a WordPress administrator, I want to see what changes are included in an update, so that I can make informed decisions about updating.

#### Acceptance Criteria

1. THE system SHALL display a changelog when user clicks "View details" on the update notification
2. THE changelog SHALL show commit messages between the current version and the latest version
3. THE system SHALL fetch commit history from GitHub API: `https://api.github.com/repos/{owner}/{repo}/commits?sha={branch}&since={current_commit_date}`
4. THE changelog SHALL display up to 20 recent commits
5. EACH commit entry SHALL show: commit message, author, date, and short commit ID
6. THE changelog SHALL be displayed in a WordPress modal/popup
7. THE system SHALL cache changelog data for 12 hours
8. THE changelog SHALL include a link to view full commit history on GitHub
9. THE system SHALL handle cases where commit history cannot be fetched
10. THE changelog SHALL be formatted for readability (line breaks, lists, etc.)

### Requirement 6: Configuration and Settings

**User Story:** As a WordPress administrator, I want to configure update settings, so that I can control how updates are checked and installed.

#### Acceptance Criteria

1. THE system SHALL provide a settings page for update configuration
2. THE settings page SHALL include the following options:
   - GitHub repository owner (default: akbarbahaulloh, read-only)
   - GitHub repository name (default: meowseo, read-only)
   - Branch to track (default: main, configurable)
   - Enable/disable automatic update checks
   - Update check frequency (default: 12 hours)
3. THE settings SHALL be stored in WordPress options table
4. THE settings page SHALL display current rate limit status (requests remaining/limit)
5. THE settings page SHALL show the current installed commit ID
6. THE settings page SHALL show the latest available commit ID
7. THE settings page SHALL include a "Check for updates now" button
8. THE settings page SHALL include a "Clear cache" button
9. THE settings SHALL be accessible only to administrators
10. THE settings page SHALL display recent update check logs

### Requirement 7: Error Handling and Logging

**User Story:** As a WordPress administrator, I want clear error messages when updates fail, so that I can troubleshoot issues.

#### Acceptance Criteria

1. THE system SHALL handle all errors gracefully without breaking WordPress
2. THE system SHALL display user-friendly error messages for common issues:
   - GitHub API unavailable
   - Rate limit exceeded
   - Invalid repository
   - Network timeout
   - Invalid ZIP file
   - Insufficient permissions
3. THE system SHALL log all errors to WordPress debug log when WP_DEBUG is enabled
4. THE system SHALL log the following events:
   - Update checks (success/failure)
   - API requests (URL, response code, rate limit status)
   - Update installations (success/failure)
   - Configuration changes
5. THE error messages SHALL include actionable guidance for resolution
6. THE system SHALL not expose sensitive information (API tokens) in error messages
7. THE system SHALL handle GitHub API rate limit errors with retry-after information
8. THE system SHALL provide a "View logs" link in error messages (if WP_DEBUG enabled)
9. THE system SHALL clear old log entries after 30 days
10. THE system SHALL include error context (commit IDs, API endpoints) in logs

### Requirement 8: Security and Validation

**User Story:** As a WordPress administrator, I want updates to be secure and validated, so that my site is not compromised by malicious code.

#### Acceptance Criteria

1. THE system SHALL validate all GitHub API responses before processing
2. THE system SHALL verify downloaded ZIP files are valid archives
3. THE system SHALL use WordPress nonces for all admin actions
4. THE system SHALL restrict update configuration to administrators only
5. THE system SHALL use HTTPS for all GitHub API requests
6. THE system SHALL validate repository owner and name format
7. THE system SHALL sanitize all user inputs
8. THE system SHALL escape all outputs
9. THE system SHALL not execute any code from downloaded packages before installation
10. THE system SHALL verify the downloaded package contains the expected plugin files
11. THE system SHALL use WordPress filesystem API for file operations
12. THE system SHALL validate commit IDs match expected format (7-40 hex characters)

### Requirement 9: Performance and Caching

**User Story:** As a WordPress administrator, I want update checks to be fast and not slow down my site, so that performance is not impacted.

#### Acceptance Criteria

1. THE system SHALL cache update check results for 12 hours using WordPress transients
2. THE system SHALL cache GitHub API responses for 12 hours
3. THE system SHALL cache changelog data for 12 hours
4. THE system SHALL use WordPress object cache when available
5. THE system SHALL perform update checks asynchronously (not blocking page loads)
6. THE system SHALL limit API requests to once per 12 hours (unless manually triggered)
7. THE system SHALL timeout API requests after 10 seconds
8. THE system SHALL not perform update checks on every page load
9. THE system SHALL clear caches when settings are changed
10. THE system SHALL provide a manual cache clear option

### Requirement 10: Backward Compatibility

**User Story:** As a WordPress administrator, I want the update system to work with existing installations, so that I can upgrade without issues.

#### Acceptance Criteria

1. THE system SHALL work with WordPress 6.0 and later
2. THE system SHALL work with PHP 8.0 and later
3. THE system SHALL handle existing installations without commit IDs in version string
4. WHEN upgrading from a version without commit ID, THE system SHALL detect the current commit from GitHub
5. THE system SHALL preserve all plugin settings during updates
6. THE system SHALL preserve all database tables during updates
7. THE system SHALL not break existing functionality if GitHub is unavailable
8. THE system SHALL fall back to manual updates if auto-update fails
9. THE system SHALL maintain compatibility with WordPress multisite
10. THE system SHALL work with WordPress in subdirectory installations

## Implementation Priority

### Phase 1: Core Update Checker (MVP)
- Requirement 1: Update Check Integration
- Requirement 2: Version Comparison Using Commit IDs
- Requirement 3: GitHub API Integration
- Requirement 4: Update Package Download

### Phase 2: User Experience
- Requirement 5: Changelog and Update Details
- Requirement 6: Configuration and Settings
- Requirement 7: Error Handling and Logging

### Phase 3: Security and Performance
- Requirement 8: Security and Validation
- Requirement 9: Performance and Caching
- Requirement 10: Backward Compatibility

## Non-Functional Requirements

### Performance
- Update checks SHALL complete within 5 seconds
- API requests SHALL timeout after 10 seconds
- Cache hit rate SHALL be > 90% for repeated checks

### Reliability
- System SHALL handle GitHub API downtime gracefully
- System SHALL not break WordPress if update fails
- System SHALL maintain 99.9% uptime for update checks

### Usability
- Update process SHALL be identical to standard WordPress plugin updates
- Error messages SHALL be clear and actionable
- Settings page SHALL be intuitive and well-documented

### Security
- All API tokens SHALL be encrypted at rest
- All API requests SHALL use HTTPS
- All user inputs SHALL be validated and sanitized

### Maintainability
- Code SHALL follow WordPress coding standards
- Code SHALL be well-documented with PHPDoc
- Code SHALL include unit tests for critical functions

## Constraints

1. **GitHub API Rate Limits**: 60 requests/hour for unauthenticated access to public repositories
2. **No GitHub Releases**: System must work with commit IDs only (no release tags)
3. **WordPress Compatibility**: Must work with WordPress 6.0+
4. **PHP Version**: Must work with PHP 8.0+
5. **No External Dependencies**: Must use only WordPress core functions and GitHub API
6. **File Size**: Update packages may be large (10-50 MB)
7. **Network Reliability**: Must handle intermittent network issues
8. **Public Repository Only**: System designed for public GitHub repositories

## Assumptions

1. GitHub repository is public and accessible without authentication
2. Repository structure remains consistent (plugin files in root directory)
3. Users have write permissions to plugins directory
4. WordPress cron is functioning correctly
5. Server has outbound HTTPS access to GitHub
6. Users understand commit-based versioning
7. Update checks every 12 hours are sufficient (within rate limits)

## Success Criteria

1. ✅ Users can check for updates from WordPress dashboard
2. ✅ Users can install updates with one click
3. ✅ Update notifications appear when new commits are available
4. ✅ Changelog displays recent commits
5. ✅ Settings page allows configuration of repository and branch
6. ✅ System handles GitHub API rate limits gracefully
7. ✅ Updates preserve plugin settings and data
8. ✅ Error messages are clear and actionable
9. ✅ System works with existing installations
10. ✅ Performance impact is minimal (< 100ms per page load)
