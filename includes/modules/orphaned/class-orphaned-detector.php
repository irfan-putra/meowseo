<?php
/**
 * Orphaned Detector class.
 *
 * Identifies content with no internal links and provides linking suggestions.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Orphaned;

use MeowSEO\Helpers\DB;
use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orphaned Detector class.
 *
 * Scans content for orphaned posts (posts with zero inbound internal links)
 * and provides suggestions for linking opportunities.
 *
 * Validates: Requirements 8.1, 8.2, 8.3, 8.6, 8.7, 8.8
 */
class Orphaned_Detector {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Schedule weekly scan if not already scheduled.
		if ( ! wp_next_scheduled( 'meowseo_scan_orphaned_content' ) ) {
			wp_schedule_event( time(), 'weekly', 'meowseo_scan_orphaned_content' );
		}

		// Hook into the scheduled event.
		add_action( 'meowseo_scan_orphaned_content', array( $this, 'scan_all_content' ) );

		// Hook into post save to update orphaned status.
		add_action( 'save_post', array( $this, 'update_post_orphaned_status' ), 10, 2 );

		// Hook into post delete to clean up orphaned records.
		add_action( 'delete_post', array( $this, 'delete_orphaned_record' ) );
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'orphaned';
	}

	/**
	 * Scan all published content for orphaned posts.
	 *
	 * Requirement 8.1: THE Orphaned_Detector SHALL scan all published posts and pages
	 * Requirement 8.2: THE Orphaned_Detector SHALL query the Internal_Link_Scanner database table
	 * Requirement 8.3: WHEN a post has zero inbound links, THE Orphaned_Detector SHALL mark it as orphaned
	 *
	 * @return array Array with scan results (total_posts, orphaned_count, processed).
	 */
	public function scan_all_content(): array {
		global $wpdb;

		$batch_size = 100;
		$offset = 0;
		$total_orphaned = 0;
		$total_processed = 0;

		// Get all published posts and pages.
		$query = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = %s AND post_type IN (%s, %s) ORDER BY ID ASC",
			'publish',
			'post',
			'page'
		);

		$all_posts = $wpdb->get_col( $query );

		if ( empty( $all_posts ) ) {
			return array(
				'total_posts'   => 0,
				'orphaned_count' => 0,
				'processed'     => 0,
			);
		}

		// Process posts in batches to avoid timeouts.
		$batches = array_chunk( $all_posts, $batch_size );

		foreach ( $batches as $batch ) {
			foreach ( $batch as $post_id ) {
				$inbound_count = $this->get_inbound_link_count( $post_id );

				// Update or insert orphaned record.
				$this->upsert_orphaned_record( $post_id, $inbound_count );

				if ( 0 === $inbound_count ) {
					$total_orphaned++;
				}

				$total_processed++;
			}

			// Allow other processes to run.
			sleep( 1 );
		}

		return array(
			'total_posts'    => count( $all_posts ),
			'orphaned_count' => $total_orphaned,
			'processed'      => $total_processed,
		);
	}

	/**
	 * Get inbound link count for a post.
	 *
	 * Counts how many other posts link to this post.
	 *
	 * @param int $post_id Post ID to check.
	 * @return int Number of inbound links.
	 */
	public function get_inbound_link_count( int $post_id ): int {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post ) {
			return 0;
		}

		// Get the post URL.
		$post_url = get_permalink( $post_id );
		if ( ! $post_url ) {
			return 0;
		}

		// Count links to this post in the link_checks table.
		$link_checks_table = $wpdb->prefix . 'meowseo_link_checks';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$link_checks_table} WHERE target_url = %s",
				$post_url
			)
		);

		return absint( $count );
	}

	/**
	 * Get orphaned posts with optional filters.
	 *
	 * Requirement 8.4: THE Orphaned_Detector SHALL provide an admin page listing orphaned content
	 * Requirement 8.5: THE Orphaned_Detector SHALL allow filtering by post type and date range
	 *
	 * @param array $filters Optional filters (post_type, date_from, date_to, limit, offset).
	 * @return array Array of orphaned posts.
	 */
	public function get_orphaned_posts( array $filters = [] ): array {
		global $wpdb;

		$orphaned_table = $wpdb->prefix . 'meowseo_orphaned_content';

		$defaults = array(
			'post_type' => array( 'post', 'page' ),
			'date_from' => null,
			'date_to'   => null,
			'limit'     => 50,
			'offset'    => 0,
		);

		$filters = \wp_parse_args( $filters, $defaults );

		// Build query.
		$query = "SELECT p.ID, p.post_title, p.post_name, p.post_date, p.post_type, o.inbound_link_count, o.last_scanned
			FROM {$wpdb->posts} p
			INNER JOIN {$orphaned_table} o ON p.ID = o.post_id
			WHERE p.post_status = 'publish'
			AND o.inbound_link_count = 0";

		$params = array();

		// Add post type filter.
		if ( ! empty( $filters['post_type'] ) ) {
			$post_types = (array) $filters['post_type'];
			$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
			$query .= " AND p.post_type IN ({$placeholders})";
			$params = array_merge( $params, $post_types );
		}

		// Add date range filter.
		if ( ! empty( $filters['date_from'] ) ) {
			$query .= " AND p.post_date >= %s";
			$params[] = $filters['date_from'];
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$query .= " AND p.post_date <= %s";
			$params[] = $filters['date_to'];
		}

		// Add ordering and pagination.
		$query .= " ORDER BY p.post_date DESC LIMIT %d OFFSET %d";
		$params[] = absint( $filters['limit'] );
		$params[] = absint( $filters['offset'] );

		// Prepare and execute query.
		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Get count of orphaned posts.
	 *
	 * Requirement 8.9: THE Orphaned_Detector SHALL display a dashboard widget showing orphaned post count
	 *
	 * @return int Count of orphaned posts.
	 */
	public function get_orphaned_count(): int {
		global $wpdb;

		$orphaned_table = $wpdb->prefix . 'meowseo_orphaned_content';

		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$orphaned_table} WHERE inbound_link_count = 0"
		);

		return absint( $count );
	}

	/**
	 * Suggest linking opportunities for an orphaned post.
	 *
	 * Analyzes content similarity and suggests posts that should link to the orphaned content.
	 *
	 * Requirement 8.6: THE Orphaned_Detector SHALL provide a "Fix Orphaned Content" guided workflow
	 * Requirement 8.7: THE Orphaned_Detector SHALL analyze content similarity and suggest 5 posts
	 *
	 * @param int $orphaned_post_id Post ID of orphaned content.
	 * @return array Array of suggested posts (max 5).
	 */
	public function suggest_linking_opportunities( int $orphaned_post_id ): array {
		$orphaned_post = get_post( $orphaned_post_id );

		if ( ! $orphaned_post ) {
			return array();
		}

		// Get focus keyword for the orphaned post.
		$focus_keyword = get_post_meta( $orphaned_post_id, '_meowseo_focus_keyword', true );

		// Get categories and tags.
		$categories = wp_get_post_categories( $orphaned_post_id );
		$tags = wp_get_post_tags( $orphaned_post_id, array( 'fields' => 'ids' ) );

		// Query for similar posts.
		$args = array(
			'post_type'      => $orphaned_post->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'post__not_in'   => array( $orphaned_post_id ),
			'orderby'        => 'relevance',
		);

		// Add keyword search if available.
		if ( ! empty( $focus_keyword ) ) {
			$args['s'] = $focus_keyword;
		}

		// Add category filter if available.
		if ( ! empty( $categories ) ) {
			$args['category__in'] = $categories;
		}

		// Query posts.
		$query = new \WP_Query( $args );
		$posts = $query->posts;

		if ( empty( $posts ) ) {
			return array();
		}

		// Score posts based on similarity.
		$scored_posts = array();

		foreach ( $posts as $post ) {
			$score = $this->calculate_similarity_score( $orphaned_post, $post, $focus_keyword );
			$scored_posts[] = array(
				'post'  => $post,
				'score' => $score,
			);
		}

		// Sort by score descending.
		usort( $scored_posts, function( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );

		// Return top 5 suggestions.
		$suggestions = array_slice( $scored_posts, 0, 5 );

		return array_map( function( $item ) {
			return array(
				'ID'           => $item['post']->ID,
				'post_title'   => $item['post']->post_title,
				'post_name'    => $item['post']->post_name,
				'similarity'   => $item['score'],
				'post_url'     => get_permalink( $item['post']->ID ),
			);
		}, $suggestions );
	}

	/**
	 * Calculate similarity score between two posts.
	 *
	 * Uses keyword overlap and category/tag relationships.
	 *
	 * @param \WP_Post $orphaned_post The orphaned post.
	 * @param \WP_Post $candidate_post The candidate post to link from.
	 * @param string   $focus_keyword Focus keyword of orphaned post.
	 * @return int Similarity score (0-100).
	 */
	private function calculate_similarity_score( \WP_Post $orphaned_post, \WP_Post $candidate_post, string $focus_keyword ): int {
		$score = 0;

		// Check keyword overlap in title and content.
		if ( ! empty( $focus_keyword ) ) {
			$keyword_lower = strtolower( $focus_keyword );

			// Title match (40 points).
			if ( stripos( $candidate_post->post_title, $focus_keyword ) !== false ) {
				$score += 40;
			}

			// Content match (20 points).
			if ( stripos( $candidate_post->post_content, $focus_keyword ) !== false ) {
				$score += 20;
			}
		}

		// Check category overlap (20 points).
		$orphaned_categories = wp_get_post_categories( $orphaned_post->ID );
		$candidate_categories = wp_get_post_categories( $candidate_post->ID );

		$category_overlap = count( array_intersect( $orphaned_categories, $candidate_categories ) );
		if ( $category_overlap > 0 ) {
			$score += 20;
		}

		// Check tag overlap (20 points).
		$orphaned_tags = wp_get_post_tags( $orphaned_post->ID, array( 'fields' => 'ids' ) );
		$candidate_tags = wp_get_post_tags( $candidate_post->ID, array( 'fields' => 'ids' ) );

		$tag_overlap = count( array_intersect( $orphaned_tags, $candidate_tags ) );
		if ( $tag_overlap > 0 ) {
			$score += 20;
		}

		// Ensure score is between 0 and 100.
		return min( 100, max( 0, $score ) );
	}

	/**
	 * Update orphaned status for a post when it's saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function update_post_orphaned_status( int $post_id, \WP_Post $post ): void {
		// Only process published posts and pages.
		if ( 'publish' !== $post->post_status || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		// Get inbound link count.
		$inbound_count = $this->get_inbound_link_count( $post_id );

		// Update orphaned record.
		$this->upsert_orphaned_record( $post_id, $inbound_count );
	}

	/**
	 * Delete orphaned record when post is deleted.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function delete_orphaned_record( int $post_id ): void {
		global $wpdb;

		$orphaned_table = $wpdb->prefix . 'meowseo_orphaned_content';

		$wpdb->delete(
			$orphaned_table,
			array( 'post_id' => $post_id ),
			array( '%d' )
		);
	}

	/**
	 * Insert or update orphaned content record.
	 *
	 * @param int $post_id Post ID.
	 * @param int $inbound_count Inbound link count.
	 * @return void
	 */
	private function upsert_orphaned_record( int $post_id, int $inbound_count ): void {
		global $wpdb;

		$orphaned_table = $wpdb->prefix . 'meowseo_orphaned_content';

		// Check if record exists.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$orphaned_table} WHERE post_id = %d",
				$post_id
			)
		);

		if ( $exists ) {
			// Update existing record.
			$wpdb->update(
				$orphaned_table,
				array(
					'inbound_link_count' => $inbound_count,
					'last_scanned'       => current_time( 'mysql' ),
				),
				array( 'post_id' => $post_id ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new record.
			$wpdb->insert(
				$orphaned_table,
				array(
					'post_id'            => $post_id,
					'inbound_link_count' => $inbound_count,
					'last_scanned'       => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
		}
	}

	/**
	 * Schedule weekly scan.
	 *
	 * Requirement 8.8: THE Orphaned_Detector SHALL schedule a weekly WP-Cron job
	 *
	 * @return void
	 */
	public function schedule_weekly_scan(): void {
		if ( ! wp_next_scheduled( 'meowseo_scan_orphaned_content' ) ) {
			wp_schedule_event( time(), 'weekly', 'meowseo_scan_orphaned_content' );
		}
	}
}
