<?php
/**
 * News Sitemap Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Sitemap;

use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * News_Sitemap_Generator class
 *
 * Generates Google News compliant XML sitemap with news:news elements.
 */
class News_Sitemap_Generator {
	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Generate news sitemap
	 *
	 * Generates Google News compliant XML sitemap with caching.
	 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8
	 *
	 * @return string|false XML sitemap or false on failure.
	 */
	public function generate() {
		// Check cache first (Requirement 3.8)
		$cached = get_transient( 'meowseo_news_sitemap' );
		if ( false !== $cached ) {
			return $cached;
		}

		// Get news posts from last 2 days (Requirement 3.2)
		$posts = $this->get_news_posts();

		// Return false if no news posts (Requirement 3.2)
		if ( empty( $posts ) ) {
			Logger::info( 'No news posts found for news sitemap' );
			return false;
		}

		// Build news XML (Requirements 3.3, 3.4, 3.5)
		$xml = $this->build_news_xml( $posts );

		// Cache for 5 minutes (Requirement 3.8)
		set_transient( 'meowseo_news_sitemap', $xml, 5 * MINUTE_IN_SECONDS );

		return $xml;
	}

	/**
	 * Get news posts
	 *
	 * Queries posts from last 2 days, excluding Googlebot-News noindex posts.
	 * Requirements: 3.2, 3.6, 3.7
	 *
	 * @return array Array of news posts.
	 */
	public function get_news_posts(): array {
		global $wpdb;

		// Calculate 2 days ago timestamp (Requirement 3.2)
		$two_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-2 days' ) );

		// Query posts from last 2 days, excluding noindex posts (Requirements 3.2, 3.6, 3.7)
		$query = "
			SELECT p.ID, p.post_date_gmt, p.post_title
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm_noindex
				ON p.ID = pm_noindex.post_id
				AND pm_noindex.meta_key = '_meowseo_noindex'
			LEFT JOIN {$wpdb->postmeta} pm_googlebot_news
				ON p.ID = pm_googlebot_news.post_id
				AND pm_googlebot_news.meta_key = '_meowseo_googlebot_news_noindex'
			WHERE p.post_type = 'post'
			AND p.post_status = 'publish'
			AND p.post_date_gmt >= %s
			AND (pm_noindex.meta_value IS NULL OR pm_noindex.meta_value != '1')
			AND (pm_googlebot_news.meta_value IS NULL OR pm_googlebot_news.meta_value != '1')
			ORDER BY p.post_date_gmt DESC
			LIMIT 1000
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare( $query, $two_days_ago )
		);

		// Handle database query failure
		if ( null === $results ) {
			Logger::error(
				'News sitemap database query failed',
				array(
					'cutoff_date' => $two_days_ago,
					'error'       => $wpdb->last_error ? $wpdb->last_error : 'Query returned null',
				)
			);
			return array();
		}

		// Handle empty result sets gracefully
		if ( empty( $results ) ) {
			Logger::info(
				'News sitemap query returned no results',
				array(
					'cutoff_date' => $two_days_ago,
				)
			);
			return array();
		}

		// Batch load postmeta for performance
		$post_ids = wp_list_pluck( $results, 'ID' );
		update_post_meta_cache( $post_ids );

		return $results;
	}

	/**
	 * Build news XML
	 *
	 * Generates Google News compliant XML with news:news elements.
	 * Requirements: 3.3, 3.4, 3.5
	 *
	 * @param array $posts Array of post objects.
	 * @return string XML content.
	 */
	private function build_news_xml( array $posts ): string {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
		$xml .= 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

		// Get publication name and language (Requirement 3.9)
		$publication_name = $this->get_publication_name();
		$publication_language = $this->get_publication_language();

		foreach ( $posts as $post ) {
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( get_permalink( $post->ID ) ) . "</loc>\n";
			$xml .= "\t\t<news:news>\n";
			
			// Publication information (Requirement 3.3)
			$xml .= "\t\t\t<news:publication>\n";
			$xml .= "\t\t\t\t<news:name>" . esc_html( $publication_name ) . "</news:name>\n";
			$xml .= "\t\t\t\t<news:language>" . esc_html( $publication_language ) . "</news:language>\n";
			$xml .= "\t\t\t</news:publication>\n";
			
			// Publication date (Requirement 3.3)
			$xml .= "\t\t\t<news:publication_date>" . $this->format_date( $post->post_date_gmt ) . "</news:publication_date>\n";
			
			// Title (Requirement 3.4)
			$xml .= "\t\t\t<news:title>" . esc_html( get_the_title( $post->ID ) ) . "</news:title>\n";
			
			// Keywords if focus keyword is set (Requirement 3.5)
			$focus_keyword = get_post_meta( $post->ID, '_meowseo_focus_keyword', true );
			if ( ! empty( $focus_keyword ) ) {
				$xml .= "\t\t\t<news:keywords>" . esc_html( $focus_keyword ) . "</news:keywords>\n";
			}
			
			$xml .= "\t\t</news:news>\n";
			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * Get publication name
	 *
	 * Returns configured publication name with fallback to site name.
	 * Requirement: 3.9
	 *
	 * @return string Publication name.
	 */
	public function get_publication_name(): string {
		$publication_name = $this->options->get( 'news_sitemap_publication_name', '' );
		
		// Fallback to site name if not configured (Requirement 3.9)
		if ( empty( $publication_name ) ) {
			$publication_name = get_bloginfo( 'name' );
		}

		return $publication_name;
	}

	/**
	 * Get publication language
	 *
	 * Returns configured publication language with fallback to site language.
	 * Requirement: 3.9
	 *
	 * @return string Publication language (ISO 639-1 code).
	 */
	public function get_publication_language(): string {
		$publication_language = $this->options->get( 'news_sitemap_language', '' );
		
		// Fallback to site language if not configured (Requirement 3.9)
		if ( empty( $publication_language ) ) {
			$site_language = get_bloginfo( 'language' );
			
			// Convert WordPress locale to ISO 639-1 (e.g., 'en-US' to 'en')
			if ( strpos( $site_language, '-' ) !== false ) {
				$parts = explode( '-', $site_language );
				$publication_language = $parts[0];
			} else {
				$publication_language = $site_language;
			}
		}

		return $publication_language;
	}

	/**
	 * Format date in ISO 8601 format
	 *
	 * @param string $date Date string in MySQL format.
	 * @return string Date in ISO 8601 format.
	 */
	private function format_date( string $date ): string {
		return gmdate( 'Y-m-d\TH:i:s\+00:00', strtotime( $date ) );
	}
}
