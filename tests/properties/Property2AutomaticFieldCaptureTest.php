<?php
/**
 * Property-Based Tests for Logger Singleton - Automatic Field Capture
 *
 * Property 2: Automatic Field Capture
 * Validates: Requirements 1.5
 *
 * This test uses property-based testing (eris/eris) to verify that for any log entry created,
 * the Logger SHALL automatically populate the timestamp, module name, and log level fields
 * without requiring explicit parameters.
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
 * Logger Singleton - Automatic Field Capture property-based test case
 *
 * @since 1.0.0
 */
class Property2AutomaticFieldCaptureTest extends TestCase {
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
	 * Property 2: Automatic Field Capture - Timestamp is captured
	 *
	 * For any log entry created, the Logger SHALL automatically populate the timestamp field
	 * in MySQL format (YYYY-MM-DD HH:MM:SS) without requiring explicit parameters.
	 *
	 * **Validates: Requirements 1.5**
	 *
	 * @return void
	 */
	public function test_timestamp_is_automatically_captured(): void {
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

				// Record time before logging
				$time_before = time();

				// Call log method
				Logger::info( $message );

				// Record time after logging
				$time_after = time();

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should create a log entry'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify timestamp field exists
				$this->assertArrayHasKey(
					'created_at',
					$log_entry,
					'Log entry should have created_at field'
				);

				$timestamp = $log_entry['created_at'];

				// Verify timestamp is not empty
				$this->assertNotEmpty(
					$timestamp,
					'Timestamp should not be empty'
				);

				// Verify timestamp format is MySQL format (YYYY-MM-DD HH:MM:SS)
				$this->assertMatchesRegularExpression(
					'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
					$timestamp,
					'Timestamp should be in MySQL format (YYYY-MM-DD HH:MM:SS)'
				);

				// Verify timestamp is within reasonable range (within 2 seconds of current time)
				$timestamp_unix = strtotime( $timestamp );
				$this->assertGreaterThanOrEqual(
					$time_before - 1,
					$timestamp_unix,
					'Timestamp should be close to current time'
				);
				$this->assertLessThanOrEqual(
					$time_after + 1,
					$timestamp_unix,
					'Timestamp should be close to current time'
				);
			}
		);
	}

	/**
	 * Property 2: Automatic Field Capture - Module name is captured
	 *
	 * For any log entry created, the Logger SHALL automatically populate the module name field
	 * by detecting the calling module from debug_backtrace() without requiring explicit parameters.
	 *
	 * **Validates: Requirements 1.5**
	 *
	 * @return void
	 */
	public function test_module_name_is_automatically_captured(): void {
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

				// Call log method
				Logger::info( $message );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should create a log entry'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify module field exists
				$this->assertArrayHasKey(
					'module',
					$log_entry,
					'Log entry should have module field'
				);

				$module = $log_entry['module'];

				// Verify module is not empty
				$this->assertNotEmpty(
					$module,
					'Module should not be empty'
				);

				// Verify module is a string
				$this->assertIsString(
					$module,
					'Module should be a string'
				);

				// Verify module is either 'core' or a valid module name
				$this->assertMatchesRegularExpression(
					'/^[a-z_]+$/',
					$module,
					'Module name should contain only lowercase letters and underscores'
				);
			}
		);
	}

	/**
	 * Property 2: Automatic Field Capture - Log level is captured
	 *
	 * For any log entry created, the Logger SHALL automatically populate the log level field
	 * matching the method called (debug, info, warning, error, critical) without requiring explicit parameters.
	 *
	 * **Validates: Requirements 1.5**
	 *
	 * @return void
	 */
	public function test_log_level_is_automatically_captured(): void {
		$log_methods = [
			'debug'    => 'DEBUG',
			'info'     => 'INFO',
			'warning'  => 'WARNING',
			'error'    => 'ERROR',
			'critical' => 'CRITICAL',
		];

		$this->forAll(
			Generators::elements( array_keys( $log_methods ) ),
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $method, string $message ) use ( $log_methods ) {
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

				// Verify level field exists
				$this->assertArrayHasKey(
					'level',
					$log_entry,
					'Log entry should have level field'
				);

				$level = $log_entry['level'];

				// Verify level matches the method called
				$expected_level = $log_methods[ $method ];
				$this->assertEquals(
					$expected_level,
					$level,
					"Log level should be $expected_level for Logger::$method()"
				);
			}
		);
	}

	/**
	 * Property 2: Automatic Field Capture - All automatic fields are populated
	 *
	 * For any log entry created, the Logger SHALL automatically populate timestamp, module name,
	 * and log level fields without requiring explicit parameters.
	 *
	 * **Validates: Requirements 1.5**
	 *
	 * @return void
	 */
	public function test_all_automatic_fields_are_populated(): void {
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

				// Verify all automatic fields are present
				$automatic_fields = [ 'created_at', 'module', 'level' ];

				foreach ( $automatic_fields as $field ) {
					$this->assertArrayHasKey(
						$field,
						$log_entry,
						"Log entry should have $field field"
					);

					$this->assertNotEmpty(
						$log_entry[ $field ],
						"$field should not be empty"
					);
				}

				// Verify timestamp format
				$this->assertMatchesRegularExpression(
					'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
					$log_entry['created_at'],
					'Timestamp should be in MySQL format'
				);

				// Verify module is a valid identifier
				$this->assertMatchesRegularExpression(
					'/^[a-z_]+$/',
					$log_entry['module'],
					'Module should be a valid identifier'
				);

				// Verify level is one of the valid levels
				$this->assertContains(
					$log_entry['level'],
					[ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ],
					'Level should be one of the valid log levels'
				);
			}
		);
	}

	/**
	 * Property 2: Automatic Field Capture - Automatic fields are not affected by context
	 *
	 * For any log entry created with or without context data, the Logger SHALL automatically
	 * populate timestamp, module name, and log level fields consistently.
	 *
	 * **Validates: Requirements 1.5**
	 *
	 * @return void
	 */
	public function test_automatic_fields_are_consistent_with_and_without_context(): void {
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

				// Log without context
				Logger::info( $message );
				$log_without_context = $meowseo_test_logs[0];

				$meowseo_test_logs = [];

				// Log with context
				$context = [
					'user_id'  => 123,
					'action'   => 'test_action',
					'metadata' => [ 'key' => 'value' ],
				];
				Logger::info( $message, $context );
				$log_with_context = $meowseo_test_logs[0];

				// Verify automatic fields are the same
				$this->assertEquals(
					$log_without_context['level'],
					$log_with_context['level'],
					'Log level should be the same with or without context'
				);

				$this->assertEquals(
					$log_without_context['module'],
					$log_with_context['module'],
					'Module should be the same with or without context'
				);

				// Verify timestamp format is the same
				$this->assertMatchesRegularExpression(
					'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
					$log_with_context['created_at'],
					'Timestamp should be in MySQL format with context'
				);
			}
		);
	}

	/**
	 * Property 2: Automatic Field Capture - Automatic fields are populated for all log levels
	 *
	 * For any log method (debug, info, warning, error, critical), the Logger SHALL automatically
	 * populate timestamp, module name, and log level fields.
	 *
	 * **Validates: Requirements 1.5**
	 *
	 * @return void
	 */
	public function test_automatic_fields_populated_for_all_log_levels(): void {
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

				// Verify all automatic fields are populated
				$this->assertNotEmpty(
					$log_entry['created_at'],
					"created_at should be populated for $method()"
				);

				$this->assertNotEmpty(
					$log_entry['module'],
					"module should be populated for $method()"
				);

				$this->assertNotEmpty(
					$log_entry['level'],
					"level should be populated for $method()"
				);

				// Verify timestamp is in correct format
				$this->assertMatchesRegularExpression(
					'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
					$log_entry['created_at'],
					"created_at should be in MySQL format for $method()"
				);
			}
		);
	}
}
