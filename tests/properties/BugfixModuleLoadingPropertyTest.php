<?php
/**
 * Bug Condition Exploration Test - Module Loading Failures
 *
 * This test encodes the EXPECTED BEHAVIOR (correct behavior after fix).
 * It MUST FAIL on unfixed code to prove the bugs exist.
 *
 * **Validates: Requirements 2.5, 2.6, 2.7, 2.8, 2.9**
 *
 * Bug Conditions Being Tested:
 * 1. Module registry references 'Modules\AI\AI' instead of 'Modules\AI\AI_Module'
 * 2. AI_Module constructor has incomplete dependency instantiation
 * 3. AI_Module boot() method is not implemented
 * 4. Dashboard component has no implementation
 * 5. handleGenerate() function is incomplete
 *
 * Expected Behavior Properties (what SHOULD happen after fix):
 * - Module registry SHOULD use correct class name 'Modules\AI\AI_Module'
 * - AI_Module constructor SHOULD properly instantiate all dependencies
 * - AI_Module boot() method SHOULD be complete with all hook registrations
 * - Dashboard component SHOULD have complete React component implementation
 * - handleGenerate() function SHOULD have complete API call implementation
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use MeowSEO\Module_Manager;
use MeowSEO\Options;
use WP_UnitTestCase;

/**
 * Property-Based Test for Module Loading Bug Conditions
 *
 * This test runs BEFORE the fix is implemented to surface counterexamples.
 */
class BugfixModuleLoadingPropertyTest extends WP_UnitTestCase {

