<?php
/**
 * Tests for Orphaned_Detector class.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Unit\Modules\Orphaned;

use MeowSEO\Modules\Orphaned\Orphaned_Detector;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Test Orphaned_Detector class.
 *
 * Validates: Requirements 8.2, 8.3, 8.7
 */
class Test_Orphaned_Detector extends TestCase {

	/**
	 * Orphaned Detector instance.
	 *
	 * @var Orphaned_Detector
	 */
	private Orphaned_Detector $detector;

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$this->detector = new Orphaned_Detector( $this->options );
	}

	/**
	 * Test get_inbound_link_count returns 0 for post with no links.
	 *
	 * Requirement 8.2: THE Orphaned_Detector SHALL query the Internal_Link_Scanner database table
	 * Requirement 8.3: WHEN a post has zero inbound links, THE Orphaned_Detector SHALL mark it as orphaned
	 *
	 * @return void
	 */
	public function test_get_inbound_link_count_returns_zero_for_orphaned_post(): void {
		// Create a test post.
		$post_id = wp_insert_post( array(
			'post_title'   => 'Test Post',
			'post_content' => 'Test content',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		) );

		// Get inbound link count.
		$count = $this->detector->get_inbound_link_count( $post_id );

		// Assert count is 0.
		$this->assertEquals( 0, $count );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_id returns correct module ID.
	 *
	 * @return void
	 */
	public function test_get_id_returns_orphaned(): void {
		$this->assertEquals( 'orphaned', $this->detector->get_id() );
	}

	/**
	 * Test suggest_linking_opportunities returns array.
	 *
	 * Requirement 8.6: THE Orphaned_Detector SHALL provide a "Fix Orphaned Content" guided workflow
	 * Requirement 8.7: THE Orphaned_Detector SHALL analyze content similarity and suggest 5 posts
	 *
	 * @return void
	 */
	public function test_suggest_linking_opportunities_returns_array(): void {
		// Create orphaned post with focus keyword.
		$orphaned_id = wp_insert_post( array(
			'post_title'   => 'SEO Best Practices',
			'post_content' => 'This post discusses SEO best practices and optimization techniques.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		) );

		// Set focus keyword.
		update_post_meta( $orphaned_id, '_meowseo_focus_keyword', 'SEO' );

		// Get suggestions.
		$suggestions = $this->detector->suggest_linking_opportunities( $orphaned_id );

		// Assert suggestions are returned as array.
		$this->assertIsArray( $suggestions );

		// Clean up.
		wp_delete_post( $orphaned_id, true );
	}

	/**
	 * Test suggest_linking_opportunities returns empty array for invalid post.
	 *
	 * @return void
	 */
	public function test_suggest_linking_opportunities_returns_empty_for_invalid_post(): void {
		// Get suggestions for non-existent post.
		$suggestions = $this->detector->suggest_linking_opportunities( 99999 );

		// Assert empty array is returned.
		$this->assertEmpty( $suggestions );
	}

	/**
	 * Test suggest_linking_opportunities returns max 5 suggestions.
	 *
	 * Requirement 8.7: THE Orphaned_Detector SHALL suggest 5 posts
	 *
	 * @return void
	 */
	public function test_suggest_linking_opportunities_returns_max_five(): void {
		// Create orphaned post.
		$orphaned_id = wp_insert_post( array(
			'post_title'   => 'SEO Best Practices',
			'post_content' => 'This post discusses SEO best practices.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		) );

		// Create multiple candidate posts.
		for ( $i = 0; $i < 10; $i++ ) {
			wp_insert_post( array(
				'post_title'   => 'SEO Guide ' . $i,
				'post_content' => 'A guide to SEO optimization.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			) );
		}

		// Get suggestions.
		$suggestions = $this->detector->suggest_linking_opportunities( $orphaned_id );

		// Assert max 5 suggestions are returned.
		$this->assertLessThanOrEqual( 5, count( $suggestions ) );

		// Clean up.
		wp_delete_post( $orphaned_id, true );
	}

	/**
	 * Test boot method registers hooks.
	 *
	 * @return void
	 */
	public function test_boot_registers_hooks(): void {
		// Boot the detector.
		$this->detector->boot();

		// Check that hooks are registered.
		$this->assertTrue( has_action( 'save_post' ) );
		$this->assertTrue( has_action( 'delete_post' ) );
	}
}

