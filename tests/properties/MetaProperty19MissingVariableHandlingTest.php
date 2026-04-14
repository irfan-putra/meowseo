<?php
/**
 * Property 19: Missing Variable Handling
 *
 * Feature: meta-module-rebuild, Property 19: For any title pattern containing
 * variables not present in the context array, those variables SHALL be replaced
 * with empty strings.
 *
 * Validates: Requirements 8.5
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
 * Test Property 19: Missing Variable Handling
 */
class MetaProperty19MissingVariableHandlingTest extends TestCase {
	use TestTrait;

	/**
	 * Test missing variables are replaced with empty strings
	 *
	 * For any pattern with variables not in the context, those variables
	 * should be replaced with empty strings (not left as placeholders).
	 *
	 * @return void
	 */
	public function test_missing_variables_replaced_with_empty_string(): void {
		$this->forAll(
			Generator\elements( 'title', 'term_name', 'author_name', 'term_description' ),
			Generator\string()
		)->then( function( $missing_var, $present_value ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			// Create pattern with missing variable.
			$pattern = '{title} {' . $missing_var . '} {sep} {site_name}';
			$context = array( 'title' => $present_value );

			$result = $patterns->resolve( $pattern, $context );

			// Property: Missing variable placeholder should not remain.
			$this->assertStringNotContainsString( '{' . $missing_var . '}', $result );

			// Property: Result should still be valid (contain present value).
			if ( ! empty( $present_value ) ) {
				$this->assertStringContainsString( $present_value, $result );
			}
		} );
	}

	/**
	 * Test multiple missing variables
	 *
	 * @return void
	 */
	public function test_multiple_missing_variables(): void {
		$this->forAll(
			Generator\string()
		)->then( function( $title ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			// Pattern with multiple missing variables.
			$pattern = '{title} - {term_name} - {author_name} - {term_description}';
			$context = array( 'title' => $title );

			$result = $patterns->resolve( $pattern, $context );

			// Property: No variable placeholders should remain.
			$this->assertStringNotContainsString( '{term_name}', $result );
			$this->assertStringNotContainsString( '{author_name}', $result );
			$this->assertStringNotContainsString( '{term_description}', $result );
		} );
	}

	/**
	 * Test empty context
	 *
	 * @return void
	 */
	public function test_empty_context(): void {
		$this->forAll(
			Generator\elements(
				'{title} {sep} {site_name}',
				'{term_name} Archives',
				'{author_name} Posts'
			)
		)->then( function( $pattern ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			// Empty context.
			$context = array();

			$result = $patterns->resolve( $pattern, $context );

			// Property: No variable placeholders should remain (except built-ins like sep, site_name).
			$this->assertStringNotContainsString( '{title}', $result );
			$this->assertStringNotContainsString( '{term_name}', $result );
			$this->assertStringNotContainsString( '{author_name}', $result );
		} );
	}
}
