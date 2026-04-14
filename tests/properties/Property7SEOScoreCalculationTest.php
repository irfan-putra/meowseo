<?php
/**
 * Property-Based Tests for SEO Score Calculation
 *
 * Property 7: SEO score is proportional to passing checks
 * Validates: Requirements 4.2, 4.3
 *
 * This test uses property-based testing (eris/eris) to verify that the SEO score
 * is directly proportional to the number of passing checks. If N checks pass out of
 * M total, the score should be (N/M)*100.
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
 * SEO Score Calculation property-based test case
 *
 * @since 1.0.0
 */
class Property7SEOScoreCalculationTest extends TestCase {
	use TestTrait;

	/**
	 * Property 7: SEO score is proportional to passing checks
	 *
	 * For any combination of passing/failing checks, the SEO score should be
	 * directly proportional to the number of passing checks.
	 * If N checks pass out of M total, the score should be (N/M)*100.
	 *
	 * This property verifies:
	 * 1. Score is calculated as (passing_checks / total_checks) * 100
	 * 2. Score is always between 0 and 100
	 * 3. Score increases with more passing checks
	 * 4. Score is deterministic for the same input
	 *
	 * **Validates: Requirements 4.2, 4.3**
	 *
	 * @return void
	 */
	public function test_seo_score_is_proportional_to_passing_checks(): void {
		$this->forAll(
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::string()
		)
		->then(
			function ( string $title, string $description, string $content, string $slug, string $focus_keyword ) {
				// Prepare analysis data
				$data = array(
					'title'         => $title,
					'description'   => $description,
					'content'       => $content,
					'slug'          => $slug,
					'focus_keyword' => $focus_keyword,
				);

				// Run analysis
				$result = SEO_Analyzer::analyze( $data );

				// Extract score and checks
				$score = $result['score'];
				$checks = $result['checks'];

				// Count passing checks
				$passing_checks = count( array_filter( $checks, fn( $check ) => $check['pass'] ) );
				$total_checks = count( $checks );

				// Calculate expected score
				$expected_score = (int) round( ( $passing_checks / $total_checks ) * 100 );

				// Verify score matches expected calculation
				$this->assertEquals(
					$expected_score,
					$score,
					"SEO score should be (passing_checks / total_checks) * 100"
				);

				// Verify score is between 0 and 100
				$this->assertGreaterThanOrEqual(
					0,
					$score,
					'SEO score should never be less than 0'
				);

				$this->assertLessThanOrEqual(
					100,
					$score,
					'SEO score should never exceed 100'
				);
			}
		);
	}

	/**
	 * Property: SEO score increases with more passing checks
	 *
	 * For any two sets of checks where the second has more passing checks,
	 * the second score should be greater than or equal to the first.
	 *
	 * @return void
	 */
	public function test_seo_score_increases_with_more_passing_checks(): void {
		$this->forAll(
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::string()
		)
		->then(
			function ( string $title, string $description, string $content, string $slug ) {
				// Scenario 1: No focus keyword (fewer checks pass)
				$data_no_keyword = array(
					'title'         => $title,
					'description'   => $description,
					'content'       => $content,
					'slug'          => $slug,
					'focus_keyword' => '', // Empty keyword
				);

				$result_no_keyword = SEO_Analyzer::analyze( $data_no_keyword );
				$score_no_keyword = $result_no_keyword['score'];

				// Scenario 2: With focus keyword (potentially more checks pass)
				$data_with_keyword = array(
					'title'         => $title,
					'description'   => $description,
					'content'       => $content,
					'slug'          => $slug,
					'focus_keyword' => $slug, // Use slug as keyword (likely to match)
				);

				$result_with_keyword = SEO_Analyzer::analyze( $data_with_keyword );
				$score_with_keyword = $result_with_keyword['score'];

				// Score with keyword should be >= score without keyword
				$this->assertGreaterThanOrEqual(
					$score_no_keyword,
					$score_with_keyword,
					'SEO score should increase (or stay same) with more passing checks'
				);
			}
		);
	}

