<?php
/**
 * Location Property Test: Coordinate Validation Correctness
 *
 * **Validates: Requirements 4.3**
 *
 * Property: Coordinate validation correctly identifies valid and invalid coordinates
 * - Valid latitude: -90 to 90
 * - Valid longitude: -180 to 180
 * - Invalid coordinates outside these ranges should be rejected
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Modules\Locations\Location_Validator;

/**
 * Location Property Test: Coordinate Validation
 */
class LocationProperty01CoordinateValidationTest extends TestCase {
	use TestTrait;

	/**
	 * Test: Valid coordinates are always accepted
	 *
	 * Property: For any latitude in [-90, 90] and longitude in [-180, 180],
	 * validation should return empty errors array.
	 *
	 * @return void
	 */
	public function test_valid_coordinates_are_accepted(): void {
		$this->forAll(
			Generators::choose( -90, 90 ),
			Generators::choose( -180, 180 )
		)->then( function ( int $latitude, int $longitude ) {
			$validator = new Location_Validator();
			$errors = $validator->validate_coordinates( (float) $latitude, (float) $longitude );

			$this->assertEmpty(
				$errors,
				"Coordinates ($latitude, $longitude) should be valid but got errors: " . implode( ', ', $errors )
			);
		} );
	}

	/**
	 * Test: Invalid latitude is always rejected
	 *
	 * Property: For any latitude outside [-90, 90], validation should return
	 * an error containing "Latitude".
	 *
	 * @return void
	 */
	public function test_invalid_latitude_is_rejected(): void {
		$this->forAll(
			Generators::elements( array( -91, -100, -500, 91, 100, 500 ) ),
			Generators::choose( -180, 180 )
		)->then( function ( int $latitude, int $longitude ) {
			$validator = new Location_Validator();
			$errors = $validator->validate_coordinates( (float) $latitude, (float) $longitude );

			$this->assertNotEmpty(
				$errors,
				"Latitude $latitude should be invalid but validation passed"
			);

			$this->assertStringContainsString(
				'Latitude',
				$errors[0],
				"Error message should mention Latitude"
			);
		} );
	}

	/**
	 * Test: Invalid longitude is always rejected
	 *
	 * Property: For any longitude outside [-180, 180], validation should return
	 * an error containing "Longitude".
	 *
	 * @return void
	 */
	public function test_invalid_longitude_is_rejected(): void {
		$this->forAll(
			Generators::choose( -90, 90 ),
			Generators::elements( array( -181, -200, -500, 181, 200, 500 ) )
		)->then( function ( int $latitude, int $longitude ) {
			$validator = new Location_Validator();
			$errors = $validator->validate_coordinates( (float) $latitude, (float) $longitude );

			$this->assertNotEmpty(
				$errors,
				"Longitude $longitude should be invalid but validation passed"
			);

			$this->assertStringContainsString(
				'Longitude',
				$errors[0],
				"Error message should mention Longitude"
			);
		} );
	}

	/**
	 * Test: Boundary values are accepted
	 *
	 * Property: Exact boundary values (-90, 90, -180, 180) should be valid.
	 *
	 * @return void
	 */
	public function test_boundary_values_are_valid(): void {
		$validator = new Location_Validator();

		// Test all boundary combinations.
		$boundaries = array(
			array( -90, -180 ),
			array( -90, 180 ),
			array( 90, -180 ),
			array( 90, 180 ),
			array( 0, 0 ),
		);

		foreach ( $boundaries as $coords ) {
			$errors = $validator->validate_coordinates( (float) $coords[0], (float) $coords[1] );
			$this->assertEmpty(
				$errors,
				"Boundary coordinates ({$coords[0]}, {$coords[1]}) should be valid"
			);
		}
	}

	/**
	 * Test: Null coordinates are handled gracefully
	 *
	 * Property: Null coordinates should not produce validation errors
	 * (they are optional fields).
	 *
	 * @return void
	 */
	public function test_null_coordinates_are_valid(): void {
		$validator = new Location_Validator();

		// Both null.
		$errors = $validator->validate_coordinates( null, null );
		$this->assertEmpty( $errors, "Null coordinates should be valid" );

		// One null, one valid.
		$errors = $validator->validate_coordinates( 40.7128, null );
		$this->assertEmpty( $errors, "Null longitude with valid latitude should be valid" );

		$errors = $validator->validate_coordinates( null, -74.0060 );
		$this->assertEmpty( $errors, "Null latitude with valid longitude should be valid" );
	}

	/**
	 * Test: Precision is preserved
	 *
	 * Property: High-precision coordinates should be accepted and preserved.
	 *
	 * @return void
	 */
	public function test_high_precision_coordinates_are_accepted(): void {
		$validator = new Location_Validator();

		// Test with high precision values.
		$high_precision_coords = array(
			array( 40.7127837, -74.0059413 ),
			array( 51.5073509, -0.1277583 ),
			array( 35.6762, 139.6503 ),
		);

		foreach ( $high_precision_coords as $coords ) {
			$errors = $validator->validate_coordinates( $coords[0], $coords[1] );
			$this->assertEmpty(
				$errors,
				"High-precision coordinates ({$coords[0]}, {$coords[1]}) should be valid"
			);
		}
	}
}
