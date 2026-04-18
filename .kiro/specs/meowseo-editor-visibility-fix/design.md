# MeowSEO Editor Visibility Fix - Bugfix Design

## Overview

This bugfix addresses 19 critical bugs across 5 categories that prevent the MeowSEO plugin from functioning properly. The primary user-facing issue is the missing Gutenberg sidebar panel, but the root causes extend deep into asset loading, module initialization, error handling, REST API implementation, and security. The fix strategy involves: (1) correcting asset file paths and adding existence checks, (2) fixing module registry mismatches and completing incomplete implementations, (3) adding comprehensive error handling with fallback mechanisms, (4) completing all REST API implementations with proper nonce verification, and (5) enhancing security with strict input validation and consistent CSRF protection.

## Glossary

- **Bug_Condition (C)**: The condition that triggers any of the 19 bugs - encompasses missing files, incorrect paths, incomplete implementations, missing error handling, improper nonce verification, and insufficient validation
- **Property (P)**: The desired behavior when bugs are fixed - proper asset loading, complete module initialization, robust error handling, complete REST API implementations, and enhanced security
- **Preservation**: All existing functionality that works correctly must remain unchanged - AI sidebar loading, admin settings, dashboard, postmeta registration, schema generation, sitemap generation, meta tag output, redirects, internal links, 404 monitoring, WooCommerce integration, social sharing, REST API validation, and plugin initialization
- **Gutenberg_Assets**: The class in `includes/modules/meta/class-gutenberg-assets.php` that enqueues JavaScript and CSS assets for the Gutenberg editor
- **Module_Manager**: The class in `includes/class-module-manager.php` that loads and boots plugin modules based on the module registry
- **AI_Module**: The class in `includes/modules/ai/class-ai-module.php` that implements the AI generation module
- **REST_API**: The class in `includes/class-rest-api.php` that registers all REST API endpoints under meowseo/v1 namespace
- **Redux Store**: The WordPress data store registered as 'meowseo/data' in `src/store/index.js` and `src/gutenberg/store/index.ts`

## Bug Details

### Bug Condition

The bugs manifest across multiple subsystems when specific conditions are met. The Gutenberg_Assets class fails when attempting to load non-existent build files. The Module_Manager fails when the module registry references incorrect class names. The AI_Module fails when dependencies are not properly instantiated. The REST_API fails when nonce verification is incomplete. The Redux stores fail when registration errors occur without fallback mechanisms.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type SystemOperation
  OUTPUT: boolean
  
  RETURN (
    // Category 1: Editor Visibility Issues
    (input.operation == "load_gutenberg_assets" AND NOT file_exists("build/gutenberg.asset.php"))
    OR (input.operation == "enqueue_gutenberg_js" AND NOT file_exists("build/gutenberg.js"))
    OR (input.operation == "enqueue_gutenberg_css" AND input.css_path == "build/index.css")
    OR (input.operation == "load_asset_file" AND NOT has_file_existence_check(input))
    
    // Category 2: Missing Module Implementations
    OR (input.operation == "load_ai_module" AND module_registry["ai"] != "Modules\\AI\\AI_Module")
    OR (input.operation == "instantiate_ai_module" AND NOT all_dependencies_instantiated(input))
    OR (input.operation == "boot_ai_module" AND NOT method_exists(AI_Module, "boot"))
    OR (input.operation == "render_admin_dashboard" AND NOT component_implemented(input))
    OR (input.operation == "handle_ai_generate" AND NOT api_call_implemented(input))
    
    // Category 3: Error Handling Deficiencies
    OR (input.operation == "register_redux_store" AND NOT has_fallback_mechanism(input))
    OR (input.operation == "register_gutenberg_store" AND NOT has_graceful_degradation(input))
    OR (input.operation == "instantiate_worker" AND NOT has_error_handling(input))
    OR (input.operation == "render_settings_app" AND uses_hardcoded_error_html(input))
    OR (input.operation == "create_sitemap_cache_dir" AND NOT validates_parent_writable(input))
    
    // Category 4: REST API & Integration Issues
    OR (input.operation == "call_rest_endpoint" AND NOT verifies_nonce_all_paths(input))
    OR (input.operation == "register_sitemap_rewrites" AND has_incomplete_regex_patterns(input))
    OR (input.operation == "handle_gsc_callback" AND NOT token_exchange_implemented(input))
    
    // Category 5: Security Vulnerabilities
    OR (input.operation == "redirect_rest_operation" AND NOT strictly_validates_redirect_type(input))
    OR (input.operation == "mutation_endpoint" AND NOT consistent_nonce_verification(input))
  )
