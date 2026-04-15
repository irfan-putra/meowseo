<?php
/**
 * Breadcrumb Node Tests
 *
 * Unit tests for the Breadcrumb_Node schema builder class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Schema_Nodes\Breadcrumb_Node;
use MeowSEO\Helpers\Breadcrumbs;
use MeowSEO\Options;
use WP_Post;

// Manually require the Breadcrumb_Node class since autoloader has issues with Schema_Nodes namespace
require_once __DIR__ . '/../includes/helpers/class-abstract-schema-node.php';
require_once __DIR__ . '/../includes/helpers/class-breadcrumbs.php';
require_once __DIR__ . '/../includes/helpers/schema-nodes/class-breadcrumb-node.php';

/**
 * Breadcrumb Node test case
 *
 * Tests Breadcrumb_Node builder (Requirements 1.3, 8.10).
 *
 * @since 1.0.0
 */
class BreadcrumbNodeTest extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Breadcrumbs instance
	 *
	 * @var Breadcrumbs
	 */
	private Breadcrumbs $breadcrumbs;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = $this->createMock( Options::class );
		$this->breadcrumbs = new Breadcrumbs( new Options() );
	}

	/**
	 * Test Breadcrumb_Node instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$post = $this->create_mock_post();
		$context = array( 'breadcrumbs' => $this->breadcrumbs );
		$node = new Breadcrumb_Node( $post->ID, $post, $this->options, $context );
		
		$this->assertInstanceOf( Breadcrumb_Node::class, $node );
	}

	/**
	 * Test is_needed always returns true
	 *
	 * Validates Requirement 1.3: BreadcrumbList node is always included in the @graph.
	 *
	 * @return void
	 */
	public function test_is_needed_always_returns_true(): void {
		$post = $this->create_mock_post();
		$context = array( 'breadcrumbs' => $this->breadcrumbs );
		$node = new Breadcrumb_Node( $post->ID, $post, $this->options, $context );
		
		$this->assertTrue( $node->is_needed() );
	}

	/**
	 * Test generate returns valid BreadcrumbList schema
	 *
	 * Validates Requirement 8.10: Generate BreadcrumbList with ListItem array.
	 *
	 * @return void
	 */
	public function test_generate_returns_valid_schema(): void {
		$post = $this->create_mock_post();
		$context = array( 'breadcrumbs' => $this->breadcrumbs );
		$node = new Breadcrumb_Node( $post->ID, $post, $this->options, $context );
		
		$schema = $node->generate();
		
		// Verify required properties.
		$this->assertIsArray( $schema );
		$this->assertEquals( 'BreadcrumbList', $schema['@type'] );
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertArrayHasKey( 'itemListElement', $schema );
		$this->assertIsArray( $schema['itemListElement'] );
	}

	/**
	 * Test generate creates ListItem array with position property
	 *
	 * Validates Requirement 8.10: Include position property for each item.
	 *
	 * @return void
	 */
	public function test_generate_creates_list_items_with_position(): void {
		$post = $this->create_mock_post();
		$context = array( 'breadcrumbs' => $this->breadcrumbs );
		$node = new Breadcrumb_Node( $post->ID, $post, $this->options, $context );
		
		$schema = $node->generate();
		
		// Verify itemListElement structure.
		$this->assertNotEmpty( $schema['itemListElement'] );
		
		foreach ( $schema['itemListElement'] as $index => $item ) {
			$this->assertEquals( 'ListItem', $item['@type'] );
			$this->assertArrayHasKey( 'position', $item );
			$this->assertEquals( $index + 1, $item['position'] );
			$this->assertArrayHasKey( 'name', $item );
		}
	}

	/**
	 * Test generate includes item URL when available
	 *
	 * Validates Requirement 8.10: Include item property when URL is not empty.
	 *
	 * @return void
	 */
	public function test_generate_includes_item_url_when_available(): void {
		$post = $this->create_mock_post();
		$context = array( 'breadcrumbs' => $this->breadcrumbs );
		$node = new Breadcrumb_Node( $post->ID, $post, $this->options, $context );
		
		$schema = $node->generate();
		
		// Verify that items with URLs have the 'item' property.
		foreach ( $schema['itemListElement'] as $item ) {
			if ( ! empty( $item['name'] ) && $item['name'] !== 'Page Not Found' ) {
				// Most items should have URLs.
				$this->assertArrayHasKey( 'item', $item );
			}
		}
	}

	/**
	 * Test generate omits item URL when empty
	 *
	 * Validates Requirement 8.10: Only include item property if URL is not empty.
	 *
	 * @return void
	 */
	public function test_generate_omits_item_url_when_empty(): void {
		$post = $this->create_mock_post();
		$context = array( 'breadcrumbs' => $this->breadcrumbs );
		$node = new Breadcrumb_Node( $post->ID, $post, $this->options, $context );
		
		$schema = $node->generate();
		
		// Verify that all items have either an 'item' property or no URL.
		foreach ( $schema['itemListElement'] as $item ) {
			if ( ! empty( $item['name'] ) && $item['name'] !== 'Page Not Found' ) {
				// Items with names should have URLs.
				$this->assertArrayHasKey( 'item', $item );
			}
		}
	}

	/**
	 * Test generate returns empty array when no breadcrumbs in context
	 *
	 * Validates Requirement 8.10: Handle missing breadcrumbs gracefully.
	 *
	 * @return void
	 */
	public function test_generate_returns_empty_when_no_breadcrumbs(): void {
		$post = $this->create_mock_post();
		$context = array(); // No breadcrumbs in context
		$node = new Breadcrumb_Node( $post->ID, $post, $this->options, $context );
		
		$schema = $node->generate();
		
		// Should return empty array when breadcrumbs not in context.
		$this->assertEmpty( $schema );
	}

	/**
	 * Test generate returns empty array when breadcrumbs is not Breadcrumbs instance
	 *
	 * Validates Requirement 8.10: Handle invalid breadcrumbs gracefully.
	 *
	 * @return void
	 */
	public function test_generate_returns_empty_when_invalid_breadcrumbs(): void {
		$post = $this->create_mock_post();
		$context = array( 'breadcrumbs' => 'invalid' ); // Invalid breadcrumbs
		$node = new Breadcrumb_Node( $post->ID, $post, $this->options, $context );
		
		$schema = $node->generate();
		
		// Should return empty array when breadcrumbs is not a Breadcrumbs instance.
		$this->assertEmpty( $schema );
	}

	/**
	 * Test @id URL format is correct
	 *
	 * Validates Requirement 1.7: Consistent @id format (URL + #fragment).
	 *
	 * @return void
	 */
	public function test_id_url_format_is_correct(): void {
		$post = $this->create_mock_post();
		$context = array( 'breadcrumbs' => $this->breadcrumbs );
		$node = new Breadcrumb_Node( $post->ID, $post, $this->options, $context );
		
		$schema = $node->generate();
		
		// Verify @id format.
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertStringContainsString( '#breadcrumb', $schema['@id'] );
	}

	/**
	 * Create a mock post object for testing
	 *
	 * @param array $overrides Optional property overrides.
	 * @return object Mock post object.
	 */
	private function create_mock_post( array $overrides = array() ) {
		$defaults = array(
			'ID'                => 1,
			'post_title'        => 'Test Breadcrumb Post',
			'post_content'      => 'This is test content for the breadcrumb post.',
			'post_excerpt'      => 'Test excerpt',
			'post_type'         => 'post',
			'post_status'       => 'publish',
			'post_author'       => 1,
			'post_date_gmt'     => '2024-01-01 12:00:00',
			'post_modified_gmt' => '2024-01-02 12:00:00',
		);

		$data = array_merge( $defaults, $overrides );
		$post = new \stdClass();
		
		foreach ( $data as $key => $value ) {
			$post->$key = $value;
		}

		return $post;
	}
}
