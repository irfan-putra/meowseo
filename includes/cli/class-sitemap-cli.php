<?php
/**
 * Sitemap WP-CLI Commands
 *
 * Provides WP-CLI commands for sitemap generation, cache management, and search engine pinging.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\CLI;

use MeowSEO\Modules\Sitemap\Sitemap_Builder;
use MeowSEO\Modules\Sitemap\Sitemap_Cache;
use MeowSEO\Modules\Sitemap\Sitemap_Ping;
use MeowSEO\Options;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemap CLI commands class
 *
 * Implements WP-CLI commands for sitemap operations.
 *
 * @since 1.0.0
 */
class Sitemap_CLI {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Sitemap Builder instance
	 *
	 * @var Sitemap_Builder
	 */
	private Sitemap_Builder $builder;

	/**
	 * Sitemap Cache instance
	 *
	 * @var Sitemap_Cache
	 */
	private Sitemap_Cache $cache;

	/**
	 * Sitemap Ping instance
	 *
	 * @var Sitemap_Ping
	 */
	private Sitemap_Ping $ping;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->cache = new Sitemap_Cache();
		$this->builder = new Sitemap_Builder( $this->cache, $options );
		$this->ping = new Sitemap_Ping();
	}

	/**
	 * Generate sitemaps
	 *
	 * ## OPTIONS
	 *
	 * [<type>]
	 * : Optional sitemap type to generate (post, page, news, video, or custom post type).
	 *   If not provided, generates all sitemaps.
	 *
	 * [--page=<page>]
	 * : Optional page number for paginated sitemaps.
	 * ---
	 * default: 1
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate all sitemaps
	 *     wp meowseo sitemap generate
	 *
	 *     # Generate posts sitemap
	 *     wp meowseo sitemap generate post
	 *
	 *     # Generate specific page of posts sitemap
	 *     wp meowseo sitemap generate post --page=2
	 *
	 *     # Generate news sitemap
	 *     wp meowseo sitemap generate news
	 *
	 *     # Generate video sitemap
	 *     wp meowseo sitemap generate video
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function generate( array $args, array $assoc_args ): void {
		$type = isset( $args[0] ) ? $args[0] : null;
		$page = isset( $assoc_args['page'] ) ? absint( $assoc_args['page'] ) : 1;

		if ( $page < 1 ) {
			$page = 1;
		}

		try {
			if ( $type ) {
				// Generate specific sitemap
				$this->generate_specific_sitemap( $type, $page );
			} else {
				// Generate all sitemaps
				$this->generate_all_sitemaps();
			}
		} catch ( \Exception $e ) {
			WP_CLI::error( sprintf( 'Failed to generate sitemap: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Generate a specific sitemap
	 *
	 * @param string $type Sitemap type.
	 * @param int    $page Page number.
	 * @return void
	 */
	private function generate_specific_sitemap( string $type, int $page ): void {
		WP_CLI::log( sprintf( 'Generating %s sitemap (page %d)...', $type, $page ) );

		$xml = '';

		if ( 'news' === $type ) {
			$xml = $this->builder->build_news();
		} elseif ( 'video' === $type ) {
			$xml = $this->builder->build_video();
		} else {
			// Handle post type sitemaps
			$xml = $this->builder->build_posts( $type, $page );
		}

		if ( empty( $xml ) ) {
			WP_CLI::warning( sprintf( 'No content generated for %s sitemap.', $type ) );
			return;
		}

		// Count URLs in the sitemap
		$url_count = substr_count( $xml, '<url>' );

		WP_CLI::success( sprintf( 'Generated %s sitemap with %d URL(s).', $type, $url_count ) );
	}

	/**
	 * Generate all sitemaps
	 *
	 * @return void
	 */
	private function generate_all_sitemaps(): void {
		WP_CLI::log( 'Generating all sitemaps...' );

		$post_types = $this->get_public_post_types();
		$total_generated = 0;

		// Generate index sitemap
		WP_CLI::log( 'Generating index sitemap...' );
		$index_xml = $this->builder->build_index();
		if ( ! empty( $index_xml ) ) {
			$total_generated++;
			WP_CLI::log( '✓ Index sitemap generated' );
		}

		// Generate sitemaps for each post type
		foreach ( $post_types as $post_type ) {
			WP_CLI::log( sprintf( 'Generating %s sitemap...', $post_type ) );

			// Get post count to determine number of pages
			$count = $this->get_post_count( $post_type );

			if ( $count === 0 ) {
				WP_CLI::log( sprintf( '  No published posts found for %s', $post_type ) );
				continue;
			}

			$pages = (int) ceil( $count / 1000 ); // 1000 URLs per sitemap

			// Generate all pages for this post type
			for ( $page = 1; $page <= $pages; $page++ ) {
				$xml = $this->builder->build_posts( $post_type, $page );
				if ( ! empty( $xml ) ) {
					$total_generated++;
				}
			}

			WP_CLI::log( sprintf( '  ✓ Generated %d page(s) for %s (%d posts)', $pages, $post_type, $count ) );
		}

		// Generate news sitemap
		WP_CLI::log( 'Generating news sitemap...' );
		$news_xml = $this->builder->build_news();
		if ( ! empty( $news_xml ) ) {
			$total_generated++;
			$news_count = substr_count( $news_xml, '<url>' );
			WP_CLI::log( sprintf( '  ✓ News sitemap generated with %d URL(s)', $news_count ) );
		} else {
			WP_CLI::log( '  No recent posts for news sitemap' );
		}

		// Generate video sitemap
		WP_CLI::log( 'Generating video sitemap...' );
		$video_xml = $this->builder->build_video();
		if ( ! empty( $video_xml ) ) {
			$total_generated++;
			$video_count = substr_count( $video_xml, '<url>' );
			WP_CLI::log( sprintf( '  ✓ Video sitemap generated with %d URL(s)', $video_count ) );
		} else {
			WP_CLI::log( '  No posts with videos found' );
		}

		WP_CLI::success( sprintf( 'Generated %d sitemap(s) successfully.', $total_generated ) );
	}

	/**
	 * Clear sitemap cache
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Optional sitemap type to clear cache for. If not provided, clears all sitemap cache.
	 *
	 * ## EXAMPLES
	 *
	 *     # Clear cache for specific sitemap type
	 *     wp meowseo sitemap clear-cache --type=post
	 *
	 *     # Clear all sitemap cache
	 *     wp meowseo sitemap clear-cache
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function clear_cache( array $args, array $assoc_args ): void {
		$type = isset( $assoc_args['type'] ) ? $assoc_args['type'] : null;

		try {
			if ( $type ) {
				// Clear cache for specific type
				WP_CLI::log( sprintf( 'Clearing cache for %s sitemap...', $type ) );

				$this->cache->invalidate( $type );

				// Also clear paginated sitemaps
				for ( $page = 2; $page <= 100; $page++ ) {
					$cache_name = $type . '-' . $page;
					$this->cache->invalidate( $cache_name );
				}

				WP_CLI::success( sprintf( 'Cache cleared for %s sitemap.', $type ) );
			} else {
				// Clear all sitemap cache
				WP_CLI::log( 'Clearing all sitemap cache...' );

				$result = $this->cache->invalidate_all();

				if ( $result ) {
					WP_CLI::success( 'All sitemap cache cleared successfully.' );
				} else {
					WP_CLI::warning( 'Failed to clear some sitemap cache files.' );
				}
			}
		} catch ( \Exception $e ) {
			WP_CLI::error( sprintf( 'Failed to clear cache: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Ping search engines
	 *
	 * Notifies Google and Bing about sitemap updates.
	 *
	 * ## EXAMPLES
	 *
	 *     wp meowseo sitemap ping
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function ping( array $args, array $assoc_args ): void {
		WP_CLI::log( 'Pinging search engines...' );

		try {
			$sitemap_url = trailingslashit( get_site_url() ) . 'sitemap.xml';

			WP_CLI::log( sprintf( 'Sitemap URL: %s', $sitemap_url ) );

			$this->ping->ping( $sitemap_url );

			WP_CLI::success( 'Search engines pinged successfully.' );
			WP_CLI::log( 'Note: Search engines may take some time to process the ping.' );

		} catch ( \Exception $e ) {
			WP_CLI::error( sprintf( 'Failed to ping search engines: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Get public post types for sitemap
	 *
	 * @return array Array of post type names.
	 */
	private function get_public_post_types(): array {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);

		// Exclude attachment post type
		unset( $post_types['attachment'] );

		return array_values( $post_types );
	}

	/**
	 * Get post count for a post type
	 *
	 * Counts only published posts that are not marked as noindex.
	 *
	 * @param string $post_type Post type name.
	 * @return int Post count.
	 */
	private function get_post_count( string $post_type ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm 
					ON p.ID = pm.post_id 
					AND pm.meta_key = '_meowseo_noindex'
				WHERE p.post_type = %s
				AND p.post_status = 'publish'
				AND (pm.meta_value IS NULL OR pm.meta_value != '1')",
				$post_type
			)
		);

		return (int) $count;
	}
}