END FUNCTION
```

### Examples

**Example 1: Gutenberg Asset Loading Failure**
- **Input**: User opens post editor in Gutenberg
- **Current Behavior**: PHP fatal error when `include` tries to load non-existent `build/gutenberg.asset.php`, sidebar never appears
- **Expected Behavior**: System checks file existence, logs error, uses fallback dependencies, sidebar loads with graceful degradation

**Example 2: AI Module Registry Mismatch**
- **Input**: Plugin initialization attempts to load AI module
- **Current Behavior**: Module_Manager looks for 'Modules\AI\AI' but actual class is 'Modules\AI\AI_Module', module fails to load silently
- **Expected Behavior**: Module registry uses correct class name 'Modules\AI\AI_Module', module loads successfully

**Example 3: Redux Store Registration Failure**
- **Input**: Gutenberg editor initializes and attempts to register 'meowseo/data' store
- **Current Behavior**: Registration fails, error logged to console, all components depending on store break
- **Expected Behavior**: Registration failure triggers fallback store or graceful degradation, components continue functioning with reduced features

**Example 4: Incomplete Nonce Verification**
- **Input**: REST API call to `/dashboard/discover-performance` endpoint
- **Current Behavior**: Nonce checked but not verified in all code paths before calling Dashboard_Widgets
- **Expected Behavior**: Nonce verified consistently in all code paths, invalid nonce returns 403 with security log entry

**Example 5: CSS Path Incorrect**
- **Input**: Gutenberg editor attempts to enqueue MeowSEO styles
- **Current Behavior**: System references `build/index.css` which doesn't exist, no styles applied
- **Expected Behavior**: System references correct `build/gutenberg.css` file, styles applied successfully

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- AI sidebar module (`build/ai-sidebar.js`) must continue to load successfully without any changes
- Admin settings page (`build/admin-settings.js`) must continue to load successfully without any changes
- Admin dashboard (`build/admin-dashboard.js`) must continue to load successfully without any changes
- Postmeta field registration for REST API access must continue to work with proper sanitization callbacks
- All sidebar tabs (General, Social, Schema, Advanced) and their functionality must remain unchanged
- Schema module JSON-LD generation must continue to output valid structured data
- Sitemap module XML generation must continue to generate valid XML for existing sitemap types
- Meta module tag output (title, description, Open Graph, Twitter Card, canonical) must remain unchanged
- Redirects module rule matching and execution must continue to work correctly
- Internal Links module suggestion algorithm must remain unchanged
- Monitor 404 module logging functionality must continue to capture 404 events
- WooCommerce module product page integration must remain unchanged
- Social module sharing metadata generation must continue to work
- REST API input validation and sanitization logic must remain unchanged
- Plugin initialization sequence and module boot order must remain unchanged

**Scope:**
All functionality that currently works correctly should be completely unaffected by this fix. This includes:
- All working asset loading paths (AI sidebar, admin settings, admin dashboard)
- All working module implementations (Meta, Schema, Sitemap, Redirects, Internal Links, Monitor 404, GSC, Social, WooCommerce)
- All working REST API endpoints with proper nonce verification
- All working error handling mechanisms
- All working security measures

## Hypothesized Root Cause

Based on the bug description and code analysis, the root causes are:

1. **Build Process Mismatch**: The webpack configuration generates `build/gutenberg.js` and `build/gutenberg.css`, but `class-gutenberg-assets.php` references `build/index.css` instead of `build/gutenberg.css`. Additionally, the asset file inclusion at line 40 has no existence check, causing fatal errors when build files are missing.

2. **Module Registry Inconsistency**: The module registry in `class-module-manager.php` line 42 maps 'ai' to 'Modules\AI\AI', but the actual class file is `class-ai-module.php` with class name `AI_Module`. This naming mismatch causes the autoloader to fail silently.

3. **Incomplete Implementations**: Multiple classes have incomplete method implementations:
   - `AI_Module` constructor has conditional instantiation but missing actual instantiation code
   - `AI_Module` boot() method is declared but not implemented
   - `src/admin/dashboard.js` has imports but no component implementation
   - `AiGeneratorPanel.js` handleGenerate() function is incomplete

4. **Missing Error Boundaries**: Redux store registration in both `src/store/index.js` and `src/gutenberg/store/index.ts` catches errors but only logs them without providing fallback mechanisms, causing cascading failures in dependent components.

5. **Inconsistent Security Patterns**: Some REST API endpoints verify nonces properly while others check but don't verify in all code paths, creating CSRF vulnerabilities. The redirect_type parameter validation is insufficient.

## Correctness Properties

Property 1: Bug Condition - Asset Loading with Error Handling

_For any_ system operation where asset files are loaded (Gutenberg assets, AI sidebar, admin settings, admin dashboard), the fixed code SHALL check file existence before attempting to include/enqueue, log appropriate errors with file paths when files are missing, use fallback dependencies when asset files are unavailable, and provide admin notices to aid debugging.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4**

Property 2: Bug Condition - Module Registry and Initialization

_For any_ module loading operation, the fixed Module_Manager SHALL use correct class names in the module registry matching actual class files, properly instantiate all module dependencies in correct initialization order, implement all required boot() methods to register hooks and initialize functionality, and complete all incomplete component and function implementations.

**Validates: Requirements 2.5, 2.6, 2.7, 2.8, 2.9**

Property 3: Bug Condition - Error Handling and Fallbacks

_For any_ error condition during store registration, worker instantiation, component rendering, or directory creation, the fixed code SHALL provide fallback mechanisms or graceful degradation, use proper React error boundaries instead of hardcoded HTML, validate preconditions before operations, and log detailed error messages for debugging.

**Validates: Requirements 2.10, 2.11, 2.12, 2.13, 2.14**

Property 4: Bug Condition - REST API and Integration Completeness

_For any_ REST API endpoint call, sitemap rewrite rule registration, or GSC authentication callback, the fixed code SHALL verify nonces properly in all code paths before processing requests, complete all incomplete regex patterns for sitemap routing, implement complete token exchange logic with error handling, and provide consistent error responses.

**Validates: Requirements 2.15, 2.16, 2.17**

Property 5: Bug Condition - Security Enhancement

_For any_ redirect operation or mutation endpoint call, the fixed code SHALL strictly validate redirect_type parameter against an allowlist of valid values (301, 302, 307, 308), verify nonces consistently across all mutation endpoints, log security events for audit trails, and prevent potential SQL injection and CSRF vulnerabilities.

**Validates: Requirements 2.18, 2.19**

Property 6: Preservation - Existing Functionality

_For any_ input that does NOT involve the 19 identified bug conditions (working asset paths, working modules, working REST endpoints, working error handling, working security measures), the fixed code SHALL produce exactly the same behavior as the original code, preserving all existing functionality for AI sidebar loading, admin settings, dashboard, postmeta registration, schema generation, sitemap generation, meta tag output, redirects, internal links, 404 monitoring, WooCommerce integration, social sharing, REST API validation, and plugin initialization.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13, 3.14, 3.15**

## Fix Implementation

### Changes Required

Assuming our root cause analysis is correct:

**Category 1: Editor Visibility Fixes**

**File**: `includes/modules/meta/class-gutenberg-assets.php`

**Function**: `enqueue_editor_assets()`

**Specific Changes**:
1. **Add File Existence Check for Asset File**: Before line 40 `$asset_file = include plugin_dir_path( __FILE__ ) . '../../../build/gutenberg.asset.php';`, add existence check and fallback
   - Check if `build/gutenberg.asset.php` exists
   - If missing, log error with file path and use fallback dependencies array
   - Provide admin notice for debugging

2. **Add File Existence Check for JavaScript Bundle**: Before `wp_enqueue_script()` call, verify `build/gutenberg.js` exists
   - If missing, log error and return early without enqueueing
   - Provide admin notice indicating build files need to be generated

3. **Correct CSS File Path**: Change line referencing `build/index.css` to `build/gutenberg.css`
   - Update `wp_enqueue_style()` call to use correct filename
   - Add existence check for CSS file as well

4. **Add Error Logging Helper**: Create private method `log_asset_error()` to centralize error logging
   - Log to WordPress debug log with file paths
   - Store admin notice in transient for display

**Category 2: Module Implementation Fixes**

**File**: `includes/class-module-manager.php`

**Property**: `$module_registry`

**Specific Changes**:
1. **Fix AI Module Registry Entry**: Change line 42 from `'ai' => 'Modules\AI\AI'` to `'ai' => 'Modules\AI\AI_Module'`
   - Ensure class name matches actual class file `class-ai-module.php`

**File**: `includes/modules/ai/class-ai-module.php`

**Function**: `__construct()`

**Specific Changes**:
1. **Complete Dependency Instantiation**: Replace conditional checks with actual instantiation
   - Remove `if ( class_exists( AI_Provider_Manager::class ) )` wrapper
   - Instantiate `AI_Provider_Manager`, `AI_Generator`, `AI_Settings`, `AI_REST` unconditionally
   - Add try-catch around instantiation with proper error handling

2. **Implement boot() Method**: Complete the boot() method implementation
   - Ensure all hooks are registered (rest_api_init, admin_enqueue_scripts, enqueue_block_editor_assets, save_post, meowseo_settings_tabs)
   - Verify all callbacks are properly bound

**File**: `src/admin/dashboard.js`

**Specific Changes**:
1. **Implement Dashboard Component**: Add complete React component implementation
   - Create DashboardApp component with proper structure
   - Implement data fetching for dashboard widgets
   - Add error handling and loading states
   - Export component for rendering

**File**: `src/ai/components/AiGeneratorPanel.js`

**Function**: `handleGenerate()`

**Specific Changes**:
1. **Complete API Call Implementation**: Implement missing API call logic
   - Add apiFetch call to `/meowseo/v1/ai/generate` endpoint
   - Implement proper error handling with user-friendly messages
   - Add loading state management
   - Implement success/failure feedback UI

**Category 3: Error Handling Improvements**

**File**: `src/store/index.js`

**Specific Changes**:
1. **Add Fallback Store Mechanism**: Wrap registerStore() call with fallback
   - If registration fails, create minimal store with default state
   - Provide graceful degradation for components
   - Log error but don't break editor

**File**: `src/gutenberg/store/index.ts`

**Specific Changes**:
1. **Add Graceful Degradation**: Enhance error handling in store registration
   - Provide fallback store implementation
   - Ensure components can function with reduced features
   - Add user-friendly error messages

**File**: `src/gutenberg/hooks/useAnalysis.ts`

**Specific Changes**:
1. **Complete Worker Instantiation**: Implement missing worker initialization
   - Add proper worker path resolution
   - Implement error handling for worker failures
   - Add fallback to synchronous analysis if workers fail

**File**: `src/admin-settings.js`

**Specific Changes**:
1. **Replace Hardcoded Error HTML with Error Boundary**: Wrap render in React error boundary
   - Create ErrorBoundary component
   - Replace hardcoded HTML error message with proper React error UI
   - Provide recovery options

**File**: `includes/modules/sitemap/class-sitemap-cache.php`

**Function**: `ensure_cache_directory()`

**Specific Changes**:
1. **Add Parent Directory Validation**: Before `wp_mkdir_p()` call, validate parent is writable
   - Check parent directory exists and is writable
   - Provide fallback locations if creation fails
   - Log detailed error messages with permissions info

**Category 4: REST API & Integration Fixes**

**File**: `includes/class-rest-api.php`

**Functions**: `get_discover_performance()`, `get_index_queue()`

**Specific Changes**:
1. **Ensure Consistent Nonce Verification**: Add nonce verification at the start of both methods
   - Call `verify_nonce()` before any processing
   - Return 403 with proper error response if verification fails
   - Log security events for audit trails

**File**: `includes/modules/sitemap/class-sitemap.php`

**Function**: `register_rewrite_rules()`

**Specific Changes**:
1. **Complete Rewrite Rule Regex Patterns**: Add missing regex patterns for all sitemap types
   - Complete patterns for index, posts, pages, custom post types
   - Add patterns for paginated sitemaps
   - Add patterns for news and video sitemaps

**File**: `includes/modules/gsc/class-gsc-auth.php`

**Function**: `handle_callback()`

**Specific Changes**:
1. **Implement Complete Token Exchange Logic**: Add missing implementation
   - Implement OAuth token exchange with Google
   - Add error handling for failed exchanges
   - Implement token storage with encryption
   - Add proper redirect handling after success/failure

**Category 5: Security Enhancements**

**File**: `includes/modules/redirects/class-redirects-rest.php`

**Function**: Redirect operation methods

**Specific Changes**:
1. **Add Strict Redirect Type Validation**: Before database operations, validate redirect_type
   - Define allowlist: [301, 302, 307, 308]
   - Validate input against allowlist before `$wpdb->prepare()`
   - Return 400 error for invalid redirect types
   - Log validation failures

**File**: `includes/class-rest-api.php`

**All Mutation Endpoints**

**Specific Changes**:
1. **Ensure Consistent Nonce Verification**: Audit all mutation endpoints
   - Verify all POST/PUT/DELETE endpoints call `verify_nonce()`
   - Ensure verification happens before any processing
   - Standardize error responses for invalid nonces
   - Add security logging for all nonce failures

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bugs on unfixed code, then verify the fixes work correctly and preserve existing behavior. Given the complexity of 19 bugs across 5 categories, testing will be organized by category with both unit tests and integration tests.

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples that demonstrate the bugs BEFORE implementing the fix. Confirm or refute the root cause analysis. If we refute, we will need to re-hypothesize.

**Test Plan**: Write tests that simulate each bug condition and assert that the expected failure occurs. Run these tests on the UNFIXED code to observe failures and understand the root causes.

**Test Cases**:

**Category 1: Editor Visibility**
1. **Missing Asset File Test**: Delete `build/gutenberg.asset.php` and attempt to load editor (will cause fatal error on unfixed code)
2. **Missing JS Bundle Test**: Delete `build/gutenberg.js` and verify sidebar doesn't appear (will fail on unfixed code)
3. **Wrong CSS Path Test**: Verify system attempts to load `build/index.css` instead of `build/gutenberg.css` (will fail on unfixed code)
4. **No Error Handling Test**: Verify no file existence checks before include/enqueue (will fail on unfixed code)

**Category 2: Module Implementation**
5. **AI Module Registry Test**: Verify module registry references 'Modules\AI\AI' instead of 'Modules\AI\AI_Module' (will fail on unfixed code)
6. **AI Module Constructor Test**: Verify dependencies are not instantiated in constructor (will fail on unfixed code)
7. **AI Module Boot Test**: Verify boot() method is not implemented (will fail on unfixed code)
8. **Dashboard Component Test**: Verify dashboard component is not implemented (will fail on unfixed code)
9. **AI Generate Handler Test**: Verify handleGenerate() is incomplete (will fail on unfixed code)

**Category 3: Error Handling**
10. **Redux Store Failure Test**: Force store registration failure and verify no fallback (will fail on unfixed code)
11. **Gutenberg Store Failure Test**: Force store registration failure and verify no graceful degradation (will fail on unfixed code)
12. **Worker Instantiation Test**: Verify worker instantiation is incomplete (will fail on unfixed code)
13. **Settings Render Error Test**: Force render error and verify hardcoded HTML is used (will fail on unfixed code)
14. **Cache Directory Test**: Verify no parent directory validation before mkdir (will fail on unfixed code)

**Category 4: REST API & Integration**
15. **Nonce Verification Test**: Call endpoints without proper nonce and verify inconsistent verification (will fail on unfixed code)
16. **Sitemap Rewrite Test**: Verify rewrite rules have incomplete regex patterns (will fail on unfixed code)
17. **GSC Callback Test**: Verify handle_callback() is incomplete (will fail on unfixed code)

**Category 5: Security**
18. **Redirect Type Validation Test**: Send invalid redirect_type and verify insufficient validation (will fail on unfixed code)
19. **Mutation Nonce Test**: Call mutation endpoints and verify inconsistent nonce verification (will fail on unfixed code)

**Expected Counterexamples**:
- Fatal PHP errors when asset files are missing
- Module loading failures due to registry mismatches
- Component breakage due to missing error handling
- Security vulnerabilities due to insufficient validation
- Possible causes: build process mismatch, module registry inconsistency, incomplete implementations, missing error boundaries, inconsistent security patterns

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds, the fixed code produces the expected behavior.

**Pseudocode:**
```
FOR ALL input WHERE isBugCondition(input) DO
  result := fixedCode(input)
  ASSERT expectedBehavior(result)
