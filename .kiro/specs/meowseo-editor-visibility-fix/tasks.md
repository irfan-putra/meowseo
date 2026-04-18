# Implementation Plan

## Phase 1: Exploratory Bug Condition Testing (BEFORE FIX)

### Category 1: Editor Visibility Bug Exploration

- [x] 1. Write bug condition exploration test - Asset Loading Failures
  - **Property 1: Bug Condition** - Asset Loading with Missing Files
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bugs exist
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate asset loading bugs exist
  - **Scoped PBT Approach**: Test concrete failing cases - missing asset files, wrong CSS path, no error handling
  - Test implementation details from Bug Condition in design:
    - Test that loading Gutenberg editor with missing `build/gutenberg.asset.php` causes fatal error
    - Test that missing `build/gutenberg.js` prevents sidebar registration
    - Test that system references `build/index.css` instead of `build/gutenberg.css`
    - Test that no file existence checks occur before include/enqueue operations
  - The test assertions should match the Expected Behavior Properties from design (file existence checks, error logging, fallback mechanisms, admin notices)
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bugs exist)
  - Document counterexamples found to understand root cause
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

### Category 2: Module Implementation Bug Exploration

- [x] 2. Write bug condition exploration test - Module Loading Failures
  - **Property 1: Bug Condition** - Module Registry and Initialization Failures
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bugs exist
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate module loading bugs exist
  - **Scoped PBT Approach**: Test concrete failing cases - registry mismatch, incomplete constructor, missing boot method, incomplete components
  - Test implementation details from Bug Condition in design:
    - Test that module registry references 'Modules\AI\AI' instead of 'Modules\AI\AI_Module'
    - Test that AI_Module constructor has incomplete dependency instantiation
    - Test that AI_Module boot() method is not implemented
    - Test that dashboard component has no implementation
    - Test that handleGenerate() function is incomplete
  - The test assertions should match the Expected Behavior Properties from design (correct class names, proper instantiation, complete implementations)
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bugs exist)
  - Document counterexamples found to understand root cause
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 2.5, 2.6, 2.7, 2.8, 2.9_

### Category 3: Error Handling Bug Exploration

- [x] 3. Write bug condition exploration test - Error Handling Deficiencies
  - **Property 1: Bug Condition** - Missing Error Handling and Fallbacks
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bugs exist
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate error handling bugs exist
  - **Scoped PBT Approach**: Test concrete failing cases - store registration failures, worker instantiation issues, hardcoded errors, directory creation failures
  - Test implementation details from Bug Condition in design:
    - Test that Redux store registration failure has no fallback mechanism
    - Test that Gutenberg store registration failure has no graceful degradation
    - Test that worker instantiation is incomplete with missing error handling
    - Test that settings render error uses hardcoded HTML instead of error boundary
    - Test that cache directory creation has no parent directory validation
  - The test assertions should match the Expected Behavior Properties from design (fallback mechanisms, error boundaries, validation, detailed logging)
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bugs exist)
  - Document counterexamples found to understand root cause
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 2.10, 2.11, 2.12, 2.13, 2.14_

### Category 4: REST API & Integration Bug Exploration

- [x] 4. Write bug condition exploration test - REST API Incompleteness
  - **Property 1: Bug Condition** - Incomplete REST API and Integration
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bugs exist
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate REST API bugs exist
  - **Scoped PBT Approach**: Test concrete failing cases - inconsistent nonce verification, incomplete rewrite rules, missing token exchange
  - Test implementation details from Bug Condition in design:
    - Test that REST endpoints check nonce but don't verify in all code paths
    - Test that sitemap rewrite rules have incomplete regex patterns
    - Test that GSC callback has incomplete token exchange logic
  - The test assertions should match the Expected Behavior Properties from design (consistent nonce verification, complete regex patterns, complete token exchange)
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bugs exist)
  - Document counterexamples found to understand root cause
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 2.15, 2.16, 2.17_

### Category 5: Security Bug Exploration

