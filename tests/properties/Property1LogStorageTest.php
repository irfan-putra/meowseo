<?php
/**
 * Property-Based Tests for Logger Singleton - Log Storage
 *
 * Property 1: Log Storage
 * Validates: Requirements 1.3, 2.1
 *
 * This test uses property-based testing (eris/eris) to verify that for any log method call
 * (debug, info, warning, error, critical) with a message string, the Logger SHALL create
 * an entry in the meowseo_logs table with the provided message.
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
 * Logger Singleton - Log Storage property-based test case
 *
 * @since 1.0.0
 */
class Property1LogStorageTest extends TestCase {
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
	 * Property 1: Log Storage - Debug method
	 *
	 * For any debug() call with a message string, the Logger SHALL create an entry
	 * in the meowseo_logs table with the provided message.
	 *
	 * **Validates: Requirements 1.3, 2.1**
	 *
	 * @return void
	 */
	public function test_debug_method_stores_log_entry(): void {
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

				// Call debug method
				Logger::debug( $message );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger::debug() should create a log entry'
				);

				// Verify the log entry contains the message
				$log_entry = $meowseo_test_logs[0];

				$this->assertIsArray(
					$log_entry,
					'Log entry should be an array'
				);

				$this->assertArrayHasKey(
					'message',
					$log_entry,
					'Log entry should have a message field'
				);

				$this->assertEquals(
					$message,
					$log_entry['message'],
					'Log entry message should match the provided message'
				);

				$this->assertArrayHasKey(
					'level',
					$log_entry,
					'Log entry should have a level field'
				);

