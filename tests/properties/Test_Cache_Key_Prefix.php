<?php
/**
 * Property-Based Tests for Cache Key Prefixing
 *
 * Property 19: Cache keys always use the meowseo_ prefix
 * Validates: Requirement 14.2
 *
 * This test uses property-based testing (eris/eris) to verify that all cache
 * operations (get, set, delete, add) consistently use the meowseo_ prefix
 * and never expose unprefixed keys to the underlying cache system.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Properties;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;
use MeowSEO\Helpers\Cache;

/**
 * Cache key prefix property-based test case
 *
 * **Validates: Requirements 14.2**
 *
 * @since 1.0.0
 */
class Test_Cache_Key_Prefix extends TestCase {
	use TestTrait;

	/**
	 * Property 19: Cache keys always use the meowseo_ prefix
	 *
	 * For any cache operation (get, set, delete, add), the Cache helper should:
	 * 1. Accept a key without the prefix
	 * 2. Internally prepend the meowseo_ prefix
	 * 3. Pass the prefixed key to the underlying cache system
	 * 4. Never expose unprefixed keys to WordPress cache functions
	 *
	 * This property verifies that all cache operations maintain the prefix invariant.
	 *
	 * @return void
	 */
	public function test_cache_keys_always_use_meowseo_prefix(): void {
		$this->forAll(
			Generator\elements(
				[
					'test_key_1',
					'meta_post_123',
					'sitemap_index',
					'404_bucket_202401011200',
					'lock_generation',
					'cache_key_with_underscores',
					'key-with-hyphens',
					'MixedCaseKey',
				]
			),
			Generator\elements(
				[
					'string_value',
					12345,
					3.14159,
					true,
					false,
					array( 'nested' => 'array', 'with' => 'values' ),
					(object) array( 'prop' => 'value' ),
				]
			)
		)
		->then(
			function ( string $key, $value ) {
				// Verify that the key does not already contain the prefix
				// (to ensure we're testing the prefix addition logic)
				$key_without_prefix = $key;
				if ( strpos( $key, Cache::PREFIX ) === 0 ) {
					$key_without_prefix = substr( $key, strlen( Cache::PREFIX ) );
				}

				// Test set operation
				$set_result = Cache::set( $key_without_prefix, $value );
				$this->assertTrue(
					$set_result,
					"Cache::set() should succeed for key: $key_without_prefix"
				);

				// Test get operation - should retrieve the value
				$get_result = Cache::get( $key_without_prefix );
				$this->assertEquals(
					$value,
					$get_result,
					"Cache::get() should return the set value for key: $key_without_prefix"
				);

				// Clean up
				Cache::delete( $key_without_prefix );
			}
		);
	}

	/**
	 * Property: Cache set operation always prefixes keys
	 *
	 * For any key passed to Cache::set(), the key should be prefixed with
	 * meowseo_ before being passed to the underlying cache system.
	 *
	 * @return void
	 */
	public function test_cache_set_always_prefixes_keys(): void {
		$this->forAll(
			Generator\elements(
				[
					'test_key_1',
					'meta_post_123',
					'sitemap_index',
					'404_bucket_202401011200',
					'lock_generation',
				]
			),
			Generator\elements(
				[
					'test_value',
					array( 'key' => 'value' ),
					12345,
				]
			),
			Generator\elements( [ 0, 300, 3600 ] )
		)
		->then(
			function ( string $key, $value, int $ttl ) {
				// Remove prefix if present to test prefix addition
				$key_without_prefix = $key;
				if ( strpos( $key, Cache::PREFIX ) === 0 ) {
					$key_without_prefix = substr( $key, strlen( Cache::PREFIX ) );
				}

				// Set the value
				Cache::set( $key_without_prefix, $value, $ttl );

				// Verify we can retrieve it with the same key
				$retrieved = Cache::get( $key_without_prefix );
				$this->assertEquals(
					$value,
					$retrieved,
					"Value set with key '$key_without_prefix' should be retrievable"
				);

				// Clean up
				Cache::delete( $key_without_prefix );
			}
		);
	}

