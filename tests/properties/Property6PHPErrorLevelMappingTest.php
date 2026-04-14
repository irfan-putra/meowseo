<?php
/**
 * Property-Based Tests for Logger PHP Error Level Mapping
 *
 * Property 6: PHP Error Level Mapping
 * Validates: Requirements 3.5
 *
 * This test uses property-based testing to verify that for any PHP error level
 * (E_ERROR, E_WARNING, E_NOTICE, etc.), the Logger SHALL map it to the appropriate
 * log level (CRITICAL, WARNING, INFO, DEBUG) according to the defined mapping.
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
 * Logger PHP Error Level Mapping property-based test case
 *
 * @since 1.0.0
 */
class Property6PHPErrorLevelMappingTest extends TestCase {
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
	 * Property 6: PHP Error Level Mapping - E_WARNING maps to WARNING
	 *
	 * For any E_WARNING error, the Logger SHALL map it to WARNING level.
	 *
	 * **Validates: Requirements 3.5**
	 *
	 * @return void
	 */
	public function test_e_warning_maps_to_warning(): void {
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
					'Logger should capture E_WARNING'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify level is WARNING
				$this->assertEquals(
					'WARNING',
					$log_entry['level'],
					'E_WARNING should map to WARNING level'
				);
			}
		);
	}

	/**
	 * Property 6: PHP Error Level Mapping - E_NOTICE maps to INFO
	 *
	 * For any E_NOTICE error, the Logger SHALL map it to INFO level.
	 *
	 * **Validates: Requirements 3.5**
	 *
	 * @return void
	 */
	public function test_e_notice_maps_to_info(): void {
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
					'Logger should capture E_NOTICE'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify level is INFO
				$this->assertEquals(
					'INFO',
					$log_entry['level'],
					'E_NOTICE should map to INFO level'
				);
			}
		);
	}

	/**
	 * Property 6: PHP Error Level Mapping - E_DEPRECATED maps to DEBUG
	 *
	 * For any E_DEPRECATED error, the Logger SHALL map it to DEBUG level.
	 *
	 * **Validates: Requirements 3.5**
	 *
	 * @return void
	 */
	public function test_e_deprecated_maps_to_debug(): void {
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
					'Logger should capture E_DEPRECATED'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify level is DEBUG
				$this->assertEquals(
					'DEBUG',
					$log_entry['level'],
					'E_DEPRECATED should map to DEBUG level'
				);
			}
		);
	}

	/**
	 * Property 6: PHP Error Level Mapping - E_USER_NOTICE maps to INFO
	 *
	 * For any E_USER_NOTICE error, the Logger SHALL map it to INFO level.
	 *
	 * **Validates: Requirements 3.5**
	 *
	 * @return void
	 */
	public function test_e_user_notice_maps_to_info(): void {
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

				// Simulate an E_USER_NOTICE error
				$logger->error_handler( E_USER_NOTICE, $error_message, __FILE__, __LINE__ );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should capture E_USER_NOTICE'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify level is INFO
				$this->assertEquals(
					'INFO',
					$log_entry['level'],
					'E_USER_NOTICE should map to INFO level'
				);
			}
		);
	}

	/**
	 * Property 6: PHP Error Level Mapping - E_USER_DEPRECATED maps to DEBUG
	 *
	 * For any E_USER_DEPRECATED error, the Logger SHALL map it to DEBUG level.
	 *
	 * **Validates: Requirements 3.5**
	 *
	 * @return void
	 */
	public function test_e_user_deprecated_maps_to_debug(): void {
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

				// Simulate an E_USER_DEPRECATED error
				$logger->error_handler( E_USER_DEPRECATED, $error_message, __FILE__, __LINE__ );

				// Verify log entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Logger should capture E_USER_DEPRECATED'
				);

				$log_entry = $meowseo_test_logs[0];

				// Verify level is DEBUG
				$this->assertEquals(
					'DEBUG',
					$log_entry['level'],
					'E_USER_DEPRECATED should map to DEBUG level'
				);
			}
		);
	}

	/**
	 * Property 6: PHP Error Level Mapping - All error types map to valid levels
	 *
	 * For any PHP error type, the Logger SHALL map it to one of the valid log levels.
	 *
	 * **Validates: Requirements 3.5**
	 *
	 * @return void
	 */
	public function test_all_error_types_map_to_valid_levels(): void {
		$error_types = [ E_WARNING, E_NOTICE, E_DEPRECATED, E_USER_NOTICE, E_USER_DEPRECATED ];
		$valid_levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];

		$this->forAll(
			Generators::elements( $error_types ),
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( int $error_type, string $error_message ) use ( $valid_levels ) {
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

				// Verify level is one of the valid levels
				$this->assertContains(
					$log_entry['level'],
					$valid_levels,
					'Log level should be one of the valid levels'
				);
			}
		);
	}
}
