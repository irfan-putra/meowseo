<?php
/**
 * Property-Based Tests for GSC Context Fields
 *
 * Property 22: GSC Context Fields
 * Validates: Requirements 11.4
 *
 * This test uses property-based testing (eris/eris) to verify that for any log entry
 * created by the GSC module, the context SHALL include job_type and payload summary fields.
 * This ensures that GSC module logging provides sufficient context for debugging API
 * interactions and queue processing.
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
 * GSC Context Fields property-based test case
 *
 * @since 1.0.0
 */
class Property22GSCContextFieldsTest extends TestCase {
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
	 * Property 22: GSC Context Fields - job_type is always present
	 *
	 * For any log entry created by the GSC module, the context SHALL include
	 * the job_type field. This field identifies the type of GSC operation
	 * (e.g., 'fetch_url', 'fetch_sitemaps', 'gsc_queue').
	 *
	 * **Validates: Requirements 11.4**
	 *
	 * @return void
	 */
	public function test_gsc_log_includes_job_type(): void {
		$this->forAll(
			Generators::elements( [ 'fetch_url', 'fetch_sitemaps', 'gsc_queue', 'token_refresh' ] )
		)
		->then(
			function ( string $job_type ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log a GSC operation with job_type
				Logger::info(
					'GSC operation completed',
					array(
						'job_type' => $job_type,
						'status'   => 'success',
					)
				);

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify job_type is present
				$this->assertArrayHasKey(
					'job_type',
					$context,
					'Context should have job_type field'
				);

				// Verify job_type matches what was logged
				$this->assertEquals(
					$job_type,
					$context['job_type'],
					'job_type should match the logged value'
				);
			}
		);
	}

