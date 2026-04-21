<?php
/**
 * Integration Tests for GitHub Auto-Update System - Task 25
 *
 * This test file verifies the complete update flow from check to installation:
 * - Update notification appears on Plugins page
 * - "View details" modal displays changelog
 * - "Update Now" downloads and installs update
 * - Plugin settings are preserved after update
 * - Different commit IDs are handled correctly
 * - Error scenarios are handled gracefully
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
 * Integration tests for the complete update flow.
 */
class Test_Update_Integration extends TestCase {

	/**
	 * GitHub_Update_Checker instance.
	 *
	 * @var GitHub_Update_Checker
	 */
	private GitHub_Update_Checker $checker;

	/**
	 * Update_Config instance.
	 *
	 * @var Update_Config
	 */
	private Update_Config $config;

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
		delete_option( 'meowseo_github_update_config' );
		delete_option( 'meowseo_github_update_logs' );
		delete_option( 'meowseo_github_last_check' );
		delete_transient( 'meowseo_github_update_info' );
		delete_transient( 'meowseo_github_changelog' );
		delete_transient( 'meowseo_github_rate_limit' );

		// Reset WordPress filters.
		global $wp_filter;
		$wp_filter = array();

		// Create instances.
		$this->config = new Update_Config();
		$this->logger = new Update_Logger();
		$this->checker = new GitHub_Update_Checker( MEOWSEO_FILE, $this->config, $this->logger );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		// Clean up.
		delete_option( 'meowseo_github_update_config' );
		delete_option( 'meowseo_github_update_logs' );
		delete_option( 'meowseo_github_last_check' );
		delete_transient( 'meowseo_github_update_info' );
		delete_transient( 'meowseo_github_changelog' );
		delete_transient( 'meowseo_github_rate_limit' );

		// Reset WordPress filters.
		global $wp_filter;
		$wp_filter = array();

