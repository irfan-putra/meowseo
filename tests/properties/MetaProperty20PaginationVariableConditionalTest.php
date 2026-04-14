<?php
/**
 * Property 20: Pagination Variable Conditional
 *
 * Feature: meta-module-rebuild, Property 20: For any paginated context (e.g., /page/2/),
 * the {page} variable SHALL resolve to "Page N"; for non-paginated contexts, {page}
 * SHALL resolve to an empty string.
 *
 * Validates: Requirements 8.7
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
 * Test Property 20: Pagination Variable Conditional
 */
class MetaProperty20PaginationVariableConditionalTest extends TestCase {
	use TestTrait;

	/**
	 * Test pagination variable conditional behavior
	 *
	 * For paginated contexts, {page} should resolve to "Page N".
	 * For non-paginated contexts, {page} should resolve to empty string.
	 *
	 * @return void
	 */
	public function test_pagination_variable_conditional(): void {
		$this->forAll(
			Generator\choose( 2, 100 ),
			Generator\string()
		)->then( function( $page_number, $title ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$pattern = '{title} {page} {sep} {site_name}';

			// Paginated context.
			$context_paginated = array(
				'title'       => $title,
				'page_number' => $page_number,
			);

			$result_paginated = $patterns->resolve( $pattern, $context_paginated );

			// Property: Should contain "Page N".
			$this->assertStringContainsString( 'Page ' . $page_number, $result_paginated );

			// Non-paginated context.
			$context_non_paginated = array(
				'title' => $title,
			);

			$result_non_paginated = $patterns->resolve( $pattern, $context_non_paginated );

			// Property: Should NOT contain "Page".
			$this->assertStringNotContainsString( 'Page ', $result_non_paginated );
		} );
	}

	/**
	 * Test page 1 is treated as non-paginated
	 *
	 * @return void
	 */
	public function test_page_one_is_non_paginated(): void {
		$this->forAll(
			Generator\string()
		)->then( function( $title ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$pattern = '{title} {page}';
			$context = array(
				'title'       => $title,
				'page_number' => 1,
			);

			$result = $patterns->resolve( $pattern, $context );

			// Property: Page 1 should not show "Page 1".
			$this->assertStringNotContainsString( 'Page ', $result );
		} );
	}

	/**
	 * Test page variable placeholder is removed
	 *
	 * @return void
	 */
	public function test_page_variable_placeholder_removed(): void {
		$this->forAll(
			Generator\oneOf(
				Generator\constant( null ),
				Generator\choose( 2, 100 )
			),
			Generator\string()
		)->then( function( $page_number, $title ) {
			$options  = new Options();
			$patterns = new Title_Patterns( $options );

			$pattern = '{title} {page} {sep} {site_name}';
			$context = array( 'title' => $title );

			if ( $page_number !== null ) {
				$context['page_number'] = $page_number;
			}

			$result = $patterns->resolve( $pattern, $context );

			// Property: {page} placeholder should never remain.
			$this->assertStringNotContainsString( '{page}', $result );
		} );
	}
}
