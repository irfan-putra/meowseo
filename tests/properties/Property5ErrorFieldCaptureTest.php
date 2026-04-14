<?php
/**
 * Property-Based Tests for Logger Error Field Capture
 *
 * Property 5: Error Field Capture
 * Validates: Requirements 3.3
 *
 * This test uses property-based testing to verify that for any PHP error captured
 * by the Logger, the log entry SHALL include error level, message, file path, and line number.
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
 * Logger Error Field Capture property-based test case
 *
 * @since 1.0.0
 */
class Property5ErrorFieldCaptureTest extends TestCase {
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
	 * Property 5: Error Field Capture - All required fields are present
	 *
	 * For any PHP error captured by the Logger, the log entry SHALL include
	 * error level, message, file path, and line number.
	 *
	 * **Validates: Requirements 3.3**
	 *
	 * @return void
	 */
	public function test_error_fields_are_captured(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 ),
			Generators::choose( 1, 1000 )
		)
		->then(
			function ( string $error_message, int $line_number ) {
				// Skip empty messages
				if ( empty( trim( $error_message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Get the Logger instance to access error handler
				$logger = Logger::get_instance();

				// Simulate an error with specific line number
				$logger->error_handler( E_WARNING, $error_message, __FILE__, $line_number );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should capture error'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify required fields exist
				$this->assertArrayHasKey(
					'level',
					$log_entry,
					'Log entry should have level field'
				);

				$this->assertArrayHasKey(
					'message',
					$log_entry,
					'Log entry should have message field'
				);

				// Verify message contains error details
				$this->assertStringContainsString(
					$error_message,
					$log_entry['message'],
					'Message should contain error message'
				);

				// Verify context contains file and line information
				$context = json_decode( $log_entry['context'] ?? '{}', true );

				$this->assertArrayHasKey(
					'file',
					$context,
					'Context should have file field'
				);

				$this->assertArrayHasKey(
					'line',
					$context,
					'Context should have line field'
				);

				$this->assertNotEmpty(
					$context['file'],
					'File path should be included'
				);

				$this->assertIsInt(
					$context['line'],
					'Line number should be an integer'
				);
			}
		);
	}

	/**
	 * Property 5: Error Field Capture - Error level is correctly set
	 *
	 * For any PHP error, the log entry level SHALL correspond to the error type.
	 *
	 * **Validates: Requirements 3.3**
	 *
	 * @return void
	 */
	public function test_error_level_is_correctly_set(): void {
		$error_levels = [
			E_WARNING => 'WARNING',
			E_NOTICE => 'INFO',
			E_DEPRECATED => 'DEBUG',
		];

		$this->forAll(
			Generators::elements( array_keys( $error_levels ) ),
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( int $error_type, string $error_message ) {
				// Skip empty messages
				if ( empty( trim( $error_message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Get the Logger instance to access error handler
				$logger = Logger::get_instance();

				// Simulate the error
				$logger->error_handler( $error_type, $error_message, __FILE__, __LINE__ );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should capture error'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify level is set
				$this->assertArrayHasKey(
					'level',
					$log_entry,
					'Log entry should have level field'
				);

				$this->assertNotEmpty(
					$log_entry['level'],
					'Log level should not be empty'
				);
			}
		);
	}

	/**
	 * Property 5: Error Field Capture - File path is included
	 *
	 * For any PHP error, the log entry context SHALL include the file path.
	 *
	 * **Validates: Requirements 3.3**
	 *
	 * @return void
	 */
	public function test_file_path_is_included(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $error_message ) {
				// Skip empty messages
				if ( empty( trim( $error_message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Get the Logger instance to access error handler
				$logger = Logger::get_instance();

				// Simulate an error
				$logger->error_handler( E_WARNING, $error_message, __FILE__, __LINE__ );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should capture error'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify context contains file
				$context = json_decode( $log_entry['context'] ?? '{}', true );

				$this->assertArrayHasKey(
					'file',
					$context,
					'Context should include file path'
				);

				$this->assertNotEmpty(
					$context['file'],
					'File path should not be empty'
				);

				$this->assertIsString(
					$context['file'],
					'File path should be a string'
				);
			}
		);
	}

	/**
	 * Property 5: Error Field Capture - Line number is included
	 *
	 * For any PHP error, the log entry context SHALL include the line number.
	 *
	 * **Validates: Requirements 3.3**
	 *
	 * @return void
	 */
	public function test_line_number_is_included(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 ),
			Generators::choose( 1, 10000 )
		)
		->then(
			function ( string $error_message, int $line_number ) {
				// Skip empty messages
				if ( empty( trim( $error_message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Get the Logger instance to access error handler
				$logger = Logger::get_instance();

				// Simulate an error with specific line number
				$logger->error_handler( E_WARNING, $error_message, __FILE__, $line_number );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should capture error'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify context contains line number
				$context = json_decode( $log_entry['context'] ?? '{}', true );

				$this->assertArrayHasKey(
					'line',
					$context,
					'Context should include line number'
				);

				$this->assertIsInt(
					$context['line'],
					'Line number should be an integer'
				);

				$this->assertGreaterThan(
					0,
					$context['line'],
					'Line number should be positive'
				);
			}
		);
	}
}
