<?php
/**
 * Property-Based Tests for Logger Nested Sanitization
 *
 * Property 30: Nested Sanitization
 * Validates: Requirements 17.3
 *
 * This test uses property-based testing (eris/eris) to verify that for any nested
 * context structure containing sensitive keys at any depth level, the Logger SHALL
 * sanitize all sensitive values regardless of nesting level.
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
 * Logger Nested Sanitization property-based test case
 *
 * @since 1.0.0
 */
class Property30NestedSanitizationTest extends TestCase {
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
	 * Recursively check if all sensitive keys are redacted in nested structure
	 *
	 * @param array $data The data structure to check
	 * @return bool True if all sensitive keys are redacted
	 */
	private function all_sensitive_keys_redacted( array $data ): bool {
		foreach ( $data as $key => $value ) {
			$lower_key = strtolower( $key );

			// Check if this is a sensitive key
			if ( in_array( $lower_key, [ 'token', 'password', 'secret', 'key' ], true ) ) {
				// If it's an array, check recursively
				if ( is_array( $value ) ) {
					if ( ! $this->all_sensitive_keys_redacted( $value ) ) {
						return false;
					}
				} else {
					// If it's not an array, it should be redacted
					if ( $value !== '[REDACTED]' ) {
						return false;
					}
				}
			} elseif ( is_array( $value ) ) {
				// Recursively check nested arrays
				if ( ! $this->all_sensitive_keys_redacted( $value ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Property 30: Nested Sanitization - One level deep nesting
	 *
	 * For any context data with sensitive keys nested one level deep,
	 * the Logger SHALL sanitize all sensitive values.
	 *
	 * **Validates: Requirements 17.3**
	 *
	 * @return void
	 */
	public function test_one_level_nested_sanitization(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 10, 50 )
		)
		->then(
			function ( string $token_value ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with nested sensitive data
				Logger::info( 'Test message', [
					'user' => [
						'token' => $token_value,
						'name' => 'John Doe',
					],
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify nested token is redacted
				$this->assertArrayHasKey(
					'user',
					$context,
					'Context should have user key'
				);

				$this->assertIsArray(
					$context['user'],
					'User should be an array'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['user']['token'],
					'Nested token should be redacted'
				);

				$this->assertEquals(
					'John Doe',
					$context['user']['name'],
					'Non-sensitive nested value should be preserved'
				);
			}
		);
	}

	/**
	 * Property 30: Nested Sanitization - Multiple levels deep nesting
	 *
	 * For any context data with sensitive keys nested multiple levels deep,
	 * the Logger SHALL sanitize all sensitive values at all depths.
	 *
	 * **Validates: Requirements 17.3**
	 *
	 * @return void
	 */
	public function test_multiple_levels_nested_sanitization(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 10, 30 ),
			Generators::string( 'a-zA-Z0-9', 10, 30 )
		)
		->then(
			function ( string $token, string $password ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with deeply nested sensitive data
				Logger::info( 'Test message', [
					'api' => [
						'credentials' => [
							'token' => $token,
							'password' => $password,
						],
						'endpoint' => 'https://api.example.com',
					],
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify deeply nested sensitive keys are redacted
				$this->assertEquals(
					'[REDACTED]',
					$context['api']['credentials']['token'],
					'Deeply nested token should be redacted'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['api']['credentials']['password'],
					'Deeply nested password should be redacted'
				);

				$this->assertEquals(
					'https://api.example.com',
					$context['api']['endpoint'],
					'Non-sensitive nested value should be preserved'
				);
			}
		);
	}

	/**
	 * Property 30: Nested Sanitization - Mixed sensitive and non-sensitive keys
	 *
	 * For any context data with mixed sensitive and non-sensitive keys at various
	 * nesting levels, the Logger SHALL sanitize only the sensitive keys.
	 *
	 * **Validates: Requirements 17.3**
	 *
	 * @return void
	 */
	public function test_mixed_sensitive_and_non_sensitive_nested(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 10, 30 ),
			Generators::string( 'a-zA-Z0-9', 10, 30 )
		)
		->then(
			function ( string $token, string $username ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with mixed sensitive and non-sensitive nested data
				Logger::info( 'Test message', [
					'user' => [
						'id' => 123,
						'username' => $username,
						'token' => $token,
						'profile' => [
							'email' => 'user@example.com',
							'secret' => 'my-secret-value',
						],
					],
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify sensitive keys are redacted
				$this->assertEquals(
					'[REDACTED]',
					$context['user']['token'],
					'Token should be redacted'
				);

				$this->assertEquals(
					'[REDACTED]',
					$context['user']['profile']['secret'],
					'Nested secret should be redacted'
				);

				// Verify non-sensitive keys are preserved
				$this->assertEquals(
					123,
					$context['user']['id'],
					'Non-sensitive ID should be preserved'
				);

				$this->assertEquals(
					$username,
					$context['user']['username'],
					'Non-sensitive username should be preserved'
				);

				$this->assertEquals(
					'user@example.com',
					$context['user']['profile']['email'],
					'Non-sensitive email should be preserved'
				);
			}
		);
	}

	/**
	 * Property 30: Nested Sanitization - Arrays of sensitive data
	 *
	 * For any context data containing arrays of sensitive data, the Logger SHALL
	 * sanitize all sensitive values in each array element.
	 *
	 * **Validates: Requirements 17.3**
	 *
	 * @return void
	 */
	public function test_arrays_of_sensitive_data(): void {
		$this->forAll(
			Generators::int( 1, 5 )
		)
		->then(
			function ( int $num_items ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create array of items with sensitive data
				$items = [];
				for ( $i = 0; $i < $num_items; $i++ ) {
					$items[] = [
						'id' => $i,
						'token' => "token_$i",
						'name' => "Item $i",
					];
				}

				// Log with array of sensitive data
				Logger::info( 'Test message', [ 'items' => $items ] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify all tokens in array are redacted
				$this->assertIsArray(
					$context['items'],
					'Items should be an array'
				);

				foreach ( $context['items'] as $item ) {
					$this->assertEquals(
						'[REDACTED]',
						$item['token'],
						'Token in array item should be redacted'
					);

					$this->assertStringContainsString(
						'Item',
						$item['name'],
						'Non-sensitive name should be preserved'
					);
				}
			}
		);
	}

	/**
	 * Property 30: Nested Sanitization - All sensitive keys redacted at all depths
	 *
	 * For any context data structure, all sensitive keys at all nesting depths
	 * SHALL be redacted.
	 *
	 * **Validates: Requirements 17.3**
	 *
	 * @return void
	 */
	public function test_all_sensitive_keys_redacted_at_all_depths(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 5, 20 )
		)
		->then(
			function ( string $random_value ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create complex nested structure with sensitive keys at various depths
				Logger::info( 'Test message', [
					'level1' => [
						'token' => $random_value,
						'level2' => [
							'password' => $random_value,
							'level3' => [
								'secret' => $random_value,
								'key' => $random_value,
							],
						],
					],
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
				$this->assertTrue(
					$this->all_sensitive_keys_redacted( $context ),
					'All sensitive keys at all depths should be redacted'
				);
			}
		);
	}
}
