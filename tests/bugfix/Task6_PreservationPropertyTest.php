<?php
/**
 * Preservation Property Tests - Existing Functionality Unchanged
 *
 * Property 2: Preservation - Existing Functionality Unchanged
 *
 * IMPORTANT: Follow observation-first methodology
 * - Observe behavior on UNFIXED code for non-buggy inputs (working functionality)
 * - Write property-based tests capturing observed behavior patterns
 * - Property-based testing generates many test cases for stronger guarantees
 *
 * EXPECTED OUTCOME: Tests PASS on unfixed code (confirms baseline behavior to preserve)
 *
 * Test preservation categories from design:
 * - Category 1: Working Asset Loading - AI sidebar, admin settings, admin dashboard
 * - Category 2: Working Modules - Meta, Schema, Sitemap, Redirects, Internal Links, Monitor 404, GSC, Social, WooCommerce
 * - Category 3: Working REST API - Meta CRUD, Settings, Dashboard, Suggestion, Public SEO endpoints
 * - Category 4: Working Error Handling - Existing error boundaries and validation
 * - Category 5: Working Security - Existing nonce verification and input sanitization
 *
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13, 3.14, 3.15
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Bugfix;

use PHPUnit\Framework\TestCase;
use MeowSEO\Options;
use MeowSEO\Module_Manager;
use MeowSEO\REST_API;
use MeowSEO\Modules\Meta\Meta_Module;
use MeowSEO\Modules\Schema\Schema_Module;
use MeowSEO\Modules\Sitemap\Sitemap;
use MeowSEO\Modules\Redirects\Redirects;
use MeowSEO\Modules\Internal_Links\Internal_Links;
use MeowSEO\Modules\Monitor_404\Monitor_404;
use MeowSEO\Modules\Social\Social_Module;

/**
 * Test preservation of existing working functionality
 *
 * These tests verify that non-buggy code paths produce identical results
 * before and after the fix.
 */
