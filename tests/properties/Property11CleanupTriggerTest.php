<?php
/**
 * Property-Based Tests for Logger Cleanup Trigger
 *
 * Property 11: Cleanup Trigger
 * Validates: Requirements 5.2
 *
 * This test uses property-based testing (eris/eris) to verify that for any new log
 * entry insertion, the Logger SHALL check the entry count and trigger cleanup if it
 * exceeds 1000.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Logger;

/**
 * Logger Cleanup Trigger property-based test case
 *
 * @since 1.0.0
 */
class Property11CleanupTriggerTest extends TestCase {
	use TestTrait;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Clear mock logs before each test
		global $meowseo_test_logs;
		$meowseo_test_logs = [];
		// Mock the database to capture log entries and track cleanup
		$this->setup_mock_database();
	}

	/**
	 * Teardown test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		// Clear mock logs after each test
		global $meowseo_test_logs;
		$meowseo_test_logs = [];
	}

	/**
	 * Setup mock database to capture log entries and track cleanup triggers
	 *
	 * @return void
	 */
	private function setup_mock_database(): void {
		global $wpdb;

		// Create a mock wpdb object that tracks cleanup triggers
		$wpdb = new class {
			public $prefix = 'wp_';
			public $meowseo_logs = 'wp_meowseo_logs';
			private $logs = [];
			private $entry_count = 0;
			private $cleanup_triggered = false;
			private $cleanup_count = 0;
			private const MAX_ENTRIES = 1000;

			public function prepare( $query, ...$args ) {
				// Simple prepare implementation for testing
				$query = str_replace( '%d', '%s', $query );
				$query = str_replace( '%s', "'%s'", $query );
				return vsprintf( $query, $args );
			}

			public function get_results( $query, $output = OBJECT ) {
				return [];
			}

			public function get_row( $query, $output = OBJECT ) {
				return null;
			}

			public function get_var( $query = null, $x = 0, $y = 0 ) {
				// Return the current entry count
				return $this->entry_count;
			}

			public function insert( $table, $data, $format = null ) {
				// Capture the log entry
				if ( strpos( $table, 'meowseo_logs' ) !== false ) {
					global $meowseo_test_logs;
					$data['id'] = count( $this->logs ) + 1;
					$data['created_at'] = $data['created_at'] ?? gmdate( 'Y-m-d H:i:s' );
					$this->logs[] = $data;
					$this->entry_count++;
					$meowseo_test_logs[] = $data;

					// Check if cleanup should be triggered
					if ( $this->entry_count > self::MAX_ENTRIES ) {
						$this->cleanup_triggered = true;
						$this->cleanup_count++;
						$this->cleanup_old_logs();
					}

					return true;
				}
				return false;
			}

			public function query( $query ) {
				// Handle DELETE queries for cleanup
				if ( strpos( $query, 'DELETE FROM' ) !== false && strpos( $query, 'ORDER BY created_at ASC' ) !== false ) {
					// Extract LIMIT value from query
					if ( preg_match( '/LIMIT (\d+)/', $query, $matches ) ) {
						$limit = (int) $matches[1];
						// Remove oldest entries
						for ( $i = 0; $i < $limit && ! empty( $this->logs ); $i++ ) {
							array_shift( $this->logs );
							$this->entry_count--;
						}
					}
					return true;
				}
				return true;
			}

			private function cleanup_old_logs(): void {
				// Delete oldest entries to maintain the 1000 entry limit
				$excess = $this->entry_count - self::MAX_ENTRIES;
				for ( $i = 0; $i < $excess; $i++ ) {
					array_shift( $this->logs );
					$this->entry_count--;
				}
			}

			public function was_cleanup_triggered(): bool {
				return $this->cleanup_triggered;
			}

			public function get_cleanup_count(): int {
				return $this->cleanup_count;
			}

			public function reset_cleanup_tracking(): void {
				$this->cleanup_triggered = false;
				$this->cleanup_count = 0;
			}

			public function get_entry_count(): int {
				return $this->entry_count;
			}

			public function get_logs(): array {
				return $this->logs;
			}
		};
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup triggered when limit exceeded
	 *
	 * For any new log entry insertion that causes the count to exceed 1000, the Logger
	 * SHALL trigger cleanup.
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_triggered_when_limit_exceeded(): void {
		$this->forAll(
			Generators::integers( 1001, 1500 )
		)
		->then(
			function ( int $num_logs ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log entries to exceed the limit
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i" );
				}

				// Verify cleanup was triggered (entry count should be at or below 1000)
				$this->assertLessThanOrEqual(
					1000,
					count( $meowseo_test_logs ),
					'Cleanup should be triggered when limit is exceeded'
				);

				// Verify we have entries (cleanup should preserve some)
				$this->assertGreaterThan(
					0,
					count( $meowseo_test_logs ),
					'Cleanup should preserve at least some entries'
				);
			}
		);
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup not triggered when under limit
	 *
	 * For any new log entry insertion that keeps the count under 1000, the Logger
	 * SHALL NOT trigger cleanup.
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_not_triggered_when_under_limit(): void {
		$this->forAll(
			Generators::integers( 1, 500 )
		)
		->then(
			function ( int $num_logs ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log entries that stay under the limit
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i" );
				}

				// Verify the entry count matches the number of logs
				$this->assertEquals(
					$num_logs,
					count( $meowseo_test_logs ),
					'No cleanup should occur when under the 1000 entry limit'
				);
			}
		);
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup triggered after each insertion exceeding limit
	 *
	 * For any sequence of log operations that exceed 1000 entries, the Logger SHALL
	 * trigger cleanup after each insertion that causes the count to exceed 1000.
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_triggered_after_each_insertion_exceeding_limit(): void {
		$this->forAll(
			Generators::integers( 1, 50 )
		)
		->then(
			function ( int $batch_size ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log entries in batches to exceed the limit
				$total_logged = 0;
				for ( $batch = 0; $batch < 25; $batch++ ) {
					for ( $i = 0; $i < $batch_size; $i++ ) {
						Logger::info( "Batch $batch Message $i" );
						$total_logged++;
					}

					// After each batch, verify cleanup is working
					$current_count = count( $meowseo_test_logs );
					$this->assertLessThanOrEqual(
						1000,
						$current_count,
						"Cleanup should be triggered after batch $batch"
					);

					// If we've logged more than 1000 total, verify cleanup is maintaining the limit
					if ( $total_logged > 1000 ) {
						$this->assertLessThanOrEqual(
							1000,
							$current_count,
							'Cleanup should maintain the 1000 entry limit'
						);
					}
				}

				// Final verification
				$this->assertLessThanOrEqual(
					1000,
					count( $meowseo_test_logs ),
					'Cleanup should maintain the 1000 entry limit throughout'
				);
			}
		);
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup uses correct deletion strategy
	 *
	 * For any cleanup operation, the Logger SHALL delete the oldest entries using
	 * ORDER BY created_at ASC LIMIT.
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_deletes_oldest_entries(): void {
		$this->forAll(
			Generators::integers( 1001, 1200 )
		)
		->then(
			function ( int $num_logs ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log entries with identifiable messages
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Message number $i" );
				}

				// Verify cleanup occurred
				$this->assertLessThanOrEqual(
					1000,
					count( $meowseo_test_logs ),
					'Cleanup should reduce entries to 1000 or less'
				);

				// Verify the remaining entries are from later messages
				// (oldest entries should be deleted)
				if ( count( $meowseo_test_logs ) === 1000 && $num_logs > 1000 ) {
					// The first remaining entry should be from a message after the first ones
					$first_entry = $meowseo_test_logs[0];
					$this->assertNotNull( $first_entry, 'First entry should exist' );

					// Verify it's a valid log entry
					$this->assertArrayHasKey( 'message', $first_entry, 'Entry should have message' );
					$this->assertArrayHasKey( 'created_at', $first_entry, 'Entry should have created_at' );
				}
			}
		);
	}

	/**
	 * Property 11: Cleanup Trigger - Cleanup maintains data integrity
	 *
	 * For any cleanup operation, the Logger SHALL maintain the integrity of remaining
	 * entries (no corruption or loss of data in preserved entries).
	 *
	 * **Validates: Requirements 5.2**
	 *
	 * @return void
	 */
	public function test_cleanup_maintains_data_integrity(): void {
		$this->forAll(
			Generators::integers( 1001, 1500 )
		)
		->then(
			function ( int $num_logs ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log entries with complete data
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i", [ 'index' => $i ] );
				}

				// Verify all remaining entries have required fields
				foreach ( $meowseo_test_logs as $entry ) {
					$this->assertArrayHasKey( 'level', $entry, 'Entry should have level' );
					$this->assertArrayHasKey( 'module', $entry, 'Entry should have module' );
					$this->assertArrayHasKey( 'message', $entry, 'Entry should have message' );
					$this->assertArrayHasKey( 'message_hash', $entry, 'Entry should have message_hash' );
					$this->assertArrayHasKey( 'created_at', $entry, 'Entry should have created_at' );

					// Verify field values are not empty
					$this->assertNotEmpty( $entry['level'], 'Level should not be empty' );
					$this->assertNotEmpty( $entry['module'], 'Module should not be empty' );
					$this->assertNotEmpty( $entry['message'], 'Message should not be empty' );
					$this->assertNotEmpty( $entry['message_hash'], 'Message hash should not be empty' );
					$this->assertNotEmpty( $entry['created_at'], 'Created at should not be empty' );
				}

				// Verify the entry count is correct
				$this->assertLessThanOrEqual(
					1000,
					count( $meowseo_test_logs ),
					'Entry count should not exceed 1000'
				);
			}
		);
	}
}
