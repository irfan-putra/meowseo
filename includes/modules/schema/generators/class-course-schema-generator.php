<?php
/**
 * Course Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Course_Schema_Generator class
 *
 * Generates Course schema markup for educational content.
 */
class Course_Schema_Generator {
	/**
	 * Generate Course schema
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
			'@type'       => 'Course',
			'@id'         => $permalink . '#course',
			'name'        => $config['name'] ?? get_the_title( $post ),
			'description' => $config['description'] ?? '',
			'provider'    => $this->format_provider( $config['provider'] ),
		);

		// Add courseCode if provided
		if ( ! empty( $config['courseCode'] ) ) {
			$schema['courseCode'] = $config['courseCode'];
		}

		// Add hasCourseInstance if provided
		if ( ! empty( $config['hasCourseInstance'] ) && is_array( $config['hasCourseInstance'] ) ) {
			$schema['hasCourseInstance'] = $this->format_course_instance( $config['hasCourseInstance'] );
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

		return $schema;
	}

	/**
	 * Get required fields
	 *
	 * @return array Required field names.
	 */
	public function get_required_fields(): array {
		return array( 'name', 'description', 'provider' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array(
			'courseCode',
			'hasCourseInstance',
			'image',
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
					sprintf( 'Course schema missing required field: %s', $field )
				);
			}
		}

		// Validate provider is an array or string
		if ( ! is_array( $config['provider'] ) && ! is_string( $config['provider'] ) ) {
			return new \WP_Error(
				'invalid_field_type',
				'provider must be an array or string'
			);
		}

		return true;
	}

	/**
	 * Format provider as Organization object
	 *
	 * @param array|string $provider Raw provider data.
	 * @return array Formatted Organization schema.
	 */
	private function format_provider( $provider ): array {
		// If provider is a simple string, create a basic Organization
		if ( is_string( $provider ) ) {
			return array(
				'@type' => 'Organization',
				'name'  => $provider,
			);
		}

		$formatted = array(
			'@type' => 'Organization',
		);

		if ( ! empty( $provider['name'] ) ) {
			$formatted['name'] = $provider['name'];
		}

		if ( ! empty( $provider['sameAs'] ) ) {
			$formatted['sameAs'] = $provider['sameAs'];
		}

		if ( ! empty( $provider['url'] ) ) {
			$formatted['url'] = $provider['url'];
		}

		return $formatted;
	}

	/**
	 * Format course instance as CourseInstance object
	 *
	 * @param array $instance Raw course instance data.
	 * @return array Formatted CourseInstance schema.
	 */
	private function format_course_instance( array $instance ): array {
		$formatted = array(
			'@type' => 'CourseInstance',
		);

		if ( ! empty( $instance['courseMode'] ) ) {
			$formatted['courseMode'] = $instance['courseMode'];
		}

		if ( ! empty( $instance['courseWorkload'] ) ) {
			$formatted['courseWorkload'] = $instance['courseWorkload'];
		}

		if ( ! empty( $instance['instructor'] ) ) {
			$formatted['instructor'] = $instance['instructor'];
		}

		return $formatted;
	}
}