END FOR
```

**Test Categories:**

**Category 1: Asset Loading Tests**
- Test asset loading with missing files (should log error and use fallback)
- Test asset loading with correct files (should load successfully)
- Test CSS path correction (should reference gutenberg.css)
- Test error logging and admin notices (should provide debugging info)

**Category 2: Module Implementation Tests**
- Test AI module loading with correct registry (should load successfully)
- Test AI module dependency instantiation (should instantiate all dependencies)
- Test AI module boot (should register all hooks)
- Test dashboard component rendering (should render complete component)
- Test AI generate handler (should make API call and handle responses)

**Category 3: Error Handling Tests**
- Test Redux store registration failure (should provide fallback)
- Test Gutenberg store registration failure (should degrade gracefully)
- Test worker instantiation failure (should fall back to synchronous)
- Test settings render error (should use error boundary)
- Test cache directory creation failure (should validate and provide fallback)

**Category 4: REST API & Integration Tests**
- Test REST endpoints with invalid nonce (should return 403 consistently)
- Test sitemap rewrite rules (should have complete regex patterns)
- Test GSC callback (should complete token exchange)

**Category 5: Security Tests**
- Test redirect operations with invalid type (should validate strictly)
- Test mutation endpoints with invalid nonce (should verify consistently)

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold, the fixed code produces the same result as the original code.

**Pseudocode:**
```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT originalCode(input) = fixedCode(input)
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many test cases automatically across the input domain
- It catches edge cases that manual unit tests might miss
- It provides strong guarantees that behavior is unchanged for all non-buggy inputs

