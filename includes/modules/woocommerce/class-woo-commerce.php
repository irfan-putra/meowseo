<?php
/**
 * WooCommerce Module
 *
 * Provides SEO enhancements specific to WooCommerce product pages.
 * Only loaded when WooCommerce is active.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\WooCommerce;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce module class
 *
 * Extends Meta and Schema modules for product post type.
 * Adds SEO score columns to WooCommerce product list table.
 *
 * @since 1.0.0
 */
class WooCommerce implements Module {

	/**
	 * Module ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const MODULE_ID = 'woocommerce';

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module
	 *
	 * Register hooks and initialize module functionality.
	 * Hooks are registered after WooCommerce initialization (Requirement 20.3).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		// Ensure WooCommerce is active (Requirement 20.2)
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Register hooks after WooCommerce initialization (Requirement 20.3)
		add_action( 'woocommerce_loaded', array( $this, 'register_hooks' ) );
	}

	/**
	 * Register module hooks after WooCommerce initialization
	 *
	 * Called on 'woocommerce_loaded' action to ensure WooCommerce is fully initialized
	 * before registering hooks (Requirement 20.3).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_hooks(): void {
		// Add SEO score columns to product list table (Requirement 20.4)
		add_filter( 'manage_product_posts_columns', array( $this, 'add_seo_score_column' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_seo_score_column' ), 10, 2 );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'make_seo_score_sortable' ) );

		// Filter sitemap posts to exclude out-of-stock products
		add_filter( 'meowseo_sitemap_posts', array( $this, 'filter_sitemap_products' ), 10, 2 );

		// Add products to sitemap with proper priority and changefreq (Requirement 22)
		add_filter( 'meowseo_sitemap_url_entry', array( $this, 'add_product_sitemap_metadata' ), 10, 2 );
	}

	/**
	 * Get module ID
	 *
	 * @since 1.0.0
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return self::MODULE_ID;
	}

	/**
	 * Add SEO score column to product list table
	 *
	 * (Requirement 12.4)
	 *
	 * @since 1.0.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_seo_score_column( array $columns ): array {
		// Insert SEO score column after the product name
		$new_columns = array();
		
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			
			if ( 'name' === $key ) {
				$new_columns['meowseo_score'] = __( 'SEO Score', 'meowseo' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render SEO score column content
	 *
	 * (Requirement 12.4)
	 *
	 * @since 1.0.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_seo_score_column( string $column, int $post_id ): void {
		if ( 'meowseo_score' !== $column ) {
			return;
		}

		// Get Meta module to compute SEO score
		$plugin = \MeowSEO\Plugin::instance();
		$module_manager = $plugin->get_module_manager();
		$meta_module = $module_manager->get_module( 'meta' );

		if ( ! $meta_module ) {
			echo '<span style="color: #999;">—</span>';
			return;
		}

		// Get post content for analysis
		$post = get_post( $post_id );
		if ( ! $post ) {
			echo '<span style="color: #999;">—</span>';
			return;
		}

		// Get SEO analysis
		$analysis = $meta_module->get_seo_analysis( $post_id, $post->post_content );

		if ( empty( $analysis ) || ! isset( $analysis['score'], $analysis['color'] ) ) {
			echo '<span style="color: #999;">—</span>';
			return;
		}

		$score = $analysis['score'];
		$color = $analysis['color'];

		// Map color to hex
		$color_map = array(
			'red'    => '#dc3232',
			'orange' => '#f56e28',
			'green'  => '#46b450',
		);

		$hex_color = $color_map[ $color ] ?? '#999';

		// Render score indicator
		echo '<span style="display: inline-flex; align-items: center; gap: 6px;">';
		echo '<span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: ' . esc_attr( $hex_color ) . ';"></span>';
		echo '<span style="font-weight: 500;">' . esc_html( $score ) . '/100</span>';
		echo '</span>';
	}

	/**
	 * Make SEO score column sortable
	 *
	 * @since 1.0.0
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function make_seo_score_sortable( array $columns ): array {
		$columns['meowseo_score'] = 'meowseo_score';
		return $columns;
	}

	/**
	 * Generate Product schema for single product pages
	 *
	 * Generates Product schema with name, description, image, sku, brand, offers, and aggregateRating.
	 * Validates schema against schema.org specification.
	 *
	 * (Requirements 21.1, 21.2, 21.3, 21.4, 21.5, 21.6, 21.7)
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return array Product schema array or empty array if product not found.
	 */
	public function generate_product_schema( int $product_id ): array {
		// Ensure WooCommerce is active (Requirement 21.1)
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		// Get WooCommerce product object
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array();
		}

