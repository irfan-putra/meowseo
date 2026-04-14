<?php
/**
 * Property-Based Tests for Readability Score Bounds
 *
 * Property 9: Readability score is bounded
 * Validates: Requirement 4.5
 *
 * This test uses property-based testing (eris/eris) to verify that the readability
 * score is always bounded between 0 and 100. Test generates various content with
 * different sentence lengths, paragraph lengths, and transition word usage, then
 * verifies the score never exceeds 100 or goes below 0.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Modules\Meta\Readability;

/**
 * Readability Score Bounds property-based test case
 *
 * @since 1.0.0
 */
class Property9ReadabilityScoreBoundsTest extends TestCase {
	use TestTrait;

	/**
	 * Property 9: Readability score is bounded
	 *
	 * For any content with different sentence lengths, paragraph lengths,
	 * and transition word usage, the readability score should always be
	 * bounded between 0 and 100.
	 *
	 * This property verifies:
	 * 1. Score is never less than 0
	 * 2. Score is never greater than 100
	 * 3. Score is always an integer
	 * 4. Score is deterministic for the same input
	 *
	 * **Validates: Requirement 4.5**
	 *
	 * @return void
	 */
	public function test_readability_score_is_bounded(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Run readability analysis
				$result = Readability::analyze( $content );

				// Extract score
				$score = $result['score'];

				// Verify score is not less than 0
				$this->assertGreaterThanOrEqual(
					0,
					$score,
					'Readability score should never be less than 0'
				);

				// Verify score is not greater than 100
				$this->assertLessThanOrEqual(
					100,
					$score,
					'Readability score should never exceed 100'
				);

				// Verify score is an integer
				$this->assertIsInt(
					$score,
					'Readability score should always be an integer'
				);
			}
		);
	}

	/**
	 * Property: Readability score is deterministic
	 *
	 * For any given content, the readability score should always be the same.
	 *
	 * @return void
	 */
	public function test_readability_score_is_deterministic(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Run analysis three times
				$result1 = Readability::analyze( $content );
				$result2 = Readability::analyze( $content );
				$result3 = Readability::analyze( $content );

				// All scores should be identical
				$this->assertEquals(
					$result1['score'],
					$result2['score'],
					'Readability score should be deterministic (run 1 vs 2)'
				);

				$this->assertEquals(
					$result2['score'],
					$result3['score'],
					'Readability score should be deterministic (run 2 vs 3)'
				);
			}
		);
	}

	/**
	 * Property: Readability score with short sentences is higher
	 *
	 * For content with short sentences, the readability score should be higher
	 * than content with long sentences.
	 *
	 * @return void
	 */
	public function test_readability_score_with_short_sentences_is_higher(): void {
		// Content with short sentences (good readability)
		$short_sentences = 'This is good. The text is clear. It reads well. Short sentences help. Readers understand better.';

		// Content with long sentences (poor readability)
		$long_sentences = 'This is a very long sentence that contains many clauses and ideas all strung together without proper breaks which makes it difficult to read and understand the main point being conveyed. Another extremely long sentence that goes on and on with multiple thoughts and concepts intertwined together making it challenging for readers to follow the narrative and comprehend what is being said.';

		$result_short = Readability::analyze( $short_sentences );
		$result_long = Readability::analyze( $long_sentences );

		// Short sentences should have higher or equal score
		$this->assertGreaterThanOrEqual(
			$result_long['score'],
			$result_short['score'],
			'Content with short sentences should have higher readability score'
		);
	}

	/**
	 * Property: Readability score with short paragraphs is higher
	 *
	 * For content with short paragraphs, the readability score should be higher
	 * than content with long paragraphs.
	 *
	 * @return void
	 */
	public function test_readability_score_with_short_paragraphs_is_higher(): void {
		// Content with short paragraphs (good readability)
		$short_paragraphs = '<p>This is a short paragraph.</p><p>This is another short paragraph.</p><p>And another one.</p>';

		// Content with long paragraphs (poor readability)
		$long_paragraphs = '<p>This is a very long paragraph that contains many sentences and ideas all strung together without proper breaks which makes it difficult to read and understand the main point being conveyed. The paragraph continues with more and more text adding to the length and complexity making it even harder for readers to follow the narrative and comprehend what is being said. This goes on and on with multiple thoughts and concepts intertwined together making it challenging for readers to process all the information at once.</p>';

		$result_short = Readability::analyze( $short_paragraphs );
		$result_long = Readability::analyze( $long_paragraphs );

		// Short paragraphs should have higher or equal score
		$this->assertGreaterThanOrEqual(
			$result_long['score'],
			$result_short['score'],
			'Content with short paragraphs should have higher readability score'
		);
	}

	/**
	 * Property: Readability score with transition words is higher
	 *
	 * For content with transition words, the readability score should be higher
	 * than content without transition words.
	 *
	 * @return void
	 */
	public function test_readability_score_with_transition_words_is_higher(): void {
		// Content with transition words (good readability)
		$with_transitions = 'First, we need to understand the basics. However, this is not always easy. Furthermore, practice is essential. Therefore, we should start today. Moreover, consistency matters. Finally, success comes with effort.';

		// Content without transition words (poor readability)
		$without_transitions = 'We need to understand the basics. This is not always easy. Practice is essential. We should start today. Consistency matters. Success comes with effort.';

		$result_with = Readability::analyze( $with_transitions );
		$result_without = Readability::analyze( $without_transitions );

		// Content with transitions should have higher or equal score
		$this->assertGreaterThanOrEqual(
			$result_without['score'],
			$result_with['score'],
			'Content with transition words should have higher readability score'
		);
	}

	/**
	 * Property: Empty content has score of 0
	 *
	 * For empty content, the readability score should be 0.
	 *
	 * @return void
	 */
	public function test_empty_content_has_score_zero(): void {
		$result = Readability::analyze( '' );

		$this->assertEquals(
			0,
			$result['score'],
			'Empty content should have readability score of 0'
		);
	}

	/**
	 * Property: All checks are either pass or fail
	 *
	 * For any readability analysis result, every check should have a boolean pass value.
	 *
	 * @return void
	 */
	public function test_all_checks_are_boolean(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Run analysis
				$result = Readability::analyze( $content );
				$checks = $result['checks'];

				// Verify all checks have boolean pass values
				foreach ( $checks as $check ) {
					$this->assertArrayHasKey(
						'pass',
						$check,
						'Each check should have a "pass" key'
					);

					$this->assertIsBool(
						$check['pass'],
						'Check "pass" value should be a boolean'
					);

					$this->assertArrayHasKey(
						'id',
						$check,
						'Each check should have an "id" key'
					);

					$this->assertArrayHasKey(
						'label',
						$check,
						'Each check should have a "label" key'
					);
				}
			}
		);
	}

	/**
	 * Property: Readability score is proportional to passing checks
	 *
	 * For any readability analysis, the score should be proportional to
	 * the number of passing checks.
	 *
	 * @return void
	 */
	public function test_readability_score_is_proportional_to_passing_checks(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Run analysis
				$result = Readability::analyze( $content );
				$score = $result['score'];
				$checks = $result['checks'];

				// Count passing checks
				$passing_checks = count( array_filter( $checks, fn( $check ) => $check['pass'] ) );
				$total_checks = count( $checks );

				// Calculate expected score
				if ( $total_checks > 0 ) {
					$expected_score = (int) round( ( $passing_checks / $total_checks ) * 100 );

					$this->assertEquals(
						$expected_score,
						$score,
						'Readability score should be proportional to passing checks'
					);
				}
			}
		);
	}

	/**
	 * Property: Readability score color is valid
	 *
	 * For any readability analysis, the color should be one of: red, orange, green.
	 *
	 * @return void
	 */
	public function test_readability_score_color_is_valid(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Run analysis
				$result = Readability::analyze( $content );
				$color = $result['color'];

				// Verify color is one of the valid options
				$valid_colors = array( 'red', 'orange', 'green' );
				$this->assertContains(
					$color,
					$valid_colors,
					'Readability score color should be one of: red, orange, green'
				);
			}
		);
	}

	/**
	 * Property: Readability score bounds are inclusive
	 *
	 * For any content, the readability score should be between 0 and 100 inclusive.
	 *
	 * @return void
	 */
	public function test_readability_score_bounds_are_inclusive(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Run analysis
				$result = Readability::analyze( $content );
				$score = $result['score'];

				// Verify score is within bounds (inclusive)
				$this->assertGreaterThanOrEqual(
					0,
					$score,
					'Readability score should be >= 0'
				);

				$this->assertLessThanOrEqual(
					100,
					$score,
					'Readability score should be <= 100'
				);

				// Verify score is an integer
				$this->assertIsInt(
					$score,
					'Readability score should be an integer'
				);
			}
		);
	}
}