- [x] 5. Write bug condition exploration test - Security Vulnerabilities
  - **Property 1: Bug Condition** - Insufficient Security Validation
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bugs exist
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate security bugs exist
  - **Scoped PBT Approach**: Test concrete failing cases - insufficient redirect type validation, inconsistent nonce verification
  - Test implementation details from Bug Condition in design:
    - Test that redirect_type parameter validation is insufficient
    - Test that mutation endpoints have inconsistent nonce verification
  - The test assertions should match the Expected Behavior Properties from design (strict validation, consistent verification, security logging)
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bugs exist)
  - Document counterexamples found to understand root cause
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 2.18, 2.19_

## Phase 2: Preservation Property Testing (BEFORE FIX)

- [x] 6. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Existing Functionality Unchanged
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for non-buggy inputs (working functionality)
  - Write property-based tests capturing observed behavior patterns from Preservation Requirements
  - Property-based testing generates many test cases for stronger guarantees
  - Test preservation categories from design:
    - **Category 1: Working Asset Loading** - AI sidebar, admin settings, admin dashboard continue to load successfully
    - **Category 2: Working Modules** - Meta, Schema, Sitemap, Redirects, Internal Links, Monitor 404, GSC, Social, WooCommerce modules continue to work correctly
    - **Category 3: Working REST API** - Meta CRUD, Settings, Dashboard, Suggestion, Public SEO endpoints continue to work correctly
    - **Category 4: Working Error Handling** - Existing error boundaries and validation continue to work correctly
    - **Category 5: Working Security** - Existing nonce verification and input sanitization continue to work correctly
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13, 3.14, 3.15_

## Phase 3: Implementation

### Category 1: Editor Visibility Fixes

- [x] 7. Fix editor visibility issues
  
  - [x] 7.1 Add file existence checks and error handling to Gutenberg_Assets
    - Modify `includes/modules/meta/class-gutenberg-assets.php`
    - Add file existence check before including `build/gutenberg.asset.php` at line 40
    - Add fallback dependencies array when asset file is missing
    - Add file existence check before enqueueing `build/gutenberg.js`
    - Add error logging helper method `log_asset_error()` for centralized logging
    - Log errors with file paths to WordPress debug log
    - Store admin notices in transient for display
    - _Bug_Condition: (operation == "load_gutenberg_assets" AND NOT file_exists("build/gutenberg.asset.php")) OR (operation == "enqueue_gutenberg_js" AND NOT file_exists("build/gutenberg.js")) OR (operation == "load_asset_file" AND NOT has_file_existence_check(input))_
    - _Expected_Behavior: Check file existence before include/enqueue, log errors with file paths, use fallback dependencies, provide admin notices_
    - _Preservation: AI sidebar loading, admin settings loading, admin dashboard loading remain unchanged_
    - _Requirements: 2.1, 2.2, 2.4, 3.1, 3.2, 3.3_

  - [x] 7.2 Correct CSS file path
    - Modify `includes/modules/meta/class-gutenberg-assets.php`
    - Change CSS file reference from `build/index.css` to `build/gutenberg.css`
    - Add file existence check for CSS file
    - Update `wp_enqueue_style()` call with correct filename
    - _Bug_Condition: (operation == "enqueue_gutenberg_css" AND css_path == "build/index.css")_
    - _Expected_Behavior: Reference correct build/gutenberg.css file, add existence check_
    - _Preservation: Existing CSS loading for other modules remains unchanged_
    - _Requirements: 2.3, 3.5_

  - [x] 7.3 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Asset Loading with Error Handling
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 1
    - **EXPECTED OUTCOME**: Test PASSES (confirms bugs are fixed)
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

### Category 2: Module Implementation Fixes

