<?php
/**
 * Property 18: Variable Replacement Completeness
 *
 * Feature: meta-module-rebuild, Property 18: For any title pattern containing
 * supported variables, all variables SHALL be replaced with their corresponding
 * values from the context array.
 *
 * Validates: Requirements 8.1, 8.4, 8.6
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
 * Test Property 18: Variable Replacement Completeness
 */
class MetaProperty18VariableReplacementCompletenessTest extends TestCase {
	use TestTrait;

	/**
	 * Test variable replacement completeness property
	 *
	 * For any pattern with supported variables and a context containing values,
	 * all variables should be replaced and no variable placeholders should remain.
	 *
	 * @return void
	 */
	public function test_variable_replacement_completeness(): void {
		$this->forAll(
			Generator\elements( 'title', 'term_name', 'author_name' ),
			Generator\string()
		)->then( function( $var_name, $value ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$pattern = '{' . $var_name . '} {sep} {site_name}';
			$context = array( $var_name => $value );

			$result = $patterns->resolve( $pattern, $context );

			// Property: No variable placeholders should remain.
			$this->assertStringNotContainsString( '{' . $var_name . '}', $result );

			// Property: The value should be present in the result.
			if ( ! empty( $value ) ) {
				$this->assertStringContainsString( $value, $result );
			}
		} );
	}

	/**
	 * Test all supported variables are replaced
	 *
	 * @return void
	 */
	public function test_all_supported_variables_replaced(): void {
		$this->forAll(
			Generator\string(),
			Generator\string(),
			Generator\string()
		)->then( function( $title, $term_name, $author_name ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$pattern = '{title} - {term_name} - {author_name}';
			$context = array(
				'title'       => $title,
				'term_name'   => $term_name,
				'author_name' => $author_name,
			);

			$result = $patterns->resolve( $pattern, $context );

			// Property: No variable placeholders should remain.
			$this->assertStringNotContainsString( '{title}', $result );
			$this->assertStringNotContainsString( '{term_name}', $result );
			$this->assertStringNotContainsString( '{author_name}', $result );
		} );
	}

	/**
	 * Test separator variable is always replaced
	 *
	 * @return void
	 */
	public function test_separator_variable_replaced(): void {
		$this->forAll(
			Generator\string()
		)->then( function( $title ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$pattern = '{title} {sep} {site_name}';
			$context = array( 'title' => $title );

			$result = $patterns->resolve( $pattern, $context );

			// Property: {sep} should be replaced with separator.
			$this->assertStringNotContainsString( '{sep}', $result );
			$this->assertStringContainsString( $options->get_separator(), $result );
		} );
	}
}
