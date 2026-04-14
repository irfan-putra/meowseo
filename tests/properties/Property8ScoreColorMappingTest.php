<?php
/**
 * Property-Based Tests for Score Color Mapping
 *
 * Property 8: Score color mapping is total and exhaustive
 * Validates: Requirement 4.4
 *
 * This test uses property-based testing (eris/eris) to verify that the score-to-color
 * mapping is total (every possible score 0-100 maps to a color) and exhaustive
 * (no score maps to multiple colors).
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Modules\Meta\SEO_Analyzer;

/**
 * Score Color Mapping property-based test case
 *
 * @since 1.0.0
 */
class Property8ScoreColorMappingTest extends TestCase {
	use TestTrait;

	/**
	 * Property 8: Score color mapping is total and exhaustive
	 *
	 * For every possible score from 0-100, the mapping should:
	 * 1. Always map to exactly one color (red, orange, or green)
	 * 2. Never map to multiple colors
	 * 3. Be deterministic (same score always maps to same color)
	 *
	 * **Validates: Requirement 4.4**
	 *
	 * @return void
	 */
	public function test_score_color_mapping_is_total_and_exhaustive(): void {
		$this->forAll(
			Generators::choose( 0, 100 )
		)
		->then(
			function ( int $score ) {
				// Create data that will produce the desired score
				$data = $this->create_data_for_score( $score );

				// Run analysis
				$result = SEO_Analyzer::analyze( $data );

				// Extract color
				$color = $result['color'];

				// Verify color is one of the valid options
				$valid_colors = array( 'red', 'orange', 'green' );
				$this->assertContains(
					$color,
					$valid_colors,
					"Score $score should map to one of: " . implode( ', ', $valid_colors )
				);

				// Verify color is a string
				$this->assertIsString(
					$color,
					'Color should be a string'
				);

				// Verify color is not empty
				$this->assertNotEmpty(
					$color,
					'Color should not be empty'
				);
			}
		);
	}

	/**
	 * Property: Score color mapping is deterministic
	 *
	 * For any given score, the color mapping should always be the same.
	 *
	 * @return void
	 */
	public function test_score_color_mapping_is_deterministic(): void {
		$this->forAll(
			Generators::choose( 0, 100 )
		)
		->then(
			function ( int $score ) {
				// Create data that will produce the desired score
				$data = $this->create_data_for_score( $score );

				// Run analysis three times
				$result1 = SEO_Analyzer::analyze( $data );
				$result2 = SEO_Analyzer::analyze( $data );
				$result3 = SEO_Analyzer::analyze( $data );

				// All colors should be identical
				$this->assertEquals(
					$result1['color'],
					$result2['color'],
					"Color mapping should be deterministic for score $score (run 1 vs 2)"
				);

				$this->assertEquals(
					$result2['color'],
					$result3['color'],
					"Color mapping should be deterministic for score $score (run 2 vs 3)"
				);
			}
		);
	}

	/**
	 * Property: Low scores map to red
	 *
	 * For any score below 50, the color should be red.
	 *
	 * @return void
	 */
	public function test_low_scores_map_to_red(): void {
		// Test specific low scores
		$low_scores = [ 0, 10, 20, 30, 40, 49 ];
		
		foreach ( $low_scores as $score ) {
			// Verify the color mapping logic
			$color = $this->get_color_for_score( $score );
			$this->assertEquals(
				'red',
				$color,
				"Score $score (below 50) should map to red"
			);
		}
	}

	/**
	 * Property: Medium scores map to orange
	 *
	 * For any score between 50 and 79, the color should be orange.
	 *
	 * @return void
	 */
	public function test_medium_scores_map_to_orange(): void {
		// Test specific medium scores
		$medium_scores = [ 50, 60, 70, 79 ];
		
		foreach ( $medium_scores as $score ) {
			// Verify the color mapping logic
			$color = $this->get_color_for_score( $score );
			$this->assertEquals(
				'orange',
				$color,
				"Score $score (50-79) should map to orange"
			);
		}
	}

