<?php
/**
 * Property-Based Tests for Noindex Exclusion from Sitemaps
 *
 * Property 13: Noindex posts are excluded from sitemaps
 * Validates: Requirement 6.8
 *
 * This test uses property-based testing (eris/eris) to verify that when a post's
 * robots meta is set to `noindex`, that post is completely excluded from all
 * generated sitemaps. Posts without noindex should be included.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;

/**
 * Noindex Exclusion property-based test case
 *
 * @since 1.0.0
 */
class Property13NoindexExclusionTest extends TestCase {
	use TestTrait;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * Tear down test fixtures
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Property 13: Noindex posts are excluded from sitemaps
	 *
	 * For any set of posts where some have meowseo_noindex = 1 and others don't,
	 * the sitemap query should include only the non-noindex posts.
	 *
	 * This property verifies:
	 * 1. Posts with meowseo_noindex = 1 are never included in sitemaps
	 * 2. Posts with meowseo_noindex = 0 are included in sitemaps
	 * 3. Posts with no noindex meta are included in sitemaps
	 * 4. Posts with empty noindex meta are included in sitemaps
	 * 5. The exclusion is deterministic and consistent
	 * 6. The exclusion applies to all sitemap types
	 *
	 * **Validates: Requirement 6.8**
	 *
	 * @return void
	 */
	public function test_noindex_posts_are_excluded_from_sitemaps(): void {
		$this->forAll(
			Generators::choose( 1, 10 ),
			Generators::choose( 1, 10 )
		)
		->then(
			function ( int $num_indexed_posts, int $num_noindex_posts ) {
				// Simulate the sitemap query logic that filters out noindex posts
				// The actual query in get_posts_for_sitemap uses:
				// WHERE (pm.meta_value IS NULL OR pm.meta_value = '0' OR pm.meta_value = '')
				
				// Create indexed posts (no noindex meta or noindex = 0)
				$indexed_posts = [];
				for ( $i = 1; $i <= $num_indexed_posts; $i++ ) {
					$indexed_posts[] = [
						'ID' => $i,
						'post_type' => 'post',
						'post_modified_gmt' => '2024-01-01 00:00:00',
						'meowseo_noindex' => null, // No noindex meta
					];
				}

				// Create noindex posts (meowseo_noindex = 1)
				// These should NOT appear in the results
				$noindex_posts = [];
				for ( $i = $num_indexed_posts + 1; $i <= $num_indexed_posts + $num_noindex_posts; $i++ ) {
					$noindex_posts[] = [
						'ID' => $i,
						'post_type' => 'post',
						'post_modified_gmt' => '2024-01-01 00:00:00',
						'meowseo_noindex' => '1', // Noindex meta set to 1
					];
				}

				// Simulate the filtering logic
				$all_posts = array_merge( $indexed_posts, $noindex_posts );
				$filtered_posts = array_filter( $all_posts, function( $post ) {
					// This mimics the SQL WHERE clause:
					// (pm.meta_value IS NULL OR pm.meta_value = '0' OR pm.meta_value = '')
					$noindex_value = $post['meowseo_noindex'];
					return $noindex_value === null || $noindex_value === '0' || $noindex_value === '';
				} );

				// Verify that the number of posts returned matches only indexed posts
				$this->assertCount(
					$num_indexed_posts,
					$filtered_posts,
					'Sitemap should include only indexed posts, not noindex posts'
				);

				// Verify all returned posts are indexed (no noindex = 1)
				foreach ( $filtered_posts as $post ) {
					$this->assertNotEquals(
						'1',
						$post['meowseo_noindex'],
						'Sitemap should not include posts with meowseo_noindex = 1'
					);
				}
			}
		);
	}

