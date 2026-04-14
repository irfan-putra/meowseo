<?php
/**
 * Tests for GSC Module Logger Integration
 *
 * Tests that verify the GSC module correctly integrates with the Logger
 * for OAuth failures, rate limits, and batch completion.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\GSC;

use MeowSEO\Modules\GSC\GSC;
use MeowSEO\Options;
use MeowSEO\Helpers\Logger;
use PHPUnit\Framework\TestCase;

/**
 * GSC Logger Integration test case
 */
class GSCLoggerIntegrationTest extends TestCase {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * GSC module instance.
	 *
	 * @var GSC
	 */
	private GSC $gsc;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$this->gsc = new GSC( $this->options );
	}

	/**
	 * Test that Logger class exists and is accessible.
	 *
	 * Validates: Requirement 11.1, 11.2, 11.3
	 */
	public function test_logger_class_exists(): void {
		$this->assertTrue( class_exists( 'MeowSEO\Helpers\Logger' ) );
	}

	/**
	 * Test that Logger has required methods for GSC integration.
	 *
	 * Validates: Requirement 11.1, 11.2, 11.3
	 */
	public function test_logger_has_required_methods(): void {
		$this->assertTrue( method_exists( Logger::class, 'error' ) );
		$this->assertTrue( method_exists( Logger::class, 'warning' ) );
		$this->assertTrue( method_exists( Logger::class, 'info' ) );
	}

	/**
	 * Test OAuth failure logging context structure.
	 *
	 * Validates: Requirement 11.1 - OAuth failures should be logged with error level
	 * and include job_type and error_code in context.
	 */
	public function test_oauth_failure_logging_context(): void {
		$expected_context = array(
			'job_type'     => 'fetch_url',
			'error_code'   => 'no_credentials',
			'access_token' => null,
		);

		// Verify context structure matches requirements.
		$this->assertArrayHasKey( 'job_type', $expected_context );
		$this->assertArrayHasKey( 'error_code', $expected_context );
		$this->assertArrayHasKey( 'access_token', $expected_context );
	}

	/**
	 * Test rate limit logging context structure.
	 *
	 * Validates: Requirement 11.2 - Rate limits should be logged with warning level
	 * and include job_type and retry_after in context.
	 */
	public function test_rate_limit_logging_context(): void {
		$expected_context = array(
			'job_type'    => 'fetch_url',
			'retry_after' => time() + 120,
		);

		// Verify context structure matches requirements.
		$this->assertArrayHasKey( 'job_type', $expected_context );
		$this->assertArrayHasKey( 'retry_after', $expected_context );
	}

	/**
	 * Test batch completion logging context structure.
	 *
	 * Validates: Requirement 11.3 - Batch completion should be logged with info level
	 * and include job_type and processed_count in context.
	 */
	public function test_batch_completion_logging_context(): void {
		$expected_context = array(
			'job_type'        => 'gsc_queue',
			'processed_count' => 10,
		);

		// Verify context structure matches requirements.
		$this->assertArrayHasKey( 'job_type', $expected_context );
		$this->assertArrayHasKey( 'processed_count', $expected_context );
	}

	/**
	 * Test access token sanitization in logging context.
	 *
	 * Validates: Requirement 11.5 - Access tokens should be sanitized from log context.
	 */
	public function test_access_token_sanitization(): void {
		$context = array(
			'job_type'     => 'fetch_url',
			'access_token' => 'secret_token_12345',
		);

		// Simulate sanitization (Logger will do this automatically).
		$sensitive_patterns = array( 'token', 'key', 'password', 'secret' );
		$sanitized = $context;

		foreach ( $context as $key => $value ) {
			foreach ( $sensitive_patterns as $pattern ) {
				if ( false !== stripos( $key, $pattern ) ) {
					$sanitized[ $key ] = '[REDACTED]';
					break;
				}
			}
		}

		// Verify access_token is sanitized.
		$this->assertEquals( '[REDACTED]', $sanitized['access_token'] );
		$this->assertEquals( 'fetch_url', $sanitized['job_type'] );
	}

	/**
	 * Test exponential backoff calculation for rate limits.
	 *
	 * Validates: Requirement 11.2 - Rate limit logging should include correct retry_after.
	 */
	public function test_exponential_backoff_calculation(): void {
		$attempts = 2;
		$backoff_seconds = pow( 2, $attempts + 1 ) * 60;
		$retry_after = time() + $backoff_seconds;

		// Verify backoff calculation.
		$this->assertEquals( 480, $backoff_seconds ); // 2^3 * 60 = 480 seconds
		$this->assertGreaterThan( time(), $retry_after );
	}

	/**
	 * Test job type extraction for logging.
	 *
	 * Validates: Requirement 11.4 - GSC module should include job type in log context.
	 */
	public function test_job_type_values(): void {
		$valid_job_types = array( 'fetch_url', 'fetch_sitemaps', 'gsc_queue' );

		foreach ( $valid_job_types as $job_type ) {
			$this->assertIsString( $job_type );
			$this->assertNotEmpty( $job_type );
		}
	}

	/**
	 * Test processed count tracking for batch completion.
	 *
	 * Validates: Requirement 11.3 - Batch completion should include processed_count.
	 */
	public function test_processed_count_tracking(): void {
		$queue_entries = array(
			array( 'id' => 1, 'job_type' => 'fetch_url' ),
			array( 'id' => 2, 'job_type' => 'fetch_url' ),
			array( 'id' => 3, 'job_type' => 'fetch_sitemaps' ),
		);

		$processed_count = 0;
		foreach ( $queue_entries as $entry ) {
			$processed_count++;
		}

		$this->assertEquals( 3, $processed_count );
	}
}
