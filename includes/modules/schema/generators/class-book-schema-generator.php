<?php
/**
 * Book Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Book_Schema_Generator class
 *
 * Generates Book schema markup for book content.
 */
class Book_Schema_Generator {
	/**
	 * Generate Book schema
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
			'@type'  => 'Book',
			'@id'    => $permalink . '#book',
			'name'   => $config['name'] ?? get_the_title( $post ),
			'author' => $this->format_author( $config['author'] ),
		);

		// Add isbn if provided
		if ( ! empty( $config['isbn'] ) ) {
			$schema['isbn'] = $config['isbn'];
		}

		// Add numberOfPages if provided
		if ( ! empty( $config['numberOfPages'] ) ) {
			$schema['numberOfPages'] = (int) $config['numberOfPages'];
		}

		// Add publisher if provided
		if ( ! empty( $config['publisher'] ) ) {
			$schema['publisher'] = $this->format_publisher( $config['publisher'] );
		}

		// Add datePublished if provided
		if ( ! empty( $config['datePublished'] ) ) {
			$schema['datePublished'] = $config['datePublished'];
		}

		// Add bookFormat if provided
		if ( ! empty( $config['bookFormat'] ) ) {
			$schema['bookFormat'] = $config['bookFormat'];
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

		// Add description if provided
		if ( ! empty( $config['description'] ) ) {
			$schema['description'] = $config['description'];
		}

		return $schema;
	}

	/**
	 * Get required fields
	 *
	 * @return array Required field names.
	 */
	public function get_required_fields(): array {
		return array( 'name', 'author' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array(
			'isbn',
			'numberOfPages',
			'publisher',
			'datePublished',
			'bookFormat',
			'image',
			'description',
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
					sprintf( 'Book schema missing required field: %s', $field )
				);
			}
		}

		// Validate author is an array or string
		if ( ! is_array( $config['author'] ) && ! is_string( $config['author'] ) ) {
			return new \WP_Error(
				'invalid_field_type',
				'author must be an array or string'
			);
		}

		return true;
	}

	/**
	 * Format author as Person object
	 *
	 * @param array|string $author Raw author data.
	 * @return array Formatted Person schema.
	 */
	private function format_author( $author ): array {
		// If author is a simple string, create a basic Person
		if ( is_string( $author ) ) {
			return array(
				'@type' => 'Person',
				'name'  => $author,
			);
		}

		$formatted = array(
			'@type' => 'Person',
		);

		if ( ! empty( $author['name'] ) ) {
			$formatted['name'] = $author['name'];
		}

		if ( ! empty( $author['url'] ) ) {
			$formatted['url'] = $author['url'];
		}

		if ( ! empty( $author['sameAs'] ) ) {
			$formatted['sameAs'] = $author['sameAs'];
		}

		return $formatted;
	}

	/**
	 * Format publisher as Organization object
	 *
	 * @param array|string $publisher Raw publisher data.
	 * @return array Formatted Organization schema.
	 */
	private function format_publisher( $publisher ): array {
		// If publisher is a simple string, create a basic Organization
		if ( is_string( $publisher ) ) {
			return array(
				'@type' => 'Organization',
				'name'  => $publisher,
			);
		}

		$formatted = array(
			'@type' => 'Organization',
		);

		if ( ! empty( $publisher['name'] ) ) {
			$formatted['name'] = $publisher['name'];
		}

		if ( ! empty( $publisher['url'] ) ) {
			$formatted['url'] = $publisher['url'];
		}

		return $formatted;
	}
}
