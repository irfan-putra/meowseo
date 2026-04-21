# Implementation Plan: GitHub Auto-Update System

## Overview

This implementation plan provides a step-by-step guide for building the GitHub-based auto-update system for the MeowSEO WordPress plugin. The system will enable automatic update checks and one-click installations using Git commit IDs for versioning.

## Tasks

- [x] 1. Create Update Configuration Class
  - Create `includes/updater/class-update-config.php`
  - Implement configuration storage using WordPress options
  - Add methods: `get_repo_owner()`, `get_repo_name()`, `get_branch()`
  - Add methods: `is_auto_update_enabled()`, `get_check_frequency()`
  - Add methods: `save()`, `get_all()`, `validate_repository()`
  - Set default values: owner='akbarbahaulloh', repo='meowseo', branch='main'
  - No encryption needed (public repository, no sensitive data)
  - _Requirements: 6.1-6.10_

- [x] 2. Create Update Logger Class
  - Create `includes/updater/class-update-logger.php`
  - Implement log storage using WordPress options
  - Add method: `log_check()` for update check events
  - Add method: `log_api_request()` for GitHub API requests
  - Add method: `log_installation()` for update installations
  - Add method: `log_config_change()` for configuration changes
  - Add method: `get_recent_logs()` to retrieve logs
  - Add method: `clear_old_logs()` to remove logs older than 30 days
  - Store logs as array in single option (max 100 entries)
  - Include timestamp, level, type, message, and context in each log entry
  - _Requirements: 7.1-7.10_

- [x] 3. Create GitHub Update Checker Class (Core)
  - Create `includes/updater/class-github-update-checker.php`
  - Add constructor accepting plugin file, config, and logger
  - Implement `init()` method to register WordPress hooks
  - Add private properties: `$plugin_file`, `$plugin_slug`, `$config`, `$logger`
  - Extract plugin slug from plugin file path
  - _Requirements: 1.1, 2.1_

- [x] 4. Implement Version Management
  - Add method: `get_current_commit_id()` to extract commit ID from plugin version
  - Add method: `extract_commit_id()` to parse version string format `1.0.0-abc1234`
  - Add method: `is_update_available()` to compare commit IDs
  - Handle cases where version doesn't have commit ID (return empty string)
  - Use regex pattern: `/^[\d.]+-([\da-f]{7,40})$/` for extraction
  - _Requirements: 2.1-2.10_

- [x] 5. Implement GitHub API Integration
  - Add method: `github_api_request()` for making API requests
  - Use `wp_remote_get()` for HTTP requests
  - Set User-Agent header: `MeowSEO-Updater/1.0 (WordPress Plugin)`
  - Set timeout: 10 seconds
  - No authentication needed (public repository)
  - Parse response and handle errors (404, 403, 500, timeout)
  - Extract rate limit info from response headers
  - Return null on error, array on success
  - _Requirements: 3.1-3.12_

- [x] 6. Implement Update Check Logic
  - Add method: `get_latest_commit()` to fetch latest commit from GitHub
  - Make API request to: `https://api.github.com/repos/{owner}/{repo}/commits/{branch}`
  - Extract commit SHA, message, author, and date from response
  - Cache result for 12 hours using transient
  - Add method: `should_check_for_update()` to check if update check is needed
  - Compare last check time with configured frequency
  - _Requirements: 1.1-1.10, 2.1-2.10_

- [x] 7. Implement WordPress Update Hook Integration
  - Add hook: `pre_set_site_transient_update_plugins` with method `check_for_update()`
  - In `check_for_update()`: get current and latest commit IDs
  - If update available, add update info to transient
  - Set update info: `id`, `slug`, `plugin`, `new_version`, `url`, `package`
  - Set package URL to: `https://github.com/{owner}/{repo}/archive/{commit_sha}.zip`
  - Return modified transient object
  - Handle errors gracefully (return unmodified transient on error)
  - _Requirements: 1.1-1.10_

- [x] 8. Implement Changelog Functionality
  - Add method: `get_commit_history()` to fetch recent commits
  - Make API request to: `https://api.github.com/repos/{owner}/{repo}/commits?sha={branch}&per_page=20`
  - Parse commit list and extract: SHA, message, author, date, URL
  - Cache result for 12 hours using transient
  - Add hook: `plugins_api` with method `get_plugin_info()`
  - In `get_plugin_info()`: check if request is for this plugin
  - Build plugin info object with: name, slug, version, author, sections (changelog)
  - Format changelog as HTML list of commits
  - _Requirements: 5.1-5.10_

