<?php
/**
 * Breadcrumb Schema Node builder.
 *
 * Generates BreadcrumbList schema node from breadcrumb trails.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Helpers\Schema_Nodes;

use MeowSEO\Helpers\Abstract_Schema_Node;
use MeowSEO\Helpers\Breadcrumbs;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Breadcrumb Schema Node class.
 *
 * Generates BreadcrumbList schema node (Requirement 1.3, 8.10).
 *
 * @since 1.0.0
 */
class Breadcrumb_Node extends Abstract_Schema_Node {

	/**
	 * Generate BreadcrumbList schema node
	 *
	 * Generates BreadcrumbList schema with itemListElement array from breadcrumb trail.
	 * Requirement 1.3: THE Schema_Builder SHALL always include WebSite, Organization, WebPage, and BreadcrumbList nodes in the @graph
	 * Requirement 8.10: THE Schema_Builder SHALL call Breadcrumbs get_trail() method for BreadcrumbList node generation
	 *
	 * @since 1.0.0
	 * @return array BreadcrumbList schema node.
	 */
	public function generate(): array {
		// Get breadcrumb trail from context
		$breadcrumbs = $this->context['breadcrumbs'] ?? null;

		if ( ! $breadcrumbs instanceof Breadcrumbs ) {
			return array();
		}

		$trail = $breadcrumbs->get_trail();

		if ( empty( $trail ) ) {
			return array();
		}

		// Build itemListElement array with position property for each item
		$item_list_element = array();

		foreach ( $trail as $position => $item ) {
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

		$node = array(
			'@type'              => 'BreadcrumbList',
			'@id'                => $this->get_id_url( 'breadcrumb' ),
			'itemListElement'    => $item_list_element,
		);

		return $node;
	}

	/**
	 * Check if BreadcrumbList node is needed
	 *
	 * BreadcrumbList node is always included in the @graph (Requirement 1.3).
	 *
	 * @since 1.0.0
	 * @return bool Always returns true.
	 */
	public function is_needed(): bool {
		return true;
	}
}
