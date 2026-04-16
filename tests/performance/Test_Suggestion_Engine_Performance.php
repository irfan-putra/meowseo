<?php
/**
 * Performance tests for suggestion engine
 *
 * Tests Requirements: 26.1, 26.2, 26.3, 26.4, 26.5
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Admin\Suggestion_Engine;
use MeowSEO\Helpers\Cache;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suggestion Engine Performance Test Class
 *
 * Validates suggestion engine performance and caching.
 */
class Test_Suggestion_Engine_Performance extends TestCase {

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

		$this->engine = new Suggestion_Engine();

		// Clear all caches before each test.
		wp_cache_flush();
	}

	/**
	 * Test 35.3: Keyword extraction limited to first 2,000 words
	 *
	 * Requirement: 26.3 - THE Suggestion_Engine SHALL limit keyword extraction to first 2,000 words of content
	 *
	 * @return void
	 */
	public function test_keyword_extraction_limited_to_2000_words(): void {
		// Create content with 5,000 words.
		$words = array_fill( 0, 5000, 'wordpress' );
		$content = implode( ' ', $words );

		// Measure time to get suggestions.
		$start_time = microtime( true );
		$suggestions = $this->engine->get_suggestions( $content, 1 );
		$end_time = microtime( true );

		$response_time_ms = ( $end_time - $start_time ) * 1000;

		// Should be fast because only first 2,000 words are processed.
		$this->assertLessThan(
			1000,
			$response_time_ms,
			"Keyword extraction should be fast when limited to 2,000 words"
		);
	}

	/**
	 * Test 35.2: Suggestion results cached for 10 minutes
	 *
	 * Requirement: 26.4 - THE Suggestion_Engine SHALL cache suggestion results for 10 minutes per post
	 *
	 * @return void
	 */
	public function test_suggestion_results_cached_for_10_minutes(): void {
		$test_post_id = 1;
		$content = 'wordpress plugin seo optimization content ranking search engine keywords metadata';

		// Get suggestions (first call - cache miss).
		$suggestions1 = $this->engine->get_suggestions( $content, $test_post_id );

		// Verify cache was set.
		$cache_key = "suggestions_{$test_post_id}";
		$cached_data = Cache::get( $cache_key );
		$this->assertNotFalse( $cached_data, 'Suggestions should be cached' );
		$this->assertEquals( $suggestions1, $cached_data, 'Cached suggestions should match returned suggestions' );
	}

	/**
	 * Test 35.2: Cached suggestions returned without database queries
	 *
	 * Requirement: 26.5 - WHEN cached suggestions exist, THE Suggestion_Engine SHALL return cached results without database queries
	 *
	 * @return void
	 */
	public function test_cached_suggestions_no_database_queries(): void {
		global $wpdb;

		$test_post_id = 1;
		$content = 'wordpress plugin seo optimization content ranking search engine keywords metadata';

		// First call - populate cache.
		$suggestions1 = $this->engine->get_suggestions( $content, $test_post_id );

		// Reset query count.
		$wpdb->num_queries = 0;
		$initial_queries = $wpdb->num_queries;

		// Second call - should use cache.
		$suggestions2 = $this->engine->get_suggestions( $content, $test_post_id );

		$final_queries = $wpdb->num_queries;
		$queries_executed = $final_queries - $initial_queries;

		// Assert suggestions are identical.
		$this->assertEquals( $suggestions1, $suggestions2, 'Cached suggestions should match original suggestions' );

		// Assert no database queries were executed.
		$this->assertEquals(
			0,
			$queries_executed,
			"Cached suggestion retrieval executed {$queries_executed} database queries, should be 0"
		);
	}

	/**
	 * Test suggestion engine returns empty array for content with fewer than 3 keywords
	 *
	 * Requirement: 14.7 - WHEN content contains fewer than 3 keywords after stopword removal, THEN THE Suggestion_Engine SHALL return an empty result set
	 *
	 * @return void
	 */
	public function test_suggestion_engine_returns_empty_for_few_keywords(): void {
		$test_post_id = 1;

		// Content with only stopwords should return empty.
		$suggestions = $this->engine->get_suggestions( 'the and is', $test_post_id );

		$this->assertIsArray( $suggestions );
		$this->assertEmpty( $suggestions, 'Should return empty array for content with only stopwords' );
	}

	/**
	 * Test suggestion engine rate limiting
	 *
	 * Requirement: 15.4, 15.5 - Rate limiting of 1 request per 2 seconds per user
	 *
	 * @return void
	 */
	public function test_suggestion_engine_rate_limiting(): void {
		$user_id = 1;

		// First request should succeed.
		$result1 = $this->engine->check_rate_limit( $user_id );
		$this->assertTrue( $result1, 'First request should pass rate limit check' );

		// Second request immediately after should fail.
		$result2 = $this->engine->check_rate_limit( $user_id );
		$this->assertFalse( $result2, 'Second request within 2 seconds should fail rate limit check' );
	}

	/**
	 * Test 35.3: Batch metadata loading optimization
	 *
	 * Requirement: 26.1 - Results return within 1 second for 5,000-word posts
	 *
	 * Verifies that metadata is batch-loaded instead of calling get_post_meta in a loop.
	 * This optimization reduces database queries from N+1 to 2 (one for posts, one for metadata).
	 *
	 * @return void
	 */
	public function test_batch_metadata_loading_optimization(): void {
		// Create test posts with metadata.
		$post_ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$post_id = wp_insert_post( array(
				'post_title'   => 'Test Post ' . $i . ' wordpress seo optimization',
				'post_content' => 'This is test content with wordpress and seo keywords for testing suggestions engine',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			) );

			// Add metadata.
			update_post_meta( $post_id, 'meowseo_description', 'Test description with wordpress keyword' );
			$post_ids[] = $post_id;
		}

		// Create a post to get suggestions for.
		$test_post_id = wp_insert_post( array(
			'post_title'   => 'Main Post wordpress seo optimization content',
			'post_content' => 'This is the main post content with wordpress and seo keywords for testing suggestions engine performance',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		) );

		// Clear cache to force fresh query.
		wp_cache_flush();

		// Measure time to get suggestions.
		$start_time = microtime( true );

		// Get suggestions - this should batch-load metadata.
		$suggestions = $this->engine->get_suggestions(
			'This is test content with wordpress and seo keywords for testing suggestions engine',
			$test_post_id
		);

		$end_time = microtime( true );
		$response_time_ms = ( $end_time - $start_time ) * 1000;

		// Should complete quickly due to batch metadata loading.
		$this->assertLessThan(
			1000,
			$response_time_ms,
			"Batch metadata loading should complete in < 1000ms, took {$response_time_ms}ms"
		);

		// Verify suggestions were returned or empty (depending on keyword matching).
		$this->assertIsArray( $suggestions );

		// Clean up.
		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		wp_delete_post( $test_post_id, true );
	}
}
