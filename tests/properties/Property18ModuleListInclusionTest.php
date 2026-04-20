<?php
/**
 * Property-Based Tests for Module List Inclusion
 *
 * Property 18: Module List Inclusion
 * Validates: Requirement 10.2
 *
 * This test uses property-based testing (eris/eris) to verify that for any formatted
 * log export, the output SHALL include the list of currently active modules.
 * This property validates that the active module list is reliably included in all
 * formatted log exports for AI debugging purposes.
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
 * Module List Inclusion property-based test case
 *
 * @since 1.0.0
 */
class Property18ModuleListInclusionTest extends TestCase {
	use TestTrait;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Define MEOWSEO_VERSION if not already defined
		if ( ! defined( 'MEOWSEO_VERSION' ) ) {
			define( 'MEOWSEO_VERSION', '1.0.0' );
		}
	}

	/**
	 * Property 18: Module List Inclusion - Module list is included in formatted output
	 *
	 * For any formatted log export with one or more entries, the output SHALL include
	 * the list of currently active modules.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_included_in_formatted_output(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the log entry
				$formatted = Log_Formatter::format_for_ai( [ $log_entry ] );

				// Verify module list is included
				$this->assertStringContainsString(
					'Active Modules:',
					$formatted,
					'Formatted output should include "Active Modules:" label'
				);
			}
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list appears in System Information section
	 *
	 * For any formatted log export, the module list SHALL appear in the System Information
	 * section alongside plugin version, WordPress version, and PHP version.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_appears_in_system_information_section(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'ERROR',
					'module' => 'gsc',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the log entry
				$formatted = Log_Formatter::format_for_ai( [ $log_entry ] );

				// Verify System Information section exists
				$this->assertStringContainsString(
					'## System Information',
					$formatted,
					'Formatted output should have System Information section'
				);

				// Verify module list is in the System Information section
				// Extract the System Information section
				preg_match( '/## System Information\n(.*?)(?:## Log Entries|$)/s', $formatted, $matches );
				$this->assertNotEmpty(
					$matches,
					'System Information section should be found'
				);

				$system_info_section = $matches[1];
				$this->assertStringContainsString(
					'Active Modules:',
					$system_info_section,
					'Module list should appear in System Information section'
				);
			}
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list is consistent across multiple calls
	 *
	 * For any formatted log export, calling format_for_ai multiple times with the same
	 * entries SHALL produce output with the same module list (deterministic).
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_consistent_across_multiple_calls(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format three times
				$formatted1 = Log_Formatter::format_for_ai( [ $log_entry ] );
				$formatted2 = Log_Formatter::format_for_ai( [ $log_entry ] );
				$formatted3 = Log_Formatter::format_for_ai( [ $log_entry ] );

				// Extract module list lines
				preg_match( '/- Active Modules:.*/', $formatted1, $matches1 );
				preg_match( '/- Active Modules:.*/', $formatted2, $matches2 );
				preg_match( '/- Active Modules:.*/', $formatted3, $matches3 );

				// Verify module list is identical across calls
				$this->assertEquals(
					$matches1[0],
					$matches2[0],
					'Module list should be consistent (call 1 vs 2)'
				);

				$this->assertEquals(
					$matches2[0],
					$matches3[0],
					'Module list should be consistent (call 2 vs 3)'
				);
			}
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list includes all active modules
	 *
	 * For any formatted log export, the module list SHALL include all currently active
	 * modules from the Options configuration.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_includes_all_active_modules(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 ),
			Generators::choose( 1, 50 )
		)
		->then(
			function ( string $message, int $entry_count ) {
				// Create multiple log entries
				$log_entries = [];
				for ( $i = 0; $i < $entry_count; $i++ ) {
					$log_entries[] = [
						'id' => $i + 1,
						'level' => 'INFO',
						'module' => 'test',
						'message' => $message . ' ' . $i,
						'context' => null,
						'stack_trace' => null,
						'hit_count' => 1,
						'created_at' => '2024-01-15 10:30:45',
					];
				}

				// Format the log entries
				$formatted = Log_Formatter::format_for_ai( $log_entries );

				// Verify module list is present
				$this->assertStringContainsString(
					'Active Modules:',
					$formatted,
					'Module list should be included in formatted output'
				);

				// Extract the module list line
				preg_match( '/- Active Modules:\s*(.*)/', $formatted, $matches );
				$this->assertNotEmpty(
					$matches,
					'Module list line should be found'
				);

				// The module list should be a comma-separated string or empty
				$module_list = $matches[1];
				$this->assertIsString(
					$module_list,
					'Module list should be a string'
				);
			}
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list has correct markdown formatting
	 *
	 * For any formatted log export, the module list SHALL be formatted as a markdown
	 * bullet point with proper label and comma-separated values.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_has_correct_markdown_formatting(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the log entry
				$formatted = Log_Formatter::format_for_ai( [ $log_entry ] );

				// Verify proper markdown formatting with bullet point
				$this->assertMatchesRegularExpression(
					'/- Active Modules:/',
					$formatted,
					'Module list should be formatted as markdown bullet point'
				);

				// Verify the format is "- Active Modules: module1, module2, ..."
				$this->assertMatchesRegularExpression(
					'/- Active Modules:\s*[\w,\s]*/',
					$formatted,
					'Module list should have proper format with modules'
				);
			}
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list appears before log entries
	 *
	 * For any formatted log export, the module list (in System Information section)
	 * SHALL appear before the Log Entries section.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_appears_before_log_entries(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 ),
			Generators::choose( 1, 10 )
		)
		->then(
			function ( string $message, int $entry_count ) {
				// Create multiple log entries
				$log_entries = [];
				for ( $i = 0; $i < $entry_count; $i++ ) {
					$log_entries[] = [
						'id' => $i + 1,
						'level' => 'INFO',
						'module' => 'test',
						'message' => $message . ' ' . $i,
						'context' => null,
						'stack_trace' => null,
						'hit_count' => 1,
						'created_at' => '2024-01-15 10:30:45',
					];
				}

				// Format the log entries
				$formatted = Log_Formatter::format_for_ai( $log_entries );

				// Find positions
				$module_list_pos = strpos( $formatted, 'Active Modules:' );
				$log_entries_pos = strpos( $formatted, '## Log Entries' );

				// Verify module list appears before log entries
				$this->assertNotFalse(
					$module_list_pos,
					'Module list should exist'
				);

				$this->assertNotFalse(
					$log_entries_pos,
					'Log Entries section should exist'
				);

				$this->assertLessThan(
					$log_entries_pos,
					$module_list_pos,
					'Module list should appear before Log Entries section'
				);
			}
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list is included with empty log entries
	 *
	 * For a formatted log export with an empty log entries array, the output SHALL still
	 * include the module list in the System Information section.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_included_with_empty_log_entries(): void {
		// Format with empty log entries
		$formatted = Log_Formatter::format_for_ai( [] );

		// Verify module list is included even with no entries
		$this->assertStringContainsString(
			'Active Modules:',
			$formatted,
			'Module list should be included even with empty log entries'
		);

		// Verify it's in the System Information section
		preg_match( '/## System Information\n(.*?)(?:## Log Entries|$)/s', $formatted, $matches );
		$this->assertNotEmpty(
			$matches,
			'System Information section should be found'
		);

		$system_info_section = $matches[1];
		$this->assertStringContainsString(
			'Active Modules:',
			$system_info_section,
			'Module list should be in System Information section even with empty entries'
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list is included with various log levels
	 *
	 * For formatted log exports with entries of various log levels, the module list
	 * SHALL be included and consistent regardless of the log levels present.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_included_with_various_log_levels(): void {
		$log_levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];

		// Create entries with all log levels
		$log_entries = [];
		foreach ( $log_levels as $index => $level ) {
			$log_entries[] = [
				'id' => $index + 1,
				'level' => $level,
				'module' => 'test',
				'message' => 'Test message for ' . $level,
				'context' => null,
				'stack_trace' => null,
				'hit_count' => 1,
				'created_at' => '2024-01-15 10:30:45',
			];
		}

		// Format the log entries
		$formatted = Log_Formatter::format_for_ai( $log_entries );

		// Verify module list is included
		$this->assertStringContainsString(
			'Active Modules:',
			$formatted,
			'Module list should be included with various log levels'
		);

		// Verify it's in the System Information section
		preg_match( '/## System Information\n(.*?)(?:## Log Entries|$)/s', $formatted, $matches );
		$this->assertNotEmpty(
			$matches,
			'System Information section should be found'
		);

		$system_info_section = $matches[1];
		$this->assertStringContainsString(
			'Active Modules:',
			$system_info_section,
			'Module list should be in System Information section with various log levels'
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list is included with various modules
	 *
	 * For formatted log exports with entries from various modules, the module list
	 * SHALL be included and consistent regardless of the modules present in the entries.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_included_with_various_modules(): void {
		$modules = [ 'gsc', 'sitemap', 'redirects', 'meta', 'schema' ];

		// Create entries from various modules
		$log_entries = [];
		foreach ( $modules as $index => $module ) {
			$log_entries[] = [
				'id' => $index + 1,
				'level' => 'INFO',
				'module' => $module,
				'message' => 'Test message from ' . $module,
				'context' => null,
				'stack_trace' => null,
				'hit_count' => 1,
				'created_at' => '2024-01-15 10:30:45',
			];
		}

		// Format the log entries
		$formatted = Log_Formatter::format_for_ai( $log_entries );

		// Verify module list is included
		$this->assertStringContainsString(
			'Active Modules:',
			$formatted,
			'Module list should be included with various modules in entries'
		);

		// Verify it's in the System Information section
		preg_match( '/## System Information\n(.*?)(?:## Log Entries|$)/s', $formatted, $matches );
		$this->assertNotEmpty(
			$matches,
			'System Information section should be found'
		);

		$system_info_section = $matches[1];
		$this->assertStringContainsString(
			'Active Modules:',
			$system_info_section,
			'Module list should be in System Information section with various modules'
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list header structure is correct
	 *
	 * For any formatted log export, the module list SHALL have the correct header
	 * structure with "Active Modules:" label followed by comma-separated module names.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_header_structure_is_correct(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the log entry
				$formatted = Log_Formatter::format_for_ai( [ $log_entry ] );

				// Verify the structure: "- Active Modules: module1, module2, ..."
				$this->assertMatchesRegularExpression(
					'/- Active Modules:\s*[\w,\s-]*\n/',
					$formatted,
					'Module list should have correct header structure'
				);

				// Extract and verify the module list line
				preg_match( '/- Active Modules:\s*(.*)/', $formatted, $matches );
				$this->assertNotEmpty(
					$matches,
					'Module list line should be found'
				);

				$module_list = trim( $matches[1] );
				// Module list should be either empty or comma-separated values
				if ( ! empty( $module_list ) ) {
					// If not empty, should contain only word characters, hyphens, commas, and spaces
					$this->assertMatchesRegularExpression(
						'/^[\w,\s-]+$/',
						$module_list,
						'Module list should contain only module names, commas, and spaces'
					);
				}
			}
		);
	}

	/**
	 * Property 18: Module List Inclusion - Module list appears after other system info
	 *
	 * For any formatted log export, the module list SHALL appear after the plugin version,
	 * WordPress version, and PHP version in the System Information section.
	 *
	 * **Validates: Requirement 10.2**
	 *
	 * @return void
	 */
	public function test_module_list_appears_after_other_system_info(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'INFO',
					'module' => 'test',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the log entry
				$formatted = Log_Formatter::format_for_ai( [ $log_entry ] );

				// Find positions of system info items
				$plugin_version_pos = strpos( $formatted, '- Plugin Version:' );
				$wordpress_version_pos = strpos( $formatted, '- WordPress Version:' );
				$php_version_pos = strpos( $formatted, '- PHP Version:' );
				$active_modules_pos = strpos( $formatted, '- Active Modules:' );

				// Verify all are present
				$this->assertNotFalse( $plugin_version_pos, 'Plugin version should be present' );
				$this->assertNotFalse( $wordpress_version_pos, 'WordPress version should be present' );
				$this->assertNotFalse( $php_version_pos, 'PHP version should be present' );
				$this->assertNotFalse( $active_modules_pos, 'Active modules should be present' );

				// Verify order: plugin -> wordpress -> php -> modules
				$this->assertLessThan(
					$wordpress_version_pos,
					$plugin_version_pos,
					'Plugin version should appear before WordPress version'
				);

				$this->assertLessThan(
					$php_version_pos,
					$wordpress_version_pos,
					'WordPress version should appear before PHP version'
				);

				$this->assertLessThan(
					$active_modules_pos,
					$php_version_pos,
					'PHP version should appear before Active modules'
				);
			}
		);
	}
}