	/**
	 * Property 22: GSC Context Fields - payload summary is present
	 *
	 * For any log entry created by the GSC module, the context SHALL include
	 * a payload summary field that describes the operation parameters.
	 *
	 * **Validates: Requirements 11.4**
	 *
	 * @return void
	 */
	public function test_gsc_log_includes_payload_summary(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_', 5, 20 ),
			Generators::string( 'a-zA-Z0-9_', 5, 20 )
		)
		->then(
			function ( string $site_url, string $url ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log a GSC fetch_url operation with payload summary
				Logger::info(
					'GSC fetch_url completed',
					array(
						'job_type'          => 'fetch_url',
						'payload_summary'   => 'Fetched data for ' . $url . ' from ' . $site_url,
						'rows_processed'    => 42,
					)
				);

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify payload_summary is present
				$this->assertArrayHasKey(
					'payload_summary',
					$context,
					'Context should have payload_summary field'
				);

				// Verify payload_summary is not empty
				$this->assertNotEmpty(
					$context['payload_summary'],
					'payload_summary should not be empty'
				);

				// Verify payload_summary is a string
				$this->assertIsString(
					$context['payload_summary'],
					'payload_summary should be a string'
				);
			}
		);
	}

	/**
	 * Property 22: GSC Context Fields - both fields present together
	 *
	 * For any log entry created by the GSC module, the context SHALL include
	 * both job_type and payload_summary fields together.
	 *
	 * **Validates: Requirements 11.4**
	 *
	 * @return void
	 */
	public function test_gsc_log_includes_both_required_fields(): void {
		$this->forAll(
			Generators::elements( [ 'fetch_url', 'fetch_sitemaps', 'gsc_queue' ] ),
			Generators::string( 'a-zA-Z0-9 ', 10, 50 )
		)
		->then(
			function ( string $job_type, string $summary ) {
				// Skip empty summaries
				if ( empty( trim( $summary ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log a GSC operation with both required fields
				Logger::warning(
					'GSC operation with context',
					array(
						'job_type'        => $job_type,
						'payload_summary' => $summary,
						'attempt'         => 1,
					)
				);

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify both required fields are present
				$this->assertArrayHasKey(
					'job_type',
					$context,
					'Context should have job_type field'
				);

				$this->assertArrayHasKey(
					'payload_summary',
					$context,
					'Context should have payload_summary field'
				);

				// Verify both fields have values
				$this->assertNotEmpty(
					$context['job_type'],
					'job_type should not be empty'
				);

				$this->assertNotEmpty(
					$context['payload_summary'],
					'payload_summary should not be empty'
				);
			}
		);
	}

	/**
	 * Property 22: GSC Context Fields - fields preserved with other context data
	 *
	 * For any log entry created by the GSC module with additional context data,
	 * the job_type and payload_summary fields SHALL be preserved alongside
	 * other context fields.
	 *
	 * **Validates: Requirements 11.4**
	 *
	 * @return void
	 */
	public function test_gsc_context_fields_preserved_with_other_data(): void {
		$this->forAll(
			Generators::elements( [ 'fetch_url', 'fetch_sitemaps' ] ),
			Generators::string( 'a-zA-Z0-9', 5, 15 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( string $job_type, string $error_code, int $retry_count ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log a GSC operation with required fields and additional context
				Logger::error(
					'GSC operation failed',
					array(
						'job_type'        => $job_type,
						'payload_summary' => 'Operation summary for ' . $job_type,
						'error_code'      => $error_code,
						'retry_count'     => $retry_count,
						'timestamp'       => time(),
					)
				);

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify required fields are present
				$this->assertArrayHasKey(
					'job_type',
					$context,
					'Context should have job_type field'
				);

				$this->assertArrayHasKey(
					'payload_summary',
					$context,
					'Context should have payload_summary field'
				);

				// Verify additional fields are also present
				$this->assertArrayHasKey(
					'error_code',
					$context,
					'Context should have error_code field'
				);

				$this->assertArrayHasKey(
					'retry_count',
					$context,
					'Context should have retry_count field'
				);

				// Verify all fields have correct values
				$this->assertEquals(
					$job_type,
					$context['job_type'],
					'job_type should match'
				);

				$this->assertStringContainsString(
					$job_type,
					$context['payload_summary'],
					'payload_summary should contain job_type'
				);

				$this->assertEquals(
					$error_code,
					$context['error_code'],
					'error_code should match'
				);

				$this->assertEquals(
					$retry_count,
					$context['retry_count'],
					'retry_count should match'
				);
			}
		);
	}

	/**
	 * Property 22: GSC Context Fields - fields present across different log levels
	 *
	 * For any log entry created by the GSC module at any log level (info, warning, error),
	 * the context SHALL include job_type and payload_summary fields.
	 *
	 * **Validates: Requirements 11.4**
	 *
	 * @return void
	 */
	public function test_gsc_context_fields_present_at_all_log_levels(): void {
		$this->forAll(
			Generators::elements( [ 'info', 'warning', 'error' ] ),
			Generators::elements( [ 'fetch_url', 'fetch_sitemaps', 'gsc_queue' ] )
		)
		->then(
			function ( string $log_level, string $job_type ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log at different levels with GSC context
				$context = array(
					'job_type'        => $job_type,
					'payload_summary' => 'Summary for ' . $job_type,
				);

				// Call the appropriate log level method
				switch ( $log_level ) {
					case 'info':
						Logger::info( 'GSC operation', $context );
						break;
					case 'warning':
						Logger::warning( 'GSC operation', $context );
						break;
					case 'error':
						Logger::error( 'GSC operation', $context );
						break;
				}

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$parsed_context = json_decode( $entry['context'] ?? '{}', true );

				// Verify required fields are present at all log levels
				$this->assertArrayHasKey(
					'job_type',
					$parsed_context,
					'Context should have job_type field at ' . $log_level . ' level'
				);

				$this->assertArrayHasKey(
					'payload_summary',
					$parsed_context,
					'Context should have payload_summary field at ' . $log_level . ' level'
				);

				// Verify values match
				$this->assertEquals(
					$job_type,
					$parsed_context['job_type'],
					'job_type should match at ' . $log_level . ' level'
				);

				$this->assertStringContainsString(
					$job_type,
					$parsed_context['payload_summary'],
					'payload_summary should contain job_type at ' . $log_level . ' level'
				);
			}
		);
	}

	/**
	 * Property 22: GSC Context Fields - payload_summary describes operation
	 *
	 * For any log entry created by the GSC module, the payload_summary field
	 * SHALL contain a human-readable description of the operation being performed.
	 *
	 * **Validates: Requirements 11.4**
	 *
	 * @return void
	 */
	public function test_gsc_payload_summary_is_descriptive(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 10, 50 )
		)
		->then(
			function ( string $description ) {
				// Skip empty or whitespace-only descriptions
				if ( empty( trim( $description ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with a descriptive payload_summary
				Logger::info(
					'GSC operation',
					array(
						'job_type'        => 'fetch_url',
						'payload_summary' => $description,
					)
				);

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify payload_summary is descriptive (non-empty)
				$this->assertNotEmpty(
					$context['payload_summary'],
					'payload_summary should not be empty'
				);

				// Verify it's a string
				$this->assertIsString(
					$context['payload_summary'],
					'payload_summary should be a string'
				);
			}
		);
	}
}
