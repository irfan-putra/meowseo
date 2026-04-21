<?php
/**
 * Tests for Error Handling - Task 19
 *
 * This test file verifies error handling functionality including:
 * - API error mapping to user-friendly messages
 * - Rate limit error handling with retry-after time
 * - Network timeout error handling
 * - Authentication error handling
 * - Repository not found error handling
 * - Error logging with context
 * - Admin notice display for errors
 *
 * @package MeowSEO
 * @subpackage Tests\Updater
 */

namespace MeowSEO\Tests\Updater;

use PHPUnit\Framework\TestCase;
use MeowSEO\Updater\GitHub_Update_Checker;
use MeowSEO\Updater\Update_Config;
use MeowSEO\Updater\Update_Logger;

/**
 * Test error handling functionality.
 */
class Test_Error_Handling extends TestCase {

	/**
	 * GitHub_Update_Checker instance.
	 *
	 * @var GitHub_Update_Checker
	 */
	private GitHub_Update_Checker $checker;

	/**
	 * Update_Logger instance.
	 *
	 * @var Update_Logger
	 */
	private Update_Logger $logger;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Clean up any existing data.
		delete_option( 'meowseo_github_update_logs' );
		delete_transient( 'meowseo_update_error_notice' );

		// Create instances.
		$config = new Update_Config();
		$this->logger = new Update_Logger();
		$this->checker = new GitHub_Update_Checker( MEOWSEO_FILE, $config, $this->logger );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		// Clean up.
		delete_option( 'meowseo_github_update_logs' );
		delete_transient( 'meowseo_update_error_notice' );

