<?php
/**
 * Schema Builder helper class.
 *
 * Constructs JSON-LD structured data arrays. Pure functions — no DB calls, no side effects.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Helpers;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema Builder class.
 */
class Schema_Builder {

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
	 * Build complete schema graph for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array Schema graph array with @context and @graph.
	 */
	public function build( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$graph = array();

		// Always include WebSite schema.
		$graph[] = $this->build_website();

		// Always include Organization schema.
		$graph[] = $this->build_organization();

		// Check for schema type override.
		$schema_type = get_post_meta( $post_id, 'meowseo_schema_type', true );

		// Handle WooCommerce products.
		if ( 'product' === $post->post_type && class_exists( 'WooCommerce' ) ) {
			$graph[] = $this->build_product( $post );
		}
		// Handle FAQ schema.
		elseif ( 'FAQPage' === $schema_type ) {
			$faq_items = get_post_meta( $post_id, 'meowseo_faq_items', true );
			if ( ! empty( $faq_items ) ) {
				$items = json_decode( $faq_items, true );
				if ( is_array( $items ) && ! empty( $items ) ) {
					$graph[] = $this->build_faq( $items );
				}
			}
		}
		// Handle Article schema (posts).
		elseif ( 'post' === $post->post_type || 'Article' === $schema_type ) {
			$graph[] = $this->build_article( $post );
		}
		// Default to WebPage for other post types.
		else {
			$graph[] = $this->build_webpage( $post );
		}

		// Add breadcrumb schema.
		$graph[] = $this->build_breadcrumb( $post );

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
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
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
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

		// Add featured image if available.
		if ( has_post_thumbnail( $post ) ) {
			$image_id = get_post_thumbnail_id( $post );
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );
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
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
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

		// Add featured image if available.
		if ( has_post_thumbnail( $post ) ) {
			$image_id = get_post_thumbnail_id( $post );
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );
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

		// Add logo if available.
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
			if ( $logo_url ) {
				$schema['logo'] = array(
					'@type' => 'ImageObject',
					'url'   => $logo_url,
				);
			}
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

		// Add image if available.
		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );
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
	 * Convert schema graph to JSON string.
	 *
	 * @param array $graph Schema graph array.
	 * @return string JSON-encoded schema.
	 */
	public function to_json( array $graph ): string {
		return wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
}
