<?php
/**
 * WebPage Schema Node builder.
 *
 * Generates WebPage schema node with context detection for different page types.
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
 * WebPage Schema Node class.
 *
 * Generates WebPage schema node with context detection (Requirement 1.3, 1.10).
 *
 * @since 1.0.0
 */
class WebPage_Node extends Abstract_Schema_Node {

	/**
	 * Generate WebPage schema node
	 *
	 * Generates WebPage schema with context-specific @type (Requirement 1.10).
	 *
	 * @since 1.0.0
	 * @return array WebPage schema node.
	 */
	public function generate(): array {
		$page_type = $this->get_page_type();
		$permalink = get_permalink( $this->post );
		$language  = get_bloginfo( 'language' );

		$node = array(
			'@type'      => $page_type,
			'@id'        => $this->get_id_url( 'webpage' ),
			'url'        => $permalink,
			'name'       => get_the_title( $this->post ),
			'isPartOf'   => array(
				'@id' => $this->get_site_id_url( 'website' ),
			),
			'breadcrumb' => array(
				'@id' => $this->get_id_url( 'breadcrumb' ),
			),
			'inLanguage' => $language,
		);

		// Add primary image if available.
		if ( has_post_thumbnail( $this->post ) ) {
			$node['primaryImageOfPage'] = array(
				'@id' => $this->get_id_url( 'primaryimage' ),
			);
		}

		// Add dates for published content.
		if ( 'publish' === $this->post->post_status ) {
			$node['datePublished'] = $this->format_date( $this->post->post_date_gmt );
			$node['dateModified']  = $this->format_date( $this->post->post_modified_gmt );
		}

		// Add description if available.
		$description = get_the_excerpt( $this->post );
		if ( ! empty( $description ) ) {
			$node['description'] = $description;
		}

		return $node;
	}

	/**
	 * Get page type based on context
	 *
	 * Detects the appropriate WebPage @type based on WordPress context (Requirement 1.10).
	 *
	 * @since 1.0.0
	 * @return string Page type (WebPage, CollectionPage, or SearchResultsPage).
	 */
	private function get_page_type(): string {
		// Check if we're on the front page.
		if ( is_front_page() ) {
			return 'WebPage';
		}

		// Check if we're on an archive page.
		if ( is_archive() || is_home() ) {
			return 'CollectionPage';
		}

		// Check if we're on a search results page.
		if ( is_search() ) {
			return 'SearchResultsPage';
		}

		// Default to WebPage for single posts and pages.
		return 'WebPage';
	}

	/**
	 * Check if WebPage node is needed
	 *
	 * WebPage node is always included in the @graph (Requirement 1.3).
	 *
	 * @since 1.0.0
	 * @return bool Always returns true.
	 */
	public function is_needed(): bool {
		return true;
	}
}