- [-] 8. Fix module implementation issues

  - [x] 8.1 Fix AI module registry entry
    - Modify `includes/class-module-manager.php`
    - Change line 42 from `'ai' => 'Modules\AI\AI'` to `'ai' => 'Modules\AI\AI_Module'`
    - Ensure class name matches actual class file `class-ai-module.php`
    - _Bug_Condition: (operation == "load_ai_module" AND module_registry["ai"] != "Modules\\AI\\AI_Module")_
    - _Expected_Behavior: Use correct class name matching actual class file_
    - _Preservation: All other module registry entries remain unchanged_
    - _Requirements: 2.5, 3.15_

  - [x] 8.2 Complete AI module constructor
    - Modify `includes/modules/ai/class-ai-module.php`
    - Complete constructor at line 80+ with proper dependency instantiation
    - Remove conditional `if ( class_exists() )` wrappers
    - Instantiate AI_Provider_Manager, AI_Generator, AI_Settings, AI_REST unconditionally
    - Add try-catch around instantiation with proper error handling
    - Ensure correct initialization order
    - _Bug_Condition: (operation == "instantiate_ai_module" AND NOT all_dependencies_instantiated(input))_
    - _Expected_Behavior: Properly instantiate all dependencies with error handling_
    - _Preservation: Existing module initialization sequence remains unchanged_
    - _Requirements: 2.6, 3.15_

  - [x] 8.3 Implement AI module boot() method
    - Modify `includes/modules/ai/class-ai-module.php`
    - Implement complete boot() method
    - Register all hooks: rest_api_init, admin_enqueue_scripts, enqueue_block_editor_assets, save_post, meowseo_settings_tabs
    - Verify all callbacks are properly bound
    - _Bug_Condition: (operation == "boot_ai_module" AND NOT method_exists(AI_Module, "boot"))_
    - _Expected_Behavior: Complete boot() method registering all hooks_
    - _Preservation: Existing module boot sequence remains unchanged_
    - _Requirements: 2.7, 3.15_

  - [x] 8.4 Implement dashboard component
    - Modify `src/admin/dashboard.js`
    - Create complete DashboardApp React component
    - Implement data fetching for dashboard widgets
    - Add error handling and loading states
    - Export component for rendering
    - _Bug_Condition: (operation == "render_admin_dashboard" AND NOT component_implemented(input))_
    - _Expected_Behavior: Complete React component with data fetching and error handling_
    - _Preservation: Existing dashboard data loading remains unchanged_
    - _Requirements: 2.8, 3.3_

  - [x] 8.5 Complete AI generate handler
    - Modify `src/ai/components/AiGeneratorPanel.js`
    - Complete handleGenerate() function at line 70+
    - Add apiFetch call to `/meowseo/v1/ai/generate` endpoint
    - Implement proper error handling with user-friendly messages
    - Add loading state management
    - Implement success/failure feedback UI
    - _Bug_Condition: (operation == "handle_ai_generate" AND NOT api_call_implemented(input))_
    - _Expected_Behavior: Complete API call with error handling and feedback_
    - _Preservation: Existing AI functionality remains unchanged_
    - _Requirements: 2.9_

  - [x] 8.6 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Module Registry and Initialization
    - **IMPORTANT**: Re-run the SAME test from task 2 - do NOT write a new test
    - The test from task 2 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 2
    - **EXPECTED OUTCOME**: Test PASSES (confirms bugs are fixed)
    - _Requirements: 2.5, 2.6, 2.7, 2.8, 2.9_

### Category 3: Error Handling Improvements

