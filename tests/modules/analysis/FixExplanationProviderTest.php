<?php
/**
 * Fix Explanation Provider Tests
 *
 * Unit tests for the Fix_Explanation_Provider class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use MeowSEO\Modules\Analysis\Fix_Explanation_Provider;
use PHPUnit\Framework\TestCase;

/**
 * FixExplanationProviderTest class
 *
 * @since 1.0.0
 */
class FixExplanationProviderTest extends TestCase {

	/**
	 * Fix explanation provider instance
	 *
	 * @var Fix_Explanation_Provider
	 */
	private Fix_Explanation_Provider $provider;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new Fix_Explanation_Provider();
	}

	/**
	 * Test Fix_Explanation_Provider instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( Fix_Explanation_Provider::class, $this->provider );
	}

	/**
	 * Test get_explanation returns empty string for unknown analyzer ID
	 *
	 * Validates Requirement 6.3: Handle unknown analyzer IDs gracefully.
	 *
	 * @return void
	 */
	public function test_get_explanation_returns_empty_for_unknown_analyzer(): void {
		$explanation = $this->provider->get_explanation( 'unknown_analyzer_id', array() );

		$this->assertIsString( $explanation );
		$this->assertEmpty( $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for title_too_short
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.4.
	 *
	 * @return void
	 */
	public function test_get_explanation_title_too_short(): void {
		$context = array(
			'current_length' => 25,
			'min_length'     => 30,
			'max_length'     => 60,
			'keyword'        => 'test keyword',
		);

		$explanation = $this->provider->get_explanation( 'title_too_short', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( 'meowseo-fix-explanation', $explanation );
		$this->assertStringContainsString( 'issue', $explanation );
		$this->assertStringContainsString( 'fix', $explanation );
		$this->assertStringContainsString( '25', $explanation );
		$this->assertStringContainsString( '30', $explanation );
		$this->assertStringContainsString( '60', $explanation );
		$this->assertStringContainsString( 'test keyword', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for title_too_long
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.5.
	 *
	 * @return void
	 */
	public function test_get_explanation_title_too_long(): void {
		$context = array(
			'current_length' => 75,
			'max_length'     => 60,
		);

		$explanation = $this->provider->get_explanation( 'title_too_long', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( '75', $explanation );
		$this->assertStringContainsString( '60', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for keyword_missing_title
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.6.
	 *
	 * @return void
	 */
	public function test_get_explanation_keyword_missing_title(): void {
		$context = array(
			'keyword' => 'focus keyword',
		);

		$explanation = $this->provider->get_explanation( 'keyword_missing_title', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( 'focus keyword', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for keyword_missing_first_paragraph
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.7.
	 *
	 * @return void
	 */
	public function test_get_explanation_keyword_missing_first_paragraph(): void {
		$context = array(
			'keyword' => 'focus keyword',
		);

		$explanation = $this->provider->get_explanation( 'keyword_missing_first_paragraph', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( 'focus keyword', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for description_missing
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.8.
	 *
	 * @return void
	 */
	public function test_get_explanation_description_missing(): void {
		$context = array(
			'keyword' => 'focus keyword',
		);

		$explanation = $this->provider->get_explanation( 'description_missing', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( 'focus keyword', $explanation );
		$this->assertStringContainsString( '150-160', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for content_too_short
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.9.
	 *
	 * @return void
	 */
	public function test_get_explanation_content_too_short(): void {
		$context = array(
			'current_words' => 150,
			'min_words'     => 300,
			'keyword'       => 'focus keyword',
		);

		$explanation = $this->provider->get_explanation( 'content_too_short', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( '150', $explanation );
		$this->assertStringContainsString( '300', $explanation );
		$this->assertStringContainsString( 'focus keyword', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for keyword_density_low
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.10.
	 *
	 * @return void
	 */
	public function test_get_explanation_keyword_density_low(): void {
		$context = array(
			'keyword'       => 'focus keyword',
			'current_density' => 0.5,
			'target_min'    => 1,
			'target_max'    => 3,
		);

		$explanation = $this->provider->get_explanation( 'keyword_density_low', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( 'focus keyword', $explanation );
		$this->assertStringContainsString( '0.5', $explanation );
		$this->assertStringContainsString( '1', $explanation );
		$this->assertStringContainsString( '3', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for keyword_density_high
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.11.
	 *
	 * @return void
	 */
	public function test_get_explanation_keyword_density_high(): void {
		$context = array(
			'keyword'         => 'focus keyword',
			'current_density' => 5.5,
			'target_max'      => 3,
		);

		$explanation = $this->provider->get_explanation( 'keyword_density_high', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( 'focus keyword', $explanation );
		$this->assertStringContainsString( '5.5', $explanation );
		$this->assertStringContainsString( '3', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for keyword_missing_headings
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.12.
	 *
	 * @return void
	 */
	public function test_get_explanation_keyword_missing_headings(): void {
		$context = array(
			'keyword' => 'focus keyword',
		);

		$explanation = $this->provider->get_explanation( 'keyword_missing_headings', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( 'focus keyword', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for images_missing_alt
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.13.
	 *
	 * @return void
	 */
	public function test_get_explanation_images_missing_alt(): void {
		$context = array(
			'count'   => 3,
			'keyword' => 'focus keyword',
		);

		$explanation = $this->provider->get_explanation( 'images_missing_alt', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( '3', $explanation );
		$this->assertStringContainsString( 'focus keyword', $explanation );
	}

	/**
	 * Test get_explanation returns formatted explanation for slug_not_optimized
	 *
	 * Validates Requirement 6.1, 6.2, 6.3, 6.14.
	 *
	 * @return void
	 */
	public function test_get_explanation_slug_not_optimized(): void {
		$context = array(
			'keyword' => 'focus keyword',
		);

		$explanation = $this->provider->get_explanation( 'slug_not_optimized', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		$this->assertStringContainsString( 'focus keyword', $explanation );
		$this->assertStringContainsString( 'focus-keyword', $explanation ); // keyword_slug
	}

	/**
	 * Test get_explanation includes site name in output
	 *
	 * Validates Requirement 6.2: Explanations include site context.
	 *
	 * @return void
	 */
	public function test_get_explanation_includes_site_name(): void {
		$context = array(
			'keyword' => 'focus keyword',
		);

		$explanation = $this->provider->get_explanation( 'keyword_missing_title', $context );

		$site_name = get_bloginfo( 'name' );
		$this->assertStringContainsString( $site_name, $explanation );
	}

	/**
	 * Test get_explanation with empty context
	 *
	 * Validates Requirement 6.3: Handle missing context gracefully.
	 *
	 * @return void
	 */
	public function test_get_explanation_with_empty_context(): void {
		$explanation = $this->provider->get_explanation( 'title_too_short', array() );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		// Should still have structure even with empty context
		$this->assertStringContainsString( 'meowseo-fix-explanation', $explanation );
	}

	/**
	 * Test get_explanation escapes HTML in output
	 *
	 * Validates Requirement 6.2: Explanations are safe for display.
	 *
	 * @return void
	 */
	public function test_get_explanation_escapes_html(): void {
		$context = array(
			'keyword' => '<script>alert("xss")</script>',
		);

		$explanation = $this->provider->get_explanation( 'keyword_missing_title', $context );

		// Should not contain unescaped script tags
		$this->assertStringNotContainsString( '<script>', $explanation );
		$this->assertStringContainsString( '&lt;script&gt;', $explanation );
	}

	/**
	 * Test get_explanation with special characters in keyword
	 *
	 * Validates Requirement 6.2: Explanations handle special characters.
	 *
	 * @return void
	 */
	public function test_get_explanation_with_special_characters(): void {
		$context = array(
			'keyword' => 'keyword & "special" \'chars\'',
		);

		$explanation = $this->provider->get_explanation( 'keyword_missing_title', $context );

		$this->assertIsString( $explanation );
		$this->assertNotEmpty( $explanation );
		// Should be properly escaped
		$this->assertStringContainsString( '&amp;', $explanation );
	}

	/**
	 * Test get_explanation returns consistent format
	 *
	 * Validates Requirement 6.1, 6.2: Explanations have consistent structure.
	 *
	 * @return void
	 */
	public function test_get_explanation_consistent_format(): void {
		$analyzer_ids = array(
			'title_too_short',
			'title_too_long',
			'keyword_missing_title',
			'keyword_missing_first_paragraph',
			'description_missing',
			'content_too_short',
			'keyword_density_low',
			'keyword_density_high',
			'keyword_missing_headings',
			'images_missing_alt',
			'slug_not_optimized',
		);

		$context = array(
			'keyword'         => 'test',
			'current_length'  => 50,
			'min_length'      => 30,
			'max_length'      => 60,
			'current_words'   => 100,
			'min_words'       => 300,
			'current_density' => 2,
			'target_min'      => 1,
			'target_max'      => 3,
			'count'           => 5,
		);

		foreach ( $analyzer_ids as $analyzer_id ) {
			$explanation = $this->provider->get_explanation( $analyzer_id, $context );

			$this->assertIsString( $explanation );
			$this->assertNotEmpty( $explanation );
			$this->assertStringContainsString( 'meowseo-fix-explanation', $explanation );
			$this->assertStringContainsString( 'issue', $explanation );
			$this->assertStringContainsString( 'fix', $explanation );
			$this->assertStringContainsString( 'How to fix:', $explanation );
		}
	}
}