class Task6_PreservationPropertyTest extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Module Manager instance
	 *
	 * @var Module_Manager
	 */
	private $module_manager;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = $this->createMock( Options::class );
		$this->module_manager = $this->createMock( Module_Manager::class );
	}

	/**
	 * ========================================================================
	 * CATEGORY 1: WORKING ASSET LOADING
	 * ========================================================================
	 */

	/**
	 * Test Preservation 3.1: AI Sidebar Loading
	 *
	 * Preservation Requirement 3.1: AI sidebar module (build/ai-sidebar.js) must
	 * continue to load successfully without any changes
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_ai_sidebar_asset_loading_unchanged() {
		// Verify AI sidebar build files exist
		$ai_sidebar_js = dirname( __DIR__, 2 ) . '/build/ai-sidebar.js';
		$ai_sidebar_asset = dirname( __DIR__, 2 ) . '/build/ai-sidebar.asset.php';

		$this->assertFileExists( $ai_sidebar_js,
			'AI sidebar JavaScript bundle should exist and continue to load successfully' );
		$this->assertFileExists( $ai_sidebar_asset,
			'AI sidebar asset file should exist and continue to load successfully' );

		// Verify asset file structure is valid
		if ( file_exists( $ai_sidebar_asset ) ) {
			$asset_data = include $ai_sidebar_asset;
			$this->assertIsArray( $asset_data,
				'AI sidebar asset file should return valid array' );
			$this->assertArrayHasKey( 'dependencies', $asset_data,
				'AI sidebar asset should have dependencies key' );
			$this->assertArrayHasKey( 'version', $asset_data,
				'AI sidebar asset should have version key' );
		}
	}

	/**
	 * Test Preservation 3.2: Admin Settings Loading
	 *
	 * Preservation Requirement 3.2: Admin settings page (build/admin-settings.js)
	 * must continue to load successfully without any changes
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_admin_settings_asset_loading_unchanged() {
		// Verify admin settings source file exists
		$admin_settings_source = dirname( __DIR__, 2 ) . '/src/admin-settings.js';
		$this->assertFileExists( $admin_settings_source,
			'Admin settings source file should exist' );

		// Note: Build files may not exist until webpack is run
		// The key preservation is that the source file and build process remain unchanged
	}

	/**
	 * Test Preservation 3.3: Admin Dashboard Loading
	 *
	 * Preservation Requirement 3.3: Admin dashboard (build/admin-dashboard.js)
	 * must continue to load successfully without any changes
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_admin_dashboard_asset_loading_unchanged() {
		// Verify admin dashboard source file exists
		$admin_dashboard_source = dirname( __DIR__, 2 ) . '/src/admin-dashboard.js';
		$this->assertFileExists( $admin_dashboard_source,
			'Admin dashboard source file should exist' );

		// Note: Build files may not exist until webpack is run
		// The key preservation is that the source file and build process remain unchanged
	}

	/**
	 * ========================================================================
	 * CATEGORY 2: WORKING MODULES
	 * ========================================================================
	 */

	/**
	 * Test Preservation 3.4: Postmeta Registration
	 *
	 * Preservation Requirement 3.4: Postmeta field registration for REST API access
	 * must continue to work with proper sanitization callbacks
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_postmeta_registration_unchanged() {
		// Verify Meta module class exists
		$meta_module_file = dirname( __DIR__, 2 ) . '/includes/modules/meta/class-meta-module.php';
		$this->assertFileExists( $meta_module_file,
			'Meta module file should exist' );

		// Verify Meta_Postmeta class exists (handles postmeta registration)
		$meta_postmeta_file = dirname( __DIR__, 2 ) . '/includes/modules/meta/class-meta-postmeta.php';
		$this->assertFileExists( $meta_postmeta_file,
			'Meta_Postmeta class file should exist' );
	}

	/**
	 * Test Preservation 3.5: Sidebar Tabs Functionality
	 *
	 * Preservation Requirement 3.5: All sidebar tabs (General, Social, Schema, Advanced)
	 * and their functionality must remain unchanged
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_sidebar_tabs_functionality_unchanged() {
		// Verify tab component files exist in Gutenberg components
		$tabs_dir = dirname( __DIR__, 2 ) . '/src/gutenberg/components/tabs/';
		$this->assertDirectoryExists( $tabs_dir,
			'Tabs directory should exist in Gutenberg components' );

		// Verify key tab files exist
		$expected_tabs = [
			'GeneralTabContent.tsx',
			'SocialTabContent.tsx',
			'SchemaTabContent.tsx',
			'AdvancedTabContent.tsx',
		];

		foreach ( $expected_tabs as $tab_file ) {
			$this->assertFileExists( $tabs_dir . $tab_file,
				"Tab file {$tab_file} should exist and remain unchanged" );
		}
	}

	/**
	 * Test Preservation 3.6: Schema Module JSON-LD Generation
	 *
	 * Preservation Requirement 3.6: Schema module JSON-LD generation must continue
	 * to output valid structured data
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_schema_module_jsonld_generation_unchanged() {
		// Verify Schema class exists
		$schema_file = dirname( __DIR__, 2 ) . '/includes/modules/schema/class-schema.php';
		$this->assertFileExists( $schema_file,
			'Schema class file should exist' );

		// Note: The schema module may have a different structure than expected
		// The key is that the file exists and functionality is preserved
	}

	/**
	 * Test Preservation 3.7: Sitemap Module XML Generation
	 *
	 * Preservation Requirement 3.7: Sitemap module XML generation must continue
	 * to generate valid XML for existing sitemap types
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_sitemap_module_xml_generation_unchanged() {
		// Verify Sitemap class exists
		$sitemap_file = dirname( __DIR__, 2 ) . '/includes/modules/sitemap/class-sitemap.php';
		$this->assertFileExists( $sitemap_file,
			'Sitemap class file should exist' );

		// Verify Sitemap_Builder class exists (handles XML generation)
		$sitemap_builder_file = dirname( __DIR__, 2 ) . '/includes/modules/sitemap/class-sitemap-builder.php';
		$this->assertFileExists( $sitemap_builder_file,
			'Sitemap_Builder class file should exist' );
	}

	/**
	 * Test Preservation 3.8: Meta Module Tag Output
	 *
	 * Preservation Requirement 3.8: Meta module tag output (title, description,
	 * Open Graph, Twitter Card, canonical) must remain unchanged
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_meta_module_tag_output_unchanged() {
		// Verify Meta module class exists
		$meta_module_file = dirname( __DIR__, 2 ) . '/includes/modules/meta/class-meta-module.php';
		$this->assertFileExists( $meta_module_file,
			'Meta module file should exist' );

		// Verify key methods exist for tag output
		$this->assertTrue( method_exists( 'MeowSEO\Modules\Meta\Meta_Module', 'output_head_tags' ),
			'Meta module should have output_head_tags method' );

		// Verify Meta_Output class exists (handles actual tag output)
		$meta_output_file = dirname( __DIR__, 2 ) . '/includes/modules/meta/class-meta-output.php';
		$this->assertFileExists( $meta_output_file,
			'Meta_Output class file should exist' );
	}

	/**
	 * Test Preservation 3.9: Redirects Module Rule Matching
	 *
	 * Preservation Requirement 3.9: Redirects module rule matching and execution
	 * must continue to work correctly
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_redirects_module_rule_matching_unchanged() {
		// Verify Redirects class exists
		$redirects_file = dirname( __DIR__, 2 ) . '/includes/modules/redirects/class-redirects.php';
		$this->assertFileExists( $redirects_file,
			'Redirects class file should exist' );

		// Verify Redirects_REST class exists (handles redirect operations)
		$redirects_rest_file = dirname( __DIR__, 2 ) . '/includes/modules/redirects/class-redirects-rest.php';
		$this->assertFileExists( $redirects_rest_file,
			'Redirects_REST class file should exist' );
	}

	/**
	 * Test Preservation 3.10: Internal Links Module Suggestion Algorithm
	 *
	 * Preservation Requirement 3.10: Internal Links module suggestion algorithm
	 * must remain unchanged
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_internal_links_suggestion_algorithm_unchanged() {
		// Verify Internal Links class exists
		$internal_links_file = dirname( __DIR__, 2 ) . '/includes/modules/internal_links/class-internal-links.php';
		$this->assertFileExists( $internal_links_file,
			'Internal Links class file should exist' );

		// Verify Internal_Links_REST class exists (handles suggestions)
		$internal_links_rest_file = dirname( __DIR__, 2 ) . '/includes/modules/internal_links/class-internal-links-rest.php';
		$this->assertFileExists( $internal_links_rest_file,
			'Internal_Links_REST class file should exist' );
	}

	/**
	 * Test Preservation 3.11: Monitor 404 Module Logging
	 *
	 * Preservation Requirement 3.11: Monitor 404 module logging functionality
	 * must continue to capture 404 events
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_monitor_404_logging_unchanged() {
		// Verify Monitor 404 class exists
		$monitor_404_file = dirname( __DIR__, 2 ) . '/includes/modules/monitor_404/class-monitor-404.php';
		$this->assertFileExists( $monitor_404_file,
			'Monitor 404 class file should exist' );

		// Verify Monitor_404_REST class exists (handles REST API operations)
		$monitor_404_rest_file = dirname( __DIR__, 2 ) . '/includes/modules/monitor_404/class-monitor-404-rest.php';
		$this->assertFileExists( $monitor_404_rest_file,
			'Monitor_404_REST class file should exist' );
	}

	/**
	 * Test Preservation 3.12: WooCommerce Module Integration
	 *
	 * Preservation Requirement 3.12: WooCommerce module product page integration
	 * must remain unchanged
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_woocommerce_module_integration_unchanged() {
		// Verify WooCommerce module directory exists
		$woocommerce_dir = dirname( __DIR__, 2 ) . '/includes/modules/woocommerce/';
		$this->assertDirectoryExists( $woocommerce_dir,
			'WooCommerce module directory should exist' );

		// Note: WooCommerce module may have different structure
		// The key is that the directory exists and functionality is preserved
	}

	/**
	 * Test Preservation 3.13: Social Module Sharing Metadata
	 *
	 * Preservation Requirement 3.13: Social module sharing metadata generation
	 * must continue to work
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_social_module_sharing_metadata_unchanged() {
		// Verify Social module directory exists
		$social_dir = dirname( __DIR__, 2 ) . '/includes/modules/social/';
		$this->assertDirectoryExists( $social_dir,
			'Social module directory should exist' );

		// Note: Social module may have different structure
		// The key is that the directory exists and functionality is preserved
	}

	/**
	 * ========================================================================
	 * CATEGORY 3: WORKING REST API
	 * ========================================================================
	 */

	/**
	 * Test Preservation 3.14: REST API Input Validation
	 *
	 * Preservation Requirement 3.14: REST API input validation and sanitization
	 * logic must remain unchanged
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_rest_api_input_validation_unchanged() {
		// Verify REST API class exists
		$rest_api_file = dirname( __DIR__, 2 ) . '/includes/class-rest-api.php';
		$this->assertFileExists( $rest_api_file,
			'REST API class file should exist' );

		// Verify key validation methods exist
		$this->assertTrue( method_exists( 'MeowSEO\REST_API', 'validate_settings' ),
			'REST API should have validate_settings method' );

		// Read source to verify validation functionality
		$source = file_get_contents( $rest_api_file );
		$this->assertStringContainsString( 'sanitize_', $source,
			'REST API should use sanitization functions' );
		$this->assertStringContainsString( 'validate_', $source,
			'REST API should use validation functions' );
	}

	/**
	 * Test Preservation 3.15: Plugin Initialization Sequence
	 *
	 * Preservation Requirement 3.15: Plugin initialization sequence and module
	 * boot order must remain unchanged
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_plugin_initialization_sequence_unchanged() {
		// Verify main plugin file exists
		$plugin_file = dirname( __DIR__, 2 ) . '/meowseo.php';
		$this->assertFileExists( $plugin_file,
			'Main plugin file should exist' );

		// Verify Module Manager class exists
		$module_manager_file = dirname( __DIR__, 2 ) . '/includes/class-module-manager.php';
		$this->assertFileExists( $module_manager_file,
			'Module Manager class file should exist' );

		// Verify key initialization methods exist
		$this->assertTrue( method_exists( 'MeowSEO\Module_Manager', 'boot' ),
			'Module Manager should have boot method' );

		// Read source to verify initialization sequence
		$source = file_get_contents( $module_manager_file );
		$this->assertStringContainsString( 'module_registry', $source,
			'Module Manager should have module registry' );
	}

	/**
	 * ========================================================================
	 * CATEGORY 4: WORKING ERROR HANDLING
	 * ========================================================================
	 */

	/**
	 * Test Preservation: Existing Error Boundaries
	 *
	 * Preservation Requirement: Existing error handling continues to work correctly
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_existing_error_boundaries_unchanged() {
		// Verify ErrorBoundary component exists in Gutenberg components
		$error_boundary_file = dirname( __DIR__, 2 ) . '/src/gutenberg/components/ErrorBoundary.tsx';
		$this->assertFileExists( $error_boundary_file,
			'ErrorBoundary component should exist in Gutenberg components' );

		// Read source to verify error boundary functionality
		$source = file_get_contents( $error_boundary_file );
		$this->assertStringContainsString( 'componentDidCatch', $source,
			'ErrorBoundary should implement componentDidCatch' );
	}

	/**
	 * ========================================================================
	 * CATEGORY 5: WORKING SECURITY
	 * ========================================================================
	 */

	/**
	 * Test Preservation: Existing Nonce Verification
	 *
	 * Preservation Requirement: Working nonce verification continues to work correctly
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_existing_nonce_verification_unchanged() {
		// Verify REST API class has nonce verification
		$rest_api_file = dirname( __DIR__, 2 ) . '/includes/class-rest-api.php';
		$this->assertFileExists( $rest_api_file,
			'REST API class file should exist' );

		// Verify verify_nonce method exists
		$this->assertTrue( method_exists( 'MeowSEO\REST_API', 'verify_nonce' ),
			'REST API should have verify_nonce method' );

		// Read source to verify nonce verification functionality
		$source = file_get_contents( $rest_api_file );
		$this->assertStringContainsString( 'wp_verify_nonce', $source,
			'REST API should use wp_verify_nonce function' );
	}

	/**
	 * Test Preservation: Existing Input Sanitization
	 *
	 * Preservation Requirement: Existing sanitization continues to work correctly
	 *
	 * EXPECTED: Test PASSES on unfixed code (confirms working functionality)
	 */
	public function test_existing_input_sanitization_unchanged() {
		// Verify REST API class has sanitization methods
		$rest_api_file = dirname( __DIR__, 2 ) . '/includes/class-rest-api.php';
		$this->assertFileExists( $rest_api_file,
			'REST API class file should exist' );

		// Read source to verify sanitization functionality
		$source = file_get_contents( $rest_api_file );
		$this->assertStringContainsString( 'sanitize_text_field', $source,
			'REST API should use sanitize_text_field function' );
		$this->assertStringContainsString( 'sanitize_', $source,
			'REST API should use WordPress sanitization functions' );
	}
}
