<?php
/**
 * Property-Based Tests for Logger PHP Error Capture
 *
 * Property 4: PHP Error Capture
 * Validates: Requirements 3.2
 *
 * This test uses property-based testing to verify that for any PHP error
 * (E_ERROR, E_WARNING, E_NOTICE, etc.) that occurs within the MeowSEO namespace,
 * the Logger SHALL create a log entry capturing the error.
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
 * Logger PHP Error Capture property-based test case
 *
 * @since 1.0.0
 */
class Property4PHPErrorCaptureTest extends TestCase {
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
	 * Property 4: PHP Error Capture - E_WARNING errors are captured
	 *
	 * For any E_WARNING error that occurs, the Logger SHALL create a log entry.
	 *
	 * **Validates: Requirements 3.2**
	 *
	 * @return void
	 */
	public function test_e_warning_errors_are_captured(): void {
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

				// Simulate an E_WARNING error
				$logger->error_handler( E_WARNING, $error_message, __FILE__, __LINE__ );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should capture E_WARNING errors'
				);

				// Verify the log entry contains error details
				$log_entry = $meowseo_test_logs[0];

				$this->assertArrayHasKey(
					'message',
					$log_entry,
					'Log entry should have a message field'
				);

				$this->assertStringContainsString(
					$error_message,
					$log_entry['message'],
					'Log entry message should contain the error message'
				);
			}
		);
	}

	/**
	 * Property 4: PHP Error Capture - E_NOTICE errors are captured
	 *
	 * For any E_NOTICE error that occurs, the Logger SHALL create a log entry.
	 *
	 * **Validates: Requirements 3.2**
	 *
	 * @return void
	 */
	public function test_e_notice_errors_are_captured(): void {
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

				// Simulate an E_NOTICE error
				$logger->error_handler( E_NOTICE, $error_message, __FILE__, __LINE__ );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should capture E_NOTICE errors'
				);

				// Verify the log entry contains error details
				$log_entry = $meowseo_test_logs[0];

				$this->assertArrayHasKey(
					'message',
					$log_entry,
					'Log entry should have a message field'
				);

				$this->assertStringContainsString(
					$error_message,
					$log_entry['message'],
					'Log entry message should contain the error message'
				);
			}
		);
	}

	/**
	 * Property 4: PHP Error Capture - E_DEPRECATED errors are captured
	 *
	 * For any E_DEPRECATED error that occurs, the Logger SHALL create a log entry.
	 *
	 * **Validates: Requirements 3.2**
	 *
	 * @return void
	 */
	public function test_e_deprecated_errors_are_captured(): void {
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

				// Simulate an E_DEPRECATED error
				$logger->error_handler( E_DEPRECATED, $error_message, __FILE__, __LINE__ );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should capture E_DEPRECATED errors'
				);

				// Verify the log entry contains error details
				$log_entry = $meowseo_test_logs[0];

				$this->assertArrayHasKey(
					'message',
					$log_entry,
					'Log entry should have a message field'
				);

				$this->assertStringContainsString(
					$error_message,
					$log_entry['message'],
					'Log entry message should contain the error message'
				);
			}
		);
	}

	/**
	 * Property 4: PHP Error Capture - Multiple error types are captured
	 *
	 * For any combination of different error types, the Logger SHALL capture all of them.
	 *
	 * **Validates: Requirements 3.2**
	 *
	 * @return void
	 */
	public function test_multiple_error_types_are_captured(): void {
		$error_types = [ E_WARNING, E_NOTICE, E_DEPRECATED ];

		$this->forAll(
			Generators::elements( $error_types ),
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
					"Logger should capture error type $error_type"
				);

				$log_entry = $meowseo_test_logs[0];

				$this->assertArrayHasKey(
					'message',
					$log_entry,
					'Log entry should have a message field'
				);
			}
		);
	}
}