- [-] 9. Fix error handling deficiencies

  - [x] 9.1 Add fallback store mechanism to Redux store
    - Modify `src/store/index.js`
    - Wrap registerStore() call at lines 95-100 with fallback
    - If registration fails, create minimal store with default state
    - Provide graceful degradation for components
    - Log error but don't break editor
    - _Bug_Condition: (operation == "register_redux_store" AND NOT has_fallback_mechanism(input))_
    - _Expected_Behavior: Fallback store or graceful degradation on failure_
    - _Preservation: Existing store functionality remains unchanged_
    - _Requirements: 2.10, 3.14_

  - [x] 9.2 Add graceful degradation to Gutenberg store
    - Modify `src/gutenberg/store/index.ts`
    - Enhance error handling in store registration
    - Provide fallback store implementation
    - Ensure components can function with reduced features
    - Add user-friendly error messages
    - _Bug_Condition: (operation == "register_gutenberg_store" AND NOT has_graceful_degradation(input))_
    - _Expected_Behavior: Fallback store or graceful degradation on failure_
    - _Preservation: Existing store functionality remains unchanged_
    - _Requirements: 2.11, 3.14_

  - [x] 9.3 Complete worker instantiation in useAnalysis hook
    - Modify `src/gutenberg/hooks/useAnalysis.ts`
    - Complete worker initialization at line 90+
    - Add proper worker path resolution
    - Implement error handling for worker failures
    - Add fallback to synchronous analysis if workers fail
    - _Bug_Condition: (operation == "instantiate_worker" AND NOT has_error_handling(input))_
    - _Expected_Behavior: Complete worker instantiation with error handling and fallback_
    - _Preservation: Existing analysis functionality remains unchanged_
    - _Requirements: 2.12_

  - [x] 9.4 Replace hardcoded error HTML with error boundary
    - Modify `src/admin-settings.js`
    - Create ErrorBoundary React component
    - Wrap render in error boundary
    - Replace hardcoded HTML error message with proper React error UI
    - Provide recovery options
    - _Bug_Condition: (operation == "render_settings_app" AND uses_hardcoded_error_html(input))_
    - _Expected_Behavior: Proper React error boundary with recovery options_
    - _Preservation: Existing settings functionality remains unchanged_
    - _Requirements: 2.13, 3.2_

  - [x] 9.5 Add parent directory validation to cache directory creation
    - Modify `includes/modules/sitemap/class-sitemap-cache.php`
    - Add validation before wp_mkdir_p() call at line 74+
    - Check parent directory exists and is writable
    - Provide fallback locations if creation fails
    - Log detailed error messages with permissions info
    - _Bug_Condition: (operation == "create_sitemap_cache_dir" AND NOT validates_parent_writable(input))_
    - _Expected_Behavior: Validate parent directory, provide fallback, log detailed errors_
    - _Preservation: Existing sitemap functionality remains unchanged_
    - _Requirements: 2.14, 3.7_

  - [x] 9.6 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Error Handling and Fallbacks
    - **IMPORTANT**: Re-run the SAME test from task 3 - do NOT write a new test
    - The test from task 3 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 3
    - **EXPECTED OUTCOME**: Test PASSES (confirms bugs are fixed)
    - _Requirements: 2.10, 2.11, 2.12, 2.13, 2.14_

### Category 4: REST API & Integration Fixes

- [x] 10. Fix REST API and integration issues

  - [x] 10.1 Ensure consistent nonce verification in REST endpoints
    - Modify `includes/class-rest-api.php`
    - Add nonce verification at start of get_discover_performance() method at line 820+
    - Add nonce verification at start of get_index_queue() method
    - Call verify_nonce() before any processing
    - Return 403 with proper error response if verification fails
    - Log security events for audit trails
    - _Bug_Condition: (operation == "call_rest_endpoint" AND NOT verifies_nonce_all_paths(input))_
    - _Expected_Behavior: Verify nonces in all code paths before processing_
    - _Preservation: Existing REST API validation remains unchanged_
    - _Requirements: 2.15, 3.14_

  - [x] 10.2 Complete sitemap rewrite rule regex patterns
    - Modify `includes/modules/sitemap/class-sitemap.php`
    - Complete rewrite rules at line 107+
    - Add missing regex patterns for all sitemap types: index, posts, pages, custom post types
    - Add patterns for paginated sitemaps
    - Add patterns for news and video sitemaps
    - _Bug_Condition: (operation == "register_sitemap_rewrites" AND has_incomplete_regex_patterns(input))_
    - _Expected_Behavior: Complete regex patterns for all sitemap types_
    - _Preservation: Existing sitemap generation remains unchanged_
    - _Requirements: 2.16, 3.7_

  - [x] 10.3 Implement complete GSC token exchange logic
    - Modify `includes/modules/gsc/class-gsc-auth.php`
    - Complete handle_callback() method at line 103+
    - Implement OAuth token exchange with Google
    - Add error handling for failed exchanges
    - Implement token storage with encryption
    - Add proper redirect handling after success/failure
    - _Bug_Condition: (operation == "handle_gsc_callback" AND NOT token_exchange_implemented(input))_
    - _Expected_Behavior: Complete token exchange with error handling and storage_
    - _Preservation: Existing GSC functionality remains unchanged_
    - _Requirements: 2.17, 3.11_

  - [x] 10.4 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - REST API and Integration Completeness
    - **IMPORTANT**: Re-run the SAME test from task 4 - do NOT write a new test
    - The test from task 4 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 4
    - **EXPECTED OUTCOME**: Test PASSES (confirms bugs are fixed)
    - _Requirements: 2.15, 2.16, 2.17_