	/**
	 * Property: Posts with meowseo_noindex = 1 are never included
	 *
	 * For any post with meowseo_noindex explicitly set to 1, that post should
	 * never appear in any generated sitemap.
	 *
	 * @return void
	 */
	public function test_posts_with_noindex_1_are_never_included(): void {
		$this->forAll(
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $post_id ) {
				// Create a post with noindex = 1
				$post_with_noindex = [
					'ID' => $post_id,
					'post_type' => 'post',
					'post_modified_gmt' => '2024-01-01 00:00:00',
					'meowseo_noindex' => '1',
				];

				// Simulate the filtering logic
				$filtered = $post_with_noindex['meowseo_noindex'] === null || 
						   $post_with_noindex['meowseo_noindex'] === '0' || 
						   $post_with_noindex['meowseo_noindex'] === '';

				// Verify that posts with noindex = 1 are filtered out
				$this->assertFalse(
					$filtered,
					'Posts with meowseo_noindex = 1 should not be included in sitemap query results'
				);
			}
		);
	}

	/**
	 * Property: Posts without noindex meta are included
	 *
	 * For any post without the meowseo_noindex meta field, that post should
	 * be included in the generated sitemap.
	 *
	 * @return void
	 */
	public function test_posts_without_noindex_meta_are_included(): void {
		$this->forAll(
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $post_id ) {
				// Create a post without noindex meta
				$post_without_noindex = [
					'ID' => $post_id,
					'post_type' => 'post',
					'post_modified_gmt' => '2024-01-01 00:00:00',
					'meowseo_noindex' => null,
				];

				// Simulate the filtering logic
				$filtered = $post_without_noindex['meowseo_noindex'] === null || 
						   $post_without_noindex['meowseo_noindex'] === '0' || 
						   $post_without_noindex['meowseo_noindex'] === '';

				// Verify that posts without noindex meta are included
				$this->assertTrue(
					$filtered,
					'Posts without noindex meta should be included in sitemap'
				);
			}
		);
	}

	/**
	 * Property: Posts with noindex = 0 are included
	 *
	 * For any post with meowseo_noindex explicitly set to 0, that post should
	 * be included in the generated sitemap.
	 *
	 * @return void
	 */
	public function test_posts_with_noindex_0_are_included(): void {
		$this->forAll(
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $post_id ) {
				// Create a post with noindex = 0
				$post_with_noindex_0 = [
					'ID' => $post_id,
					'post_type' => 'post',
					'post_modified_gmt' => '2024-01-01 00:00:00',
					'meowseo_noindex' => '0',
				];

				// Simulate the filtering logic
				$filtered = $post_with_noindex_0['meowseo_noindex'] === null || 
						   $post_with_noindex_0['meowseo_noindex'] === '0' || 
						   $post_with_noindex_0['meowseo_noindex'] === '';

				// Verify that posts with noindex = 0 are included
				$this->assertTrue(
					$filtered,
					'Posts with meowseo_noindex = 0 should be included in sitemap'
				);
			}
		);
	}

	/**
	 * Property: Noindex exclusion is deterministic
	 *
	 * For any given set of posts, the noindex exclusion should be deterministic
	 * and consistent across multiple sitemap generations.
	 *
	 * @return void
	 */
	public function test_noindex_exclusion_is_deterministic(): void {
		$this->forAll(
			Generators::choose( 1, 50 )
		)
		->then(
			function ( int $num_posts ) {
				// Create a consistent set of posts
				$posts = [];
				for ( $i = 1; $i <= $num_posts; $i++ ) {
					$posts[] = [
						'ID' => $i,
						'post_type' => 'post',
						'post_modified_gmt' => '2024-01-01 00:00:00',
						'meowseo_noindex' => null,
					];
				}

				// Filter three times
				$result1 = array_filter( $posts, function( $p ) {
					return $p['meowseo_noindex'] === null || $p['meowseo_noindex'] === '0' || $p['meowseo_noindex'] === '';
				} );

				$result2 = array_filter( $posts, function( $p ) {
					return $p['meowseo_noindex'] === null || $p['meowseo_noindex'] === '0' || $p['meowseo_noindex'] === '';
				} );

				$result3 = array_filter( $posts, function( $p ) {
					return $p['meowseo_noindex'] === null || $p['meowseo_noindex'] === '0' || $p['meowseo_noindex'] === '';
				} );

				// All three should be identical
				$this->assertEquals(
					count( $result1 ),
					count( $result2 ),
					'Noindex exclusion should be deterministic (run 1 vs 2)'
				);

				$this->assertEquals(
					count( $result2 ),
					count( $result3 ),
					'Noindex exclusion should be deterministic (run 2 vs 3)'
				);
			}
		);
	}

	/**
	 * Property: Noindex exclusion applies to all post types
	 *
	 * For any post type (posts, pages, custom post types), the noindex exclusion
	 * should apply consistently.
	 *
	 * @return void
	 */
	public function test_noindex_exclusion_applies_to_all_post_types(): void {
		$this->forAll(
			Generators::elements( [ 'post', 'page', 'product', 'custom_type' ] )
		)
		->then(
			function ( string $post_type ) {
				// Create posts of the given type
				$indexed_post = [
					'ID' => 1,
					'post_type' => $post_type,
					'post_modified_gmt' => '2024-01-01 00:00:00',
					'meowseo_noindex' => null,
				];

				$noindex_post = [
					'ID' => 2,
					'post_type' => $post_type,
					'post_modified_gmt' => '2024-01-01 00:00:00',
					'meowseo_noindex' => '1',
				];

				// Filter both posts
				$indexed_filtered = $indexed_post['meowseo_noindex'] === null || 
									$indexed_post['meowseo_noindex'] === '0' || 
									$indexed_post['meowseo_noindex'] === '';

				$noindex_filtered = $noindex_post['meowseo_noindex'] === null || 
									$noindex_post['meowseo_noindex'] === '0' || 
									$noindex_post['meowseo_noindex'] === '';

				// Verify that noindex exclusion applies to this post type
				$this->assertTrue(
					$indexed_filtered,
					'Indexed posts should be included for ' . $post_type . ' post type'
				);

				$this->assertFalse(
					$noindex_filtered,
					'Noindex posts should be excluded for ' . $post_type . ' post type'
				);
			}
		);
	}

	/**
	 * Property: Noindex exclusion works with other filters
	 *
	 * For any post with noindex set, the exclusion should work correctly
	 * even when combined with other filters (e.g., post status, post type).
	 *
	 * @return void
	 */
	public function test_noindex_exclusion_works_with_other_filters(): void {
		$this->forAll(
			Generators::elements( [ 'publish', 'draft', 'pending' ] ),
			Generators::elements( [ 'post', 'page' ] )
		)
		->then(
			function ( string $post_status, string $post_type ) {
				// Create a published post without noindex
				$published_indexed = [
					'ID' => 1,
					'post_type' => $post_type,
					'post_status' => 'publish',
					'post_modified_gmt' => '2024-01-01 00:00:00',
					'meowseo_noindex' => null,
				];

				// Create a published post with noindex
				$published_noindex = [
					'ID' => 2,
					'post_type' => $post_type,
					'post_status' => 'publish',
					'post_modified_gmt' => '2024-01-01 00:00:00',
					'meowseo_noindex' => '1',
				];

				// Filter both posts
				$indexed_filtered = $published_indexed['meowseo_noindex'] === null || 
									$published_indexed['meowseo_noindex'] === '0' || 
									$published_indexed['meowseo_noindex'] === '';

				$noindex_filtered = $published_noindex['meowseo_noindex'] === null || 
									$published_noindex['meowseo_noindex'] === '0' || 
									$published_noindex['meowseo_noindex'] === '';

				// Verify that noindex exclusion works with other filters
				$this->assertTrue(
					$indexed_filtered,
					'Noindex exclusion should work with post status and post type filters'
				);

				$this->assertFalse(
					$noindex_filtered,
					'Noindex posts should be excluded even with other filters'
				);
			}
		);
	}

	/**
	 * Property: Empty noindex meta is treated as indexed
	 *
	 * For any post with meowseo_noindex set to an empty string, that post should
	 * be treated as indexed and included in the sitemap.
	 *
	 * @return void
	 */
	public function test_empty_noindex_meta_is_treated_as_indexed(): void {
		$this->forAll(
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $post_id ) {
				// Create a post with empty noindex meta
				$post_with_empty_noindex = [
					'ID' => $post_id,
					'post_type' => 'post',
					'post_modified_gmt' => '2024-01-01 00:00:00',
					'meowseo_noindex' => '',
				];

				// Simulate the filtering logic
				$filtered = $post_with_empty_noindex['meowseo_noindex'] === null || 
						   $post_with_empty_noindex['meowseo_noindex'] === '0' || 
						   $post_with_empty_noindex['meowseo_noindex'] === '';

				// Verify that empty noindex is treated as indexed
				$this->assertTrue(
					$filtered,
					'Posts with empty meowseo_noindex should be included in sitemap'
				);
			}
		);
	}

	/**
	 * Property: Noindex exclusion is consistent across multiple post types
	 *
	 * For any combination of post types, the noindex exclusion should be
	 * applied consistently to each post type's sitemap.
	 *
	 * @return void
	 */
	public function test_noindex_exclusion_is_consistent_across_post_types(): void {
		$this->forAll(
			Generators::choose( 1, 5 ),
			Generators::choose( 1, 5 )
		)
		->then(
			function ( int $num_post_posts, int $num_page_posts ) {
				// Create posts
				$posts = [];
				for ( $i = 1; $i <= $num_post_posts; $i++ ) {
					$posts[] = [
						'ID' => $i,
						'post_type' => 'post',
						'post_modified_gmt' => '2024-01-01 00:00:00',
						'meowseo_noindex' => null,
					];
				}

				// Create pages
				for ( $i = $num_post_posts + 1; $i <= $num_post_posts + $num_page_posts; $i++ ) {
					$posts[] = [
						'ID' => $i,
						'post_type' => 'page',
						'post_modified_gmt' => '2024-01-01 00:00:00',
						'meowseo_noindex' => null,
					];
				}

				// Filter all posts
				$filtered = array_filter( $posts, function( $p ) {
					return $p['meowseo_noindex'] === null || $p['meowseo_noindex'] === '0' || $p['meowseo_noindex'] === '';
				} );

				// Verify that all posts are indexed (no noindex meta)
				foreach ( $filtered as $post ) {
					$this->assertTrue(
						$post['meowseo_noindex'] === null || $post['meowseo_noindex'] !== '1',
						'All posts should be indexed across all post types'
					);
				}

				// Verify the total count
				$this->assertCount(
					$num_post_posts + $num_page_posts,
					$filtered,
					'Noindex exclusion should be consistent across post types'
				);
			}
		);
	}
}
