<?php
/**
 * Product Schema Node builder.
 *
 * Generates Product schema node for WooCommerce products with offers and aggregateRating.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Helpers\Schema_Nodes;

use MeowSEO\Helpers\Abstract_Schema_Node;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Schema Node class.
 *
 * Generates Product schema node for WooCommerce products (Requirements 1.5, 11.1, 11.2, 11.3).
 *
 * @since 1.0.0
 */
class Product_Node extends Abstract_Schema_Node {

	/**
	 * Generate Product schema node
	 *
	 * Generates Product schema with name, url, description, sku, image, offers, and aggregateRating.
	 *
	 * @since 1.0.0
	 * @return array Product schema node.
	 */
	public function generate(): array {
		// Ensure WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		// Get WooCommerce product object.
		$product = wc_get_product( $this->post_id );
		if ( ! $product ) {
			return array();
		}

		$permalink = get_permalink( $this->post );

		$node = array(
			'@type' => 'Product',
			'@id'   => $this->get_id_url( 'product' ),
			'name'  => $product->get_name(),
			'url'   => $permalink,
		);

		// Add description (Requirement 11.2).
		$description = $product->get_description();
		if ( empty( $description ) ) {
			$description = $product->get_short_description();
		}
		if ( ! empty( $description ) ) {
			$node['description'] = wp_strip_all_tags( $description );
		}

		// Add SKU (Requirement 11.2).
		$sku = $product->get_sku();
		if ( ! empty( $sku ) ) {
			$node['sku'] = $sku;
		}

		// Add image (Requirement 11.2).
		$image_id = $product->get_image_id();
		if ( ! empty( $image_id ) ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );
			if ( ! empty( $image_url ) ) {
				$node['image'] = array(
					'@id' => $this->get_id_url( 'primaryimage' ),
				);
			}
		}

		// Add offers (Requirement 11.2, 11.3).
		$offers = $this->build_offers( $product, $permalink );
		if ( ! empty( $offers ) ) {
			$node['offers'] = $offers;
		}

		// Add aggregateRating when reviews exist (Requirement 11.4).
		$rating = $this->build_aggregate_rating( $product );
		if ( ! empty( $rating ) ) {
			$node['aggregateRating'] = $rating;
		}

		return $node;
	}

	/**
	 * Build offers property
	 *
	 * Generates Offer with price, priceCurrency, and availability (Requirement 11.3).
	 *
	 * @since 1.0.0
	 * @param \WC_Product $product   WooCommerce product object.
	 * @param string      $permalink Product permalink.
	 * @return array Offer schema.
	 */
	private function build_offers( \WC_Product $product, string $permalink ): array {
		$price = $product->get_price();
		
		// Return empty if no price is set.
		if ( empty( $price ) ) {
			return array();
		}

		$offers = array(
			'@type'         => 'Offer',
			'url'           => $permalink,
			'priceCurrency' => get_woocommerce_currency(),
			'price'         => $price,
		);

		// Add availability (Requirement 11.3).
		$availability = $this->get_availability_schema( $product );
		if ( ! empty( $availability ) ) {
			$offers['availability'] = $availability;
		}

		// Add priceValidUntil if product is on sale.
		if ( $product->is_on_sale() ) {
			$sale_end_date = $product->get_date_on_sale_to();
			if ( ! empty( $sale_end_date ) ) {
				$offers['priceValidUntil'] = $sale_end_date->format( 'Y-m-d' );
			}
		}

		return $offers;
	}

	/**
	 * Get availability schema URL
	 *
	 * Maps WooCommerce stock status to Schema.org availability values.
	 *
	 * @since 1.0.0
	 * @param \WC_Product $product WooCommerce product object.
	 * @return string Schema.org availability URL.
	 */
	private function get_availability_schema( \WC_Product $product ): string {
		if ( ! $product->is_in_stock() ) {
			return 'https://schema.org/OutOfStock';
		}

		// Check if product is on backorder.
		if ( $product->is_on_backorder() ) {
			return 'https://schema.org/BackOrder';
		}

		// Check stock quantity for limited availability.
		if ( $product->managing_stock() ) {
			$stock_quantity = $product->get_stock_quantity();
			if ( $stock_quantity !== null && $stock_quantity <= 5 ) {
				return 'https://schema.org/LimitedAvailability';
			}
		}

		return 'https://schema.org/InStock';
	}

	/**
	 * Build aggregateRating property
	 *
	 * Generates aggregateRating when product has reviews (Requirement 11.4).
	 *
	 * @since 1.0.0
	 * @param \WC_Product $product WooCommerce product object.
	 * @return array AggregateRating schema or empty array.
	 */
	private function build_aggregate_rating( \WC_Product $product ): array {
		$review_count = $product->get_review_count();
		
		// Return empty if no reviews.
		if ( empty( $review_count ) || $review_count < 1 ) {
			return array();
		}

		$average_rating = $product->get_average_rating();
		
		// Return empty if no rating.
		if ( empty( $average_rating ) ) {
			return array();
		}

		return array(
			'@type'       => 'AggregateRating',
			'ratingValue' => $average_rating,
			'reviewCount' => $review_count,
		);
	}

	/**
	 * Check if Product node is needed
	 *
	 * Product node is included when post_type is "product" AND WooCommerce is active (Requirement 1.5, 11.1).
	 *
	 * @since 1.0.0
	 * @return bool True if Product node should be included, false otherwise.
	 */
	public function is_needed(): bool {
		// Check if WooCommerce is active (Requirement 11.1).
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		// Check if post type is "product" (Requirement 1.5, 11.1).
		return 'product' === $this->post->post_type;
	}
}