	/**
	 * Module Manager instance
	 *
	 * @var Module_Manager
	 */
	private $module_manager;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = new Options();
		$this->module_manager = new Module_Manager( $this->options );
	}

	/**
	 * Test Property 1: Module registry uses correct AI module class name
	 *
	 * EXPECTED BEHAVIOR: Module registry should reference 'Modules\AI\AI_Module'
	 * to match the actual class file class-ai-module.php.
	 *
	 * CURRENT BEHAVIOR (UNFIXED): Module registry references 'Modules\AI\AI'
	 * at line 42 of class-module-manager.php, which doesn't match the actual class.
	 *
	 * This test will FAIL on unfixed code because:
	 * - Line 42 has: 'ai' => 'Modules\AI\AI'
	 * - Should be: 'ai' => 'Modules\AI\AI_Module'
	 * - Actual class file is class-ai-module.php with class name AI_Module
	 */
	public function test_module_registry_correct_ai_class_name() {
		// Read the Module_Manager source file
		$source_file = dirname( __DIR__, 2 ) . '/includes/class-module-manager.php';
		$source_content = file_get_contents( $source_file );

		// Check if the module registry has the correct AI module class name
		$has_correct_class_name = preg_match(
			"/'ai'\s*=>\s*'Modules\\\\AI\\\\AI_Module'/",
			$source_content
		);

		$has_wrong_class_name = preg_match(
			"/'ai'\s*=>\s*'Modules\\\\AI\\\\AI'(?!_Module)/",
			$source_content
		);

		// EXPECTED BEHAVIOR: Should reference 'Modules\AI\AI_Module'
		$this->assertTrue(
			(bool) $has_correct_class_name && ! $has_wrong_class_name,
			'EXPECTED BEHAVIOR: Module registry should reference \'Modules\AI\AI_Module\' to match actual class file. ' .
			'CURRENT BEHAVIOR: Module registry references \'Modules\AI\AI\' at line 42 (class-module-manager.php), ' .
			'but actual class file is class-ai-module.php with class name AI_Module.'
		);
	}

	/**
	 * Test Property 2: AI_Module constructor properly instantiates all dependencies
	 *
	 * EXPECTED BEHAVIOR: AI_Module constructor should unconditionally instantiate
	 * all dependencies (AI_Provider_Manager, AI_Generator, AI_Settings, AI_REST)
	 * with proper error handling.
	 *
	 * CURRENT BEHAVIOR (UNFIXED): Constructor has conditional class_exists() checks
	 * but doesn't actually instantiate the dependencies (line 80+ in class-ai-module.php).
	 *
	 * This test will FAIL on unfixed code because:
	 * - Constructor wraps instantiation in if ( class_exists() ) conditionals
	 * - Dependencies are not actually instantiated unconditionally
	 * - Missing try-catch error handling
	 */
	public function test_ai_module_constructor_instantiates_dependencies() {
		// Read the AI_Module source file
		$source_file = dirname( __DIR__, 2 ) . '/includes/modules/ai/class-ai-module.php';
		$source_content = file_get_contents( $source_file );

		// Check if constructor has unconditional instantiation (not wrapped in class_exists)
		// Expected pattern: $this->provider_manager = new AI_Provider_Manager(...);
		// Not expected: if ( class_exists(...) ) { $this->provider_manager = ... }

		// Count conditional instantiations (bad pattern)
		$conditional_instantiations = preg_match_all(
			'/if\s*\(\s*class_exists\s*\([^)]+\)\s*\)\s*\{[^}]*\$this->(provider_manager|generator|settings|rest)\s*=/s',
			$source_content
		);

		// Count unconditional instantiations (good pattern)
		$unconditional_instantiations = preg_match_all(
			'/\$this->(provider_manager|generator|settings|rest)\s*=\s*new\s+/s',
			$source_content
		);

		// Check for try-catch around instantiation
		$has_try_catch = preg_match(
			'/try\s*\{[^}]*new\s+(AI_Provider_Manager|AI_Generator|AI_Settings|AI_REST)/s',
			$source_content
		);

		// EXPECTED BEHAVIOR: Should have unconditional instantiation with try-catch
		$this->assertTrue(
			$unconditional_instantiations >= 4 && $conditional_instantiations === 0 && $has_try_catch,
			'EXPECTED BEHAVIOR: AI_Module constructor should unconditionally instantiate all dependencies ' .
			'(AI_Provider_Manager, AI_Generator, AI_Settings, AI_REST) with try-catch error handling. ' .
			'CURRENT BEHAVIOR: Constructor has conditional class_exists() checks at line 80+ (class-ai-module.php) ' .
			'but dependencies are not actually instantiated unconditionally. ' .
			'Found ' . $conditional_instantiations . ' conditional instantiations (should be 0), ' .
			$unconditional_instantiations . ' unconditional instantiations (should be >= 4), ' .
			'and ' . ( $has_try_catch ? 'has' : 'missing' ) . ' try-catch error handling.'
		);
	}

	/**
	 * Test Property 3: AI_Module boot() method is complete with hook registrations
	 *
	 * EXPECTED BEHAVIOR: AI_Module boot() method should register all required hooks:
	 * - rest_api_init
	 * - admin_enqueue_scripts
	 * - enqueue_block_editor_assets
	 * - save_post
	 * - meowseo_settings_tabs
	 *
	 * CURRENT BEHAVIOR (UNFIXED): boot() method exists but may be incomplete.
	 *
	 * This test will FAIL on unfixed code if:
	 * - boot() method is missing any of the required hook registrations
	 * - Callbacks are not properly bound
	 */
	public function test_ai_module_boot_method_complete() {
		// Read the AI_Module source file
		$source_file = dirname( __DIR__, 2 ) . '/includes/modules/ai/class-ai-module.php';
		$source_content = file_get_contents( $source_file );

		// Check for all required hook registrations in boot() method
		$required_hooks = [
			'rest_api_init',
			'admin_enqueue_scripts',
			'enqueue_block_editor_assets',
			'save_post',
			'meowseo_settings_tabs',
		];

		$missing_hooks = [];
		foreach ( $required_hooks as $hook ) {
			// Check if hook is registered in boot() method
			$pattern = '/add_(action|filter)\s*\(\s*[\'"]' . preg_quote( $hook, '/' ) . '[\'"]/';
			if ( ! preg_match( $pattern, $source_content ) ) {
				$missing_hooks[] = $hook;
			}
		}

		// EXPECTED BEHAVIOR: All required hooks should be registered
		$this->assertEmpty(
			$missing_hooks,
			'EXPECTED BEHAVIOR: AI_Module boot() method should register all required hooks. ' .
			'CURRENT BEHAVIOR: boot() method in class-ai-module.php is missing hook registrations for: ' .
			implode( ', ', $missing_hooks ) . '. ' .
			'Required hooks: rest_api_init, admin_enqueue_scripts, enqueue_block_editor_assets, save_post, meowseo_settings_tabs.'
		);
	}

	/**
	 * Test Property 4: Dashboard component has complete implementation
	 *
	 * EXPECTED BEHAVIOR: src/admin/dashboard.js should have a complete
	 * DashboardApp React component with proper structure, data fetching,
	 * error handling, and loading states.
	 *
	 * CURRENT BEHAVIOR (UNFIXED): dashboard.js has imports but no actual
	 * component implementation.
	 *
	 * This test will FAIL on unfixed code because:
	 * - File has imports but no React component definition
	 * - No DashboardApp component export
	 * - No component implementation
	 */
	public function test_dashboard_component_implementation() {
		// Read the dashboard.js source file
		$source_file = dirname( __DIR__, 2 ) . '/src/admin/dashboard.js';
		
		// Check if file exists
		if ( ! file_exists( $source_file ) ) {
			$this->fail(
				'EXPECTED BEHAVIOR: src/admin/dashboard.js should exist with complete DashboardApp component. ' .
				'CURRENT BEHAVIOR: File does not exist.'
			);
			return;
		}

		$source_content = file_get_contents( $source_file );

		// Check for React component patterns
		$has_component_definition = preg_match(
			'/(function\s+DashboardApp|const\s+DashboardApp\s*=|class\s+DashboardApp)/i',
			$source_content
		);

		$has_component_export = preg_match(
			'/export\s+(default\s+)?DashboardApp/i',
			$source_content
		);

		$has_data_fetching = preg_match(
			'/(fetch|apiFetch|useEffect|useState)/i',
			$source_content
		);

		$has_error_handling = preg_match(
			'/(try|catch|error|Error)/i',
			$source_content
		);

		// EXPECTED BEHAVIOR: Should have complete React component
		$this->assertTrue(
			$has_component_definition && $has_component_export && $has_data_fetching && $has_error_handling,
			'EXPECTED BEHAVIOR: src/admin/dashboard.js should have complete DashboardApp React component ' .
			'with data fetching, error handling, and loading states. ' .
			'CURRENT BEHAVIOR: File has imports but no actual component implementation. ' .
			'Component definition: ' . ( $has_component_definition ? 'found' : 'MISSING' ) . ', ' .
			'Component export: ' . ( $has_component_export ? 'found' : 'MISSING' ) . ', ' .
			'Data fetching: ' . ( $has_data_fetching ? 'found' : 'MISSING' ) . ', ' .
			'Error handling: ' . ( $has_error_handling ? 'found' : 'MISSING' ) . '.'
		);
	}

	/**
	 * Test Property 5: handleGenerate() function is complete with API call
	 *
	 * EXPECTED BEHAVIOR: src/ai/components/AiGeneratorPanel.js handleGenerate()
	 * function should have complete implementation with:
	 * - apiFetch call to /meowseo/v1/ai/generate endpoint
	 * - Proper error handling with user-friendly messages
	 * - Loading state management
	 * - Success/failure feedback UI
	 *
	 * CURRENT BEHAVIOR (UNFIXED): handleGenerate() function is incomplete
	 * at line 70+ with missing API call implementation.
	 *
	 * This test will FAIL on unfixed code because:
	 * - Function exists but API call is not implemented
	 * - Missing error handling
	 * - Missing loading state management
	 */
	public function test_handle_generate_function_complete() {
		// Read the AiGeneratorPanel.js source file
		$source_file = dirname( __DIR__, 2 ) . '/src/ai/components/AiGeneratorPanel.js';
		
		// Check if file exists
		if ( ! file_exists( $source_file ) ) {
			$this->fail(
				'EXPECTED BEHAVIOR: src/ai/components/AiGeneratorPanel.js should exist with complete handleGenerate() function. ' .
				'CURRENT BEHAVIOR: File does not exist.'
			);
			return;
		}

		$source_content = file_get_contents( $source_file );

		// Check for handleGenerate function definition
		$has_handle_generate = preg_match(
			'/(const|function)\s+handleGenerate/i',
			$source_content
		);

		// Check for API call to generate endpoint
		$has_api_call = preg_match(
			'/apiFetch\s*\([^)]*[\'"]\/meowseo\/v1\/ai\/generate[\'"]/s',
			$source_content
		);

		// Check for error handling
		$has_error_handling = preg_match(
			'/(try|catch|\.catch\()/i',
			$source_content
		);

		// Check for loading state management
		$has_loading_state = preg_match(
			'/setIsGenerating|isGenerating/i',
			$source_content
		);

		// Check for success/failure feedback
		$has_feedback = preg_match(
			'/(setError|setSuccess|Notice|alert)/i',
			$source_content
		);

		// EXPECTED BEHAVIOR: Should have complete implementation
		$this->assertTrue(
			$has_handle_generate && $has_api_call && $has_error_handling && $has_loading_state && $has_feedback,
			'EXPECTED BEHAVIOR: handleGenerate() function should have complete implementation with ' .
			'apiFetch call, error handling, loading state management, and success/failure feedback. ' .
			'CURRENT BEHAVIOR: handleGenerate() function at line 70+ (src/ai/components/AiGeneratorPanel.js) is incomplete. ' .
			'Function definition: ' . ( $has_handle_generate ? 'found' : 'MISSING' ) . ', ' .
			'API call to /meowseo/v1/ai/generate: ' . ( $has_api_call ? 'found' : 'MISSING' ) . ', ' .
			'Error handling: ' . ( $has_error_handling ? 'found' : 'MISSING' ) . ', ' .
			'Loading state: ' . ( $has_loading_state ? 'found' : 'MISSING' ) . ', ' .
			'Feedback UI: ' . ( $has_feedback ? 'found' : 'MISSING' ) . '.'
		);
	}

	/**
	 * Test Property 6: AI module can be loaded without fatal errors
	 *
	 * EXPECTED BEHAVIOR: When AI module is enabled and loaded, it should
	 * initialize without fatal errors even if some dependencies are missing.
	 *
	 * CURRENT BEHAVIOR (UNFIXED): Module loading may fail due to registry
	 * mismatch or incomplete constructor.
	 *
	 * This test will FAIL on unfixed code if:
	 * - Module registry mismatch prevents loading
	 * - Constructor throws errors due to incomplete instantiation
	 */
	public function test_ai_module_loads_without_fatal_errors() {
		// Enable AI module
		$this->options->set( 'enabled_modules', [ 'ai' ] );

		// Capture any errors or warnings
		$error_triggered = false;
		$error_message = '';
		
		set_error_handler(
			function ( $errno, $errstr ) use ( &$error_triggered, &$error_message ) {
				$error_triggered = true;
				$error_message = $errstr;
				return true; // Suppress the error
			}
		);

		// Attempt to boot modules
		try {
			$this->module_manager->boot();
			$ai_module = $this->module_manager->get_module( 'ai' );
		} catch ( \Throwable $e ) {
			$error_triggered = true;
			$error_message = $e->getMessage();
			$ai_module = null;
		}
		
		restore_error_handler();

		// EXPECTED BEHAVIOR: Module should load without errors
		$this->assertFalse(
			$error_triggered,
			'EXPECTED BEHAVIOR: AI module should load without fatal errors when enabled. ' .
			'CURRENT BEHAVIOR: Module loading failed with error: ' . $error_message . '. ' .
			'This may be due to module registry mismatch (line 42 class-module-manager.php) ' .
			'or incomplete constructor (line 80+ class-ai-module.php).'
		);

		// EXPECTED BEHAVIOR: Module should be loaded and accessible
		$this->assertNotNull(
			$ai_module,
			'EXPECTED BEHAVIOR: AI module should be loaded and accessible via get_module(\'ai\'). ' .
			'CURRENT BEHAVIOR: Module is null, indicating loading failed. ' .
			'Check module registry class name at line 42 (class-module-manager.php).'
		);
	}
}
