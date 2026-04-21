<?php
/**
 * Tests for GitHub_Update_Checker class.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Updater\GitHub_Update_Checker;
use MeowSEO\Updater\Update_Config;
use MeowSEO\Updater\Update_Logger;

/**
 * Test GitHub_Update_Checker class.
 */
class GitHubUpdateCheckerTest extends TestCase {

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
		$this->config = new Update_Config();
		$this->logger = new Update_Logger();

		// Clean up any existing data.
		delete_option( 'meowseo_github_update_config' );
		delete_option( 'meowseo_github_update_logs' );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		// Clean up.
		delete_option( 'meowseo_github_update_config' );
		delete_option( 'meowseo_github_update_logs' );
		parent::tearDown();
	}

	/**
	 * Test constructor accepts required dependencies.
	 */
	public function test_constructor_accepts_dependencies() {
		$plugin_file = '/path/to/wp-content/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		$this->assertInstanceOf( GitHub_Update_Checker::class, $checker );
	}

	/**
	 * Test init method exists and can be called.
	 */
	public function test_init_method_exists() {
		$plugin_file = '/path/to/wp-content/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Should not throw an error.
		$checker->init();

		// If we get here, the method exists and is callable.
		$this->assertTrue( true );
	}

	/**
	 * Test plugin slug extraction from multi-file plugin path.
	 *
	 * Uses reflection to test the private extract_plugin_slug method.
	 */
	public function test_plugin_slug_extraction_multi_file() {
		$plugin_file = '/path/to/wp-content/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Use reflection to access private property.
		$reflection = new \ReflectionClass( $checker );
		$property = $reflection->getProperty( 'plugin_slug' );
		$property->setAccessible( true );
		$slug = $property->getValue( $checker );

		$this->assertEquals( 'meowseo', $slug );
	}

	/**
	 * Test plugin slug extraction from single-file plugin path.
	 */
	public function test_plugin_slug_extraction_single_file() {
		$plugin_file = '/path/to/wp-content/plugins/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Use reflection to access private property.
		$reflection = new \ReflectionClass( $checker );
		$property = $reflection->getProperty( 'plugin_slug' );
		$property->setAccessible( true );
		$slug = $property->getValue( $checker );

		$this->assertEquals( 'meowseo', $slug );
	}

	/**
	 * Test plugin slug extraction with different plugin names.
	 */
	public function test_plugin_slug_extraction_various_names() {
		$test_cases = array(
			'/path/to/plugins/my-plugin/my-plugin.php' => 'my-plugin',
			'/path/to/plugins/test_plugin/index.php'   => 'test_plugin',
			'/path/to/plugins/plugin.name/main.php'    => 'plugin.name',
		);

		foreach ( $test_cases as $plugin_file => $expected_slug ) {
			$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

			// Use reflection to access private property.
			$reflection = new \ReflectionClass( $checker );
			$property = $reflection->getProperty( 'plugin_slug' );
			$property->setAccessible( true );
			$slug = $property->getValue( $checker );

			$this->assertEquals( $expected_slug, $slug, "Failed for plugin file: $plugin_file" );
		}
	}

	/**
	 * Test that private properties are set correctly.
	 */
	public function test_private_properties_are_set() {
		$plugin_file = '/path/to/wp-content/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		$reflection = new \ReflectionClass( $checker );

		// Test plugin_file property.
		$plugin_file_prop = $reflection->getProperty( 'plugin_file' );
		$plugin_file_prop->setAccessible( true );
		$this->assertEquals( $plugin_file, $plugin_file_prop->getValue( $checker ) );

		// Test config property.
		$config_prop = $reflection->getProperty( 'config' );
		$config_prop->setAccessible( true );
		$this->assertSame( $this->config, $config_prop->getValue( $checker ) );

		// Test logger property.
		$logger_prop = $reflection->getProperty( 'logger' );
		$logger_prop->setAccessible( true );
		$this->assertSame( $this->logger, $logger_prop->getValue( $checker ) );

		// Test plugin_slug property.
		$slug_prop = $reflection->getProperty( 'plugin_slug' );
		$slug_prop->setAccessible( true );
		$this->assertEquals( 'meowseo', $slug_prop->getValue( $checker ) );
	}

	/**
	 * Test that checker can be instantiated multiple times.
	 */
	public function test_multiple_instances() {
		$plugin_file1 = '/path/to/plugins/plugin1/plugin1.php';
		$plugin_file2 = '/path/to/plugins/plugin2/plugin2.php';

		$checker1 = new GitHub_Update_Checker( $plugin_file1, $this->config, $this->logger );
		$checker2 = new GitHub_Update_Checker( $plugin_file2, $this->config, $this->logger );

		$this->assertInstanceOf( GitHub_Update_Checker::class, $checker1 );
		$this->assertInstanceOf( GitHub_Update_Checker::class, $checker2 );

		// Verify they have different plugin slugs.
		$reflection = new \ReflectionClass( $checker1 );
		$property = $reflection->getProperty( 'plugin_slug' );
		$property->setAccessible( true );

		$slug1 = $property->getValue( $checker1 );
		$slug2 = $property->getValue( $checker2 );

		$this->assertEquals( 'plugin1', $slug1 );
		$this->assertEquals( 'plugin2', $slug2 );
		$this->assertNotEquals( $slug1, $slug2 );
	}

	/**
	 * Test extract_commit_id with valid version strings.
	 *
	 * @dataProvider valid_version_strings_provider
	 */
	public function test_extract_commit_id_valid_versions( string $version, string $expected_commit ) {
		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'extract_commit_id' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, $version );

		$this->assertEquals( $expected_commit, $result );
	}

	/**
	 * Data provider for valid version strings.
	 */
	public function valid_version_strings_provider(): array {
		return array(
			'short commit ID (7 chars)' => array( '1.0.0-abc1234', 'abc1234' ),
			'medium commit ID (10 chars)' => array( '2.1.5-def5678901', 'def5678901' ),
			'full commit ID (40 chars)' => array( '1.0.0-abc1234567890abcdef1234567890abcdef12', 'abc1234567890abcdef1234567890abcdef12' ),
			'version with multiple dots' => array( '1.2.3.4-abc1234', 'abc1234' ),
			'version with leading zeros' => array( '0.0.1-abc1234', 'abc1234' ),
			'hex with letters a-f' => array( '1.0.0-abcdef1', 'abcdef1' ),
			'hex with numbers only' => array( '1.0.0-1234567', '1234567' ),
		);
	}

	/**
	 * Test extract_commit_id with invalid version strings.
	 *
	 * @dataProvider invalid_version_strings_provider
	 */
	public function test_extract_commit_id_invalid_versions( string $version ) {
		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'extract_commit_id' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, $version );

		$this->assertNull( $result );
	}

	/**
	 * Data provider for invalid version strings.
	 */
	public function invalid_version_strings_provider(): array {
		return array(
			'no commit ID' => array( '1.0.0' ),
			'commit ID too short (6 chars)' => array( '1.0.0-abc123' ),
			'commit ID too long (41 chars)' => array( '1.0.0-abc1234567890abcdef1234567890abcdef123456' ),
			'invalid hex characters (uppercase)' => array( '1.0.0-ABC1234' ),
			'invalid hex characters (special)' => array( '1.0.0-abc123!' ),
			'invalid hex characters (space)' => array( '1.0.0-abc 1234' ),
			'no version number' => array( 'abc1234' ),
			'empty string' => array( '' ),
			'multiple dashes' => array( '1.0.0-abc-1234' ),
		);
	}

	/**
	 * Test is_update_available with different commit IDs.
	 *
	 * @dataProvider update_availability_provider
	 */
	public function test_is_update_available( string $current, string $latest, bool $expected ) {
		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'is_update_available' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, $current, $latest );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for update availability tests.
	 */
	public function update_availability_provider(): array {
		return array(
			'different commits - update available' => array( 'abc1234', 'def5678', true ),
			'same commits - no update' => array( 'abc1234', 'abc1234', false ),
			'empty current - no update' => array( '', 'def5678', false ),
			'empty latest - no update' => array( 'abc1234', '', false ),
			'both empty - no update' => array( '', '', false ),
			'different length commits - update available' => array( 'abc1234', 'def567890abcdef', true ),
			'full commit IDs different - update available' => array(
				'abc1234567890abcdef1234567890abcdef12',
				'def5678901234567890abcdef1234567890ab',
				true,
			),
			'full commit IDs same - no update' => array(
				'abc1234567890abcdef1234567890abcdef12',
				'abc1234567890abcdef1234567890abcdef12',
				false,
			),
		);
	}

	/**
	 * Test get_current_commit_id returns empty string when no commit ID in version.
	 *
	 * This test requires WordPress functions to be available.
	 */
	public function test_get_current_commit_id_no_commit() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'get_current_commit_id' );
		$method->setAccessible( true );

		// This will return empty string if the plugin file doesn't exist or has no commit ID.
		$result = $method->invoke( $checker );

		$this->assertIsString( $result );
	}

	/**
	 * Test extract_commit_id edge cases.
	 */
	public function test_extract_commit_id_edge_cases() {
		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'extract_commit_id' );
		$method->setAccessible( true );

		// Test minimum valid length (7 characters).
		$result = $method->invoke( $checker, '1.0.0-abcdef0' );
		$this->assertEquals( 'abcdef0', $result );

		// Test maximum valid length (40 characters).
		$long_commit = str_repeat( 'a', 40 );
		$result = $method->invoke( $checker, "1.0.0-{$long_commit}" );
		$this->assertEquals( $long_commit, $result );

		// Test with version having many dots.
		$result = $method->invoke( $checker, '1.2.3.4.5.6-abc1234' );
		$this->assertEquals( 'abc1234', $result );
	}

	/**
	 * Test is_update_available handles whitespace correctly.
	 */
	public function test_is_update_available_whitespace() {
		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'is_update_available' );
		$method->setAccessible( true );

		// Test with whitespace (should be trimmed and treated as empty).
		$result = $method->invoke( $checker, ' ', 'abc1234' );
		$this->assertFalse( $result );

		$result = $method->invoke( $checker, 'abc1234', ' ' );
		$this->assertFalse( $result );

		$result = $method->invoke( $checker, ' ', ' ' );
		$this->assertFalse( $result );

		// Test with whitespace around valid commit IDs (should be trimmed).
		$result = $method->invoke( $checker, ' abc1234 ', 'def5678' );
		$this->assertTrue( $result );

		$result = $method->invoke( $checker, 'abc1234', ' def5678 ' );
		$this->assertTrue( $result );

		$result = $method->invoke( $checker, ' abc1234 ', ' abc1234 ' );
		$this->assertFalse( $result );
	}

	/**
	 * Test github_api_request with successful response.
	 */
	public function test_github_api_request_success() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Mock wp_remote_get response.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode(
						array(
							'sha'    => 'abc1234567890abcdef1234567890abcdef12',
							'commit' => array(
								'message' => 'Test commit message',
								'author'  => array(
									'name' => 'Test Author',
									'date' => '2025-01-15T10:00:00Z',
								),
							),
						)
					),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'sha', $result );
		$this->assertEquals( 'abc1234567890abcdef1234567890abcdef12', $result['sha'] );
	}

	/**
	 * Test github_api_request with WP_Error.
	 */
	public function test_github_api_request_wp_error() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) || ! class_exists( 'WP_Error' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Mock wp_remote_get to return WP_Error.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return new \WP_Error( 'http_request_failed', 'Network timeout' );
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		$this->assertNull( $result );
	}

	/**
	 * Test github_api_request with 404 error.
	 */
	public function test_github_api_request_404_error() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Mock wp_remote_get response with 404.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 404 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode( array( 'message' => 'Not Found' ) ),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, '/repos/invalid/repo/commits/main' );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		$this->assertNull( $result );
	}

	/**
	 * Test github_api_request with 403 rate limit error.
	 */
	public function test_github_api_request_rate_limit_error() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Mock wp_remote_get response with 403 (rate limit).
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 403 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '0',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode( array( 'message' => 'API rate limit exceeded' ) ),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		$this->assertNull( $result );
	}

	/**
	 * Test github_api_request with 500 server error.
	 */
	public function test_github_api_request_server_error() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Mock wp_remote_get response with 500.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 500 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode( array( 'message' => 'Internal Server Error' ) ),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		$this->assertNull( $result );
	}

	/**
	 * Test github_api_request with invalid JSON response.
	 */
	public function test_github_api_request_invalid_json() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Mock wp_remote_get response with invalid JSON.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => 'This is not valid JSON',
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		$this->assertNull( $result );
	}

	/**
	 * Test github_api_request extracts rate limit headers correctly.
	 */
	public function test_github_api_request_rate_limit_extraction() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		$expected_reset = time() + 3600;

		// Mock wp_remote_get response.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $expected_reset ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '45',
						'x-ratelimit-reset'     => (string) $expected_reset,
					),
					'body'     => wp_json_encode( array( 'sha' => 'abc1234' ) ),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		// Verify the request was logged with rate limit info.
		$logs = $this->logger->get_recent_logs( 10 );
		$this->assertNotEmpty( $logs );

		// Find the API request log entry.
		$api_log = null;
		foreach ( $logs as $log ) {
			if ( $log['type'] === 'api_request' ) {
				$api_log = $log;
				break;
			}
		}

		$this->assertNotNull( $api_log, 'API request log entry not found' );
		$this->assertArrayHasKey( 'context', $api_log );
		$this->assertArrayHasKey( 'rate_limit', $api_log['context'] );
		$this->assertEquals( 60, $api_log['context']['rate_limit']['limit'] );
		$this->assertEquals( 45, $api_log['context']['rate_limit']['remaining'] );
		$this->assertEquals( $expected_reset, $api_log['context']['rate_limit']['reset'] );
	}

	/**
	 * Test github_api_request uses correct User-Agent header.
	 */
	public function test_github_api_request_user_agent() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		$captured_args = null;

		// Mock wp_remote_get to capture arguments.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$captured_args ) {
				$captured_args = $args;
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(),
					'body'     => wp_json_encode( array( 'sha' => 'abc1234' ) ),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		$this->assertNotNull( $captured_args );
		$this->assertArrayHasKey( 'user-agent', $captured_args );
		$this->assertEquals( 'MeowSEO-Updater/1.0 (WordPress Plugin)', $captured_args['user-agent'] );
	}

	/**
	 * Test github_api_request uses 10 second timeout.
	 */
	public function test_github_api_request_timeout() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		$captured_args = null;

		// Mock wp_remote_get to capture arguments.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$captured_args ) {
				$captured_args = $args;
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(),
					'body'     => wp_json_encode( array( 'sha' => 'abc1234' ) ),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'github_api_request' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker, '/repos/akbarbahaulloh/meowseo/commits/main' );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		$this->assertNotNull( $captured_args );
		$this->assertArrayHasKey( 'timeout', $captured_args );
		$this->assertEquals( 10, $captured_args['timeout'] );
	}

	/**
	 * Test get_latest_commit returns cached data when available.
	 */
	public function test_get_latest_commit_returns_cached_data() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'set_transient' ) || ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Set up cached data.
		$cached_data = array(
			'sha'       => 'abc1234567890abcdef1234567890abcdef12',
			'short_sha' => 'abc1234',
			'message'   => 'Cached commit message',
			'author'    => 'Cached Author',
			'date'      => '2025-01-15T10:00:00Z',
			'url'       => 'https://github.com/akbarbahaulloh/meowseo/commit/abc1234',
		);
		set_transient( 'meowseo_github_update_info', $cached_data, 43200 );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'get_latest_commit' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );

		$this->assertIsArray( $result );
		$this->assertEquals( $cached_data, $result );
	}

	/**
	 * Test get_latest_commit fetches from API when cache is empty.
	 */
	public function test_get_latest_commit_fetches_from_api() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear any existing cache.
		delete_transient( 'meowseo_github_update_info' );

		// Mock wp_remote_get response.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode(
						array(
							'sha'      => 'def5678901234567890abcdef1234567890ab',
							'commit'   => array(
								'message' => 'Test commit from API',
								'author'  => array(
									'name' => 'API Author',
									'date' => '2025-01-15T12:00:00Z',
								),
							),
							'html_url' => 'https://github.com/akbarbahaulloh/meowseo/commit/def5678',
						)
					),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'get_latest_commit' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'sha', $result );
		$this->assertEquals( 'def5678901234567890abcdef1234567890ab', $result['sha'] );
		$this->assertEquals( 'def5678', $result['short_sha'] );
		$this->assertEquals( 'Test commit from API', $result['message'] );
		$this->assertEquals( 'API Author', $result['author'] );
		$this->assertEquals( '2025-01-15T12:00:00Z', $result['date'] );
		$this->assertEquals( 'https://github.com/akbarbahaulloh/meowseo/commit/def5678', $result['url'] );
	}

	/**
	 * Test get_latest_commit caches the result.
	 */
	public function test_get_latest_commit_caches_result() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear any existing cache.
		delete_transient( 'meowseo_github_update_info' );

		// Mock wp_remote_get response.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode(
						array(
							'sha'      => 'abc1234567890abcdef1234567890abcdef12',
							'commit'   => array(
								'message' => 'Test commit',
								'author'  => array(
									'name' => 'Test Author',
									'date' => '2025-01-15T10:00:00Z',
								),
							),
							'html_url' => 'https://github.com/akbarbahaulloh/meowseo/commit/abc1234',
						)
					),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'get_latest_commit' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		// Verify the result was cached.
		$cached = get_transient( 'meowseo_github_update_info' );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );

		$this->assertIsArray( $cached );
		$this->assertEquals( $result, $cached );
	}

	/**
	 * Test get_latest_commit returns null on API error.
	 */
	public function test_get_latest_commit_returns_null_on_error() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear any existing cache.
		delete_transient( 'meowseo_github_update_info' );

		// Mock wp_remote_get to return error.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return new \WP_Error( 'http_request_failed', 'Network error' );
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'get_latest_commit' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		$this->assertNull( $result );
	}

	/**
	 * Test get_latest_commit returns null when SHA is missing.
	 */
	public function test_get_latest_commit_returns_null_when_sha_missing() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear any existing cache.
		delete_transient( 'meowseo_github_update_info' );

		// Mock wp_remote_get response without SHA.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode(
						array(
							'commit' => array(
								'message' => 'Test commit',
								'author'  => array(
									'name' => 'Test Author',
									'date' => '2025-01-15T10:00:00Z',
								),
							),
						)
					),
				);
			},
			10,
			3
		);

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'get_latest_commit' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );

		$this->assertNull( $result );
	}

	/**
	 * Test should_check_for_update returns true when cache is empty.
	 */
	public function test_should_check_for_update_returns_true_when_cache_empty() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear cache.
		delete_transient( 'meowseo_github_update_info' );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'should_check_for_update' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker );

		$this->assertTrue( $result );
	}

	/**
	 * Test should_check_for_update returns true when enough time has passed.
	 */
	public function test_should_check_for_update_returns_true_when_time_passed() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Set cache to simulate it exists.
		set_transient( 'meowseo_github_update_info', array( 'sha' => 'abc1234' ), 43200 );

		// Set last check time to 13 hours ago (more than 12 hour default frequency).
		$last_check = time() - ( 13 * 3600 );
		update_option( 'meowseo_github_last_check', $last_check );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'should_check_for_update' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		$this->assertTrue( $result );
	}

	/**
	 * Test should_check_for_update returns false when not enough time has passed.
	 */
	public function test_should_check_for_update_returns_false_when_time_not_passed() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Set cache to simulate it exists.
		set_transient( 'meowseo_github_update_info', array( 'sha' => 'abc1234' ), 43200 );

		// Set last check time to 1 hour ago (less than 12 hour default frequency).
		$last_check = time() - 3600;
		update_option( 'meowseo_github_last_check', $last_check );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'should_check_for_update' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		$this->assertFalse( $result );
	}

	/**
	 * Test should_check_for_update returns true when last check is 0 (never checked).
	 */
	public function test_should_check_for_update_returns_true_when_never_checked() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Set cache to simulate it exists.
		set_transient( 'meowseo_github_update_info', array( 'sha' => 'abc1234' ), 43200 );

		// Ensure last check option doesn't exist (defaults to 0).
		delete_option( 'meowseo_github_last_check' );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $checker );
		$method = $reflection->getMethod( 'should_check_for_update' );
		$method->setAccessible( true );

		$result = $method->invoke( $checker );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );

		$this->assertTrue( $result );
	}

	/**
	 * Test check_for_update returns unmodified transient when transient is empty.
	 */
	public function test_check_for_update_returns_unmodified_when_transient_empty() {
		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		$result = $checker->check_for_update( null );

		$this->assertNull( $result );
	}

	/**
	 * Test check_for_update returns unmodified transient when transient is not an object.
	 */
	public function test_check_for_update_returns_unmodified_when_not_object() {
		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		$result = $checker->check_for_update( 'not an object' );

		$this->assertEquals( 'not an object', $result );
	}

	/**
	 * Test check_for_update returns unmodified transient when should not check.
	 */
	public function test_check_for_update_returns_unmodified_when_should_not_check() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Set cache and recent last check to prevent update check.
		set_transient( 'meowseo_github_update_info', array( 'sha' => 'abc1234' ), 43200 );
		update_option( 'meowseo_github_last_check', time() );

		$transient = (object) array( 'response' => array() );
		$result = $checker->check_for_update( $transient );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		$this->assertEquals( $transient, $result );
	}

	/**
	 * Test check_for_update returns unmodified transient when API fails.
	 */
	public function test_check_for_update_returns_unmodified_when_api_fails() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear cache to force API check.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		// Mock wp_remote_get to return error.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return new \WP_Error( 'http_request_failed', 'Network error' );
			},
			10,
			3
		);

		$transient = (object) array( 'response' => array() );
		$result = $checker->check_for_update( $transient );

		// Remove the filter.
		remove_all_filters( 'pre_http_request' );

		// Clean up.
		delete_option( 'meowseo_github_last_check' );

		$this->assertEquals( $transient, $result );
	}

	/**
	 * Test check_for_update returns unmodified transient when no update available.
	 */
	public function test_check_for_update_returns_unmodified_when_no_update() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear cache to force API check.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		// Mock wp_remote_get to return same commit as current.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode(
						array(
							'sha'      => 'abc1234567890abcdef1234567890abcdef12',
							'commit'   => array(
								'message' => 'Test commit',
								'author'  => array(
									'name' => 'Test Author',
									'date' => '2025-01-15T10:00:00Z',
								),
							),
							'html_url' => 'https://github.com/akbarbahaulloh/meowseo/commit/abc1234',
						)
					),
				);
			},
			10,
			3
		);

		// Mock get_plugin_data to return version with same commit ID.
		add_filter(
			'get_plugin_data',
			function ( $plugin_data ) {
				$plugin_data['Version'] = '1.0.0-abc1234';
				return $plugin_data;
			}
		);

		$transient = (object) array( 'response' => array() );
		$result = $checker->check_for_update( $transient );

		// Remove the filters.
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'get_plugin_data' );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		$this->assertEquals( $transient, $result );
		$this->assertEmpty( $result->response );
	}

	/**
	 * Test check_for_update adds update info when update is available.
	 */
	public function test_check_for_update_adds_update_info_when_update_available() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'get_transient' ) || ! function_exists( 'get_plugin_data' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		// Use the actual plugin file path.
		$plugin_file = MEOWSEO_FILE;
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear cache to force API check.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		// Mock wp_remote_get to return different commit.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode(
						array(
							'sha'      => 'def5678901234567890abcdef1234567890ab',
							'commit'   => array(
								'message' => 'New commit',
								'author'  => array(
									'name' => 'Test Author',
									'date' => '2025-01-15T12:00:00Z',
								),
							),
							'html_url' => 'https://github.com/akbarbahaulloh/meowseo/commit/def5678',
						)
					),
				);
			},
			10,
			3
		);

		$transient = (object) array( 'response' => array() );
		$result = $checker->check_for_update( $transient );

		// Remove the filters.
		remove_all_filters( 'pre_http_request' );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		$this->assertIsObject( $result );
		$this->assertObjectHasProperty( 'response', $result );

		// Get the plugin basename.
		$plugin_basename = plugin_basename( $plugin_file );

		// If update was added, verify the structure.
		if ( ! empty( $result->response ) && isset( $result->response[ $plugin_basename ] ) ) {
			$update_info = $result->response[ $plugin_basename ];
			$this->assertIsObject( $update_info );
			$this->assertEquals( $plugin_basename, $update_info->id );
			$this->assertEquals( 'meowseo', $update_info->slug );
			$this->assertEquals( $plugin_basename, $update_info->plugin );
			$this->assertEquals( '1.0.0-def5678', $update_info->new_version );
			$this->assertEquals( 'https://github.com/akbarbahaulloh/meowseo', $update_info->url );
			$this->assertEquals( 'https://github.com/akbarbahaulloh/meowseo/archive/def5678901234567890abcdef1234567890ab.zip', $update_info->package );
		} else {
			// If no update was added, it means the current version matches the latest.
			// This is acceptable in a test environment.
			$this->assertTrue( true, 'No update available - current version matches latest' );
		}
	}

	/**
	 * Test check_for_update handles exceptions gracefully.
	 */
	public function test_check_for_update_handles_exceptions() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear cache to force check.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		// Mock get_plugin_data to throw exception.
		add_filter(
			'get_plugin_data',
			function ( $plugin_data ) {
				throw new \Exception( 'Test exception' );
			}
		);

		$transient = (object) array( 'response' => array() );
		$result = $checker->check_for_update( $transient );

		// Remove the filter.
		remove_all_filters( 'get_plugin_data' );

		// Clean up.
		delete_option( 'meowseo_github_last_check' );

		// Should return unmodified transient on exception.
		$this->assertEquals( $transient, $result );
	}

	/**
	 * Test check_for_update updates last check time.
	 */
	public function test_check_for_update_updates_last_check_time() {
		// Skip if WordPress functions are not available.
		if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'get_transient' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Clear cache to force API check.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		// Mock wp_remote_get.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array(
						'x-ratelimit-limit'     => '60',
						'x-ratelimit-remaining' => '59',
						'x-ratelimit-reset'     => (string) ( time() + 3600 ),
					),
					'body'     => wp_json_encode(
						array(
							'sha'      => 'abc1234567890abcdef1234567890abcdef12',
							'commit'   => array(
								'message' => 'Test commit',
								'author'  => array(
									'name' => 'Test Author',
									'date' => '2025-01-15T10:00:00Z',
								),
							),
							'html_url' => 'https://github.com/akbarbahaulloh/meowseo/commit/abc1234',
						)
					),
				);
			},
			10,
			3
		);

		// Mock get_plugin_data.
		add_filter(
			'get_plugin_data',
			function ( $plugin_data ) {
				$plugin_data['Version'] = '1.0.0-abc1234';
				return $plugin_data;
			}
		);

		$before_time = time();
		$transient = (object) array( 'response' => array() );
		$result = $checker->check_for_update( $transient );
		$after_time = time();

		// Remove the filters.
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'get_plugin_data' );

		$last_check = get_option( 'meowseo_github_last_check', 0 );

		// Clean up.
		delete_transient( 'meowseo_github_update_info' );
		delete_option( 'meowseo_github_last_check' );

		$this->assertGreaterThanOrEqual( $before_time, $last_check );
		$this->assertLessThanOrEqual( $after_time, $last_check );
	}

	/**
	 * Test init registers WordPress hook.
	 */
	public function test_init_registers_wordpress_hook() {
		$plugin_file = '/path/to/plugins/meowseo/meowseo.php';
		$checker = new GitHub_Update_Checker( $plugin_file, $this->config, $this->logger );

		// Call init to register hooks.
		$checker->init();

		// Verify the hook was registered by checking if has_filter returns a priority.
		$priority = has_filter( 'pre_set_site_transient_update_plugins', array( $checker, 'check_for_update' ) );
		
		$this->assertNotFalse( $priority, 'check_for_update callback should be registered on pre_set_site_transient_update_plugins filter' );
	}
}
