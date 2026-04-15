<?php
/**
 * Meta Module Real WordPress Hooks Integration Tests
 *
 * Tests that the Meta Module works correctly with real WordPress hooks
 * (not mocked). Requires a full WordPress installation.
 *
 * @package MeowSEO
 * @subpackage Tests\Integration
 */

namespace MeowSEO\Tests\Integration;

/**
 * Meta Module Real Hooks Test Case
 *
 * NOTE: These tests require a real WordPress installation with the
 * WordPress Test Suite. They test actual hook execution, not mocked functions.
 */
class MetaModuleRealHooksTest extends \WP_UnitTestCase {
	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Activate MeowSEO plugin.
		activate_plugin( 'meowseo/meowseo.php' );

		// Boot the plugin.
		\MeowSEO\Plugin::instance()->boot();
	}

	/**
	 * Test wp_head output contains all expected tags
	 *
	 * Validates: Requirements 1.2, 2.1
	 *
	 * @return void
	 */
	public function test_wp_head_output_contains_all_expected_tags(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content for the post',
				'post_status'  => 'publish',
			)
		);

		// Set meta description.
		update_post_meta( $post_id, '_meowseo_description', 'Test meta description' );

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Capture wp_head output.
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		// Verify all expected tags are present.
		$this->assertStringContainsString( '<title>', $output, 'Should contain title tag' );
		$this->assertStringContainsString( '<meta name="description"', $output, 'Should contain meta description' );
		$this->assertStringContainsString( '<meta name="robots"', $output, 'Should contain robots meta tag' );
		$this->assertStringContainsString( '<link rel="canonical"', $output, 'Should contain canonical link' );
	}

	/**
	 * Test document_title_parts filter returns empty array
	 *
	 * Validates: Requirements 1.3
	 *
	 * @return void
	 */
	public function test_document_title_parts_filter_returns_empty_array(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Apply document_title_parts filter with initial parts.
		$parts = apply_filters(
			'document_title_parts',
			array(
				'title' => 'Test Post',
				'site'  => 'Test Site',
			)
		);

		// Should return empty array to suppress WordPress's default title.
		$this->assertIsArray( $parts );
		$this->assertEmpty( $parts, 'document_title_parts filter should return empty array' );
	}

	/**
	 * Test no duplicate title tags output
	 *
	 * Validates: Requirements 1.2, 2.1
	 *
	 * @return void
	 */
	public function test_no_duplicate_title_tags_output(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Capture wp_head output.
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		// Count title tags.
		$title_count = substr_count( $output, '<title>' );

		// Should have exactly one title tag.
		$this->assertSame( 1, $title_count, 'Should have exactly one title tag' );

		// Verify closing tag.
		$closing_count = substr_count( $output, '</title>' );
		$this->assertSame( 1, $closing_count, 'Should have exactly one closing title tag' );
	}

	/**
	 * Test meta tag output order
	 *
	 * Validates: Requirements 2.1
	 *
	 * @return void
	 */
	public function test_meta_tag_output_order(): void {
		// Create a test post with all meta fields.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content for the post',
				'post_status'  => 'publish',
			)
		);

		// Set meta fields.
		update_post_meta( $post_id, '_meowseo_title', 'Custom Title' );
		update_post_meta( $post_id, '_meowseo_description', 'Custom Description' );
		update_post_meta( $post_id, '_meowseo_canonical', 'https://example.com/custom' );

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Capture wp_head output.
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		// Find positions of tags.
		$title_pos       = strpos( $output, '<title>' );
		$description_pos = strpos( $output, '<meta name="description"' );
		$robots_pos      = strpos( $output, '<meta name="robots"' );
		$canonical_pos   = strpos( $output, '<link rel="canonical"' );

		// Verify order: Title → Description → Robots → Canonical.
		$this->assertNotFalse( $title_pos, 'Title tag should be present' );
		$this->assertNotFalse( $description_pos, 'Description tag should be present' );
		$this->assertNotFalse( $robots_pos, 'Robots tag should be present' );
		$this->assertNotFalse( $canonical_pos, 'Canonical tag should be present' );

		// Verify order.
		$this->assertLessThan( $description_pos, $title_pos, 'Title should come before description' );
		$this->assertLessThan( $robots_pos, $description_pos, 'Description should come before robots' );
		$this->assertLessThan( $canonical_pos, $robots_pos, 'Robots should come before canonical' );
	}

	/**
	 * Test title tag uses custom SEO title
	 *
	 * Validates: Requirements 1.2, 3.1
	 *
	 * @return void
	 */
	public function test_title_tag_uses_custom_seo_title(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Original Title',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Set custom SEO title.
		update_post_meta( $post_id, '_meowseo_title', 'Custom SEO Title' );

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Capture wp_head output.
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		// Verify custom title is used.
		$this->assertStringContainsString( '<title>Custom SEO Title</title>', $output, 'Should use custom SEO title' );
		$this->assertStringNotContainsString( '<title>Original Title</title>', $output, 'Should not use original title' );
	}

	/**
	 * Test meta description uses custom value
	 *
	 * Validates: Requirements 1.2, 4.1
	 *
	 * @return void
	 */
	public function test_meta_description_uses_custom_value(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Set custom meta description.
		update_post_meta( $post_id, '_meowseo_description', 'Custom meta description' );

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Capture wp_head output.
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		// Verify custom description is used.
		$this->assertStringContainsString( 'content="Custom meta description"', $output, 'Should use custom meta description' );
	}

	/**
	 * Test canonical URL is output
	 *
	 * Validates: Requirements 1.2, 6.1
	 *
	 * @return void
	 */
	public function test_canonical_url_is_output(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Capture wp_head output.
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		// Verify canonical URL is present.
		$this->assertStringContainsString( '<link rel="canonical"', $output, 'Should output canonical link' );
		$this->assertStringContainsString( get_permalink( $post_id ), $output, 'Canonical should contain post URL' );
	}

	/**
	 * Test robots meta tag is output
	 *
	 * Validates: Requirements 1.2, 7.1
	 *
	 * @return void
	 */
	public function test_robots_meta_tag_is_output(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Capture wp_head output.
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		// Verify robots meta tag is present.
		$this->assertStringContainsString( '<meta name="robots"', $output, 'Should output robots meta tag' );

		// Verify Google Discover directives are present.
		$this->assertStringContainsString( 'max-image-preview:large', $output, 'Should contain max-image-preview directive' );
		$this->assertStringContainsString( 'max-snippet:-1', $output, 'Should contain max-snippet directive' );
		$this->assertStringContainsString( 'max-video-preview:-1', $output, 'Should contain max-video-preview directive' );
	}

	/**
	 * Test no duplicate meta description tags
	 *
	 * Validates: Requirements 2.1, 2.3
	 *
	 * @return void
	 */
	public function test_no_duplicate_meta_description_tags(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Set meta description.
		update_post_meta( $post_id, '_meowseo_description', 'Test description' );

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Capture wp_head output.
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		// Count meta description tags.
		$description_count = substr_count( $output, '<meta name="description"' );

		// Should have exactly one meta description tag.
		$this->assertSame( 1, $description_count, 'Should have exactly one meta description tag' );
	}

	/**
	 * Test no duplicate canonical tags
	 *
	 * Validates: Requirements 2.1, 6.1
	 *
	 * @return void
	 */
	public function test_no_duplicate_canonical_tags(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Capture wp_head output.
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();

		// Count canonical tags.
		$canonical_count = substr_count( $output, '<link rel="canonical"' );

		// Should have exactly one canonical tag.
		$this->assertSame( 1, $canonical_count, 'Should have exactly one canonical tag' );
	}

	/**
	 * Test theme title tag support is removed
	 *
	 * Validates: Requirements 1.3
	 *
	 * @return void
	 */
	public function test_theme_title_tag_support_is_removed(): void {
		// Add title-tag support (simulating a theme).
		add_theme_support( 'title-tag' );

		// Verify it's added.
		$this->assertTrue( current_theme_supports( 'title-tag' ), 'Title tag support should be added' );

		// Boot the plugin (which should remove title-tag support).
		\MeowSEO\Plugin::instance()->boot();

		// Verify title-tag support is removed.
		$this->assertFalse( current_theme_supports( 'title-tag' ), 'Title tag support should be removed by Meta Module' );
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Remove all hooks.
		remove_all_actions( 'wp_head' );
		remove_all_filters( 'document_title_parts' );

		parent::tearDown();
	}
}
