<?php
/**
 * Robots_Txt Test
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use WP_UnitTestCase;
use MeowSEO\Modules\Meta\Robots_Txt;
use MeowSEO\Options;

/**
 * Test Robots_Txt class
 */
class RobotsTxtTest extends WP_UnitTestCase {
	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Robots_Txt instance
	 *
	 * @var Robots_Txt
	 */
	private Robots_Txt $robots_txt;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->options    = new Options();
		$this->robots_txt = new Robots_Txt( $this->options );
	}

	/**
	 * Test filter hook registration
	 *
	 * Requirement 11.1: Hook into robots_txt filter.
	 *
	 * @return void
	 */
	public function test_hook_registration(): void {
		// Register hooks.
		$this->robots_txt->register();

		// Verify filter is registered.
		$this->assertNotFalse(
			has_filter( 'robots_txt', array( $this->robots_txt, 'filter_robots_txt' ) ),
			'robots_txt filter should be registered'
		);
	}

	/**
	 * Test default directives output
	 *
	 * Requirement 11.4: Include default directives.
	 *
	 * @return void
	 */
	public function test_default_directives(): void {
		// Register hooks.
		$this->robots_txt->register();

		// Get robots.txt output.
		$output = apply_filters( 'robots_txt', '', true );

		// Verify default directives are present.
		$this->assertStringContainsString( 'User-agent: *', $output, 'Should contain User-agent declaration' );
		$this->assertStringContainsString( 'Disallow: /wp-admin/', $output, 'Should contain wp-admin disallow' );
		$this->assertStringContainsString( 'Disallow: /wp-login.php', $output, 'Should contain wp-login disallow' );
		$this->assertStringContainsString( 'Disallow: /wp-includes/', $output, 'Should contain wp-includes disallow' );
	}

	/**
	 * Test custom directives appending
	 *
	 * Requirement 11.3: Provide custom directives from settings.
	 * Requirement 11.6: Append custom directives after defaults, before sitemap.
	 *
	 * @return void
	 */
	public function test_custom_directives(): void {
		// Set custom directives in options.
		$custom_directives = "Disallow: /private/\nDisallow: /temp/";
		$options_data = array( 'robots_txt_custom' => $custom_directives );
		update_option( 'meowseo_options', $options_data );

		// Recreate options and robots_txt to pick up new value.
		$options = new Options();
		$robots_txt = new Robots_Txt( $options );
		$robots_txt->register();

		// Get robots.txt output.
		$output = apply_filters( 'robots_txt', '', true );

		// Verify custom directives are present.
		$this->assertStringContainsString( 'Disallow: /private/', $output, 'Should contain custom directive 1' );
		$this->assertStringContainsString( 'Disallow: /temp/', $output, 'Should contain custom directive 2' );

		// Verify order: default directives should come before custom directives.
		$default_pos = strpos( $output, 'Disallow: /wp-admin/' );
		$custom_pos  = strpos( $output, 'Disallow: /private/' );
		$this->assertLessThan( $custom_pos, $default_pos, 'Default directives should come before custom directives' );

		// Verify custom directives come before sitemap.
		$sitemap_pos = strpos( $output, 'Sitemap:' );
		$this->assertLessThan( $sitemap_pos, $custom_pos, 'Custom directives should come before sitemap URL' );
	}

	/**
	 * Test sitemap URL inclusion
	 *
	 * Requirement 11.2: Automatically append sitemap index URL.
	 *
	 * @return void
	 */
	public function test_sitemap_url(): void {
		// Register hooks.
		$this->robots_txt->register();

		// Get robots.txt output.
		$output = apply_filters( 'robots_txt', '', true );

		// Verify sitemap URL is present.
		$expected_sitemap = 'Sitemap: ' . home_url( '/meowseo-sitemap.xml' );
		$this->assertStringContainsString( $expected_sitemap, $output, 'Should contain sitemap URL' );

		// Verify sitemap is at the end (after default directives).
		$default_pos = strpos( $output, 'Disallow: /wp-admin/' );
		$sitemap_pos = strpos( $output, 'Sitemap:' );
		$this->assertGreaterThan( $default_pos, $sitemap_pos, 'Sitemap URL should come after default directives' );
	}

	/**
	 * Test robots.txt not output when site is not public
	 *
	 * @return void
	 */
	public function test_not_public_site(): void {
		// Register hooks.
		$this->robots_txt->register();

		// Get robots.txt output with public=false.
		$original_output = 'Original output';
		$output          = apply_filters( 'robots_txt', $original_output, false );

		// Verify original output is returned unchanged.
		$this->assertEquals( $original_output, $output, 'Should return original output when site is not public' );
	}

	/**
	 * Test formatting with proper line breaks
	 *
	 * Requirement 11.5: Format with proper line breaks.
	 *
	 * @return void
	 */
	public function test_formatting(): void {
		// Register hooks.
		$this->robots_txt->register();

		// Get robots.txt output.
		$output = apply_filters( 'robots_txt', '', true );

		// Verify sections are separated by double line breaks.
		$this->assertStringContainsString( "\n\n", $output, 'Should contain double line breaks between sections' );

		// Verify ends with single newline.
		$this->assertStringEndsWith( "\n", $output, 'Should end with newline' );
	}
}
