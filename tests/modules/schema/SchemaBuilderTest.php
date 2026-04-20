<?php
/**
 * Schema Builder Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Schema;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Schema_Builder;
use MeowSEO\Options;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test Schema Builder functionality
 *
 * @since 1.0.0
 */
class SchemaBuilderTest extends TestCase {

	/**
	 * Schema Builder instance
	 *
	 * @var Schema_Builder
	 */
	private Schema_Builder $schema_builder;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Skip if WordPress functions are already defined (can't mock with Patchwork).
		if ( function_exists( 'get_site_url' ) ) {
			$this->markTestSkipped( 'WordPress functions already defined. These tests require Brain\Monkey mocking which cannot override existing functions.' );
		}
		
		Monkey\setUp();

		// Mock WordPress functions.
		Functions\when( 'get_site_url' )->justReturn( 'https://example.com' );
		Functions\when( 'get_bloginfo' )->alias( function ( $key ) {
			$values = array(
				'name'        => 'Test Site',
				'description' => 'Test Description',
				'language'    => 'en-US',
			);
			return $values[ $key ] ?? '';
		} );

		$this->options = $this->createMock( Options::class );
		$this->schema_builder = new Schema_Builder( $this->options );
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test build_website returns correct structure
	 *
	 * @since 1.0.0
	 */
	public function test_build_website_returns_correct_structure() {
		$schema = $this->schema_builder->build_website();

		$this->assertIsArray( $schema );
		$this->assertEquals( 'WebSite', $schema['@type'] );
		$this->assertEquals( 'https://example.com/#website', $schema['@id'] );
		$this->assertEquals( 'https://example.com', $schema['url'] );
		$this->assertEquals( 'Test Site', $schema['name'] );
		$this->assertArrayHasKey( 'potentialAction', $schema );
	}

	/**
	 * Test build_organization returns correct structure
	 *
	 * @since 1.0.0
	 */
	public function test_build_organization_returns_correct_structure() {
		Functions\when( 'get_theme_mod' )->justReturn( false );

		$schema = $this->schema_builder->build_organization();

		$this->assertIsArray( $schema );
		$this->assertEquals( 'Organization', $schema['@type'] );
		$this->assertEquals( 'https://example.com/#organization', $schema['@id'] );
		$this->assertEquals( 'Test Site', $schema['name'] );
		$this->assertEquals( 'https://example.com', $schema['url'] );
	}

	/**
	 * Test build_faq returns correct structure
	 *
	 * @since 1.0.0
	 */
	public function test_build_faq_returns_correct_structure() {
		$items = array(
			array(
				'question' => 'What is this?',
				'answer'   => 'This is a test.',
			),
			array(
				'question' => 'How does it work?',
				'answer'   => 'It works great.',
			),
		);

		$schema = $this->schema_builder->build_faq( $items );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'FAQPage', $schema['@type'] );
		$this->assertArrayHasKey( 'mainEntity', $schema );
		$this->assertCount( 2, $schema['mainEntity'] );
		$this->assertEquals( 'Question', $schema['mainEntity'][0]['@type'] );
		$this->assertEquals( 'What is this?', $schema['mainEntity'][0]['name'] );
	}

	/**
	 * Test build_faq returns empty array for empty items
	 *
	 * @since 1.0.0
	 */
	public function test_build_faq_returns_empty_for_empty_items() {
		$schema = $this->schema_builder->build_faq( array() );

		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test to_json returns valid JSON
	 *
	 * @since 1.0.0
	 */
	public function test_to_json_returns_valid_json() {
		$graph = array(
			'@context' => 'https://schema.org',
			'@type'    => 'WebSite',
			'name'     => 'Test Site',
		);

		$json = $this->schema_builder->to_json( $graph );

		$this->assertIsString( $json );
		$this->assertNotEmpty( $json );

		$decoded = json_decode( $json, true );
		$this->assertIsArray( $decoded );
		$this->assertEquals( 'https://schema.org', $decoded['@context'] );
	}
}