**Test Plan**: Observe behavior on UNFIXED code first for working functionality, then write property-based tests capturing that behavior.

**Test Cases**:

**Preservation Category 1: Working Asset Loading**
1. **AI Sidebar Loading**: Verify `build/ai-sidebar.js` continues to load successfully
2. **Admin Settings Loading**: Verify `build/admin-settings.js` continues to load successfully
3. **Admin Dashboard Loading**: Verify `build/admin-dashboard.js` continues to load successfully

**Preservation Category 2: Working Modules**
4. **Meta Module**: Verify meta tag output continues to work correctly
5. **Schema Module**: Verify JSON-LD generation continues to work correctly
6. **Sitemap Module**: Verify XML generation continues to work correctly
7. **Redirects Module**: Verify redirect matching continues to work correctly
8. **Internal Links Module**: Verify suggestion algorithm continues to work correctly
9. **Monitor 404 Module**: Verify 404 logging continues to work correctly
10. **GSC Module**: Verify existing GSC functionality continues to work correctly
11. **Social Module**: Verify social metadata continues to work correctly
12. **WooCommerce Module**: Verify product integration continues to work correctly

**Preservation Category 3: Working REST API**
13. **Meta CRUD Endpoints**: Verify meta get/update continues to work correctly
14. **Settings Endpoints**: Verify settings get/update continues to work correctly
15. **Dashboard Endpoints**: Verify dashboard data loading continues to work correctly
16. **Suggestion Endpoint**: Verify internal link suggestions continue to work correctly
17. **Public SEO Endpoints**: Verify public SEO data access continues to work correctly