- [x] 9. Implement Package Download Handling
  - Add hook: `upgrader_pre_download` with method `modify_package_url()`
  - Verify package URL is for this plugin
  - Return GitHub archive URL: `https://github.com/{owner}/{repo}/archive/{commit_sha}.zip`
  - Handle nested directory structure (WordPress will extract correctly)
  - Log download attempt
  - _Requirements: 4.1-4.12_

- [x] 10. Implement Caching System
  - Add method: `get_cache()` using `get_transient()`
  - Add method: `set_cache()` using `set_transient()`
  - Add method: `clear_cache()` to delete all update-related transients
  - Cache keys: `meowseo_github_update_info`, `meowseo_github_changelog`, `meowseo_github_rate_limit`
  - Default expiration: 12 hours (43200 seconds)
  - Clear cache on: manual check, settings save, successful update
  - _Requirements: 9.1-9.10_

- [x] 11. Checkpoint - Test Core Update Functionality
  - Verify update checker initializes correctly
  - Test GitHub API requests with mock responses
  - Test version comparison logic
  - Test cache storage and retrieval
  - Verify WordPress hooks are registered
  - Test error handling for API failures
  - Ask the user if questions arise.

- [x] 12. Create Update Settings Page Class
  - Create `includes/updater/class-update-settings-page.php`
  - Add constructor accepting config, checker, and logger
  - Implement `register()` method to add settings page to WordPress admin
  - Add to Settings menu with title "GitHub Updates"
  - Require `manage_options` capability
  - Set menu slug: `meowseo-github-updates`
  - _Requirements: 6.1-6.10_

- [x] 13. Implement Settings Page Rendering
  - Create view file: `includes/admin/views/update-settings.php`
  - Add method: `render_page()` to display settings page
  - Add method: `render_status_section()` to show current status
  - Display: current version, latest version, update available status
  - Display: last check time, next check time
  - Display: GitHub rate limit status (remaining/limit, resets at)
  - Add method: `render_config_form()` to display configuration form
  - Form fields: branch (select: main/master/develop)
  - Form fields: auto_update_enabled (checkbox), check_frequency (select)
  - Display repo_owner and repo_name as read-only (hardcoded values)
  - Add nonce field for security
  - _Requirements: 6.1-6.10_

- [x] 14. Implement Settings Form Handling
  - Add method: `handle_save()` to process form submission
  - Verify nonce and user capabilities
  - Validate inputs: branch (must be valid branch name)
  - Sanitize inputs using `sanitize_text_field()`
  - Validate repository accessibility using GitHub API
  - Save configuration using `Update_Config::save()`
  - Clear update caches after save
  - Display success/error admin notice
  - Log configuration change
  - _Requirements: 6.1-6.10, 8.1-8.12_

- [x] 15. Implement Manual Update Check
  - Add method: `handle_check_now()` to process manual check request
  - Verify nonce and user capabilities
  - Clear all update caches
  - Trigger immediate update check
  - Display result in admin notice
  - Log manual check attempt
  - Redirect back to settings page
  - _Requirements: 1.7, 6.9_

- [x] 16. Implement Cache Management
  - Add method: `handle_clear_cache()` to clear all caches
  - Verify nonce and user capabilities
  - Call `clear_cache()` on update checker
  - Display success admin notice
  - Log cache clear action
  - Redirect back to settings page
  - _Requirements: 9.10_

- [x] 17. Implement Logs Display
  - Add method: `render_logs_section()` to display recent logs
  - Fetch logs using `Update_Logger::get_recent_logs()`
  - Display in table format: timestamp, level, type, message
  - Add expandable details for context data
  - Limit display to 50 most recent logs
  - Add "Clear old logs" button
  - Style with WordPress admin CSS classes
  - _Requirements: 7.1-7.10_

- [x] 18. Checkpoint - Test Settings Page
  - Verify settings page appears in WordPress admin
  - Test form submission and validation
  - Test manual update check button
  - Test cache clear button
  - Verify logs display correctly
  - Test error handling for invalid inputs
  - Ask the user if questions arise.

- [x] 19. Implement Error Handling
  - Add method: `handle_api_error()` to process API errors
  - Map HTTP status codes to user-friendly messages
  - Handle rate limit errors with retry-after time
  - Handle network timeout errors
  - Handle authentication errors
  - Handle repository not found errors
  - Log all errors with context
  - Display admin notices for errors
  - _Requirements: 7.1-7.10_