				$this->assertEquals(
					'DEBUG',
					$log_entry['level'],
					'Log entry level should be DEBUG'
				);
			}
		);
	}

	/**
	 * Property 1: Log Storage - Info method
	 *
	 * For any info() call with a message string, the Logger SHALL create an entry
	 * in the meowseo_logs table with the provided message.
	 *
	 * **Validates: Requirements 1.3, 2.1**
	 *
	 * @return void
	 */
	public function test_info_method_stores_log_entry(): void {
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

				// Call info method
				Logger::info( $message );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger::info() should create a log entry'
				);

				// Verify the log entry contains the message
				$log_entry = $meowseo_test_logs[0];

				$this->assertEquals(
					$message,
					$log_entry['message'],
					'Log entry message should match the provided message'
				);

				$this->assertEquals(
					'INFO',
					$log_entry['level'],
					'Log entry level should be INFO'
				);
			}
		);
	}

	/**
	 * Property 1: Log Storage - Warning method
	 *
	 * For any warning() call with a message string, the Logger SHALL create an entry
	 * in the meowseo_logs table with the provided message.
	 *
	 * **Validates: Requirements 1.3, 2.1**
	 *
	 * @return void
	 */
	public function test_warning_method_stores_log_entry(): void {
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

				// Call warning method
				Logger::warning( $message );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger::warning() should create a log entry'
				);

				// Verify the log entry contains the message
				$log_entry = $meowseo_test_logs[0];

				$this->assertEquals(
					$message,
					$log_entry['message'],
					'Log entry message should match the provided message'
				);

				$this->assertEquals(
					'WARNING',
					$log_entry['level'],
					'Log entry level should be WARNING'
				);
			}
		);
	}

	/**
	 * Property 1: Log Storage - Error method
	 *
	 * For any error() call with a message string, the Logger SHALL create an entry
	 * in the meowseo_logs table with the provided message.
	 *
	 * **Validates: Requirements 1.3, 2.1**
	 *
	 * @return void
	 */
	public function test_error_method_stores_log_entry(): void {
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

				// Call error method
				Logger::error( $message );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger::error() should create a log entry'
				);

				// Verify the log entry contains the message
				$log_entry = $meowseo_test_logs[0];

				$this->assertEquals(
					$message,
					$log_entry['message'],
					'Log entry message should match the provided message'
				);

				$this->assertEquals(
					'ERROR',
					$log_entry['level'],
					'Log entry level should be ERROR'
				);
			}
		);
	}

	/**
	 * Property 1: Log Storage - Critical method
	 *
	 * For any critical() call with a message string, the Logger SHALL create an entry
	 * in the meowseo_logs table with the provided message.
	 *
	 * **Validates: Requirements 1.3, 2.1**
	 *
	 * @return void
	 */
	public function test_critical_method_stores_log_entry(): void {
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

				// Call critical method
				Logger::critical( $message );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger::critical() should create a log entry'
				);

				// Verify the log entry contains the message
				$log_entry = $meowseo_test_logs[0];

				$this->assertEquals(
					$message,
					$log_entry['message'],
					'Log entry message should match the provided message'
				);

				$this->assertEquals(
					'CRITICAL',
					$log_entry['level'],
					'Log entry level should be CRITICAL'
				);
			}
		);
	}

	/**
	 * Property 1: Log Storage - All methods store required fields
	 *
	 * For any log method call, the Logger SHALL create an entry with all required fields:
	 * level, module, message, message_hash, context, created_at
	 *
	 * **Validates: Requirements 1.3, 2.1**
	 *
	 * @return void
	 */
	public function test_all_log_methods_store_required_fields(): void {
		$log_methods = [ 'debug', 'info', 'warning', 'error', 'critical' ];

		$this->forAll(
			Generators::elements( $log_methods ),
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $method, string $message ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Call the log method
				Logger::$method( $message );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					"Logger::$method() should create a log entry"
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify required fields exist
				$required_fields = [ 'level', 'module', 'message', 'message_hash', 'context', 'created_at' ];

				foreach ( $required_fields as $field ) {
					$this->assertArrayHasKey(
						$field,
						$log_entry,
						"Log entry should have '$field' field"
					);
				}

				// Verify field values
				$this->assertNotEmpty(
					$log_entry['level'],
					'Log level should not be empty'
				);

				$this->assertNotEmpty(
					$log_entry['module'],
					'Log module should not be empty'
				);

				$this->assertEquals(
					$message,
					$log_entry['message'],
					'Log message should match'
				);

				$this->assertNotEmpty(
					$log_entry['message_hash'],
					'Log message_hash should not be empty'
				);

				$this->assertNotEmpty(
					$log_entry['created_at'],
					'Log created_at should not be empty'
				);
			}
		);
	}

	/**
	 * Property 1: Log Storage - Message hash is consistent
	 *
	 * For any message, the message_hash should be consistent (same message produces same hash)
	 *
	 * **Validates: Requirements 1.3, 2.1**
	 *
	 * @return void
	 */
	public function test_message_hash_is_consistent(): void {
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

				// Log the same message twice
				Logger::info( $message );
				$first_hash = $meowseo_test_logs[0]['message_hash'] ?? null;

				$meowseo_test_logs = [];

				Logger::info( $message );
				$second_hash = $meowseo_test_logs[0]['message_hash'] ?? null;

				// Verify hashes are the same
				$this->assertNotNull( $first_hash, 'First hash should not be null' );
				$this->assertNotNull( $second_hash, 'Second hash should not be null' );

				$this->assertEquals(
					$first_hash,
					$second_hash,
					'Message hash should be consistent for the same message'
				);
			}
		);
	}

	/**
	 * Property 1: Log Storage - Different messages produce different hashes
	 *
	 * For any two different messages, their message_hashes should be different
	 *
	 * **Validates: Requirements 1.3, 2.1**
	 *
	 * @return void
	 */
	public function test_different_messages_produce_different_hashes(): void {
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

				// Log first message
				Logger::info( $message1 );
				$hash1 = $meowseo_test_logs[0]['message_hash'] ?? null;

				$meowseo_test_logs = [];

				// Log second message
				Logger::info( $message2 );
				$hash2 = $meowseo_test_logs[0]['message_hash'] ?? null;

				// Verify hashes are different
				$this->assertNotNull( $hash1, 'First hash should not be null' );
				$this->assertNotNull( $hash2, 'Second hash should not be null' );

				$this->assertNotEquals(
					$hash1,
					$hash2,
					'Different messages should produce different hashes'
				);
			}
		);
	}
}
