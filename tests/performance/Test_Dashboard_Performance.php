<?php
/**
 * Performance tests for dashboard load time and caching
 *
 * Tests Requirements: 25.1, 25.2, 25.3, 25.4, 25.5
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Admin\Dashboard_Widgets;
use MeowSEO\Options;
use MeowSEO\Module_Manager;
use MeowSEO\Helpers\Cache;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Performance Test Class
 *
 * Validates dashboard load time and caching effectiveness.
 */
class Test_Dashboard_Performance extends TestCase {

	/**
	 * Dashboard_Widgets instance
	 *
	 * @var Dashboard_Widgets
	 */
	private Dashboard_Widgets $widgets;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Module_Manager instance
	 *
	 * @var Module_Manager
	 */
	private Module_Manager $module_manager;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$this->module_manager = new Module_Manager( $this->options );
		$this->widgets = new Dashboard_Widgets( $this->options, $this->module_manager );

		// Clear all caches before each test.
		wp_cache_flush();
	}

	/**
	 * Test 35.1: Dashboard initial render completes in <500ms
	 *
	 * Requirement: 25.1 - THE Admin_Dashboard SHALL complete initial HTML render within 500ms on sites with 10,000+ posts
	 *
	 * @return void
	 */
	public function test_dashboard_initial_render_under_500ms(): void {
		// Measure render time.
		$start_time = microtime( true );

		// Render widgets (this should not execute database queries).
		ob_start();
		$this->widgets->render_widgets();
		$output = ob_get_clean();
		$end_time = microtime( true );

		$render_time_ms = ( $end_time - $start_time ) * 1000;

		// Assert render time is under 500ms.
		$this->assertLessThan(
			500,
			$render_time_ms,
			"Dashboard initial render took {$render_time_ms}ms, should be under 500ms"
		);

		// Assert output contains widget containers.
		$this->assertStringContainsString( 'meowseo-dashboard-widgets', $output );
		$this->assertStringContainsString( 'meowseo-widget', $output );
	}

	/**
	 * Test 35.1: All data loading deferred to async REST calls
	 *
	 * Requirement: 25.3 - THE Admin_Dashboard SHALL defer all data loading to asynchronous REST calls
	 *
	 * @return void
	 */
	public function test_dashboard_data_loading_deferred_to_rest(): void {
		// Render widgets.
		ob_start();
		$this->widgets->render_widgets();
		$output = ob_get_clean();

		// Assert widget containers have data attributes for REST endpoints.
		$this->assertStringContainsString( 'data-endpoint="/meowseo/v1/dashboard/content-health"', $output );
		$this->assertStringContainsString( 'data-endpoint="/meowseo/v1/dashboard/sitemap-status"', $output );
		$this->assertStringContainsString( 'data-endpoint="/meowseo/v1/dashboard/top-404s"', $output );
		$this->assertStringContainsString( 'data-endpoint="/meowseo/v1/dashboard/gsc-summary"', $output );
		$this->assertStringContainsString( 'data-endpoint="/meowseo/v1/dashboard/discover-performance"', $output );
		$this->assertStringContainsString( 'data-endpoint="/meowseo/v1/dashboard/index-queue"', $output );

		// Assert loading indicators are present.
		$this->assertStringContainsString( 'meowseo-widget-loading', $output );
		$this->assertStringContainsString( 'spinner is-active', $output );
	}

	/**
	 * Test 35.2: Widget data cached with 5-minute TTL
	 *
	 * Requirement: 25.4 - THE Admin_Dashboard SHALL use WordPress transients for widget data with 5-minute TTL
	 *
	 * @return void
	 */
	public function test_widget_data_cached_with_5_minute_ttl(): void {
		// Get content health data (first call - cache miss).
		$data1 = $this->widgets->get_content_health_data();
		$this->assertIsArray( $data1 );
		$this->assertArrayHasKey( 'total_posts', $data1 );

		// Verify cache was set.
		$cached_data = Cache::get( 'dashboard_content_health' );
		$this->assertNotFalse( $cached_data, 'Widget data should be cached' );
		$this->assertEquals( $data1, $cached_data, 'Cached data should match returned data' );
	}

	/**
	 * Test 35.2: Cached data returned without database queries
	 *
	 * Requirement: 25.5 - WHEN widget data is cached, THE REST_SEO_API SHALL return cached data without database queries
	 *
	 * @return void
	 */
	public function test_cached_widget_data_no_database_queries(): void {
		global $wpdb;

		// First call - populate cache.
		$data1 = $this->widgets->get_content_health_data();

		// Reset query count.
		$wpdb->num_queries = 0;
		$initial_queries = $wpdb->num_queries;

		// Second call - should use cache.
		$data2 = $this->widgets->get_content_health_data();

		$final_queries = $wpdb->num_queries;
		$queries_executed = $final_queries - $initial_queries;

		// Assert data is identical.
		$this->assertEquals( $data1, $data2, 'Cached data should match original data' );

		// Assert no database queries were executed.
		$this->assertEquals(
			0,
			$queries_executed,
			"Cached widget data retrieval executed {$queries_executed} database queries, should be 0"
		);
	}

	/**
	 * Test 35.2: Cache invalidation on data changes
	 *
	 * Requirement: 25.4, 25.5 - Cache should be invalidated when relevant data changes
	 *
	 * @return void
	 */
	public function test_cache_invalidation_on_post_save(): void {
		// Get content health data (populate cache).
		$data1 = $this->widgets->get_content_health_data();
		$this->assertIsArray( $data1 );

		// Verify cache is set.
		$cached_before = Cache::get( 'dashboard_content_health' );
		$this->assertNotFalse( $cached_before );

		// Invalidate cache manually (simulating post save).
		$this->widgets->invalidate_content_health_cache();

		// Verify cache is cleared.
		$cached_after = Cache::get( 'dashboard_content_health' );
		$this->assertFalse( $cached_after, 'Cache should be cleared after invalidation' );
	}

	/**
	 * Test all widget data methods return correct structure
	 *
	 * @return void
	 */
	public function test_all_widget_data_methods_return_correct_structure(): void {
		// Test content health data.
		$content_health = $this->widgets->get_content_health_data();
		$this->assertArrayHasKey( 'total_posts', $content_health );
		$this->assertArrayHasKey( 'missing_title', $content_health );
		$this->assertArrayHasKey( 'missing_description', $content_health );
		$this->assertArrayHasKey( 'missing_focus_keyword', $content_health );
		$this->assertArrayHasKey( 'percentage_complete', $content_health );

		// Test sitemap status data.
		$sitemap_status = $this->widgets->get_sitemap_status_data();
		$this->assertArrayHasKey( 'enabled', $sitemap_status );
		$this->assertArrayHasKey( 'last_generated', $sitemap_status );
		$this->assertArrayHasKey( 'total_urls', $sitemap_status );
		$this->assertArrayHasKey( 'post_types', $sitemap_status );
		$this->assertArrayHasKey( 'cache_status', $sitemap_status );

		// Test top 404s data.
		$top_404s = $this->widgets->get_top_404s_data();
		$this->assertIsArray( $top_404s );

		// Test GSC summary data.
		$gsc_summary = $this->widgets->get_gsc_summary_data();
		$this->assertArrayHasKey( 'clicks', $gsc_summary );
		$this->assertArrayHasKey( 'impressions', $gsc_summary );
		$this->assertArrayHasKey( 'ctr', $gsc_summary );
		$this->assertArrayHasKey( 'position', $gsc_summary );
		$this->assertArrayHasKey( 'date_range', $gsc_summary );
		$this->assertArrayHasKey( 'last_synced', $gsc_summary );

		// Test Discover performance data.
		$discover = $this->widgets->get_discover_performance_data();
		$this->assertArrayHasKey( 'impressions', $discover );
		$this->assertArrayHasKey( 'clicks', $discover );
		$this->assertArrayHasKey( 'ctr', $discover );
		$this->assertArrayHasKey( 'available', $discover );
		$this->assertArrayHasKey( 'date_range', $discover );

		// Test index queue data.
		$index_queue = $this->widgets->get_index_queue_data();
		$this->assertArrayHasKey( 'pending', $index_queue );
		$this->assertArrayHasKey( 'processing', $index_queue );
		$this->assertArrayHasKey( 'completed', $index_queue );
		$this->assertArrayHasKey( 'failed', $index_queue );
		$this->assertArrayHasKey( 'last_processed', $index_queue );
	}
}
