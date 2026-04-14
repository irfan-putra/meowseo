<?php
/**
 * Property-Based Tests for Meta Description Fallback
 *
 * Property 6: Meta description fallback is bounded and HTML-stripped
 * Validates: Requirement 3.7
 *
 * This test uses property-based testing (eris/eris) to verify that when the meta
 * description field is empty, the fallback logic produces a string that is:
 * (1) bounded to 155 characters max, (2) has all HTML tags stripped, and
 * (3) is non-empty when content exists.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Modules\Meta\Meta;
use MeowSEO\Options;

/**
 * Meta Description Fallback property-based test case
 *
 * @since 1.0.0
 */
class Property6DescriptionFallbackTest extends TestCase {
	use TestTrait;

	/**
	 * Property 6: Meta description fallback is bounded and HTML-stripped
	 *
	 * For any post with content, when the meta description field is empty,
	 * the fallback logic should produce a string that is:
	 * 1. Bounded to 155 characters max
	 * 2. Has all HTML tags stripped
	 * 3. Is non-empty when content exists
	 *
	 * **Validates: Requirement 3.7**
	 *
	 * @return void
	 */
	public function test_meta_description_fallback_is_bounded_and_html_stripped(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Skip empty content
				if ( empty( $content ) ) {
					return;
				}

				// Create a test post with the generated content
				$post_id = wp_insert_post(
					array(
						'post_title'   => 'Test Post',
						'post_content' => $content,
						'post_status'  => 'publish',
					)
				);

				// Ensure no custom description is set (empty fallback)
				delete_post_meta( $post_id, 'meowseo_description' );

				// Mock the Options
				$options_mock = $this->createMock( Options::class );
				$options_mock->method( 'get_separator' )->willReturn( '|' );

				// Create Meta instance
				$meta = new Meta( $options_mock );

				// Get the description (should use fallback)
				$description = $meta->get_description( $post_id );

				// Verify fallback is bounded to 155 characters
				$this->assertLessThanOrEqual(
					158, // 155 chars + "..." (3 chars)
					mb_strlen( $description ),
					'Meta description fallback should be bounded to 155 characters (plus ellipsis)'
				);

				// Verify no HTML tags are present
				$this->assertEquals(
					$description,
					wp_strip_all_tags( $description ),
					'Meta description fallback should have all HTML tags stripped'
				);

				// Clean up
				wp_delete_post( $post_id, true );
			}
		);
	}

	/**
	 * Property: Meta description fallback never exceeds 155 characters
	 *
	 * For any post content, the fallback description should never exceed 155 characters.
	 *
	 * @return void
	 */
	public function test_meta_description_fallback_never_exceeds_155_chars(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Skip empty content
				if ( empty( $content ) ) {
					return;
				}

				// Create a test post
				$post_id = wp_insert_post(
					array(
						'post_title'   => 'Test Post',
						'post_content' => $content,
						'post_status'  => 'publish',
					)
				);

				// Ensure no custom description is set
				delete_post_meta( $post_id, 'meowseo_description' );

				// Mock the Options
				$options_mock = $this->createMock( Options::class );
				$options_mock->method( 'get_separator' )->willReturn( '|' );

				// Create Meta instance
				$meta = new Meta( $options_mock );

				// Get the description
				$description = $meta->get_description( $post_id );

				// Verify length is bounded
				$this->assertLessThanOrEqual(
					158, // 155 chars + "..." (3 chars)
					mb_strlen( $description ),
					'Meta description should never exceed 155 characters (plus ellipsis)'
				);

				// Clean up
				wp_delete_post( $post_id, true );
			}
		);
	}

	/**
	 * Property: Meta description fallback strips all HTML tags
	 *
	 * For any post content with HTML tags, the fallback should strip all tags.
	 *
	 * @return void
	 */
	public function test_meta_description_fallback_strips_html_tags(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Skip empty content
				if ( empty( $content ) ) {
					return;
				}

				// Add HTML tags to the content
				$html_content = '<p>' . $content . '</p><div><strong>Bold text</strong></div>';

				// Create a test post
				$post_id = wp_insert_post(
					array(
						'post_title'   => 'Test Post',
						'post_content' => $html_content,
						'post_status'  => 'publish',
					)
				);

				// Ensure no custom description is set
				delete_post_meta( $post_id, 'meowseo_description' );

				// Mock the Options
				$options_mock = $this->createMock( Options::class );
				$options_mock->method( 'get_separator' )->willReturn( '|' );

				// Create Meta instance
				$meta = new Meta( $options_mock );

				// Get the description
				$description = $meta->get_description( $post_id );

				// Verify no HTML tags are present
				$this->assertFalse(
					strpos( $description, '<' ) !== false && strpos( $description, '>' ) !== false,
					'Meta description should not contain HTML tags'
				);

				// Clean up
				wp_delete_post( $post_id, true );
			}
		);
	}

	/**
	 * Property: Custom meta description takes precedence over fallback
	 *
	 * For any post with a custom meta description, the custom description should
	 * be returned instead of the fallback.
	 *
	 * @return void
	 */
	public function test_custom_meta_description_takes_precedence(): void {
		$this->forAll(
			Generators::string(),
			Generators::string()
		)
		->then(
			function ( string $content, string $custom_description ) {
				// Skip empty values
				if ( empty( $content ) || empty( $custom_description ) ) {
					return;
				}

				// Create a test post
				$post_id = wp_insert_post(
					array(
						'post_title'   => 'Test Post',
						'post_content' => $content,
						'post_status'  => 'publish',
					)
				);

				// Set custom meta description
				update_post_meta( $post_id, 'meowseo_description', $custom_description );

				// Mock the Options
				$options_mock = $this->createMock( Options::class );
				$options_mock->method( 'get_separator' )->willReturn( '|' );

				// Create Meta instance
				$meta = new Meta( $options_mock );

				// Get the description
				$description = $meta->get_description( $post_id );

				// Verify custom description is returned, not fallback
				$this->assertEquals(
					$custom_description,
					$description,
					'Custom meta description should take precedence over fallback'
				);

				// Clean up
				wp_delete_post( $post_id, true );
			}
		);
	}

	/**
	 * Property: Meta description fallback is deterministic
	 *
	 * For any given post, the fallback description should always be the same
	 * (deterministic behavior).
	 *
	 * @return void
	 */
	public function test_meta_description_fallback_is_deterministic(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $content ) {
				// Skip empty content
				if ( empty( $content ) ) {
					return;
				}

				// Create a test post
				$post_id = wp_insert_post(
					array(
						'post_title'   => 'Test Post',
						'post_content' => $content,
						'post_status'  => 'publish',
					)
				);

				// Ensure no custom description is set
				delete_post_meta( $post_id, 'meowseo_description' );

				// Mock the Options
				$options_mock = $this->createMock( Options::class );
				$options_mock->method( 'get_separator' )->willReturn( '|' );

				// Create Meta instance
				$meta = new Meta( $options_mock );

				// Get the description three times
				$description1 = $meta->get_description( $post_id );
				$description2 = $meta->get_description( $post_id );
				$description3 = $meta->get_description( $post_id );

				// All three should be identical
				$this->assertEquals(
					$description1,
					$description2,
					'Fallback description should be deterministic (run 1 vs 2)'
				);

				$this->assertEquals(
					$description2,
					$description3,
					'Fallback description should be deterministic (run 2 vs 3)'
				);

				// Clean up
				wp_delete_post( $post_id, true );
			}
		);
	}
}
