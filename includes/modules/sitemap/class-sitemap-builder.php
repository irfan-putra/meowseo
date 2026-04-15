<?php
/**
 * Sitemap Builder
 *
 * Generates XML sitemap content and routes through Sitemap_Cache with lock pattern.
 * Implements performance optimizations for sites with 50,000+ posts.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Sitemap;

use MeowSEO\Helpers\Logger;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemap Builder class
 *
 * Generates sitemap XML content with performance optimizations.
 * All generation is routed through Sitemap_Cache to prevent cache stampede.
 *
 * @since 1.0.0
 */
class Sitemap_Builder {

	/**
	 * Sitemap Cache instance
	 *
	 * @var Sitemap_Cache
	 */
	private Sitemap_Cache $cache;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Maximum URLs per sitemap file
	 *
	 * @var int
	 */
	private int $max_urls_per_sitemap = 1000;

	/**
	 * Constructor
	 *
	 * @param Sitemap_Cache $cache   Sitemap cache instance.
	 * @param Options       $options Options instance.
	 */
	public function __construct( Sitemap_Cache $cache, Options $options ) {
		$this->cache = $cache;
		$this->options = $options;
	}

	/**
	 * Build index sitemap
	 *
	 * Routes through Sitemap_Cache get_or_generate() (Requirement 5.1, 5.2).
	 *
	 * @return string XML content.
	 */
	public function build_index(): string {
		return $this->cache->get_or_generate(
			'index',
			function() {
				return $this->generate_index_xml();
			}
		);
	}

	/**
	 * Build posts sitemap for specific post type and page
	 *
	 * Routes through Sitemap_Cache get_or_generate() (Requirement 5.1, 5.3).
	 *
	 * @param string $post_type Post type name.
	 * @param int    $page      Page number (1-indexed).
	 * @return string XML content.
	 */
	public function build_posts( string $post_type, int $page = 1 ): string {
		$cache_name = $post_type;
		if ( $page > 1 ) {
			$cache_name .= '-' . $page;
		}

		return $this->cache->get_or_generate(
			$cache_name,
			function() use ( $post_type, $page ) {
				return $this->generate_posts_xml( $post_type, $page );
			}
		);
	}

	/**
	 * Generate index sitemap XML
	 *
	 * @return string XML content.
	 */
	private function generate_index_xml(): string {
		$start_time = microtime( true );
		$start_memory = memory_get_usage();

		$post_types = $this->get_public_post_types();
		$site_url = trailingslashit( get_site_url() );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $post_types as $post_type ) {
			// Check if post type has published posts
			$count = $this->get_post_count( $post_type );
			if ( $count === 0 ) {
				continue;
			}

			// Calculate number of pages needed
			$pages = (int) ceil( $count / $this->max_urls_per_sitemap );

			for ( $page = 1; $page <= $pages; $page++ ) {
				$sitemap_name = $post_type;
				if ( $page > 1 ) {
					$sitemap_name .= '-' . $page;
				}

				$xml .= "\t<sitemap>\n";
				$xml .= "\t\t<loc>" . esc_url( $site_url . 'sitemap-' . $sitemap_name . '.xml' ) . "</loc>\n";
				$xml .= "\t\t<lastmod>" . $this->format_date( current_time( 'mysql', true ) ) . "</lastmod>\n";
				$xml .= "\t</sitemap>\n";
			}
		}

		$xml .= '</sitemapindex>';

