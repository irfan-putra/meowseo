<?php
/**
 * Article Node Tests
 *
 * Unit tests for the Article_Node schema builder class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Schema_Nodes\Article_Node;
use MeowSEO\Options;
use WP_Post;

/**
 * Article Node test case
 *
 * Tests Article_Node builder (Requirements 1.4, 1.11, 20.1, 20.2).
 *
 * @since 1.0.0
 */
class Test_Article_Node extends TestCase {

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
	 * Test Article_Node instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$post = $this->create_mock_post();
		$node = new Article_Node( $post->ID, $post, $this->options );
		
		$this->assertInstanceOf( Article_Node::class, $node );
	}

	/**
	 * Test is_needed returns true for post type "post"
	 *
	 * Validates Requirement 1.4: Article node included when post_type is "post".
	 *
	 * @return void
	 */
	public function test_is_needed_returns_true_for_post_type(): void {
		$post = $this->create_mock_post( array( 'post_type' => 'post' ) );
		$node = new Article_Node( $post->ID, $post, $this->options );
		
		$this->assertTrue( $node->is_needed() );
	}

	/**
	 * Test is_needed returns true for schema type "Article"
	 *
	 * Validates Requirement 1.4: Article node included when schema_type is "Article".
	 *
	 * @return void
	 */
	public function test_is_needed_returns_true_for_schema_type(): void {
		$post = $this->create_mock_post( array( 'post_type' => 'page' ) );
		
		// Set schema type in postmeta storage.
		update_post_meta( $post->ID, '_meowseo_schema_type', 'Article' );
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		
		$this->assertTrue( $node->is_needed() );
	}

	/**
	 * Test is_needed returns false for other post types
	 *
	 * @return void
	 */
	public function test_is_needed_returns_false_for_other_types(): void {
		$post = $this->create_mock_post( array( 'post_type' => 'page' ) );
		
		// No schema type set in postmeta.
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		
		$this->assertFalse( $node->is_needed() );
	}

	/**
	 * Test generate returns valid Article schema
	 *
	 * Validates Requirements 1.4, 1.11: Article node structure.
	 *
	 * @return void
	 */
	public function test_generate_returns_valid_schema(): void {
		$post = $this->create_mock_post();
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify required properties.
		$this->assertIsArray( $schema );
		$this->assertEquals( 'Article', $schema['@type'] );
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertArrayHasKey( 'isPartOf', $schema );
		$this->assertArrayHasKey( 'headline', $schema );
		$this->assertArrayHasKey( 'datePublished', $schema );
		$this->assertArrayHasKey( 'dateModified', $schema );
		$this->assertArrayHasKey( 'mainEntityOfPage', $schema );
		$this->assertArrayHasKey( 'publisher', $schema );
		$this->assertArrayHasKey( 'inLanguage', $schema );
	}

	/**
	 * Test generate includes author Person
	 *
	 * Validates Requirement 1.4: Article includes author Person.
	 *
	 * @return void
	 */
	public function test_generate_includes_author(): void {
		$post = $this->create_mock_post();
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		$this->assertArrayHasKey( 'author', $schema );
		$this->assertEquals( 'Person', $schema['author']['@type'] );
		$this->assertArrayHasKey( '@id', $schema['author'] );
		$this->assertArrayHasKey( 'name', $schema['author'] );
	}

	/**
	 * Test generate includes wordCount
	 *
	 * Validates Requirement 1.4: Article includes wordCount.
	 *
	 * @return void
	 */
	public function test_generate_includes_word_count(): void {
		$post = $this->create_mock_post( array(
			'post_content' => 'This is a test post with some content to count words.',
		));
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		$this->assertArrayHasKey( 'wordCount', $schema );
		$this->assertGreaterThan( 0, $schema['wordCount'] );
	}

	/**
	 * Test generate includes commentCount
	 *
	 * Validates Requirement 1.4: Article includes commentCount.
	 *
	 * @return void
	 */
	public function test_generate_includes_comment_count(): void {
		$post = $this->create_mock_post( array( 'comment_count' => 5 ) );
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		$this->assertArrayHasKey( 'commentCount', $schema );
		$this->assertEquals( 5, $schema['commentCount'] );
	}

	/**
	 * Test generate includes publisher reference
	 *
	 * Validates Requirement 1.4: Article includes publisher reference.
	 *
	 * @return void
	 */
	public function test_generate_includes_publisher(): void {
		$post = $this->create_mock_post();
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		$this->assertArrayHasKey( 'publisher', $schema );
		$this->assertArrayHasKey( '@id', $schema['publisher'] );
		$this->assertStringContainsString( '#organization', $schema['publisher']['@id'] );
	}

	/**
	 * Test generate includes image when featured image exists
	 *
	 * Validates Requirement 1.4: Article includes image.
	 *
	 * @return void
	 */
	public function test_generate_includes_image(): void {
		$post = $this->create_mock_post();
		
		// Mock has_post_thumbnail to return true.
		global $wp_postmeta_storage;
		$wp_postmeta_storage[ $post->ID ]['_thumbnail_id'] = array( 123 );
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		$this->assertArrayHasKey( 'image', $schema );
		$this->assertArrayHasKey( '@id', $schema['image'] );
	}

	/**
	 * Test generate includes articleSection from categories
	 *
	 * Validates Requirement 1.4: Article includes articleSection.
	 *
	 * @return void
	 */
	public function test_generate_includes_article_section(): void {
		$post = $this->create_mock_post();
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Since get_the_category returns empty array by default, articleSection should not be present.
		$this->assertArrayNotHasKey( 'articleSection', $schema );
	}

	/**
	 * Test generate includes keywords from tags
	 *
	 * Validates Requirement 1.4: Article includes keywords.
	 *
	 * @return void
	 */
	public function test_generate_includes_keywords(): void {
		$post = $this->create_mock_post();
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Since get_the_tags returns false by default, keywords should not be present.
		$this->assertArrayNotHasKey( 'keywords', $schema );
	}

	/**
	 * Test generate includes speakable property
	 *
	 * Validates Requirements 1.11, 20.1, 20.2: Article includes speakable with cssSelector.
	 *
	 * @return void
	 */
	public function test_generate_includes_speakable(): void {
		$post = $this->create_mock_post();
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		$this->assertArrayHasKey( 'speakable', $schema );
		$this->assertEquals( 'SpeakableSpecification', $schema['speakable']['@type'] );
		$this->assertArrayHasKey( 'cssSelector', $schema['speakable'] );
		$this->assertIsArray( $schema['speakable']['cssSelector'] );
		$this->assertContains( '#meowseo-direct-answer', $schema['speakable']['cssSelector'] );
	}

	/**
	 * Test generate handles missing categories gracefully
	 *
	 * @return void
	 */
	public function test_generate_handles_missing_categories(): void {
		$post = $this->create_mock_post();
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		$this->assertArrayNotHasKey( 'articleSection', $schema );
	}

	/**
	 * Test generate handles missing tags gracefully
	 *
	 * @return void
	 */
	public function test_generate_handles_missing_tags(): void {
		$post = $this->create_mock_post();
		
		$node = new Article_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		$this->assertArrayNotHasKey( 'keywords', $schema );
	}

	/**
	 * Create a mock post object for testing
	 *
	 * @param array $overrides Optional property overrides.
	 * @return WP_Post Mock post object.
	 */
	private function create_mock_post( array $overrides = array() ): WP_Post {
		$defaults = array(
			'ID'            => 1,
			'post_title'    => 'Test Article',
			'post_content'  => 'This is test content for the article.',
			'post_excerpt'  => 'Test excerpt',
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_author'   => 1,
			'post_date_gmt' => '2024-01-01 12:00:00',
			'post_modified_gmt' => '2024-01-02 12:00:00',
			'comment_count' => 0,
		);

		$data = array_merge( $defaults, $overrides );
		$post = new \stdClass();
		
		foreach ( $data as $key => $value ) {
			$post->$key = $value;
		}

		return (object) $post;
	}
}
