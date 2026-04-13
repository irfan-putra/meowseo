<?php
/**
 * Tests for Meta module SEO analysis integration
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Meta;

use MeowSEO\Modules\Meta\Meta;
use MeowSEO\Options;
use WP_UnitTestCase;

/**
 * Meta analysis integration test class
 */
class MetaAnalysisTest extends WP_UnitTestCase {

	/**
	 * Meta module instance
	 *
	 * @var Meta
	 */
	private $meta;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Set up test
	 */
	public function setUp(): void {
		parent::setUp();

		// Create Options mock.
		$options = $this->createMock( Options::class );
		$options->method( 'get_separator' )->willReturn( '|' );

		$this->meta = new Meta( $options );

		// Create a test post.
		$this->post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test SEO Post',
				'post_content' => '<p>This is the first paragraph with keyword SEO.</p><h2>SEO Heading</h2><p>Another paragraph.</p>',
				'post_name'    => 'test-seo-post',
			)
		);

		// Set focus keyword.
		update_post_meta( $this->post_id, 'meowseo_focus_keyword', 'SEO' );
		update_post_meta( $this->post_id, 'meowseo_title', 'Test SEO Post Title' );
		update_post_meta( $this->post_id, 'meowseo_description', 'This is a test SEO description with the keyword SEO in it.' );
	}

	/**
	 * Test get_seo_analysis returns valid structure
	 */
	public function test_get_seo_analysis_returns_valid_structure() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertArrayHasKey( 'checks', $result );
		$this->assertArrayHasKey( 'color', $result );
	}

	/**
	 * Test SEO analysis score is between 0 and 100
	 */
	public function test_seo_analysis_score_is_bounded() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		$this->assertGreaterThanOrEqual( 0, $result['score'] );
		$this->assertLessThanOrEqual( 100, $result['score'] );
	}

	/**
	 * Test SEO analysis checks is an array
	 */
	public function test_seo_analysis_checks_is_array() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		$this->assertIsArray( $result['checks'] );
		$this->assertNotEmpty( $result['checks'] );
	}

	/**
	 * Test SEO analysis color indicator is valid
	 */
	public function test_seo_analysis_color_is_valid() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		$this->assertContains( $result['color'], array( 'red', 'orange', 'green' ) );
	}

	/**
	 * Test get_readability_analysis returns valid structure
	 */
	public function test_get_readability_analysis_returns_valid_structure() {
		$content = '<p>This is a test paragraph. It has multiple sentences. Each sentence is short.</p>';
		$result  = $this->meta->get_readability_analysis( $content );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertArrayHasKey( 'checks', $result );
		$this->assertArrayHasKey( 'color', $result );
	}

	/**
	 * Test readability analysis score is between 0 and 100
	 */
	public function test_readability_analysis_score_is_bounded() {
		$content = '<p>This is a test paragraph. It has multiple sentences. Each sentence is short.</p>';
		$result  = $this->meta->get_readability_analysis( $content );

		$this->assertGreaterThanOrEqual( 0, $result['score'] );
		$this->assertLessThanOrEqual( 100, $result['score'] );
	}

	/**
	 * Test readability analysis with empty content
	 */
	public function test_readability_analysis_with_empty_content() {
		$result = $this->meta->get_readability_analysis( '' );

		$this->assertEquals( 0, $result['score'] );
		$this->assertEquals( 'red', $result['color'] );
	}

	/**
	 * Test SEO analysis with custom content and keyword
	 */
	public function test_seo_analysis_with_custom_content_and_keyword() {
		$content       = '<p>WordPress is great for SEO optimization.</p><h2>SEO Best Practices</h2>';
		$focus_keyword = 'WordPress';

		$result = $this->meta->get_seo_analysis( $this->post_id, $content, $focus_keyword );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertGreaterThan( 0, $result['score'] );
	}

	/**
	 * Test SEO analysis uses postmeta focus keyword when not provided
	 */
	public function test_seo_analysis_uses_postmeta_focus_keyword() {
		$result = $this->meta->get_seo_analysis( $this->post_id );

		// Should use 'SEO' from postmeta.
		$this->assertGreaterThan( 0, $result['score'] );
	}

	/**
	 * Test analysis with non-existent post
	 */
	public function test_seo_analysis_with_invalid_post() {
		$result = $this->meta->get_seo_analysis( 999999 );

		$this->assertEquals( 0, $result['score'] );
		$this->assertEquals( 'red', $result['color'] );
	}
}