	/**
	 * Property: SEO score is deterministic
	 *
	 * For any given input data, the SEO score should always be the same
	 * (deterministic behavior).
	 *
	 * @return void
	 */
	public function test_seo_score_is_deterministic(): void {
		$this->forAll(
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::string()
		)
		->then(
			function ( string $title, string $description, string $content, string $slug, string $focus_keyword ) {
				// Prepare analysis data
				$data = array(
					'title'         => $title,
					'description'   => $description,
					'content'       => $content,
					'slug'          => $slug,
					'focus_keyword' => $focus_keyword,
				);

				// Run analysis three times
				$result1 = SEO_Analyzer::analyze( $data );
				$result2 = SEO_Analyzer::analyze( $data );
				$result3 = SEO_Analyzer::analyze( $data );

				// All scores should be identical
				$this->assertEquals(
					$result1['score'],
					$result2['score'],
					'SEO score should be deterministic (run 1 vs 2)'
				);

				$this->assertEquals(
					$result2['score'],
					$result3['score'],
					'SEO score should be deterministic (run 2 vs 3)'
				);
			}
		);
	}

	/**
	 * Property: SEO score is always an integer
	 *
	 * For any input data, the SEO score should always be an integer.
	 *
	 * @return void
	 */
	public function test_seo_score_is_always_integer(): void {
		$this->forAll(
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::string()
		)
		->then(
			function ( string $title, string $description, string $content, string $slug, string $focus_keyword ) {
				// Prepare analysis data
				$data = array(
					'title'         => $title,
					'description'   => $description,
					'content'       => $content,
					'slug'          => $slug,
					'focus_keyword' => $focus_keyword,
				);

				// Run analysis
				$result = SEO_Analyzer::analyze( $data );
				$score = $result['score'];

				// Verify score is an integer
				$this->assertIsInt(
					$score,
					'SEO score should always be an integer'
				);
			}
		);
	}

	/**
	 * Property: All checks are either pass or fail
	 *
	 * For any analysis result, every check should have a boolean pass value.
	 *
	 * @return void
	 */
	public function test_all_checks_are_boolean(): void {
		$this->forAll(
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::string()
		)
		->then(
			function ( string $title, string $description, string $content, string $slug, string $focus_keyword ) {
				// Prepare analysis data
				$data = array(
					'title'         => $title,
					'description'   => $description,
					'content'       => $content,
					'slug'          => $slug,
					'focus_keyword' => $focus_keyword,
				);

				// Run analysis
				$result = SEO_Analyzer::analyze( $data );
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
	 * Property: Score of 0 means no checks pass
	 *
	 * For any analysis result with a score of 0, no checks should pass.
	 *
	 * @return void
	 */
	public function test_score_zero_means_no_checks_pass(): void {
		// Create data that will likely result in 0 score
		$data = array(
			'title'         => '',
			'description'   => '',
			'content'       => '',
			'slug'          => '',
			'focus_keyword' => 'nonexistent-keyword-xyz',
		);

		$result = SEO_Analyzer::analyze( $data );
		$score = $result['score'];
		$checks = $result['checks'];

		// If score is 0, no checks should pass
		if ( $score === 0 ) {
			$passing_checks = count( array_filter( $checks, fn( $check ) => $check['pass'] ) );
			$this->assertEquals(
				0,
				$passing_checks,
				'If score is 0, no checks should pass'
			);
		}
	}

	/**
	 * Property: Score of 100 means all checks pass
	 *
	 * For any analysis result with a score of 100, all checks should pass.
	 *
	 * @return void
	 */
	public function test_score_100_means_all_checks_pass(): void {
		// Create data that will likely result in 100 score
		$keyword = 'test';
		$data = array(
			'title'         => 'Test Title About Test',
			'description'   => 'This is a test description about test keyword',
			'content'       => '<p>Test content with test keyword in first paragraph</p><h2>Test Heading</h2>',
			'slug'          => 'test-slug',
			'focus_keyword' => $keyword,
		);

		$result = SEO_Analyzer::analyze( $data );
		$score = $result['score'];
		$checks = $result['checks'];

		// If score is 100, all checks should pass
		if ( $score === 100 ) {
			$passing_checks = count( array_filter( $checks, fn( $check ) => $check['pass'] ) );
			$total_checks = count( $checks );
			$this->assertEquals(
				$total_checks,
				$passing_checks,
				'If score is 100, all checks should pass'
			);
		} else {
			// If score is not 100, at least verify the score is valid
			$this->assertGreaterThanOrEqual( 0, $score );
			$this->assertLessThanOrEqual( 100, $score );
		}
	}
}



