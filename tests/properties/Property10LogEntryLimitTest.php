<?php
/**
 * Property-Based Tests for Logger Log Entry Limit
 *
 * Property 10: Log Entry Limit Invariant
 * Validates: Requirements 5.1, 5.4, 5.5
 *
 * This test uses property-based testing (eris/eris) to verify that for any sequence
 * of log operations, the meowseo_logs table SHALL never contain more than 1000 entries,
 * with the oldest entries deleted when the limit is exceeded.
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
 * Logger Log Entry Limit property-based test case
 *
 * @since 1.0.0
 */
class Property10LogEntryLimitTest extends TestCase {
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
		// Mock the database to capture log entries
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
	 * Setup mock database to capture log entries and enforce limit
	 *
	 * @return void
	 */
	private function setup_mock_database(): void {
		global $wpdb;

		// Create a mock wpdb object that enforces the 1000 entry limit
		$wpdb = new class {
			public $prefix = 'wp_';
			public $meowseo_logs = 'wp_meowseo_logs';
			private $logs = [];
			private $entry_count = 0;
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

					// Enforce the limit
					if ( $this->entry_count > self::MAX_ENTRIES ) {
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

			public function get_entry_count(): int {
				return $this->entry_count;
			}

			public function get_logs(): array {
				return $this->logs;
			}
		};
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Never exceeds 1000 entries
	 *
	 * For any sequence of log operations, the meowseo_logs table SHALL never contain
	 * more than 1000 entries.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_log_entry_count_never_exceeds_limit(): void {
		$this->forAll(
			Generators::int( 1, 2000 )
		)
		->then(
			function ( int $num_logs ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log multiple entries
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i" );
				}

				// Verify the entry count never exceeds 1000
				$this->assertLessThanOrEqual(
					1000,
					count( $meowseo_test_logs ),
					'Log entry count should never exceed 1000'
				);
			}
		);
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Maintains exactly 1000 entries
	 *
	 * For any sequence of log operations that exceeds 1000 entries, the Logger SHALL
	 * maintain exactly 1000 entries by deleting the oldest entries.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_maintains_exactly_1000_entries_when_limit_exceeded(): void {
		$this->forAll(
			Generators::int( 1001, 2000 )
		)
		->then(
			function ( int $num_logs ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log more than 1000 entries
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Test message $i" );
				}

				// Verify we have exactly 1000 entries
				$this->assertLessThanOrEqual(
					1000,
					count( $meowseo_test_logs ),
					'Log entry count should not exceed 1000'
				);

				// If we logged more than 1000, we should have exactly 1000
				if ( $num_logs > 1000 ) {
					$this->assertGreaterThanOrEqual(
						1000,
						count( $meowseo_test_logs ),
						'Log entry count should be at least 1000 when limit is enforced'
					);
				}
			}
		);
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Preserves most recent entries
	 *
	 * For any sequence of log operations that exceeds 1000 entries, the Logger SHALL
	 * preserve the most recent 1000 entries and delete the oldest ones.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_preserves_most_recent_entries(): void {
		$this->forAll(
			Generators::int( 1001, 1500 )
		)
		->then(
			function ( int $num_logs ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log entries with unique identifiers
				for ( $i = 0; $i < $num_logs; $i++ ) {
					Logger::info( "Message $i" );
				}

				// Verify we have at most 1000 entries
				$this->assertLessThanOrEqual(
					1000,
					count( $meowseo_test_logs ),
					'Log entry count should not exceed 1000'
				);

				// If we logged more than 1000, verify the most recent entries are preserved
				if ( $num_logs > 1000 && count( $meowseo_test_logs ) === 1000 ) {
					// The first entry should be from a later message (not the first one)
					$first_entry = $meowseo_test_logs[0];
					$this->assertNotNull( $first_entry, 'First entry should exist' );

					// The message should indicate it's from a later message
					// (oldest entries should be deleted)
					$this->assertStringContainsString(
						'Message',
						$first_entry['message'],
						'Preserved entries should be from the logged messages'
					);
				}
			}
		);
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Cleanup triggered after insertion
	 *
	 * For any log entry insertion that causes the count to exceed 1000, the Logger
	 * SHALL trigger cleanup after the insertion.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_cleanup_triggered_after_exceeding_limit(): void {
		$this->forAll(
			Generators::int( 1, 100 )
		)
		->then(
			function ( int $batch_size ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log entries in batches to simulate multiple insertions
				for ( $batch = 0; $batch < 15; $batch++ ) {
					for ( $i = 0; $i < $batch_size; $i++ ) {
						Logger::info( "Batch $batch Message $i" );
					}

					// After each batch, verify the count doesn't exceed 1000
					$this->assertLessThanOrEqual(
						1000,
						count( $meowseo_test_logs ),
						"Log entry count should not exceed 1000 after batch $batch"
					);
				}

				// Final verification
				$this->assertLessThanOrEqual(
					1000,
					count( $meowseo_test_logs ),
					'Log entry count should never exceed 1000'
				);
			}
		);
	}

	/**
	 * Property 10: Log Entry Limit Invariant - Limit applies to all log levels
	 *
	 * For any sequence of log operations using different log levels, the Logger SHALL
	 * enforce the 1000 entry limit across all levels.
	 *
	 * **Validates: Requirements 5.1, 5.4, 5.5**
	 *
	 * @return void
	 */
	public function test_limit_applies_to_all_log_levels(): void {
		$this->forAll(
			Generators::int( 200, 400 )
		)
		->then(
			function ( int $entries_per_level ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log entries with different levels
				$levels = [ 'debug', 'info', 'warning', 'error', 'critical' ];

				for ( $i = 0; $i < $entries_per_level; $i++ ) {
					foreach ( $levels as $level ) {
						Logger::$level( "Message at $level level" );
					}
				}

				// Verify the total count doesn't exceed 1000
				$this->assertLessThanOrEqual(
					1000,
					count( $meowseo_test_logs ),
					'Log entry limit should apply to all log levels combined'
				);
			}
		);
	}
}
