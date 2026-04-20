<?php
/**
 * Location Schema Generator
 *
 * Generates LocalBusiness schema for location custom post type.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Locations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Location_Schema_Generator class
 *
 * Generates LocalBusiness schema with address, GPS coordinates, phone, email, and opening hours.
 * Requirements 4.4: Generate LocalBusiness schema for locations.
 *
 * @since 1.0.0
 */
class Location_Schema_Generator {

	/**
	 * Generate LocalBusiness schema for a location
	 *
	 * Requirements 4.4: Generate LocalBusiness schema with address, GPS coordinates, phone, email, opening hours.
	 *
	 * @since 1.0.0
	 * @param int $post_id Location post ID.
	 * @return array LocalBusiness schema object, or empty array if required fields missing.
	 */
	public function generate( int $post_id ): array {
		$location_cpt = new Location_CPT( new \MeowSEO\Options() );
		$location_data = $location_cpt->get_location_data( $post_id );

		// Business name is required.
		if ( empty( $location_data['business_name'] ) ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'LocalBusiness',
			'@id'      => esc_url( get_permalink( $post_id ) . '#localbusiness' ),
			'name'     => $location_data['business_name'],
			'url'      => esc_url( get_permalink( $post_id ) ),
		);

		// Add address if available.
		$address = $this->get_address_schema( $location_data );
		if ( ! empty( $address ) ) {
			$schema['address'] = $address;
		}

		// Add geo coordinates if available.
		$geo = $this->get_geo_schema( $location_data );
		if ( ! empty( $geo ) ) {
			$schema['geo'] = $geo;
		}

		// Add phone if available.
		if ( ! empty( $location_data['phone'] ) ) {
			$schema['telephone'] = sanitize_text_field( $location_data['phone'] );
		}

		// Add email if available.
		if ( ! empty( $location_data['email'] ) ) {
			$schema['email'] = sanitize_email( $location_data['email'] );
		}

		// Add opening hours if available.
		$opening_hours = $this->get_opening_hours_schema( $location_data );
		if ( ! empty( $opening_hours ) ) {
			$schema['openingHoursSpecification'] = $opening_hours;
		}

		return $schema;
	}

	/**
	 * Get address schema
	 *
	 * @since 1.0.0
	 * @param array $location_data Location data.
	 * @return array PostalAddress schema, or empty array if no address data.
	 */
	private function get_address_schema( array $location_data ): array {
		// At least one address field is required.
		if ( empty( $location_data['street_address'] ) && empty( $location_data['city'] ) ) {
			return array();
		}

		$address = array(
			'@type' => 'PostalAddress',
		);

		if ( ! empty( $location_data['street_address'] ) ) {
			$address['streetAddress'] = sanitize_text_field( $location_data['street_address'] );
		}

		if ( ! empty( $location_data['city'] ) ) {
			$address['addressLocality'] = sanitize_text_field( $location_data['city'] );
		}

		if ( ! empty( $location_data['state'] ) ) {
			$address['addressRegion'] = sanitize_text_field( $location_data['state'] );
		}

		if ( ! empty( $location_data['postal_code'] ) ) {
			$address['postalCode'] = sanitize_text_field( $location_data['postal_code'] );
		}

		if ( ! empty( $location_data['country'] ) ) {
			$address['addressCountry'] = sanitize_text_field( $location_data['country'] );
		}

		return $address;
	}

	/**
	 * Get geo coordinates schema
	 *
	 * @since 1.0.0
	 * @param array $location_data Location data.
	 * @return array GeoCoordinates schema, or empty array if no coordinates.
	 */
	private function get_geo_schema( array $location_data ): array {
		// Both latitude and longitude are required for geo schema.
		if ( empty( $location_data['latitude'] ) || empty( $location_data['longitude'] ) ) {
			return array();
		}

		return array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => floatval( $location_data['latitude'] ),
			'longitude' => floatval( $location_data['longitude'] ),
		);
	}

	/**
	 * Get opening hours schema
	 *
	 * @since 1.0.0
	 * @param array $location_data Location data.
	 * @return array Array of OpeningHoursSpecification objects, or empty array if no hours.
	 */
	private function get_opening_hours_schema( array $location_data ): array {
		if ( empty( $location_data['opening_hours'] ) || ! is_array( $location_data['opening_hours'] ) ) {
			return array();
		}

		$opening_hours_schema = array();

		foreach ( $location_data['opening_hours'] as $hours ) {
			if ( ! isset( $hours['day'] ) || ! isset( $hours['open'] ) || ! isset( $hours['close'] ) ) {
				continue;
			}

			$opening_hours_schema[] = array(
				'@type'      => 'OpeningHoursSpecification',
				'dayOfWeek'  => sanitize_text_field( $hours['day'] ),
				'opens'      => sanitize_text_field( $hours['open'] ),
				'closes'     => sanitize_text_field( $hours['close'] ),
			);
		}

		return $opening_hours_schema;
	}
}
