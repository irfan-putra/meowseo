<?php
/**
 * Event Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Event_Schema_Generator class
 *
 * Generates Event schema markup for concerts, webinars, meetups, and other events.
 */
class Event_Schema_Generator {
	/**
	 * Generate Event schema
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
			'@type'       => 'Event',
			'@id'         => $permalink . '#event',
			'name'        => $config['name'] ?? get_the_title( $post ),
			'startDate'   => $config['startDate'],
		);

		// Add description if provided
		if ( ! empty( $config['description'] ) ) {
			$schema['description'] = $config['description'];
		}

		// Add endDate if provided
		if ( ! empty( $config['endDate'] ) ) {
			$schema['endDate'] = $config['endDate'];
		}

		// Add eventStatus if provided
		if ( ! empty( $config['eventStatus'] ) ) {
			$schema['eventStatus'] = $config['eventStatus'];
		}

		// Add eventAttendanceMode if provided
		if ( ! empty( $config['eventAttendanceMode'] ) ) {
			$schema['eventAttendanceMode'] = $config['eventAttendanceMode'];
		}

		// Add location (required field)
		$schema['location'] = $this->format_location( $config['location'] );

		// Add organizer if provided
		if ( ! empty( $config['organizer'] ) && is_array( $config['organizer'] ) ) {
			$schema['organizer'] = $this->format_organizer( $config['organizer'] );
		}

		// Add offers if provided
		if ( ! empty( $config['offers'] ) && is_array( $config['offers'] ) ) {
			$schema['offers'] = $this->format_offers( $config['offers'] );
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
		return array( 'name', 'startDate', 'location' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array(
			'endDate',
			'description',
			'eventStatus',
			'eventAttendanceMode',
			'organizer',
			'offers',
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
					sprintf( 'Event schema missing required field: %s', $field )
				);
			}
		}

		// Validate location is an array or string
		if ( ! is_array( $config['location'] ) && ! is_string( $config['location'] ) ) {
			return new \WP_Error(
				'invalid_field_type',
				'location must be an array or string'
			);
		}

		return true;
	}

	/**
	 * Format location as Place object
	 *
	 * @param array|string $location Raw location data.
	 * @return array Formatted Place schema.
	 */
	private function format_location( $location ): array {
		// If location is a simple string, create a basic Place
		if ( is_string( $location ) ) {
			return array(
				'@type' => 'Place',
				'name'  => $location,
			);
		}

		$formatted = array(
			'@type' => 'Place',
		);

		// Add place name if provided
		if ( ! empty( $location['name'] ) ) {
			$formatted['name'] = $location['name'];
		}

		// Add address if provided
		if ( ! empty( $location['address'] ) && is_array( $location['address'] ) ) {
			$address = array(
				'@type' => 'PostalAddress',
			);

			$address_fields = array(
				'streetAddress',
				'addressLocality',
				'addressRegion',
				'postalCode',
				'addressCountry',
			);

			foreach ( $address_fields as $field ) {
				if ( ! empty( $location['address'][ $field ] ) ) {
					$address[ $field ] = $location['address'][ $field ];
				}
			}

			// Only add address if it has more than just @type
			if ( count( $address ) > 1 ) {
				$formatted['address'] = $address;
			}
		}

		return $formatted;
	}

	/**
	 * Format organizer as Organization object
	 *
	 * @param array $organizer Raw organizer data.
	 * @return array Formatted Organization schema.
	 */
	private function format_organizer( array $organizer ): array {
		$formatted = array(
			'@type' => 'Organization',
		);

		if ( ! empty( $organizer['name'] ) ) {
			$formatted['name'] = $organizer['name'];
		}

		if ( ! empty( $organizer['url'] ) ) {
			$formatted['url'] = $organizer['url'];
		}

		return $formatted;
	}

	/**
	 * Format offers as Offer object
	 *
	 * @param array $offers Raw offers data.
	 * @return array Formatted Offer schema.
	 */
	private function format_offers( array $offers ): array {
		$formatted = array(
			'@type' => 'Offer',
		);

		if ( ! empty( $offers['url'] ) ) {
			$formatted['url'] = $offers['url'];
		}

		if ( ! empty( $offers['price'] ) ) {
			$formatted['price'] = $offers['price'];
		}

		if ( ! empty( $offers['priceCurrency'] ) ) {
			$formatted['priceCurrency'] = $offers['priceCurrency'];
		}

		if ( ! empty( $offers['availability'] ) ) {
			$formatted['availability'] = $offers['availability'];
		}

		return $formatted;
	}
}