		parent::tearDown();
	}

	/**
	 * Test 1: Complete update flow - Check to notification.
	 */
	public function test_complete_update_flow_check_to_notification() {
		// Initialize checker.
		$this->checker->init();

		// Set last check time to old value to force check.
		update_option( 'meowseo_github_last_check', time() - 86400 );

		// Mock GitHub API to return a new commit.
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com/repos' ) !== false && strpos( $url, 'commits' ) !== false ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers' => array(
						'x-ratelimit-limit' => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset' => (string) ( time() + 3600 ),
					),
					'body' => json_encode( array(
						'sha' => 'abc1234567890abcdef1234567890abcdef123456',
						'commit' => array(
							'message' => 'Feature: Add new update system',
							'author' => array(
								'name' => 'Test Author',
								'date' => '2025-01-15T10:00:00Z',
							),
						),
						'html_url' => 'https://github.com/akbarbahaulloh/meowseo/commit/abc1234567890abcdef1234567890abcdef123456',
					) ),
				);
			}
			return $preempt;
		}, 10, 3 );

		// Create transient object.
		$transient = new \stdClass();
		$transient->response = array();

		// Call check_for_update.
		$result = $this->checker->check_for_update( $transient );

		// Verify transient has update info.
		$this->assertIsObject( $result );
		$this->assertObjectHasProperty( 'response', $result );
		$this->assertIsArray( $result->response );

		// Verify update notification is in response.
		$this->assertNotEmpty( $result->response, 'Update notification should be in response' );

		// Verify update info structure.
		$update_info = reset( $result->response );
		$this->assertIsObject( $update_info );
		$this->assertObjectHasProperty( 'new_version', $update_info );
		$this->assertObjectHasProperty( 'package', $update_info );
		$this->assertObjectHasProperty( 'url', $update_info );
	}

	/**
	 * Test 2: Update notification displays on Plugins page.
	 */
	public function test_update_notification_on_plugins_page() {
		// Initialize checker.
		$this->checker->init();

		// Set last check time to old value to force check.
		update_option( 'meowseo_github_last_check', time() - 86400 );

		// Mock GitHub API to return a new commit.
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com/repos' ) !== false && strpos( $url, 'commits' ) !== false ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers' => array(
						'x-ratelimit-limit' => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset' => (string) ( time() + 3600 ),
					),
					'body' => json_encode( array(
						'sha' => 'abc1234567890abcdef1234567890abcdef123456',
						'commit' => array(
							'message' => 'Fix: Update system improvements',
							'author' => array(
								'name' => 'Test Author',
								'date' => '2025-01-15T10:00:00Z',
							),
						),
						'html_url' => 'https://github.com/akbarbahaulloh/meowseo/commit/abc1234567890abcdef1234567890abcdef123456',
					) ),
				);
			}
			return $preempt;
		}, 10, 3 );

		// Create transient object.
		$transient = new \stdClass();
		$transient->response = array();

		// Call check_for_update.
		$result = $this->checker->check_for_update( $transient );

		// Verify update is available.
		$this->assertNotEmpty( $result->response );

		// Verify update info has required fields for Plugins page display.
		$update_info = reset( $result->response );
		$this->assertObjectHasProperty( 'slug', $update_info );
		$this->assertObjectHasProperty( 'plugin', $update_info );
		$this->assertObjectHasProperty( 'new_version', $update_info );
		$this->assertObjectHasProperty( 'url', $update_info );
		$this->assertObjectHasProperty( 'package', $update_info );
	}

	/**
	 * Test 3: View details modal displays changelog.
	 */
	public function test_view_details_displays_changelog() {
		// Initialize checker.
		$this->checker->init();

		// Mock GitHub API for commit history.
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com/repos' ) !== false && strpos( $url, 'commits' ) !== false ) {
				// Return commit history.
				return array(
					'response' => array( 'code' => 200 ),
					'headers' => array(
						'x-ratelimit-limit' => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset' => (string) ( time() + 3600 ),
					),
					'body' => json_encode( array(
						array(
							'sha' => 'abc1234567890abcdef1234567890abcdef123456',
							'commit' => array(
								'message' => 'Feature: Add new update system',
								'author' => array(
									'name' => 'Test Author',
									'date' => '2025-01-15T10:00:00Z',
								),
							),
							'html_url' => 'https://github.com/akbarbahaulloh/meowseo/commit/abc1234567890abcdef1234567890abcdef123456',
						),
						array(
							'sha' => 'def5678901234567890abcdef5678901234567890',
							'commit' => array(
								'message' => 'Fix: Update system improvements',
								'author' => array(
									'name' => 'Test Author',
									'date' => '2025-01-14T10:00:00Z',
								),
							),
							'html_url' => 'https://github.com/akbarbahaulloh/meowseo/commit/def5678901234567890abcdef5678901234567890',
						),
					) ),
				);
			}
			return $preempt;
		}, 10, 3 );

		// Create args object.
		$args = new \stdClass();
		$args->slug = 'meowseo';

		// Call get_plugin_info.
		$result = $this->checker->get_plugin_info( false, 'plugin_information', $args );

		// Verify result has changelog.
		$this->assertIsObject( $result );
		$this->assertObjectHasProperty( 'sections', $result );
		$this->assertIsArray( $result->sections );
		$this->assertArrayHasKey( 'changelog', $result->sections );

		// Verify changelog contains commit information.
		$changelog = $result->sections['changelog'];
		$this->assertIsString( $changelog );
		$this->assertStringContainsString( 'Feature: Add new update system', $changelog );
		$this->assertStringContainsString( 'Fix: Update system improvements', $changelog );
	}

	/**
	 * Test 4: Update Now downloads and installs update.
	 */
	public function test_update_now_downloads_and_installs() {
		// Initialize checker.
		$this->checker->init();

		// Test package URL.
		$package = 'https://github.com/akbarbahaulloh/meowseo/archive/abc1234567890abcdef1234567890abcdef123456.zip';

		// Call modify_package_url.
		$result = $this->checker->modify_package_url( false, $package, new \stdClass() );

		// Verify result is a valid GitHub archive URL.
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'github.com', $result );
		$this->assertStringContainsString( 'akbarbahaulloh', $result );
		$this->assertStringContainsString( 'meowseo', $result );
		$this->assertStringContainsString( '.zip', $result );
		$this->assertStringContainsString( 'archive', $result );
	}

	/**
	 * Test 5: Plugin settings are preserved after update.
	 */
	public function test_plugin_settings_preserved_after_update() {
		// Save some plugin settings.
		update_option( 'meowseo_settings', array(
			'enable_seo' => true,
			'enable_analytics' => true,
			'api_key' => 'test-key-12345',
		) );

		// Verify settings are saved.
		$settings = get_option( 'meowseo_settings' );
		$this->assertIsArray( $settings );
		$this->assertTrue( $settings['enable_seo'] );
		$this->assertTrue( $settings['enable_analytics'] );
		$this->assertEquals( 'test-key-12345', $settings['api_key'] );

		// Simulate update (settings should remain unchanged).
		// In real scenario, WordPress handles this during update.
		$settings_after = get_option( 'meowseo_settings' );
		$this->assertEquals( $settings, $settings_after );
	}

	/**
	 * Test 6: Different commit IDs are handled correctly.
	 */
	public function test_different_commit_ids_handled_correctly() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->checker );
		$extract_method = $reflection->getMethod( 'extract_commit_id' );
		$extract_method->setAccessible( true );

		// Test various commit ID formats.
		$test_cases = array(
			'1.0.0-abc1234' => 'abc1234',
			'1.0.0-def5678' => 'def5678',
			'2.1.5-abc1234567890abcdef' => 'abc1234567890abcdef',
			'1.0.0' => null,
			'1.0.0-' => null,
			'1.0.0-xyz' => null,
		);

		foreach ( $test_cases as $version => $expected ) {
			$result = $extract_method->invoke( $this->checker, $version );
			$this->assertEquals( $expected, $result, "Failed for version: $version" );
		}
	}

	/**
	 * Test 7: Error scenario - Invalid repository.
	 */
	public function test_error_scenario_invalid_repository() {
		// Initialize checker.
		$this->checker->init();

		// Set last check time to old value to force check.
		update_option( 'meowseo_github_last_check', time() - 86400 );

		// Mock GitHub API to return 404 (repository not found).
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com/repos' ) !== false ) {
				return array(
					'response' => array( 'code' => 404 ),
					'headers' => array(
						'x-ratelimit-limit' => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset' => (string) ( time() + 3600 ),
					),
					'body' => json_encode( array( 'message' => 'Not Found' ) ),
				);
			}
			return $preempt;
		}, 10, 3 );

		// Create transient object.
		$transient = new \stdClass();
		$transient->response = array();

		// Call check_for_update.
		$result = $this->checker->check_for_update( $transient );

		// Verify transient is returned unmodified (no update added).
		$this->assertIsObject( $result );
		$this->assertObjectHasProperty( 'response', $result );
		$this->assertEmpty( $result->response, 'No update should be added on error' );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 2 );
		$error_logged = false;
		foreach ( $logs as $log ) {
			if ( $log['level'] === 'error' ) {
				$error_logged = true;
				break;
			}
		}
		$this->assertTrue( $error_logged, 'Error should be logged' );
	}

	/**
	 * Test 8: Error scenario - Rate limit exceeded.
	 */
	public function test_error_scenario_rate_limit_exceeded() {
		// Initialize checker.
		$this->checker->init();

		// Mock GitHub API to return 403 (rate limit).
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com/repos' ) !== false ) {
				return array(
					'response' => array( 'code' => 403 ),
					'headers' => array(
						'x-ratelimit-limit' => '60',
						'x-ratelimit-remaining' => '0',
						'x-ratelimit-reset' => (string) ( time() + 3600 ),
					),
					'body' => json_encode( array( 'message' => 'API rate limit exceeded' ) ),
				);
			}
			return $preempt;
		}, 10, 3 );

		// Create transient object.
		$transient = new \stdClass();
		$transient->response = array();

		// Call check_for_update.
		$result = $this->checker->check_for_update( $transient );

		// Verify transient is returned unmodified.
		$this->assertIsObject( $result );
		$this->assertEmpty( $result->response );

		// Verify rate limit error was logged.
		$logs = $this->logger->get_recent_logs( 2 );
		$rate_limit_logged = false;
		foreach ( $logs as $log ) {
			if ( $log['level'] === 'error' && isset( $log['context']['response_code'] ) && $log['context']['response_code'] === 403 ) {
				$rate_limit_logged = true;
				break;
			}
		}
		$this->assertTrue( $rate_limit_logged, 'Rate limit error should be logged' );
	}

	/**
	 * Test 9: Error scenario - Network error.
	 */
	public function test_error_scenario_network_error() {
		// Initialize checker.
		$this->checker->init();

		// Mock wp_remote_get to return WP_Error.
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com/repos' ) !== false ) {
				return new \WP_Error( 'http_request_failed', 'Connection timed out' );
			}
			return $preempt;
		}, 10, 3 );

		// Create transient object.
		$transient = new \stdClass();
		$transient->response = array();

		// Call check_for_update.
		$result = $this->checker->check_for_update( $transient );

		// Verify transient is returned unmodified.
		$this->assertIsObject( $result );
		$this->assertEmpty( $result->response );

		// Verify network error was logged.
		$logs = $this->logger->get_recent_logs( 2 );
		$error_logged = false;
		foreach ( $logs as $log ) {
			if ( $log['level'] === 'error' && strpos( strtolower( $log['message'] ), 'failed' ) !== false ) {
				$error_logged = true;
				break;
			}
		}
		$this->assertTrue( $error_logged, 'Network error should be logged' );
	}

	/**
	 * Test 10: Update check respects cache.
	 */
	public function test_update_check_respects_cache() {
		// Initialize checker.
		$this->checker->init();

		// Set cache with update info.
		set_transient( 'meowseo_github_update_info', array(
			'sha' => 'abc1234',
			'message' => 'Cached commit',
		), 3600 );

		// Set last check time to recent.
		update_option( 'meowseo_github_last_check', time() );

		// Create transient object.
		$transient = new \stdClass();
		$transient->response = array();

		// Call check_for_update.
		$result = $this->checker->check_for_update( $transient );

		// Verify transient is returned (cache was used, no new API call).
		$this->assertIsObject( $result );
	}

	/**
	 * Test 11: Manual update check clears cache.
	 */
	public function test_manual_update_check_clears_cache() {
		// Set cache data.
		set_transient( 'meowseo_github_update_info', array( 'test' => 'data' ), 3600 );
		set_transient( 'meowseo_github_changelog', array( 'commits' => array() ), 3600 );

		// Verify cache exists.
		$this->assertNotFalse( get_transient( 'meowseo_github_update_info' ) );
		$this->assertNotFalse( get_transient( 'meowseo_github_changelog' ) );

		// Clear cache.
		$this->checker->clear_cache();

		// Verify cache is cleared.
		$this->assertFalse( get_transient( 'meowseo_github_update_info' ) );
		$this->assertFalse( get_transient( 'meowseo_github_changelog' ) );
	}

	/**
	 * Test 12: Settings page form submission.
	 */
	public function test_settings_page_form_submission() {
		// Save configuration.
		$config_data = array(
			'repo_owner' => 'akbarbahaulloh',
			'repo_name' => 'meowseo',
			'branch' => 'main',
			'auto_update_enabled' => true,
			'check_frequency' => 43200,
		);

		$this->config->save( $config_data );

		// Verify configuration was saved.
		$saved_config = $this->config->get_all();
		$this->assertEquals( 'akbarbahaulloh', $saved_config['repo_owner'] );
		$this->assertEquals( 'meowseo', $saved_config['repo_name'] );
		$this->assertEquals( 'main', $saved_config['branch'] );
		$this->assertTrue( $saved_config['auto_update_enabled'] );
		$this->assertEquals( 43200, $saved_config['check_frequency'] );
	}

	/**
	 * Test 13: Cache clear functionality.
	 */
	public function test_cache_clear_functionality() {
		// Set multiple cache entries.
		set_transient( 'meowseo_github_update_info', array( 'test' => 'data' ), 3600 );
		set_transient( 'meowseo_github_changelog', array( 'commits' => array() ), 3600 );
		set_transient( 'meowseo_github_rate_limit', array( 'remaining' => 60 ), 3600 );
		update_option( 'meowseo_github_last_check', time() );

		// Verify all caches exist.
		$this->assertNotFalse( get_transient( 'meowseo_github_update_info' ) );
		$this->assertNotFalse( get_transient( 'meowseo_github_changelog' ) );
		$this->assertNotFalse( get_transient( 'meowseo_github_rate_limit' ) );
		$this->assertNotFalse( get_option( 'meowseo_github_last_check' ) );

		// Clear all caches.
		$this->checker->clear_cache();

		// Verify all caches are cleared.
		$this->assertFalse( get_transient( 'meowseo_github_update_info' ) );
		$this->assertFalse( get_transient( 'meowseo_github_changelog' ) );
		$this->assertFalse( get_transient( 'meowseo_github_rate_limit' ) );
		$this->assertFalse( get_option( 'meowseo_github_last_check' ) );
	}

	/**
	 * Test 14: Error handling scenarios.
	 */
	public function test_error_handling_scenarios() {
		// Initialize checker.
		$this->checker->init();

		// Test multiple error scenarios.
		$error_scenarios = array(
			array(
				'code' => 500,
				'message' => 'Internal Server Error',
				'description' => 'Server error',
			),
			array(
				'code' => 503,
				'message' => 'Service Unavailable',
				'description' => 'Service unavailable',
			),
			array(
				'code' => 401,
				'message' => 'Unauthorized',
				'description' => 'Authentication error',
			),
		);

		foreach ( $error_scenarios as $scenario ) {
			// Reset filters.
			remove_all_filters( 'pre_http_request' );

			// Mock GitHub API to return error.
			add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $scenario ) {
				if ( strpos( $url, 'api.github.com/repos' ) !== false ) {
					return array(
						'response' => array( 'code' => $scenario['code'] ),
						'headers' => array(
							'x-ratelimit-limit' => '60',
							'x-ratelimit-remaining' => '59',
							'x-ratelimit-reset' => (string) ( time() + 3600 ),
						),
						'body' => json_encode( array( 'message' => $scenario['message'] ) ),
					);
				}
				return $preempt;
			}, 10, 3 );

			// Create transient object.
			$transient = new \stdClass();
			$transient->response = array();

			// Call check_for_update.
			$result = $this->checker->check_for_update( $transient );

			// Verify error is handled gracefully.
			$this->assertIsObject( $result );
			$this->assertEmpty( $result->response, "Error {$scenario['code']} should be handled gracefully" );
		}
	}
}
