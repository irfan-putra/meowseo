<?php
/**
 * Property 25: Robots.txt Custom Directives Inclusion
 *
 * Feature: meta-module-rebuild, Property 25: For any custom directives configured
 * in settings, they SHALL appear in the robots.txt output after default directives
 * and before the sitemap URL.
 *
 * Validates: Requirements 11.3, 11.6
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
 * Test Property 25: Robots.txt Custom Directives Inclusion
 */
class MetaProperty25RobotsTxtCustomDirectivesInclusionTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test custom directives inclusion and ordering
	 *
	 * @return void
	 */
	public function test_custom_directives_inclusion_and_ordering(): void {
		$this->forAll(
			Generator\oneOf(
				Generator\constant( "Disallow: /private/" ),
				Generator\constant( "Disallow: /temp/\nDisallow: /cache/" ),
				Generator\constant( "Allow: /public/\nDisallow: /admin/" ),
				Generator\constant( "User-agent: Googlebot\nDisallow: /test/\nAllow: /test/public/" )
			)
		)->then( function( $custom_directives ) {
			// Set custom directives.
			$options_data = array( 'robots_txt_custom' => $custom_directives );
			update_option( 'meowseo_options', $options_data );

			// Create robots_txt instance.
			$options    = new Options();
			$robots_txt = new Robots_Txt( $options );
			$robots_txt->register();

			// Get robots.txt output.
			$output = apply_filters( 'robots_txt', '', true );

			// Property: Custom directives should be present in output.
			$custom_lines = explode( "\n", trim( $custom_directives ) );
			foreach ( $custom_lines as $line ) {
				$this->assertStringContainsString(
					$line,
					$output,
					"Custom directive '{$line}' should be present in output"
				);
			}

			// Property: Custom directives should come after default directives.
			$default_pos = strpos( $output, 'Disallow: /wp-admin/' );
			$custom_pos  = strpos( $output, $custom_lines[0] );
			$this->assertLessThan(
				$custom_pos,
				$default_pos,
				'Custom directives should come after default directives'
			);

			// Property: Custom directives should come before sitemap URL.
			$sitemap_pos = strpos( $output, 'Sitemap:' );
			$this->assertLessThan(
				$sitemap_pos,
				$custom_pos,
				'Custom directives should come before sitemap URL'
			);
		} );
	}
}