		// Add debug stats if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$xml .= $this->get_debug_stats( 'index', 0, $start_time, $start_memory );
		}

		return $xml;
	}

	/**
	 * Generate posts sitemap XML
	 *
	 * Uses direct database query with LEFT JOIN to exclude noindex posts
	 * (Requirements 5.3, 5.4, 5.5, 5.6, 5.10, 12.1, 12.2, 12.3).
	 *
	 * @param string $post_type Post type name.
	 * @param int    $page      Page number (1-indexed).
	 * @return string XML content.
	 */
	private function generate_posts_xml( string $post_type, int $page ): string {
		$start_time = microtime( true );
		$start_memory = memory_get_usage();

		$posts = $this->get_posts_for_sitemap( $post_type, $page );
		$url_count = count( $posts );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

		foreach ( $posts as $post ) {
			$xml .= $this->format_url_entry( $post );
		}

		$xml .= '</urlset>';

		// Add debug stats if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$xml .= $this->get_debug_stats( $post_type, $url_count, $start_time, $start_memory );
		}

		/**
		 * Filter sitemap XML before output
		 *
		 * Allows customization of the complete sitemap XML.
		 *
		 * @since 1.0.0
		 * @param string $xml          Complete sitemap XML.
		 * @param string $sitemap_name Sitemap name (post type or 'index', 'news', 'video').
		 */
		$xml = apply_filters( 'meowseo_sitemap_xml', $xml, $post_type );

		return $xml;
	}

	/**
	 * Get posts for sitemap using optimized direct query
	 *
	 * Uses direct database query with LEFT JOIN to exclude noindex posts in single query.
	 * Calls update_post_meta_cache() before loops for performance (Requirements 12.1, 12.2).
	 * Implements Requirement 13.1 for database query error logging.
	 *
	 * @param string $post_type Post type name.
	 * @param int    $page      Page number (1-indexed).
	 * @return array Array of post objects with ID and post_modified_gmt.
	 */
	private function get_posts_for_sitemap( string $post_type, int $page ): array {
		global $wpdb;

		$offset = ( $page - 1 ) * $this->max_urls_per_sitemap;

		// Direct query with LEFT JOIN to exclude noindex posts (Requirement 12.1)
		$query = "
			SELECT p.ID, p.post_modified_gmt
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm 
				ON p.ID = pm.post_id 
				AND pm.meta_key = '_meowseo_noindex'
			WHERE p.post_type = %s
			AND p.post_status = 'publish'
			AND (pm.meta_value IS NULL OR pm.meta_value != '1')
			ORDER BY p.post_modified_gmt DESC
			LIMIT %d OFFSET %d
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				$query,
				$post_type,
				$this->max_urls_per_sitemap,
				$offset
			)
		);

		// Handle database query failure (Requirement 13.1).
		if ( null === $results ) {
			Logger::error(
				'Sitemap database query failed',
				array(
					'post_type'   => $post_type,
					'page'        => $page,
					'error'       => $wpdb->last_error ? $wpdb->last_error : 'Query returned null',
					'query'       => $wpdb->last_query,
				)
			);
			return array();
		}

		// Handle empty result sets gracefully (Requirement 13.1).
		if ( empty( $results ) ) {
			Logger::info(
				'Sitemap query returned no results',
				array(
					'post_type' => $post_type,
					'page'      => $page,
				)
			);
			return array();
		}

		// Batch load postmeta for performance (Requirement 12.2)
		$post_ids = wp_list_pluck( $results, 'ID' );
		update_post_meta_cache( $post_ids );

		return $results;
	}

	/**
	 * Format URL entry for sitemap
	 *
	 * Includes lastmod in ISO 8601 format (Requirement 5.10).
	 *
	 * @param object $post Post object with ID and post_modified_gmt.
	 * @return string XML for URL entry.
	 */
	private function format_url_entry( object $post ): string {
		// Check if post should be excluded using filter hook.
		$exclude = false;
		
		/**
		 * Filter to exclude specific posts from sitemap
		 *
		 * Allows custom exclusion logic for individual posts.
		 *
		 * @since 1.0.0
		 * @param bool $exclude Whether to exclude the post (default: false).
		 * @param int  $post_id Post ID.
		 */
		$exclude = apply_filters( 'meowseo_sitemap_exclude_post', $exclude, $post->ID );
		
		if ( $exclude ) {
			return '';
		}

		$entry = array(
			'loc'     => get_permalink( $post->ID ),
			'lastmod' => $this->format_date( $post->post_modified_gmt ),
		);

		// Add featured image if present (Requirement 15.1)
		$thumbnail_id = get_post_thumbnail_id( $post->ID );
		if ( $thumbnail_id ) {
			$image_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
			if ( $image_url ) {
				$entry['image'] = $image_url;
			}
		}

		/**
		 * Filter sitemap URL entry
		 *
		 * Allows customization of individual URL entries in the sitemap.
		 *
		 * @since 1.0.0
		 * @param array $entry   URL entry data with 'loc', 'lastmod', and optionally 'image'.
		 * @param int   $post_id Post ID.
		 */
		$entry = apply_filters( 'meowseo_sitemap_url_entry', $entry, $post->ID );

		// Build XML from entry data
		$xml = "\t<url>\n";
		$xml .= "\t\t<loc>" . esc_url( $entry['loc'] ) . "</loc>\n";
		$xml .= "\t\t<lastmod>" . $entry['lastmod'] . "</lastmod>\n";

		if ( isset( $entry['image'] ) && ! empty( $entry['image'] ) ) {
			$xml .= "\t\t<image:image>\n";
			$xml .= "\t\t\t<image:loc>" . esc_url( $entry['image'] ) . "</image:loc>\n";
			$xml .= "\t\t</image:image>\n";
		}

		$xml .= "\t</url>\n";

		return $xml;
	}

	/**
	 * Get public post types for sitemap
	 *
	 * Excludes attachment post type (Requirement 19.3).
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

		// Exclude attachment post type (Requirement 19.3)
		unset( $post_types['attachment'] );

		/**
		 * Filter sitemap post types
		 *
		 * Allows customization of which post types are included in sitemaps.
		 *
		 * @since 1.0.0
		 * @param array $post_types Array of post type names.
		 */
		$post_types = apply_filters( 'meowseo_sitemap_post_types', array_values( $post_types ) );

		return $post_types;
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

		// Handle database query failure.
		if ( null === $count ) {
			Logger::error(
				'Failed to get post count for sitemap',
				array(
					'post_type' => $post_type,
					'error'     => $wpdb->last_error ? $wpdb->last_error : 'Query returned null',
				)
			);
			return 0;
		}

		return (int) $count;
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

	/**
	 * Build news sitemap
	 *
	 * Generates Google News sitemap with posts from last 48 hours only.
	 * Routes through Sitemap_Cache (Requirements 5.7, 16.1, 16.2, 16.3, 16.4, 16.5, 16.6).
	 *
	 * @return string XML content.
	 */
	public function build_news(): string {
		return $this->cache->get_or_generate(
			'news',
			function() {
				return $this->generate_news_xml();
			}
		);
	}

	/**
	 * Generate news sitemap XML
	 *
	 * Includes only posts from last 48 hours with Google News XML format
	 * (Requirements 16.1, 16.2, 16.3, 16.4, 16.5).
	 *
	 * @return string XML content.
	 */
	private function generate_news_xml(): string {
		$start_time = microtime( true );
		$start_memory = memory_get_usage();

		$posts = $this->get_recent_posts_for_news();
		$url_count = count( $posts );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

		$site_name = get_bloginfo( 'name' );
		$site_language = get_bloginfo( 'language' );

		foreach ( $posts as $post ) {
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( get_permalink( $post->ID ) ) . "</loc>\n";
			$xml .= "\t\t<news:news>\n";
			$xml .= "\t\t\t<news:publication>\n";
			$xml .= "\t\t\t\t<news:name>" . esc_html( $site_name ) . "</news:name>\n";
			$xml .= "\t\t\t\t<news:language>" . esc_html( $site_language ) . "</news:language>\n";
			$xml .= "\t\t\t</news:publication>\n";
			$xml .= "\t\t\t<news:publication_date>" . $this->format_date( $post->post_date_gmt ) . "</news:publication_date>\n";
			$xml .= "\t\t\t<news:title>" . esc_html( get_the_title( $post->ID ) ) . "</news:title>\n";
			$xml .= "\t\t</news:news>\n";
			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		// Add debug stats if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$xml .= $this->get_debug_stats( 'news', $url_count, $start_time, $start_memory );
		}

		return $xml;
	}

	/**
	 * Get recent posts for news sitemap
	 *
	 * Queries posts from last 48 hours only, excluding noindex posts
	 * (Requirements 16.1, 16.5).
	 *
	 * @return array Array of post objects with ID, post_date_gmt.
	 */
	private function get_recent_posts_for_news(): array {
		global $wpdb;

		// Calculate 48 hours ago timestamp
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( '-48 hours' ) );

		// Direct query with LEFT JOIN to exclude noindex posts
		$query = "
			SELECT p.ID, p.post_date_gmt
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm 
				ON p.ID = pm.post_id 
				AND pm.meta_key = '_meowseo_noindex'
			WHERE p.post_type = 'post'
			AND p.post_status = 'publish'
			AND p.post_date_gmt >= %s
			AND (pm.meta_value IS NULL OR pm.meta_value != '1')
			ORDER BY p.post_date_gmt DESC
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare( $query, $cutoff_date )
		);

		// Handle database query failure.
		if ( null === $results ) {
			Logger::error(
				'News sitemap database query failed',
				array(
					'cutoff_date' => $cutoff_date,
					'error'       => $wpdb->last_error ? $wpdb->last_error : 'Query returned null',
				)
			);
			return array();
		}

		// Handle empty result sets gracefully.
		if ( empty( $results ) ) {
			Logger::info(
				'News sitemap query returned no results',
				array(
					'cutoff_date' => $cutoff_date,
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
	 * Build video sitemap
	 *
	 * Generates Google Video sitemap scanning for YouTube and Vimeo embeds.
	 * Routes through Sitemap_Cache (Requirements 5.8, 15.4, 15.6, 15.7).
	 *
	 * @return string XML content.
	 */
	public function build_video(): string {
		return $this->cache->get_or_generate(
			'video',
			function() {
				return $this->generate_video_xml();
			}
		);
	}

	/**
	 * Generate video sitemap XML
	 *
	 * Scans post content for video embeds and includes metadata
	 * (Requirements 15.4, 15.6, 15.7).
	 *
	 * @return string XML content.
	 */
	private function generate_video_xml(): string {
		$start_time = microtime( true );
		$start_memory = memory_get_usage();

		$posts_with_videos = $this->get_posts_with_videos();
		$url_count = count( $posts_with_videos );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

		foreach ( $posts_with_videos as $post_data ) {
			$post = $post_data['post'];
			$videos = $post_data['videos'];

			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( get_permalink( $post->ID ) ) . "</loc>\n";

			foreach ( $videos as $video ) {
				$xml .= "\t\t<video:video>\n";
				$xml .= "\t\t\t<video:title>" . esc_html( $video['title'] ) . "</video:title>\n";
				$xml .= "\t\t\t<video:description>" . esc_html( $video['description'] ) . "</video:description>\n";
				$xml .= "\t\t\t<video:thumbnail_loc>" . esc_url( $video['thumbnail_url'] ) . "</video:thumbnail_loc>\n";
				$xml .= "\t\t\t<video:content_loc>" . esc_url( $video['content_url'] ) . "</video:content_loc>\n";
				$xml .= "\t\t</video:video>\n";
			}

			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		// Add debug stats if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$xml .= $this->get_debug_stats( 'video', $url_count, $start_time, $start_memory );
		}

		return $xml;
	}

	/**
	 * Get posts with video embeds
	 *
	 * Scans published posts for YouTube and Vimeo embeds
	 * (Requirements 5.8, 15.7).
	 *
	 * @return array Array of posts with video metadata.
	 */
	private function get_posts_with_videos(): array {
		global $wpdb;

		// Query published posts excluding noindex
		$query = "
			SELECT p.ID, p.post_content
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm 
				ON p.ID = pm.post_id 
				AND pm.meta_key = '_meowseo_noindex'
			WHERE p.post_type = 'post'
			AND p.post_status = 'publish'
			AND (pm.meta_value IS NULL OR pm.meta_value != '1')
			ORDER BY p.post_modified_gmt DESC
			LIMIT 1000
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$posts = $wpdb->get_results( $query );

		// Handle database query failure.
		if ( null === $posts ) {
			Logger::error(
				'Video sitemap database query failed',
				array(
					'error' => $wpdb->last_error ? $wpdb->last_error : 'Query returned null',
				)
			);
			return array();
		}

		// Handle empty result sets gracefully.
		if ( empty( $posts ) ) {
			Logger::info( 'Video sitemap query returned no results' );
			return array();
		}

		$posts_with_videos = array();

		foreach ( $posts as $post ) {
			$videos = $this->detect_video_embeds( $post->post_content );

			if ( ! empty( $videos ) ) {
				$posts_with_videos[] = array(
					'post' => $post,
					'videos' => $videos,
				);
			}
		}

		return $posts_with_videos;
	}

	/**
	 * Detect video embeds in content
	 *
	 * Scans content for YouTube and Vimeo URLs using regex patterns
	 * (Requirements 5.8, 5.9, 15.2, 15.3).
	 *
	 * @param string $content Post content.
	 * @return array Array of video metadata.
	 */
	private function detect_video_embeds( string $content ): array {
		$videos = array();

		// Detect YouTube embeds (Requirements 15.2)
		$youtube_pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i';
		if ( preg_match_all( $youtube_pattern, $content, $youtube_matches ) ) {
			foreach ( $youtube_matches[1] as $video_id ) {
				$metadata = $this->get_youtube_metadata( $video_id );
				if ( $metadata ) {
					$videos[] = $metadata;
				}
			}
		}

		// Detect Vimeo embeds (Requirements 15.3)
		$vimeo_pattern = '/(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(?:video\/)?(\d+)/i';
		if ( preg_match_all( $vimeo_pattern, $content, $vimeo_matches ) ) {
			foreach ( $vimeo_matches[1] as $video_id ) {
				$metadata = $this->get_vimeo_metadata( $video_id );
				if ( $metadata ) {
					$videos[] = $metadata;
				}
			}
		}

		return $videos;
	}

	/**
	 * Get YouTube video metadata using oEmbed API
	 *
	 * Fetches video metadata and caches for 24 hours
	 * (Requirements 5.9, 15.5).
	 *
	 * @param string $video_id YouTube video ID.
	 * @return array|null Video metadata or null on failure.
	 */
	private function get_youtube_metadata( string $video_id ): ?array {
		$cache_key = 'youtube_metadata_' . $video_id;
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$oembed_url = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $video_id . '&format=json';
		$response = wp_remote_get( $oembed_url );

		if ( is_wp_error( $response ) ) {
			Logger::error(
				'YouTube oEmbed API request failed',
				array(
					'video_id' => $video_id,
					'error' => $response->get_error_message(),
				)
			);
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data || ! isset( $data['title'] ) ) {
			Logger::error(
				'YouTube oEmbed API returned invalid data',
				array(
					'video_id' => $video_id,
				)
			);
			return null;
		}

		$metadata = array(
			'title' => $data['title'],
			'description' => $data['title'], // YouTube oEmbed doesn't provide description
			'thumbnail_url' => $data['thumbnail_url'] ?? "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg",
			'content_url' => "https://www.youtube.com/watch?v={$video_id}",
		);

		// Cache for 24 hours (Requirement 15.5)
		set_transient( $cache_key, $metadata, 86400 );

		return $metadata;
	}

	/**
	 * Get Vimeo video metadata using oEmbed API
	 *
	 * Fetches video metadata and caches for 24 hours
	 * (Requirements 5.9, 15.5).
	 *
	 * @param string $video_id Vimeo video ID.
	 * @return array|null Video metadata or null on failure.
	 */
	private function get_vimeo_metadata( string $video_id ): ?array {
		$cache_key = 'vimeo_metadata_' . $video_id;
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$oembed_url = 'https://vimeo.com/api/oembed.json?url=https://vimeo.com/' . $video_id;
		$response = wp_remote_get( $oembed_url );

		if ( is_wp_error( $response ) ) {
			Logger::error(
				'Vimeo oEmbed API request failed',
				array(
					'video_id' => $video_id,
					'error' => $response->get_error_message(),
				)
			);
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data || ! isset( $data['title'] ) ) {
			Logger::error(
				'Vimeo oEmbed API returned invalid data',
				array(
					'video_id' => $video_id,
				)
			);
			return null;
		}

		$metadata = array(
			'title' => $data['title'],
			'description' => $data['description'] ?? $data['title'],
			'thumbnail_url' => $data['thumbnail_url'] ?? '',
			'content_url' => "https://vimeo.com/{$video_id}",
		);

		// Cache for 24 hours (Requirement 15.5)
		set_transient( $cache_key, $metadata, 86400 );

		return $metadata;
	}

	/**
	 * Get debug stats for sitemap generation
	 *
	 * Returns XML comment with generation statistics when WP_DEBUG is enabled.
	 * Includes timing, memory usage, and URL count.
	 *
	 * @since 1.0.0
	 * @param string $sitemap_type Sitemap type (index, post type, news, video).
	 * @param int    $url_count    Number of URLs in sitemap.
	 * @param float  $start_time   Start time from microtime(true).
	 * @param int    $start_memory Start memory from memory_get_usage().
	 * @return string XML comment with debug stats.
	 */
	private function get_debug_stats( string $sitemap_type, int $url_count, float $start_time, int $start_memory ): string {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return '';
		}

		$end_time = microtime( true );
		$end_memory = memory_get_usage();

		$generation_time = round( ( $end_time - $start_time ) * 1000, 2 ); // Convert to milliseconds
		$memory_used = round( ( $end_memory - $start_memory ) / 1024 / 1024, 2 ); // Convert to MB
		$peak_memory = round( memory_get_peak_usage() / 1024 / 1024, 2 ); // Convert to MB

		$stats = "\n<!-- MeowSEO Sitemap Debug Stats -->\n";
		$stats .= "<!-- Sitemap Type: {$sitemap_type} -->\n";
		$stats .= "<!-- URL Count: {$url_count} -->\n";
		$stats .= "<!-- Generation Time: {$generation_time}ms -->\n";
		$stats .= "<!-- Memory Used: {$memory_used}MB -->\n";
		$stats .= "<!-- Peak Memory: {$peak_memory}MB -->\n";
		$stats .= "<!-- Generated: " . gmdate( 'Y-m-d H:i:s' ) . " UTC -->\n";

		return $stats;
	}
}
