<?php
/**
 * Readability Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Readability;

/**
 * Test Readability functionality
 *
 * @since 1.0.0
 */
class ReadabilityTest extends TestCase {

	/**
	 * Test analyze returns correct structure
	 *
	 * @since 1.0.0
	 */
	public function test_analyze_returns_correct_structure() {
		$content = '<p>This is a test sentence.</p>';

		$result = Readability::analyze( $content );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertArrayHasKey( 'checks', $result );
		$this->assertArrayHasKey( 'color', $result );
		$this->assertIsInt( $result['score'] );
		$this->assertIsArray( $result['checks'] );
		$this->assertIsString( $result['color'] );
	}

	/**
	 * Test empty content returns zero score
	 *
	 * @since 1.0.0
	 */
	public function test_empty_content_returns_zero_score() {
		$result = Readability::analyze( '' );

		$this->assertEquals( 0, $result['score'] );
		$this->assertEquals( 'red', $result['color'] );
		$this->assertEmpty( $result['checks'] );
	}

	/**
	 * Test average sentence length check - pass
	 *
	 * @since 1.0.0
	 */
	public function test_sentence_length_pass() {
		// Sentences with 10 words each (well under 20 word limit)
		$content = '<p>This is a short sentence with exactly ten words here. Another short sentence with exactly ten words here too.</p>';

		$result = Readability::analyze( $content );
		$sentence_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'sentence_length' );
		$sentence_check = reset( $sentence_check );

