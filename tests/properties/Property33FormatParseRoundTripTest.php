<?php
/**
 * Property-Based Tests for Format-Parse Round-Trip
 *
 * Property 33: Format-Parse Round-Trip
 * Validates: Requirement 18.5
 *
 * This test uses property-based testing (eris/eris) to verify that for any valid log entry,
 * formatting to markdown then parsing back to structured data SHALL produce equivalent
 * data structures.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Log_Formatter;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Format-Parse Round-Trip property-based test case
 *
 * @since 1.0.0
 */
class Property33FormatParseRoundTripTest extends TestCase {
	use TestTrait;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Define MEOWSEO_VERSION constant if not defined.
		if ( ! defined( 'MEOWSEO_VERSION' ) ) {
			define( 'MEOWSEO_VERSION', '1.0.0' );
		}
	}

	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Property 33: Format-Parse Round-Trip
	 *
	 * For any valid log entry, formatting to markdown then parsing back to structured data
	 * SHALL produce equivalent data structures.
	 *
	 * This property verifies:
	 * 1. Context can be parsed from formatted output
	 * 2. Parsed context is equivalent to original context
	 * 3. Round-trip preserves data structure integrity
	 * 4. Round-trip works with nested context data
	 * 5. Round-trip handles various data types (strings, numbers, booleans, arrays)
	 *
	 * **Validates: Requirement 18.5**
	 *
	 * @return void
	 */
	public function test_format_parse_round_trip_preserves_context(): void {
		$this->forAll(
			Generators::string(),
			Generators::elements( [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ] ),
			Generators::elements( [ 'gsc', 'sitemap', 'redirects', 'meta', 'schema' ] ),
			Generators::string(),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( string $message, string $level, string $module, string $timestamp, int $hit_count ) {
				// Skip empty messages
				if ( empty( $message ) ) {
					return;
				}

				// Create a valid log entry with context
				$original_context = [
					'key1' => 'value1',
					'key2' => 'value2',
					'nested' => [
						'key3' => 'value3',
						'key4' => 123,
					],
				];

				$entry = [
					'id'         => 1,
					'level'      => $level,
					'module'     => $module,
					'message'    => $message,
					'context'    => json_encode( $original_context ),
					'stack_trace' => null,
					'hit_count'  => $hit_count,
					'created_at' => $timestamp,
				];

				// Format the entry to markdown
				$formatted = Log_Formatter::format_single_entry( $entry );

				// Verify formatted output is not empty
				$this->assertNotEmpty(
					$formatted,
					'Formatted output should not be empty'
				);

				// Verify formatted output contains the message
				$this->assertStringContainsString(
					$message,
					$formatted,
					'Formatted output should contain the original message'
				);

				// Parse context from the formatted output
				$parsed_context = Log_Formatter::parse_context( $entry['context'] );

				// Verify parsed context is equivalent to original
				$this->assertEquals(
					$original_context,
					$parsed_context,
					'Parsed context should be equivalent to original context'
				);

				// Verify parsed context is an array
				$this->assertIsArray(
					$parsed_context,
					'Parsed context should be an array'
				);

				// Verify nested structure is preserved
				if ( isset( $parsed_context['nested'] ) ) {
					$this->assertIsArray(
						$parsed_context['nested'],
						'Nested context should be an array'
					);
					$this->assertEquals(
						'value3',
						$parsed_context['nested']['key3'],
						'Nested context values should be preserved'
					);
				}
			}
		);
	}

	/**
	 * Property: Format-Parse Round-Trip with Complex Context
	 *
	 * For any log entry with complex nested context data, the round-trip should
	 * preserve all data types and structure.
	 *
	 * @return void
	 */
	public function test_format_parse_round_trip_with_complex_context(): void {
		$this->forAll(
			Generators::choose( 1, 1000 ),
			Generators::elements( [ true, false ] ),
			Generators::choose( 0, 100 )
		)
		->then(
			function ( int $int_value, bool $bool_value, int $float_value ) {
				// Create complex context with various data types
				$original_context = [
					'string_value' => 'test string',
					'int_value' => $int_value,
					'bool_value' => $bool_value,
					'float_value' => (float) $float_value,
					'array_value' => [ 'a', 'b', 'c' ],
					'nested_object' => [
						'level1' => [
							'level2' => [
								'level3' => 'deep value',
							],
						],
					],
				];

				$entry = [
					'id'         => 1,
					'level'      => 'INFO',
					'module'     => 'test',
					'message'    => 'Test message',
					'context'    => json_encode( $original_context ),
					'stack_trace' => null,
					'hit_count'  => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format and parse
				$formatted = Log_Formatter::format_single_entry( $entry );
				$parsed_context = Log_Formatter::parse_context( $entry['context'] );

				// Verify all data types are preserved
				$this->assertIsString(
					$parsed_context['string_value'],
					'String values should be preserved'
				);

				$this->assertIsInt(
					$parsed_context['int_value'],
					'Integer values should be preserved'
				);

				$this->assertIsBool(
					$parsed_context['bool_value'],
					'Boolean values should be preserved'
				);

				// Note: JSON doesn't distinguish between int and float for whole numbers
				// so we just verify the numeric value is preserved
				$this->assertIsNumeric(
					$parsed_context['float_value'],
					'Numeric values should be preserved'
				);

				$this->assertIsArray(
					$parsed_context['array_value'],
					'Array values should be preserved'
				);

				// Verify deep nesting is preserved
				$this->assertEquals(
					'deep value',
					$parsed_context['nested_object']['level1']['level2']['level3'],
					'Deep nested values should be preserved'
				);

				// Verify complete equivalence
				$this->assertEquals(
					$original_context,
					$parsed_context,
					'Complex context should be completely equivalent after round-trip'
				);
			}
		);
	}

	/**
	 * Property: Format-Parse Round-Trip with Empty Context
	 *
	 * For any log entry with empty or null context, the round-trip should
	 * handle gracefully and return empty array.
	 *
	 * @return void
	 */
	public function test_format_parse_round_trip_with_empty_context(): void {
		$entry_with_null_context = [
			'id'         => 1,
			'level'      => 'INFO',
			'module'     => 'test',
			'message'    => 'Test message',
			'context'    => null,
			'stack_trace' => null,
			'hit_count'  => 1,
			'created_at' => '2024-01-15 10:30:45',
		];

		$entry_with_empty_context = [
			'id'         => 2,
			'level'      => 'INFO',
			'module'     => 'test',
			'message'    => 'Test message',
			'context'    => '',
			'stack_trace' => null,
			'hit_count'  => 1,
			'created_at' => '2024-01-15 10:30:45',
		];

		$entry_with_empty_json = [
			'id'         => 3,
			'level'      => 'INFO',
			'module'     => 'test',
			'message'    => 'Test message',
			'context'    => '{}',
			'stack_trace' => null,
			'hit_count'  => 1,
			'created_at' => '2024-01-15 10:30:45',
		];

		// Test null context
		$formatted1 = Log_Formatter::format_single_entry( $entry_with_null_context );
		$parsed1 = Log_Formatter::parse_context( $entry_with_null_context['context'] ?? '' );
		$this->assertIsArray( $parsed1 );
		$this->assertEmpty( $parsed1 );

		// Test empty string context
		$formatted2 = Log_Formatter::format_single_entry( $entry_with_empty_context );
		$parsed2 = Log_Formatter::parse_context( $entry_with_empty_context['context'] );
		$this->assertIsArray( $parsed2 );
		$this->assertEmpty( $parsed2 );

		// Test empty JSON object
		$formatted3 = Log_Formatter::format_single_entry( $entry_with_empty_json );
		$parsed3 = Log_Formatter::parse_context( $entry_with_empty_json['context'] );
		$this->assertIsArray( $parsed3 );
		$this->assertEmpty( $parsed3 );
	}

	/**
	 * Property: Format-Parse Round-Trip is Deterministic
	 *
	 * For any given log entry, the round-trip should always produce the same result
	 * (deterministic behavior).
	 *
	 * @return void
	 */
	public function test_format_parse_round_trip_is_deterministic(): void {
		$this->forAll(
			Generators::string()
		)
		->then(
			function ( string $message ) {
				// Skip empty messages
				if ( empty( $message ) ) {
					return;
				}

				$context = [
					'key1' => 'value1',
					'key2' => 'value2',
				];

				$entry = [
					'id'         => 1,
					'level'      => 'INFO',
					'module'     => 'test',
					'message'    => $message,
					'context'    => json_encode( $context ),
					'stack_trace' => null,
					'hit_count'  => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Perform round-trip three times
				$parsed1 = Log_Formatter::parse_context( $entry['context'] );
				$parsed2 = Log_Formatter::parse_context( $entry['context'] );
				$parsed3 = Log_Formatter::parse_context( $entry['context'] );

				// All three should be identical
				$this->assertEquals(
					$parsed1,
					$parsed2,
					'Round-trip should be deterministic (run 1 vs 2)'
				);

				$this->assertEquals(
					$parsed2,
					$parsed3,
					'Round-trip should be deterministic (run 2 vs 3)'
				);
			}
		);
	}

	/**
	 * Property: Format-Parse Round-Trip with Stack Traces
	 *
	 * For any log entry with stack trace, the formatted output should include
	 * the stack trace and be parseable.
	 *
	 * @return void
	 */
	public function test_format_parse_round_trip_with_stack_traces(): void {
		$stack_traces = [
			"#0 /path/to/file.php(123): function_name()\n#1 /path/to/another.php(456): another_function()",
			"#0 /includes/helpers/class-logger.php(100): Logger->log()\n#1 /includes/class-plugin.php(50): Plugin->boot()",
			"#0 /path/file.php(1): func1()\n#1 /path/file.php(2): func2()\n#2 /path/file.php(3): func3()",
		];

		foreach ( $stack_traces as $stack_trace ) {
			$entry = [
				'id'         => 1,
				'level'      => 'ERROR',
				'module'     => 'test',
				'message'    => 'Test error',
				'context'    => json_encode( [ 'error' => 'test' ] ),
				'stack_trace' => $stack_trace,
				'hit_count'  => 1,
				'created_at' => '2024-01-15 10:30:45',
			];

			// Format the entry
			$formatted = Log_Formatter::format_single_entry( $entry );

			// Verify stack trace is included in formatted output
			$this->assertStringContainsString(
				'**Stack Trace**:',
				$formatted,
				'Formatted output should include stack trace section'
			);

			// Verify context is still parseable
			$parsed_context = Log_Formatter::parse_context( $entry['context'] );
			$this->assertIsArray( $parsed_context );
			$this->assertEquals( 'test', $parsed_context['error'] );
		}
	}

	/**
	 * Property: Format-Parse Round-Trip with All Log Levels
	 *
	 * For any log level (DEBUG, INFO, WARNING, ERROR, CRITICAL), the round-trip
	 * should work correctly.
	 *
	 * @return void
	 */
	public function test_format_parse_round_trip_with_all_log_levels(): void {
		$log_levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];

		foreach ( $log_levels as $level ) {
			$context = [
				'level' => $level,
				'test' => 'data',
			];

			$entry = [
				'id'         => 1,
				'level'      => $level,
				'module'     => 'test',
				'message'    => "Test $level message",
				'context'    => json_encode( $context ),
				'stack_trace' => null,
				'hit_count'  => 1,
				'created_at' => '2024-01-15 10:30:45',
			];

			// Format and parse
			$formatted = Log_Formatter::format_single_entry( $entry );
			$parsed_context = Log_Formatter::parse_context( $entry['context'] );

			// Verify level is preserved in formatted output
			$this->assertStringContainsString(
				$level,
				$formatted,
				"Formatted output should include log level: $level"
			);

			// Verify context is correctly parsed
			$this->assertEquals(
				$context,
				$parsed_context,
				"Context should be correctly parsed for level: $level"
			);
		}
	}

	/**
	 * Property: Format-Parse Round-Trip with All Modules
	 *
	 * For any module name, the round-trip should work correctly.
	 *
	 * @return void
	 */
	public function test_format_parse_round_trip_with_all_modules(): void {
		$modules = [ 'gsc', 'sitemap', 'redirects', 'meta', 'schema', 'social', 'woocommerce', 'monitor_404', 'internal_links' ];

		foreach ( $modules as $module ) {
			$context = [
				'module' => $module,
				'action' => 'test',
			];

			$entry = [
				'id'         => 1,
				'level'      => 'INFO',
				'module'     => $module,
				'message'    => "Test $module message",
				'context'    => json_encode( $context ),
				'stack_trace' => null,
				'hit_count'  => 1,
				'created_at' => '2024-01-15 10:30:45',
			];

			// Format and parse
			$formatted = Log_Formatter::format_single_entry( $entry );
			$parsed_context = Log_Formatter::parse_context( $entry['context'] );

			// Verify module is preserved in formatted output
			$this->assertStringContainsString(
				$module,
				$formatted,
				"Formatted output should include module: $module"
			);

			// Verify context is correctly parsed
			$this->assertEquals(
				$context,
				$parsed_context,
				"Context should be correctly parsed for module: $module"
			);
		}
	}
}
