<?php
/**
 * Property 26: Robots.txt Default Directives Presence
 *
 * Feature: meta-module-rebuild, Property 26: For any robots.txt output,
 * the default directives SHALL always be present.
 *
 * Validates: Requirements 11.4
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
 * Test Property 26: Robots.txt Default Directives Presence
 */
class MetaProperty26RobotsTxtDefaultDirectivesPresenceTest extends WP_UnitTestCase {
	use TestTrait;

	/**
	 * Test default directives always present
	 *
	 * @return void
	 */
	public function test_default_directives_always_present(): void {
		$this->forAll(
			Generator\oneOf(
				Generator\constant( '' ),
				Generator\constant( "Disallow: /private/" ),
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

			// Property: Default directives should always be present.
			$this->assertStringContainsString(
				'Disallow: /wp-admin/',
				$output,
				'Default directive "Disallow: /wp-admin/" should always be present'
			);
			$this->assertStringContainsString(
				'Disallow: /wp-login.php',
				$output,
				'Default directive "Disallow: /wp-login.php" should always be present'
			);
			$this->assertStringContainsString(
				'Disallow: /wp-includes/',
				$output,
				'Default directive "Disallow: /wp-includes/" should always be present'
			);
		} );
	}
}
