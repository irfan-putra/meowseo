<?php
/**
 * Property 28: Title Pattern Round-Trip
 *
 * Feature: meta-module-rebuild, Property 28: For any valid title pattern string,
 * parsing then printing then parsing SHALL produce an equivalent structured
 * representation (parse(p) → print → parse → result, where result ≡ parse(p))
 *
 * Validates: Requirements 12.1, 12.2, 12.3
 *
 * @package MeowSEO
 * @subpackage Tests\Properties
 */

namespace MeowSEO\Tests\Properties;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Title_Patterns;
use MeowSEO\Options;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Test Property 28: Title Pattern Round-Trip
 */
class MetaProperty28TitlePatternRoundTripTest extends TestCase {
	use TestTrait;

	/**
	 * Test title pattern round-trip property
	 *
	 * For any valid pattern, parse → print → parse should produce
	 * an equivalent structure.
	 *
	 * @return void
	 */
	public function test_title_pattern_round_trip(): void {
		$this->forAll(
			Generator\elements(
				'{title} {sep} {site_name}',
				'{site_name} {sep} {tagline}',
				'{term_name} Archives {sep} {site_name}',
				'{author_name} {sep} {site_name}',
				'{current_month} {current_year} Archives',
				'{title} {page} {sep} {site_name}'
			)
		)->then( function( $pattern ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			// Parse.
			$parsed1 = $patterns->parse( $pattern );
			$this->assertIsArray( $parsed1, 'First parse should return array' );

			// Print.
			$printed = $patterns->print( $parsed1 );
			$this->assertEquals( $pattern, $printed, 'Printed pattern should match original' );

			// Parse again.
			$parsed2 = $patterns->parse( $printed );
			$this->assertIsArray( $parsed2, 'Second parse should return array' );

			// Property: Structures should be equivalent.
			$this->assertEquals( $parsed1, $parsed2, 'Round-trip should produce equivalent structure' );
		} );
	}

	/**
	 * Test round-trip with complex patterns
	 *
	 * @return void
	 */
	public function test_round_trip_complex_patterns(): void {
		$this->forAll(
			Generator\elements( 'title', 'sep', 'site_name', 'tagline', 'term_name', 'author_name' ),
			Generator\elements( 'title', 'sep', 'site_name', 'tagline', 'term_name', 'author_name' ),
			Generator\elements( 'title', 'sep', 'site_name', 'tagline', 'term_name', 'author_name' )
		)->then( function( $var1, $var2, $var3 ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			// Build pattern from variables.
			$pattern = '{' . $var1 . '} - {' . $var2 . '} - {' . $var3 . '}';

			// Parse.
			$parsed1 = $patterns->parse( $pattern );
			$this->assertIsArray( $parsed1 );

			// Print.
			$printed = $patterns->print( $parsed1 );

			// Parse again.
			$parsed2 = $patterns->parse( $printed );
			$this->assertIsArray( $parsed2 );

			// Property: Structures should be equivalent.
			$this->assertEquals( $parsed1, $parsed2 );
		} );
	}

	/**
	 * Test round-trip preserves token count
	 *
	 * @return void
	 */
	public function test_round_trip_preserves_token_count(): void {
		$this->forAll(
			Generator\elements(
				'{title} {sep} {site_name}',
				'{site_name} {sep} {tagline}',
				'{term_name} Archives {sep} {site_name}'
			)
		)->then( function( $pattern ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			// Parse.
			$parsed1 = $patterns->parse( $pattern );
			$count1  = count( $parsed1 );

			// Print and parse again.
			$printed = $patterns->print( $parsed1 );
			$parsed2 = $patterns->parse( $printed );
			$count2  = count( $parsed2 );

			// Property: Token count should be preserved.
			$this->assertEquals( $count1, $count2 );
		} );
	}
}
