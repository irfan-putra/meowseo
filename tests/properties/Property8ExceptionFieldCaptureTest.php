<?php
/**
 * Property-Based Tests for Logger Exception Field Capture
 *
 * Property 8: Exception Field Capture
 * Validates: Requirements 4.3
 *
 * This test uses property-based testing to verify that for any exception logged,
 * the log entry SHALL include exception message, class name, file path, line number,
 * and full stack trace.
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
 * Logger Exception Field Capture property-based test case
 *
 * @since 1.0.0
 */
class Property8ExceptionFieldCaptureTest extends TestCase {
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
	 * Property 8: Exception Field Capture - Exception message is captured
	 *
	 * For any exception logged, the log entry SHALL include the exception message.
	 *
	 * **Validates: Requirements 4.3**
	 *
	 * @return void
	 */
	public function test_exception_message_is_captured(): void {
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
				$context = json_decode( $log_entry['context'] ?? '{}', true );

				// Verify exception message is in context
				$this->assertArrayHasKey(
					'exception_message',
					$context,
					'Context should contain exception_message field'
				);

				$this->assertEquals(
					$exception_message,
					$context['exception_message'],
					'Exception message should be captured exactly'
				);
			}
		);
	}

	/**
	 * Property 8: Exception Field Capture - Exception class name is captured
	 *
	 * For any exception logged, the log entry SHALL include the exception class name.
	 *
	 * **Validates: Requirements 4.3**
	 *
	 * @return void
	 */
	public function test_exception_class_name_is_captured(): void {
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
						'Exception caught',
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
				$context = json_decode( $log_entry['context'] ?? '{}', true );

				// Verify exception class is in context
				$this->assertArrayHasKey(
					'exception_class',
					$context,
					'Context should contain exception_class field'
				);

				$this->assertStringContainsString(
					$exception_class,
					$context['exception_class'],
					'Exception class name should be captured'
				);
			}
		);
	}

	/**
	 * Property 8: Exception Field Capture - Exception file path is captured
	 *
	 * For any exception logged, the log entry SHALL include the file path where exception occurred.
	 *
	 * **Validates: Requirements 4.3**
	 *
	 * @return void
	 */
	public function test_exception_file_path_is_captured(): void {
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
					$file_path = $e->getFile();

					Logger::error(
						'Exception caught',
						[
							'exception_class' => get_class( $e ),
							'exception_message' => $e->getMessage(),
							'file' => $file_path,
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
				$context = json_decode( $log_entry['context'] ?? '{}', true );

				// Verify file path is in context
				$this->assertArrayHasKey(
					'file',
					$context,
					'Context should contain file field'
				);

				$this->assertNotEmpty(
					$context['file'],
					'File path should not be empty'
				);

				$this->assertStringContainsString(
					'Property8ExceptionFieldCaptureTest.php',
					$context['file'],
					'File path should contain test file name'
				);
			}
		);
	}

	/**
	 * Property 8: Exception Field Capture - Exception line number is captured
	 *
	 * For any exception logged, the log entry SHALL include the line number where exception occurred.
	 *
	 * **Validates: Requirements 4.3**
	 *
	 * @return void
	 */
	public function test_exception_line_number_is_captured(): void {
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
					$line_number = $e->getLine();

					Logger::error(
						'Exception caught',
						[
							'exception_class' => get_class( $e ),
							'exception_message' => $e->getMessage(),
							'file' => $e->getFile(),
							'line' => $line_number,
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
				$context = json_decode( $log_entry['context'] ?? '{}', true );

				// Verify line number is in context
				$this->assertArrayHasKey(
					'line',
					$context,
					'Context should contain line field'
				);

				$this->assertIsInt(
					$context['line'],
					'Line number should be an integer'
				);

				$this->assertGreaterThan(
					0,
					$context['line'],
					'Line number should be greater than 0'
				);
			}
		);
	}

	/**
	 * Property 8: Exception Field Capture - Exception stack trace is captured
	 *
	 * For any exception logged, the log entry SHALL include the full stack trace.
	 *
	 * **Validates: Requirements 4.3**
	 *
	 * @return void
	 */
	public function test_exception_stack_trace_is_captured(): void {
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
					$stack_trace = $e->getTraceAsString();

					Logger::error(
						'Exception caught',
						[
							'exception_class' => get_class( $e ),
							'exception_message' => $e->getMessage(),
							'file' => $e->getFile(),
							'line' => $e->getLine(),
							'stack_trace' => $stack_trace,
						]
					);
				}

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should create entry for exception'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify stack trace is in the log entry (separate column, not in context)
				$this->assertArrayHasKey(
					'stack_trace',
					$log_entry,
					'Log entry should contain stack_trace field'
				);

				$this->assertNotEmpty(
					$log_entry['stack_trace'],
					'Stack trace should not be empty'
				);

				$this->assertStringContainsString(
					'#0',
					$log_entry['stack_trace'],
					'Stack trace should contain frame information'
				);
			}
		);
	}

	/**
	 * Property 8: Exception Field Capture - All fields are captured together
	 *
	 * For any exception logged, the log entry SHALL include all required fields:
	 * message, class name, file path, line number, and stack trace.
	 *
	 * **Validates: Requirements 4.3**
	 *
	 * @return void
	 */
	public function test_all_exception_fields_are_captured_together(): void {
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
				$context = json_decode( $log_entry['context'] ?? '{}', true );

				// Verify all required fields are present (stack_trace is in log entry, not context)
				$required_context_fields = [
					'exception_message',
					'exception_class',
					'file',
					'line',
				];

				foreach ( $required_context_fields as $field ) {
					$this->assertArrayHasKey(
						$field,
						$context,
						"Context should contain $field field"
					);

					$this->assertNotEmpty(
						$context[ $field ],
						"$field should not be empty"
					);
				}

				// Verify stack_trace is in log entry (separate column)
				$this->assertArrayHasKey(
					'stack_trace',
					$log_entry,
					'Log entry should contain stack_trace field'
				);

				$this->assertNotEmpty(
					$log_entry['stack_trace'],
					'stack_trace should not be empty'
				);

				// Verify field types
				$this->assertIsString(
					$context['exception_message'],
					'exception_message should be string'
				);

				$this->assertIsString(
					$context['exception_class'],
					'exception_class should be string'
				);

				$this->assertIsString(
					$context['file'],
					'file should be string'
				);

				$this->assertIsInt(
					$context['line'],
					'line should be integer'
				);

				$this->assertIsString(
					$log_entry['stack_trace'],
					'stack_trace should be string (in log entry, not context)'
				);
			}
		);
	}
}