**Preservation Category 4: Working Error Handling**
18. **Existing Error Boundaries**: Verify existing error handling continues to work correctly
19. **Existing Validation**: Verify existing input validation continues to work correctly

**Preservation Category 5: Working Security**
20. **Existing Nonce Verification**: Verify working nonce verification continues to work correctly
21. **Existing Input Sanitization**: Verify existing sanitization continues to work correctly

### Unit Tests

**Asset Loading Tests**
- Test file existence checks for all asset types
- Test fallback dependency arrays when assets missing
- Test error logging with correct file paths
- Test admin notice generation

**Module Loading Tests**
- Test module registry with correct class names
- Test module dependency instantiation order
- Test module boot() method hook registration
- Test module initialization error handling

**Error Handling Tests**
- Test store registration fallback mechanisms
- Test error boundary component rendering
- Test worker instantiation with error handling
- Test directory creation with validation

**REST API Tests**
- Test nonce verification in all code paths
- Test redirect type validation with allowlist
- Test error response consistency
- Test security logging

**Component Tests**
- Test dashboard component rendering
- Test AI generator panel functionality
- Test error boundary behavior
- Test loading states

### Property-Based Tests

**Asset Loading Properties**
- Generate random file existence scenarios and verify correct handling
- Generate random asset paths and verify correct resolution
- Test that all asset loading operations handle missing files gracefully

