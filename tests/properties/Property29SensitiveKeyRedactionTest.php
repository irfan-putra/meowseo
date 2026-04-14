<?php
/**
 * Property-Based Tests for Logger Sensitive Key Redaction
 *
 * Property 29: Sensitive Key Redaction
 * Validates: Requirements 17.1, 17.2
 *
 * This test uses property-based testing (eris/eris) to verify that for any context data
 * containing keys matching sensitive patterns (token, key, password, secret), the Logger
 * SHALL replace the values with '[REDACTED]'.
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
 * Logger Sensitive Key Redaction property-based test case
 *
 * @since 1.0.0
 */
class Property29SensitiveKeyRedactionTest extends TestCase {
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
	 * Property 29: Sensitive Key Redaction - Token keys are redacted
	 *
	 * For any context data containing a 'token' key, the Logger SHALL replace
	 * the value with '[REDACTED]'.
	 *
	 * **Validates: Requirements 17.1, 17.2**
	 *
	 * @return void
	 */
	public function test_token_keys_are_redacted(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 10, 50 )
		)
		->then(
			function ( string $token_value ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with a token in context
				Logger::info( 'Test message', [ 'token' => $token_value ] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the token is redacted
				$this->assertArrayHasKey(
					'token',
					$context,
					'Context should have token key'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['token'],
					'Token value should be redacted'
				);
			}
		);
	}

	/**
	 * Property 29: Sensitive Key Redaction - API key keys are redacted
	 *
	 * For any context data containing an 'api_key' key, the Logger SHALL replace
	 * the value with '[REDACTED]'.
	 *
	 * **Validates: Requirements 17.1, 17.2**
	 *
	 * @return void
	 */
	public function test_api_key_keys_are_redacted(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 10, 50 )
		)
		->then(
			function ( string $api_key_value ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with an api_key in context
				Logger::info( 'Test message', [ 'api_key' => $api_key_value ] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the api_key is redacted
				$this->assertArrayHasKey(
					'api_key',
					$context,
					'Context should have api_key key'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['api_key'],
					'API key value should be redacted'
				);
			}
		);
	}

	/**
	 * Property 29: Sensitive Key Redaction - Password keys are redacted
	 *
	 * For any context data containing a 'password' key, the Logger SHALL replace
	 * the value with '[REDACTED]'.
	 *
	 * **Validates: Requirements 17.1, 17.2**
	 *
	 * @return void
	 */
	public function test_password_keys_are_redacted(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 10, 50 )
		)
		->then(
			function ( string $password_value ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with a password in context
				Logger::info( 'Test message', [ 'password' => $password_value ] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the password is redacted
				$this->assertArrayHasKey(
					'password',
					$context,
					'Context should have password key'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['password'],
					'Password value should be redacted'
				);
			}
		);
	}

	/**
	 * Property 29: Sensitive Key Redaction - Secret keys are redacted
	 *
	 * For any context data containing a 'secret' key, the Logger SHALL replace
	 * the value with '[REDACTED]'.
	 *
	 * **Validates: Requirements 17.1, 17.2**
	 *
	 * @return void
	 */
	public function test_secret_keys_are_redacted(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 10, 50 )
		)
		->then(
			function ( string $secret_value ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with a secret in context
				Logger::info( 'Test message', [ 'secret' => $secret_value ] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the secret is redacted
				$this->assertArrayHasKey(
					'secret',
					$context,
					'Context should have secret key'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['secret'],
					'Secret value should be redacted'
				);
			}
		);
	}

	/**
	 * Property 29: Sensitive Key Redaction - Multiple sensitive keys are redacted
	 *
	 * For any context data containing multiple sensitive keys, the Logger SHALL
	 * redact all of them.
	 *
	 * **Validates: Requirements 17.1, 17.2**
	 *
	 * @return void
	 */
	public function test_multiple_sensitive_keys_are_redacted(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 10, 30 ),
			Generators::string( 'a-zA-Z0-9', 10, 30 ),
			Generators::string( 'a-zA-Z0-9', 10, 30 )
		)
		->then(
			function ( string $token, string $password, string $secret ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with multiple sensitive keys
				Logger::info( 'Test message', [
					'token' => $token,
					'password' => $password,
					'secret' => $secret,
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify all sensitive keys are redacted
				$this->assertEquals(
					'[REDACTED]',
					$context['token'],
					'Token should be redacted'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['password'],
					'Password should be redacted'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['secret'],
					'Secret should be redacted'
				);
			}
		);
	}

	/**
	 * Property 29: Sensitive Key Redaction - Case-insensitive matching
	 *
	 * For any context data containing sensitive keys in different cases,
	 * the Logger SHALL redact them.
	 *
	 * **Validates: Requirements 17.1, 17.2**
	 *
	 * @return void
	 */
	public function test_case_insensitive_sensitive_key_matching(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 10, 50 )
		)
		->then(
			function ( string $value ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with case variations of sensitive keys
				Logger::info( 'Test message', [
					'TOKEN' => $value,
					'Password' => $value,
					'SECRET' => $value,
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify case-insensitive matching works
				// (The implementation should handle case-insensitive matching)
				foreach ( $context as $key => $val ) {
					$lower_key = strtolower( $key );
					if ( in_array( $lower_key, [ 'token', 'password', 'secret', 'key' ], true ) ) {
						$this->assertEquals(
							'[REDACTED]',
							$val,
							"Sensitive key '$key' should be redacted"
						);
					}
				}
			}
		);
	}
}
