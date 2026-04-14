<?php
/**
 * Property-Based Tests for Logger Token Sanitization
 *
 * Property 23: Token Sanitization
 * Validates: Requirements 11.5
 *
 * This test uses property-based testing (eris/eris) to verify that for any log entry
 * with an access token in the context, the token value SHALL be replaced with '[REDACTED]'
 * before storage. This is critical for the GSC module integration where OAuth tokens
 * must be protected from exposure in debug logs.
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
 * Logger Token Sanitization property-based test case
 *
 * @since 1.0.0
 */
class Property23TokenSanitizationTest extends TestCase {
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
	 * Property 23: Token Sanitization - Access tokens are redacted
	 *
	 * For any log entry with an access_token in the context, the Logger SHALL
	 * replace the token value with '[REDACTED]' before storage. This prevents
	 * OAuth credentials from being exposed in debug logs.
	 *
	 * **Validates: Requirements 11.5**
	 *
	 * @return void
	 */
	public function test_access_token_is_redacted(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-', 20, 100 )
		)
		->then(
			function ( string $access_token ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with an access_token in context (typical GSC module scenario)
				Logger::error( 'OAuth authentication failed', [
					'job_type' => 'fetch_url',
					'access_token' => $access_token,
					'error_code' => 'invalid_grant',
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the access_token is redacted
				$this->assertArrayHasKey(
					'access_token',
					$context,
					'Context should have access_token key'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['access_token'],
					'Access token value should be redacted'
				);

				// Verify non-sensitive fields are preserved
				$this->assertEquals(
					'fetch_url',
					$context['job_type'],
					'Non-sensitive job_type should be preserved'
				);

				$this->assertEquals(
					'invalid_grant',
					$context['error_code'],
					'Non-sensitive error_code should be preserved'
				);
			}
		);
	}

	/**
	 * Property 23: Token Sanitization - Refresh tokens are redacted
	 *
	 * For any log entry with a refresh_token in the context, the Logger SHALL
	 * replace the token value with '[REDACTED]' before storage.
	 *
	 * **Validates: Requirements 11.5**
	 *
	 * @return void
	 */
	public function test_refresh_token_is_redacted(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-', 20, 100 )
		)
		->then(
			function ( string $refresh_token ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with a refresh_token in context
				Logger::info( 'Token refresh completed', [
					'job_type' => 'token_refresh',
					'refresh_token' => $refresh_token,
					'expires_in' => 3600,
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the refresh_token is redacted
				$this->assertArrayHasKey(
					'refresh_token',
					$context,
					'Context should have refresh_token key'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['refresh_token'],
					'Refresh token value should be redacted'
				);

				// Verify non-sensitive fields are preserved
				$this->assertEquals(
					'token_refresh',
					$context['job_type'],
					'Non-sensitive job_type should be preserved'
				);

				$this->assertEquals(
					3600,
					$context['expires_in'],
					'Non-sensitive expires_in should be preserved'
				);
			}
		);
	}

	/**
	 * Property 23: Token Sanitization - Multiple tokens are redacted
	 *
	 * For any log entry with multiple token types in the context, the Logger
	 * SHALL redact all of them.
	 *
	 * **Validates: Requirements 11.5**
	 *
	 * @return void
	 */
	public function test_multiple_tokens_are_redacted(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-', 20, 80 ),
			Generators::string( 'a-zA-Z0-9_-', 20, 80 )
		)
		->then(
			function ( string $access_token, string $refresh_token ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with multiple tokens
				Logger::error( 'OAuth sync failed', [
					'job_type' => 'sync_data',
					'access_token' => $access_token,
					'refresh_token' => $refresh_token,
					'error_message' => 'Rate limit exceeded',
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify both tokens are redacted
				$this->assertEquals(
					'[REDACTED]',
					$context['access_token'],
					'Access token should be redacted'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['refresh_token'],
					'Refresh token should be redacted'
				);

				// Verify non-sensitive fields are preserved
				$this->assertEquals(
					'sync_data',
					$context['job_type'],
					'Non-sensitive job_type should be preserved'
				);

				$this->assertEquals(
					'Rate limit exceeded',
					$context['error_message'],
					'Non-sensitive error_message should be preserved'
				);
			}
		);
	}

	/**
	 * Property 23: Token Sanitization - Tokens with various formats are redacted
	 *
	 * For any log entry with tokens in various formats (JWT, OAuth2, etc.),
	 * the Logger SHALL redact them regardless of format.
	 *
	 * **Validates: Requirements 11.5**
	 *
	 * @return void
	 */
	public function test_tokens_with_various_formats_are_redacted(): void {
		$this->forAll(
			Generators::choose( 0, 2 )
		)
		->then(
			function ( int $token_type ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Generate different token formats
				$tokens = [
					// JWT format
					'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
					// OAuth2 format
					'ya29.a0AfH6SMBx_1234567890abcdefghijklmnopqrstuvwxyz',
					// Simple alphanumeric
					'abc123def456ghi789jkl012mno345pqr678stu901vwx',
				];

				$token = $tokens[ $token_type ];

				// Log with a token in various formats
				Logger::warning( 'Token validation', [
					'access_token' => $token,
					'token_type' => 'Bearer',
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the token is redacted regardless of format
				$this->assertEquals(
					'[REDACTED]',
					$context['access_token'],
					'Token should be redacted regardless of format'
				);

				// Verify non-sensitive fields are preserved
				$this->assertEquals(
					'Bearer',
					$context['token_type'],
					'Non-sensitive token_type should be preserved'
				);
			}
		);
	}

	/**
	 * Property 23: Token Sanitization - Tokens in nested context are redacted
	 *
	 * For any log entry with tokens nested in context arrays, the Logger
	 * SHALL redact them at any nesting level.
	 *
	 * **Validates: Requirements 11.5**
	 *
	 * @return void
	 */
	public function test_tokens_in_nested_context_are_redacted(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-', 20, 80 )
		)
		->then(
			function ( string $access_token ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with nested token in context
				Logger::error( 'API request failed', [
					'job_type' => 'fetch_data',
					'oauth' => [
						'access_token' => $access_token,
						'scope' => 'https://www.googleapis.com/auth/webmasters',
					],
					'retry_count' => 3,
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the nested token is redacted
				$this->assertArrayHasKey(
					'oauth',
					$context,
					'Context should have oauth key'
				);

				$this->assertIsArray(
					$context['oauth'],
					'oauth should be an array'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['oauth']['access_token'],
					'Nested access token should be redacted'
				);

				// Verify non-sensitive nested fields are preserved
				$this->assertEquals(
					'https://www.googleapis.com/auth/webmasters',
					$context['oauth']['scope'],
					'Non-sensitive scope should be preserved'
				);

				$this->assertEquals(
					3,
					$context['retry_count'],
					'Non-sensitive retry_count should be preserved'
				);
			}
		);
	}
}
