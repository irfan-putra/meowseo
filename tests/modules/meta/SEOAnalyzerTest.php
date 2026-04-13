<?php
/**
 * SEO Analyzer Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\SEO_Analyzer;

/**
 * Test SEO Analyzer functionality
 *
 * @since 1.0.0
 */
class SEOAnalyzerTest extends TestCase {

	/**
	 * Test analyze returns correct structure
	 *
	 * @since 1.0.0
	 */
	public function test_analyze_returns_correct_structure() {
		$data = array(
			'title'         => 'Test Title',
			'description'   => 'Test description',
			'content'       => '<p>Test content</p>',
			'slug'          => 'test-slug',
			'focus_keyword' => 'test',
		);

		$result = SEO_Analyzer::analyze( $data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertArrayHasKey( 'checks', $result );
		$this->assertArrayHasKey( 'color', $result );
		$this->assertIsInt( $result['score'] );
		$this->assertIsArray( $result['checks'] );
		$this->assertIsString( $result['color'] );
	}

	/**
	 * Test keyword in title check
	 *
	 * @since 1.0.0
	 */
	public function test_keyword_in_title() {
		$data = array(
			'title'         => 'WordPress SEO Plugin',
			'description'   => 'A great plugin',
			'content'       => '<p>Content here</p>',
			'slug'          => 'wordpress-plugin',
			'focus_keyword' => 'WordPress',
		);

		$result = SEO_Analyzer::analyze( $data );
		$keyword_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'keyword_in_title' );
		$keyword_check = reset( $keyword_check );

		$this->assertTrue( $keyword_check['pass'] );
	}

	/**
	 * Test keyword not in title check
	 *
	 * @since 1.0.0
	 */
	public function test_keyword_not_in_title() {
		$data = array(
			'title'         => 'My Blog Post',
			'description'   => 'A great plugin',
			'content'       => '<p>Content here</p>',
			'slug'          => 'blog-post',
			'focus_keyword' => 'WordPress',
		);

		$result = SEO_Analyzer::analyze( $data );
		$keyword_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'keyword_in_title' );
		$keyword_check = reset( $keyword_check );

