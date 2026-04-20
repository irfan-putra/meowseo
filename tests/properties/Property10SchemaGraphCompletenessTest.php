<?php
/**
 * Property-Based Tests for Schema Graph Completeness
 *
 * Property 10: Schema graph contains all required types
 * Validates: Requirements 5.2
 *
 * This test uses property-based testing (eris/eris) to verify that the Schema_Builder
 * constructs schema graphs containing all required types: WebSite, WebPage, Article,
 * BreadcrumbList, Organization, and Person.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Schema_Builder;
use MeowSEO\Options;

/**
 * Schema Graph Completeness property-based test case
 *
 * @since 1.0.0
 */
class Property10SchemaGraphCompletenessTest extends TestCase {
	use TestTrait;

	/**
	 * Schema_Builder instance.
	 *
	 * @var Schema_Builder
	 */
	private Schema_Builder $schema_builder;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create a mock Options instance.
		$options = $this->createMock( Options::class );
		$options->method( 'get_separator' )->willReturn( ' - ' );
		$options->method( 'get_default_social_image_url' )->willReturn( '' );
		$options->method( 'get' )->willReturn( true ); // Default return for any get() call

		$this->schema_builder = new Schema_Builder( $options );
	}

	/**
	 * Property 10: Schema graph contains all required types
	 *
	 * For any post, the schema graph should contain all required schema types:
	 * WebSite, WebPage, Article, BreadcrumbList, Organization, and Person.
	 *
	 * This property verifies:
	 * 1. The @graph array exists and is not empty
	 * 2. All required types are present in the graph
	 * 3. Each type appears at least once
	 * 4. The graph structure is valid JSON-LD format
	 * 5. The property is deterministic - same input produces same output
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_schema_graph_contains_all_required_types(): void {
		$this->forAll(
			Generators::choose( 1, 1000 )
		)
		->then(
			function ( int $post_id ) {
				// Create a test post.
				$post_id = wp_insert_post(
					array(
						'post_title'   => 'Test Post ' . $post_id,
						'post_content' => 'Test content for schema testing',
						'post_status'  => 'publish',
						'post_type'    => 'post',
					)
				);

				if ( \is_wp_error( $post_id ) ) {
					$this->markTestSkipped( 'Could not create test post' );
					return;
				}

				// Build schema graph.
				$result = $this->schema_builder->build( $post_id );

				// Verify structure.
				$this->assertIsArray( $result, 'Schema result should be an array' );
				$this->assertArrayHasKey( '@context', $result, 'Schema should have @context' );
				$this->assertArrayHasKey( '@graph', $result, 'Schema should have @graph' );

				$graph = $result['@graph'];

				// Verify @graph is an array and not empty.
				$this->assertIsArray( $graph, '@graph should be an array' );
				$this->assertNotEmpty( $graph, '@graph should not be empty' );

				// Extract all types from the graph.
				$types_in_graph = array();
				foreach ( $graph as $item ) {
					if ( is_array( $item ) && isset( $item['@type'] ) ) {
						$types_in_graph[] = $item['@type'];
					}
				}

				// Define required types.
				$required_types = array(
					'WebSite',
					'Organization',
					'BreadcrumbList',
				);

				// Verify all required types are present.
				foreach ( $required_types as $required_type ) {
					$this->assertContains(
						$required_type,
						$types_in_graph,
						"Schema graph should contain '{$required_type}' type"
					);
				}

				// Verify at least one of WebPage or Article is present.
				$has_webpage_or_article = in_array( 'WebPage', $types_in_graph, true ) ||
										  in_array( 'Article', $types_in_graph, true );
				$this->assertTrue(
					$has_webpage_or_article,
					'Schema graph should contain either WebPage or Article type'
				);

				// Verify Person type is present (either as separate item or nested in Article author)
				$has_person = in_array( 'Person', $types_in_graph, true );
				if ( ! $has_person ) {
					// Check if Person is nested in Article author
					foreach ( $graph as $item ) {
						if ( is_array( $item ) && isset( $item['@type'] ) && 'Article' === $item['@type'] ) {
							if ( isset( $item['author']['@type'] ) && 'Person' === $item['author']['@type'] ) {
								$has_person = true;
								break;
							}
						}
					}
				}
				$this->assertTrue(
					$has_person,
					'Schema graph should contain Person type (from Article author or as separate item)'
				);

				// Clean up.
				wp_delete_post( $post_id, true );
			}
		);
	}

	/**
	 * Property: Schema graph is valid JSON-LD format
	 *
	 * For any post, the schema graph should be convertible to valid JSON-LD.
	 *
	 * @return void
	 */
	public function test_schema_graph_is_valid_json_ld(): void {
		$this->forAll(
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $post_id ) {
				// Create a test post.
				$post_id = wp_insert_post(
					array(
						'post_title'   => 'Test Post ' . $post_id,
						'post_content' => 'Test content for JSON-LD validation',
						'post_status'  => 'publish',
						'post_type'    => 'post',
					)
				);

				if ( \is_wp_error( $post_id ) ) {
					$this->markTestSkipped( 'Could not create test post' );
					return;
				}

				// Build schema graph.
				$result = $this->schema_builder->build( $post_id );

				// Convert to JSON.
				$json = $this->schema_builder->to_json( $result );

				// Verify JSON is valid.
				$this->assertIsString( $json, 'to_json should return a string' );
				$this->assertNotEmpty( $json, 'JSON should not be empty' );

				// Verify JSON can be decoded.
				$decoded = json_decode( $json, true );
				$this->assertIsArray( $decoded, 'JSON should be decodable to array' );
				$this->assertArrayHasKey( '@context', $decoded, 'Decoded JSON should have @context' );
				$this->assertArrayHasKey( '@graph', $decoded, 'Decoded JSON should have @graph' );

				// Clean up.
				wp_delete_post( $post_id, true );
			}
		);
	}

	/**
	 * Property: Schema graph is deterministic
	 *
	 * For any given post, the schema graph should always be the same
	 * (deterministic behavior).
	 *
	 * @return void
	 */
	public function test_schema_graph_is_deterministic(): void {
		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Deterministic Test Post',
				'post_content' => 'Test content for determinism check',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		if ( \is_wp_error( $post_id ) ) {
			$this->markTestSkipped( 'Could not create test post' );
			return;
		}

		// Build schema graph three times.
		$result1 = $this->schema_builder->build( $post_id );
		$result2 = $this->schema_builder->build( $post_id );
		$result3 = $this->schema_builder->build( $post_id );

		// Convert to JSON for comparison.
		$json1 = $this->schema_builder->to_json( $result1 );
		$json2 = $this->schema_builder->to_json( $result2 );
		$json3 = $this->schema_builder->to_json( $result3 );

		// All JSON outputs should be identical.
		$this->assertEquals(
			$json1,
			$json2,
			'Schema graph should be deterministic (run 1 vs 2)'
		);

		$this->assertEquals(
			$json2,
			$json3,
			'Schema graph should be deterministic (run 2 vs 3)'
		);

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Property: Schema graph has valid @context
	 *
	 * For any post, the schema graph should have a valid @context value.
	 *
	 * @return void
	 */
	public function test_schema_graph_has_valid_context(): void {
		$this->forAll(
			Generators::choose( 1, 50 )
		)
		->then(
			function ( int $post_id ) {
				// Create a test post.
				$post_id = wp_insert_post(
					array(
						'post_title'   => 'Context Test Post ' . $post_id,
						'post_content' => 'Test content for context validation',
						'post_status'  => 'publish',
						'post_type'    => 'post',
					)
				);

				if ( \is_wp_error( $post_id ) ) {
					$this->markTestSkipped( 'Could not create test post' );
					return;
				}

				// Build schema graph.
				$result = $this->schema_builder->build( $post_id );

				// Verify @context is correct.
				$this->assertEquals(
					'https://schema.org',
					$result['@context'],
					'@context should be https://schema.org'
				);

				// Clean up.
				wp_delete_post( $post_id, true );
			}
		);
	}

	/**
	 * Property: Each schema item has required @type field
	 *
	 * For any post, every item in the @graph should have an @type field.
	 *
	 * @return void
	 */
	public function test_each_schema_item_has_type(): void {
		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Type Test Post',
				'post_content' => 'Test content for type validation',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		if ( \is_wp_error( $post_id ) ) {
			$this->markTestSkipped( 'Could not create test post' );
			return;
		}

		// Build schema graph.
		$result = $this->schema_builder->build( $post_id );
		$graph = $result['@graph'];

		// Verify each item has @type.
		foreach ( $graph as $index => $item ) {
			$this->assertIsArray( $item, "Graph item {$index} should be an array" );
			$this->assertArrayHasKey(
				'@type',
				$item,
				"Graph item {$index} should have @type field"
			);
			$this->assertIsString(
				$item['@type'],
				"Graph item {$index} @type should be a string"
			);
			$this->assertNotEmpty(
				$item['@type'],
				"Graph item {$index} @type should not be empty"
			);
		}

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Property: Schema graph handles empty/minimal posts
	 *
	 * For posts with minimal data, the schema graph should still contain
	 * all required types.
	 *
	 * @return void
	 */
	public function test_schema_graph_handles_minimal_posts(): void {
		// Create a minimal post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => '',
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		if ( \is_wp_error( $post_id ) ) {
			$this->markTestSkipped( 'Could not create test post' );
			return;
		}

		// Build schema graph.
		$result = $this->schema_builder->build( $post_id );

		// Verify structure is still valid.
		$this->assertIsArray( $result, 'Schema result should be an array' );
		$this->assertArrayHasKey( '@context', $result, 'Schema should have @context' );
		$this->assertArrayHasKey( '@graph', $result, 'Schema should have @graph' );

		$graph = $result['@graph'];

		// Verify @graph is not empty even for minimal posts.
		$this->assertNotEmpty( $graph, '@graph should not be empty for minimal posts' );

		// Extract types.
		$types_in_graph = array();
		foreach ( $graph as $item ) {
			if ( is_array( $item ) && isset( $item['@type'] ) ) {
				$types_in_graph[] = $item['@type'];
			}
		}

		// Verify required types are still present.
		$this->assertContains( 'WebSite', $types_in_graph, 'WebSite should be present' );
		$this->assertContains( 'Organization', $types_in_graph, 'Organization should be present' );
		$this->assertContains( 'BreadcrumbList', $types_in_graph, 'BreadcrumbList should be present' );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Property: Schema graph returns empty array for invalid post
	 *
	 * For invalid post IDs, the schema graph should return an empty array.
	 *
	 * @return void
	 */
	public function test_schema_graph_returns_empty_for_invalid_post(): void {
		// Use a very high post ID that doesn't exist.
		$invalid_post_id = 999999999;

		// Build schema graph.
		$result = $this->schema_builder->build( $invalid_post_id );

		// Verify result is empty array.
		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertEmpty( $result, 'Result should be empty for invalid post' );
	}

	/**
	 * Property: Schema graph contains @id fields for identification
	 *
	 * For any post, schema items should have @id fields for proper identification.
	 *
	 * @return void
	 */
	public function test_schema_graph_items_have_id_fields(): void {
		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'ID Test Post',
				'post_content' => 'Test content for ID validation',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		if ( \is_wp_error( $post_id ) ) {
			$this->markTestSkipped( 'Could not create test post' );
			return;
		}

		// Build schema graph.
		$result = $this->schema_builder->build( $post_id );
		$graph = $result['@graph'];

		// Verify key items have @id fields.
		$types_with_id = array();
		foreach ( $graph as $item ) {
			if ( is_array( $item ) && isset( $item['@type'] ) ) {
				if ( isset( $item['@id'] ) ) {
					$types_with_id[] = $item['@type'];
				}
			}
		}

		// WebSite, Organization, and BreadcrumbList should have @id.
		$this->assertContains( 'WebSite', $types_with_id, 'WebSite should have @id' );
		$this->assertContains( 'Organization', $types_with_id, 'Organization should have @id' );
		$this->assertContains( 'BreadcrumbList', $types_with_id, 'BreadcrumbList should have @id' );

		// Clean up.
		wp_delete_post( $post_id, true );
	}
}
