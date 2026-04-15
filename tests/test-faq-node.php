<?php
/**
 * FAQ Node Tests
 *
 * Unit tests for the FAQ_Node schema builder class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Schema_Nodes\FAQ_Node;
use MeowSEO\Options;
use WP_Post;

// Manually require the FAQ_Node class since autoloader has issues with Schema_Nodes namespace
require_once __DIR__ . '/../includes/helpers/class-abstract-schema-node.php';
require_once __DIR__ . '/../includes/helpers/schema-nodes/class-faq-node.php';

/**
 * FAQ Node test case
 *
 * Tests FAQ_Node builder (Requirements 1.6, 9.2).
 *
 * @since 1.0.0
 */
class Test_FAQ_Node extends TestCase {

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
		$this->options = $this->createMock( Options::class );
	}

	/**
	 * Test FAQ_Node instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$post = $this->create_mock_post();
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		
		$this->assertInstanceOf( FAQ_Node::class, $node );
	}

	/**
	 * Test is_needed returns true when schema type is FAQPage and FAQ items exist
	 *
	 * Validates Requirement 1.6: FAQ node included when schema_type is "FAQPage" AND FAQ items exist.
	 *
	 * @return void
	 */
	public function test_is_needed_returns_true_for_faqpage_with_items(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta to return FAQPage schema type and FAQ items.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_type'   => 'FAQPage',
			'_meowseo_schema_config' => json_encode( array(
				'faq_items' => array(
					array(
						'question' => 'What is the question?',
						'answer'   => 'This is the answer.',
					),
				),
			)),
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		
		$this->assertTrue( $node->is_needed() );
	}

	/**
	 * Test is_needed returns false when schema type is not FAQPage
	 *
	 * Validates Requirement 1.6: FAQ node only for FAQPage schema type.
	 *
	 * @return void
	 */
	public function test_is_needed_returns_false_for_non_faqpage_type(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta to return Article schema type.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_type' => 'Article',
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		
		$this->assertFalse( $node->is_needed() );
	}

	/**
	 * Test is_needed returns false when FAQ items are empty
	 *
	 * Validates Requirement 1.6: FAQ node requires FAQ items to exist.
	 *
	 * @return void
	 */
	public function test_is_needed_returns_false_when_faq_items_empty(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta to return FAQPage schema type but no FAQ items.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_type'   => 'FAQPage',
			'_meowseo_schema_config' => json_encode( array(
				'faq_items' => array(),
			)),
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		
		$this->assertFalse( $node->is_needed() );
	}

	/**
	 * Test is_needed returns false when FAQ items have missing question or answer
	 *
	 * Validates Requirement 1.6: FAQ items must have both question and answer.
	 *
	 * @return void
	 */
	public function test_is_needed_returns_false_when_faq_items_incomplete(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta with incomplete FAQ items.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_type'   => 'FAQPage',
			'_meowseo_schema_config' => json_encode( array(
				'faq_items' => array(
					array(
						'question' => 'What is the question?',
						// Missing answer.
					),
					array(
						// Missing question.
						'answer' => 'This is the answer.',
					),
				),
			)),
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		
		$this->assertFalse( $node->is_needed() );
	}

	/**
	 * Test generate returns valid FAQPage schema
	 *
	 * Validates Requirement 9.2: Generate mainEntity array with Question/Answer pairs.
	 *
	 * @return void
	 */
	public function test_generate_returns_valid_schema(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta with FAQ items.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_config' => json_encode( array(
				'faq_items' => array(
					array(
						'question' => 'What is the first question?',
						'answer'   => 'This is the first answer.',
					),
					array(
						'question' => 'What is the second question?',
						'answer'   => 'This is the second answer.',
					),
				),
			)),
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify required properties.
		$this->assertIsArray( $schema );
		$this->assertEquals( 'FAQPage', $schema['@type'] );
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertArrayHasKey( 'mainEntity', $schema );
		$this->assertIsArray( $schema['mainEntity'] );
		$this->assertCount( 2, $schema['mainEntity'] );
	}

	/**
	 * Test generate creates Question/Answer pairs correctly
	 *
	 * Validates Requirement 9.2: mainEntity contains Question with acceptedAnswer.
	 *
	 * @return void
	 */
	public function test_generate_creates_question_answer_pairs(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta with FAQ items.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_config' => json_encode( array(
				'faq_items' => array(
					array(
						'question' => 'What is the question?',
						'answer'   => 'This is the answer.',
					),
				),
			)),
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify Question/Answer structure.
		$this->assertEquals( 'Question', $schema['mainEntity'][0]['@type'] );
		$this->assertEquals( 'What is the question?', $schema['mainEntity'][0]['name'] );
		$this->assertArrayHasKey( 'acceptedAnswer', $schema['mainEntity'][0] );
		$this->assertEquals( 'Answer', $schema['mainEntity'][0]['acceptedAnswer']['@type'] );
		$this->assertEquals( 'This is the answer.', $schema['mainEntity'][0]['acceptedAnswer']['text'] );
	}

	/**
	 * Test generate skips incomplete FAQ items
	 *
	 * Validates Requirement 9.2: Only include FAQ items with both question and answer.
	 *
	 * @return void
	 */
	public function test_generate_skips_incomplete_items(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta with mixed complete and incomplete FAQ items.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_config' => json_encode( array(
				'faq_items' => array(
					array(
						'question' => 'Valid question?',
						'answer'   => 'Valid answer.',
					),
					array(
						'question' => 'Missing answer?',
						// No answer.
					),
					array(
						// No question.
						'answer' => 'Missing question.',
					),
					array(
						'question' => 'Another valid question?',
						'answer'   => 'Another valid answer.',
					),
				),
			)),
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify only valid items are included.
		$this->assertCount( 2, $schema['mainEntity'] );
		$this->assertEquals( 'Valid question?', $schema['mainEntity'][0]['name'] );
		$this->assertEquals( 'Another valid question?', $schema['mainEntity'][1]['name'] );
	}

	/**
	 * Test generate handles JSON string schema config
	 *
	 * Validates Requirement 9.2: Read FAQ items from _meowseo_schema_config postmeta.
	 *
	 * @return void
	 */
	public function test_generate_handles_json_string_config(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta with JSON string.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_config' => '{"faq_items":[{"question":"Test?","answer":"Test answer."}]}',
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify schema is generated correctly.
		$this->assertArrayHasKey( 'mainEntity', $schema );
		$this->assertCount( 1, $schema['mainEntity'] );
		$this->assertEquals( 'Test?', $schema['mainEntity'][0]['name'] );
	}

	/**
	 * Test generate handles array schema config
	 *
	 * Validates Requirement 9.2: Handle both JSON string and array formats.
	 *
	 * @return void
	 */
	public function test_generate_handles_array_config(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta with array.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_config' => array(
				'faq_items' => array(
					array(
						'question' => 'Test?',
						'answer'   => 'Test answer.',
					),
				),
			),
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify schema is generated correctly.
		$this->assertArrayHasKey( 'mainEntity', $schema );
		$this->assertCount( 1, $schema['mainEntity'] );
		$this->assertEquals( 'Test?', $schema['mainEntity'][0]['name'] );
	}

	/**
	 * Test generate returns empty mainEntity when no valid items
	 *
	 * Validates Requirement 9.2: Only add mainEntity if FAQ items exist.
	 *
	 * @return void
	 */
	public function test_generate_omits_main_entity_when_no_valid_items(): void {
		$post = $this->create_mock_post();
		
		// Mock get_post_meta with no valid FAQ items.
		$this->mock_post_meta( $post->ID, array(
			'_meowseo_schema_config' => json_encode( array(
				'faq_items' => array(
					array(
						'question' => 'Missing answer?',
					),
				),
			)),
		));
		
		$node = new FAQ_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify mainEntity is not present.
		$this->assertArrayNotHasKey( 'mainEntity', $schema );
	}

	/**
	 * Create a mock post object for testing
	 *
	 * @param array $overrides Optional property overrides.
	 * @return WP_Post Mock post object.
	 */
	private function create_mock_post( array $overrides = array() ): WP_Post {
		$defaults = array(
			'ID'                => 1,
			'post_title'        => 'Test FAQ Post',
			'post_content'      => 'This is test content for the FAQ post.',
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

		return (object) $post;
	}

	/**
	 * Mock post meta for testing
	 *
	 * Uses the global postmeta storage from bootstrap.php.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $meta    Meta key-value pairs.
	 * @return void
	 */
	private function mock_post_meta( int $post_id, array $meta ): void {
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
