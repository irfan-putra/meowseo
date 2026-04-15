<?php
/**
 * WebSite Schema Node builder.
 *
 * Generates WebSite schema node with SearchAction potentialAction.
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
 * WebSite Schema Node class.
 *
 * Generates WebSite schema node (Requirement 1.3, 1.8).
 *
 * @since 1.0.0
 */
class WebSite_Node extends Abstract_Schema_Node {

	/**
	 * Generate WebSite schema node
	 *
	 * Generates WebSite schema with SearchAction potentialAction (Requirement 1.8).
	 *
	 * @since 1.0.0
	 * @return array WebSite schema node.
	 */
	public function generate(): array {
		$site_url  = $this->get_site_url();
		$site_name = get_bloginfo( 'name' );
		$site_desc = get_bloginfo( 'description' );
		$language  = get_bloginfo( 'language' );

		$node = array(
			'@type'       => 'WebSite',
			'@id'         => $this->get_site_id_url( 'website' ),
			'url'         => $site_url,
			'name'        => $site_name,
			'description' => $site_desc,
			'publisher'   => array(
				'@id' => $this->get_site_id_url( 'organization' ),
			),
			'potentialAction' => array(
				array(
					'@type'  => 'SearchAction',
					'target' => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => trailingslashit( $site_url ) . '?s={search_term_string}',
					),
					'query-input' => array(
						'@type'         => 'PropertyValueSpecification',
						'valueRequired' => true,
						'valueName'     => 'search_term_string',
					),
				),
			),
			'inLanguage' => $language,
		);

		return $node;
	}

	/**
	 * Check if WebSite node is needed
	 *
	 * WebSite node is always included in the @graph (Requirement 1.3).
	 *
	 * @since 1.0.0
	 * @return bool Always returns true.
	 */
	public function is_needed(): bool {
		return true;
	}
}

