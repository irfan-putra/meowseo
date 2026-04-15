<?php
/**
 * Performance Benchmark Integration Tests
 *
 * Tests that the Meta Module meets performance requirements.
 * Requires a real WordPress installation with query monitoring.
 *
 * @package MeowSEO
 * @subpackage Tests\Integration
 */

namespace MeowSEO\Tests\Integration;

/**
 * Performance Benchmark Test Case
 *
 * NOTE: These tests require a real WordPress installation and performance
 * monitoring tools. They should be run in a CI/CD environment.
 */
class PerformanceBenchmarkTest extends \WP_UnitTestCase {
	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Activate MeowSEO plugin.
		activate_plugin( 'meowseo/meowseo.php' );

		// Boot the plugin.
		\MeowSEO\Plugin::instance()->boot();

		// Clear all caches.
		wp_cache_flush();
	}

	/**
	 * Test database queries with cache
	 *
	 * Validates: Performance requirement - 0 queries with cache
	 *
	 * @return void
	 */
	public function test_database_queries_with_cache(): void {
		global $wpdb;

		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// First request - populate cache.
		ob_start();
		do_action( 'wp_head' );
		ob_end_clean();

		// Reset query counter.
		$wpdb->num_queries = 0;

		// Second request - should use cache.
		ob_start();
		do_action( 'wp_head' );
		ob_end_clean();

		// Should have 0 queries (all cached).
		$this->assertSame( 0, $wpdb->num_queries, 'Should have 0 database queries with cache' );
	}

	/**
	 * Test memory usage
	 *
	 * Validates: Performance requirement - < 1MB per request
	 *
	 * @return void
	 */
	public function test_memory_usage(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Measure memory before.
		$memory_before = memory_get_usage();

		// Execute wp_head.
		ob_start();
		do_action( 'wp_head' );
		ob_end_clean();

		// Measure memory after.
		$memory_after = memory_get_usage();

		// Calculate memory used.
		$memory_used = $memory_after - $memory_before;

		// Should use less than 1MB (1048576 bytes).
		$this->assertLessThan( 1048576, $memory_used, 'Should use less than 1MB of memory' );
	}

	/**
	 * Test execution time
	 *
	 * Validates: Performance requirement - < 10ms for output_head_tags
	 *
	 * @return void
	 */
	public function test_execution_time(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Warm up cache.
		ob_start();
		do_action( 'wp_head' );
		ob_end_clean();

		// Measure execution time.
		$start_time = microtime( true );

		ob_start();
		do_action( 'wp_head' );
		ob_end_clean();

		$end_time = microtime( true );

		// Calculate execution time in milliseconds.
		$execution_time = ( $end_time - $start_time ) * 1000;

		// Should execute in less than 10ms.
		$this->assertLessThan( 10, $execution_time, 'Should execute in less than 10ms' );
	}

	/**
	 * Test cache hit rate
	 *
	 * Validates: Performance requirement - > 95% cache hit rate
	 *
	 * @return void
	 */
	public function test_cache_hit_rate(): void {
		// Create test posts.
		$post_ids = array();
		for ( $i = 0; $i < 100; $i++ ) {
			$post_ids[] = $this->factory->post->create(
				array(
					'post_title'   => "Test Post {$i}",
					'post_content' => 'Test content',
					'post_status'  => 'publish',
				)
			);
		}

		// Clear cache.
		wp_cache_flush();

		$cache_hits   = 0;
		$cache_misses = 0;

		// First pass - populate cache.
		foreach ( $post_ids as $post_id ) {
			$this->go_to( get_permalink( $post_id ) );

			ob_start();
			do_action( 'wp_head' );
			ob_end_clean();
		}

		// Second pass - measure cache hits.
		foreach ( $post_ids as $post_id ) {
			$this->go_to( get_permalink( $post_id ) );

			// Check if cached.
			$cache_key = "meowseo_meta_{$post_id}";
			$cached    = wp_cache_get( $cache_key, 'meowseo' );

			if ( false !== $cached ) {
				$cache_hits++;
			} else {
				$cache_misses++;
			}

			ob_start();
			do_action( 'wp_head' );
			ob_end_clean();
		}

		// Calculate cache hit rate.
		$total           = $cache_hits + $cache_misses;
		$cache_hit_rate  = ( $cache_hits / $total ) * 100;

		// Should have > 95% cache hit rate.
		$this->assertGreaterThan( 95, $cache_hit_rate, 'Should have > 95% cache hit rate' );
	}

	/**
	 * Test performance with large content
	 *
	 * Validates: Performance with large posts
	 *
	 * @return void
	 */
	public function test_performance_with_large_content(): void {
		// Create a large post (10,000 words).
		$large_content = str_repeat( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 2000 );

		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Large Test Post',
				'post_content' => $large_content,
				'post_status'  => 'publish',
			)
		);

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Measure execution time.
		$start_time = microtime( true );

		ob_start();
		do_action( 'wp_head' );
		ob_end_clean();

		$end_time = microtime( true );

		// Calculate execution time in milliseconds.
		$execution_time = ( $end_time - $start_time ) * 1000;

		// Should still execute in reasonable time (< 50ms for large content).
		$this->assertLessThan( 50, $execution_time, 'Should execute in less than 50ms even with large content' );
	}

	/**
	 * Test performance with many meta fields
	 *
	 * Validates: Performance with all meta fields set
	 *
	 * @return void
	 */
	public function test_performance_with_many_meta_fields(): void {
		// Create a post with all meta fields set.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Set all meta fields.
		update_post_meta( $post_id, '_meowseo_title', 'Custom Title' );
		update_post_meta( $post_id, '_meowseo_description', 'Custom Description' );
		update_post_meta( $post_id, '_meowseo_robots_noindex', false );
		update_post_meta( $post_id, '_meowseo_robots_nofollow', false );
		update_post_meta( $post_id, '_meowseo_canonical', 'https://example.com/custom' );
		update_post_meta( $post_id, '_meowseo_og_title', 'Custom OG Title' );
		update_post_meta( $post_id, '_meowseo_og_description', 'Custom OG Description' );
		update_post_meta( $post_id, '_meowseo_og_image', 123 );
		update_post_meta( $post_id, '_meowseo_twitter_title', 'Custom Twitter Title' );
		update_post_meta( $post_id, '_meowseo_twitter_description', 'Custom Twitter Description' );
		update_post_meta( $post_id, '_meowseo_twitter_image', 123 );

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Measure execution time.
		$start_time = microtime( true );

		ob_start();
		do_action( 'wp_head' );
		ob_end_clean();

		$end_time = microtime( true );

		// Calculate execution time in milliseconds.
		$execution_time = ( $end_time - $start_time ) * 1000;

		// Should execute in less than 10ms even with all meta fields.
		$this->assertLessThan( 10, $execution_time, 'Should execute in less than 10ms with all meta fields' );
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clear all caches.
		wp_cache_flush();

		parent::tearDown();
	}
}
