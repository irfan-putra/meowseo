<?php
/**
 * Property-Based Tests for Logger Non-Sensitive Preservation
 *
 * Property 31: Non-Sensitive Preservation
 * Validates: Requirements 17.4
 *
 * This test uses property-based testing (eris/eris) to verify that for any context
 * data containing non-sensitive keys, the Logger SHALL preserve the original values
 * unchanged after sanitization.
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
 * Logger Non-Sensitive Preservation property-based test case
 *
 * @since 1.0.0
 */
class Property31NonSensitivePreservationTest extends TestCase {
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
	 * Property 31: Non-Sensitive Preservation - String values preserved
	 *
	 * For any context data containing non-sensitive string keys, the Logger SHALL
	 * preserve the original string values unchanged.
	 *
	 * **Validates: Requirements 17.4**
	 *
	 * @return void
	 */
	public function test_string_values_preserved(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 100 )
		)
		->then(
			function ( string $value ) {
				// Skip empty strings
				if ( empty( trim( $value ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with non-sensitive string data
				Logger::info( 'Test message', [ 'username' => $value ] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the string value is preserved
				$this->assertEquals(
					$value,
					$context['username'],
					'Non-sensitive string value should be preserved'
				);
			}
		);
	}

	/**
	 * Property 31: Non-Sensitive Preservation - Numeric values preserved
	 *
	 * For any context data containing non-sensitive numeric keys, the Logger SHALL
	 * preserve the original numeric values unchanged.
	 *
	 * **Validates: Requirements 17.4**
	 *
	 * @return void
	 */
	public function test_numeric_values_preserved(): void {
		$this->forAll(
			Generators::integers( -1000000, 1000000 )
		)
		->then(
			function ( int $value ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with non-sensitive numeric data
				Logger::info( 'Test message', [ 'user_id' => $value ] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the numeric value is preserved
				$this->assertEquals(
					$value,
					$context['user_id'],
					'Non-sensitive numeric value should be preserved'
				);
			}
		);
	}

	/**
	 * Property 31: Non-Sensitive Preservation - Boolean values preserved
	 *
	 * For any context data containing non-sensitive boolean keys, the Logger SHALL
	 * preserve the original boolean values unchanged.
	 *
	 * **Validates: Requirements 17.4**
	 *
	 * @return void
	 */
	public function test_boolean_values_preserved(): void {
		$this->forAll(
			Generators::booleans()
		)
		->then(
			function ( bool $value ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with non-sensitive boolean data
				Logger::info( 'Test message', [ 'is_active' => $value ] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the boolean value is preserved
				$this->assertEquals(
					$value,
					$context['is_active'],
					'Non-sensitive boolean value should be preserved'
				);
			}
		);
	}

	/**
	 * Property 31: Non-Sensitive Preservation - Array values preserved
	 *
	 * For any context data containing non-sensitive array keys, the Logger SHALL
	 * preserve the original array values unchanged.
	 *
	 * **Validates: Requirements 17.4**
	 *
	 * @return void
	 */
	public function test_array_values_preserved(): void {
		$this->forAll(
			Generators::integers( 1, 5 )
		)
		->then(
			function ( int $num_items ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create array of non-sensitive data
				$items = [];
				for ( $i = 0; $i < $num_items; $i++ ) {
					$items[] = "item_$i";
				}

				// Log with non-sensitive array data
				Logger::info( 'Test message', [ 'items' => $items ] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify the array values are preserved
				$this->assertEquals(
					$items,
					$context['items'],
					'Non-sensitive array values should be preserved'
				);
			}
		);
	}

	/**
	 * Property 31: Non-Sensitive Preservation - Mixed types preserved
	 *
	 * For any context data containing mixed non-sensitive types, the Logger SHALL
	 * preserve all original values unchanged.
	 *
	 * **Validates: Requirements 17.4**
	 *
	 * @return void
	 */
	public function test_mixed_types_preserved(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 5, 20 ),
			Generators::integers( 1, 1000 ),
			Generators::booleans()
		)
		->then(
			function ( string $name, int $count, bool $active ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with mixed non-sensitive data
				Logger::info( 'Test message', [
					'name' => $name,
					'count' => $count,
					'active' => $active,
					'tags' => [ 'tag1', 'tag2' ],
				] );

				// Verify the entry was created
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Log entry should be created'
				);

				$entry = $meowseo_test_logs[0];

				// Parse the context
				$context = json_decode( $entry['context'] ?? '{}', true );

				// Verify all values are preserved
				$this->assertEquals(
					$name,
					$context['name'],
					'String value should be preserved'
				);

				$this->assertEquals(
					$count,
					$context['count'],
					'Numeric value should be preserved'
				);

				$this->assertEquals(
					$active,
					$context['active'],
					'Boolean value should be preserved'
				);

				$this->assertEquals(
					[ 'tag1', 'tag2' ],
					$context['tags'],
					'Array value should be preserved'
				);
			}
		);
	}

	/**
	 * Property 31: Non-Sensitive Preservation - Nested non-sensitive data preserved
	 *
	 * For any context data containing nested non-sensitive keys, the Logger SHALL
	 * preserve all original values unchanged.
	 *
	 * **Validates: Requirements 17.4**
	 *
	 * @return void
	 */
	public function test_nested_non_sensitive_data_preserved(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 5, 20 ),
			Generators::integers( 1, 1000 )
		)
		->then(
			function ( string $email, int $user_id ) {
				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Log with nested non-sensitive data
				Logger::info( 'Test message', [
					'user' => [
						'id' => $user_id,
						'email' => $email,
						'profile' => [
							'first_name' => 'John',
							'last_name' => 'Doe',
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

				// Verify all nested values are preserved
				$this->assertEquals(
					$user_id,
					$context['user']['id'],
					'Nested numeric value should be preserved'
				);

				$this->assertEquals(
					$email,
					$context['user']['email'],
					'Nested string value should be preserved'
				);

				$this->assertEquals(
					'John',
					$context['user']['profile']['first_name'],
					'Deeply nested string value should be preserved'
				);

				$this->assertEquals(
					'Doe',
					$context['user']['profile']['last_name'],
					'Deeply nested string value should be preserved'
				);
			}
		);
	}

	/**
	 * Property 31: Non-Sensitive Preservation - Null values preserved
	 *
	 * For any context data containing null values, the Logger SHALL preserve
	 * the null values unchanged.
	 *
	 * **Validates: Requirements 17.4**
	 *
	 * @return void
	 */
	public function test_null_values_preserved(): void {
		// Clear previous logs
		global $meowseo_test_logs;
		$meowseo_test_logs = [];

		// Log with null values
		Logger::info( 'Test message', [
			'optional_field' => null,
			'another_field' => 'value',
		] );

		// Verify the entry was created
		$this->assertNotEmpty(
			$meowseo_test_logs,
			'Log entry should be created'
		);

		$entry = $meowseo_test_logs[0];

		// Parse the context
		$context = json_decode( $entry['context'] ?? '{}', true );

		// Verify null value is preserved
		$this->assertNull(
			$context['optional_field'],
			'Null value should be preserved'
		);

		$this->assertEquals(
			'value',
			$context['another_field'],
			'Non-null value should be preserved'
		);
	}

	/**
	 * Property 31: Non-Sensitive Preservation - Empty strings preserved
	 *
	 * For any context data containing empty strings, the Logger SHALL preserve
	 * the empty strings unchanged.
	 *
	 * **Validates: Requirements 17.4**
	 *
	 * @return void
	 */
	public function test_empty_strings_preserved(): void {
		// Clear previous logs
		global $meowseo_test_logs;
		$meowseo_test_logs = [];

		// Log with empty strings
		Logger::info( 'Test message', [
			'empty_field' => '',
			'normal_field' => 'value',
		] );

		// Verify the entry was created
		$this->assertNotEmpty(
			$meowseo_test_logs,
			'Log entry should be created'
		);

		$entry = $meowseo_test_logs[0];

		// Parse the context
		$context = json_decode( $entry['context'] ?? '{}', true );

		// Verify empty string is preserved
		$this->assertEquals(
			'',
			$context['empty_field'],
			'Empty string should be preserved'
		);

		$this->assertEquals(
			'value',
			$context['normal_field'],
			'Non-empty string should be preserved'
		);
	}
}
