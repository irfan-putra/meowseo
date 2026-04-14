<?php
/**
 * Property-Based Tests for Options Round-Trip
 *
 * Property 3: Options round-trip preserves all values
 * Validates: Requirement 2.2
 *
 * This test uses property-based testing (eris/eris) to verify that the Options class
 * correctly preserves values when they are set via Options::set() and retrieved via
 * Options::get(). Tests with various data types including strings, arrays, booleans,
 * and integers.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Options;

/**
 * Options round-trip property-based test case
 *
 * @since 1.0.0
 */
class OptionsRoundtripPropertyTest extends TestCase {
	use TestTrait;

	/**
	 * Property 3: Options round-trip preserves all values
	 *
	 * For any arbitrary option value (string, array, boolean, integer):
	 * 1. When a value is set via Options::set()
	 * 2. And retrieved via Options::get()
	 * 3. The retrieved value should match the original value exactly
	 * 4. Type should be preserved (no type coercion)
	 *
	 * **Validates: Requirement 2.2**
	 *
	 * @return void
	 */
	public function test_options_roundtrip_preserves_all_values(): void {
		$this->forAll(
			Generators::oneOf(
				// String values
				Generators::string(),
				// Integer values
				Generators::int(),
				// Boolean values
				Generators::bool(),
				// Array values with string keys and mixed values
				Generators::associative(
					[
						'string_key' => Generators::string(),
						'int_key' => Generators::int(),
						'bool_key' => Generators::bool(),
					]
				)
			)
		)
		->then(
			function ( $value ) {
				// Create a fresh Options instance
				$options = new Options();

				// Set the value
				$options->set( 'test_key', $value );

				// Get the value back
				$retrieved = $options->get( 'test_key' );

				// Verify the value is preserved exactly
				$this->assertEquals(
					$value,
					$retrieved,
					'Options round-trip should preserve the value exactly'
				);

				// Verify the type is preserved (no type coercion)
				$this->assertSame(
					gettype( $value ),
					gettype( $retrieved ),
					'Options round-trip should preserve the type'
				);
			}
		);
	}