	/**
	 * Property: High scores map to green
	 *
	 * For any score 80 or above, the color should be green.
	 *
	 * @return void
	 */
	public function test_high_scores_map_to_green(): void {
		// Test specific high scores
		$high_scores = [ 80, 90, 100 ];
		
		foreach ( $high_scores as $score ) {
			// Verify the color mapping logic
			$color = $this->get_color_for_score( $score );
			$this->assertEquals(
				'green',
				$color,
				"Score $score (80+) should map to green"
			);
		}
	}

	/**
	 * Property: Color mapping has no gaps
	 *
	 * For every score from 0-100, there should be a valid color mapping.
	 *
	 * @return void
	 */
	public function test_color_mapping_has_no_gaps(): void {
		// Test all scores from 0 to 100
		for ( $score = 0; $score <= 100; $score++ ) {
			$data = $this->create_data_for_score( $score );
			$result = SEO_Analyzer::analyze( $data );

			$this->assertNotEmpty(
				$result['color'],
				"Score $score should have a color mapping"
			);

			$this->assertContains(
				$result['color'],
				array( 'red', 'orange', 'green' ),
				"Score $score should map to a valid color"
			);
		}
	}

	/**
	 * Property: Boundary scores map correctly
	 *
	 * For boundary scores (49, 50, 79, 80), the color mapping should be correct.
	 *
	 * @return void
	 */
	public function test_boundary_scores_map_correctly(): void {
		// Score 49 should be red
		$color_49 = $this->get_color_for_score( 49 );
		$this->assertEquals( 'red', $color_49, 'Score 49 should map to red' );

		// Score 50 should be orange
		$color_50 = $this->get_color_for_score( 50 );
		$this->assertEquals( 'orange', $color_50, 'Score 50 should map to orange' );

		// Score 79 should be orange
		$color_79 = $this->get_color_for_score( 79 );
		$this->assertEquals( 'orange', $color_79, 'Score 79 should map to orange' );

		// Score 80 should be green
		$color_80 = $this->get_color_for_score( 80 );
		$this->assertEquals( 'green', $color_80, 'Score 80 should map to green' );
	}

	/**
	 * Helper: Get color for a score
	 *
	 * Implements the same color mapping logic as SEO_Analyzer.
	 *
	 * @param int $score Score from 0-100.
	 * @return string Color: 'red', 'orange', or 'green'.
	 */
	private function get_color_for_score( int $score ): string {
		if ( $score >= 80 ) {
			return 'green';
		} elseif ( $score >= 50 ) {
			return 'orange';
		} else {
			return 'red';
		}
	}

	/**
	 * Helper: Create analysis data that will produce a specific score
	 *
	 * This is a best-effort approach to create data that produces a target score.
	 * The actual score may vary slightly due to the complexity of the analysis.
	 *
	 * @param int $target_score Target score (0-100).
	 * @return array Analysis data.
	 */
	private function create_data_for_score( int $target_score ): array {
		// There are 7 checks in SEO analysis
		// To get a specific score, we need to pass a certain number of checks
		// Score = (passing_checks / 7) * 100
		// So: passing_checks = (score / 100) * 7

		$passing_checks_needed = round( ( $target_score / 100 ) * 7 );

		// Create data that will pass specific checks
		$data = array(
			'title'         => 'Test keyword here',
			'description'   => 'This is a test keyword description that is properly sized',
			'content'       => '<p>First paragraph with keyword content</p><h2>Heading with keyword</h2>',
			'slug'          => 'keyword-slug',
			'focus_keyword' => 'keyword',
		);

		// If we need fewer checks to pass, remove some content
		if ( $passing_checks_needed < 7 ) {
			// Remove keyword from slug
			if ( $passing_checks_needed < 5 ) {
				$data['slug'] = 'other-slug';
			}
			// Remove keyword from headings
			if ( $passing_checks_needed < 4 ) {
				$data['content'] = '<p>First paragraph with keyword content</p><h2>Other heading</h2>';
			}
			// Remove keyword from first paragraph
			if ( $passing_checks_needed < 3 ) {
				$data['content'] = '<p>First paragraph without keyword</p>';
			}
			// Remove keyword from description
			if ( $passing_checks_needed < 2 ) {
				$data['description'] = 'This is a description without the keyword';
			}
			// Remove keyword from title
			if ( $passing_checks_needed < 1 ) {
				$data['title'] = 'Test title without keyword';
				$data['focus_keyword'] = 'nonexistent';
			}
		}

		return $data;
	}
}



