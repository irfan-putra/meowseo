<?php
/**
 * Analysis Engine Tests
 *
 * Unit tests for the Analysis_Engine class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use MeowSEO\Modules\Analysis\Analysis_Engine;
use MeowSEO\Modules\Analysis\Fix_Explanation_Provider;
use PHPUnit\Framework\TestCase;

/**
 * AnalysisEngineTest class
 *
 * @since 1.0.0
 */
class AnalysisEngineTest extends TestCase {

	/**
	 * Analysis engine instance
	 *
	 * @var Analysis_Engine
	 */
	private Analysis_Engine $engine;

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
		$this->engine = new Analysis_Engine( $this->provider );
	}

	/**
	 * Test Analysis_Engine instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( Analysis_Engine::class, $this->engine );
	}

	/**
	 * Test analyze method returns expected structure
	 *
	 * @return void
	 */
	public function test_analyze_returns_expected_structure(): void {
		$result = $this->engine->analyze( 1, array(
			'title' => 'Test Title',
			'description' => 'Test description',
			'content' => '<p>Test content with some words</p>',
			'slug' => 'test-slug',
			'focus_keyword' => 'test',
		) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'seo_results', $result );
		$this->assertArrayHasKey( 'readability_results', $result );
		$this->assertArrayHasKey( 'seo_score', $result );
		$this->assertArrayHasKey( 'readability_score', $result );
		$this->assertArrayHasKey( 'seo_color', $result );
		$this->assertArrayHasKey( 'readability_color', $result );
	}

	/**
	 * Test analyze returns arrays for results
	 *
	 * @return void
	 */
	public function test_analyze_returns_arrays_for_results(): void {
		$result = $this->engine->analyze( 1, array(
			'title' => 'Test Title',
			'description' => 'Test description',
			'content' => '<p>Test content with some words</p>',
			'slug' => 'test-slug',
			'focus_keyword' => 'test',
		) );

		$this->assertIsArray( $result['seo_results'] );
		$this->assertIsArray( $result['readability_results'] );
	}

	/**
	 * Test fix explanations are added for failing checks
	 *
	 * @return void
	 */
	public function test_fix_explanations_added_for_failing_checks(): void {
		// Create data with a title that's too short (will fail)
		$result = $this->engine->analyze( 1, array(
			'title' => 'Short',
			'description' => 'Test description that is long enough',
			'content' => '<p>Test content with some words to make it longer</p>',
			'slug' => 'test-slug',
			'focus_keyword' => 'test',
		) );

		// Check if any SEO result has a fix_explanation
		$has_fix_explanation = false;
		foreach ( $result['seo_results'] as $check ) {
			if ( ! $check['pass'] && isset( $check['fix_explanation'] ) ) {
				$has_fix_explanation = true;
				$this->assertNotEmpty( $check['fix_explanation'] );
				break;
			}
		}

		$this->assertTrue( $has_fix_explanation, 'At least one failing check should have a fix_explanation' );
	}

	/**
	 * Test fix explanations contain expected HTML structure
	 *
	 * @return void
	 */
	public function test_fix_explanations_contain_expected_structure(): void {
		$result = $this->engine->analyze( 1, array(
			'title' => 'Short',
			'description' => 'Test description that is long enough',
			'content' => '<p>Test content with some words to make it longer</p>',
			'slug' => 'test-slug',
			'focus_keyword' => 'test',
		) );

		// Find a check with fix_explanation
		foreach ( $result['seo_results'] as $check ) {
			if ( ! $check['pass'] && isset( $check['fix_explanation'] ) ) {
				$explanation = $check['fix_explanation'];
				$this->assertStringContainsString( 'meowseo-fix-explanation', $explanation );
				$this->assertStringContainsString( 'issue', $explanation );
				$this->assertStringContainsString( 'fix', $explanation );
				break;
			}
		}
	}

	/**
	 * Test passing checks don't have fix explanations
	 *
	 * @return void
	 */
	public function test_passing_checks_no_fix_explanations(): void {
		// Create data with good values
		$result = $this->engine->analyze( 1, array(
			'title' => 'This is a good test title for SEO',
			'description' => 'This is a good description that is between 50 and 160 characters long',
			'content' => '<p>This is test content with the keyword test included multiple times. Test is important for SEO. We test everything here.</p>',
			'slug' => 'test-slug',
			'focus_keyword' => 'test',
		) );

		// Check that passing checks don't have fix_explanation
		foreach ( $result['seo_results'] as $check ) {
			if ( $check['pass'] ) {
				$this->assertArrayNotHasKey( 'fix_explanation', $check );
			}
		}
	}

	/**
	 * Test scores are numeric
	 *
	 * @return void
	 */
	public function test_scores_are_numeric(): void {
		$result = $this->engine->analyze( 1, array(
			'title' => 'Test Title',
			'description' => 'Test description',
			'content' => '<p>Test content with some words</p>',
			'slug' => 'test-slug',
			'focus_keyword' => 'test',
		) );

		$this->assertIsInt( $result['seo_score'] );
		$this->assertIsInt( $result['readability_score'] );
		$this->assertGreaterThanOrEqual( 0, $result['seo_score'] );
		$this->assertLessThanOrEqual( 100, $result['seo_score'] );
		$this->assertGreaterThanOrEqual( 0, $result['readability_score'] );
		$this->assertLessThanOrEqual( 100, $result['readability_score'] );
	}

	/**
	 * Test colors are valid
	 *
	 * @return void
	 */
	public function test_colors_are_valid(): void {
		$result = $this->engine->analyze( 1, array(
			'title' => 'Test Title',
			'description' => 'Test description',
			'content' => '<p>Test content with some words</p>',
			'slug' => 'test-slug',
			'focus_keyword' => 'test',
		) );

		$valid_colors = array( 'red', 'orange', 'green' );
		$this->assertContains( $result['seo_color'], $valid_colors );
		$this->assertContains( $result['readability_color'], $valid_colors );
	}
}
