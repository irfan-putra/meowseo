<?php
/**
 * Property 29: Invalid Pattern Error Handling
 *
 * Feature: meta-module-rebuild, Property 29: For any title pattern with invalid
 * syntax (unbalanced braces, unsupported variables), the parse() method SHALL
 * return an error object with a descriptive message.
 *
 * Validates: Requirements 12.4, 12.5, 12.6
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
 * Test Property 29: Invalid Pattern Error Handling
 */
class MetaProperty29InvalidPatternErrorHandlingTest extends TestCase {
	use TestTrait;

	/**
	 * Test unbalanced braces return error
	 *
	 * For any pattern with unbalanced braces, parse() should return
	 * an error object with descriptive message.
	 *
	 * @return void
	 */
	public function test_unbalanced_braces_return_error(): void {
		$this->forAll(
			Generator\elements(
				'{title {sep} {site_name}',
				'{title} {sep {site_name}',
				'{title} {sep} {site_name',
				'title} {sep} {site_name}',
				'{title {sep {site_name'
			)
		)->then( function( $invalid_pattern ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$result = $patterns->parse( $invalid_pattern );

			// Property: Should return error object.
			$this->assertIsObject( $result );
			$this->assertTrue( $result->error );
			$this->assertNotEmpty( $result->message );
			$this->assertStringContainsString( 'Unbalanced', $result->message );
		} );
	}

	/**
	 * Test unsupported variables return error
	 *
	 * @return void
	 */
	public function test_unsupported_variables_return_error(): void {
		$this->forAll(
			Generator\elements(
				'invalid_var',
				'unknown',
				'post_id',
				'user_name',
				'category_name'
			)
		)->then( function( $invalid_var ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$invalid_pattern = '{title} {sep} {' . $invalid_var . '}';
			$result          = $patterns->parse( $invalid_pattern );

			// Property: Should return error object.
			$this->assertIsObject( $result );
			$this->assertTrue( $result->error );
			$this->assertNotEmpty( $result->message );
			$this->assertStringContainsString( 'Unsupported variable', $result->message );
		} );
	}

	/**
	 * Test validate method returns error for invalid patterns
	 *
	 * @return void
	 */
	public function test_validate_returns_error_for_invalid_patterns(): void {
		$this->forAll(
			Generator\elements(
				'{title {sep}',
				'{invalid_var}',
				'{title} {unknown}',
				'title} {sep'
			)
		)->then( function( $invalid_pattern ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$result = $patterns->validate( $invalid_pattern );

			// Property: Should return error object (not true).
			$this->assertIsObject( $result );
			$this->assertTrue( $result->error );
		} );
	}

	/**
	 * Test validate method returns true for valid patterns
	 *
	 * @return void
	 */
	public function test_validate_returns_true_for_valid_patterns(): void {
		$this->forAll(
			Generator\elements(
				'{title} {sep} {site_name}',
				'{site_name} {sep} {tagline}',
				'{term_name} Archives',
				'{author_name} Posts',
				'{current_year} {current_month}'
			)
		)->then( function( $valid_pattern ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$result = $patterns->validate( $valid_pattern );

			// Property: Should return true for valid patterns.
			$this->assertTrue( $result );
		} );
	}

	/**
	 * Test error messages are descriptive
	 *
	 * @return void
	 */
	public function test_error_messages_are_descriptive(): void {
		$this->forAll(
			Generator\elements(
				array( '{title {sep}', 'Unbalanced' ),
				array( '{invalid}', 'Unsupported' )
			)
		)->then( function( $test_case ) {
			list( $invalid_pattern, $expected_keyword ) = $test_case;

			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$result = $patterns->parse( $invalid_pattern );

			// Property: Error message should contain expected keyword.
			$this->assertIsObject( $result );
			$this->assertTrue( $result->error );
			$this->assertStringContainsString( $expected_keyword, $result->message );
		} );
	}
}
