<?php
/**
 * Synonym Analyzer
 *
 * Analyzes content for keyword synonyms and calculates combined scores.
 *
 * @package MeowSEO
 * @subpackage Modules\Synonyms
 */

namespace MeowSEO\Modules\Synonyms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Synonym_Analyzer class
 *
 * Provides synonym storage, analysis, and combined score calculation.
 *
 * Requirements: 11.1, 11.2, 11.3, 11.5, 11.7, 11.8
 */
class Synonym_Analyzer {

	/**
	 * Maximum number of synonyms allowed per post
	 *
	 * @var int
	 */
	private const MAX_SYNONYMS = 5;

	/**
	 * Get synonyms for a post
	 *
	 * Requirement: 11.2 - Retrieve synonyms from postmeta
	 *
	 * @param int $post_id Post ID.
	 * @return array Array of synonym strings.
	 */
	public function get_synonyms( int $post_id ): array {
		$synonyms = get_post_meta( $post_id, '_meowseo_keyword_synonyms', true );

		if ( empty( $synonyms ) ) {
			return array();
		}

		// Ensure it's an array
		if ( is_string( $synonyms ) ) {
			$decoded = json_decode( $synonyms, true );
			return is_array( $decoded ) ? $decoded : array();
		}

		return is_array( $synonyms ) ? $synonyms : array();
	}

	/**
	 * Set synonyms for a post
	 *
	 * Requirements: 11.1, 11.2, 11.7 - Store synonyms with validation
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $synonyms Array of synonym strings.
	 * @return bool True on success, false on failure.
	 */
	public function set_synonyms( int $post_id, array $synonyms ): bool {
		// Sanitize and filter synonyms
		$sanitized = array();
		foreach ( $synonyms as $synonym ) {
			$clean = sanitize_text_field( trim( $synonym ) );
			if ( ! empty( $clean ) ) {
				$sanitized[] = $clean;
			}
		}

		// Requirement 11.7: Limit to 5 synonyms
		if ( count( $sanitized ) > self::MAX_SYNONYMS ) {
			$sanitized = array_slice( $sanitized, 0, self::MAX_SYNONYMS );
		}

		// Store as JSON array
		$json = wp_json_encode( $sanitized );
		return update_post_meta( $post_id, '_meowseo_keyword_synonyms', $json ) !== false;
	}

	/**
	 * Analyze a single synonym in content
	 *
	 * Requirements: 11.3, 11.8 - Run checks for synonym
	 *
	 * @param string $synonym Synonym to analyze.
	 * @param string $content Post content (HTML).
	 * @param array  $context Context data (title, description, etc.).
	 * @return array Analysis results with checks.
	 */
	public function analyze_synonym( string $synonym, string $content, array $context ): array {
		$title = $context['title'] ?? '';
		$description = $context['description'] ?? '';

		$checks = array();

		// Check 1: Synonym density (0.5-2.5%)
		$density_result = $this->analyze_synonym_density( $synonym, $content );
		$checks[] = array(
			'id'    => 'synonym_density',
			'label' => sprintf(
				/* translators: %s: synonym */
				__( 'Synonym "%s" density (0.5-2.5%%)', 'meowseo' ),
				$synonym
			),
			'pass'  => $density_result['pass'],
			'value' => $density_result['density'],
		);

		// Check 2: Synonym in title
		$checks[] = array(
			'id'    => 'synonym_in_title',
			'label' => sprintf(
				/* translators: %s: synonym */
				__( 'Synonym "%s" in title', 'meowseo' ),
				$synonym
			),
			'pass'  => $this->contains_keyword( $title, $synonym ),
		);

		// Check 3: Synonym in headings
		$headings = $this->extract_headings( $content );
		$checks[] = array(
			'id'    => 'synonym_in_headings',
			'label' => sprintf(
				/* translators: %s: synonym */
				__( 'Synonym "%s" in headings', 'meowseo' ),
				$synonym
			),
			'pass'  => $this->keyword_in_headings( $headings, $synonym ),
		);

		// Check 4: Synonym in first paragraph
		$first_paragraph = $this->extract_first_paragraph( $content );
		$checks[] = array(
			'id'    => 'synonym_in_first_paragraph',
			'label' => sprintf(
				/* translators: %s: synonym */
				__( 'Synonym "%s" in first paragraph', 'meowseo' ),
				$synonym
			),
			'pass'  => $this->contains_keyword( $first_paragraph, $synonym ),
		);

		// Check 5: Synonym in meta description
		$checks[] = array(
			'id'    => 'synonym_in_meta_description',
			'label' => sprintf(
				/* translators: %s: synonym */
				__( 'Synonym "%s" in meta description', 'meowseo' ),
				$synonym
			),
			'pass'  => $this->contains_keyword( $description, $synonym ),
		);

		// Calculate score for this synonym
		$passing_checks = count( array_filter( $checks, fn( $check ) => $check['pass'] ) );
		$total_checks = count( $checks );
		$score = (int) round( ( $passing_checks / $total_checks ) * 100 );

		return array(
			'synonym' => $synonym,
			'score'   => $score,
			'checks'  => $checks,
		);
	}

