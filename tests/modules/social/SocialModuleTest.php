<?php
/**
 * Social Module Tests
 *
 * Tests for the Social module functionality.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Social;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Social\Social;
use MeowSEO\Options;

/**
 * Social module test case
 */
class SocialModuleTest extends TestCase {

	/**
	 * Social module instance
	 *
	 * @var Social
	 */
	private Social $social;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$this->social = new Social( $this->options );

		// Create a test post.
		$this->post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post for Social Meta',
				'post_content' => 'This is test content for social meta tags.',
				'post_excerpt' => 'This is a test excerpt.',
				'post_status'  => 'publish',
			)
		);
	}

	/**
	 * Test module ID
	 */
	public function test_get_id(): void {
		$this->assertEquals( 'social', $this->social->get_id() );
	}

	/**
	 * Test social data retrieval with defaults
	 */
	public function test_get_social_data_defaults(): void {
		$social_data = $this->social->get_social_data( $this->post_id );

		$this->assertIsArray( $social_data );
		$this->assertArrayHasKey( 'title', $social_data );
		$this->assertArrayHasKey( 'description', $social_data );
		$this->assertArrayHasKey( 'image', $social_data );
		$this->assertArrayHasKey( 'type', $social_data );
		$this->assertArrayHasKey( 'url', $social_data );

		// Title should default to post title.
		$this->assertEquals( 'Test Post for Social Meta', $social_data['title'] );

		// Description should default to excerpt.
		$this->assertEquals( 'This is a test excerpt.', $social_data['description'] );

		// Type should be 'article' for posts.
		$this->assertEquals( 'article', $social_data['type'] );

		// URL should be the permalink.
		$this->assertEquals( get_permalink( $this->post_id ), $social_data['url'] );
	}

	/**
	 * Test social data with custom overrides
	 */
	public function test_get_social_data_with_overrides(): void {
		// Set custom social meta.
		update_post_meta( $this->post_id, 'meowseo_social_title', 'Custom Social Title' );
		update_post_meta( $this->post_id, 'meowseo_social_description', 'Custom social description for sharing.' );

		$social_data = $this->social->get_social_data( $this->post_id );

		$this->assertEquals( 'Custom Social Title', $social_data['title'] );
		$this->assertEquals( 'Custom social description for sharing.', $social_data['description'] );
	}

	/**
	 * Test social image fallback logic
	 */
	public function test_social_image_fallback(): void {
		// Create a test attachment.
		$attachment_id = $this->factory->attachment->create_upload_object(
			dirname( __FILE__ ) . '/../../fixtures/test-image.jpg'
		);

		// Test with custom social image.
		update_post_meta( $this->post_id, 'meowseo_social_image_id', $attachment_id );
		$social_data = $this->social->get_social_data( $this->post_id );
		$this->assertNotEmpty( $social_data['image'] );

		// Clear cache for next test.
		\MeowSEO\Helpers\Cache::delete( "social_{$this->post_id}" );

		// Test fallback to featured image.
		delete_post_meta( $this->post_id, 'meowseo_social_image_id' );
		set_post_thumbnail( $this->post_id, $attachment_id );
		$social_data = $this->social->get_social_data( $this->post_id );
		$this->assertNotEmpty( $social_data['image'] );

		// Clear cache for next test.
		\MeowSEO\Helpers\Cache::delete( "social_{$this->post_id}" );

		// Test fallback to global default.
		delete_post_thumbnail( $this->post_id );
		$this->options->set( 'default_social_image', $attachment_id );
		$social_data = $this->social->get_social_data( $this->post_id );
		$this->assertNotEmpty( $social_data['image'] );
	}

	/**
	 * Test cache invalidation on post save
	 */
	public function test_cache_invalidation(): void {
		// Get social data to populate cache.
		$social_data = $this->social->get_social_data( $this->post_id );
		$this->assertNotEmpty( $social_data );

		// Verify cache exists.
		$cached = \MeowSEO\Helpers\Cache::get( "social_{$this->post_id}" );
		$this->assertNotEmpty( $cached );

		// Invalidate cache.
		$this->social->invalidate_cache( $this->post_id );

		// Verify cache is cleared.
		$cached = \MeowSEO\Helpers\Cache::get( "social_{$this->post_id}" );
		$this->assertFalse( $cached );
	}

	/**
	 * Test Open Graph type for different post types
	 */
	public function test_og_type_for_post_types(): void {
		// Test for post (should be 'article').
		$social_data = $this->social->get_social_data( $this->post_id );
		$this->assertEquals( 'article', $social_data['type'] );

		// Create a page.
		$page_id = $this->factory->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Test Page',
				'post_status' => 'publish',
			)
		);

		$social_data = $this->social->get_social_data( $page_id );
		$this->assertEquals( 'website', $social_data['type'] );
	}

	/**
	 * Clean up test environment
	 */
	public function tearDown(): void {
		wp_delete_post( $this->post_id, true );
		parent::tearDown();
	}
}