- [x] 20. Implement Rate Limit Handling
  - Add method: `check_rate_limit()` to extract rate limit from headers
  - Parse headers: `x-ratelimit-limit`, `x-ratelimit-remaining`, `x-ratelimit-reset`
  - Cache rate limit status for 1 hour
  - Add method: `is_rate_limited()` to check if rate limited
  - If rate limited, skip API requests until reset time
  - Display rate limit status in settings page
  - Show time until reset in error messages
  - _Requirements: 3.5, 7.7_

- [x] 21. Implement Security Measures
  - Validate all user inputs using WordPress sanitization functions
  - Escape all outputs using `esc_html()`, `esc_url()`, `esc_attr()`
  - Verify nonces on all form submissions
  - Check user capabilities (`manage_options`) on all admin actions
  - Use HTTPS for all GitHub API requests
  - Validate commit IDs match expected format
  - Validate branch name format
  - No sensitive data to protect (public repository)
  - _Requirements: 8.1-8.12_

- [x] 22. Implement ZIP File Validation
  - Add method: `validate_zip_file()` to verify downloaded ZIP
  - Check file exists and is readable
  - Verify file is a valid ZIP archive using `ZipArchive`
  - Check ZIP contains expected plugin files (meowseo.php)
  - Verify ZIP structure matches expected format
  - Return WP_Error on validation failure
  - Log validation results
  - _Requirements: 4.6, 8.2_

- [x] 23. Initialize Updater in Plugin Class
  - Modify `includes/class-plugin.php` to initialize updater
  - Add updater initialization in `boot()` method
  - Only initialize if user has `update_plugins` capability
  - Create instances: `Update_Config`, `Update_Logger`, `GitHub_Update_Checker`, `Update_Settings_Page`
  - Call `init()` on checker and `register()` on settings page
  - Hook initialization to `admin_init` action
  - _Requirements: 1.1_

- [x] 24. Update Plugin Version Format
  - Modify `meowseo.php` plugin header
  - Get current commit ID from Git: `git rev-parse --short HEAD`
  - Update version to format: `1.0.0-{commit_id}`
  - Update `MEOWSEO_VERSION` constant to match
  - Document version format in plugin header comments
  - _Requirements: 2.6_

- [x] 25. Checkpoint - Integration Testing
  - Test complete update flow from check to installation
  - Verify update notification appears on Plugins page
  - Test "View details" modal displays changelog
  - Test "Update Now" downloads and installs update
  - Verify plugin settings are preserved after update
  - Test with different commit IDs
  - Test error scenarios (invalid repo, rate limit, network error)
  - Ask the user if questions arise.

- [x] 26. Implement Backward Compatibility
  - Add method: `detect_current_commit()` to find commit for installations without commit ID
  - Query GitHub API for commits and match based on file hashes or dates
  - Handle first-time initialization gracefully
  - Preserve existing plugin settings during update
  - Test with WordPress 6.0, 6.1, 6.2, 6.3, 6.4
  - Test with PHP 8.0, 8.1, 8.2, 8.3
  - Test with WordPress multisite
  - Test with WordPress in subdirectory
  - _Requirements: 10.1-10.10_

- [x] 27. Write Unit Tests
  - Create `tests/updater/test-update-config.php`
  - Test configuration save/retrieve
  - Test validation methods
  - Create `tests/updater/test-update-logger.php`
  - Test log writing and retrieval
  - Test log rotation (max 100 entries)
  - Test old log cleanup
  - Create `tests/updater/test-github-update-checker.php`
  - Test version extraction and comparison
  - Test GitHub API request handling (with mocks)
  - Test cache operations
  - Test WordPress hook integration
  - _Requirements: All_

- [x] 28. Write Integration Tests
  - Create `tests/updater/test-update-integration.php`
  - Test full update check flow
  - Test settings page form submission
  - Test manual update check
  - Test cache clear functionality
  - Test error handling scenarios
  - Mock GitHub API responses
  - _Requirements: All_

- [x] 29. Create Documentation
  - Create `docs/GITHUB_UPDATES.md` with user guide
  - Document how to obtain GitHub Personal Access Token
  - Document settings page options
  - Document troubleshooting common issues
  - Document version format and commit ID tracking
  - Add inline code comments and PHPDoc blocks
  - Update main README.md with update system information
  - _Requirements: 9.1_

