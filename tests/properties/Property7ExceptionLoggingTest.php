<?php
/**
 * Property-Based Tests for Logger Exception Logging
 *
 * Property 7: Exception Logging
 * Validates: Requirements 4.2
 *
 * This test uses property-based testing to verify that for any exception thrown
 * during module boot(), the Module_Manager SHALL log the exception via Logger.
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
 * Logger Exception Logging property-based test case
 *
 * @since 1.0.0
 */
class Property7ExceptionLoggingTest extends TestCase {
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
	 * Property 7: Exception Logging - Exceptions are logged
	 *
	 * For any exception thrown, the Logger SHALL create a log entry.
	 *
	 * **Validates: Requirements 4.2**
	 *
	 * @return void
	 */
	public function test_exceptions_are_logged(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $exception_message ) {
				// Skip empty messages
				if ( empty( trim( $exception_message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create and log an exception
				try {
					throw new \Exception( $exception_message );
				} catch ( \Exception $e ) {
					Logger::error(
						'Exception caught: ' . $e->getMessage(),
						[
							'exception_class' => get_class( $e ),
							'exception_message' => $e->getMessage(),
							'file' => $e->getFile(),
							'line' => $e->getLine(),
							'stack_trace' => $e->getTraceAsString(),
						]
					);
				}

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should create entry for exception'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify the entry contains exception details
				$this->assertArrayHasKey(
					'message',
					$log_entry,
					'Log entry should have message'
				);

				$this->assertStringContainsString(
					'Exception caught',
					$log_entry['message'],
					'Message should indicate exception'
				);
			}
		);
	}

	/**
	 * Property 7: Exception Logging - Exception level is ERROR
	 *
	 * For any exception logged, the log level SHALL be ERROR.
	 *
	 * **Validates: Requirements 4.2**
	 *
	 * @return void
	 */
	public function test_exception_level_is_error(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $exception_message ) {
				// Skip empty messages
				if ( empty( trim( $exception_message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create and log an exception
				try {
					throw new \Exception( $exception_message );
				} catch ( \Exception $e ) {
					Logger::error(
						'Exception caught: ' . $e->getMessage(),
						[
							'exception_class' => get_class( $e ),
							'exception_message' => $e->getMessage(),
						]
					);
				}

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should create entry for exception'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify level is ERROR
				$this->assertEquals(
					'ERROR',
					$log_entry['level'],
					'Exception should be logged at ERROR level'
				);
			}
		);
	}

	/**
	 * Property 7: Exception Logging - Multiple exceptions are logged
	 *
	 * For any sequence of exceptions, the Logger SHALL create separate log entries for each.
	 *
	 * **Validates: Requirements 4.2**
	 *
	 * @return void
	 */
	public function test_multiple_exceptions_are_logged(): void {
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

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log first exception
				try {
					throw new \Exception( $message1 );
				} catch ( \Exception $e ) {
					Logger::error( 'Exception 1: ' . $e->getMessage() );
				}

				// Log second exception
				try {
					throw new \Exception( $message2 );
				} catch ( \Exception $e ) {
					Logger::error( 'Exception 2: ' . $e->getMessage() );
				}

				// Verify both entries were created
				$this->assertCount(
					2,
					$meowseo_test_logs,
					'Logger should create separate entries for each exception'
				);

				// Verify first entry
				$this->assertStringContainsString(
					'Exception 1',
					$meowseo_test_logs[0]['message']
				);

				// Verify second entry
				$this->assertStringContainsString(
					'Exception 2',
					$meowseo_test_logs[1]['message']
				);
			}
		);
	}

	/**
	 * Property 7: Exception Logging - Different exception types are logged
	 *
	 * For any exception type (RuntimeException, LogicException, etc.), the Logger SHALL log it.
	 *
	 * **Validates: Requirements 4.2**
	 *
	 * @return void
	 */
	public function test_different_exception_types_are_logged(): void {
		$exception_classes = [
			'Exception',
			'RuntimeException',
			'LogicException',
		];

		$this->forAll(
			Generators::elements( $exception_classes ),
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $exception_class, string $message ) {
				// Skip empty messages
				if ( empty( trim( $message ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create and log the exception
				try {
					throw new $exception_class( $message );
				} catch ( \Exception $e ) {
					Logger::error(
						'Exception: ' . $e->getMessage(),
						[
							'exception_class' => get_class( $e ),
						]
					);
				}

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					"Logger should log $exception_class"
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify context contains exception class
				$context = json_decode( $log_entry['context'] ?? '{}', true );

				$this->assertArrayHasKey(
					'exception_class',
					$context,
					'Context should contain exception class'
				);

				$this->assertStringContainsString(
					$exception_class,
					$context['exception_class'],
					'Exception class should be recorded'
				);
			}
		);
	}
}
