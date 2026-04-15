<?php
/**
 * Organization Schema Node builder.
 *
 * Generates Organization schema node with logo ImageObject and social profiles.
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
 * Organization Schema Node class.
 *
 * Generates Organization schema node (Requirement 1.3, 1.9).
 *
 * @since 1.0.0
 */
class Organization_Node extends Abstract_Schema_Node {

	/**
	 * Generate Organization schema node
	 *
	 * Generates Organization schema with logo ImageObject and sameAs array (Requirement 1.9).
	 *
	 * @since 1.0.0
	 * @return array Organization schema node.
	 */
	public function generate(): array {
		$site_url = $this->get_site_url();
		
		// Get organization name from settings or fallback to site name.
		$org_name = $this->options->get( 'meowseo_schema_organization_name' );
		if ( empty( $org_name ) ) {
			$org_name = get_bloginfo( 'name' );
		}

		$node = array(
			'@type' => 'Organization',
			'@id'   => $this->get_site_id_url( 'organization' ),
			'name'  => $org_name,
			'url'   => $site_url,
		);

		// Add logo if configured.
		$logo_url = $this->options->get( 'meowseo_schema_organization_logo' );
		$logo_id  = $this->options->get( 'meowseo_schema_organization_logo_id' );
		
		if ( ! empty( $logo_url ) ) {
			$logo_node = array(
				'@type'      => 'ImageObject',
				'@id'        => $this->get_site_id_url( 'logo' ),
				'url'        => $logo_url,
				'contentUrl' => $logo_url,
			);

			// Add dimensions if logo_id is available.
			if ( ! empty( $logo_id ) ) {
				$image_meta = wp_get_attachment_metadata( $logo_id );
				if ( ! empty( $image_meta['width'] ) && ! empty( $image_meta['height'] ) ) {
					$logo_node['width']  = $image_meta['width'];
					$logo_node['height'] = $image_meta['height'];
				}
			}

			$node['logo']  = $logo_node;
			$node['image'] = array(
				'@id' => $this->get_site_id_url( 'logo' ),
			);
		}

		// Add social profiles if configured.
		$social_profiles = $this->options->get( 'meowseo_schema_social_profiles' );
		if ( ! empty( $social_profiles ) && is_array( $social_profiles ) ) {
			$same_as = array();
			foreach ( $social_profiles as $platform => $url ) {
				if ( ! empty( $url ) ) {
					$same_as[] = $url;
				}
			}
			
			if ( ! empty( $same_as ) ) {
				$node['sameAs'] = $same_as;
			}
		}

		return $node;
	}

	/**
	 * Check if Organization node is needed
	 *
	 * Organization node is always included in the @graph (Requirement 1.3).
	 *
	 * @since 1.0.0
	 * @return bool Always returns true.
	 */
	public function is_needed(): bool {
		return true;
	}
}