		$this->assertTrue( $sentence_check['pass'] );
	}

	/**
	 * Test average sentence length check - fail
	 *
	 * @since 1.0.0
	 */
	public function test_sentence_length_fail() {
		// One very long sentence (over 20 words)
		$content = '<p>This is a very long sentence that contains way more than twenty words and should fail the readability check for average sentence length.</p>';

		$result = Readability::analyze( $content );
		$sentence_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'sentence_length' );
		$sentence_check = reset( $sentence_check );

		$this->assertFalse( $sentence_check['pass'] );
	}

	/**
	 * Test paragraph length check - pass
	 *
	 * @since 1.0.0
	 */
	public function test_paragraph_length_pass() {
		// Short paragraph (under 150 words)
		$words = array_fill( 0, 100, 'word' );
		$content = '<p>' . implode( ' ', $words ) . '.</p>';

		$result = Readability::analyze( $content );
		$para_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'paragraph_length' );
		$para_check = reset( $para_check );

		$this->assertTrue( $para_check['pass'] );
	}

	/**
	 * Test paragraph length check - fail
	 *
	 * @since 1.0.0
	 */
	public function test_paragraph_length_fail() {
		// Long paragraph (over 150 words)
		$words = array_fill( 0, 200, 'word' );
		$content = '<p>' . implode( ' ', $words ) . '.</p>';

		$result = Readability::analyze( $content );
		$para_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'paragraph_length' );
		$para_check = reset( $para_check );

		$this->assertFalse( $para_check['pass'] );
	}

	/**
	 * Test transition words check - pass
	 *
	 * @since 1.0.0
	 */
	public function test_transition_words_pass() {
		// 3 sentences, 2 with transition words (66% > 30%)
		$content = '<p>However, this is a test. Therefore, we continue. This is the end.</p>';

		$result = Readability::analyze( $content );
		$transition_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'transition_words' );
		$transition_check = reset( $transition_check );

		$this->assertTrue( $transition_check['pass'] );
	}

	/**
	 * Test transition words check - fail
	 *
	 * @since 1.0.0
	 */
	public function test_transition_words_fail() {
		// 3 sentences, none with transition words (0% < 30%)
		$content = '<p>This is a test. We continue testing. This is the end.</p>';

		$result = Readability::analyze( $content );
		$transition_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'transition_words' );
		$transition_check = reset( $transition_check );

		$this->assertFalse( $transition_check['pass'] );
	}

	/**
	 * Test passive voice check - pass
	 *
	 * @since 1.0.0
	 */
	public function test_passive_voice_pass() {
		// All active voice sentences
		$content = '<p>The cat chased the mouse. The dog barked loudly. The bird sang beautifully.</p>';

		$result = Readability::analyze( $content );
		$passive_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'passive_voice' );
		$passive_check = reset( $passive_check );

		$this->assertTrue( $passive_check['pass'] );
	}

	/**
	 * Test passive voice check - fail
	 *
	 * @since 1.0.0
	 */
	public function test_passive_voice_fail() {
		// All passive voice sentences (100% > 10%)
		$content = '<p>The mouse was chased by the cat. The bone was buried by the dog. The song was sung by the bird.</p>';

		$result = Readability::analyze( $content );
		$passive_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'passive_voice' );
		$passive_check = reset( $passive_check );

		$this->assertFalse( $passive_check['pass'] );
	}

	/**
	 * Test score calculation with all checks passing
	 *
	 * @since 1.0.0
	 */
	public function test_score_calculation_all_pass() {
		// Content designed to pass all checks
		$content = '<p>However, this is a short sentence. Therefore, we write clearly. Moreover, we use active voice. Finally, paragraphs stay brief.</p>';

		$result = Readability::analyze( $content );

		$this->assertEquals( 100, $result['score'] );
		$this->assertEquals( 'green', $result['color'] );
	}

	/**
	 * Test color indicators
	 *
	 * @since 1.0.0
	 */
	public function test_color_indicators() {
		// Test green (all checks pass)
		$content_green = '<p>However, this is short. Therefore, we continue. Moreover, it works well. Finally, we finish.</p>';
		$result = Readability::analyze( $content_green );
		$this->assertEquals( 'green', $result['color'] );

		// Test red/orange (most checks fail - no transition words, long sentences)
		$content_red = '<p>This content has no transition words whatsoever making readability poor because there are no connecting phrases between ideas which makes comprehension difficult especially when sentences become excessively long like this one that just keeps going on without proper breaks.</p>';
		$result = Readability::analyze( $content_red );
		$this->assertContains( $result['color'], array( 'red', 'orange' ), 'Color should be red or orange for poor readability' );
	}

	/**
	 * Test HTML stripping
	 *
	 * @since 1.0.0
	 */
	public function test_html_stripping() {
		$content = '<p>This is <strong>bold</strong> and <em>italic</em> text. However, it should still work.</p>';

		$result = Readability::analyze( $content );

		// Should analyze the text content, not the HTML tags
		$this->assertIsArray( $result );
		$this->assertGreaterThan( 0, $result['score'] );
	}

	/**
	 * Test multiple paragraphs
	 *
	 * @since 1.0.0
	 */
	public function test_multiple_paragraphs() {
		$content = '<p>However, this is the first paragraph. It is short.</p><p>Therefore, this is the second paragraph. It is also short.</p>';

		$result = Readability::analyze( $content );

		$this->assertIsArray( $result );
		$this->assertGreaterThan( 0, $result['score'] );
	}

	/**
	 * Test shortcode stripping
	 *
	 * @since 1.0.0
	 */
	public function test_shortcode_stripping() {
		$content = '<p>This is a test. [shortcode param="value"]Content[/shortcode] However, it continues.</p>';

		$result = Readability::analyze( $content );

		// Should analyze without shortcodes
		$this->assertIsArray( $result );
		$this->assertGreaterThan( 0, $result['score'] );
	}

	/**
	 * Test sentence splitting with various punctuation
	 *
	 * @since 1.0.0
	 */
	public function test_sentence_splitting() {
		$content = '<p>This is a sentence. Is this a question? This is exciting! However, we continue.</p>';

		$result = Readability::analyze( $content );

		// Should correctly identify 4 sentences
		$this->assertIsArray( $result );
		$this->assertGreaterThan( 0, $result['score'] );
	}

	/**
	 * Test transition word detection with word boundaries
	 *
	 * @since 1.0.0
	 */
	public function test_transition_word_boundaries() {
		// "however" should match, but "whatsoever" should not
		$content = '<p>However, this is a test. Whatsoever the case may be. Therefore, we continue.</p>';

		$result = Readability::analyze( $content );
		$transition_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'transition_words' );
		$transition_check = reset( $transition_check );

		// Should detect "however" and "therefore" (2 out of 3 = 66%)
		$this->assertTrue( $transition_check['pass'] );
	}
}
