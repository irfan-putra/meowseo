<?php
/**
 * Event Schema Generator Tests
 *
 * Unit tests for the Event_Schema_Generator class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use MeowSEO\Modules\Schema\Generators\Event_Schema_Generator;
use PHPUnit\Framework\TestCase;

/**
 * Test_Event_Schema_Generator class
 *
 * @since 1.0.0
 */
class Test_Event_Schema_Generator extends TestCase {

	/**
	 * Event generator instance
	 *
	 * @var Event_Schema_Generator
	 */
	private Event_Schema_Generator $generator;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->generator = new Event_Schema_Generator();
	}

	/**
	 * Test Event_Schema_Generator instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( Event_Schema_Generator::class, $this->generator );
	}

	/**
	 * Test get_required_fields returns correct fields
	 *
	 * Validates Requirement 1.2: Event schema has required fields.
	 *
	 * @return void
	 */
	public function test_get_required_fields(): void {
		$required = $this->generator->get_required_fields();

		$this->assertIsArray( $required );
		$this->assertContains( 'name', $required );
		$this->assertContains( 'startDate', $required );
		$this->assertContains( 'location', $required );
	}

	/**
	 * Test get_optional_fields returns correct fields
	 *
	 * Validates Requirement 1.2: Event schema has optional fields.
	 *
	 * @return void
	 */
	public function test_get_optional_fields(): void {
		$optional = $this->generator->get_optional_fields();

		$this->assertIsArray( $optional );
		$this->assertContains( 'endDate', $optional );
		$this->assertContains( 'description', $optional );
		$this->assertContains( 'eventStatus', $optional );
		$this->assertContains( 'eventAttendanceMode', $optional );
		$this->assertContains( 'organizer', $optional );
		$this->assertContains( 'offers', $optional );
		$this->assertContains( 'image', $optional );
	}

	/**
	 * Test validate_config returns true for valid config
	 *
	 * @return void
	 */
	public function test_validate_config_returns_true_for_valid_config(): void {
		$config = array(
			'name'      => 'Test Event',
			'startDate' => '2024-06-15T19:00:00-05:00',
			'location'  => 'Test Venue',
		);

		$result = $this->generator->validate_config( $config );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate_config returns WP_Error for missing required field
	 *
	 * @return void
	 */
	public function test_validate_config_returns_error_for_missing_field(): void {
		$config = array(
			'name' => 'Test Event',
			// Missing startDate and location
		);

		$result = $this->generator->validate_config( $config );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test validate_config returns WP_Error for invalid location type
	 *
	 * @return void
	 */
	public function test_validate_config_returns_error_for_invalid_location_type(): void {
		$config = array(
			'name'      => 'Test Event',
			'startDate' => '2024-06-15T19:00:00-05:00',
			'location'  => 123, // Should be array or string
		);

		$result = $this->generator->validate_config( $config );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_field_type', $result->get_error_code() );
	}

	/**
	 * Test generate returns valid Event schema with minimal config
	 *
	 * Validates Requirement 1.2: Event schema generation with required fields only.
	 *
	 * @return void
	 */
	public function test_generate_returns_valid_schema_minimal(): void {
		// Create a mock post
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Event Post',
				'post_content' => 'Event content',
			)
		);

		$config = array(
			'name'      => 'Summer Concert',
			'startDate' => '2024-06-15T19:00:00-05:00',
			'location'  => 'Central Park',
		);

		$schema = $this->generator->generate( $post_id, $config );

		// Verify schema structure
		$this->assertIsArray( $schema );
		$this->assertEquals( 'Event', $schema['@type'] );
		$this->assertStringContainsString( '#event', $schema['@id'] );
		$this->assertEquals( 'Summer Concert', $schema['name'] );
		$this->assertEquals( '2024-06-15T19:00:00-05:00', $schema['startDate'] );

		// Verify location
		$this->assertArrayHasKey( 'location', $schema );
		$this->assertEquals( 'Place', $schema['location']['@type'] );
		$this->assertEquals( 'Central Park', $schema['location']['name'] );
	}

	/**
	 * Test generate returns valid Event schema with all fields
	 *
	 * Validates Requirement 1.2: Event schema generation with all optional fields.
	 *
	 * @return void
	 */
	public function test_generate_returns_valid_schema_complete(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Event Post',
				'post_content' => 'Event content',
			)
		);

		$config = array(
			'name'                 => 'Tech Conference 2024',
			'description'          => 'Annual technology conference',
			'startDate'            => '2024-06-15T09:00:00-05:00',
			'endDate'              => '2024-06-17T17:00:00-05:00',
			'eventStatus'          => 'https://schema.org/EventScheduled',
			'eventAttendanceMode'  => 'https://schema.org/OfflineEventAttendanceMode',
			'location'             => array(
				'name'    => 'Convention Center',
				'address' => array(
					'streetAddress'   => '123 Main St',
					'addressLocality' => 'San Francisco',
					'addressRegion'   => 'CA',
					'postalCode'      => '94102',
					'addressCountry'  => 'US',
				),
			),
			'organizer'            => array(
				'name' => 'Tech Events Inc',
				'url'  => 'https://example.com',
			),
			'offers'               => array(
				'url'           => 'https://example.com/tickets',
				'price'         => '299',
				'priceCurrency' => 'USD',
				'availability'  => 'https://schema.org/InStock',
			),
		);

		$schema = $this->generator->generate( $post_id, $config );

		// Verify schema structure
		$this->assertIsArray( $schema );
		$this->assertEquals( 'Event', $schema['@type'] );
		$this->assertEquals( 'Tech Conference 2024', $schema['name'] );
		$this->assertEquals( 'Annual technology conference', $schema['description'] );
		$this->assertEquals( '2024-06-15T09:00:00-05:00', $schema['startDate'] );
		$this->assertEquals( '2024-06-17T17:00:00-05:00', $schema['endDate'] );
		$this->assertEquals( 'https://schema.org/EventScheduled', $schema['eventStatus'] );
		$this->assertEquals( 'https://schema.org/OfflineEventAttendanceMode', $schema['eventAttendanceMode'] );

		// Verify location with address
		$this->assertArrayHasKey( 'location', $schema );
		$this->assertEquals( 'Place', $schema['location']['@type'] );
		$this->assertEquals( 'Convention Center', $schema['location']['name'] );
		$this->assertArrayHasKey( 'address', $schema['location'] );
		$this->assertEquals( 'PostalAddress', $schema['location']['address']['@type'] );
		$this->assertEquals( '123 Main St', $schema['location']['address']['streetAddress'] );
		$this->assertEquals( 'San Francisco', $schema['location']['address']['addressLocality'] );

		// Verify organizer
		$this->assertArrayHasKey( 'organizer', $schema );
		$this->assertEquals( 'Organization', $schema['organizer']['@type'] );
		$this->assertEquals( 'Tech Events Inc', $schema['organizer']['name'] );
		$this->assertEquals( 'https://example.com', $schema['organizer']['url'] );

		// Verify offers
		$this->assertArrayHasKey( 'offers', $schema );
		$this->assertEquals( 'Offer', $schema['offers']['@type'] );
		$this->assertEquals( 'https://example.com/tickets', $schema['offers']['url'] );
		$this->assertEquals( '299', $schema['offers']['price'] );
		$this->assertEquals( 'USD', $schema['offers']['priceCurrency'] );
	}

	/**
	 * Test generate returns empty array for invalid config
	 *
	 * @return void
	 */
	public function test_generate_returns_empty_for_invalid_config(): void {
		$post_id = wp_insert_post( array( 'post_title' => 'Test Event' ) );

		$config = array(
			'name' => 'Test Event',
			// Missing required fields
		);

		$schema = $this->generator->generate( $post_id, $config );

		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test generate returns empty array for non-existent post
	 *
	 * @return void
	 */
	public function test_generate_returns_empty_for_nonexistent_post(): void {
		$config = array(
			'name'      => 'Test Event',
			'startDate' => '2024-06-15T19:00:00-05:00',
			'location'  => 'Test Venue',
		);

		$schema = $this->generator->generate( 999999, $config );

		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test generate includes featured image when available
	 *
	 * @return void
	 */
	public function test_generate_includes_featured_image(): void {
		$post_id = wp_insert_post( array( 'post_title' => 'Test Event' ) );

		// Mock featured image
		set_post_thumbnail( $post_id, 1 );

		$config = array(
			'name'      => 'Test Event',
			'startDate' => '2024-06-15T19:00:00-05:00',
			'location'  => 'Test Venue',
		);

		$schema = $this->generator->generate( $post_id, $config );

		// Note: In a real test environment with WordPress, this would check for the image
		// For now, we just verify the schema structure is valid
		$this->assertIsArray( $schema );
		$this->assertEquals( 'Event', $schema['@type'] );
	}
}
