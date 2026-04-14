<?php
/**
 * Property-Based Tests for Context Serialization Round-Trip
 *
 * Property 3: Context Serialization Round-Trip
 * Validates: Requirement 2.3
 *
 * This test uses property-based testing (eris/eris) to verify that for any valid PHP array
 * provided as context, serializing to JSON then deserializing SHALL produce an equivalent
 * array structure. This property validates that context data can be reliably stored and
 * retrieved from the database without data loss or corruption.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;

/**
 * Context Serialization Round-Trip property-based test case
 *
 * @since 1.0.0
 */
class Property3ContextSerializationTest extends TestCase {
	use TestTrait;

	/**
	 * Property 3: Context Serialization Round-Trip
	 *
	 * For any valid PHP array provided as context, serializing to JSON then deserializing
	 * SHALL produce an equivalent array structure.
	 *
	 * This property verifies:
	 * 1. Random context arrays can be JSON encoded
	 * 2. JSON can be decoded back to PHP arrays
	 * 3. The decoded array equals the original array
	 * 4. This holds true for nested structures and various data types
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_round_trip_with_strings(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 0, 100 )
		)
		->then(
			function ( string $value ) {
				// Create a simple context array with string value
				$context = [
					'key1' => $value,
					'key2' => 'static_value',
				];

				// Serialize to JSON
				$json = wp_json_encode( $context );

				// Deserialize back to array
				$decoded = json_decode( $json, true );

				// Verify equivalence
				$this->assertEquals(
					$context,
					$decoded,
					'Context array should be equivalent after JSON round-trip'
				);
			}
		);
	}

	/**
	 * Property 3: Context Serialization Round-Trip - With integers
	 *
	 * For any context array containing integer values, the round-trip serialization
	 * SHALL preserve the integer values.
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_round_trip_with_integers(): void {
		$this->forAll(
			Generators::choose( -1000000, 1000000 )
		)
		->then(
			function ( int $value ) {
				// Create context array with integer value
				$context = [
					'count' => $value,
					'status_code' => 200,
				];

				// Serialize to JSON
				$json = wp_json_encode( $context );

				// Deserialize back to array
				$decoded = json_decode( $json, true );

				// Verify equivalence
				$this->assertEquals(
					$context,
					$decoded,
					'Context array with integers should be equivalent after JSON round-trip'
				);

				// Verify integer types are preserved
				$this->assertIsInt(
					$decoded['count'],
					'Integer values should remain integers after round-trip'
				);
			}
		);
	}

	/**
	 * Property 3: Context Serialization Round-Trip - With booleans
	 *
	 * For any context array containing boolean values, the round-trip serialization
	 * SHALL preserve the boolean values.
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_round_trip_with_booleans(): void {
		$this->forAll(
			Generators::elements( [ true, false ] )
		)
		->then(
			function ( bool $value ) {
				// Create context array with boolean value
				$context = [
					'is_active' => $value,
					'is_enabled' => true,
				];

				// Serialize to JSON
				$json = wp_json_encode( $context );

				// Deserialize back to array
				$decoded = json_decode( $json, true );

				// Verify equivalence
				$this->assertEquals(
					$context,
					$decoded,
					'Context array with booleans should be equivalent after JSON round-trip'
				);

				// Verify boolean types are preserved
				$this->assertIsBool(
					$decoded['is_active'],
					'Boolean values should remain booleans after round-trip'
				);
			}
		);
	}

	/**
	 * Property 3: Context Serialization Round-Trip - With floats
	 *
	 * For any context array containing float values, the round-trip serialization
	 * SHALL preserve the float values.
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_round_trip_with_floats(): void {
		$this->forAll(
			Generators::choose( 0, 10000 )
		)
		->then(
			function ( int $int_value ) {
				// Convert to float
				$value = $int_value / 100.0;

				// Create context array with float value
				$context = [
					'score' => $value,
					'rating' => 4.5,
				];

				// Serialize to JSON
				$json = wp_json_encode( $context );

				// Deserialize back to array
				$decoded = json_decode( $json, true );

				// Verify equivalence (with float precision tolerance)
				$this->assertEqualsWithDelta(
					$context['score'],
					$decoded['score'],
					0.0001,
					'Float values should be equivalent after JSON round-trip'
				);
			}
		);
	}

	/**
	 * Property 3: Context Serialization Round-Trip - With nested arrays
	 *
	 * For any context array containing nested arrays, the round-trip serialization
	 * SHALL preserve the nested structure.
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_round_trip_with_nested_arrays(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 20 ),
			Generators::choose( -1000, 1000 ),
			Generators::elements( [ true, false ] )
		)
		->then(
			function ( string $nested_key, int $nested_int, bool $nested_bool ) {
				// Create context array with nested structure
				$context = [
					'top_level' => 'value',
					'nested' => [
						$nested_key => $nested_int,
						'flag' => $nested_bool,
					],
					'another_level' => [
						'deep' => [
							'value' => 'deeply_nested',
						],
					],
				];

				// Serialize to JSON
				$json = wp_json_encode( $context );

				// Deserialize back to array
				$decoded = json_decode( $json, true );

				// Verify equivalence
				$this->assertEquals(
					$context,
					$decoded,
					'Nested context arrays should be equivalent after JSON round-trip'
				);

				// Verify nested structure is preserved
				$this->assertIsArray(
					$decoded['nested'],
					'Nested arrays should remain arrays after round-trip'
				);

				$this->assertIsArray(
					$decoded['another_level']['deep'],
					'Deeply nested arrays should remain arrays after round-trip'
				);
			}
		);
	}

	/**
	 * Property 3: Context Serialization Round-Trip - With mixed data types
	 *
	 * For any context array containing mixed data types, the round-trip serialization
	 * SHALL preserve all values and their types.
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_round_trip_with_mixed_types(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 30 ),
			Generators::choose( -1000, 1000 ),
			Generators::elements( [ true, false ] )
		)
		->then(
			function ( string $str_val, int $int_val, bool $bool_val ) {
				// Create context array with mixed data types
				$context = [
					'string_field' => $str_val,
					'integer_field' => $int_val,
					'boolean_field' => $bool_val,
					'null_field' => null,
					'nested_mixed' => [
						'str' => 'nested_string',
						'num' => 42,
						'flag' => false,
						'empty_array' => [],
					],
				];

				// Serialize to JSON
				$json = wp_json_encode( $context );

				// Deserialize back to array
				$decoded = json_decode( $json, true );

				// Verify equivalence
				$this->assertEquals(
					$context,
					$decoded,
					'Mixed type context arrays should be equivalent after JSON round-trip'
				);

				// Verify individual field types
				$this->assertIsString(
					$decoded['string_field'],
					'String fields should remain strings'
				);

				$this->assertIsInt(
					$decoded['integer_field'],
					'Integer fields should remain integers'
				);

				$this->assertIsBool(
					$decoded['boolean_field'],
					'Boolean fields should remain booleans'
				);

				$this->assertNull(
					$decoded['null_field'],
					'Null fields should remain null'
				);

				$this->assertIsArray(
					$decoded['nested_mixed'],
					'Nested arrays should remain arrays'
				);
			}
		);
	}

	/**
	 * Property 3: Context Serialization Round-Trip - With empty arrays
	 *
	 * For any context array containing empty arrays, the round-trip serialization
	 * SHALL preserve the empty array structure.
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_round_trip_with_empty_arrays(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 20 )
		)
		->then(
			function ( string $key ) {
				// Create context array with empty arrays
				$context = [
					'empty_array' => [],
					'nested_with_empty' => [
						'inner_empty' => [],
						'value' => 'test',
					],
					$key => [],
				];

				// Serialize to JSON
				$json = wp_json_encode( $context );

				// Deserialize back to array
				$decoded = json_decode( $json, true );

				// Verify equivalence
				$this->assertEquals(
					$context,
					$decoded,
					'Context arrays with empty arrays should be equivalent after JSON round-trip'
				);

				// Verify empty arrays are preserved
				$this->assertEmpty(
					$decoded['empty_array'],
					'Empty arrays should remain empty'
				);

				$this->assertEmpty(
					$decoded['nested_with_empty']['inner_empty'],
					'Nested empty arrays should remain empty'
				);
			}
		);
	}

	/**
	 * Property 3: Context Serialization Round-Trip - With numeric string keys
	 *
	 * For any context array containing numeric string keys, the round-trip serialization
	 * SHALL preserve the array structure.
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_round_trip_with_numeric_string_keys(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 20 )
		)
		->then(
			function ( string $value ) {
				// Create context array with numeric string keys
				$context = [
					'0' => 'zero',
					'1' => 'one',
					'2' => $value,
					'items' => [
						'0' => 'first',
						'1' => 'second',
					],
				];

				// Serialize to JSON
				$json = wp_json_encode( $context );

				// Deserialize back to array
				$decoded = json_decode( $json, true );

				// Verify equivalence
				$this->assertEquals(
					$context,
					$decoded,
					'Context arrays with numeric string keys should be equivalent after JSON round-trip'
				);

				// Verify keys are preserved
				$this->assertArrayHasKey(
					'0',
					$decoded,
					'Numeric string keys should be preserved'
				);

				$this->assertArrayHasKey(
					'items',
					$decoded,
					'Regular keys should be preserved alongside numeric string keys'
				);
			}
		);
	}

	/**
	 * Property 3: Context Serialization Round-Trip - Deterministic
	 *
	 * For any context array, serializing and deserializing multiple times
	 * SHALL always produce the same result (deterministic behavior).
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_is_deterministic(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 ),
			Generators::choose( -1000, 1000 ),
			Generators::elements( [ true, false ] )
		)
		->then(
			function ( string $str, int $num, bool $flag ) {
				// Create context array
				$context = [
					'string' => $str,
					'number' => $num,
					'flag' => $flag,
					'nested' => [
						'value' => 'test',
					],
				];

				// Perform round-trip three times
				$decoded1 = json_decode( wp_json_encode( $context ), true );
				$decoded2 = json_decode( wp_json_encode( $context ), true );
				$decoded3 = json_decode( wp_json_encode( $context ), true );

				// All three should be identical
				$this->assertEquals(
					$decoded1,
					$decoded2,
					'Round-trip serialization should be deterministic (run 1 vs 2)'
				);

				$this->assertEquals(
					$decoded2,
					$decoded3,
					'Round-trip serialization should be deterministic (run 2 vs 3)'
				);

				// All should equal the original
				$this->assertEquals(
					$context,
					$decoded1,
					'Final result should equal original context'
				);
			}
		);
	}

	/**
	 * Property 3: Context Serialization Round-Trip - With special characters
	 *
	 * For any context array containing special characters and unicode, the round-trip
	 * serialization SHALL preserve the values correctly.
	 *
	 * **Validates: Requirement 2.3**
	 *
	 * @return void
	 */
	public function test_context_serialization_round_trip_with_special_characters(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $value ) {
				// Create context array with special characters
				$context = [
					'message' => $value,
					'special' => 'Test "quotes" and \'apostrophes\'',
					'escaped' => 'Line\nbreak\ttab',
					'unicode' => '你好世界 🌍',
				];

				// Serialize to JSON
				$json = wp_json_encode( $context );

				// Deserialize back to array
				$decoded = json_decode( $json, true );

				// Verify equivalence
				$this->assertEquals(
					$context,
					$decoded,
					'Context arrays with special characters should be equivalent after JSON round-trip'
				);

				// Verify special characters are preserved
				$this->assertStringContainsString(
					'quotes',
					$decoded['special'],
					'Quoted strings should be preserved'
				);

				$this->assertStringContainsString(
					'🌍',
					$decoded['unicode'],
					'Unicode characters should be preserved'
				);
			}
		);
	}
}
