<?php
/**
 * Property-Based Tests for Sitemap Lock Exclusivity
 *
 * Property 12: Sitemap lock is mutually exclusive
 * Validates: Requirement 6.4
 *
 * This test uses property-based testing (eris/eris) to verify that the sitemap
 * lock mechanism using wp_cache_add() is atomic and mutually exclusive. Only one
 * process can acquire the lock at a time, preventing cache stampede during
 * concurrent sitemap generation requests.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Cache;

/**
 * Sitemap Lock Exclusivity property-based test case
 *
 * @since 1.0.0
 */
class Property12SitemapLockExclusivityTest extends TestCase {
	use TestTrait;

	/**
	 * Lock TTL for testing
	 *
	 * @var int
	 */
	private const LOCK_TTL = 60;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Clear any existing locks before each test
		Cache::delete( 'test_lock_key' );
	}

	/**
	 * Teardown test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		// Clean up locks after each test
		Cache::delete( 'test_lock_key' );
	}

	/**
	 * Property 12: Sitemap lock is mutually exclusive
	 *
	 * For any lock key and TTL, when one process acquires the lock via Cache::add(),
	 * concurrent attempts to acquire the same lock must fail until the lock is released.
	 *
	 * This property verifies:
	 * 1. Only one process can acquire the lock at a time
	 * 2. When a lock is held, concurrent attempts to acquire it fail
	 * 3. The lock is released after generation completes
	 * 4. Lock acquisition is atomic (wp_cache_add behavior)
	 *
	 * **Validates: Requirement 6.4**
	 *
	 * @return void
	 */
	public function test_sitemap_lock_is_mutually_exclusive(): void {
		$this->forAll(
			Generators::string(),
			Generators::choose( 1, 300 )
		)
		->then(
			function ( string $lock_suffix, int $ttl ) {
				// Skip empty lock suffixes
				if ( empty( $lock_suffix ) ) {
					return;
				}

				// Sanitize lock suffix to valid characters
				$lock_suffix = preg_replace( '/[^a-zA-Z0-9_-]/', '', $lock_suffix );
				if ( empty( $lock_suffix ) ) {
					return;
				}

				$lock_key = 'test_lock_' . $lock_suffix;

				// Clean up any existing lock
				Cache::delete( $lock_key );

				// First process acquires the lock
				$first_acquisition = Cache::add( $lock_key, 1, $ttl );

				// Verify first acquisition succeeds
				$this->assertTrue(
					$first_acquisition,
					'First process should successfully acquire the lock'
				);

				// Second process attempts to acquire the same lock
				$second_acquisition = Cache::add( $lock_key, 1, $ttl );

				// Verify second acquisition fails (lock is held)
				$this->assertFalse(
					$second_acquisition,
					'Second process should fail to acquire the lock while it is held'
				);

				// Third process also attempts to acquire the lock
				$third_acquisition = Cache::add( $lock_key, 1, $ttl );

				// Verify third acquisition also fails
				$this->assertFalse(
					$third_acquisition,
					'Third process should also fail to acquire the lock while it is held'
				);

				// Release the lock
				Cache::delete( $lock_key );

				// After release, a new process can acquire the lock
				$reacquisition = Cache::add( $lock_key, 1, $ttl );

				// Verify lock can be re-acquired after release
				$this->assertTrue(
					$reacquisition,
					'Lock should be re-acquirable after release'
				);

				// Clean up
				Cache::delete( $lock_key );
			}
		);
	}

	/**
	 * Property: Lock acquisition is atomic
	 *
	 * For any lock key, Cache::add() must be atomic - it either succeeds completely
	 * or fails completely, with no partial state.
	 *
	 * @return void
	 */
	public function test_lock_acquisition_is_atomic(): void {
		$this->forAll(
			Generators::string(),
			Generators::choose( 1, 300 )
		)
		->then(
			function ( string $lock_suffix, int $ttl ) {
				// Skip empty lock suffixes
				if ( empty( $lock_suffix ) ) {
					return;
				}

				// Sanitize lock suffix
				$lock_suffix = preg_replace( '/[^a-zA-Z0-9_-]/', '', $lock_suffix );
				if ( empty( $lock_suffix ) ) {
					return;
				}

				$lock_key = 'test_lock_' . $lock_suffix;
				Cache::delete( $lock_key );

				// Attempt to acquire lock
				$acquired = Cache::add( $lock_key, 1, $ttl );

				if ( $acquired ) {
					// If acquisition succeeded, verify the lock value is set
					$lock_value = Cache::get( $lock_key );
					$this->assertNotFalse(
						$lock_value,
						'Lock value should be retrievable after successful acquisition'
					);
				} else {
					// If acquisition failed, verify the lock was already held
					$lock_value = Cache::get( $lock_key );
					$this->assertNotFalse(
						$lock_value,
						'Lock value should exist if acquisition failed'
					);
				}

				// Clean up
				Cache::delete( $lock_key );
			}
		);
	}

	/**
	 * Property: Lock prevents concurrent access
	 *
	 * For any lock key, once acquired, the lock must prevent all concurrent
	 * acquisition attempts until explicitly released.
	 *
	 * @return void
	 */
	public function test_lock_prevents_concurrent_access(): void {
		$this->forAll(
			Generators::string(),
			Generators::choose( 1, 10 )
		)
		->then(
			function ( string $lock_suffix, int $concurrent_attempts ) {
				// Skip empty lock suffixes
				if ( empty( $lock_suffix ) ) {
					return;
				}

				// Sanitize lock suffix
				$lock_suffix = preg_replace( '/[^a-zA-Z0-9_-]/', '', $lock_suffix );
				if ( empty( $lock_suffix ) ) {
					return;
				}

				$lock_key = 'test_lock_' . $lock_suffix;
				Cache::delete( $lock_key );

				// First process acquires the lock
				$first_acquired = Cache::add( $lock_key, 1, self::LOCK_TTL );
				$this->assertTrue( $first_acquired, 'First process should acquire lock' );

				// Simulate multiple concurrent attempts
				$failed_attempts = 0;
				for ( $i = 0; $i < $concurrent_attempts; $i++ ) {
					$attempt = Cache::add( $lock_key, 1, self::LOCK_TTL );
					if ( ! $attempt ) {
						$failed_attempts++;
					}
				}

				// All concurrent attempts should fail
				$this->assertEquals(
					$concurrent_attempts,
					$failed_attempts,
					'All concurrent lock acquisition attempts should fail'
				);

				// Clean up
				Cache::delete( $lock_key );
			}
		);
	}

	/**
	 * Property: Lock release allows re-acquisition
	 *
	 * For any lock key, after the lock is released, subsequent processes
	 * must be able to acquire it again.
	 *
	 * @return void
	 */
	public function test_lock_release_allows_reacquisition(): void {
		$this->forAll(
			Generators::string(),
			Generators::choose( 1, 5 )
		)
		->then(
			function ( string $lock_suffix, int $reacquisition_cycles ) {
				// Skip empty lock suffixes
				if ( empty( $lock_suffix ) ) {
					return;
				}

				// Sanitize lock suffix
				$lock_suffix = preg_replace( '/[^a-zA-Z0-9_-]/', '', $lock_suffix );
				if ( empty( $lock_suffix ) ) {
					return;
				}

				$lock_key = 'test_lock_' . $lock_suffix;
				Cache::delete( $lock_key );

				// Simulate multiple acquire-release cycles
				for ( $cycle = 0; $cycle < $reacquisition_cycles; $cycle++ ) {
					// Acquire lock
					$acquired = Cache::add( $lock_key, 1, self::LOCK_TTL );
					$this->assertTrue(
						$acquired,
						"Lock should be acquirable in cycle $cycle"
					);

					// Release lock
					Cache::delete( $lock_key );

					// Verify lock is released
					$lock_value = Cache::get( $lock_key );
					$this->assertFalse(
						$lock_value,
						"Lock should be released after delete in cycle $cycle"
					);
				}

				// Clean up
				Cache::delete( $lock_key );
			}
		);
	}

	/**
	 * Property: Lock value is consistent
	 *
	 * For any lock key, the lock value stored must be consistent and retrievable
	 * while the lock is held.
	 *
	 * @return void
	 */
	public function test_lock_value_is_consistent(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $lock_suffix ) {
				// Skip empty lock suffixes
				if ( empty( $lock_suffix ) ) {
					return;
				}

				// Sanitize lock suffix
				$lock_suffix = preg_replace( '/[^a-zA-Z0-9_-]/', '', $lock_suffix );
				if ( empty( $lock_suffix ) ) {
					return;
				}

				$lock_key = 'test_lock_' . $lock_suffix;
				Cache::delete( $lock_key );

				// Acquire lock with value 1
				$acquired = Cache::add( $lock_key, 1, self::LOCK_TTL );
				$this->assertTrue( $acquired, 'Lock should be acquired' );

				// Retrieve lock value multiple times
				$value1 = Cache::get( $lock_key );
				$value2 = Cache::get( $lock_key );
				$value3 = Cache::get( $lock_key );

				// All retrievals should return the same value
				$this->assertEquals(
					$value1,
					$value2,
					'Lock value should be consistent across retrievals'
				);

				$this->assertEquals(
					$value2,
					$value3,
					'Lock value should remain consistent'
				);

				// Lock value should be 1 (what we set)
				$this->assertEquals(
					1,
					$value1,
					'Lock value should be the value we set'
				);

				// Clean up
				Cache::delete( $lock_key );
			}
		);
	}
}