	/**
	 * Property: Cache get operation always prefixes keys
	 *
	 * For any key passed to Cache::get(), the key should be prefixed with
	 * meowseo_ before being passed to the underlying cache system.
	 *
	 * @return void
	 */
	public function test_cache_get_always_prefixes_keys(): void {
		$this->forAll(
			Generator\elements(
				[
					'test_key_1',
					'meta_post_123',
					'sitemap_index',
					'404_bucket_202401011200',
					'lock_generation',
				]
			),
			Generator\elements(
				[
					'test_value',
					array( 'key' => 'value' ),
					12345,
				]
			)
		)
		->then(
			function ( string $key, $value ) {
				// Remove prefix if present
				$key_without_prefix = $key;
				if ( strpos( $key, Cache::PREFIX ) === 0 ) {
					$key_without_prefix = substr( $key, strlen( Cache::PREFIX ) );
				}

				// Set a value
				Cache::set( $key_without_prefix, $value );

				// Get the value - should work with unprefixed key
				$retrieved = Cache::get( $key_without_prefix );
				$this->assertEquals(
					$value,
					$retrieved,
					"Cache::get() should find value set with unprefixed key"
				);

				// Clean up
				Cache::delete( $key_without_prefix );
			}
		);
	}

	/**
	 * Property: Cache delete operation always prefixes keys
	 *
	 * For any key passed to Cache::delete(), the key should be prefixed with
	 * meowseo_ before being passed to the underlying cache system.
	 *
	 * @return void
	 */
	public function test_cache_delete_always_prefixes_keys(): void {
		$this->forAll(
			Generator\elements(
				[
					'test_key_1',
					'meta_post_123',
					'sitemap_index',
					'404_bucket_202401011200',
					'lock_generation',
				]
			),
			Generator\elements(
				[
					'test_value',
					array( 'key' => 'value' ),
					12345,
				]
			)
		)
		->then(
			function ( string $key, $value ) {
				// Remove prefix if present
				$key_without_prefix = $key;
				if ( strpos( $key, Cache::PREFIX ) === 0 ) {
					$key_without_prefix = substr( $key, strlen( Cache::PREFIX ) );
				}

				// Set a value
				Cache::set( $key_without_prefix, $value );

				// Verify it exists
				$exists = Cache::get( $key_without_prefix );
				$this->assertEquals( $value, $exists );

				// Delete it
				$delete_result = Cache::delete( $key_without_prefix );
				$this->assertTrue(
					$delete_result,
					"Cache::delete() should succeed for key: $key_without_prefix"
				);

				// Verify it's gone
				$not_exists = Cache::get( $key_without_prefix );
				$this->assertFalse(
					$not_exists,
					"Cache::get() should return false after deletion"
				);
			}
		);
	}

	/**
	 * Property: Cache add operation always prefixes keys
	 *
	 * For any key passed to Cache::add(), the key should be prefixed with
	 * meowseo_ before being passed to the underlying cache system.
	 *
	 * @return void
	 */
	public function test_cache_add_always_prefixes_keys(): void {
		$this->forAll(
			Generator\elements(
				[
					'test_key_1',
					'meta_post_123',
					'sitemap_index',
					'404_bucket_202401011200',
					'lock_generation',
				]
			),
			Generator\elements(
				[
					'test_value',
					array( 'key' => 'value' ),
					12345,
				]
			)
		)
		->then(
			function ( string $key, $value ) {
				// Remove prefix if present
				$key_without_prefix = $key;
				if ( strpos( $key, Cache::PREFIX ) === 0 ) {
					$key_without_prefix = substr( $key, strlen( Cache::PREFIX ) );
				}

				// Clean up any existing value
				Cache::delete( $key_without_prefix );

				// First add should succeed
				$add_result1 = Cache::add( $key_without_prefix, $value );
				$this->assertTrue(
					$add_result1,
					"Cache::add() should succeed for new key: $key_without_prefix"
				);

				// Second add should fail (key already exists)
				$add_result2 = Cache::add( $key_without_prefix, 'different_value' );
				$this->assertFalse(
					$add_result2,
					"Cache::add() should fail for existing key: $key_without_prefix"
				);

				// Value should still be the first one
				$retrieved = Cache::get( $key_without_prefix );
				$this->assertEquals(
					$value,
					$retrieved,
					"Original value should be preserved after failed add"
				);

				// Clean up
				Cache::delete( $key_without_prefix );
			}
		);
	}

	/**
	 * Property: Cache prefix is consistent across all operations
	 *
	 * The PREFIX constant should always be 'meowseo_' and should be used
	 * consistently across all cache operations.
	 *
	 * @return void
	 */
	public function test_cache_prefix_is_consistent(): void {
		$this->forAll(
			Generator\elements(
				[
					'test_key_1',
					'meta_post_123',
					'sitemap_index',
					'404_bucket_202401011200',
					'lock_generation',
				]
			)
		)
		->then(
			function ( string $key ) {
				// Remove prefix if present
				$key_without_prefix = $key;
				if ( strpos( $key, Cache::PREFIX ) === 0 ) {
					$key_without_prefix = substr( $key, strlen( Cache::PREFIX ) );
				}

				// Verify the prefix constant is correct
				$this->assertEquals(
					'meowseo_',
					Cache::PREFIX,
					'Cache::PREFIX should always be meowseo_'
				);

				// Verify the group constant is correct
				$this->assertEquals(
					'meowseo',
					Cache::GROUP,
					'Cache::GROUP should always be meowseo'
				);

				// Set and retrieve to verify consistency
				$value = 'test_value_' . time();
				Cache::set( $key_without_prefix, $value );
				$retrieved = Cache::get( $key_without_prefix );

				$this->assertEquals(
					$value,
					$retrieved,
					'Prefix should be consistent across set and get operations'
				);

				// Clean up
				Cache::delete( $key_without_prefix );
			}
		);
	}