	/**
	 * Calculate combined score from primary and synonym results
	 *
	 * Requirement: 11.5 - Formula: (primary * 0.6) + (avg_synonyms * 0.4)
	 *
	 * @param array $primary_results  Primary keyword analysis results.
	 * @param array $synonym_results  Array of synonym analysis results.
	 * @return float Combined score (0-100).
	 */
	public function calculate_combined_score( array $primary_results, array $synonym_results ): float {
		$primary_score = $primary_results['score'] ?? 0;

		// Calculate average synonym score
		$synonym_scores = array_map( fn( $result ) => $result['score'] ?? 0, $synonym_results );
		$avg_synonym_score = empty( $synonym_scores ) ? 0 : array_sum( $synonym_scores ) / count( $synonym_scores );

		// Apply formula: (primary * 0.6) + (avg_synonyms * 0.4)
		$combined = ( $primary_score * 0.6 ) + ( $avg_synonym_score * 0.4 );

		// Ensure result is between 0 and 100
		return max( 0, min( 100, $combined ) );
	}

	/**
	 * Analyze synonym density
	 *
	 * Requirement: 11.8 - Check density is between 0.5-2.5%
	 *
	 * @param string $synonym Synonym to analyze.
	 * @param string $content Post content (HTML).
	 * @return array Result with pass status and density value.
	 */
	private function analyze_synonym_density( string $synonym, string $content ): array {
		$text = wp_strip_all_tags( $content );
		$text = trim( $text );

		if ( empty( $text ) || empty( $synonym ) ) {
			return array(
				'pass'    => false,
				'density' => 0,
			);
		}

		// Count words
		$words = preg_split( '/\s+/', $text );
		$word_count = count( array_filter( $words ) );

		if ( $word_count === 0 ) {
			return array(
				'pass'    => false,
				'density' => 0,
			);
		}

		// Count synonym occurrences (case-insensitive)
		$synonym_count = substr_count( mb_strtolower( $text ), mb_strtolower( $synonym ) );

		// Calculate density percentage
		$density = ( $synonym_count / $word_count ) * 100;

		// Check if density is within optimal range (0.5-2.5%)
		$pass = $density >= 0.5 && $density <= 2.5;

		return array(
			'pass'    => $pass,
			'density' => round( $density, 2 ),
		);
	}

	/**
	 * Check if text contains keyword (case-insensitive)
	 *
	 * @param string $text    Text to search in.
	 * @param string $keyword Keyword to search for.
	 * @return bool True if keyword found.
	 */
	private function contains_keyword( string $text, string $keyword ): bool {
		if ( empty( $keyword ) || empty( $text ) ) {
			return false;
		}

		return mb_stripos( $text, $keyword ) !== false;
	}

	/**
	 * Extract first paragraph from HTML content
	 *
	 * @param string $content HTML content.
	 * @return string First paragraph text.
	 */
	private function extract_first_paragraph( string $content ): string {
		if ( empty( $content ) ) {
			return '';
		}

		// Strip shortcodes first
		$content = strip_shortcodes( $content );

		// Try to extract first <p> tag
		if ( preg_match( '/<p[^>]*>(.*?)<\/p>/is', $content, $matches ) ) {
			return wp_strip_all_tags( $matches[1] );
		}

		// Fallback: get first 200 characters of stripped content
		$text = wp_strip_all_tags( $content );
		return mb_substr( $text, 0, 200 );
	}

	/**
	 * Extract H2 and H3 headings from HTML content
	 *
	 * @param string $content HTML content.
	 * @return array Array of heading texts.
	 */
	private function extract_headings( string $content ): array {
		if ( empty( $content ) ) {
			return array();
		}

		$headings = array();

		// Extract H2 headings
		if ( preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $content, $matches ) ) {
			foreach ( $matches[1] as $heading ) {
				$headings[] = wp_strip_all_tags( $heading );
			}
		}

		// Extract H3 headings
		if ( preg_match_all( '/<h3[^>]*>(.*?)<\/h3>/is', $content, $matches ) ) {
			foreach ( $matches[1] as $heading ) {
				$headings[] = wp_strip_all_tags( $heading );
			}
		}

		return $headings;
	}

	/**
	 * Check if keyword appears in any heading
	 *
	 * @param array  $headings Array of heading texts.
	 * @param string $keyword  Keyword to search for.
	 * @return bool True if keyword found in at least one heading.
	 */
	private function keyword_in_headings( array $headings, string $keyword ): bool {
		if ( empty( $keyword ) || empty( $headings ) ) {
			return false;
		}

		foreach ( $headings as $heading ) {
			if ( $this->contains_keyword( $heading, $keyword ) ) {
				return true;
			}
		}

		return false;
	}
}