### Category 5: Security Enhancements

- [x] 11. Fix security vulnerabilities

  - [x] 11.1 Add strict redirect type validation
    - Modify `includes/modules/redirects/class-redirects-rest.php`
    - Add validation before database operations at line 250
    - Define allowlist: [301, 302, 307, 308]
    - Validate redirect_type against allowlist before $wpdb->prepare()
    - Return 400 error for invalid redirect types
    - Log validation failures
    - _Bug_Condition: (operation == "redirect_rest_operation" AND NOT strictly_validates_redirect_type(input))_
    - _Expected_Behavior: Strict validation against allowlist before database operations_
    - _Preservation: Existing redirect functionality remains unchanged_
    - _Requirements: 2.18, 3.9_

  - [x] 11.2 Ensure consistent nonce verification across all mutation endpoints
    - Modify `includes/class-rest-api.php`
    - Audit all mutation endpoints (POST/PUT/DELETE)
    - Verify all endpoints call verify_nonce()
    - Ensure verification happens before any processing
    - Standardize error responses for invalid nonces
    - Add security logging for all nonce failures
    - _Bug_Condition: (operation == "mutation_endpoint" AND NOT consistent_nonce_verification(input))_
    - _Expected_Behavior: Consistent nonce verification across all mutation endpoints_
    - _Preservation: Existing REST API security remains unchanged_
    - _Requirements: 2.19, 3.14_

  - [x] 11.3 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Security Enhancement
    - **IMPORTANT**: Re-run the SAME test from task 5 - do NOT write a new test
    - The test from task 5 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 5
    - **EXPECTED OUTCOME**: Test PASSES (confirms bugs are fixed)
    - _Requirements: 2.18, 2.19_

## Phase 4: Final Verification

- [x] 12. Verify preservation tests still pass
  - **Property 2: Preservation** - Existing Functionality Unchanged
  - **IMPORTANT**: Re-run the SAME tests from task 6 - do NOT write new tests
  - Run preservation property tests from step 6
  - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)
  - Confirm all tests still pass after fix (no regressions)
  - Verify all 5 preservation categories:
    - Working asset loading (AI sidebar, admin settings, admin dashboard)
    - Working modules (Meta, Schema, Sitemap, Redirects, Internal Links, Monitor 404, GSC, Social, WooCommerce)
    - Working REST API (Meta CRUD, Settings, Dashboard, Suggestion, Public SEO)
    - Working error handling (existing boundaries and validation)
    - Working security (existing nonce verification and sanitization)
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13, 3.14, 3.15_

- [x] 13. Checkpoint - Ensure all tests pass
  - Run complete test suite (unit tests, property-based tests, integration tests)
  - Verify all bug condition tests pass (tasks 1-5 now passing)
  - Verify all preservation tests pass (task 6 still passing)
  - Verify all implementation verification tests pass (tasks 7.3, 8.6, 9.6, 10.4, 11.3)
  - Ensure no regressions in existing functionality
  - Ask the user if questions arise
