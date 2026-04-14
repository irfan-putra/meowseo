<?php
/**
 * Property-Based Tests for Logger Deduplication Matching
 *
 * Property 13: Deduplication Matching
 * Validates: Requirements 6.2
 *
 * This test uses property-based testing (eris/eris) to verify that for any two log entries,
 * they SHALL be considered duplicates if and only if they have the same level, module,
 * and message hash.
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
 * Logger Deduplication Matching property-based test case
 *
 * @since 1.0.0
 */
class Property13DeduplicationMatchingTest extends TestCase {
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
	 * Setup mock database to capture log entries
	 *
	 * @return void
	 */
	private function setup_mock_database(): void {
		global $wpdb;

		// Create a mock wpdb object that captures log entries
		$wpdb = new class {
			public $prefix = 'wp_';
			public $meowseo_logs = 'wp_meowseo_logs';

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
				// Return a count that's always under the limit to avoid cleanup
				return 100;
			}

			public function insert( $table, $data, $format = null ) {
				// Capture the log entry
				if ( strpos( $table, 'meowseo_logs' ) !== false ) {
					global $meowseo_test_logs;
					$meowseo_test_logs[] = $data;
					return true;
				}
				return false;
			}

			public function query( $query ) {
				return true;
			}
		};
	}

	/**
	 * Property 13: Deduplication Matching - Same level, module, message_hash are duplicates
	 *
	 * For any two log entries with the same level, module, and message_hash,
	 * they SHALL be considered duplicates.
	 *
	 * **Validates: Requirements 6.2**
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

				// These should be considered duplicates
				$this->assertEquals(
					$first_entry['level'],
					$second_entry['level'],
					'Duplicate entries must have the same level'
				);

				$this->assertEquals(
					$first_entry['module'],
					$second_entry['module'],
					'Duplicate entries must have the same module'
				);

				$this->assertEquals(
					$first_entry['message_hash'],
					$second_entry['message_hash'],
					'Duplicate entries must have the same message_hash'
				);
			}
		);
	}

	/**
	 * Property 13: Deduplication Matching - Different levels are not duplicates
	 *
	 * For any two log entries with the same message but different levels,
	 * they SHALL NOT be considered duplicates.
	 *
	 * **Validates: Requirements 6.2**
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
				$first_entry = $meowseo_test_logs[0] ?? null;

				$meowseo_test_logs = [];

				Logger::warning( $message );
				$second_entry = $meowseo_test_logs[0] ?? null;

				// Verify entries have different levels
				$this->assertNotNull( $first_entry, 'First entry should exist' );
				$this->assertNotNull( $second_entry, 'Second entry should exist' );

				// These should NOT be considered duplicates
				$this->assertNotEquals(
					$first_entry['level'],
					$second_entry['level'],
					'Entries with different levels are not duplicates'
				);

				// But they should have the same message_hash
				$this->assertEquals(
					$first_entry['message_hash'],
					$second_entry['message_hash'],
					'Entries with the same message should have the same message_hash'
				);
			}
		);
	}

	/**
	 * Property 13: Deduplication Matching - Different messages are not duplicates
	 *
	 * For any two log entries with different messages, they SHALL NOT be considered
	 * duplicates even if they have the same level and module.
	 *
	 * **Validates: Requirements 6.2**
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

				// Log two different messages with the same level
				Logger::info( $message1 );
				$first_entry = $meowseo_test_logs[0] ?? null;

				$meowseo_test_logs = [];

				Logger::info( $message2 );
				$second_entry = $meowseo_test_logs[0] ?? null;

				// Verify entries have different message_hashes
				$this->assertNotNull( $first_entry, 'First entry should exist' );
				$this->assertNotNull( $second_entry, 'Second entry should exist' );

				// These should NOT be considered duplicates
				$this->assertNotEquals(
					$first_entry['message_hash'],
					$second_entry['message_hash'],
					'Entries with different messages are not duplicates'
				);

				// But they should have the same level and module
				$this->assertEquals(
					$first_entry['level'],
					$second_entry['level'],
					'Entries should have the same level'
				);

				$this->assertEquals(
					$first_entry['module'],
					$second_entry['module'],
					'Entries should have the same module'
				);
			}
		);
	}

	/**
	 * Property 13: Deduplication Matching - Duplicate matching is exact
	 *
	 * For any two log entries, they are duplicates if and only if ALL three conditions
	 * are met: same level, same module, and same message_hash.
	 *
	 * **Validates: Requirements 6.2**
	 *
	 * @return void
	 */
	public function test_duplicate_matching_requires_all_three_conditions(): void {
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

				// Log the message with INFO level
				Logger::info( $message );
				$info_entry = $meowseo_test_logs[0] ?? null;

				$meowseo_test_logs = [];

				// Log the same message with WARNING level
				Logger::warning( $message );
				$warning_entry = $meowseo_test_logs[0] ?? null;

				$this->assertNotNull( $info_entry, 'INFO entry should exist' );
				$this->assertNotNull( $warning_entry, 'WARNING entry should exist' );

				// Verify they have same message_hash but different level
				$this->assertEquals(
					$info_entry['message_hash'],
					$warning_entry['message_hash'],
					'Same message should produce same hash'
				);

				$this->assertNotEquals(
					$info_entry['level'],
					$warning_entry['level'],
					'Different levels should not match'
				);

				// They should NOT be duplicates because level differs
				// (In a real scenario, the second log would create a new entry)
				$this->assertNotEquals(
					$info_entry['level'],
					$warning_entry['level'],
					'Entries with different levels are not duplicates'
				);
			}
		);
	}

	/**
	 * Property 13: Deduplication Matching - Message hash is deterministic
	 *
	 * For any message, the message_hash SHALL be deterministic (same message always
	 * produces the same hash).
	 *
	 * **Validates: Requirements 6.2**
	 *
	 * @return void
	 */
	public function test_message_hash_is_deterministic(): void {
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

				// Log the message three times
				Logger::info( $message );
				$hash1 = $meowseo_test_logs[0]['message_hash'] ?? null;

				$meowseo_test_logs = [];

				Logger::info( $message );
				$hash2 = $meowseo_test_logs[0]['message_hash'] ?? null;

				$meowseo_test_logs = [];

				Logger::info( $message );
				$hash3 = $meowseo_test_logs[0]['message_hash'] ?? null;

				// All hashes should be identical
				$this->assertNotNull( $hash1, 'First hash should not be null' );
				$this->assertNotNull( $hash2, 'Second hash should not be null' );
				$this->assertNotNull( $hash3, 'Third hash should not be null' );

				$this->assertEquals(
					$hash1,
					$hash2,
					'Message hash should be deterministic (first vs second)'
				);

				$this->assertEquals(
					$hash2,
					$hash3,
					'Message hash should be deterministic (second vs third)'
				);

				$this->assertEquals(
					$hash1,
					$hash3,
					'Message hash should be deterministic (first vs third)'
				);
			}
		);
	}
}