		$permalink = get_permalink( $product_id );

		// Build base Product schema (Requirement 21.2)
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Product',
			'name'     => $product->get_name(),
			'url'      => $permalink,
		);

		// Add description (Requirement 21.2)
		$description = $product->get_description();
		if ( empty( $description ) ) {
			$description = $product->get_short_description();
		}
		if ( ! empty( $description ) ) {
			$schema['description'] = wp_strip_all_tags( $description );
		}

		// Add SKU (Requirement 21.2)
		$sku = $product->get_sku();
		if ( ! empty( $sku ) ) {
			$schema['sku'] = $sku;
		}

		// Add image (Requirement 21.2)
		$image_id = $product->get_image_id();
		if ( ! empty( $image_id ) ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );
			if ( ! empty( $image_url ) ) {
				$schema['image'] = $image_url;
			}
		}

		// Add brand (Requirement 21.2)
		$brand = $this->get_product_brand( $product );
		if ( ! empty( $brand ) ) {
			$schema['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brand,
			);
		}

		// Add offers (Requirements 21.2, 21.3, 21.4, 21.5)
		$offers = $this->build_product_offers( $product, $permalink );
		if ( ! empty( $offers ) ) {
			$schema['offers'] = $offers;
		}

		// Add aggregateRating when reviews exist (Requirements 21.2, 21.6)
		$rating = $this->build_product_aggregate_rating( $product );
		if ( ! empty( $rating ) ) {
			$schema['aggregateRating'] = $rating;
		}

		// Validate schema against schema.org specification (Requirement 21.7)
		if ( ! $this->validate_product_schema( $schema ) ) {
			// Log validation error but still return schema
			error_log( 'Product schema validation failed for product ID: ' . $product_id );
		}

		return $schema;
	}

	/**
	 * Get product brand
	 *
	 * Retrieves product brand from custom field or returns empty string.
	 *
	 * @since 1.0.0
	 * @param \WC_Product $product WooCommerce product object.
	 * @return string Product brand name or empty string.
	 */
	private function get_product_brand( \WC_Product $product ): string {
		// Try to get brand from custom field
		$brand = get_post_meta( $product->get_id(), '_product_brand', true );
		
		if ( ! empty( $brand ) ) {
			return (string) $brand;
		}

		// Try to get brand from product attribute if available
		if ( function_exists( 'wc_get_product_terms' ) ) {
			$brands = wc_get_product_terms( $product->get_id(), 'pa_brand', array( 'fields' => 'names' ) );
			if ( ! empty( $brands ) && is_array( $brands ) ) {
				return (string) $brands[0];
			}
		}

		return '';
	}

	/**
	 * Build product offers
	 *
	 * Generates Offer with price, priceCurrency, and availability.
	 *
	 * (Requirements 21.3, 21.4, 21.5)
	 *
	 * @since 1.0.0
	 * @param \WC_Product $product   WooCommerce product object.
	 * @param string      $permalink Product permalink.
	 * @return array Offer schema or empty array.
	 */
	private function build_product_offers( \WC_Product $product, string $permalink ): array {
		$price = $product->get_price();
		
		// Return empty if no price is set
		if ( empty( $price ) ) {
			return array();
		}

		$offers = array(
			'@type'         => 'Offer',
			'url'           => $permalink,
			'priceCurrency' => get_woocommerce_currency(), // Requirement 21.4
			'price'         => (string) $price, // Requirement 21.3
		);

		// Add availability (Requirement 21.5)
		$availability = $this->get_product_availability_schema( $product );
		if ( ! empty( $availability ) ) {
			$offers['availability'] = $availability;
		}

		return $offers;
	}

	/**
	 * Get product availability schema URL
	 *
	 * Maps WooCommerce stock status to Schema.org availability values.
	 * (Requirement 21.5)
	 *
	 * @since 1.0.0
	 * @param \WC_Product $product WooCommerce product object.
	 * @return string Schema.org availability URL.
	 */
	private function get_product_availability_schema( \WC_Product $product ): string {
		// Check if product is in stock
		if ( $product->is_in_stock() ) {
			return 'https://schema.org/InStock';
		}

		// Check if product is on backorder (PreOrder)
		if ( $product->is_on_backorder() ) {
			return 'https://schema.org/PreOrder';
		}

		// Default to OutOfStock
		return 'https://schema.org/OutOfStock';
	}

	/**
	 * Build product aggregateRating
	 *
	 * Generates aggregateRating when product has reviews.
	 * (Requirement 21.6)
	 *
	 * @since 1.0.0
	 * @param \WC_Product $product WooCommerce product object.
	 * @return array AggregateRating schema or empty array.
	 */
	private function build_product_aggregate_rating( \WC_Product $product ): array {
		$review_count = $product->get_review_count();
		
		// Return empty if no reviews
		if ( empty( $review_count ) || $review_count < 1 ) {
			return array();
		}

		$average_rating = $product->get_average_rating();
		
		// Return empty if no rating
		if ( empty( $average_rating ) ) {
			return array();
		}

		return array(
			'@type'       => 'AggregateRating',
			'ratingValue' => (string) $average_rating,
			'reviewCount' => (int) $review_count,
		);
	}

	/**
	 * Validate product schema against schema.org specification
	 *
	 * Validates that the Product schema contains required fields and proper structure.
	 * (Requirement 21.7)
	 *
	 * @since 1.0.0
	 * @param array $schema Product schema array.
	 * @return bool True if schema is valid, false otherwise.
	 */
	private function validate_product_schema( array $schema ): bool {
		// Check required fields
		$required_fields = array( '@context', '@type', 'name', 'url' );
		
		foreach ( $required_fields as $field ) {
			if ( ! isset( $schema[ $field ] ) || empty( $schema[ $field ] ) ) {
				return false;
			}
		}

		// Validate @type is Product
		if ( 'Product' !== $schema['@type'] ) {
			return false;
		}

		// Validate @context is schema.org
		if ( 'https://schema.org' !== $schema['@context'] ) {
			return false;
		}

		// Validate offers structure if present
		if ( isset( $schema['offers'] ) && is_array( $schema['offers'] ) ) {
			$required_offer_fields = array( '@type', 'url', 'priceCurrency', 'price' );
			foreach ( $required_offer_fields as $field ) {
				if ( ! isset( $schema['offers'][ $field ] ) || empty( $schema['offers'][ $field ] ) ) {
					return false;
				}
			}

			// Validate offer @type
			if ( 'Offer' !== $schema['offers']['@type'] ) {
				return false;
			}
		}

		// Validate aggregateRating structure if present
		if ( isset( $schema['aggregateRating'] ) && is_array( $schema['aggregateRating'] ) ) {
			$required_rating_fields = array( '@type', 'ratingValue', 'reviewCount' );
			foreach ( $required_rating_fields as $field ) {
				if ( ! isset( $schema['aggregateRating'][ $field ] ) ) {
					return false;
				}
			}

			// Validate aggregateRating @type
			if ( 'AggregateRating' !== $schema['aggregateRating']['@type'] ) {
				return false;
			}

			// Validate ratingValue is numeric
			if ( ! is_numeric( $schema['aggregateRating']['ratingValue'] ) ) {
				return false;
			}

			// Validate reviewCount is numeric
			if ( ! is_numeric( $schema['aggregateRating']['reviewCount'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Filter sitemap products to exclude out-of-stock items
	 *
	 * (Requirement 12.3)
	 *
	 * @since 1.0.0
	 * @param array  $posts     Array of WP_Post objects.
	 * @param string $post_type Post type name.
	 * @return array Filtered posts.
	 */
	public function filter_sitemap_products( array $posts, string $post_type ): array {
		// Only filter product post type
		if ( 'product' !== $post_type ) {
			return $posts;
		}

		// Check if out-of-stock exclusion is enabled
		$exclude_out_of_stock = $this->options->get( 'woocommerce_exclude_out_of_stock', false );
		
		if ( ! $exclude_out_of_stock ) {
			return $posts;
		}

		// Filter out out-of-stock products
		return array_filter(
			$posts,
			function ( $post ) {
				$product = wc_get_product( $post->ID );
				
				if ( ! $product ) {
					return true; // Keep if product object can't be loaded
				}

				return $product->is_in_stock();
			}
		);
	}

	/**
	 * Add product sitemap metadata (priority and changefreq)
	 *
	 * Adds priority (0.8) and changefreq (weekly) to product URLs in sitemaps.
	 * (Requirements 22.4, 22.5)
	 *
	 * @since 1.0.0
	 * @param array $entry   URL entry data with 'loc', 'lastmod', and optionally 'image'.
	 * @param int   $post_id Post ID.
	 * @return array Modified URL entry with priority and changefreq for products.
	 */
	public function add_product_sitemap_metadata( array $entry, int $post_id ): array {
		// Get the post object to check post type
		$post = get_post( $post_id );
		
		if ( ! $post || 'product' !== $post->post_type ) {
			return $entry;
		}

		// Add product-specific priority and changefreq (Requirements 22.4, 22.5)
		$entry['priority'] = '0.8';
		$entry['changefreq'] = 'weekly';

		return $entry;
	}

	/**
	 * Add products to XML sitemaps
	 *
	 * Adds product post type to sitemap generation with proper configuration.
	 * Respects "Exclude out-of-stock products" setting.
	 * Sets product priority to 0.8 and changefreq to weekly.
	 * Uses product modified date for lastmod.
	 *
	 * (Requirements 22.1, 22.2, 22.3, 22.4, 22.5, 22.6)
	 *
	 * @since 1.0.0
	 * @param array $sitemap Sitemap array.
	 * @return array Modified sitemap array with products added.
	 */
	public function add_products_to_sitemap( array $sitemap ): array {
		// Ensure WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $sitemap;
		}

		// Get all published products
		$products = get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
			)
		);

		if ( empty( $products ) ) {
			return $sitemap;
		}

		// Check if out-of-stock exclusion is enabled (Requirement 22.2)
		$exclude_out_of_stock = $this->options->get( 'woocommerce_exclude_out_of_stock', false );

		// Process each product
		foreach ( $products as $product_id ) {
			$product = wc_get_product( $product_id );
			
			if ( ! $product ) {
				continue;
			}

			// Exclude out-of-stock products if setting enabled (Requirement 22.3)
			if ( $exclude_out_of_stock && ! $product->is_in_stock() ) {
				continue;
			}

			// Get product modified date for lastmod (Requirement 22.6)
			$post = get_post( $product_id );
			if ( ! $post ) {
				continue;
			}

			// Add product to sitemap with proper metadata
			$sitemap[] = array(
				'loc'        => get_permalink( $product_id ),
				'lastmod'    => gmdate( 'Y-m-d\TH:i:s\+00:00', strtotime( $post->post_modified_gmt ) ),
				'priority'   => '0.8', // Requirement 22.4
				'changefreq' => 'weekly', // Requirement 22.5
			);
		}

		return $sitemap;
	}

	/**
	 * Generate product breadcrumbs with category hierarchy
	 *
	 * Generates breadcrumbs for product pages including category hierarchy.
	 * Uses primary category if product has multiple categories.
	 * Includes Shop page in breadcrumb trail before categories.
	 * Formats as: Home > Shop > Category > Subcategory > Product
	 * Generates BreadcrumbList schema for product pages.
	 *
	 * (Requirements 24.1, 24.2, 24.3, 24.4, 24.5)
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return array Breadcrumb array with 'breadcrumbs' (array of items) and 'schema' (BreadcrumbList schema).
	 */
	public function generate_product_breadcrumbs( int $product_id ): array {
		// Ensure WooCommerce is active (Requirement 24.1)
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		// Get WooCommerce product object
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array();
		}

		$breadcrumbs = array();

		// Add Home (Requirement 24.4)
		$breadcrumbs[] = array(
			'label' => __( 'Home', 'meowseo' ),
			'url'   => home_url(),
		);

		// Add Shop page (Requirement 24.3)
		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop_page_id = wc_get_page_id( 'shop' );
			if ( $shop_page_id ) {
				$breadcrumbs[] = array(
					'label' => get_the_title( $shop_page_id ),
					'url'   => get_permalink( $shop_page_id ),
				);
			}
		}

		// Get product categories
		$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'orderby' => 'parent' ) );

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			// Use primary category if available (Requirement 24.2)
			$primary_category = null;

			// Check if product has a primary category set (WooCommerce Premium feature)
			$primary_cat_id = get_post_meta( $product_id, '_primary_product_cat', true );
			if ( ! empty( $primary_cat_id ) ) {
				$primary_category = get_term( $primary_cat_id, 'product_cat' );
			}

			// Fall back to first category if no primary category
			if ( ! $primary_category || is_wp_error( $primary_category ) ) {
				$primary_category = $categories[0];
			}

			// Build category hierarchy from root to current (Requirement 24.4)
			if ( $primary_category && ! is_wp_error( $primary_category ) ) {
				$category_hierarchy = $this->get_category_hierarchy( $primary_category->term_id );

				// Add category hierarchy to breadcrumbs
				foreach ( $category_hierarchy as $category ) {
					$breadcrumbs[] = array(
						'label' => $category->name,
						'url'   => get_term_link( $category, 'product_cat' ),
					);
				}
			}
		}

		// Add product title (Requirement 24.4)
		$breadcrumbs[] = array(
			'label' => $product->get_name(),
			'url'   => get_permalink( $product_id ),
		);

		// Generate BreadcrumbList schema (Requirement 24.5)
		$schema = $this->build_breadcrumb_schema( $breadcrumbs );

		return array(
			'breadcrumbs' => $breadcrumbs,
			'schema'      => $schema,
		);
	}

	/**
	 * Get category hierarchy from root to current category
	 *
	 * Traverses parent categories to build complete hierarchy.
	 *
	 * @since 1.0.0
	 * @param int $category_id Category term ID.
	 * @return array Array of category objects from root to current.
	 */
	private function get_category_hierarchy( int $category_id ): array {
		$hierarchy = array();
		$current_id = $category_id;

		// Traverse up to root category
		while ( $current_id ) {
			$category = get_term( $current_id, 'product_cat' );

			if ( ! $category || is_wp_error( $category ) ) {
				break;
			}

			$hierarchy[] = $category;
			$current_id = $category->parent;
		}

		// Reverse to get from root to current
		return array_reverse( $hierarchy );
	}

	/**
	 * Build BreadcrumbList schema
	 *
	 * Generates Schema.org BreadcrumbList from breadcrumb array.
	 *
	 * @since 1.0.0
	 * @param array $breadcrumbs Array of breadcrumb items with 'label' and 'url'.
	 * @return array BreadcrumbList schema array.
	 */
	private function build_breadcrumb_schema( array $breadcrumbs ): array {
		if ( empty( $breadcrumbs ) ) {
			return array();
		}

		$item_list_element = array();

		foreach ( $breadcrumbs as $position => $item ) {
			$position_num = $position + 1;

			$list_item = array(
				'@type'    => 'ListItem',
				'position' => $position_num,
				'name'     => $item['label'],
			);

			// Only include 'item' property if URL is not empty
			if ( ! empty( $item['url'] ) ) {
				$list_item['item'] = $item['url'];
			}

			$item_list_element[] = $list_item;
		}

		return array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BreadcrumbList',
			'itemListElement'  => $item_list_element,
		);
	}

	/**
	 * Get product category meta tags
	 *
	 * Generates meta tags for product_cat taxonomy archives and shop page.
	 * Uses category description as meta description if available.
	 * Generates fallback description from "Products in [category name]" when empty.
	 *
	 * (Requirements 23.1, 23.2, 23.3, 23.4)
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID (used for context, not directly needed for category meta).
	 * @return array Meta tags array with title, description, robots, canonical.
	 */
	public function get_product_meta( int $product_id ): array {
		// Ensure WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$meta = array();

		// Check if we're on a product_cat taxonomy archive (Requirement 23.1)
		if ( is_tax( 'product_cat' ) ) {
			$term = get_queried_object();
			
			if ( ! $term || ! isset( $term->term_id ) ) {
				return array();
			}

			// Generate title for product category
			$meta['title'] = $term->name . ' - ' . get_bloginfo( 'name' );

			// Use category description as meta description if available (Requirement 23.3)
			if ( ! empty( $term->description ) ) {
				$meta['description'] = wp_strip_all_tags( $term->description );
			} else {
				// Generate description from "Products in [category name]" when empty (Requirement 23.4)
				$meta['description'] = sprintf(
					/* translators: %s is the category name */
					__( 'Products in %s', 'meowseo' ),
					$term->name
				);
			}

			// Set canonical to category link
			$meta['canonical'] = get_term_link( $term );

			// Default robots directives
			$meta['robots'] = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

			return $meta;
		}

		// Check if we're on the shop page (Requirement 23.2)
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			// Get shop page ID
			$shop_page_id = wc_get_page_id( 'shop' );
			
			if ( ! $shop_page_id ) {
				return array();
			}

			// Get shop page post
			$shop_page = get_post( $shop_page_id );
			
			if ( ! $shop_page ) {
				return array();
			}

			// Generate title for shop page
			$meta['title'] = $shop_page->post_title . ' - ' . get_bloginfo( 'name' );

			// Use shop page content as description if available
			if ( ! empty( $shop_page->post_excerpt ) ) {
				$meta['description'] = wp_strip_all_tags( $shop_page->post_excerpt );
			} else {
				// Generate fallback description
				$meta['description'] = sprintf(
					/* translators: %s is the site name */
					__( 'Shop - %s', 'meowseo' ),
					get_bloginfo( 'name' )
				);
			}

			// Set canonical to shop page link
			$meta['canonical'] = get_permalink( $shop_page_id );

			// Default robots directives
			$meta['robots'] = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

			return $meta;
		}

		return $meta;
	}
}