	/**
	 * Property: Options preserves string values with special characters
	 *
	 * For any string value including special characters, Unicode, and edge cases:
	 * 1. The value should be preserved exactly
	 * 2. Special characters should not be escaped or modified
	 * 3. Unicode characters should be preserved
	 *
	 * **Validates: Requirement 2.2**
	 *
	 * @return void
	 */
	public function test_options_preserves_string_values_with_special_characters(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $value ) {
				$options = new Options();

				$options->set( 'test_string', $value );
				$retrieved = $options->get( 'test_string' );

				$this->assertSame(
					$value,
					$retrieved,
					'String values with special characters should be preserved'
				);

				$this->assertIsString( $retrieved );
			}
		);
	}

	/**
	 * Property: Options preserves integer values across full range
	 *
	 * For any integer value including negative, zero, and large values:
	 * 1. The value should be preserved exactly
	 * 2. Type should remain integer (not converted to string)
	 * 3. Negative values should be preserved
	 *
	 * **Validates: Requirement 2.2**
	 *
	 * @return void
	 */
	public function test_options_preserves_integer_values(): void {
		$this->forAll(
			Generators::int()
		)
		->then(
			function ( int $value ) {
				$options = new Options();

				$options->set( 'test_int', $value );
				$retrieved = $options->get( 'test_int' );

				$this->assertSame(
					$value,
					$retrieved,
					'Integer values should be preserved exactly'
				);

				$this->assertIsInt( $retrieved );
			}
		);
	}

	/**
	 * Property: Options preserves boolean values
	 *
	 * For any boolean value (true or false):
	 * 1. The value should be preserved exactly
	 * 2. Type should remain boolean (not converted to 0/1 or string)
	 * 3. Both true and false should be preserved
	 *
	 * **Validates: Requirement 2.2**
	 *
	 * @return void
	 */
	public function test_options_preserves_boolean_values(): void {
		$this->forAll(
			Generators::bool()
		)
		->then(
			function ( bool $value ) {
				$options = new Options();

				$options->set( 'test_bool', $value );
				$retrieved = $options->get( 'test_bool' );

				$this->assertSame(
					$value,
					$retrieved,
					'Boolean values should be preserved exactly'
				);

				$this->assertIsBool( $retrieved );
			}
		);
	}

	/**
	 * Property: Options preserves array values with mixed types
	 *
	 * For any array with mixed value types:
	 * 1. All array elements should be preserved
	 * 2. Array keys should be preserved
	 * 3. Nested structures should be preserved
	 * 4. Element types should not be coerced
	 *
	 * **Validates: Requirement 2.2**
	 *
	 * @return void
	 */
	public function test_options_preserves_array_values_with_mixed_types(): void {
		$this->forAll(
			Generators::associative(
				[
					'string_key' => Generators::string(),
					'int_key' => Generators::int(),
					'bool_key' => Generators::bool(),
				]
			)
		)
		->then(
			function ( array $value ) {
				$options = new Options();

				$options->set( 'test_array', $value );
				$retrieved = $options->get( 'test_array' );

				$this->assertSame(
					$value,
					$retrieved,
					'Array values should be preserved exactly'
				);

				$this->assertIsArray( $retrieved );

				// Verify each element type is preserved
				foreach ( $value as $key => $element ) {
					$this->assertSame(
						gettype( $element ),
						gettype( $retrieved[ $key ] ),
						"Type of array element '$key' should be preserved"
					);
				}
			}
		);
	}

	/**
	 * Property: Options get() returns default when key not set
	 *
	 * For any key that has not been set:
	 * 1. get() should return the provided default value
	 * 2. The default value should be returned unchanged
	 * 3. Type of default should be preserved
	 *
	 * **Validates: Requirement 2.2**
	 *
	 * @return void
	 */
	public function test_options_get_returns_default_when_key_not_set(): void {
		$this->forAll(
			Generators::oneOf(
				Generators::string(),
				Generators::int(),
				Generators::bool()
			)
		)
		->then(
			function ( $default_value ) {
				$options = new Options();

				// Don't set the key, just get with default
				$retrieved = $options->get( 'nonexistent_key', $default_value );

				$this->assertSame(
					$default_value,
					$retrieved,
					'get() should return the default value when key is not set'
				);

				$this->assertSame(
					gettype( $default_value ),
					gettype( $retrieved ),
					'Default value type should be preserved'
				);
			}
		);
	}

	/**
	 * Property: Options preserves multiple values independently
	 *
	 * For any set of multiple key-value pairs:
	 * 1. Each value should be preserved independently
	 * 2. Setting one value should not affect others
	 * 3. All values should be retrievable in any order
	 *
	 * **Validates: Requirement 2.2**
	 *
	 * @return void
	 */
	public function test_options_preserves_multiple_values_independently(): void {
		$this->forAll(
			Generators::string(),
			Generators::string(),
			Generators::string(),
			Generators::oneOf(
				Generators::string(),
				Generators::int(),
				Generators::bool()
			),
			Generators::oneOf(
				Generators::string(),
				Generators::int(),
				Generators::bool()
			),
			Generators::oneOf(
				Generators::string(),
				Generators::int(),
				Generators::bool()
			)
		)
		->then(
			function ( $key1, $key2, $key3, $value1, $value2, $value3 ) {
				// Ensure keys are unique
				$keys = array_unique( [ $key1, $key2, $key3 ] );
				if ( count( $keys ) < 3 ) {
					// Skip if keys are not unique
					return;
				}

				$options = new Options();

				// Set multiple values
				$options->set( $key1, $value1 );
				$options->set( $key2, $value2 );
				$options->set( $key3, $value3 );

				// Retrieve all values
				$retrieved1 = $options->get( $key1 );
				$retrieved2 = $options->get( $key2 );
				$retrieved3 = $options->get( $key3 );

				// Verify all values are preserved
				$this->assertSame( $value1, $retrieved1 );
				$this->assertSame( $value2, $retrieved2 );
				$this->assertSame( $value3, $retrieved3 );

				// Verify types are preserved
				$this->assertSame( gettype( $value1 ), gettype( $retrieved1 ) );
				$this->assertSame( gettype( $value2 ), gettype( $retrieved2 ) );
				$this->assertSame( gettype( $value3 ), gettype( $retrieved3 ) );
			}
		);
	}

	/**
	 * Property: Options preserves empty values
	 *
	 * For empty values (empty string, empty array, false, 0):
	 * 1. Empty values should be preserved (not treated as unset)
	 * 2. Type should be preserved
	 * 3. Empty values should be distinguishable from unset keys
	 *
	 * **Validates: Requirement 2.2**
	 *
	 * @return void
	 */
	public function test_options_preserves_empty_values(): void {
		$empty_values = [
			'empty_string' => '',
			'empty_array' => [],
			'false' => false,
			'zero' => 0,
		];

		$options = new Options();

		foreach ( $empty_values as $key => $value ) {
			$options->set( $key, $value );
			$retrieved = $options->get( $key );

			$this->assertSame(
				$value,
				$retrieved,
				"Empty value for key '$key' should be preserved"
			);

			$this->assertSame(
				gettype( $value ),
				gettype( $retrieved ),
				"Type of empty value for key '$key' should be preserved"
			);
		}
	}

	/**
	 * Property: Options distinguishes between unset and empty values
	 *
	 * For any key:
	 * 1. An unset key with default should return the default
	 * 2. A key set to empty value should return the empty value
	 * 3. These should be distinguishable
	 *
	 * **Validates: Requirement 2.2**
	 *
	 * @return void
	 */
	public function test_options_distinguishes_unset_from_empty(): void {
		$options = new Options();

		// Set a key to empty string
		$options->set( 'empty_key', '' );

		// Get unset key with default
		$unset_result = $options->get( 'unset_key', 'default' );

		// Get empty key
		$empty_result = $options->get( 'empty_key' );

		// Verify they are different
		$this->assertNotSame(
			$unset_result,
			$empty_result,
			'Unset key with default should differ from empty value'
		);

		$this->assertSame( 'default', $unset_result );
		$this->assertSame( '', $empty_result );
	}
}
