<?php
/**
 * Property 24: Robots.txt Sitemap URL Presence
 *
 * Feature: meta-module-rebuild, Property 24: For any robots.txt output,
 * the sitemap index URL SHALL always be present at the end of the output.
 *
 * Validates: Requirements 11.2
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
 * Test Property 24: Robots.txt Sitemap URL Presence
 */
class MetaProperty24RobotsTxtSitemapURLPresenceTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test sitemap URL always present
	 *
	 * @return void
	 */
	public function test_sitemap_url_always_present(): void {
		$this->forAll(
			Generator\oneOf(
				Generator\constant( '' ),
				Generator\constant( "Disallow: /private/\nDisallow: /temp/" ),
				Generator\constant( "Allow: /public/\nDisallow: /admin/" ),
				Generator\constant( "User-agent: Googlebot\nDisallow: /test/" )
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

			// Property: Sitemap URL should always be present.
			$expected_sitemap = 'Sitemap: ' . home_url( '/meowseo-sitemap.xml' );
			$this->assertStringContainsString(
				$expected_sitemap,
				$output,
				'Sitemap URL should always be present in robots.txt output'
			);

			// Property: Sitemap URL should be at the end.
			$sitemap_pos = strpos( $output, 'Sitemap:' );
			$this->assertGreaterThan(
				0,
				$sitemap_pos,
				'Sitemap URL should be found in output'
			);

			// Verify sitemap is after default directives.
			$default_pos = strpos( $output, 'User-agent: *' );
			$this->assertLessThan(
				$sitemap_pos,
				$default_pos,
				'Sitemap URL should come after default directives'
			);
		} );
	}
}
