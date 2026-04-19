<?php
/**
 * Cornerstone Content Manager
 *
 * Manages cornerstone content designation and retrieval.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Cornerstone;

use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cornerstone Manager class
 *
 * Handles cornerstone content management.
 * Requirements: 6.2, 6.3, 6.9, 6.10
 *
 * @since 1.0.0
 */
class Cornerstone_Manager {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Postmeta key for cornerstone flag
	 *
	 * @var string
	 */
	private const META_KEY = '_meowseo_is_cornerstone';

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Check if a post is marked as cornerstone content
	 *
	 * Requirement: 6.2
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if cornerstone, false otherwise.
	 */
	public function is_cornerstone( int $post_id ): bool {
		$value = get_post_meta( $post_id, self::META_KEY, true );
		return '1' === $value;
	}

	/**
	 * Set cornerstone status for a post
	 *
	 * Requirement: 6.3
	 *
	 * @param int  $post_id      Post ID.
	 * @param bool $is_cornerstone Whether to mark as cornerstone.
	 * @return bool True on success, false on failure.
	 */
	public function set_cornerstone( int $post_id, bool $is_cornerstone ): bool {
		// Validate post exists
		if ( ! get_post( $post_id ) ) {
			return false;
		}

		if ( $is_cornerstone ) {
			// Set postmeta value to "1"
			return (bool) update_post_meta( $post_id, self::META_KEY, '1' );
		} else {
			// Delete postmeta key when unchecked
			return delete_post_meta( $post_id, self::META_KEY );
		}
	}

	/**
	 * Get all cornerstone posts
	 *
	 * Requirement: 6.10
	 *
	 * @param array $args Optional query arguments.
	 * @return array Array of post objects.
	 */
	public function get_cornerstone_posts( array $args = array() ): array {
		$defaults = array(
			'post_type'      => 'any',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => self::META_KEY,
					'value' => '1',
				),
			),
		);

		$query_args = wp_parse_args( $args, $defaults );
		$query      = new \WP_Query( $query_args );

		return $query->posts;
	}

	/**
	 * Get count of cornerstone posts
	 *
	 * Requirement: 6.10
	 *
	 * @return int Number of cornerstone posts.
	 */
	public function get_cornerstone_count(): int {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
				WHERE meta_key = %s AND meta_value = '1'",
				self::META_KEY
			)
		);

		return (int) $count;
	}

	/**
	 * Apply cornerstone weight to link suggestion score
	 *
	 * Multiplies score by 2 for cornerstone posts.
	 * Requirement: 6.9
	 *
	 * @param float $base_score  Base relevance score.
	 * @param int   $post_id     Post ID.
	 * @return float Weighted score.
	 */
	public function apply_cornerstone_weight( float $base_score, int $post_id ): float {
		$is_cornerstone = $this->is_cornerstone( $post_id );
		return $base_score * ( $is_cornerstone ? 2.0 : 1.0 );
	}

	/**
	 * Get postmeta key
	 *
	 * @return string Postmeta key.
	 */
	public function get_meta_key(): string {
		return self::META_KEY;
	}
}
