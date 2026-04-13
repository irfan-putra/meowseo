<?php
/**
 * Internal Links Module
 *
 * Analyzes internal link structure and provides link health reporting.
 * Scans post content for internal links and schedules HTTP status checks via WP-Cron.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\Internal_Links
 */

namespace MeowSEO\Modules\Internal_Links;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\DB;
use MeowSEO\Options;
use DOMDocument;
use DOMXPath;

defined( 'ABSPATH' ) || exit;

/**
 * Internal Links Module class
 *
 * Implements link scanning, analysis, and suggestion system.
 */
class Internal_Links implements Module {

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
	 * Register hooks for link scanning and cron processing.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Hook into save_post to schedule link scanning (Requirement 9.1, 9.2).
		add_action( 'save_post', array( $this, 'schedule_link_scan' ), 10, 2 );

		// Register cron hooks for link scanning and status checks.
		add_action( 'meowseo_scan_links_cron', array( $this, 'scan_post_links' ) );
		add_action( 'meowseo_check_link_status_cron', array( $this, 'check_link_status' ) );

		// Register REST API endpoints (Requirement 9.5).
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$rest = new Internal_Links_REST();
		$rest->register_routes();
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'internal_links';
	}

	/**
	 * Schedule link scan for a post.
	 *
	 * Triggered on save_post. Schedules asynchronous link scanning via WP-Cron.
	 * Does not perform synchronous HTTP requests (Requirement 9.2).
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function schedule_link_scan( int $post_id, \WP_Post $post ): void {
		// Skip autosaves and revisions.
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only scan published posts.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Schedule single cron event for link scanning.
		if ( ! wp_next_scheduled( 'meowseo_scan_links_cron', array( $post_id ) ) ) {
			wp_schedule_single_event( time() + 10, 'meowseo_scan_links_cron', array( $post_id ) );
		}
	}

	/**
	 * Scan post content for internal links.
	 *
	 * Parses HTML with DOMDocument to extract <a href> elements.
	 * Filters to internal URLs only (same host as site_url).
	 * Stores link data in meowseo_link_checks table (Requirement 9.1).
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function scan_post_links( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}

		// Get post content.
		$content = apply_filters( 'the_content', $post->post_content );

		if ( empty( $content ) ) {
			return;
		}

		// Parse HTML with DOMDocument.
		$links = $this->extract_links_from_html( $content );

		if ( empty( $links ) ) {
			return;
		}

		// Filter to internal URLs only (Requirement 9.1).
		$internal_links = $this->filter_internal_links( $links );

		// Store link data in database.
		foreach ( $internal_links as $link ) {
			DB::upsert_link_check(
				array(
					'source_post_id' => $post_id,
					'target_url'     => $link['url'],
					'anchor_text'    => $link['anchor'],
					'http_status'    => null, // Not yet checked.
					'last_checked'   => null,
				)
			);

			// Schedule HTTP status check for this link.
			$this->schedule_link_status_check( $post_id, $link['url'] );
		}
	}

	/**
	 * Extract links from HTML content.
	 *
	 * Uses DOMDocument to parse HTML and extract <a href> elements.
	 *
	 * @param string $html HTML content.
	 * @return array Array of link data with 'url' and 'anchor' keys.
	 */
	private function extract_links_from_html( string $html ): array {
		$links = array();

		// Suppress DOMDocument warnings for malformed HTML.
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );
		$anchors = $xpath->query( '//a[@href]' );

		if ( ! $anchors ) {
			return $links;
		}

		foreach ( $anchors as $anchor ) {
			$href = $anchor->getAttribute( 'href' );
			$text = $anchor->textContent;

			if ( ! empty( $href ) ) {
				$links[] = array(
					'url'    => $href,
					'anchor' => mb_substr( $text, 0, 512 ), // Limit to 512 chars.
				);
			}
		}

		return $links;
	}

	/**
	 * Filter links to internal URLs only.
	 *
	 * Compares link host with site_url() host.
	 * Only returns links with the same host (Requirement 9.1).
	 *
	 * @param array $links Array of link data.
	 * @return array Filtered array of internal links.
	 */
	private function filter_internal_links( array $links ): array {
		$site_host = $this->get_site_host();
		$internal_links = array();

		foreach ( $links as $link ) {
			$url = $link['url'];

			// Skip empty URLs, anchors, and mailto/tel links.
			if ( empty( $url ) || '#' === $url[0] || str_starts_with( $url, 'mailto:' ) || str_starts_with( $url, 'tel:' ) ) {
				continue;
			}

			// Handle relative URLs.
			if ( '/' === $url[0] ) {
				// Relative URL - it's internal.
				$link['url'] = site_url( $url );
				$internal_links[] = $link;
				continue;
			}

			// Parse absolute URL.
			$parsed = wp_parse_url( $url );

			if ( ! isset( $parsed['host'] ) ) {
				continue;
			}

			// Compare host with site host.
			if ( $parsed['host'] === $site_host ) {
				$internal_links[] = $link;
			}
		}

		return $internal_links;
	}

	/**
	 * Get site host from site_url().
	 *
	 * @return string Site host.
	 */
	private function get_site_host(): string {
		$site_url = site_url();
		$parsed = wp_parse_url( $site_url );

		return $parsed['host'] ?? '';
	}

	/**
	 * Schedule HTTP status check for a link.
	 *
	 * Schedules asynchronous HTTP status check via WP-Cron (Requirement 9.2).
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     Target URL.
	 * @return void
	 */
	private function schedule_link_status_check( int $post_id, string $url ): void {
		$url_hash = hash( 'sha256', $url );

		// Schedule single cron event for status check.
		// Use URL hash in event args to prevent duplicate scheduling.
		if ( ! wp_next_scheduled( 'meowseo_check_link_status_cron', array( $post_id, $url_hash ) ) ) {
			wp_schedule_single_event( time() + 60, 'meowseo_check_link_status_cron', array( $post_id, $url_hash ) );
		}
	}

	/**
	 * Check HTTP status of a link.
	 *
	 * Performs HTTP HEAD request to check link status.
	 * Updates http_status in meowseo_link_checks table (Requirement 9.3).
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $url_hash URL hash.
	 * @return void
	 */
	public function check_link_status( int $post_id, string $url_hash ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_link_checks';

		// Get link data from database.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE source_post_id = %d AND target_url_hash = %s LIMIT 1",
				$post_id,
				$url_hash
			),
			ARRAY_A
		);

		if ( ! $link ) {
			return;
		}

		$target_url = $link['target_url'];

		// Perform HTTP HEAD request.
		$response = wp_remote_head(
			$target_url,
			array(
				'timeout'     => 10,
				'redirection' => 5,
				'user-agent'  => 'MeowSEO Link Checker/1.0',
			)
		);

		// Get HTTP status code.
		$http_status = null;

		if ( ! is_wp_error( $response ) ) {
			$http_status = wp_remote_retrieve_response_code( $response );
		}

		// Update link check record (Requirement 9.3).
		DB::upsert_link_check(
			array(
				'source_post_id' => $post_id,
				'target_url'     => $target_url,
				'anchor_text'    => $link['anchor_text'],
				'http_status'    => $http_status,
				'last_checked'   => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Get link suggestions for a post.
	 *
	 * Suggests internal links based on keyword overlap between the current post's
	 * focus keyword and other published posts' titles and meta descriptions (Requirement 9.4).
	 *
	 * @param int $post_id Post ID.
	 * @return array Array of suggested posts with relevance scores.
	 */
	public function get_link_suggestions( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array();
		}

		// Get focus keyword for current post.
		$focus_keyword = get_post_meta( $post_id, 'meowseo_focus_keyword', true );

		if ( empty( $focus_keyword ) ) {
			return array();
		}

		// Query other published posts.
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'post__not_in'   => array( $post_id ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new \WP_Query( $args );
		$suggestions = array();

		if ( ! $query->have_posts() ) {
			return $suggestions;
		}

		foreach ( $query->posts as $suggested_post ) {
			$relevance = $this->calculate_keyword_overlap( $focus_keyword, $suggested_post );

			if ( $relevance > 0 ) {
				$suggestions[] = array(
					'post_id'   => $suggested_post->ID,
					'title'     => $suggested_post->post_title,
					'url'       => get_permalink( $suggested_post->ID ),
					'relevance' => $relevance,
				);
			}
		}

		// Sort by relevance score (highest first).
		usort(
			$suggestions,
			function ( $a, $b ) {
				return $b['relevance'] <=> $a['relevance'];
			}
		);

		return array_slice( $suggestions, 0, 5 ); // Return top 5 suggestions.
	}

	/**
	 * Calculate keyword overlap between focus keyword and post content.
	 *
	 * Checks for keyword presence in title and meta description.
	 *
	 * @param string   $focus_keyword Focus keyword.
	 * @param \WP_Post $post          Post object.
	 * @return int Relevance score (0-100).
	 */
	private function calculate_keyword_overlap( string $focus_keyword, \WP_Post $post ): int {
		$score = 0;
		$keyword_lower = mb_strtolower( $focus_keyword );

		// Check title.
		$title_lower = mb_strtolower( $post->post_title );
		if ( str_contains( $title_lower, $keyword_lower ) ) {
			$score += 50;
		}

		// Check meta description.
		$meta_description = get_post_meta( $post->ID, 'meowseo_description', true );
		if ( ! empty( $meta_description ) ) {
			$description_lower = mb_strtolower( $meta_description );
			if ( str_contains( $description_lower, $keyword_lower ) ) {
				$score += 30;
			}
		}

		// Check excerpt as fallback.
		if ( 0 === $score && ! empty( $post->post_excerpt ) ) {
			$excerpt_lower = mb_strtolower( $post->post_excerpt );
			if ( str_contains( $excerpt_lower, $keyword_lower ) ) {
				$score += 20;
			}
		}

		return $score;
	}
}