		parent::tearDown();
	}

	/**
	 * Test 1: Handle API error - 404 Repository not found.
	 */
	public function test_handle_api_error_404_not_found() {
		// Call handle_api_error.
		$result = $this->checker->handle_api_error( 404, 'Repository not found' );

		// Verify result structure.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'user_message', $result );
		$this->assertArrayHasKey( 'log_message', $result );
		$this->assertArrayHasKey( 'is_rate_limited', $result );
		$this->assertArrayHasKey( 'retry_after', $result );

		// Verify error message contains "not found".
		$this->assertStringContainsString( 'not found', strtolower( $result['user_message'] ) );

		// Verify not rate limited.
		$this->assertFalse( $result['is_rate_limited'] );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]['level'] );
	}

	/**
	 * Test 2: Handle API error - 403 Rate limit exceeded.
	 */
	public function test_handle_api_error_403_rate_limit() {
		// Prepare rate limit info.
		$rate_limit = array(
			'limit' => 60,
			'remaining' => 0,
			'reset' => time() + 3600,
		);

		// Call handle_api_error.
		$result = $this->checker->handle_api_error( 403, 'API rate limit exceeded', $rate_limit );

		// Verify result structure.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'user_message', $result );
		$this->assertArrayHasKey( 'is_rate_limited', $result );
		$this->assertArrayHasKey( 'retry_after', $result );

		// Verify rate limited flag is set.
		$this->assertTrue( $result['is_rate_limited'] );

		// Verify retry_after is set.
		$this->assertGreater( $result['retry_after'], 0 );

		// Verify error message contains rate limit info.
		$this->assertStringContainsString( 'rate limit', strtolower( $result['user_message'] ) );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]['level'] );
	}

	/**
	 * Test 3: Handle API error - 401 Authentication error.
	 */
	public function test_handle_api_error_401_auth() {
		// Call handle_api_error.
		$result = $this->checker->handle_api_error( 401, 'Invalid credentials' );

		// Verify error message contains "authentication".
		$this->assertStringContainsString( 'authentication', strtolower( $result['user_message'] ) );

		// Verify not rate limited.
		$this->assertFalse( $result['is_rate_limited'] );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]['level'] );
	}

	/**
	 * Test 4: Handle API error - 500 Server error.
	 */
	public function test_handle_api_error_500_server() {
		// Call handle_api_error.
		$result = $this->checker->handle_api_error( 500, 'Internal Server Error' );

		// Verify error message contains "GitHub" or "server".
		$this->assertTrue(
			strpos( strtolower( $result['user_message'] ), 'github' ) !== false ||
			strpos( strtolower( $result['user_message'] ), 'server' ) !== false
		);

		// Verify not rate limited.
		$this->assertFalse( $result['is_rate_limited'] );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]['level'] );
	}

	/**
	 * Test 5: Handle API error - 0 Network timeout.
	 */
	public function test_handle_api_error_0_network_timeout() {
		// Call handle_api_error.
		$result = $this->checker->handle_api_error( 0, 'Connection timed out' );

		// Verify error message contains "connect" or "timeout".
		$this->assertTrue(
			strpos( strtolower( $result['user_message'] ), 'connect' ) !== false ||
			strpos( strtolower( $result['user_message'] ), 'timeout' ) !== false
		);

		// Verify not rate limited.
		$this->assertFalse( $result['is_rate_limited'] );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]['level'] );
	}

	/**
	 * Test 6: Check rate limit - Not limited.
	 */
	public function test_check_rate_limit_not_limited() {
		// Set rate limit cache with remaining requests.
		$rate_limit = array(
			'limit' => 60,
			'remaining' => 30,
			'reset' => time() + 3600,
		);
		set_transient( 'meowseo_github_rate_limit', $rate_limit, HOUR_IN_SECONDS );

		// Call check_rate_limit.
		$result = $this->checker->check_rate_limit();

		// Verify result structure.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_limited', $result );
		$this->assertArrayHasKey( 'retry_after', $result );
		$this->assertArrayHasKey( 'reset_time', $result );

		// Verify not limited.
		$this->assertFalse( $result['is_limited'] );

		// Verify retry_after is 0.
		$this->assertEquals( 0, $result['retry_after'] );
	}

	/**
	 * Test 7: Check rate limit - Limited.
	 */
	public function test_check_rate_limit_limited() {
		// Set rate limit cache with no remaining requests.
		$reset_time = time() + 3600;
		$rate_limit = array(
			'limit' => 60,
			'remaining' => 0,
			'reset' => $reset_time,
		);
		set_transient( 'meowseo_github_rate_limit', $rate_limit, HOUR_IN_SECONDS );

		// Call check_rate_limit.
		$result = $this->checker->check_rate_limit();

		// Verify limited.
		$this->assertTrue( $result['is_limited'] );

		// Verify retry_after is approximately 3600 seconds.
		$this->assertGreater( $result['retry_after'], 3590 );
		$this->assertLess( $result['retry_after'], 3610 );
	}

	/**
	 * Test 8: Check rate limit - No cache.
	 */
	public function test_check_rate_limit_no_cache() {
		// Ensure no rate limit cache.
		delete_transient( 'meowseo_github_rate_limit' );

		// Call check_rate_limit.
		$result = $this->checker->check_rate_limit();

		// Verify not limited (no cache means assume not limited).
		$this->assertFalse( $result['is_limited'] );

		// Verify retry_after is 0.
		$this->assertEquals( 0, $result['retry_after'] );
	}

	/**
	 * Test 9: Is rate limited - True.
	 */
	public function test_is_rate_limited_true() {
		// Set rate limit cache with no remaining requests.
		$rate_limit = array(
			'limit' => 60,
			'remaining' => 0,
			'reset' => time() + 3600,
		);
		set_transient( 'meowseo_github_rate_limit', $rate_limit, HOUR_IN_SECONDS );

		// Call is_rate_limited.
		$result = $this->checker->is_rate_limited();

		// Verify true.
		$this->assertTrue( $result );
	}

	/**
	 * Test 10: Is rate limited - False.
	 */
	public function test_is_rate_limited_false() {
		// Set rate limit cache with remaining requests.
		$rate_limit = array(
			'limit' => 60,
			'remaining' => 30,
			'reset' => time() + 3600,
		);
		set_transient( 'meowseo_github_rate_limit', $rate_limit, HOUR_IN_SECONDS );

		// Call is_rate_limited.
		$result = $this->checker->is_rate_limited();

		// Verify false.
		$this->assertFalse( $result );
	}

	/**
	 * Test 11: Error logging includes context.
	 */
	public function test_error_logging_includes_context() {
		// Call handle_api_error with context.
		$rate_limit = array(
			'limit' => 60,
			'remaining' => 0,
			'reset' => time() + 3600,
		);
		$this->checker->handle_api_error( 403, 'Rate limit exceeded', $rate_limit );

		// Verify error was logged with context.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertArrayHasKey( 'context', $logs[0] );
		$this->assertArrayHasKey( 'response_code', $logs[0]['context'] );
		$this->assertEquals( 403, $logs[0]['context']['response_code'] );
	}

	/**
	 * Test 12: Multiple errors are logged separately.
	 */
	public function test_multiple_errors_logged_separately() {
		// Log first error.
		$this->checker->handle_api_error( 404, 'Not found' );

		// Log second error.
		$this->checker->handle_api_error( 500, 'Server error' );

		// Verify both errors are logged.
		$logs = $this->logger->get_recent_logs( 2 );
		$this->assertCount( 2, $logs );

		// Verify first error (most recent).
		$this->assertEquals( 500, $logs[0]['context']['response_code'] );

		// Verify second error.
		$this->assertEquals( 404, $logs[1]['context']['response_code'] );
	}

	/**
	 * Test 13: Error message is user-friendly (no technical jargon).
	 */
	public function test_error_message_user_friendly() {
		// Call handle_api_error.
		$result = $this->checker->handle_api_error( 404, 'Repository not found' );

		// Verify message is user-friendly (no JSON, no raw error codes).
		$message = $result['user_message'];
		$this->assertStringNotContainsString( '{', $message );
		$this->assertStringNotContainsString( '}', $message );
		$this->assertStringNotContainsString( 'HTTP', $message );

		// Verify message is in English.
		$this->assertIsString( $message );
		$this->assertGreater( strlen( $message ), 0 );
	}

	/**
	 * Test 14: Rate limit error includes reset time.
	 */
	public function test_rate_limit_error_includes_reset_time() {
		// Prepare rate limit info.
		$reset_time = time() + 3600;
		$rate_limit = array(
			'limit' => 60,
			'remaining' => 0,
			'reset' => $reset_time,
		);

		// Call handle_api_error.
		$result = $this->checker->handle_api_error( 403, 'Rate limit exceeded', $rate_limit );

		// Verify message includes reset time information.
		$this->assertStringContainsString( 'resume', strtolower( $result['user_message'] ) );
	}

	/**
	 * Test 15: Error handling doesn't break on missing rate limit info.
	 */
	public function test_error_handling_missing_rate_limit() {
		// Call handle_api_error with empty rate limit.
		$result = $this->checker->handle_api_error( 403, 'Access forbidden', array() );

		// Verify result is valid.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'user_message', $result );

		// Verify message is still user-friendly.
		$this->assertGreater( strlen( $result['user_message'] ), 0 );
	}
}
