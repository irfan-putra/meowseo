<?php
/**
 * Block Manager - Registers and manages Gutenberg blocks
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Blocks;

use MeowSEO\Core\Module_Interface;

/**
 * Block Manager class
 */
class Block_Manager implements Module_Interface {

	/**
	 * Initialize the module
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
	}

	/**
	 * Register block category and blocks
	 */
	public function register_blocks(): void {
		// Register block category
		if ( function_exists( 'register_block_type' ) ) {
			register_block_type( __DIR__ . '/blocks/estimated-reading-time' );
			register_block_type( __DIR__ . '/blocks/related-posts' );
			register_block_type( __DIR__ . '/blocks/siblings' );
			register_block_type( __DIR__ . '/blocks/subpages' );
		}
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_block_assets(): void {
		wp_enqueue_script(
			'meowseo-blocks',
			MEOWSEO_BUILD_URL . '/blocks/index.js',
			[ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data' ],
			MEOWSEO_VERSION,
			true
		);

		wp_enqueue_style(
			'meowseo-blocks-editor',
			MEOWSEO_BUILD_URL . '/blocks/editor.css',
			[],
			MEOWSEO_VERSION
		);

		wp_set_script_translations( 'meowseo-blocks', 'meowseo' );
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets(): void {
		wp_enqueue_style(
			'meowseo-blocks-frontend',
			MEOWSEO_BUILD_URL . '/blocks/style.css',
			[],
			MEOWSEO_VERSION
		);
	}

	/**
	 * Register REST API endpoints for blocks
	 */
	public function register_rest_endpoints(): void {
		// Related posts endpoint
		register_rest_route(
			'meowseo/v1',
			'/related-posts',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_related_posts' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'post_id'  => [
						'type'     => 'integer',
						'required' => true,
					],
					'type'     => [
						'type'    => 'string',
						'default' => 'keyword',
						'enum'    => [ 'keyword', 'category', 'tag' ],
					],
					'limit'    => [
						'type'    => 'integer',
						'default' => 3,
						'minimum' => 1,
						'maximum' => 10,
					],
				],
			]
		);

		// Siblings endpoint
		register_rest_route(
			'meowseo/v1',
			'/siblings',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_siblings' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'post_id'   => [
						'type'     => 'integer',
						'required' => true,
					],
					'order_by'  => [
						'type'    => 'string',
						'default' => 'menu_order',
						'enum'    => [ 'menu_order', 'title', 'date' ],
					],
				],
			]
		);

		// Subpages endpoint
		register_rest_route(
			'meowseo/v1',
			'/subpages',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_subpages' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'post_id' => [
						'type'     => 'integer',
						'required' => true,
					],
					'depth'   => [
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
						'maximum' => 3,
					],
				],
			]
		);
	}

	/**
	 * Get related posts
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_related_posts( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$type    = $request->get_param( 'type' );
		$limit   = $request->get_param( 'limit' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_REST_Response( [], 404 );
		}

		$related = [];

		if ( 'keyword' === $type ) {
			$related = $this->get_posts_by_keyword( $post, $limit );
		} elseif ( 'category' === $type ) {
			$related = $this->get_posts_by_category( $post, $limit );
		} elseif ( 'tag' === $type ) {
			$related = $this->get_posts_by_tag( $post, $limit );
		}

		return new \WP_REST_Response( $related );
	}

	/**
	 * Get posts by keyword similarity
	 *
	 * @param \WP_Post $post Post object.
	 * @param int      $limit Number of posts to return.
	 * @return array
	 */
	private function get_posts_by_keyword( $post, $limit ) {
		$keywords = $this->extract_keywords( $post->post_content );

		if ( empty( $keywords ) ) {
			return [];
		}

		$args = [
			'post_type'      => $post->post_type,
			'posts_per_page' => $limit,
			'post__not_in'   => [ $post->ID ],
			's'              => implode( ' ', $keywords ),
		];

		$query = new \WP_Query( $args );
		return $this->format_posts( $query->posts );
	}

	/**
	 * Get posts by category
	 *
	 * @param \WP_Post $post Post object.
	 * @param int      $limit Number of posts to return.
	 * @return array
	 */
	private function get_posts_by_category( $post, $limit ) {
		$categories = wp_get_post_categories( $post->ID );

		if ( empty( $categories ) ) {
			return [];
		}

		$args = [
			'post_type'      => $post->post_type,
			'posts_per_page' => $limit,
			'post__not_in'   => [ $post->ID ],
			'category__in'   => $categories,
		];

		$query = new \WP_Query( $args );
		return $this->format_posts( $query->posts );
	}

	/**
	 * Get posts by tag
	 *
	 * @param \WP_Post $post Post object.
	 * @param int      $limit Number of posts to return.
	 * @return array
	 */
	private function get_posts_by_tag( $post, $limit ) {
		$tags = wp_get_post_tags( $post->ID, [ 'fields' => 'ids' ] );

		if ( empty( $tags ) ) {
			return [];
		}

		$args = [
			'post_type'      => $post->post_type,
			'posts_per_page' => $limit,
			'post__not_in'   => [ $post->ID ],
			'tag__in'        => $tags,
		];

		$query = new \WP_Query( $args );
		return $this->format_posts( $query->posts );
	}

	/**
	 * Get sibling pages
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_siblings( $request ) {
		$post_id  = $request->get_param( 'post_id' );
		$order_by = $request->get_param( 'order_by' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_REST_Response( [], 404 );
		}

		$args = [
			'post_type'      => $post->post_type,
			'post_parent'    => $post->post_parent,
			'post__not_in'   => [ $post->ID ],
			'posts_per_page' => -1,
			'orderby'        => $order_by,
		];

		$query = new \WP_Query( $args );
		return new \WP_REST_Response( $this->format_posts( $query->posts ) );
	}

	/**
	 * Get subpages
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_subpages( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$depth   = $request->get_param( 'depth' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_REST_Response( [], 404 );
		}

		$subpages = $this->get_subpages_recursive( $post->ID, 1, $depth );
		return new \WP_REST_Response( $subpages );
	}

	/**
	 * Get subpages recursively
	 *
	 * @param int $parent_id Parent post ID.
	 * @param int $current_depth Current depth.
	 * @param int $max_depth Maximum depth.
	 * @return array
	 */
	private function get_subpages_recursive( $parent_id, $current_depth, $max_depth ) {
		if ( $current_depth > $max_depth ) {
			return [];
		}

		$args = [
			'post_type'      => 'page',
			'post_parent'    => $parent_id,
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
		];

		$query = new \WP_Query( $args );
		$subpages = [];

		foreach ( $query->posts as $post ) {
			$subpages[] = [
				'id'              => $post->ID,
				'title'           => $post->post_title,
				'link'            => get_permalink( $post->ID ),
				'featured_media'  => get_post_thumbnail_id( $post->ID ),
				'depth'           => $current_depth,
			];

			// Recursively get child pages
			$subpages = array_merge(
				$subpages,
				$this->get_subpages_recursive( $post->ID, $current_depth + 1, $max_depth )
			);
		}

		return $subpages;
	}

	/**
	 * Extract keywords from content
	 *
	 * @param string $content Post content.
	 * @return array
	 */
	private function extract_keywords( $content ) {
		// Remove HTML tags
		$text = wp_strip_all_tags( $content );

		// Convert to lowercase and split into words
		$words = preg_split( '/\s+/', strtolower( $text ), -1, PREG_SPLIT_NO_EMPTY );

		// Filter out stop words and short words
		$keywords = array_filter(
			$words,
			function ( $word ) {
				return strlen( $word ) > 3 && ! $this->is_stop_word( $word );
			}
		);

		// Count frequency and return top keywords
		$frequency = array_count_values( $keywords );
		arsort( $frequency );

		return array_slice( array_keys( $frequency ), 0, 5 );
	}

	/**
	 * Check if word is a stop word
	 *
	 * @param string $word Word to check.
	 * @return bool
	 */
	private function is_stop_word( $word ) {
		$stop_words = [
			'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
			'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been',
			'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
			'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that',
			'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they',
		];

		return in_array( $word, $stop_words, true );
	}

	/**
	 * Format posts for REST response
	 *
	 * @param array $posts Posts array.
	 * @return array
	 */
	private function format_posts( $posts ) {
		return array_map(
			function ( $post ) {
				return [
					'id'              => $post->ID,
					'title'           => $post->post_title,
					'excerpt'         => wp_trim_words( $post->post_excerpt ?: $post->post_content, 20 ),
					'link'            => get_permalink( $post->ID ),
					'featured_media'  => get_post_thumbnail_id( $post->ID ),
				];
			},
			$posts
		);
	}
}