		$this->assertFalse( $keyword_check['pass'] );
	}

	/**
	 * Test description length check - valid
	 *
	 * @since 1.0.0
	 */
	public function test_description_length_valid() {
		$description = str_repeat( 'a', 100 ); // 100 chars, within 50-160 range

		$data = array(
			'title'         => 'Test',
			'description'   => $description,
			'content'       => '<p>Content</p>',
			'slug'          => 'test',
			'focus_keyword' => 'test',
		);

		$result = SEO_Analyzer::analyze( $data );
		$desc_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'description_length' );
		$desc_check = reset( $desc_check );

		$this->assertTrue( $desc_check['pass'] );
	}

	/**
	 * Test description length check - too short
	 *
	 * @since 1.0.0
	 */
	public function test_description_length_too_short() {
		$data = array(
			'title'         => 'Test',
			'description'   => 'Short',
			'content'       => '<p>Content</p>',
			'slug'          => 'test',
			'focus_keyword' => 'test',
		);

		$result = SEO_Analyzer::analyze( $data );
		$desc_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'description_length' );
		$desc_check = reset( $desc_check );

		$this->assertFalse( $desc_check['pass'] );
	}

	/**
	 * Test description length check - too long
	 *
	 * @since 1.0.0
	 */
	public function test_description_length_too_long() {
		$description = str_repeat( 'a', 200 ); // 200 chars, exceeds 160 limit

		$data = array(
			'title'         => 'Test',
			'description'   => $description,
			'content'       => '<p>Content</p>',
			'slug'          => 'test',
			'focus_keyword' => 'test',
		);

		$result = SEO_Analyzer::analyze( $data );
		$desc_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'description_length' );
		$desc_check = reset( $desc_check );

		$this->assertFalse( $desc_check['pass'] );
	}

	/**
	 * Test title length check - valid
	 *
	 * @since 1.0.0
	 */
	public function test_title_length_valid() {
		$title = str_repeat( 'a', 45 ); // 45 chars, within 30-60 range

		$data = array(
			'title'         => $title,
			'description'   => 'Description',
			'content'       => '<p>Content</p>',
			'slug'          => 'test',
			'focus_keyword' => 'test',
		);

		$result = SEO_Analyzer::analyze( $data );
		$title_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'title_length' );
		$title_check = reset( $title_check );

		$this->assertTrue( $title_check['pass'] );
	}

	/**
	 * Test keyword in first paragraph
	 *
	 * @since 1.0.0
	 */
	public function test_keyword_in_first_paragraph() {
		$data = array(
			'title'         => 'Test',
			'description'   => 'Description',
			'content'       => '<p>This is about WordPress and its features.</p><p>Second paragraph.</p>',
			'slug'          => 'test',
			'focus_keyword' => 'WordPress',
		);

		$result = SEO_Analyzer::analyze( $data );
		$para_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'keyword_in_first_paragraph' );
		$para_check = reset( $para_check );

		$this->assertTrue( $para_check['pass'] );
	}

	/**
	 * Test keyword in headings
	 *
	 * @since 1.0.0
	 */
	public function test_keyword_in_headings() {
		$data = array(
			'title'         => 'Test',
			'description'   => 'Description',
			'content'       => '<h2>WordPress Features</h2><p>Content</p><h3>More Info</h3>',
			'slug'          => 'test',
			'focus_keyword' => 'WordPress',
		);

		$result = SEO_Analyzer::analyze( $data );
		$heading_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'keyword_in_headings' );
		$heading_check = reset( $heading_check );

		$this->assertTrue( $heading_check['pass'] );
	}

	/**
	 * Test keyword in slug
	 *
	 * @since 1.0.0
	 */
	public function test_keyword_in_slug() {
		$data = array(
			'title'         => 'Test',
			'description'   => 'Description',
			'content'       => '<p>Content</p>',
			'slug'          => 'wordpress-seo-guide',
			'focus_keyword' => 'WordPress',
		);

		$result = SEO_Analyzer::analyze( $data );
		$slug_check = array_filter( $result['checks'], fn( $c ) => $c['id'] === 'keyword_in_slug' );
		$slug_check = reset( $slug_check );

		$this->assertTrue( $slug_check['pass'] );
	}

	/**
	 * Test score calculation
	 *
	 * @since 1.0.0
	 */
	public function test_score_calculation() {
		// All checks should pass
		$data = array(
			'title'         => 'WordPress SEO Plugin Guide for Beginners',
			'description'   => 'Learn how to use WordPress SEO plugins effectively. This comprehensive guide covers everything you need to know about optimizing your site.',
			'content'       => '<p>WordPress is a powerful platform for SEO.</p><h2>WordPress SEO Best Practices</h2><p>More content here.</p>',
			'slug'          => 'wordpress-seo-plugin-guide',
			'focus_keyword' => 'WordPress',
		);

		$result = SEO_Analyzer::analyze( $data );

		$this->assertEquals( 100, $result['score'] );
		$this->assertEquals( 'green', $result['color'] );
	}

	/**
	 * Test color indicators
	 *
	 * @since 1.0.0
	 */
	public function test_color_indicators() {
		// Test green (80-100)
		$data_green = array(
			'title'         => 'WordPress SEO Plugin Guide for Beginners',
			'description'   => 'Learn how to use WordPress SEO plugins effectively. This comprehensive guide covers everything you need to know about optimizing your site.',
			'content'       => '<p>WordPress is great.</p><h2>WordPress Features</h2>',
			'slug'          => 'wordpress-guide',
			'focus_keyword' => 'WordPress',
		);
		$result = SEO_Analyzer::analyze( $data_green );
		$this->assertEquals( 'green', $result['color'] );

		// Test red (0-49)
		$data_red = array(
			'title'         => 'Test',
			'description'   => 'Short',
			'content'       => '<p>Content</p>',
			'slug'          => 'test',
			'focus_keyword' => 'WordPress',
		);
		$result = SEO_Analyzer::analyze( $data_red );
		$this->assertEquals( 'red', $result['color'] );
	}

	/**
	 * Test case insensitive keyword matching
	 *
	 * @since 1.0.0
	 */
	public function test_case_insensitive_keyword_matching() {
		$data = array(
			'title'         => 'wordpress seo guide',
			'description'   => 'WORDPRESS is great',
			'content'       => '<p>WordPress content</p>',
			'slug'          => 'WordPress-guide',
			'focus_keyword' => 'WordPress',
		);

		$result = SEO_Analyzer::analyze( $data );

		// All keyword checks should pass despite case differences
		$keyword_checks = array_filter( $result['checks'], function( $c ) {
			return in_array( $c['id'], array( 'keyword_in_title', 'keyword_in_description', 'keyword_in_first_paragraph', 'keyword_in_slug' ) );
		} );

		foreach ( $keyword_checks as $check ) {
			$this->assertTrue( $check['pass'], "Check {$check['id']} should pass with case-insensitive matching" );
		}
	}
}
