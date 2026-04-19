<?php
/**
 * Person Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Person_Schema_Generator class
 *
 * Generates Person schema markup for author bios and about pages.
 */
class Person_Schema_Generator {
	/**
	 * Generate Person schema
	 *
	 * @param int   $post_id Post ID.
	 * @param array $config  Schema configuration.
	 * @return array Schema data.
	 */
	public function generate( int $post_id, array $config ): array {
		$validation = $this->validate_config( $config );
		if ( is_wp_error( $validation ) ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$permalink = get_permalink( $post );

		$schema = array(
			'@type' => 'Person',
			'@id'   => $permalink . '#person',
			'name'  => $config['name'] ?? get_the_title( $post ),
		);

		// Add jobTitle if provided
		if ( ! empty( $config['jobTitle'] ) ) {
			$schema['jobTitle'] = $config['jobTitle'];
		}

		// Add description if provided
		if ( ! empty( $config['description'] ) ) {
			$schema['description'] = $config['description'];
		}

		// Add image if available
		if ( ! empty( $config['image'] ) ) {
			$schema['image'] = $config['image'];
		} elseif ( has_post_thumbnail( $post ) ) {
			$image_url = get_the_post_thumbnail_url( $post, 'full' );
			if ( $image_url ) {
				$schema['image'] = $image_url;
			}
		}

		// Add url if provided
		if ( ! empty( $config['url'] ) ) {
			$schema['url'] = $config['url'];
		}

		// Add sameAs if provided
		if ( ! empty( $config['sameAs'] ) ) {
			// Ensure sameAs is an array
			if ( is_string( $config['sameAs'] ) ) {
				$schema['sameAs'] = array( $config['sameAs'] );
			} elseif ( is_array( $config['sameAs'] ) ) {
				$schema['sameAs'] = array_values( $config['sameAs'] );
			}
		}

		return $schema;
	}

	/**
	 * Get required fields
	 *
	 * @return array Required field names.
	 */
	public function get_required_fields(): array {
		return array( 'name' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array(
			'jobTitle',
			'description',
			'image',
			'url',
			'sameAs',
		);
	}

	/**
	 * Validate configuration
	 *
	 * @param array $config Schema configuration.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_config( array $config ) {
		$required_fields = $this->get_required_fields();

		foreach ( $required_fields as $field ) {
			if ( empty( $config[ $field ] ) ) {
				return new \WP_Error(
					'missing_required_field',
					sprintf( 'Person schema missing required field: %s', $field )
				);
			}
		}

		return true;
	}
}
