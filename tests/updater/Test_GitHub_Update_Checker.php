<?php
/**
 * Tests for GitHub_Update_Checker class - Task 11 Checkpoint
 *
 * This test file verifies the core update functionality including:
 * - Update checker initialization
 * - GitHub API requests with mock responses
 * - Version comparison logic
 * - Cache storage and retrieval
 * - WordPress hooks registration
 * - Error handling for API failures
 *
 * @package MeowSEO
 * @subpackage Tests\Updater
 */

namespace MeowSEO\Tests\Updater;

use PHPUnit\Framework\TestCase;
use MeowSEO\Updater\GitHub_Update_Checker;
use MeowSEO\Updater\Update_Config;
use MeowSEO\Updater\Update_Logger;

// Mock WP_Error class if not exists.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $errors = array();
		
		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( ! empty( $code ) ) {
				$this->errors[ $code ] = array( $message );
			}
		}
		
		public function get_error_message() {
			if ( empty( $this->errors ) ) {
				return '';
			}
			$first_error = reset( $this->errors );
			return is_array( $first_error ) ? $first_error[0] : $first_error;
		}
	}
}

/**
 * Test GitHub_Update_Checker class.
 */
class Test_GitHub_Update_Checker extends TestCase {

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
	 * Plugin file path for testing.
	 *
	 * @var string
	 */
	private string $plugin_file;

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
		$this->plugin_file = MEOWSEO_FILE;
		$this->checker = new GitHub_Update_Checker( $this->plugin_file, $this->config, $this->logger );
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
	 * Test 1: Verify update checker initializes correctly.
	 */
	public function test_update_checker_initializes_correctly() {
		// Verify checker was constructed successfully.
		$this->assertInstanceOf( GitHub_Update_Checker::class, $this->checker );

		// Verify hooks are not registered yet (init() not called).
		$this->assertFalse( has_filter( 'pre_set_site_transient_update_plugins' ) );
		$this->assertFalse( has_filter( 'plugins_api' ) );
		$this->assertFalse( has_filter( 'upgrader_pre_download' ) );

		// Call init() to register hooks.
		$this->checker->init();

		// Verify hooks are now registered.
		$this->assertNotFalse( has_filter( 'pre_set_site_transient_update_plugins' ) );
		$this->assertNotFalse( has_filter( 'plugins_api' ) );
		$this->assertNotFalse( has_filter( 'upgrader_pre_download' ) );
	}

