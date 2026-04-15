<?php
/**
 * Schema Builder helper class.
 *
 * Core schema engine that assembles JSON-LD @graph arrays from individual node builders.
 * Uses node-based architecture where each schema type is a separate class extending Abstract_Schema_Node.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Helpers;

use MeowSEO\Options;
use WP_Post;
use MeowSEO\Helpers\Breadcrumbs;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema Builder class.
 *
 * Assembles complete @graph arrays by collecting nodes from individual node builders.
 * Implements Requirements 1.1, 1.2, 1.3.
 *
 * @since 1.0.0
 */
class Schema_Builder {

	/**
	 * Options instance.
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Post ID
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private int $post_id;

	/**
	 * Post object
	 *
	 * @since 1.0.0
	 * @var WP_Post|null
	 */
	private ?WP_Post $post = null;

	/**
	 * Context data
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $context = array();

	/**
	 * Breadcrumbs instance
	 *
	 * @since 1.0.0
	 * @var Breadcrumbs
	 */
	private Breadcrumbs $breadcrumbs;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->breadcrumbs = new Breadcrumbs( $options );
	}

	/**
	 * Build complete schema graph for a post.
	 *
	 * Returns a complete script tag with JSON-LD @graph array.
	 * Implements Requirement 1.1.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Schema graph array with @context and @graph.
	 */
	public function build( int $post_id ): array {
		$this->post_id = $post_id;
		$this->post    = get_post( $post_id );

		if ( ! $this->post ) {
			return array();
		}

		// Initialize context.
		$this->context = $this->get_context();

		// Collect nodes from builders.
		$nodes = $this->collect_nodes();

		// Assemble @graph array.
		$graph = $this->assemble_graph( $nodes );

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}

	/**
	 * Collect nodes from individual node builders.
	 *
	 * Gathers schema nodes from all node builder classes.
	 * Implements Requirement 1.2.
	 *
	 * @since 1.0.0
	 * @return array Array of schema node arrays.
	 */
	private function collect_nodes(): array {
		$nodes = array();

		// Note: Node builder classes will be implemented in subsequent tasks.
		// For now, we use the legacy methods to maintain backward compatibility.
		
		// Always include base nodes (Requirement 1.3).
		$nodes[] = $this->build_website();
		$nodes[] = $this->build_organization();
		$nodes[] = $this->build_webpage( $this->post );
		$nodes[] = $this->build_breadcrumb( $this->post );

		// Conditional nodes based on post type and schema type.
		if ( $this->should_include_article() ) {
			$nodes[] = $this->build_article( $this->post );
		}

		if ( $this->should_include_product() ) {
			$nodes[] = $this->build_product( $this->post );
		}

		if ( $this->should_include_faq() ) {
			$schema_type = get_post_meta( $this->post_id, 'meowseo_schema_type', true );
			if ( 'FAQPage' === $schema_type ) {
				$faq_items = get_post_meta( $this->post_id, 'meowseo_faq_items', true );
				if ( ! empty( $faq_items ) ) {
					$items = json_decode( $faq_items, true );
					if ( is_array( $items ) && ! empty( $items ) ) {
						$nodes[] = $this->build_faq( $items );
					}
				}
			}
		}

		// Validate and filter nodes (Requirement 17.1, 17.2).
		$validated_nodes = array();
		foreach ( $nodes as $node ) {
			if ( $this->validate_node( $node ) ) {
				$validated_nodes[] = $node;
			}
		}

		return $validated_nodes;
	}

	/**
	 * Assemble @graph array from collected nodes.
	 *
	 * Takes an array of schema nodes and assembles them into a @graph array.
	 * Implements Requirement 1.3.
	 *
	 * @since 1.0.0
	 * @param array $nodes Array of schema node arrays.
	 * @return array Assembled @graph array.
	 */
	private function assemble_graph( array $nodes ): array {
		// Filter out empty nodes.
		$nodes = array_filter( $nodes, function( $node ) {
			return ! empty( $node ) && is_array( $node );
		});

		// Apply individual node filters for each node type.
		$filtered_nodes = array();
		foreach ( $nodes as $node ) {
			if ( isset( $node['@type'] ) ) {
				$node_type = strtolower( $node['@type'] );
				/**
				 * Filter individual schema node before adding to @graph
				 *
				 * Allows customization of specific node types.
				 *
				 * @since 1.0.0
				 * @param array $node    Schema node array.
				 * @param int   $post_id Post ID.
				 */
				$node = apply_filters( "meowseo_schema_node_{$node_type}", $node, $this->post_id );
			}
			$filtered_nodes[] = $node;
		}

		/**
		 * Filter complete @graph array before output
		 *
		 * Allows customization of the entire schema graph.
		 *
		 * @since 1.0.0
		 * @param array $nodes   Array of schema nodes.
		 * @param int   $post_id Post ID.
		 */
		$filtered_nodes = apply_filters( 'meowseo_schema_graph', $filtered_nodes, $this->post_id );

		return array_values( $filtered_nodes );
	}

	/**
	 * Get context data.
	 *
	 * Builds context data array for node builders.
	 *
	 * @since 1.0.0
	 * @return array Context data.
	 */
	private function get_context(): array {
		return array(
			'post_id'     => $this->post_id,
			'post_type'   => $this->post->post_type,
			'schema_type' => get_post_meta( $this->post_id, '_meowseo_schema_type', true ),
			'is_front_page' => is_front_page(),
			'is_archive'    => is_archive(),
			'is_search'     => is_search(),
			'breadcrumbs'   => $this->breadcrumbs,
		);
	}

	/**
	 * Check if Article node should be included.
	 *
	 * Implements conditional logic for Article node (Requirement 1.4).
	 *
	 * @since 1.0.0
	 * @return bool True if Article node should be included.
	 */
	private function should_include_article(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		/**
		 * Filter schema type detection
		 *
		 * Allows customization of schema type for a post.
		 *
		 * @since 1.0.0
		 * @param string $schema_type Current schema type.
		 * @param int    $post_id     Post ID.
		 */
		$schema_type = apply_filters( 'meowseo_schema_type', $schema_type, $this->post_id );
		
		// Include if post type is 'post' OR schema type is 'Article'.
		return 'post' === $this->post->post_type || 'Article' === $schema_type;
	}

	/**
	 * Check if Product node should be included.
	 *
	 * Implements conditional logic for Product node (Requirement 1.5).
	 *
	 * @since 1.0.0
	 * @return bool True if Product node should be included.
	 */
	private function should_include_product(): bool {
		// Include if post type is 'product' AND WooCommerce is active.
		return 'product' === $this->post->post_type && class_exists( 'WooCommerce' );
	}

	/**
	 * Check if FAQ node should be included.
	 *
	 * Implements conditional logic for FAQ node (Requirement 1.6).
	 *
	 * @since 1.0.0
	 * @return bool True if FAQ node should be included.
	 */
	private function should_include_faq(): bool {
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		
		// Include if schema type is 'FAQPage'.
		return 'FAQPage' === $schema_type;
	}

	/**
	 * Check if a specific node type should be included.
	 *
	 * Generic method for checking if a node type should be included.
	 * Implements Requirement 1.3.
	 *
	 * @since 1.0.0
	 * @param string $node_type Node type identifier.
	 * @return bool True if node should be included.
	 */
	private function should_include_node( string $node_type ): bool {
		switch ( $node_type ) {
			case 'article':
				return $this->should_include_article();
			case 'product':
				return $this->should_include_product();
			case 'faq':
				return $this->should_include_faq();
			default:
				return false;
		}
	}

	/**
	 * Build WebSite schema.
	 *
	 * @return array WebSite schema array.
	 */
	public function build_website(): array {
		$site_url = get_site_url();
		$site_name = get_bloginfo( 'name' );
		$site_description = get_bloginfo( 'description' );

		$schema = array(
			'@type'       => 'WebSite',
			'@id'         => $site_url . '/#website',
			'url'         => $site_url,
			'name'        => $site_name,
			'description' => $site_description,
			'publisher'   => array(
				'@id' => $site_url . '/#organization',
			),
		);

		// Add potential action for search.
		$schema['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $site_url . '/?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		);

		return $schema;
	}

	/**
	 * Build WebPage schema.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array WebPage schema array.
	 */
	public function build_webpage( \WP_Post $post ): array {
		$permalink = get_permalink( $post );
		$title = get_the_title( $post );
		$site_url = get_site_url();

		$schema = array(
			'@type'            => 'WebPage',
			'@id'              => $permalink . '#webpage',
			'url'              => $permalink,
			'name'             => $title,
			'isPartOf'         => array(
				'@id' => $site_url . '/#website',
			),
			'datePublished'    => $this->format_date_safe( get_the_date( 'c', $post ) ),
			'dateModified'     => $this->format_date_safe( get_the_modified_date( 'c', $post ) ),
			'breadcrumb'       => array(
				'@id' => $permalink . '#breadcrumb',
			),
			'inLanguage'       => get_bloginfo( 'language' ),
		);

		// Add description if available.
		$description = get_post_meta( $post->ID, 'meowseo_description', true );
		if ( empty( $description ) && ! empty( $post->post_excerpt ) ) {
			$description = $post->post_excerpt;
		}
		if ( ! empty( $description ) ) {
			$schema['description'] = $description;
		}

		// Add featured image if available (with error handling).
		if ( has_post_thumbnail( $post ) ) {
			$image_id = get_post_thumbnail_id( $post );
			$image_url = $this->get_image_url_safe( $image_id );
			if ( $image_url ) {
				$schema['primaryImageOfPage'] = array(
					'@id' => $permalink . '#primaryimage',
				);
			}
		}

		return $schema;
	}

	/**
	 * Build Article schema.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Article schema array.
	 */
	public function build_article( \WP_Post $post ): array {
		$permalink = get_permalink( $post );
		$title = get_the_title( $post );
		$site_url = get_site_url();

		$schema = array(
			'@type'            => 'Article',
			'@id'              => $permalink . '#article',
			'isPartOf'         => array(
				'@id' => $permalink . '#webpage',
			),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $post->post_author ),
				'url'   => get_author_posts_url( $post->post_author ),
			),
			'headline'         => $title,
			'datePublished'    => $this->format_date_safe( get_the_date( 'c', $post ) ),
			'dateModified'     => $this->format_date_safe( get_the_modified_date( 'c', $post ) ),
			'mainEntityOfPage' => array(
				'@id' => $permalink . '#webpage',
			),
			'publisher'        => array(
				'@id' => $site_url . '/#organization',
			),
			'inLanguage'       => get_bloginfo( 'language' ),
		);

		// Add description if available.
		$description = get_post_meta( $post->ID, 'meowseo_description', true );
		if ( empty( $description ) && ! empty( $post->post_excerpt ) ) {
			$description = $post->post_excerpt;
		}
		if ( ! empty( $description ) ) {
			$schema['description'] = $description;
		}

		// Add featured image if available (with error handling).
		if ( has_post_thumbnail( $post ) ) {
			$image_id = get_post_thumbnail_id( $post );
			$image_url = $this->get_image_url_safe( $image_id );
			if ( $image_url ) {
				$schema['image'] = array(
					'@id' => $permalink . '#primaryimage',
				);
			}
		}

		return $schema;
	}

	/**
	 * Build BreadcrumbList schema.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array BreadcrumbList schema array.
	 */
	public function build_breadcrumb( \WP_Post $post ): array {
		$permalink = get_permalink( $post );
		$site_url = get_site_url();
		$site_name = get_bloginfo( 'name' );

		$items = array();

		// Home breadcrumb.
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => $site_name,
			'item'     => $site_url,
		);

		$position = 2;

		// Add post type archive if applicable.
		$post_type_object = get_post_type_object( $post->post_type );
		if ( $post_type_object && $post_type_object->has_archive ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $post_type_object->labels->name,
				'item'     => get_post_type_archive_link( $post->post_type ),
			);
		}

		// Add categories for posts.
		if ( 'post' === $post->post_type ) {
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				$category = $categories[0];
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => $category->name,
					'item'     => get_category_link( $category->term_id ),
				);
			}
		}

		// Current page.
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => get_the_title( $post ),
			'item'     => $permalink,
		);

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $permalink . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	/**
	 * Build Organization schema.
	 *
	 * @return array Organization schema array.
	 */
	public function build_organization(): array {
		$site_url = get_site_url();
		$site_name = get_bloginfo( 'name' );

		$schema = array(
			'@type' => 'Organization',
			'@id'   => $site_url . '/#organization',
			'name'  => $site_name,
			'url'   => $site_url,
		);

		// Add logo if available (with error handling).
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo_url = $this->get_image_url_safe( $custom_logo_id );
			if ( $logo_url ) {
				$schema['logo'] = array(
					'@type' => 'ImageObject',
					'url'   => $logo_url,
				);
			}
		}

		// Add social profiles with filter hook.
		$social_profiles = $this->options->get( 'meowseo_schema_social_profiles', array() );
		
		/**
		 * Filter social profiles for Organization schema
		 *
		 * Allows customization of social media profiles.
		 *
		 * @since 1.0.0
		 * @param array $profiles Array of social profile URLs.
		 */
		$social_profiles = apply_filters( 'meowseo_schema_social_profiles', $social_profiles );
		
		if ( ! empty( $social_profiles ) && is_array( $social_profiles ) ) {
			$schema['sameAs'] = array_values( $social_profiles );
		}

		return $schema;
	}

	/**
	 * Build Product schema (WooCommerce).
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Product schema array.
	 */
	public function build_product( \WP_Post $post ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return array();
		}

		$permalink = get_permalink( $post );
		$site_url = get_site_url();

		$schema = array(
			'@type'       => 'Product',
			'@id'         => $permalink . '#product',
			'name'        => $product->get_name(),
			'url'         => $permalink,
			'description' => wp_strip_all_tags( $product->get_short_description() ),
		);

		// Add SKU if available.
		if ( $product->get_sku() ) {
			$schema['sku'] = $product->get_sku();
		}

		// Add image if available (with error handling).
		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$image_url = $this->get_image_url_safe( $image_id );
			if ( $image_url ) {
				$schema['image'] = $image_url;
			}
		}

		// Add offers.
		$schema['offers'] = array(
			'@type'         => 'Offer',
			'url'           => $permalink,
			'priceCurrency' => get_woocommerce_currency(),
			'price'         => $product->get_price(),
			'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
		);

		// Add aggregate rating if reviews exist.
		$rating_count = $product->get_rating_count();
		if ( $rating_count > 0 ) {
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => $rating_count,
			);
		}

		return $schema;
	}

	/**
	 * Build FAQPage schema.
	 *
	 * @param array $items Array of FAQ items with 'question' and 'answer' keys.
	 * @return array FAQPage schema array.
	 */
	public function build_faq( array $items ): array {
		if ( empty( $items ) ) {
			return array();
		}

		$main_entity = array();

		foreach ( $items as $item ) {
			if ( empty( $item['question'] ) || empty( $item['answer'] ) ) {
				continue;
			}

			$main_entity[] = array(
				'@type'          => 'Question',
				'name'           => $item['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $item['answer'],
				),
			);
		}

		if ( empty( $main_entity ) ) {
			return array();
		}

		return array(
			'@type'      => 'FAQPage',
			'mainEntity' => $main_entity,
		);
	}

	/**
	 * Validate schema node
	 *
	 * Checks that required properties (@type and @id) are present and valid.
	 * Logs warnings for missing properties and skips invalid nodes.
	 * Implements Requirements 17.1, 17.2, 17.3.
	 *
	 * @since 1.0.0
	 * @param array $node Schema node array.
	 * @return bool True if node is valid, false otherwise.
	 */
	private function validate_node( array $node ): bool {
		// Empty nodes are invalid.
		if ( empty( $node ) ) {
			return false;
		}

		// Check for required @type property (Requirement 17.1).
		if ( ! isset( $node['@type'] ) || empty( $node['@type'] ) ) {
			Logger::warning(
				'Schema node missing required @type property',
				array(
					'post_id' => $this->post_id,
					'node'    => wp_json_encode( $node ),
				)
			);
			return false;
		}

		// Check for required @id property (Requirement 17.1).
		if ( ! isset( $node['@id'] ) || empty( $node['@id'] ) ) {
			Logger::warning(
				'Schema node missing required @id property',
				array(
					'post_id' => $this->post_id,
					'type'    => $node['@type'],
				)
			);
			return false;
		}

		// Validate @id is a valid URL (Requirement 17.3).
		if ( ! filter_var( $node['@id'], FILTER_VALIDATE_URL ) ) {
			Logger::warning(
				'Schema node @id is not a valid URL',
				array(
					'post_id' => $this->post_id,
					'type'    => $node['@type'],
					'id'      => $node['@id'],
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Format date with error handling
	 *
	 * Handles invalid date formats with fallback to current time.
	 * Implements Requirement 17.4.
	 *
	 * @since 1.0.0
	 * @param string $date Date string.
	 * @return string ISO 8601 formatted date.
	 */
	private function format_date_safe( string $date ): string {
		// Try to parse the date.
		$timestamp = strtotime( $date );

		// If parsing fails, use current time as fallback.
		if ( false === $timestamp ) {
			Logger::warning(
				'Invalid date format in schema generation, using current time as fallback',
				array(
					'post_id'      => $this->post_id,
					'invalid_date' => $date,
				)
			);
			$timestamp = time();
		}

		// Return ISO 8601 format.
		return gmdate( 'c', $timestamp );
	}

	/**
	 * Get image URL with error handling
	 *
	 * Handles missing images gracefully by returning null instead of failing.
	 *
	 * @since 1.0.0
	 * @param int $image_id Attachment ID.
	 * @return string|null Image URL or null if not found.
	 */
	private function get_image_url_safe( int $image_id ): ?string {
		if ( ! $image_id ) {
			return null;
		}

		$image_url = wp_get_attachment_image_url( $image_id, 'full' );

		if ( false === $image_url || empty( $image_url ) ) {
			Logger::warning(
				'Failed to retrieve image URL for schema',
				array(
					'post_id'  => $this->post_id,
					'image_id' => $image_id,
				)
			);
			return null;
		}

		return $image_url;
	}

	/**
	 * Convert schema graph to JSON string.
	 *
	 * Security: Escapes all output in schema JSON-LD (Requirement 19.2).
	 *
	 * @param array $graph Schema graph array.
	 * @return string JSON-encoded schema.
	 */
	public function to_json( array $graph ): string {
		// Security: Recursively escape all string values in the graph (Requirement 19.2).
		$escaped_graph = $this->escape_schema_values( $graph );
		
		return wp_json_encode( $escaped_graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Recursively escape all string values in schema data
	 *
	 * Security: Ensures all output is properly escaped (Requirement 19.2).
	 *
	 * @since 1.0.0
	 * @param mixed $data Data to escape.
	 * @return mixed Escaped data.
	 */
	private function escape_schema_values( $data ) {
		if ( is_array( $data ) ) {
			$escaped = array();
			foreach ( $data as $key => $value ) {
				$escaped[ $key ] = $this->escape_schema_values( $value );
			}
			return $escaped;
		}

		if ( is_string( $data ) ) {
			// Use esc_html for string values to prevent XSS.
			// JSON encoding will handle the rest.
			return esc_html( $data );
		}

		// Return other types as-is (numbers, booleans, null).
		return $data;
	}

	/**
	 * Get validation errors for debug mode
	 *
	 * Returns array of validation errors when WP_DEBUG is enabled.
	 * Implements Requirements 17.5, 17.6.
	 *
	 * @since 1.0.0
	 * @return array Array of validation error messages.
	 */
	public function get_validation_errors(): array {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return array();
		}

		$errors = array();

		if ( ! $this->post ) {
			return $errors;
		}

		// Collect nodes
		$nodes = $this->collect_nodes();

		// Validate each node
		foreach ( $nodes as $index => $node ) {
			if ( empty( $node ) ) {
				$errors[] = "Node at index {$index} is empty";
				continue;
			}

			if ( ! isset( $node['@type'] ) || empty( $node['@type'] ) ) {
				$errors[] = "Node at index {$index} missing required @type property";
			}

			if ( ! isset( $node['@id'] ) || empty( $node['@id'] ) ) {
				$errors[] = "Node at index {$index} ({$node['@type']}) missing required @id property";
			}

			if ( isset( $node['@id'] ) && ! filter_var( $node['@id'], FILTER_VALIDATE_URL ) ) {
				$errors[] = "Node {$node['@type']} has invalid @id format: {$node['@id']}";
			}

			if ( isset( $node['datePublished'] ) ) {
				$timestamp = strtotime( $node['datePublished'] );
				if ( false === $timestamp ) {
					$errors[] = "Node {$node['@type']} has invalid datePublished format: {$node['datePublished']}";
				}
			}

			if ( isset( $node['dateModified'] ) ) {
				$timestamp = strtotime( $node['dateModified'] );
				if ( false === $timestamp ) {
					$errors[] = "Node {$node['@type']} has invalid dateModified format: {$node['dateModified']}";
				}
			}
		}

		return $errors;
	}

	/**
	 * Output validation errors as HTML comments
	 *
	 * Outputs validation errors as HTML comments when WP_DEBUG is enabled.
	 * Implements Requirements 17.5, 17.6.
	 *
	 * @since 1.0.0
	 * @return string HTML comments with validation errors.
	 */
	public function get_debug_output(): string {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return '';
		}

		$errors = $this->get_validation_errors();

		if ( empty( $errors ) ) {
			return "<!-- MeowSEO Schema Debug: No validation errors -->\n";
		}

		$output = "<!-- MeowSEO Schema Debug: Validation Errors -->\n";
		foreach ( $errors as $error ) {
			$output .= "<!-- Schema Error: " . esc_html( $error ) . " -->\n";
		}

		return $output;
	}
}
