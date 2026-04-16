<?php
/**
 * Performance tests for caching effectiveness
 *
 * Tests Requirements: 25.4, 25.5, 26.4, 26.5
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Admin\Dashboard_Widgets;
use MeowSEO\Admin\Suggestion_Engine;
use MeowSEO\Options;
use MeowSEO\Module_Manager;
use MeowSEO\Helpers\Cache;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Caching Effectiveness Test Class
 *
 * Validates that caching reduces database queries as expected.
 */
class Test_Caching_Effectiveness extends TestCase {

	/**
	 * Dashboard_Widgets instance
	 *
	 * @var Dashboard_Widgets
	 */
	private Dashboard_Widgets $widgets;

	/**
	 * Suggestion_Engine instance
	 *
	 * @var Suggestion_Engine
	 */
	private Suggestion_Engine $engine;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$options = new Options();
		$module_manager = new Module_Manager( $options );
		$this->widgets = new Dashboard_Widgets( $options, $module_manager );
		$this->engine = new Suggestion_Engine();

		// Clear all caches before each test.
		wp_cache_flush();
	}

	/**
	 * Test 35.2: Widget data caching reduces database queries
	 *
	 * Requirement: 25.4, 25.5 - Widget data should be cached with 5-minute TTL and returned without queries
	 *
	 * @return void
	 */
	public function test_widget_caching_reduces_database_queries(): void {
		global $wpdb;

		// First call - cache miss (will execute queries).
		$wpdb->num_queries = 0;
		$data1 = $this->widgets->get_content_health_data();
		$queries_first_call = $wpdb->num_queries;

		// Second call - cache hit (should not execute queries).
		$wpdb->num_queries = 0;
		$data2 = $this->widgets->get_content_health_data();
		$queries_second_call = $wpdb->num_queries;

		// Assert first call executed queries.
		$this->assertGreaterThan(
			0,
			$queries_first_call,
			'First call should execute database queries'
		);

		// Assert second call did not execute queries.
		$this->assertEquals(
			0,
			$queries_second_call,
			'Second call should not execute database queries (should use cache)'
		);

		// Assert data is identical.
		$this->assertEquals( $data1, $data2 );
	}

	/**
	 * Test 35.2: All widget types use caching
	 *
	 * Requirement: 25.4, 25.5 - All widget data should be cached
	 *
	 * @return void
	 */
	public function test_all_widget_types_use_caching(): void {
		global $wpdb;

		$widget_methods = array(
			'get_content_health_data',
			'get_sitemap_status_data',
			'get_top_404s_data',
			'get_gsc_summary_data',
			'get_discover_performance_data',
			'get_index_queue_data',
		);

		foreach ( $widget_methods as $method ) {
			// Clear cache.
			wp_cache_flush();

			// First call - populate cache.
			$wpdb->num_queries = 0;
			$data1 = $this->widgets->$method();
			$queries_first = $wpdb->num_queries;

			// Second call - should use cache.
			$wpdb->num_queries = 0;
			$data2 = $this->widgets->$method();
			$queries_second = $wpdb->num_queries;

			// Assert cache is being used.
			$this->assertEquals(
				0,
				$queries_second,
				"{$method} should use cache on second call"
			);

			// Assert data is identical.
			$this->assertEquals( $data1, $data2, "{$method} should return identical data from cache" );
		}
	}

	/**
	 * Test 35.2: Suggestion caching reduces database queries
	 *
	 * Requirement: 26.4, 26.5 - Suggestion results should be cached for 10 minutes
	 *
	 * @return void
	 */
	public function test_suggestion_caching_reduces_database_queries(): void {
		global $wpdb;

		$test_post_id = 1;
		$content = 'wordpress plugin seo optimization content ranking search engine keywords metadata';

		// First call - cache miss (will execute queries).
		$wpdb->num_queries = 0;
		$suggestions1 = $this->engine->get_suggestions( $content, $test_post_id );
		$queries_first_call = $wpdb->num_queries;

		// Second call - cache hit (should not execute queries).
		$wpdb->num_queries = 0;
		$suggestions2 = $this->engine->get_suggestions( $content, $test_post_id );
		$queries_second_call = $wpdb->num_queries;

		// Assert first call executed queries.
		$this->assertGreaterThan(
			0,
			$queries_first_call,
			'First call should execute database queries'
		);

		// Assert second call did not execute queries.
		$this->assertEquals(
			0,
			$queries_second_call,
			'Second call should not execute database queries (should use cache)'
		);

		// Assert suggestions are identical.
		$this->assertEquals( $suggestions1, $suggestions2 );
	}

	/**
	 * Test 35.2: Cache invalidation works correctly
	 *
	 * Requirement: 25.4, 25.5 - Cache should be invalidated when data changes
	 *
	 * @return void
	 */
	public function test_cache_invalidation_works_correctly(): void {
		// Get widget data to populate cache.
		$data1 = $this->widgets->get_content_health_data();
		$this->assertNotFalse( Cache::get( 'dashboard_content_health' ) );

		// Invalidate cache.
		$this->widgets->invalidate_content_health_cache();

		// Verify cache is cleared.
		$this->assertFalse( Cache::get( 'dashboard_content_health' ) );

		// Get data again (should repopulate cache).
		$data2 = $this->widgets->get_content_health_data();
		$this->assertNotFalse( Cache::get( 'dashboard_content_health' ) );

		// Data should be identical.
		$this->assertEquals( $data1, $data2 );
	}

	/**
	 * Test 35.2: Multiple widget caches are independent
	 *
	 * Requirement: 25.4, 25.5 - Each widget should have independent cache
	 *
	 * @return void
	 */
	public function test_multiple_widget_caches_are_independent(): void {
		// Populate all widget caches.
		$this->widgets->get_content_health_data();
		$this->widgets->get_sitemap_status_data();
		$this->widgets->get_top_404s_data();

		// Verify all caches are set.
		$this->assertNotFalse( Cache::get( 'dashboard_content_health' ) );
		$this->assertNotFalse( Cache::get( 'dashboard_sitemap_status' ) );
		$this->assertNotFalse( Cache::get( 'dashboard_top_404s' ) );

		// Invalidate one cache.
		$this->widgets->invalidate_content_health_cache();

		// Verify only that cache is cleared.
		$this->assertFalse( Cache::get( 'dashboard_content_health' ) );
		$this->assertNotFalse( Cache::get( 'dashboard_sitemap_status' ) );
		$this->assertNotFalse( Cache::get( 'dashboard_top_404s' ) );
	}
}