**Module Loading Properties**
- Generate random module configurations and verify correct loading
- Generate random dependency graphs and verify correct instantiation order
- Test that all modules boot correctly regardless of load order

**Error Handling Properties**
- Generate random error conditions and verify graceful degradation
- Generate random component states and verify error boundaries catch all errors
- Test that all operations provide fallback mechanisms

**REST API Properties**
- Generate random nonce values and verify consistent verification
- Generate random redirect types and verify strict validation
- Test that all mutation endpoints verify nonces before processing

**Preservation Properties**
- Generate random working configurations and verify unchanged behavior
- Generate random valid inputs and verify identical output to original code
- Test that all non-buggy code paths produce identical results

### Integration Tests

**Full Editor Loading Flow**
- Test complete Gutenberg editor initialization with MeowSEO sidebar
- Test sidebar tab switching and functionality
- Test asset loading in various WordPress environments
- Test error recovery when build files are missing

**Module System Integration**
- Test complete plugin initialization with all modules
- Test module interdependencies and communication
- Test module boot sequence and hook registration
- Test module error handling and recovery

**REST API Integration**
- Test complete REST API request/response cycle
- Test authentication and authorization flow
- Test error handling and security logging
- Test cache headers and ETag support

**Security Integration**
- Test complete CSRF protection across all endpoints
- Test input validation and sanitization pipeline
- Test security logging and audit trail
- Test SQL injection prevention

**User Workflow Integration**
- Test complete post creation workflow with SEO metadata
- Test complete settings configuration workflow
- Test complete dashboard data loading workflow
- Test complete AI generation workflow
