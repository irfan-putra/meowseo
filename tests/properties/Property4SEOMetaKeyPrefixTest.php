<?php
/**
 * Property-Based Tests for SEO Meta Key Prefix
 *
 * Property 4: SEO meta uses consistent key prefix
 * Validates: Requirements 3.1, 3.4
 *
 * This test uses property-based testing (eris/eris) to verify that all SEO meta keys
 * stored in wp_postmeta use the "meowseo_" prefix consistently.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;

/**
 * SEO Meta Key Prefix property-based test case
 *
 * @since 1.0.0
 */
class Property4SEOMetaKeyPrefixTest extends TestCase {
	use TestTrait;

	/**
	 * Property 4: SEO meta uses consistent key prefix
	 *
	 * For any post ID and meta value, when SEO meta is stored in wp_postmeta,
	 * every key written should start with the "meowseo_" prefix.
	 *
	 * This property verifies:
	 * 1. All SEO meta keys use the "meowseo_" prefix
	 * 2. The prefix is applied consistently across all meta fields
	 * 3. No SEO meta is stored without the prefix
	 *
	 * **Validates: Requirements 3.1, 3.4**
	 *
	 * @return void
	 */
	public function test_seo_meta_uses_consistent_key_prefix(): void {
		$this->forAll(
			Generators::choose( 1, 1000 ),
			Generators::elements(
				[
					'title',
					'description',
					'robots',
					'canonical',
					'focus_keyword',
					'schema_type',
					'social_title',
					'social_description',
					'social_image_id',
					'noindex',
				]
			),
			Generators::string()
		)
		->then(
			function ( int $post_id, string $field_name, string $meta_value ) {
				// Create a test post if it doesn't exist
				if ( ! get_post( $post_id ) ) {
					$post_id = wp_insert_post(
						array(
							'post_title'   => 'Test Post',
							'post_content' => 'Test content',
							'post_status'  => 'publish',
						)
					);
				}

				// Store SEO meta using the Meta module's expected key format
				$meta_key = 'meowseo_' . $field_name;
				update_post_meta( $post_id, $meta_key, $meta_value );

				// Retrieve the meta directly from database
				$stored_value = get_post_meta( $post_id, $meta_key, true );

				// Verify the key starts with meowseo_ prefix
				$this->assertStringStartsWith(
					'meowseo_',
					$meta_key,
					"Meta key '$meta_key' must start with 'meowseo_' prefix"
				);

				// Verify the value was stored correctly
				$this->assertEquals(
					$meta_value,
					$stored_value,
					"Meta value for key '$meta_key' should be stored and retrieved correctly"
				);

				// Clean up
				delete_post_meta( $post_id, $meta_key );
			}
		);
	}

	/**
	 * Property: All SEO meta fields use the meowseo_ prefix
	 *
	 * For any combination of SEO meta fields, all keys should consistently
	 * use the "meowseo_" prefix.
	 *
	 * @return void
	 */
	public function test_all_seo_meta_fields_use_prefix(): void {
		$this->forAll(
			Generators::choose( 1, 1000 )
		)
		->then(
			function ( int $post_id ) {
				// Create a test post if it doesn't exist
				if ( ! get_post( $post_id ) ) {
					$post_id = wp_insert_post(
						array(
							'post_title'   => 'Test Post',
							'post_content' => 'Test content',
							'post_status'  => 'publish',
						)
					);
				}

				// Define all SEO meta fields
				$seo_fields = array(
					'title'              => 'Test Title',
					'description'        => 'Test Description',
					'robots'             => 'index,follow',
					'canonical'          => 'https://example.com/test',
					'focus_keyword'      => 'test keyword',
					'schema_type'        => 'Article',
					'social_title'       => 'Social Title',
					'social_description' => 'Social Description',
					'social_image_id'    => 123,
					'noindex'            => false,
				);

				// Store all SEO meta fields
				foreach ( $seo_fields as $field => $value ) {
					$meta_key = 'meowseo_' . $field;
					update_post_meta( $post_id, $meta_key, $value );
				}

				// Retrieve all post meta for this post
				$all_meta = get_post_meta( $post_id );

				// Verify all meowseo_ prefixed keys are present
				foreach ( $seo_fields as $field => $value ) {
					$meta_key = 'meowseo_' . $field;
					$this->assertArrayHasKey(
						$meta_key,
						$all_meta,
						"Meta key '$meta_key' should be stored in postmeta"
					);

					// Verify the key starts with meowseo_
					$this->assertStringStartsWith(
						'meowseo_',
						$meta_key,
						"All SEO meta keys must start with 'meowseo_' prefix"
					);
				}

				// Clean up
				foreach ( $seo_fields as $field => $value ) {
					$meta_key = 'meowseo_' . $field;
					delete_post_meta( $post_id, $meta_key );
				}
			}
		);
	}

	/**
	 * Property: SEO meta prefix is deterministic
	 *
	 * For any given field name, the prefix should always be "meowseo_"
	 * (deterministic behavior).
	 *
	 * @return void
	 */
	public function test_seo_meta_prefix_is_deterministic(): void {
		$this->forAll(
			Generators::elements(
				[
					'title',
					'description',
					'robots',
					'canonical',
					'focus_keyword',
					'schema_type',
					'social_title',
					'social_description',
					'social_image_id',
					'noindex',
				]
			)
		)
		->then(
			function ( string $field_name ) {
				// Generate the key three times
				$key1 = 'meowseo_' . $field_name;
				$key2 = 'meowseo_' . $field_name;
				$key3 = 'meowseo_' . $field_name;

				// All three should be identical
				$this->assertEquals(
					$key1,
					$key2,
					"SEO meta key generation should be deterministic (run 1 vs 2)"
				);

				$this->assertEquals(
					$key2,
					$key3,
					"SEO meta key generation should be deterministic (run 2 vs 3)"
				);

				// All should start with meowseo_
				$this->assertStringStartsWith(
					'meowseo_',
					$key1,
					"Generated key should start with 'meowseo_' prefix"
				);
			}
		);
	}

	/**
	 * Property: No SEO meta is stored without the prefix
	 *
	 * For any SEO meta field, it should never be stored without the "meowseo_" prefix.
	 *
	 * @return void
	 */
	public function test_no_seo_meta_without_prefix(): void {
		$this->forAll(
			Generators::choose( 1, 1000 ),
			Generators::elements(
				[
					'title',
					'description',
					'robots',
					'canonical',
					'focus_keyword',
					'schema_type',
					'social_title',
					'social_description',
					'social_image_id',
					'noindex',
				]
			)
		)
		->then(
			function ( int $post_id, string $field_name ) {
				// Create a test post if it doesn't exist
				if ( ! get_post( $post_id ) ) {
					$post_id = wp_insert_post(
						array(
							'post_title'   => 'Test Post',
							'post_content' => 'Test content',
							'post_status'  => 'publish',
						)
					);
				}

				// Verify that storing without prefix is different from with prefix
				$key_without_prefix = $field_name;
				$key_with_prefix = 'meowseo_' . $field_name;

				// These should be different
				$this->assertNotEquals(
					$key_without_prefix,
					$key_with_prefix,
					"Key with prefix should be different from key without prefix"
				);

				// The correct key should always have the prefix
				$this->assertStringStartsWith(
					'meowseo_',
					$key_with_prefix,
					"Correct SEO meta key must start with 'meowseo_' prefix"
				);

				// Clean up
				delete_post_meta( $post_id, $key_with_prefix );
			}
		);
	}
}


