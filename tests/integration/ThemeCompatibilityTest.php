<?php
/**
 * Theme Compatibility Integration Tests
 *
 * Tests that the Meta Module works correctly with popular WordPress themes.
 * Requires a real WordPress installation with test themes installed.
 *
 * @package MeowSEO
 * @subpackage Tests\Integration
 */

namespace MeowSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Theme Compatibility Test Case
 *
 * NOTE: These tests require a real WordPress installation and cannot be run
 * with mocked WordPress functions. They should be run in a CI/CD environment
 * with WordPress Test Suite installed.
 */
class ThemeCompatibilityTest extends TestCase {
	/**
	 * Test themes to verify compatibility
	 *
	 * @var array
	 */
	private array $test_themes = array(
		'twentytwentyfour',
		'astra',
		'generatepress',
	);

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Skip if WordPress test framework is not available
		if ( ! class_exists( '\WP_UnitTestCase' ) || ! function_exists( 'activate_plugin' ) ) {
			$this->markTestSkipped( 'WordPress test framework is not available. These tests require a full WordPress installation with the WordPress Test Suite.' );
		}

		// Activate MeowSEO plugin.
		activate_plugin( 'meowseo/meowseo.php' );

		// Boot the plugin.
		\MeowSEO\Plugin::instance()->boot();
	}

	/**
	 * Test no duplicate title tags with Twenty Twenty-Four theme
	 *
	 * Validates: Requirements 2.1, 10.8
	 *
	 * @return void
	 */
	public function test_no_duplicate_title_tags_twentytwentyfour(): void {
		// Switch to Twenty Twenty-Four theme.
		switch_theme( 'twentytwentyfour' );

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
	}

	/**
	 * Test no duplicate meta description tags with Astra theme
	 *
	 * Validates: Requirements 2.1, 10.8
	 *
	 * @return void
	 */
	public function test_no_duplicate_meta_description_astra(): void {
		// Switch to Astra theme.
		switch_theme( 'astra' );

		// Create a test post with meta description.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

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
	 * Test correct hook priority with GeneratePress theme
	 *
	 * Validates: Requirements 2.1, 10.8
	 *
	 * @return void
	 */
	public function test_correct_hook_priority_generatepress(): void {
		// Switch to GeneratePress theme.
		switch_theme( 'generatepress' );

		// Check that our wp_head hook has priority 1.
		$priority = has_action( 'wp_head', array( \MeowSEO\Plugin::instance()->get_module( 'meta' ), 'output_head_tags' ) );

		$this->assertSame( 1, $priority, 'wp_head hook should have priority 1' );
	}

	/**
	 * Test meta tag output order with all themes
	 *
	 * Validates: Requirements 2.1
	 *
	 * @return void
	 */
	public function test_meta_tag_output_order_all_themes(): void {
		foreach ( $this->test_themes as $theme ) {
			// Skip if theme is not installed.
			if ( ! wp_get_theme( $theme )->exists() ) {
				$this->markTestSkipped( "Theme {$theme} is not installed" );
				continue;
			}

			// Switch to theme.
			switch_theme( $theme );

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

			// Verify tag order: Title → Description → Robots → Canonical → OG → Twitter → Hreflang.
			$title_pos       = strpos( $output, '<title>' );
			$description_pos = strpos( $output, '<meta name="description"' );
			$robots_pos      = strpos( $output, '<meta name="robots"' );
			$canonical_pos   = strpos( $output, '<link rel="canonical"' );
			$og_pos          = strpos( $output, '<meta property="og:' );

			// Title should come first.
			$this->assertNotFalse( $title_pos, "Title tag should be present in {$theme}" );

			// If description exists, it should come after title.
			if ( false !== $description_pos ) {
				$this->assertGreaterThan( $title_pos, $description_pos, "Description should come after title in {$theme}" );
			}

			// Robots should come after title.
			if ( false !== $robots_pos ) {
				$this->assertGreaterThan( $title_pos, $robots_pos, "Robots should come after title in {$theme}" );
			}

			// Canonical should come after robots.
			if ( false !== $canonical_pos && false !== $robots_pos ) {
				$this->assertGreaterThan( $robots_pos, $canonical_pos, "Canonical should come after robots in {$theme}" );
			}

			// OG tags should come after canonical.
			if ( false !== $og_pos && false !== $canonical_pos ) {
				$this->assertGreaterThan( $canonical_pos, $og_pos, "OG tags should come after canonical in {$theme}" );
			}
		}
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Switch back to default theme.
		switch_theme( WP_DEFAULT_THEME );

		parent::tearDown();
	}
}
