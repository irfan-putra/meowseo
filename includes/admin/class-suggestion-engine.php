<?php
/**
 * Suggestion_Engine class for MeowSEO plugin.
 *
 * Provides internal linking suggestions based on content analysis.
 *
 * @package MeowSEO
 * @subpackage MeowSEO\Admin
 * @since 1.0.0
 */

namespace MeowSEO\Admin;

use MeowSEO\Helpers\Cache;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suggestion_Engine class
 *
 * Analyzes content and suggests relevant internal links.
 * Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7
 *
 * @since 1.0.0
 */
class Suggestion_Engine {

	/**
	 * English stopwords
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $stopwords_en = array();

	/**
	 * Indonesian stopwords
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $stopwords_id = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_stopwords();
	}

	/**
	 * Load stopwords from data files
	 *
	 * Requirements: 16.1, 16.2, 16.3, 16.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_stopwords(): void {
		// Load English stopwords.
		$en_file = \MEOWSEO_PATH . 'includes/data/stopwords-en.php';
		if ( file_exists( $en_file ) ) {
			$this->stopwords_en = include $en_file;
		}

		// Load Indonesian stopwords.
		$id_file = \MEOWSEO_PATH . 'includes/data/stopwords-id.php';
		if ( file_exists( $id_file ) ) {
			$this->stopwords_id = include $id_file;
		}
	}

	/**
	 * Get suggestions for content
	 *
	 * Main entry point for getting internal link suggestions.
	 * Requirements: 14.4, 14.5, 26.4
	 *
	 * @since 1.0.0
	 * @param string $content Post content.
	 * @param int    $post_id Current post ID to exclude.
	 * @return array Array of suggestions with post_id, title, url, score.
	 */
	public function get_suggestions( string $content, int $post_id ): array {
		// Check cache first (Requirement 26.4).
		$cache_key = "suggestions_{$post_id}";
		$cached = Cache::get( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Extract keywords from content.
		$keywords = $this->extract_keywords( $content );

		// Return empty if fewer than 3 keywords (Requirement 14.7).
		if ( count( $keywords ) < 3 ) {
			Cache::set( $cache_key, array(), 600 );
			return array();
		}

		// Query relevant posts.
		$posts = $this->query_relevant_posts( $keywords, $post_id );

		if ( empty( $posts ) ) {
			Cache::set( $cache_key, array(), 600 );
			return array();
		}

		// Score and sort posts.
		$scored = array();
		foreach ( $posts as $post ) {
			$score = $this->score_post( $post, $keywords );
			if ( $score > 0 ) {
				$scored[] = array(
					'post_id' => $post->ID,
					'title'   => $post->post_title,
					'url'     => get_permalink( $post->ID ),
					'score'   => $score,
				);
			}
		}

		// Sort by score descending.
		usort( $scored, function( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );

		// Return top 10 (Requirement 14.4).
		$suggestions = array_slice( $scored, 0, 10 );

		// Cache for 10 minutes (Requirement 26.4).
		Cache::set( $cache_key, $suggestions, 600 );

		return $suggestions;
	}

	/**
	 * Extract keywords from content
	 *
	 * Tokenizes content and removes stopwords.
	 * Requirements: 14.1, 26.3
	 *
	 * @since 1.0.0
	 * @param string $content Content to analyze.
	 * @return array Array of keywords.
	 */
	private function extract_keywords( string $content ): array {
		// Limit to first 2,000 words (Requirement 26.3).
		$words = explode( ' ', wp_strip_all_tags( $content ) );
		$words = array_slice( $words, 0, 2000 );

		// Convert to lowercase and filter.
		$keywords = array();
		foreach ( $words as $word ) {
			$word = strtolower( trim( $word ) );

			// Remove punctuation.
			$word = preg_replace( '/[^\w\-]/u', '', $word );

			// Skip empty or short words.
			if ( strlen( $word ) < 3 ) {
				continue;
			}

			// Filter stopwords (Requirement 16.2, 16.3).
			if ( $this->is_stopword( $word ) ) {
				continue;
			}

			$keywords[] = $word;
		}

		// Return unique keywords.
		return array_unique( $keywords );
	}

	/**
	 * Check if word is a stopword
	 *
	 * Requirements: 16.2, 16.3
	 *
	 * @since 1.0.0
	 * @param string $word Word to check.
	 * @return bool True if stopword.
	 */
	private function is_stopword( string $word ): bool {
		$word_lower = strtolower( $word );

		// Check English stopwords.
		if ( in_array( $word_lower, $this->stopwords_en, true ) ) {
			return true;
		}

		// Check Indonesian stopwords.
		if ( in_array( $word_lower, $this->stopwords_id, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Query relevant posts by keywords
	 *
	 * Requirements: 14.2, 26.2
	 *
	 * Optimized to use efficient database queries with indexes on post_title and post_content.
	 * Batch-loads post metadata to avoid N+1 queries in scoring.
	 *
	 * @since 1.0.0
	 * @param array $keywords Keywords to search for.
	 * @param int   $exclude_post_id Post ID to exclude.
	 * @return array Array of WP_Post objects with preloaded metadata.
	 */
	private function query_relevant_posts( array $keywords, int $exclude_post_id ): array {
		global $wpdb;

		if ( empty( $keywords ) ) {
			return array();
		}

		// Build search query with keywords.
		$search_terms = array_map( function( $keyword ) use ( $wpdb ) {
			return '%' . $wpdb->esc_like( $keyword ) . '%';
		}, $keywords );

		// Build WHERE clause for keyword matching.
		$where_parts = array();
		foreach ( $search_terms as $term ) {
			$where_parts[] = $wpdb->prepare(
				"(p.post_title LIKE %s OR p.post_content LIKE %s)",
				$term,
				$term
			);
		}

		$where_clause = implode( ' OR ', $where_parts );

		// Query posts (Requirement 14.5, 14.6).
		// Uses indexes on post_title and post_content for efficient searching.
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, p.post_content, p.post_excerpt 
				FROM {$wpdb->posts} p 
				WHERE p.post_status = 'publish' 
				AND p.post_type IN ('post', 'page') 
				AND p.ID != %d 
				AND ({$where_clause}) 
				LIMIT 50",
				$exclude_post_id
			)
		);

		if ( empty( $posts ) ) {
			return array();
		}

		// Batch-load post metadata to avoid N+1 queries in scoring.
		$post_ids = wp_list_pluck( $posts, 'ID' );
		$this->preload_post_metadata( $post_ids );

		return $posts;
	}

	/**
	 * Preload post metadata for multiple posts
	 *
	 * Batch-loads metadata to avoid N+1 queries during scoring.
	 * Uses WordPress meta cache to store results.
	 *
	 * @since 1.0.0
	 * @param array $post_ids Array of post IDs.
	 * @return void
	 */
	private function preload_post_metadata( array $post_ids ): void {
		if ( empty( $post_ids ) ) {
			return;
		}

		global $wpdb;

		// Query all meowseo_description metadata for these posts in one query.
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$query = $wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} 
			WHERE post_id IN ({$placeholders}) 
			AND meta_key = 'meowseo_description'",
			$post_ids
		);

		$results = $wpdb->get_results( $query );

		// Cache results in WordPress meta cache.
		foreach ( $results as $row ) {
			wp_cache_set( $row->post_id . '_meowseo_description', $row->meta_value, 'post_meta' );
		}

		// Cache empty values for posts without metadata.
		$found_ids = wp_list_pluck( $results, 'post_id' );
		foreach ( $post_ids as $post_id ) {
			if ( ! in_array( $post_id, $found_ids, true ) ) {
				wp_cache_set( $post_id . '_meowseo_description', '', 'post_meta' );
			}
		}
	}

	/**
	 * Score a post based on keyword matches
	 *
	 * Scoring algorithm: title match +50, content match +10 (max 50 per keyword),
	 * meta description match +30.
	 * Requirements: 14.3
	 *
	 * Optimized to use preloaded metadata instead of calling get_post_meta in loop.
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post to score.
	 * @param array    $keywords Keywords to match.
	 * @return int Score value.
	 */
	private function score_post( \WP_Post $post, array $keywords ): int {
		$score = 0;

		// Get preloaded description from cache (set by preload_post_metadata).
		$description = wp_cache_get( $post->ID . '_meowseo_description', 'post_meta' );
		if ( false === $description ) {
			// Fallback to get_post_meta if not preloaded (shouldn't happen in normal flow).
			$description = get_post_meta( $post->ID, 'meowseo_description', true );
		}

		foreach ( $keywords as $keyword ) {
			// Title match: +50 points per keyword.
			if ( stripos( $post->post_title, $keyword ) !== false ) {
				$score += 50;
			}

			// Content match: +10 points per occurrence (max 50 per keyword).
			$content_matches = substr_count( strtolower( $post->post_content ), strtolower( $keyword ) );
			$content_score = min( $content_matches * 10, 50 );
			$score += $content_score;

			// Meta description match: +30 points per keyword.
			if ( ! empty( $description ) && stripos( $description, $keyword ) !== false ) {
				$score += 30;
			}
		}

		return $score;
	}

	/**
	 * Check rate limit for user
	 *
	 * Enforces 1 request per 2 seconds per user.
	 * Requirements: 15.4, 15.5
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return bool True if within rate limit.
	 */
	public function check_rate_limit( int $user_id ): bool {
		$cache_key = "suggest_ratelimit_{$user_id}";
		$last_request = Cache::get( $cache_key );

		if ( false === $last_request ) {
			// First request or cache expired.
			Cache::set( $cache_key, time(), 2 );
			return true;
		}

		// Check if 2 seconds have passed.
		if ( time() - $last_request >= 2 ) {
			Cache::set( $cache_key, time(), 2 );
			return true;
		}

		return false;
	}
}
