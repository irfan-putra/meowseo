<?php
/**
 * Property-Based Tests for Context Parsing
 *
 * Property 32: Context Parsing
 * Validates: Requirement 18.1
 *
 * This test uses property-based testing (eris/eris) to verify that for any log entry
 * retrieved from the database with valid JSON context, the Log_Formatter SHALL parse it
 * into a structured PHP array. This property validates that context data stored as JSON
 * in the database can be reliably parsed back into PHP arrays without data loss.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Log_Formatter;

/**
 * Context Parsing property-based test case
 *
 * @since 1.0.0
 */
class Property32ContextParsingTest extends TestCase {
	use TestTrait;

	/**
	 * Property 32: Context Parsing - With strings
	 *
	 * For any valid JSON context string containing string values, the Log_Formatter
	 * SHALL parse it into a structured PHP array with the same string values.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_strings(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 0, 100 )
		)
		->then(
			function ( string $value ) {
				// Create a context array with string values
				$context = [
					'key1' => $value,
					'key2' => 'static_value',
					'key3' => 'another_string',
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with string values'
				);

				// Verify all string values are preserved
				$this->assertIsString(
					$parsed['key1'],
					'String values should remain strings after parsing'
				);

				$this->assertEquals(
					$value,
					$parsed['key1'],
					'String value should be preserved exactly'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - With integers
	 *
	 * For any valid JSON context string containing integer values, the Log_Formatter
	 * SHALL parse it into a structured PHP array with the same integer values.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_integers(): void {
		$this->forAll(
			Generators::choose( -1000000, 1000000 )
		)
		->then(
			function ( int $value ) {
				// Create a context array with integer values
				$context = [
					'count' => $value,
					'status_code' => 200,
					'error_code' => -1,
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with integer values'
				);

				// Verify integer types are preserved
				$this->assertIsInt(
					$parsed['count'],
					'Integer values should remain integers after parsing'
				);

				$this->assertEquals(
					$value,
					$parsed['count'],
					'Integer value should be preserved exactly'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - With booleans
	 *
	 * For any valid JSON context string containing boolean values, the Log_Formatter
	 * SHALL parse it into a structured PHP array with the same boolean values.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_booleans(): void {
		$this->forAll(
			Generators::elements( [ true, false ] )
		)
		->then(
			function ( bool $value ) {
				// Create a context array with boolean values
				$context = [
					'is_active' => $value,
					'is_enabled' => true,
					'is_disabled' => false,
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with boolean values'
				);

				// Verify boolean types are preserved
				$this->assertIsBool(
					$parsed['is_active'],
					'Boolean values should remain booleans after parsing'
				);

				$this->assertEquals(
					$value,
					$parsed['is_active'],
					'Boolean value should be preserved exactly'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - With floats
	 *
	 * For any valid JSON context string containing float values, the Log_Formatter
	 * SHALL parse it into a structured PHP array with the same numeric values.
	 * Note: JSON may convert floats to integers if they have no decimal part.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_floats(): void {
		$this->forAll(
			Generators::choose( 1, 10000 )
		)
		->then(
			function ( int $int_value ) {
				// Convert to float with decimal part
				$value = $int_value / 100.0;

				// Create a context array with float values
				$context = [
					'score' => $value,
					'rating' => 4.5,
					'percentage' => 99.99,
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array (with float precision tolerance)
				$this->assertEqualsWithDelta(
					$context['score'],
					$parsed['score'],
					0.0001,
					'Float values should be equivalent after parsing'
				);

				// Verify numeric types are preserved (int or float)
				$this->assertTrue(
					is_int( $parsed['score'] ) || is_float( $parsed['score'] ),
					'Numeric values should remain numeric after parsing'
				);

				// Verify the other float values are preserved
				$this->assertEqualsWithDelta(
					4.5,
					$parsed['rating'],
					0.0001,
					'Rating float should be preserved'
				);

				$this->assertEqualsWithDelta(
					99.99,
					$parsed['percentage'],
					0.0001,
					'Percentage float should be preserved'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - With null values
	 *
	 * For any valid JSON context string containing null values, the Log_Formatter
	 * SHALL parse it into a structured PHP array with the same null values.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_null_values(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 20 )
		)
		->then(
			function ( string $key ) {
				// Create a context array with null values
				$context = [
					'value1' => null,
					'value2' => 'not_null',
					$key => null,
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with null values'
				);

				// Verify null values are preserved
				$this->assertNull(
					$parsed['value1'],
					'Null values should remain null after parsing'
				);

				$this->assertNull(
					$parsed[ $key ],
					'Null values should remain null regardless of key'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - With nested arrays
	 *
	 * For any valid JSON context string containing nested arrays, the Log_Formatter
	 * SHALL parse it into a structured PHP array with the same nested structure.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_nested_arrays(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 20 ),
			Generators::choose( -1000, 1000 ),
			Generators::elements( [ true, false ] )
		)
		->then(
			function ( string $nested_key, int $nested_int, bool $nested_bool ) {
				// Create a context array with nested structure
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

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with nested structure'
				);

				// Verify nested structure is preserved
				$this->assertIsArray(
					$parsed['nested'],
					'Nested arrays should remain arrays after parsing'
				);

				$this->assertIsArray(
					$parsed['another_level']['deep'],
					'Deeply nested arrays should remain arrays after parsing'
				);

				$this->assertEquals(
					'deeply_nested',
					$parsed['another_level']['deep']['value'],
					'Deeply nested values should be preserved'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - With mixed data types
	 *
	 * For any valid JSON context string containing mixed data types, the Log_Formatter
	 * SHALL parse it into a structured PHP array with all values and types preserved.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_mixed_types(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 30 ),
			Generators::choose( -1000, 1000 ),
			Generators::elements( [ true, false ] )
		)
		->then(
			function ( string $str_val, int $int_val, bool $bool_val ) {
				// Create a context array with mixed data types
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

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with mixed types'
				);

				// Verify individual field types
				$this->assertIsString(
					$parsed['string_field'],
					'String fields should remain strings'
				);

				$this->assertIsInt(
					$parsed['integer_field'],
					'Integer fields should remain integers'
				);

				$this->assertIsBool(
					$parsed['boolean_field'],
					'Boolean fields should remain booleans'
				);

				$this->assertNull(
					$parsed['null_field'],
					'Null fields should remain null'
				);

				$this->assertIsArray(
					$parsed['nested_mixed'],
					'Nested arrays should remain arrays'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - With empty arrays
	 *
	 * For any valid JSON context string containing empty arrays, the Log_Formatter
	 * SHALL parse it into a structured PHP array with the same empty array structure.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_empty_arrays(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 20 )
		)
		->then(
			function ( string $key ) {
				// Create a context array with empty arrays
				$context = [
					'empty_array' => [],
					'nested_with_empty' => [
						'inner_empty' => [],
						'value' => 'test',
					],
					$key => [],
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with empty arrays'
				);

				// Verify empty arrays are preserved
				$this->assertEmpty(
					$parsed['empty_array'],
					'Empty arrays should remain empty'
				);

				$this->assertEmpty(
					$parsed['nested_with_empty']['inner_empty'],
					'Nested empty arrays should remain empty'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - With numeric string keys
	 *
	 * For any valid JSON context string containing numeric string keys, the Log_Formatter
	 * SHALL parse it into a structured PHP array with the same array structure.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_numeric_string_keys(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 20 )
		)
		->then(
			function ( string $value ) {
				// Create a context array with numeric string keys
				$context = [
					'0' => 'zero',
					'1' => 'one',
					'2' => $value,
					'items' => [
						'0' => 'first',
						'1' => 'second',
					],
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with numeric string keys'
				);

				// Verify keys are preserved
				$this->assertArrayHasKey(
					'0',
					$parsed,
					'Numeric string keys should be preserved'
				);

				$this->assertArrayHasKey(
					'items',
					$parsed,
					'Regular keys should be preserved alongside numeric string keys'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - With special characters
	 *
	 * For any valid JSON context string containing special characters and unicode,
	 * the Log_Formatter SHALL parse it into a structured PHP array with the same values.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_special_characters(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $value ) {
				// Create a context array with special characters
				$context = [
					'message' => $value,
					'special' => 'Test "quotes" and \'apostrophes\'',
					'escaped' => 'Line\nbreak\ttab',
					'unicode' => '你好世界 🌍',
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with special characters'
				);

				// Verify special characters are preserved
				$this->assertStringContainsString(
					'quotes',
					$parsed['special'],
					'Quoted strings should be preserved'
				);

				$this->assertStringContainsString(
					'🌍',
					$parsed['unicode'],
					'Unicode characters should be preserved'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - Empty string returns empty array
	 *
	 * For an empty JSON context string, the Log_Formatter SHALL return an empty array.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_empty_string(): void {
		// Parse empty string
		$parsed = Log_Formatter::parse_context( '' );

		// Verify empty array is returned
		$this->assertIsArray(
			$parsed,
			'Empty string should return an array'
		);

		$this->assertEmpty(
			$parsed,
			'Empty string should return an empty array'
		);
	}

	/**
	 * Property 32: Context Parsing - Deterministic behavior
	 *
	 * For any valid JSON context string, parsing it multiple times SHALL always
	 * produce the same result (deterministic behavior).
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_is_deterministic(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 ),
			Generators::choose( -1000, 1000 ),
			Generators::elements( [ true, false ] )
		)
		->then(
			function ( string $str, int $num, bool $flag ) {
				// Create a context array
				$context = [
					'string' => $str,
					'number' => $num,
					'flag' => $flag,
					'nested' => [
						'value' => 'test',
					],
				];

				// Encode to JSON
				$json_context = wp_json_encode( $context );

				// Parse three times
				$parsed1 = Log_Formatter::parse_context( $json_context );
				$parsed2 = Log_Formatter::parse_context( $json_context );
				$parsed3 = Log_Formatter::parse_context( $json_context );

				// All three should be identical
				$this->assertEquals(
					$parsed1,
					$parsed2,
					'Parsing should be deterministic (run 1 vs 2)'
				);

				$this->assertEquals(
					$parsed2,
					$parsed3,
					'Parsing should be deterministic (run 2 vs 3)'
				);

				// All should equal the original
				$this->assertEquals(
					$context,
					$parsed1,
					'Final result should equal original context'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - Large nested structures
	 *
	 * For any valid JSON context string with deeply nested structures, the Log_Formatter
	 * SHALL parse it into a structured PHP array with the same nested structure.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_large_nested_structures(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 20 )
		)
		->then(
			function ( string $value ) {
				// Create a deeply nested context array
				$context = [
					'level1' => [
						'level2' => [
							'level3' => [
								'level4' => [
									'level5' => [
										'value' => $value,
										'count' => 42,
										'flag' => true,
									],
								],
							],
						],
					],
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with large nested structures'
				);

				// Verify deeply nested value is accessible and correct
				$this->assertEquals(
					$value,
					$parsed['level1']['level2']['level3']['level4']['level5']['value'],
					'Deeply nested values should be preserved'
				);

				$this->assertEquals(
					42,
					$parsed['level1']['level2']['level3']['level4']['level5']['count'],
					'Deeply nested integers should be preserved'
				);

				$this->assertTrue(
					$parsed['level1']['level2']['level3']['level4']['level5']['flag'],
					'Deeply nested booleans should be preserved'
				);
			}
		);
	}

	/**
	 * Property 32: Context Parsing - Array with many keys
	 *
	 * For any valid JSON context string with many keys, the Log_Formatter
	 * SHALL parse it into a structured PHP array with all keys preserved.
	 *
	 * **Validates: Requirement 18.1**
	 *
	 * @return void
	 */
	public function test_parse_context_with_many_keys(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 20 )
		)
		->then(
			function ( string $value ) {
				// Create a context array with many keys
				$context = [
					'key1' => $value,
					'key2' => 'value2',
					'key3' => 'value3',
					'key4' => 'value4',
					'key5' => 'value5',
					'key6' => 'value6',
					'key7' => 'value7',
					'key8' => 'value8',
					'key9' => 'value9',
					'key10' => 'value10',
				];

				// Encode to JSON as it would be stored in database
				$json_context = wp_json_encode( $context );

				// Parse the JSON context
				$parsed = Log_Formatter::parse_context( $json_context );

				// Verify the parsed result matches the original array
				$this->assertEquals(
					$context,
					$parsed,
					'Parsed context should match the original array with many keys'
				);

				// Verify all keys are present
				$this->assertCount(
					10,
					$parsed,
					'All keys should be preserved'
				);

				// Verify each key is accessible
				for ( $i = 1; $i <= 10; $i++ ) {
					$key = "key$i";
					$this->assertArrayHasKey(
						$key,
						$parsed,
						"Key '$key' should be present"
					);
				}
			}
		);
	}
}
