<?php
/**
 * Location Validator
 *
 * Validates location data including GPS coordinates.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Locations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Location_Validator class
 *
 * Validates location data including latitude (-90 to 90) and longitude (-180 to 180).
 * Requirements 4.3: Validate coordinates on post save.
 *
 * @since 1.0.0
 */
class Location_Validator {

	/**
	 * Validate coordinates
	 *
	 * Requirements 4.3: Validate latitude (-90 to 90) and longitude (-180 to 180).
	 *
	 * @since 1.0.0
	 * @param float|null $latitude Latitude value.
	 * @param float|null $longitude Longitude value.
	 * @return array Array of validation errors, empty if valid.
	 */
	public function validate_coordinates( ?float $latitude, ?float $longitude ): array {
		$errors = array();

		// Validate latitude.
		if ( $latitude !== null && ( $latitude < -90 || $latitude > 90 ) ) {
			$errors[] = __( 'Latitude must be between -90 and 90.', 'meowseo' );
		}

		// Validate longitude.
		if ( $longitude !== null && ( $longitude < -180 || $longitude > 180 ) ) {
			$errors[] = __( 'Longitude must be between -180 and 180.', 'meowseo' );
		}

		return $errors;
	}

	/**
	 * Validate location data
	 *
	 * @since 1.0.0
	 * @param array $location Location data.
	 * @return array Array of validation errors, empty if valid.
	 */
	public function validate_location( array $location ): array {
		$errors = array();

		// Validate coordinates if provided.
		$latitude = isset( $location['latitude'] ) ? floatval( $location['latitude'] ) : null;
		$longitude = isset( $location['longitude'] ) ? floatval( $location['longitude'] ) : null;

		$coordinate_errors = $this->validate_coordinates( $latitude, $longitude );
		$errors = array_merge( $errors, $coordinate_errors );

		return $errors;
	}
}