	/**
	 * Test 2: Test GitHub API requests with mock responses - Success case.
	 */
	public function test_github_api_request_success() {
		// Mock wp_remote_get to return a successful response.
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com' ) !== false ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers' => array(
						'x-ratelimit-limit' => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset' => (string) ( time() + 3600 ),
					),
					'body' => json_encode( array(
						'sha' => 'abc1234567890abcdef',
						'commit' => array(
							'message' => 'Test commit',
							'author' => array(
								'name' => 'Test Author',
								'date' => '2025-01-15T10:00:00Z',
							),
						),
						'html_url' => 'https://github.com/test/test/commit/abc1234567890abcdef',
					) ),
				);
			}
			return $preempt;
		}, 10, 3 );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		// Call the method.
		$result = $method->invoke( $this->checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Verify result is an array with expected data.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'sha', $result );
		$this->assertEquals( 'abc1234567890abcdef', $result['sha'] );
	}

	/**
	 * Test 3: Test GitHub API requests with mock responses - 404 error.
	 */
	public function test_github_api_request_404_error() {
		// Mock wp_remote_get to return a 404 response.
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com' ) !== false ) {
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

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		// Call the method.
		$result = $method->invoke( $this->checker, '/repos/invalid/nonexistent/commits/main' );

		// Result should be null for failed API requests.
		$this->assertNull( $result );
	}

	/**
	 * Test 4: Test GitHub API requests with mock responses - 403 rate limit error.
	 */
	public function test_github_api_request_rate_limit_error() {
		// Mock wp_remote_get to return a 403 response (rate limit).
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com' ) !== false ) {
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

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		// Call the method.
		$result = $method->invoke( $this->checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Verify result is null (error).
		$this->assertNull( $result );

		// Verify rate limit info was logged.
		$logs = $this->logger->get_recent_logs( 2 );
		$api_log = null;
		foreach ( $logs as $log ) {
			if ( $log['type'] === 'api_request' ) {
				$api_log = $log;
				break;
			}
		}
		
		$this->assertNotNull( $api_log );
		$this->assertEquals( 403, $api_log['context']['response_code'] );
		$this->assertEquals( 0, $api_log['context']['rate_limit']['remaining'] );
	}

	/**
	 * Test 5: Test GitHub API requests with mock responses - 500 server error.
	 */
	public function test_github_api_request_server_error() {
		// Mock wp_remote_get to return a 500 response.
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com' ) !== false ) {
				return array(
					'response' => array( 'code' => 500 ),
					'headers' => array(
						'x-ratelimit-limit' => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset' => (string) ( time() + 3600 ),
					),
					'body' => json_encode( array( 'message' => 'Internal Server Error' ) ),
				);
			}
			return $preempt;
		}, 10, 3 );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		// Call the method.
		$result = $method->invoke( $this->checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Verify result is null (error).
		$this->assertNull( $result );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 2 );
		$error_log = null;
		foreach ( $logs as $log ) {
			if ( $log['level'] === 'error' ) {
				$error_log = $log;
				break;
			}
		}
		
		$this->assertNotNull( $error_log );
	}

	/**
	 * Test 6: Test version comparison logic - Update available.
	 */
	public function test_version_comparison_update_available() {
		// Use reflection to access private methods.
		$reflection = new \ReflectionClass( $this->checker );
		
		$extract_method = $reflection->getMethod( 'extract_commit_id' );
		$extract_method->setAccessible( true );
		
		$compare_method = $reflection->getMethod( 'is_update_available' );
		$compare_method->setAccessible( true );

		// Test extracting commit ID from version string.
		$version = '1.0.0-abc1234';
		$commit_id = $extract_method->invoke( $this->checker, $version );
		$this->assertEquals( 'abc1234', $commit_id );

		// Test version comparison - update available.
		$current = 'abc1234';
		$latest = 'def5678';
		$update_available = $compare_method->invoke( $this->checker, $current, $latest );
		$this->assertTrue( $update_available );
	}

	/**
	 * Test 7: Test version comparison logic - No update available.
	 */
	public function test_version_comparison_no_update() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->checker );
		$method = $reflection->getMethod( 'is_update_available' );
		$method->setAccessible( true );

		// Test version comparison - same commit ID.
		$current = 'abc1234';
		$latest = 'abc1234';
		$update_available = $method->invoke( $this->checker, $current, $latest );
		$this->assertFalse( $update_available );
	}

	/**
	 * Test 8: Test version comparison logic - Empty commit IDs.
	 */
	public function test_version_comparison_empty_commits() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->checker );
		$method = $reflection->getMethod( 'is_update_available' );
		$method->setAccessible( true );

		// Test with empty current commit.
		$update_available = $method->invoke( $this->checker, '', 'def5678' );
		$this->assertFalse( $update_available );

		// Test with empty latest commit.
		$update_available = $method->invoke( $this->checker, 'abc1234', '' );
		$this->assertFalse( $update_available );

		// Test with both empty.
		$update_available = $method->invoke( $this->checker, '', '' );
		$this->assertFalse( $update_available );
	}

	/**
	 * Test 9: Test cache storage and retrieval.
	 */
	public function test_cache_storage_and_retrieval() {
		// Use reflection to access private methods.
		$reflection = new \ReflectionClass( $this->checker );
		
		$set_cache_method = $reflection->getMethod( 'set_cache' );
		$set_cache_method->setAccessible( true );
		
		$get_cache_method = $reflection->getMethod( 'get_cache' );
		$get_cache_method->setAccessible( true );

		// Test setting cache.
		$cache_key = 'meowseo_github_update_info';
		$cache_data = array(
			'sha' => 'abc1234',
			'message' => 'Test commit',
		);
		
		$set_cache_method->invoke( $this->checker, $cache_key, $cache_data, 3600 );

		// Test retrieving cache.
		$retrieved = $get_cache_method->invoke( $this->checker, $cache_key );
		$this->assertEquals( $cache_data, $retrieved );

		// Test retrieving non-existent cache.
		$non_existent = $get_cache_method->invoke( $this->checker, 'non_existent_key' );
		$this->assertFalse( $non_existent );
	}

	/**
	 * Test 10: Test cache expiration.
	 */
	public function test_cache_expiration() {
		// Use reflection to access private methods.
		$reflection = new \ReflectionClass( $this->checker );
		
		$set_cache_method = $reflection->getMethod( 'set_cache' );
		$set_cache_method->setAccessible( true );
		
		$get_cache_method = $reflection->getMethod( 'get_cache' );
		$get_cache_method->setAccessible( true );

		// Set cache with 1 second expiration.
		$cache_key = 'test_expiration';
		$cache_data = array( 'test' => 'data' );
		
		$set_cache_method->invoke( $this->checker, $cache_key, $cache_data, 1 );

		// Verify cache exists immediately.
		$retrieved = $get_cache_method->invoke( $this->checker, $cache_key );
		$this->assertEquals( $cache_data, $retrieved );

		// Wait for cache to expire.
		sleep( 2 );

		// Verify cache is now expired.
		$expired = $get_cache_method->invoke( $this->checker, $cache_key );
		$this->assertFalse( $expired );
	}

	/**
	 * Test 11: Test clear_cache method.
	 */
	public function test_clear_cache() {
		// Set some cache data.
		set_transient( 'meowseo_github_update_info', array( 'test' => 'data' ), 3600 );
		set_transient( 'meowseo_github_changelog', array( 'commits' => array() ), 3600 );
		set_transient( 'meowseo_github_rate_limit', array( 'remaining' => 60 ), 3600 );
		update_option( 'meowseo_github_last_check', time() );

		// Verify cache exists.
		$this->assertNotFalse( get_transient( 'meowseo_github_update_info' ) );
		$this->assertNotFalse( get_transient( 'meowseo_github_changelog' ) );
		$this->assertNotFalse( get_transient( 'meowseo_github_rate_limit' ) );
		$this->assertNotFalse( get_option( 'meowseo_github_last_check' ) );

		// Clear cache.
		$this->checker->clear_cache();

		// Verify cache is cleared.
		$this->assertFalse( get_transient( 'meowseo_github_update_info' ) );
		$this->assertFalse( get_transient( 'meowseo_github_changelog' ) );
		$this->assertFalse( get_transient( 'meowseo_github_rate_limit' ) );
		$this->assertFalse( get_option( 'meowseo_github_last_check' ) );

		// Verify cache clear was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'check', $logs[0]['type'] );
		// The message is "Update check completed successfully" and the context contains the error message "Update caches cleared"
		$this->assertStringContainsString( 'cleared', strtolower( $logs[0]['context']['error'] ?? '' ) );
	}

	/**
	 * Test 12: Verify WordPress hooks are registered correctly.
	 */
	public function test_wordpress_hooks_registration() {
		// Initialize the checker.
		$this->checker->init();

		// Verify pre_set_site_transient_update_plugins hook.
		$this->assertNotFalse( has_filter( 'pre_set_site_transient_update_plugins' ) );
		
		// Verify plugins_api hook.
		$this->assertNotFalse( has_filter( 'plugins_api' ) );
		
		// Verify upgrader_pre_download hook.
		$this->assertNotFalse( has_filter( 'upgrader_pre_download' ) );
	}

	/**
	 * Test 13: Test check_for_update with empty transient.
	 */
	public function test_check_for_update_empty_transient() {
		$this->checker->init();

		// Test with null transient.
		$result = $this->checker->check_for_update( null );
		$this->assertNull( $result );

		// Test with empty string.
		$result = $this->checker->check_for_update( '' );
		$this->assertEquals( '', $result );

		// Test with non-object.
		$result = $this->checker->check_for_update( array() );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test 14: Test check_for_update respects check frequency.
	 */
	public function test_check_for_update_respects_frequency() {
		$this->checker->init();

		// Set last check to recent time.
		update_option( 'meowseo_github_last_check', time() );

		// Set cache to simulate recent check.
		set_transient( 'meowseo_github_update_info', array( 'sha' => 'abc1234' ), 3600 );

		// Create transient object.
		$transient = new \stdClass();
		$transient->response = array();

		// Call check_for_update.
		$result = $this->checker->check_for_update( $transient );

		// Verify transient is returned unmodified (no new check performed).
		$this->assertEquals( $transient, $result );
	}

	/**
	 * Test 15: Test extract_commit_id with various version formats.
	 */
	public function test_extract_commit_id_various_formats() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->checker );
		$method = $reflection->getMethod( 'extract_commit_id' );
		$method->setAccessible( true );

		// Valid formats.
		$this->assertEquals( 'abc1234', $method->invoke( $this->checker, '1.0.0-abc1234' ) );
		$this->assertEquals( 'def5678', $method->invoke( $this->checker, '2.1.5-def5678' ) );
		$this->assertEquals( 'abc1234567890abcdef', $method->invoke( $this->checker, '1.0.0-abc1234567890abcdef' ) );

		// Invalid formats (should return null).
		$this->assertNull( $method->invoke( $this->checker, '1.0.0' ) );
		$this->assertNull( $method->invoke( $this->checker, '1.0.0-' ) );
		$this->assertNull( $method->invoke( $this->checker, '1.0.0-xyz' ) ); // Too short.
		$this->assertNull( $method->invoke( $this->checker, 'abc1234' ) ); // No version prefix.
		$this->assertNull( $method->invoke( $this->checker, '' ) );
	}

	/**
	 * Test 16: Test get_plugin_info returns correct structure.
	 */
	public function test_get_plugin_info_structure() {
		$this->checker->init();

		// Mock get_plugin_data function.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			function get_plugin_data( $plugin_file, $markup = true, $translate = true ) {
				return array(
					'Name' => 'MeowSEO',
					'Version' => '1.0.0-abc1234',
					'Author' => 'Test Author',
				);
			}
		}

		// Create args object.
		$args = new \stdClass();
		$args->slug = 'meowseo';

		// Call get_plugin_info.
		$result = $this->checker->get_plugin_info( false, 'plugin_information', $args );

		// Verify result structure.
		$this->assertIsObject( $result );
		$this->assertTrue( property_exists( $result, 'name' ), 'Result should have name property' );
		$this->assertTrue( property_exists( $result, 'slug' ), 'Result should have slug property' );
		$this->assertTrue( property_exists( $result, 'version' ), 'Result should have version property' );
		$this->assertTrue( property_exists( $result, 'sections' ), 'Result should have sections property' );
		$this->assertIsArray( $result->sections );
		$this->assertArrayHasKey( 'changelog', $result->sections );
	}

	/**
	 * Test 17: Test get_plugin_info returns false for wrong slug.
	 */
	public function test_get_plugin_info_wrong_slug() {
		$this->checker->init();

		// Create args object with wrong slug.
		$args = new \stdClass();
		$args->slug = 'different-plugin';

		// Call get_plugin_info.
		$result = $this->checker->get_plugin_info( false, 'plugin_information', $args );

		// Verify result is false (not our plugin).
		$this->assertFalse( $result );
	}

	/**
	 * Test 18: Test modify_package_url with valid GitHub URL.
	 */
	public function test_modify_package_url_valid() {
		$this->checker->init();

		// Test package URL.
		$package = 'https://github.com/akbarbahaulloh/meowseo/archive/abc1234.zip';

		// Call modify_package_url.
		$result = $this->checker->modify_package_url( false, $package, new \stdClass() );

		// Verify result is a valid GitHub archive URL.
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'github.com', $result );
		$this->assertStringContainsString( 'akbarbahaulloh', $result );
		$this->assertStringContainsString( 'meowseo', $result );
		$this->assertStringContainsString( '.zip', $result );
	}

	/**
	 * Test 19: Test modify_package_url with non-plugin URL.
	 */
	public function test_modify_package_url_non_plugin() {
		$this->checker->init();

		// Test package URL for different plugin.
		$package = 'https://downloads.wordpress.org/plugin/other-plugin.zip';

		// Call modify_package_url.
		$result = $this->checker->modify_package_url( false, $package, new \stdClass() );

		// Verify result is false (not modified).
		$this->assertFalse( $result );
	}

	/**
	 * Test 20: Test error handling - Network timeout simulation.
	 */
	public function test_error_handling_network_timeout() {
		// Mock wp_remote_get to return WP_Error.
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			if ( strpos( $url, 'api.github.com' ) !== false ) {
				return new \WP_Error( 'http_request_failed', 'Connection timed out' );
			}
			return $preempt;
		}, 10, 3 );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		// Call the method.
		$result = $method->invoke( $this->checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Verify result is null (error).
		$this->assertNull( $result );

		// Verify error was logged.
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
}
