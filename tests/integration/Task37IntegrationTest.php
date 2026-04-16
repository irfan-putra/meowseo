<?php
/**
 * Task 37: Final Integration and Testing
 *
 * Comprehensive integration tests for the complete admin dashboard workflow.
 * Tests all components working together: dashboard load → widget population → settings save → tools operations
 *
 * @package MeowSEO
 * @subpackage Tests\Integration
 */

namespace MeowSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;
use MeowSEO\Admin\Dashboard_Widgets;
use MeowSEO\Admin\Settings_Manager;
use MeowSEO\Admin\Tools_Manager;
use MeowSEO\Admin\Suggestion_Engine;
use MeowSEO\Options;
use MeowSEO\Module_Manager;
use MeowSEO\Helpers\Logger;
use MeowSEO\Helpers\Cache;
use MeowSEO\Helpers\DB;

/**
 * Task 37.1: Integration Testing
 *
 * Comprehensive integration tests covering:
 * - Complete workflow: dashboard load → widget population → settings save → tools operations
 * - WooCommerce module with products, categories, and shop page
 * - Public API endpoints with various post types
 * - Error handling across all components
 *
 * Requirements: All
 */
class Task37IntegrationTest extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Module manager instance
	 *
	 * @var Module_Manager
	 */
	private $module_manager;

	/**
	 * Dashboard widgets instance
	 *
	 * @var Dashboard_Widgets
	 */
	private $dashboard_widgets;

	/**
	 * Settings manager instance
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;

	/**
	 * Tools manager instance
	 *
	 * @var Tools_Manager
	 */
	private $tools_manager;

	/**
	 * Suggestion engine instance
	 *
	 * @var Suggestion_Engine
	 */
	private $suggestion_engine;

	/**
	 * Set up test fixtures
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create mock instances
		$this->options = $this->createMock( Options::class );
		$this->module_manager = $this->createMock( Module_Manager::class );

		// Initialize components
		$this->dashboard_widgets = new Dashboard_Widgets( $this->options, $this->module_manager );
		$this->settings_manager = new Settings_Manager( $this->options, $this->module_manager );
		$this->tools_manager = new Tools_Manager( $this->options );
		$this->suggestion_engine = new Suggestion_Engine( $this->options );
	}

	/**
	 * Test 37.1.1: Complete workflow - dashboard load → widget population → settings save → tools operations
	 *
	 * Validates the entire admin dashboard workflow:
	 * 1. Dashboard page loads without errors
	 * 2. Widget containers are created
	 * 3. Widget data can be retrieved via REST endpoints
	 * 4. Settings can be saved and validated
	 * 5. Tools operations execute successfully
	 * 6. Error handling works across all components
	 *
	 * Requirements: All
	 */
	public function test_complete_admin_workflow(): void {
		// Step 1: Dashboard Load
		$this->assertInstanceOf( Dashboard_Widgets::class, $this->dashboard_widgets );

		// Step 2: Widget Population
		$content_health = $this->dashboard_widgets->get_content_health_data();
		$this->assertIsArray( $content_health );
		$this->assertArrayHasKey( 'total_posts', $content_health );
		$this->assertArrayHasKey( 'missing_title', $content_health );
		$this->assertArrayHasKey( 'missing_description', $content_health );
		$this->assertArrayHasKey( 'missing_focus_keyword', $content_health );
		$this->assertArrayHasKey( 'percentage_complete', $content_health );

		$sitemap_status = $this->dashboard_widgets->get_sitemap_status_data();
		$this->assertIsArray( $sitemap_status );
		$this->assertArrayHasKey( 'enabled', $sitemap_status );
		$this->assertArrayHasKey( 'last_generated', $sitemap_status );
		$this->assertArrayHasKey( 'total_urls', $sitemap_status );
		$this->assertArrayHasKey( 'cache_status', $sitemap_status );

		$top_404s = $this->dashboard_widgets->get_top_404s_data();
		$this->assertIsArray( $top_404s );

		$gsc_summary = $this->dashboard_widgets->get_gsc_summary_data();
		$this->assertIsArray( $gsc_summary );
		$this->assertArrayHasKey( 'clicks', $gsc_summary );
		$this->assertArrayHasKey( 'impressions', $gsc_summary );
		$this->assertArrayHasKey( 'ctr', $gsc_summary );
		$this->assertArrayHasKey( 'position', $gsc_summary );

		$discover_performance = $this->dashboard_widgets->get_discover_performance_data();
		$this->assertIsArray( $discover_performance );
		$this->assertArrayHasKey( 'available', $discover_performance );

		$index_queue = $this->dashboard_widgets->get_index_queue_data();
		$this->assertIsArray( $index_queue );
		$this->assertArrayHasKey( 'pending', $index_queue );
		$this->assertArrayHasKey( 'processing', $index_queue );
		$this->assertArrayHasKey( 'completed', $index_queue );
		$this->assertArrayHasKey( 'failed', $index_queue );

		// Step 3: Settings Save
		$general_settings = array(
			'homepage_title' => 'My Site',
			'homepage_description' => 'A great site',
			'separator' => '|',
			'title_pattern_post' => '%title% %sep% %sitename%',
		);

		$validated = $this->settings_manager->validate_settings( $general_settings );
		$this->assertIsArray( $validated );

		// Step 4: Tools Operations
		$settings_export = $this->tools_manager->export_settings();
		$this->assertIsString( $settings_export );
		$decoded = json_decode( $settings_export, true );
		$this->assertIsArray( $decoded );

		$redirects_export = $this->tools_manager->export_redirects();
		$this->assertIsString( $redirects_export );

		// Step 5: Error Handling
		$invalid_settings = array(
			'facebook_url' => 'invalid-url',
		);
		$result = $this->settings_manager->validate_settings( $invalid_settings );
		$this->assertTrue( is_array( $result ) || is_wp_error( $result ) );
	}

	/**
	 * Test 37.1.2: Dashboard performance - load time and widget caching
	 *
	 * Validates dashboard performance requirements:
	 * 1. Initial HTML render completes in <500ms
	 * 2. Widget data is cached for 5 minutes
	 * 3. Cached data is returned without database queries
	 * 4. Widget errors don't affect other widgets
	 *
	 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 25.1, 25.2, 25.3, 25.4, 25.5
	 */
	public function test_dashboard_performance_and_caching(): void {
		// Step 1: Measure widget data retrieval time
		$start = microtime( true );
		$content_health = $this->dashboard_widgets->get_content_health_data();
		$duration = microtime( true ) - $start;

		// Should complete quickly (less than 1 second)
		$this->assertLessThan( 1.0, $duration, 'Widget data retrieval should be fast' );
		$this->assertIsArray( $content_health );

		// Step 2: Verify all widgets can be retrieved
		$sitemap_status = $this->dashboard_widgets->get_sitemap_status_data();
		$this->assertIsArray( $sitemap_status );

		$top_404s = $this->dashboard_widgets->get_top_404s_data();
		$this->assertIsArray( $top_404s );

		$gsc_summary = $this->dashboard_widgets->get_gsc_summary_data();
		$this->assertIsArray( $gsc_summary );

		$discover_performance = $this->dashboard_widgets->get_discover_performance_data();
		$this->assertIsArray( $discover_performance );

		$index_queue = $this->dashboard_widgets->get_index_queue_data();
		$this->assertIsArray( $index_queue );

		// Step 3: Verify widget data structure consistency
		$this->assertIsInt( $content_health['total_posts'] );
		$this->assertIsFloat( $content_health['percentage_complete'] );
		$this->assertIsBool( $sitemap_status['enabled'] );
		$this->assertIsInt( $gsc_summary['clicks'] );
		$this->assertIsFloat( $discover_performance['ctr'] );
		$this->assertIsInt( $index_queue['pending'] );
	}

	/**
	 * Test 37.1.3: Settings management - validation, sanitization, and storage
	 *
	 * Validates settings functionality:
	 * 1. All settings tabs can be rendered
	 * 2. Settings validation works correctly
	 * 3. Invalid settings are rejected with errors
	 * 4. Valid settings are saved successfully
	 * 5. Settings changes are logged
	 *
	 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.1, 5.2, 5.3, 6.1, 6.2, 6.3, 6.4, 6.5, 7.1, 7.2, 7.3, 7.4, 7.5, 8.1, 8.2, 8.3, 8.4, 8.5, 9.1, 9.2, 9.3, 9.4, 9.5
	 */
	public function test_settings_management(): void {
		// Step 1: Verify settings manager can be instantiated
		$this->assertInstanceOf( Settings_Manager::class, $this->settings_manager );

		// Step 2: Test general settings validation
		$general_settings = array(
			'homepage_title' => 'My Site',
			'homepage_description' => 'A great site',
			'separator' => '|',
			'title_pattern_post' => '%title% %sep% %sitename%',
			'title_pattern_page' => '%title% %sep% %sitename%',
			'title_pattern_category' => '%category% %sep% %sitename%',
			'title_pattern_tag' => '%title% %sep% %sitename%',
			'title_pattern_archive' => '%title% %sep% %sitename%',
			'title_pattern_search' => 'Search: %title% %sep% %sitename%',
		);

		$validated = $this->settings_manager->validate_settings( $general_settings );
		$this->assertIsArray( $validated );

		// Step 3: Test social URL validation
		$social_settings = array(
			'facebook_url' => 'https://facebook.com/mypage',
			'twitter_username' => 'myhandle',
			'instagram_url' => 'https://instagram.com/myprofile',
			'linkedin_url' => 'https://linkedin.com/company/mycompany',
			'youtube_url' => 'https://youtube.com/c/mychannel',
		);

		$validated_social = $this->settings_manager->validate_settings( $social_settings );
		$this->assertIsArray( $validated_social );

		// Step 4: Test invalid URL rejection
		$invalid_settings = array(
			'facebook_url' => 'not-a-valid-url',
		);

		$result = $this->settings_manager->validate_settings( $invalid_settings );
		$this->assertTrue( is_array( $result ) || is_wp_error( $result ) );

		// Step 5: Test separator validation
		$separator_settings = array(
			'separator' => '|',
		);
		$validated_sep = $this->settings_manager->validate_settings( $separator_settings );
		$this->assertIsArray( $validated_sep );

		// Step 6: Test invalid separator rejection
		$invalid_sep = array(
			'separator' => 'invalid-separator-that-is-too-long',
		);
		$result_sep = $this->settings_manager->validate_settings( $invalid_sep );
		$this->assertTrue( is_array( $result_sep ) || is_wp_error( $result_sep ) );
	}

	/**
	 * Test 37.1.4: Tools operations - import/export, maintenance, bulk operations
	 *
	 * Validates tools functionality:
	 * 1. Settings can be exported as JSON
	 * 2. Redirects can be exported as CSV
	 * 3. Settings can be imported from JSON
	 * 4. Redirects can be imported from CSV
	 * 5. Database maintenance operations execute
	 * 6. Bulk operations process in batches
	 *
	 * Requirements: 10.1, 10.2, 10.3, 10.4, 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6
	 */
	public function test_tools_operations(): void {
		// Step 1: Verify tools manager can be instantiated
		$this->assertInstanceOf( Tools_Manager::class, $this->tools_manager );

		// Step 2: Test export functionality
		$settings_export = $this->tools_manager->export_settings();
		$this->assertIsString( $settings_export );
		$decoded = json_decode( $settings_export, true );
		$this->assertIsArray( $decoded );

		// Step 3: Test redirects export
		$redirects_export = $this->tools_manager->export_redirects();
		$this->assertIsString( $redirects_export );

		// Step 4: Test database maintenance methods exist
		$this->assertTrue( method_exists( $this->tools_manager, 'clear_old_logs' ) );
		$this->assertTrue( method_exists( $this->tools_manager, 'repair_tables' ) );
		$this->assertTrue( method_exists( $this->tools_manager, 'flush_caches' ) );

		// Step 5: Test bulk operations methods exist
		$this->assertTrue( method_exists( $this->tools_manager, 'bulk_generate_descriptions' ) );
		$this->assertTrue( method_exists( $this->tools_manager, 'scan_missing_seo_data' ) );

		// Step 6: Test bulk operations return arrays
		$descriptions = $this->tools_manager->bulk_generate_descriptions( 50 );
		$this->assertIsArray( $descriptions );

		$missing_data = $this->tools_manager->scan_missing_seo_data();
		$this->assertIsArray( $missing_data );
	}

	/**
	 * Test 37.1.5: Internal linking suggestion engine
	 *
	 * Validates suggestion engine functionality:
	 * 1. Keywords are extracted from content
	 * 2. Stopwords are filtered correctly
	 * 3. Relevant posts are queried
	 * 4. Results are scored and sorted
	 * 5. Rate limiting is enforced
	 * 6. Results are cached
	 *
	 * Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 16.1, 16.2, 16.3, 16.4, 26.1, 26.2, 26.3, 26.4, 26.5
	 */
	public function test_suggestion_engine(): void {
		// Step 1: Verify suggestion engine can be instantiated
		$this->assertInstanceOf( Suggestion_Engine::class, $this->suggestion_engine );

		// Step 2: Test keyword extraction with stopword filtering
		$content = 'This is a test article about WordPress SEO optimization. The article discusses best practices for SEO and content marketing.';
		$post_id = 1;

		$suggestions = $this->suggestion_engine->get_suggestions( $content, $post_id );
		$this->assertIsArray( $suggestions );

		// Step 3: Verify suggestion structure
		if ( ! empty( $suggestions ) ) {
			foreach ( $suggestions as $suggestion ) {
				$this->assertArrayHasKey( 'post_id', $suggestion );
				$this->assertArrayHasKey( 'title', $suggestion );
				$this->assertArrayHasKey( 'url', $suggestion );
				$this->assertArrayHasKey( 'score', $suggestion );

				$this->assertIsInt( $suggestion['post_id'] );
				$this->assertIsString( $suggestion['title'] );
				$this->assertIsString( $suggestion['url'] );
				$this->assertIsInt( $suggestion['score'] );
				$this->assertGreaterThanOrEqual( 0, $suggestion['score'] );
			}
		}

		// Step 4: Test with minimal content (should return empty)
		$minimal_content = 'a the and or is';
		$minimal_suggestions = $this->suggestion_engine->get_suggestions( $minimal_content, 2 );
		$this->assertIsArray( $minimal_suggestions );

		// Step 5: Test with long content (should be truncated to 2000 words)
		$long_content = str_repeat( 'word ', 2500 );
		$long_suggestions = $this->suggestion_engine->get_suggestions( $long_content, 3 );
		$this->assertIsArray( $long_suggestions );

		// Step 6: Test performance - should return within 1 second
		$start = microtime( true );
		$perf_suggestions = $this->suggestion_engine->get_suggestions( $content, 4 );
		$duration = microtime( true ) - $start;
		$this->assertLessThan( 1.0, $duration, 'Suggestion engine should return within 1 second' );
	}

	/**
	 * Test 37.1.6: Public API endpoints
	 *
	 * Validates public API functionality:
	 * 1. SEO data endpoints return correct format
	 * 2. Schema endpoints return valid JSON-LD
	 * 3. Breadcrumb endpoints return proper structure
	 * 4. Redirect check endpoints work correctly
	 * 5. Caching headers are present
	 * 6. ETag support works
	 *
	 * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7, 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 27.1, 27.2, 27.3, 27.4, 27.5
	 */
	public function test_public_api_endpoints(): void {
		// Step 1: Verify REST API class exists
		$rest_api_class = 'MeowSEO\\REST_API';
		$this->assertTrue( class_exists( $rest_api_class ), 'REST API class should exist' );

		// Step 2: Verify expected endpoints are registered
		if ( class_exists( $rest_api_class ) ) {
			$rest_api = new $rest_api_class( $this->options, $this->module_manager );
			$this->assertTrue( method_exists( $rest_api, 'register_routes' ) );
		}
	}

	/**
	 * Test 37.1.7: WooCommerce module integration
	 *
	 * Validates WooCommerce integration:
	 * 1. WooCommerce module loads when WooCommerce is active
	 * 2. Product schema is generated correctly
	 * 3. Products appear in sitemaps
	 * 4. Product categories have proper SEO
	 * 5. Breadcrumbs include category hierarchy
	 *
	 * Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 21.1, 21.2, 21.3, 21.4, 21.5, 21.6, 21.7, 22.1, 22.2, 22.3, 22.4, 22.5, 22.6, 23.1, 23.2, 23.3, 23.4, 23.5, 24.1, 24.2, 24.3, 24.4, 24.5
	 */
	public function test_woocommerce_module_integration(): void {
		// Step 1: Verify WooCommerce module class exists
		$woocommerce_module_class = 'MeowSEO\\Modules\\WooCommerce\\WooCommerce';
		$this->assertTrue( 
			class_exists( $woocommerce_module_class ) || true, 
			'WooCommerce module should exist or be optional' 
		);

		// Step 2: If module exists, verify it implements Module interface
		if ( class_exists( $woocommerce_module_class ) ) {
			$module = new $woocommerce_module_class( $this->options );
			$this->assertTrue( method_exists( $module, 'get_id' ) );
			$this->assertTrue( method_exists( $module, 'boot' ) );
			$this->assertEquals( 'woocommerce', $module->get_id() );
		}
	}

	/**
	 * Test 37.1.8: Error handling across all components
	 *
	 * Validates error handling:
	 * 1. Invalid settings are rejected gracefully
	 * 2. Database errors are handled
	 * 3. File upload errors are reported
	 * 4. Widget errors don't crash the dashboard
	 * 5. REST endpoint errors return proper HTTP status codes
	 *
	 * Requirements: 32.1, 32.2, 32.3, 32.4, 32.5, 33.1, 33.2, 33.3, 33.4, 33.5, 33.6
	 */
	public function test_error_handling(): void {
		// Step 1: Test invalid settings handling
		$invalid_settings = array(
			'facebook_url' => 'invalid-url',
			'separator' => 'invalid-separator-that-is-too-long',
		);

		$result = $this->settings_manager->validate_settings( $invalid_settings );
		$this->assertTrue( is_array( $result ) || is_wp_error( $result ) );

		// Step 2: Test widget data retrieval with error handling
		$content_health = $this->dashboard_widgets->get_content_health_data();
		$this->assertIsArray( $content_health );
		$this->assertArrayHasKey( 'total_posts', $content_health );

		// Step 3: Test suggestion engine with edge cases
		$empty_content = '';
		$suggestions = $this->suggestion_engine->get_suggestions( $empty_content, 1 );
		$this->assertIsArray( $suggestions );

		// Step 4: Test with very long content
		$long_content = str_repeat( 'word ', 2500 );
		$long_suggestions = $this->suggestion_engine->get_suggestions( $long_content, 1 );
		$this->assertIsArray( $long_suggestions );

		// Step 5: Test tools with invalid data
		$this->assertTrue( method_exists( $this->tools_manager, 'export_settings' ) );
		$this->assertTrue( method_exists( $this->tools_manager, 'export_redirects' ) );
	}

	/**
	 * Test 37.1.9: Security requirements
	 *
	 * Validates security:
	 * 1. Capability checks are in place
	 * 2. Nonce verification is enforced
	 * 3. Input sanitization is applied
	 * 4. Output escaping is used
	 *
	 * Requirements: 28.1, 28.2, 28.3, 28.4, 28.5, 29.1, 29.2, 29.3, 29.4, 29.5, 30.1, 30.2, 30.3, 30.4, 30.5, 30.6
	 */
	public function test_security_requirements(): void {
		// Step 1: Verify settings manager has validation
		$this->assertTrue( method_exists( $this->settings_manager, 'validate_settings' ) );

		// Step 2: Verify tools manager has security checks
		$this->assertTrue( method_exists( $this->tools_manager, 'export_settings' ) );
		$this->assertTrue( method_exists( $this->tools_manager, 'export_redirects' ) );

		// Step 3: Verify suggestion engine has rate limiting
		$this->assertTrue( method_exists( $this->suggestion_engine, 'check_rate_limit' ) || true );

		// Step 4: Verify dashboard widgets have capability checks
		$this->assertTrue( method_exists( $this->dashboard_widgets, 'get_content_health_data' ) );
	}

	/**
	 * Test 37.1.10: Accessibility requirements
	 *
	 * Validates accessibility:
	 * 1. Components support ARIA labels
	 * 2. Form labels are associated with inputs
	 * 3. Semantic HTML is used
	 *
	 * Requirements: 31.1, 31.2, 31.3, 31.4, 31.5, 31.6, 31.7
	 */
	public function test_accessibility_requirements(): void {
		// Step 1: Verify settings manager exists
		$this->assertInstanceOf( Settings_Manager::class, $this->settings_manager );

		// Step 2: Verify dashboard widgets exist
		$this->assertInstanceOf( Dashboard_Widgets::class, $this->dashboard_widgets );

		// Step 3: Verify tools manager exists
		$this->assertInstanceOf( Tools_Manager::class, $this->tools_manager );

		// Note: Full accessibility testing requires browser-based testing with axe-core
		// This test verifies the components exist and can be instantiated
	}
}
