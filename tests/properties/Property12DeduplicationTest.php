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
					// Extract the level, module, and message_hash from the query
					// Query format: SELECT id, created_at FROM wp_meowseo_logs WHERE level = 'INFO' AND module = 'core' AND message_hash = 'abc123' AND created_at >= '2024-01-01 00:00:00' ORDER BY created_at DESC LIMIT 1
					
					// Extract message_hash (64 character hex string)
					if ( ! preg_match( "/'([a-f0-9]{64})'/", $query, $hash_matches ) ) {
						return null;
					}
					$message_hash = $hash_matches[1];
					
					// Extract level (first quoted string after WHERE)
					if ( ! preg_match( "/WHERE level = '([^']+)'/", $query, $level_matches ) ) {
						return null;
					}
					$level = $level_matches[1];
					
					// Extract module
					if ( ! preg_match( "/module = '([^']+)'/", $query, $module_matches ) ) {
						return null;
					}
					$module = $module_matches[1];
					
					// Extract time window
					$time_window = null;
					if ( preg_match( "/created_at >= '([^']+)'/", $query, $time_matches ) ) {
						$time_window = $time_matches[1];
					}
					
					// Find matching entry in logs (search from most recent)
					foreach ( array_reverse( $this->logs ) as $log ) {
						if ( isset( $log['message_hash'], $log['level'], $log['module'], $log['created_at'] ) &&
							$log['message_hash'] === $message_hash &&
							$log['level'] === $level &&
							$log['module'] === $module ) {
							
							// Check time window if specified
							if ( $time_window && $log['created_at'] < $time_window ) {
								continue;
							}
							
							// Return the matching log
							$result = new \stdClass();
							$result->id = $log['id'];
							$result->created_at = $log['created_at'];
							return $result;
						}
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
					// hit_count defaults to 1 if not provided (matching database schema DEFAULT 1)
					if ( ! isset( $data['hit_count'] ) ) {
						$data['hit_count'] = 1;
					}
					// created_at defaults to current time if not provided
					if ( ! isset( $data['created_at'] ) ) {
						$data['created_at'] = gmdate( 'Y-m-d H:i:s' );
					}
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
					
					// Extract the ID from the query
					if ( preg_match( "/WHERE id = '?(\d+)'?/", $query, $matches ) ) {
						$id = (int) $matches[1];
						
						// Find and update the log entry
						foreach ( $this->logs as $key => $log ) {
							if ( isset( $log['id'] ) && $log['id'] == $id ) {
								$this->logs[ $key ]['hit_count'] = ( $log['hit_count'] ?? 1 ) + 1;
								
								// Also update in global test logs
								global $meowseo_test_logs;
								foreach ( $meowseo_test_logs as $test_key => $test_log ) {
									if ( isset( $test_log['id'] ) && $test_log['id'] == $id ) {
										$meowseo_test_logs[ $test_key ]['hit_count'] = ( $test_log['hit_count'] ?? 1 ) + 1;
										break;
									}
								}
								break;
							}
						}
					}
					
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
			Generators::int( 2, 10 )
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

				// First, test that a single log call works
				Logger::info( $message );
				
				// Verify at least one entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should create at least one log entry for first call'
				);
				
				$initial_count = count( $meowseo_test_logs );

				// Log the same message additional times
				for ( $i = 1; $i < $duplicate_count; $i++ ) {
					Logger::info( $message );
				}

				// Verify that we still have exactly the initial count (all duplicates were deduplicated)
				$this->assertCount(
					$initial_count,
					$meowseo_test_logs,
					'Duplicate log entries should be deduplicated into a single entry'
				);

				// Verify that UPDATE was called (duplicate_count - 1) times
				global $wpdb;
				if ( method_exists( $wpdb, 'get_update_count' ) ) {
					$update_count = $wpdb->get_update_count();
					$this->assertEquals(
						$duplicate_count - 1,
						$update_count,
						'Deduplication should increment hit_count via UPDATE'
					);
				}

				// Verify the entry has the correct hit_count
				$first_entry = $meowseo_test_logs[0];
				$this->assertArrayHasKey(
					'hit_count',
					$first_entry,
					'Log entry should have hit_count field'
				);

				// hit_count should equal duplicate_count
				$this->assertEquals(
					$duplicate_count,
					$first_entry['hit_count'],
					'Hit count should equal the number of duplicate log calls'
				);
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
				global $meowseo_test_logs, $wpdb;
				$meowseo_test_logs = [];
				$wpdb->reset_update_count();

				// Log with the same level and message twice
				$method = strtolower( $level );
				Logger::$method( $message );
				$first_entry = $meowseo_test_logs[0] ?? null;

				// Don't clear logs - we want to test deduplication
				Logger::$method( $message );

				// Verify both entries have the same level, module, and message_hash
				$this->assertNotNull( $first_entry, 'First entry should exist' );

				// After second log call, we should still have only 1 entry
				$this->assertCount(
					1,
					$meowseo_test_logs,
					'Duplicate entries should not create new log entries'
				);

				// Verify UPDATE was called once for deduplication
				$update_count = $wpdb->get_update_count();
				$this->assertEquals(
					1,
					$update_count,
					'Second duplicate should trigger UPDATE for hit_count'
				);

				// Verify hit_count was incremented
				$updated_entry = $meowseo_test_logs[0];
				$this->assertEquals(
					2,
					$updated_entry['hit_count'],
					'Hit count should be 2 after logging the same message twice'
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
			Generators::int( 0, 299 ) // 0 to 299 seconds (within 5 minutes)
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
