<?php
/**
 * Plugin Compatibility Integration Tests
 *
 * Tests that the Meta Module works correctly with other WordPress plugins.
 * Requires a real WordPress installation with test plugins installed.
 *
 * @package MeowSEO
 * @subpackage Tests\Integration
 */

namespace MeowSEO\Tests\Integration;

/**
 * Plugin Compatibility Test Case
 *
 * NOTE: These tests require a real WordPress installation with WPML and Polylang
 * plugins installed. They cannot be run with mocked WordPress functions.
 */
class PluginCompatibilityTest extends \WP_UnitTestCase {
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
	 * Test hreflang output with WPML active
	 *
	 * Validates: Requirements 2.9
	 *
	 * @return void
	 */
	public function test_hreflang_output_with_wpml(): void {
		// Check if WPML is installed.
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$this->markTestSkipped( 'WPML is not installed' );
		}

		// Activate WPML.
		activate_plugin( 'sitepress-multilingual-cms/sitepress.php' );

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

		// Should contain hreflang alternate links.
		$this->assertStringContainsString( '<link rel="alternate" hreflang=', $output, 'Should output hreflang alternates with WPML' );
	}

	/**
	 * Test hreflang output with Polylang active
	 *
	 * Validates: Requirements 2.9
	 *
	 * @return void
	 */
	public function test_hreflang_output_with_polylang(): void {
		// Check if Polylang is installed.
		if ( ! function_exists( 'pll_the_languages' ) ) {
			$this->markTestSkipped( 'Polylang is not installed' );
		}

		// Activate Polylang.
		activate_plugin( 'polylang/polylang.php' );

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

		// Should contain hreflang alternate links.
		$this->assertStringContainsString( '<link rel="alternate" hreflang=', $output, 'Should output hreflang alternates with Polylang' );
	}

	/**
	 * Test no hreflang output without multilingual plugins
	 *
	 * Validates: Requirements 2.9
	 *
	 * @return void
	 */
	public function test_no_hreflang_without_multilingual_plugins(): void {
		// Deactivate WPML and Polylang if active.
		deactivate_plugins( array( 'sitepress-multilingual-cms/sitepress.php', 'polylang/polylang.php' ) );

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

		// Should NOT contain hreflang alternate links.
		$this->assertStringNotContainsString( '<link rel="alternate" hreflang=', $output, 'Should not output hreflang alternates without multilingual plugins' );
	}

	/**
	 * Test no conflicts with Yoast SEO
	 *
	 * Validates: Requirements 2.1
	 *
	 * @return void
	 */
	public function test_no_conflicts_with_yoast(): void {
		// Check if Yoast SEO is installed.
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			$this->markTestSkipped( 'Yoast SEO is not installed' );
		}

		// Deactivate Yoast SEO (MeowSEO should be the only active SEO plugin).
		deactivate_plugins( 'wordpress-seo/wp-seo.php' );

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

		// Should have exactly one title tag.
		$title_count = substr_count( $output, '<title>' );
		$this->assertSame( 1, $title_count, 'Should have exactly one title tag with Yoast deactivated' );

		// Should have MeowSEO meta tags.
		$this->assertStringContainsString( '<meta name="robots"', $output, 'Should have MeowSEO robots tag' );
		$this->assertStringContainsString( '<link rel="canonical"', $output, 'Should have MeowSEO canonical tag' );
	}

	/**
	 * Test no conflicts with RankMath
	 *
	 * Validates: Requirements 2.1
	 *
	 * @return void
	 */
	public function test_no_conflicts_with_rankmath(): void {
		// Check if RankMath is installed.
		if ( ! defined( 'RANK_MATH_VERSION' ) ) {
			$this->markTestSkipped( 'RankMath is not installed' );
		}

		// Deactivate RankMath (MeowSEO should be the only active SEO plugin).
		deactivate_plugins( 'seo-by-rank-math/rank-math.php' );

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

		// Should have exactly one title tag.
		$title_count = substr_count( $output, '<title>' );
		$this->assertSame( 1, $title_count, 'Should have exactly one title tag with RankMath deactivated' );

		// Should have MeowSEO meta tags.
		$this->assertStringContainsString( '<meta name="robots"', $output, 'Should have MeowSEO robots tag' );
		$this->assertStringContainsString( '<link rel="canonical"', $output, 'Should have MeowSEO canonical tag' );
	}

	/**
	 * Test document_title_parts filter returns empty array
	 *
	 * Validates: Requirements 1.3
	 *
	 * @return void
	 */
	public function test_document_title_parts_filter_returns_empty(): void {
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

		// Apply document_title_parts filter.
		$parts = apply_filters( 'document_title_parts', array( 'title' => 'Test' ) );

		// Should return empty array.
		$this->assertIsArray( $parts );
		$this->assertEmpty( $parts, 'document_title_parts filter should return empty array' );
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Deactivate test plugins.
		deactivate_plugins(
			array(
				'sitepress-multilingual-cms/sitepress.php',
				'polylang/polylang.php',
				'wordpress-seo/wp-seo.php',
				'seo-by-rank-math/rank-math.php',
			)
		);

		parent::tearDown();
	}
}