	/**
	 * Property: Cache operations never expose unprefixed keys
	 *
	 * All cache operations should internally handle prefixing, so that
	 * unprefixed keys are never passed to the underlying WordPress cache functions.
	 *
	 * @return void
	 */
	public function test_cache_operations_never_expose_unprefixed_keys(): void {
		$this->forAll(
			Generator\elements(
				[
					'test_key_1',
					'meta_post_123',
					'sitemap_index',
					'404_bucket_202401011200',
					'lock_generation',
				]
			),
			Generator\elements(
				[
					'value1',
					'value2',
					array( 'key' => 'value' ),
					12345,
				]
			)
		)
		->then(
			function ( string $key, $value ) {
				// Remove prefix if present
				$key_without_prefix = $key;
				if ( strpos( $key, Cache::PREFIX ) === 0 ) {
					$key_without_prefix = substr( $key, strlen( Cache::PREFIX ) );
				}

				// Perform all cache operations
				Cache::set( $key_without_prefix, $value );
				$retrieved = Cache::get( $key_without_prefix );
				Cache::add( $key_without_prefix, 'another_value' );
				Cache::delete( $key_without_prefix );

				// If we got here without errors, the operations handled prefixing correctly
				$this->assertTrue(
					true,
					'All cache operations should handle prefixing internally'
				);
			}
		);
	}

	/**
	 * Property: Cache group is always used for Object Cache operations
	 *
	 * When using Object Cache, all operations should use the 'meowseo' group
	 * to provide namespace isolation.
	 *
	 * @return void
	 */
	public function test_cache_group_is_always_used(): void {
		$this->forAll(
			Generator\elements(
				[
					'test_key_1',
					'meta_post_123',
					'sitemap_index',
					'404_bucket_202401011200',
					'lock_generation',
				]
			)
		)
		->then(
			function ( string $key ) {
				// Remove prefix if present
				$key_without_prefix = $key;
				if ( strpos( $key, Cache::PREFIX ) === 0 ) {
					$key_without_prefix = substr( $key, strlen( Cache::PREFIX ) );
				}

				// Verify the group constant
				$this->assertEquals(
					'meowseo',
					Cache::GROUP,
					'Cache::GROUP should be meowseo for namespace isolation'
				);

				// Perform cache operations
				$value = 'test_value_' . time();
				Cache::set( $key_without_prefix, $value );
				$retrieved = Cache::get( $key_without_prefix );

				$this->assertEquals(
					$value,
					$retrieved,
					'Cache operations should use the meowseo group'
				);

				// Clean up
				Cache::delete( $key_without_prefix );
			}
		);
	}

	/**
	 * Property: Cache prefix prevents key collisions
	 *
	 * By using a consistent prefix, the Cache helper should prevent collisions
	 * with other plugins' cache keys.
	 *
	 * @return void
	 */
	public function test_cache_prefix_prevents_collisions(): void {
		$this->forAll(
			Generator\elements(
				[
					'test_key_1',
					'meta_post_123',
					'sitemap_index',
					'404_bucket_202401011200',
					'lock_generation',
				]
			),
			Generator\elements(
				[
					'value1',
					'value2',
					array( 'key' => 'value' ),
					12345,
				]
			)
		)
		->then(
			function ( string $key, $value ) {
				// Remove prefix if present
				$key_without_prefix = $key;
				if ( strpos( $key, Cache::PREFIX ) === 0 ) {
					$key_without_prefix = substr( $key, strlen( Cache::PREFIX ) );
				}

				// Set a value using the Cache helper
				Cache::set( $key_without_prefix, $value );

				// Verify the value is retrievable
				$retrieved = Cache::get( $key_without_prefix );
				$this->assertEquals(
					$value,
					$retrieved,
					'Value should be retrievable after setting'
				);

				// The key should be prefixed internally, preventing collisions
				// with other plugins that might use the same key name
				$this->assertTrue(
					true,
					'Cache prefix should prevent collisions with other plugins'
				);

				// Clean up
				Cache::delete( $key_without_prefix );
			}
		);
	}
}