- [x] 30. Final Checkpoint - Complete Verification
  - Run all unit tests
  - Run all integration tests
  - Test on fresh WordPress installation
  - Test on existing MeowSEO installation
  - Test rate limit handling (60 requests/hour limit)
  - Test error scenarios
  - Verify logs are written correctly
  - Verify caching works as expected
  - Test on WordPress multisite
  - Performance test: verify update checks don't slow down admin
  - Security audit: verify all inputs validated and outputs escaped
  - Ask the user if questions arise.

## Notes

- All tasks should be completed in order as they build upon each other
- Each checkpoint task should be used to verify progress before continuing
- The implementation uses only WordPress core functions and GitHub API (no external dependencies)
- Version format: `{semantic_version}-{short_commit_id}` (e.g., `1.0.0-abc1234`)
- GitHub API rate limits: 60 requests/hour for unauthenticated access to public repos
- Cache expiration: 12 hours for update info, 1 hour for rate limit status
- No authentication required (public repository)
- All user inputs must be validated and sanitized
- All outputs must be escaped
- Error handling must be graceful (fail silently, don't break WordPress)
- Logging should be comprehensive but not verbose
- Performance impact should be minimal (< 100ms per page load)

## Testing Checklist

### Manual Testing
- [ ] Update notification appears on Plugins page
- [ ] "View details" shows changelog with recent commits
- [ ] "Update Now" downloads and installs update successfully
- [ ] Plugin settings are preserved after update
- [ ] Settings page displays correctly
- [ ] Settings form saves configuration
- [ ] "Check for updates now" triggers immediate check
- [ ] "Clear cache" clears all caches
- [ ] Logs display recent activity
- [ ] Rate limit status displays correctly
- [ ] Error messages are user-friendly
- [ ] Works with WordPress multisite
- [ ] Works with WordPress in subdirectory

### Automated Testing
- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] Code coverage > 80%
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors
- [ ] WordPress coding standards compliance

### Security Testing
- [ ] Nonces are verified on all forms
- [ ] User capabilities are checked
- [ ] All inputs are validated and sanitized
- [ ] All outputs are escaped
- [ ] HTTPS used for all API requests
- [ ] No sensitive data in error messages (N/A - public repo)

### Performance Testing
- [ ] Update checks complete within 5 seconds
- [ ] API requests timeout after 10 seconds
- [ ] Cache hit rate > 90%
- [ ] No noticeable page load impact
- [ ] Database queries optimized

## Deployment Steps

1. **Prepare Repository**
   - Ensure repository is accessible (public or with valid token)
   - Verify main/master branch exists
   - Get current commit ID: `git rev-parse --short HEAD`

2. **Update Plugin Version**
   - Update version in `meowseo.php` header to `1.0.0-{commit_id}`
   - Update `MEOWSEO_VERSION` constant

3. **Deploy Code**
   - Commit and push all updater code to GitHub
   - Verify files are accessible in repository

4. **Initialize Configuration**
   - On plugin activation, initialize default configuration
   - Set repo_owner, repo_name, branch
   - Enable auto-updates by default

5. **Test Update Flow**
   - Make a test commit to repository
   - Verify update notification appears
   - Test update installation
   - Verify plugin works after update

6. **Monitor**
   - Check logs for errors
   - Monitor GitHub API rate limit usage
   - Monitor update success rate

## Rollback Plan

If the update system fails:

1. **Disable Auto-Updates**
   - Set `auto_update_enabled` to false in settings
   - Clear update transients

2. **Manual Update**
   - Users can still manually update via ZIP upload
   - Provide download link on plugin website

3. **Revert Code**
   - Revert to previous commit if needed
   - Push to GitHub repository

4. **Clear Caches**
   - Clear all update-related transients
   - Clear WordPress object cache

5. **Restore Previous Version**
   - Use WordPress plugin rollback feature
   - Or manually upload previous version ZIP

## Success Criteria

- ✅ Users can check for updates from WordPress dashboard
- ✅ Users can install updates with one click
- ✅ Update notifications appear when new commits are available
- ✅ Changelog displays recent commits
- ✅ Settings page allows configuration
- ✅ System handles GitHub API rate limits gracefully
- ✅ Updates preserve plugin settings and data
- ✅ Error messages are clear and actionable
- ✅ System works with existing installations
- ✅ Performance impact is minimal
- ✅ All tests pass
- ✅ Security audit passes
- ✅ Documentation is complete
