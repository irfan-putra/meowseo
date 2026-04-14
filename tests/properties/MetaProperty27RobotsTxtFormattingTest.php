<?php
/**
 * Property 27: Robots.txt Formatting
 *
 * Feature: meta-module-rebuild, Property 27: For any robots.txt output,
 * it SHALL include a User-agent: * declaration at the beginning and proper
 * line breaks between directives.
 *
 * Validates: Requirements 11.5
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use WP_UnitTestCase;
use MeowSEO\Modules\Meta\Robots_Txt;
use MeowSEO\Options;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Test Property 27: Robots.txt Formatting
 */
class MetaProperty27RobotsTxtFormattingTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test robots.txt formatting
	 *
	 * @return void
	 */
	public function test_robots_txt_formatting(): void {
		$this->forAll(
			Generator\oneOf(
				Generator\constant( '' ),
				Generator\constant( "Disallow: /private/" ),
				Generator\constant( "Allow: /public/\nDisallow: /admin/" )
			)
		)->then( function( $custom_directives ) {
			// Set custom directives (may be empty).
			$options_data = array( 'robots_txt_custom' => $custom_directives );
			update_option( 'meowseo_options', $options_data );

			// Create robots_txt instance.
			$options    = new Options();
			$robots_txt = new Robots_Txt( $options );
			$robots_txt->register();

			// Get robots.txt output.
			$output = apply_filters( 'robots_txt', '', true );

			// Property: Should start with User-agent: * declaration.
			$this->assertStringStartsWith(
				'User-agent: *',
				$output,
				'robots.txt should start with "User-agent: *" declaration'
			);

			// Property: Should contain double line breaks between sections.
			$this->assertStringContainsString(
				"\n\n",
				$output,
				'robots.txt should contain double line breaks between sections'
			);

			// Property: Should end with a newline.
			$this->assertStringEndsWith(
				"\n",
				$output,
				'robots.txt should end with a newline'
			);

			// Property: User-agent should be at the beginning of default directives.
			$lines = explode( "\n", $output );
			$this->assertEquals(
				'User-agent: *',
				$lines[0],
				'First line should be "User-agent: *"'
			);
		} );
	}
}
