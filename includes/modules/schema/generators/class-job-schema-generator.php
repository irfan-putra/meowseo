<?php
/**
 * Job Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Job_Schema_Generator class
 *
 * Generates JobPosting schema markup for job listings.
 */
class Job_Schema_Generator {
	/**
	 * Generate JobPosting schema
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
			'@type'              => 'JobPosting',
			'@id'                => $permalink . '#jobposting',
			'title'              => $config['title'] ?? get_the_title( $post ),
			'description'        => $config['description'] ?? '',
			'datePosted'         => $config['datePosted'] ?? get_the_date( 'c', $post ),
			'hiringOrganization' => $this->format_hiring_organization( $config['hiringOrganization'] ),
		);

		// Add validThrough if provided
		if ( ! empty( $config['validThrough'] ) ) {
			$schema['validThrough'] = $config['validThrough'];
		}

		// Add employmentType if provided
		if ( ! empty( $config['employmentType'] ) ) {
			$schema['employmentType'] = $config['employmentType'];
		}

		// Add jobLocation if provided
		if ( ! empty( $config['jobLocation'] ) && is_array( $config['jobLocation'] ) ) {
			$schema['jobLocation'] = $this->format_job_location( $config['jobLocation'] );
		}

		// Add baseSalary if provided
		if ( ! empty( $config['baseSalary'] ) && is_array( $config['baseSalary'] ) ) {
			$schema['baseSalary'] = $this->format_base_salary( $config['baseSalary'] );
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
		return array( 'title', 'description', 'datePosted', 'hiringOrganization' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array(
			'validThrough',
			'employmentType',
			'jobLocation',
			'baseSalary',
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
					sprintf( 'JobPosting schema missing required field: %s', $field )
				);
			}
		}

		// Validate hiringOrganization is an array or string
		if ( ! is_array( $config['hiringOrganization'] ) && ! is_string( $config['hiringOrganization'] ) ) {
			return new \WP_Error(
				'invalid_field_type',
				'hiringOrganization must be an array or string'
			);
		}

		return true;
	}

	/**
	 * Format hiring organization as Organization object
	 *
	 * @param array|string $organization Raw organization data.
	 * @return array Formatted Organization schema.
	 */
	private function format_hiring_organization( $organization ): array {
		// If organization is a simple string, create a basic Organization
		if ( is_string( $organization ) ) {
			return array(
				'@type' => 'Organization',
				'name'  => $organization,
			);
		}

		$formatted = array(
			'@type' => 'Organization',
		);

		if ( ! empty( $organization['name'] ) ) {
			$formatted['name'] = $organization['name'];
		}

		if ( ! empty( $organization['sameAs'] ) ) {
			$formatted['sameAs'] = $organization['sameAs'];
		}

		if ( ! empty( $organization['url'] ) ) {
			$formatted['url'] = $organization['url'];
		}

		return $formatted;
	}

	/**
	 * Format job location as Place object
	 *
	 * @param array $location Raw location data.
	 * @return array Formatted Place schema.
	 */
	private function format_job_location( array $location ): array {
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
	 * Format base salary as MonetaryAmount object
	 *
	 * @param array $salary Raw salary data.
	 * @return array Formatted MonetaryAmount schema.
	 */
	private function format_base_salary( array $salary ): array {
		$formatted = array(
			'@type'    => 'MonetaryAmount',
			'currency' => $salary['currency'] ?? 'USD',
		);

		if ( ! empty( $salary['value'] ) ) {
			$formatted['value'] = array(
				'@type'    => 'QuantitativeValue',
				'value'    => $salary['value'],
				'unitText' => $salary['unitText'] ?? 'YEAR',
			);
		}

		return $formatted;
	}
}
