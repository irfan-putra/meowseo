<?php
/**
 * Video Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Video_Schema_Generator class
 *
 * Generates VideoObject schema markup for video content.
 */
class Video_Schema_Generator {
	/**
	 * Generate VideoObject schema
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
			'@type'        => 'VideoObject',
			'@id'          => $permalink . '#video',
			'name'         => $config['name'] ?? get_the_title( $post ),
			'description'  => $config['description'] ?? '',
			'thumbnailUrl' => $config['thumbnailUrl'],
			'uploadDate'   => $config['uploadDate'],
		);

		// Add optional duration field
		if ( ! empty( $config['duration'] ) ) {
			$schema['duration'] = $config['duration'];
		}

		// Add optional contentUrl field
		if ( ! empty( $config['contentUrl'] ) ) {
			$schema['contentUrl'] = $config['contentUrl'];
		}

		// Add optional embedUrl field
		if ( ! empty( $config['embedUrl'] ) ) {
			$schema['embedUrl'] = $config['embedUrl'];
		}

		return $schema;
	}

	/**
	 * Get required fields
	 *
	 * @return array Required field names.
	 */
	public function get_required_fields(): array {
		return array( 'name', 'description', 'thumbnailUrl', 'uploadDate' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array( 'duration', 'contentUrl', 'embedUrl' );
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
					sprintf( 'VideoObject schema missing required field: %s', $field )
				);
			}
		}

		return true;
	}
}
