<?php
/**
 * Location CPT Module Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Locations;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Locations\Location_CPT;
use MeowSEO\Modules\Locations\Location_Validator;
use MeowSEO\Modules\Locations\Location_Schema_Generator;
use MeowSEO\Options;

/**
 * Location CPT module test case
 */
class LocationCPTTest extends TestCase {

	/**
	 * Test Location_Validator validates correct coordinates
	 */
	public function test_validator_accepts_valid_coordinates(): void {
		$validator = new Location_Validator();

		// Valid coordinates should return empty errors array.
		$errors = $validator->validate_coordinates( 40.7128, -74.0060 );
		$this->assertEmpty( $errors );

		// Edge cases: min and max values.
		$errors = $validator->validate_coordinates( -90, -180 );
		$this->assertEmpty( $errors );

		$errors = $validator->validate_coordinates( 90, 180 );
		$this->assertEmpty( $errors );

		// Zero coordinates.
		$errors = $validator->validate_coordinates( 0, 0 );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test Location_Validator rejects invalid latitude
	 */
	public function test_validator_rejects_invalid_latitude(): void {
		$validator = new Location_Validator();

		// Latitude too low.
		$errors = $validator->validate_coordinates( -91, 0 );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'Latitude', $errors[0] );

		// Latitude too high.
		$errors = $validator->validate_coordinates( 91, 0 );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'Latitude', $errors[0] );
	}

	/**
	 * Test Location_Validator rejects invalid longitude
	 */
	public function test_validator_rejects_invalid_longitude(): void {
		$validator = new Location_Validator();

		// Longitude too low.
		$errors = $validator->validate_coordinates( 0, -181 );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'Longitude', $errors[0] );

		// Longitude too high.
		$errors = $validator->validate_coordinates( 0, 181 );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'Longitude', $errors[0] );
	}

	/**
	 * Test Location_Validator handles null coordinates
	 */
	public function test_validator_handles_null_coordinates(): void {
		$validator = new Location_Validator();

		// Null coordinates should be valid (optional fields).
		$errors = $validator->validate_coordinates( null, null );
		$this->assertEmpty( $errors );

		// One null, one valid.
		$errors = $validator->validate_coordinates( 40.7128, null );
		$this->assertEmpty( $errors );

		$errors = $validator->validate_coordinates( null, -74.0060 );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test Location_CPT boots without errors
	 */
	public function test_location_cpt_boots(): void {
		$options = $this->createMock( Options::class );
		$location_cpt = new Location_CPT( $options );

		// Boot should not throw any exceptions.
		$this->expectNotToPerformAssertions();
		$location_cpt->boot();
	}

	/**
	 * Test Location_Schema_Generator generates schema with required fields
	 */
	public function test_schema_generator_requires_business_name(): void {
		$generator = new Location_Schema_Generator();

		// Mock post without business name should return empty array.
		$schema = $generator->generate( 999 );
		$this->assertEmpty( $schema );
	}
}
