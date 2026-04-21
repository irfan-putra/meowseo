# Spec Changelog: GitHub Auto-Update System

## 2025-01-15 - Simplified for Public Repository

### Changes Made

Updated the specification to simplify implementation for public GitHub repositories, removing unnecessary authentication complexity.

### What Was Removed

1. **Personal Access Token (PAT) Support**
   - Removed PAT configuration option
   - Removed token encryption/decryption logic
   - Removed token storage in settings

2. **Authentication Logic**
   - Removed `get_access_token()` method from Update_Config
   - Removed `encrypt()` and `decrypt()` methods
   - Removed authentication headers in API requests

3. **Settings Complexity**
   - Removed PAT input field from settings page
   - Made repo_owner and repo_name read-only (hardcoded)
   - Simplified configuration to only: branch, auto_update_enabled, check_frequency

### What Was Simplified

1. **GitHub API Integration**
   - Now uses unauthenticated requests only
   - Simplified to 60 requests/hour rate limit
   - Removed conditional authentication logic

2. **Security**
   - No encryption needed (no sensitive data)
   - Removed token-related security measures
   - Simplified to basic input validation and output escaping

3. **Configuration**
   - Reduced from 6 settings to 3 configurable settings
   - Hardcoded repository owner and name
   - Only branch selection is user-configurable

### What Remains

1. **Core Functionality**
   - ✅ Update checking using commit IDs
   - ✅ One-click update installation
   - ✅ Changelog display
   - ✅ Settings page
   - ✅ Error handling and logging
   - ✅ Caching system

2. **Rate Limiting**
   - ✅ 60 requests/hour monitoring
   - ✅ Rate limit status display
   - ✅ Graceful handling when limit reached

3. **Security**
   - ✅ Nonce verification
   - ✅ Capability checks
   - ✅ Input validation
   - ✅ Output escaping
   - ✅ HTTPS for API requests

### Benefits of Simplification

1. **Easier Implementation**
   - ~30% less code to write
   - No encryption/decryption complexity
   - Fewer edge cases to handle

2. **Easier Maintenance**
   - Fewer configuration options
   - Less user confusion
   - Simpler troubleshooting

3. **Sufficient for Use Case**
   - Public repository doesn't need authentication
   - 60 requests/hour is plenty for update checks every 12 hours
   - Simpler = more reliable

### Rate Limit Analysis

**Without Authentication (Current Approach):**
- 60 requests per hour per IP address
- Update checks every 12 hours = 2 requests per day
- Changelog fetch = 1 additional request per check
- **Total: ~4 requests per day** (well within limits)

**Conclusion:** Authentication is unnecessary for this use case.

### Migration Notes

If you later need to support private repositories or higher rate limits:

1. Add `get_access_token()` method back to Update_Config
2. Add encryption/decryption methods
3. Add PAT field to settings page
4. Add conditional authentication in `github_api_request()`
5. Update rate limit handling for 5000/hour limit

But for now, the simplified approach is recommended.

---

## Summary

The spec has been updated to focus on public repository support only, removing ~30% of the complexity while maintaining all core functionality. This makes implementation faster, maintenance easier, and the system more reliable for the intended use case.
