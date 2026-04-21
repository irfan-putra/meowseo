<?php
/**
 * Tests for Update_Logger class.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Updater\Update_Logger;

/**
 * Test Update_Logger class.
 */
class Test_Update_Logger extends TestCase {

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
		$this->logger = new Update_Logger();
		
		// Clean up any existing logs.
		delete_option( 'meowseo_github_update_logs' );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		// Clean up.
		delete_option( 'meowseo_github_update_logs' );
		parent::tearDown();
	}

	/**
	 * Test log_check with successful check.
	 */
	public function test_log_check_success() {
		$context = array(
			'current_version' => '1.0.0-abc1234',
			'latest_version'  => '1.0.0-def5678',
			'update_available' => true,
		);

		$this->logger->log_check( true, null, $context );

		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );

		$log = $logs[0];
		$this->assertEquals( 'info', $log['level'] );
		$this->assertEquals( 'check', $log['type'] );
		$this->assertEquals( 'Update check completed successfully', $log['message'] );
		$this->assertEquals( $context, $log['context'] );
		$this->assertArrayHasKey( 'timestamp', $log );
	}

	/**
	 * Test log_check with failed check.
	 */
	public function test_log_check_failure() {
		$error = 'GitHub API unavailable';
		$context = array( 'endpoint' => '/repos/test/test/commits/main' );

		$this->logger->log_check( false, $error, $context );

		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );

		$log = $logs[0];
		$this->assertEquals( 'error', $log['level'] );
		$this->assertEquals( 'check', $log['type'] );
		$this->assertEquals( 'Update check failed', $log['message'] );
		$this->assertEquals( $error, $log['context']['error'] );
	}

	/**
	 * Test log_api_request with successful request.
	 */
	public function test_log_api_request_success() {
		$endpoint = '/repos/akbarbahaulloh/meowseo/commits/main';
		$response_code = 200;
		$rate_limit = array(
			'limit'     => 60,
			'remaining' => 59,
			'reset'     => time() + 3600,
		);

		$this->logger->log_api_request( $endpoint, $response_code, $rate_limit );

		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );

		$log = $logs[0];
		$this->assertEquals( 'info', $log['level'] );
		$this->assertEquals( 'api_request', $log['type'] );
		$this->assertStringContainsString( $endpoint, $log['message'] );
		$this->assertStringContainsString( '200', $log['message'] );
		$this->assertEquals( $endpoint, $log['context']['endpoint'] );
		$this->assertEquals( $response_code, $log['context']['response_code'] );
		$this->assertEquals( $rate_limit, $log['context']['rate_limit'] );
	}

	/**
	 * Test log_api_request with error response.
	 */
	public function test_log_api_request_error() {
		$endpoint = '/repos/invalid/repo/commits/main';
		$response_code = 404;

		$this->logger->log_api_request( $endpoint, $response_code );

		$logs = $this->logger->get_recent_logs( 1 );
		$log = $logs[0];

		$this->assertEquals( 'error', $log['level'] );
		$this->assertEquals( 'api_request', $log['type'] );
	}

	/**
	 * Test log_installation with successful installation.
	 */
	public function test_log_installation_success() {
		$version = '1.0.0-def5678';
		$context = array( 'commit_id' => 'def5678' );

		$this->logger->log_installation( true, $version, null, $context );

		$logs = $this->logger->get_recent_logs( 1 );
		$log = $logs[0];

		$this->assertEquals( 'info', $log['level'] );
		$this->assertEquals( 'installation', $log['type'] );
		$this->assertStringContainsString( $version, $log['message'] );
		$this->assertStringContainsString( 'installed successfully', $log['message'] );
		$this->assertEquals( $version, $log['context']['version'] );
	}

	/**
	 * Test log_installation with failed installation.
	 */
	public function test_log_installation_failure() {
		$version = '1.0.0-def5678';
		$error = 'Invalid ZIP file';
		$context = array( 'commit_id' => 'def5678' );

		$this->logger->log_installation( false, $version, $error, $context );

		$logs = $this->logger->get_recent_logs( 1 );
		$log = $logs[0];

		$this->assertEquals( 'error', $log['level'] );
		$this->assertEquals( 'installation', $log['type'] );
		$this->assertStringContainsString( 'failed', $log['message'] );
		$this->assertEquals( $error, $log['context']['error'] );
	}

	/**
	 * Test log_config_change.
	 */
	public function test_log_config_change() {
		$old_config = array(
			'repo_owner' => 'olduser',
			'branch'     => 'main',
		);

		$new_config = array(
			'repo_owner' => 'newuser',
			'branch'     => 'develop',
		);

		$this->logger->log_config_change( $old_config, $new_config );

		$logs = $this->logger->get_recent_logs( 1 );
		$log = $logs[0];

		$this->assertEquals( 'info', $log['level'] );
		$this->assertEquals( 'config_change', $log['type'] );
		$this->assertEquals( 'Update configuration changed', $log['message'] );
		$this->assertArrayHasKey( 'changes', $log['context'] );
		$this->assertArrayHasKey( 'repo_owner', $log['context']['changes'] );
		$this->assertArrayHasKey( 'branch', $log['context']['changes'] );
		$this->assertEquals( 'olduser', $log['context']['changes']['repo_owner']['old'] );
		$this->assertEquals( 'newuser', $log['context']['changes']['repo_owner']['new'] );
	}

	/**
	 * Test get_recent_logs returns correct number of entries.
	 */
	public function test_get_recent_logs_limit() {
		// Create 10 log entries.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->logger->log_check( true, null, array( 'iteration' => $i ) );
		}

		// Get only 5 most recent.
		$logs = $this->logger->get_recent_logs( 5 );
		$this->assertCount( 5, $logs );

		// Verify most recent is first.
		$this->assertEquals( 9, $logs[0]['context']['iteration'] );
		$this->assertEquals( 5, $logs[4]['context']['iteration'] );
	}

	/**
	 * Test logs are stored in reverse chronological order.
	 */
	public function test_logs_chronological_order() {
		$this->logger->log_check( true, null, array( 'order' => 'first' ) );
		sleep( 1 ); // Ensure different timestamps.
		$this->logger->log_check( true, null, array( 'order' => 'second' ) );
		sleep( 1 );
		$this->logger->log_check( true, null, array( 'order' => 'third' ) );

		$logs = $this->logger->get_recent_logs( 3 );

		$this->assertEquals( 'third', $logs[0]['context']['order'] );
		$this->assertEquals( 'second', $logs[1]['context']['order'] );
		$this->assertEquals( 'first', $logs[2]['context']['order'] );
	}

	/**
	 * Test maximum log entries limit (100).
	 */
	public function test_max_entries_limit() {
		// Create 150 log entries.
		for ( $i = 0; $i < 150; $i++ ) {
			$this->logger->log_check( true, null, array( 'iteration' => $i ) );
		}

		$logs = $this->logger->get_recent_logs( 200 );

		// Should only keep 100 most recent.
		$this->assertCount( 100, $logs );

		// Verify oldest kept entry is iteration 50 (150 - 100).
		$this->assertEquals( 149, $logs[0]['context']['iteration'] );
		$this->assertEquals( 50, $logs[99]['context']['iteration'] );
	}

	/**
	 * Test clear_old_logs removes logs older than specified days.
	 */
	public function test_clear_old_logs() {
		// Create logs with different timestamps.
		$logs = array();

		// Old log (40 days ago).
		$logs[] = array(
			'timestamp' => date( 'Y-m-d H:i:s', time() - ( 40 * DAY_IN_SECONDS ) ),
			'level'     => 'info',
			'type'      => 'check',
			'message'   => 'Old log',
			'context'   => array(),
		);

		// Recent log (10 days ago).
		$logs[] = array(
			'timestamp' => date( 'Y-m-d H:i:s', time() - ( 10 * DAY_IN_SECONDS ) ),
			'level'     => 'info',
			'type'      => 'check',
			'message'   => 'Recent log',
			'context'   => array(),
		);

		// Current log.
		$logs[] = array(
			'timestamp' => current_time( 'mysql' ),
			'level'     => 'info',
			'type'      => 'check',
			'message'   => 'Current log',
			'context'   => array(),
		);

		update_option( 'meowseo_github_update_logs', $logs );

		// Clear logs older than 30 days.
		$removed = $this->logger->clear_old_logs( 30 );

		$this->assertEquals( 1, $removed );

		$remaining_logs = $this->logger->get_recent_logs( 10 );
		$this->assertCount( 2, $remaining_logs );

		// Verify old log is removed.
		foreach ( $remaining_logs as $log ) {
			$this->assertNotEquals( 'Old log', $log['message'] );
		}
	}

	/**
	 * Test clear_old_logs with custom retention period.
	 */
	public function test_clear_old_logs_custom_days() {
		// Create logs with different timestamps.
		$logs = array();

		// 20 days old.
		$logs[] = array(
			'timestamp' => date( 'Y-m-d H:i:s', time() - ( 20 * DAY_IN_SECONDS ) ),
			'level'     => 'info',
			'type'      => 'check',
			'message'   => '20 days old',
			'context'   => array(),
		);

		// 5 days old.
		$logs[] = array(
			'timestamp' => date( 'Y-m-d H:i:s', time() - ( 5 * DAY_IN_SECONDS ) ),
			'level'     => 'info',
			'type'      => 'check',
			'message'   => '5 days old',
			'context'   => array(),
		);

		update_option( 'meowseo_github_update_logs', $logs );

		// Clear logs older than 7 days.
		$removed = $this->logger->clear_old_logs( 7 );

		$this->assertEquals( 1, $removed );

		$remaining_logs = $this->logger->get_recent_logs( 10 );
		$this->assertCount( 1, $remaining_logs );
		$this->assertEquals( '5 days old', $remaining_logs[0]['message'] );
	}

	/**
	 * Test clear_old_logs returns 0 when no logs exist.
	 */
	public function test_clear_old_logs_empty() {
		$removed = $this->logger->clear_old_logs();
		$this->assertEquals( 0, $removed );
	}

	/**
	 * Test clear_old_logs handles invalid log entries.
	 */
	public function test_clear_old_logs_invalid_entries() {
		$logs = array(
			array(
				'timestamp' => current_time( 'mysql' ),
				'level'     => 'info',
				'type'      => 'check',
				'message'   => 'Valid log',
				'context'   => array(),
			),
			array(
				// Missing timestamp.
				'level'   => 'info',
				'type'    => 'check',
				'message' => 'Invalid log',
				'context' => array(),
			),
		);

		update_option( 'meowseo_github_update_logs', $logs );

		$removed = $this->logger->clear_old_logs();

		// Invalid entry should be removed.
		$this->assertEquals( 1, $removed );

		$remaining_logs = $this->logger->get_recent_logs( 10 );
		$this->assertCount( 1, $remaining_logs );
		$this->assertEquals( 'Valid log', $remaining_logs[0]['message'] );
	}

	/**
	 * Test get_recent_logs returns empty array when no logs exist.
	 */
	public function test_get_recent_logs_empty() {
		$logs = $this->logger->get_recent_logs();
		$this->assertIsArray( $logs );
		$this->assertEmpty( $logs );
	}

	/**
	 * Test log entry structure.
	 */
	public function test_log_entry_structure() {
		$this->logger->log_check( true );

		$logs = $this->logger->get_recent_logs( 1 );
		$log = $logs[0];

		$this->assertArrayHasKey( 'timestamp', $log );
		$this->assertArrayHasKey( 'level', $log );
		$this->assertArrayHasKey( 'type', $log );
		$this->assertArrayHasKey( 'message', $log );
		$this->assertArrayHasKey( 'context', $log );

		$this->assertIsString( $log['timestamp'] );
		$this->assertIsString( $log['level'] );
		$this->assertIsString( $log['type'] );
		$this->assertIsString( $log['message'] );
		$this->assertIsArray( $log['context'] );
	}

	/**
	 * Test timestamp format.
	 */
	public function test_timestamp_format() {
		$this->logger->log_check( true );

		$logs = $this->logger->get_recent_logs( 1 );
		$timestamp = $logs[0]['timestamp'];

		// Verify MySQL datetime format (YYYY-MM-DD HH:MM:SS).
		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
			$timestamp
		);
	}

	/**
	 * Test multiple log types can coexist.
	 */
	public function test_multiple_log_types() {
		$this->logger->log_check( true );
		$this->logger->log_api_request( '/test', 200 );
		$this->logger->log_installation( true, '1.0.0' );
		$this->logger->log_config_change( array(), array( 'branch' => 'main' ) );

		$logs = $this->logger->get_recent_logs( 10 );
		$this->assertCount( 4, $logs );

		$types = array_column( $logs, 'type' );
		$this->assertContains( 'check', $types );
		$this->assertContains( 'api_request', $types );
		$this->assertContains( 'installation', $types );
		$this->assertContains( 'config_change', $types );
	}
}
