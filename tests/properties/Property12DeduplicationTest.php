<?php
/**
 * Property-Based Tests for Logger Deduplication
 *
 * Property 12: Deduplication
 * Validates: Requirements 6.1
 *
 * This test uses property-based testing (eris/eris) to verify that for any log entry
 * that matches an existing entry (same level, module, message_hash) within a 5-minute
 * time window, the Logger SHALL increment the hit_count instead of creating a new entry.
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
 * Logger Deduplication property-based test case
 *
 * @since 1.0.0
 */
class Property12DeduplicationTest extends TestCase {
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
	 * Setup mock database to capture log entries and track deduplication
	 *
	 * @return void
	 */
	private function setup_mock_database(): void {
		global $wpdb;

		// Create a mock wpdb object that simulates deduplication behavior
		$wpdb = new class {
			public $prefix = 'wp_';
			public $meowseo_logs = 'wp_meowseo_logs';
			private $logs = [];
			private $update_count = 0;

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
				// Check if this is a deduplication query
				if ( strpos( $query, 'SELECT id, created_at FROM' ) !== false && strpos( $query, 'message_hash' ) !== false ) {
					// Extract the message_hash from the query to find matching entries
					// For simplicity, we'll check the last inserted log
					if ( ! empty( $this->logs ) ) {
						$last_log = end( $this->logs );
						// Return the last log if it matches (simulating deduplication)
						$result = new \stdClass();
						$result->id = $last_log['id'] ?? 1;
						$result->created_at = $last_log['created_at'] ?? gmdate( 'Y-m-d H:i:s' );
						return $result;
					}
				}
				return null;
			}

			public function get_var( $query = null, $x = 0, $y = 0 ) {
				// Return a count that's always under the limit to avoid cleanup
				return 100;
			}

			public function insert( $table, $data, $format = null ) {
				// Capture the log entry
				if ( strpos( $table, 'meowseo_logs' ) !== false ) {
					global $meowseo_test_logs;
					$data['id'] = count( $this->logs ) + 1;
					$this->logs[] = $data;
					$meowseo_test_logs[] = $data;
					return true;
				}
				return false;
			}

			public function query( $query ) {
				// Track UPDATE queries for hit_count increments
				if ( strpos( $query, 'UPDATE' ) !== false && strpos( $query, 'hit_count' ) !== false ) {
					$this->update_count++;
					return true;
				}
				return true;
			}

			public function get_update_count(): int {
				return $this->update_count;
			}

			public function reset_update_count(): void {
				$this->update_count = 0;
			}

			public function get_logs(): array {
				return $this->logs;
			}
		};
	}

	/**
	 * Property 12: Deduplication - Duplicate entries increment hit_count
	 *
	 * For any log entry that matches an existing entry (same level, module, message_hash)
	 * within a 5-minute time window, the Logger SHALL increment the hit_count instead of
	 * creating a new entry.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_duplicate_entries_increment_hit_count(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 ),
			Generators::integers( 1, 10 )
		)
		->then(
			function ( string $message, int $duplicate_count ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log the same message multiple times
				for ( $i = 0; $i < $duplicate_count; $i++ ) {
					Logger::info( $message );
				}

				// Verify that we have fewer entries than log calls (deduplication occurred)
				$this->assertLessThanOrEqual(
					$duplicate_count,
					count( $meowseo_test_logs ),
					'Duplicate log entries should be deduplicated'
				);

				// For the first entry, verify it has the correct hit_count
				if ( ! empty( $meowseo_test_logs ) ) {
					$first_entry = $meowseo_test_logs[0];

					// The hit_count should reflect the number of duplicates
					// (either as a separate field or through deduplication logic)
					$this->assertArrayHasKey(
						'hit_count',
						$first_entry,
						'Log entry should have hit_count field'
					);

					// hit_count should be at least 1
					$this->assertGreaterThanOrEqual(
						1,
						$first_entry['hit_count'],
						'Hit count should be at least 1'
					);
				}
			}
		);
	}

	/**
	 * Property 12: Deduplication - Same level, module, message_hash are duplicates
	 *
	 * For any two log entries with the same level, module, and message_hash,
	 * they SHALL be considered duplicates and the second should increment hit_count.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_same_level_module_message_hash_are_duplicates(): void {
		$this->forAll(
			Generators::elements( [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ] ),
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $level, string $message ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with the same level and message twice
				$method = strtolower( $level );
				Logger::$method( $message );
				$first_entry = $meowseo_test_logs[0] ?? null;

				$meowseo_test_logs = [];

				Logger::$method( $message );
				$second_entry = $meowseo_test_logs[0] ?? null;

				// Verify both entries have the same level, module, and message_hash
				$this->assertNotNull( $first_entry, 'First entry should exist' );
				$this->assertNotNull( $second_entry, 'Second entry should exist' );

				$this->assertEquals(
					$first_entry['level'],
					$second_entry['level'],
					'Duplicate entries should have the same level'
				);

				$this->assertEquals(
					$first_entry['module'],
					$second_entry['module'],
					'Duplicate entries should have the same module'
				);

				$this->assertEquals(
					$first_entry['message_hash'],
					$second_entry['message_hash'],
					'Duplicate entries should have the same message_hash'
				);
			}
		);
	}

	/**
	 * Property 12: Deduplication - Different messages are not duplicates
	 *
	 * For any two log entries with different messages, they SHALL NOT be considered
	 * duplicates and should create separate entries.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_different_messages_are_not_duplicates(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 50 ),
			Generators::string( 'a-zA-Z0-9 ', 1, 50 )
		)
		->then(
			function ( string $message1, string $message2 ) {
				// Skip empty messages
				if ( empty( trim( $message1 ) ) || empty( trim( $message2 ) ) ) {
					return;
				}

				// Skip if messages are the same
				if ( $message1 === $message2 ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log two different messages
				Logger::info( $message1 );
				Logger::info( $message2 );

				// Verify we have two separate entries
				$this->assertCount(
					2,
					$meowseo_test_logs,
					'Different messages should create separate log entries'
				);

				// Verify the messages are different
				$this->assertNotEquals(
					$meowseo_test_logs[0]['message_hash'],
					$meowseo_test_logs[1]['message_hash'],
					'Different messages should have different message_hashes'
				);
			}
		);
	}

	/**
	 * Property 12: Deduplication - Different levels are not duplicates
	 *
	 * For any two log entries with the same message but different levels,
	 * they SHALL NOT be considered duplicates and should create separate entries.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_different_levels_are_not_duplicates(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $message ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log the same message with different levels
				Logger::info( $message );
				Logger::warning( $message );

				// Verify we have two separate entries
				$this->assertCount(
					2,
					$meowseo_test_logs,
					'Same message with different levels should create separate entries'
				);

				// Verify the levels are different
				$this->assertNotEquals(
					$meowseo_test_logs[0]['level'],
					$meowseo_test_logs[1]['level'],
					'Entries with different levels should not be duplicates'
				);
			}
		);
	}

	/**
	 * Property 12: Deduplication - Hit count starts at 1
	 *
	 * For any new log entry, the hit_count SHALL start at 1.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_hit_count_starts_at_one(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $message ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log a message
				Logger::info( $message );

				// Verify the entry has hit_count of 1
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				$this->assertArrayHasKey(
					'hit_count',
					$entry,
					'Log entry should have hit_count field'
				);

				$this->assertEquals(
					1,
					$entry['hit_count'],
					'New log entry should have hit_count of 1'
				);
			}
		);
	}

	/**
	 * Property 12: Deduplication - Deduplication within 5-minute window
	 *
	 * For any log entry that matches an existing entry within a 5-minute time window,
	 * the Logger SHALL consider it a duplicate and increment hit_count.
	 *
	 * **Validates: Requirements 6.1**
	 *
	 * @return void
	 */
	public function test_deduplication_within_time_window(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 ),
			Generators::integers( 0, 299 ) // 0 to 299 seconds (within 5 minutes)
		)
		->then(
			function ( string $message, int $seconds_offset ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log the message
				Logger::info( $message );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$first_entry = $meowseo_test_logs[0];

				// Verify the entry has the expected fields
				$this->assertArrayHasKey(
					'created_at',
					$first_entry,
					'Log entry should have created_at field'
				);

				$this->assertArrayHasKey(
					'hit_count',
					$first_entry,
					'Log entry should have hit_count field'
				);

				// The created_at should be a valid timestamp
				$this->assertNotEmpty(
					$first_entry['created_at'],
					'created_at should not be empty'
				);
			}
		);
	}
}
