<?php
/**
 * Property-Based Tests for System Info Inclusion
 *
 * Property 17: System Info Inclusion
 * Validates: Requirement 10.1
 *
 * This test uses property-based testing (eris/eris) to verify that for any formatted
 * log export, the output SHALL include plugin version, WordPress version, and PHP version.
 * This property validates that system context information is reliably included in all
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
 * System Info Inclusion property-based test case
 *
 * @since 1.0.0
 */
class Property17SystemInfoInclusionTest extends TestCase {
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
	 * Property 17: System Info Inclusion - Plugin version included
	 *
	 * For any formatted log export with one or more entries, the output SHALL include
	 * the plugin version in the System Information section.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_includes_plugin_version(): void {
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

				// Verify plugin version is included
				$this->assertStringContainsString(
					'Plugin Version:',
					$formatted,
					'Formatted output should include "Plugin Version:" label'
				);

				// Verify the version value is present (should be MEOWSEO_VERSION)
				$this->assertStringContainsString(
					'1.0.0',
					$formatted,
					'Formatted output should include the plugin version value'
				);
			}
		);
	}

	/**
	 * Property 17: System Info Inclusion - WordPress version included
	 *
	 * For any formatted log export with one or more entries, the output SHALL include
	 * the WordPress version in the System Information section.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_includes_wordpress_version(): void {
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

				// Verify WordPress version is included
				$this->assertStringContainsString(
					'WordPress Version:',
					$formatted,
					'Formatted output should include "WordPress Version:" label'
				);

				// Verify the version value is present (get_bloginfo returns a version)
				$this->assertMatchesRegularExpression(
					'/WordPress Version:\s+[\d.]+/',
					$formatted,
					'Formatted output should include WordPress version value'
				);
			}
		);
	}

	/**
	 * Property 17: System Info Inclusion - PHP version included
	 *
	 * For any formatted log export with one or more entries, the output SHALL include
	 * the PHP version in the System Information section.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_includes_php_version(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 )
		)
		->then(
			function ( string $message ) {
				// Create a log entry
				$log_entry = [
					'id' => 1,
					'level' => 'WARNING',
					'module' => 'sitemap',
					'message' => $message,
					'context' => null,
					'stack_trace' => null,
					'hit_count' => 1,
					'created_at' => '2024-01-15 10:30:45',
				];

				// Format the log entry
				$formatted = Log_Formatter::format_for_ai( [ $log_entry ] );

				// Verify PHP version is included
				$this->assertStringContainsString(
					'PHP Version:',
					$formatted,
					'Formatted output should include "PHP Version:" label'
				);

				// Verify the version value is present (phpversion() returns a version)
				$this->assertMatchesRegularExpression(
					'/PHP Version:\s+[\d.]+/',
					$formatted,
					'Formatted output should include PHP version value'
				);
			}
		);
	}

	/**
	 * Property 17: System Info Inclusion - All three versions in single export
	 *
	 * For any formatted log export with one or more entries, the output SHALL include
	 * all three system versions (plugin, WordPress, PHP) in the System Information section.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_includes_all_three_versions(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9', 1, 50 ),
			Generators::choose( 1, 100 )
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

				// Verify all three versions are included
				$this->assertStringContainsString(
					'Plugin Version:',
					$formatted,
					'Formatted output should include plugin version'
				);

				$this->assertStringContainsString(
					'WordPress Version:',
					$formatted,
					'Formatted output should include WordPress version'
				);

				$this->assertStringContainsString(
					'PHP Version:',
					$formatted,
					'Formatted output should include PHP version'
				);

				// Verify they appear in the System Information section
				$this->assertStringContainsString(
					'## System Information',
					$formatted,
					'Formatted output should have System Information section'
				);

				// Verify the section appears before log entries
				$system_info_pos = strpos( $formatted, '## System Information' );
				$log_entries_pos = strpos( $formatted, '## Log Entries' );
				$this->assertLessThan(
					$log_entries_pos,
					$system_info_pos,
					'System Information section should appear before Log Entries section'
				);
			}
		);
	}

	/**
	 * Property 17: System Info Inclusion - With empty log entries
	 *
	 * For a formatted log export with an empty log entries array, the output SHALL still
	 * include the system information section with all three versions.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_included_with_empty_entries(): void {
		// Format with empty log entries
		$formatted = Log_Formatter::format_for_ai( [] );

		// Verify all three versions are included even with no entries
		$this->assertStringContainsString(
			'Plugin Version:',
			$formatted,
			'Formatted output should include plugin version even with empty entries'
		);

		$this->assertStringContainsString(
			'WordPress Version:',
			$formatted,
			'Formatted output should include WordPress version even with empty entries'
		);

		$this->assertStringContainsString(
			'PHP Version:',
			$formatted,
			'Formatted output should include PHP version even with empty entries'
		);

		// Verify System Information section exists
		$this->assertStringContainsString(
			'## System Information',
			$formatted,
			'Formatted output should have System Information section even with empty entries'
		);
	}

	/**
	 * Property 17: System Info Inclusion - Consistent across multiple calls
	 *
	 * For any formatted log export, calling format_for_ai multiple times with the same
	 * entries SHALL produce output with the same system information (deterministic).
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_consistent_across_calls(): void {
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

				// Extract system info sections
				preg_match( '/## System Information.*?## Log Entries/s', $formatted1, $matches1 );
				preg_match( '/## System Information.*?## Log Entries/s', $formatted2, $matches2 );
				preg_match( '/## System Information.*?## Log Entries/s', $formatted3, $matches3 );

				// Verify system info is identical across calls
				$this->assertEquals(
					$matches1[0],
					$matches2[0],
					'System information should be consistent (call 1 vs 2)'
				);

				$this->assertEquals(
					$matches2[0],
					$matches3[0],
					'System information should be consistent (call 2 vs 3)'
				);
			}
		);
	}

	/**
	 * Property 17: System Info Inclusion - Correct format structure
	 *
	 * For any formatted log export, the system information SHALL be formatted as a list
	 * with proper markdown bullet points and labels.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_has_correct_format(): void {
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

				// Verify proper markdown formatting with bullet points
				$this->assertMatchesRegularExpression(
					'/- Plugin Version:/',
					$formatted,
					'Plugin version should be formatted as markdown bullet point'
				);

				$this->assertMatchesRegularExpression(
					'/- WordPress Version:/',
					$formatted,
					'WordPress version should be formatted as markdown bullet point'
				);

				$this->assertMatchesRegularExpression(
					'/- PHP Version:/',
					$formatted,
					'PHP version should be formatted as markdown bullet point'
				);

				// Verify versions have values after the colon
				$this->assertMatchesRegularExpression(
					'/- Plugin Version:\s+\S+/',
					$formatted,
					'Plugin version should have a value'
				);

				$this->assertMatchesRegularExpression(
					'/- WordPress Version:\s+\S+/',
					$formatted,
					'WordPress version should have a value'
				);

				$this->assertMatchesRegularExpression(
					'/- PHP Version:\s+\S+/',
					$formatted,
					'PHP version should have a value'
				);
			}
		);
	}

	/**
	 * Property 17: System Info Inclusion - With various log levels
	 *
	 * For formatted log exports with entries of various log levels, the system information
	 * SHALL be included and consistent regardless of the log levels present.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_included_with_various_log_levels(): void {
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

		// Verify all three versions are included
		$this->assertStringContainsString(
			'Plugin Version:',
			$formatted,
			'System info should be included with various log levels'
		);

		$this->assertStringContainsString(
			'WordPress Version:',
			$formatted,
			'System info should be included with various log levels'
		);

		$this->assertStringContainsString(
			'PHP Version:',
			$formatted,
			'System info should be included with various log levels'
		);
	}

	/**
	 * Property 17: System Info Inclusion - With various modules
	 *
	 * For formatted log exports with entries from various modules, the system information
	 * SHALL be included and consistent regardless of the modules present.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_included_with_various_modules(): void {
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

		// Verify all three versions are included
		$this->assertStringContainsString(
			'Plugin Version:',
			$formatted,
			'System info should be included with various modules'
		);

		$this->assertStringContainsString(
			'WordPress Version:',
			$formatted,
			'System info should be included with various modules'
		);

		$this->assertStringContainsString(
			'PHP Version:',
			$formatted,
			'System info should be included with various modules'
		);
	}

	/**
	 * Property 17: System Info Inclusion - System info appears before entries
	 *
	 * For any formatted log export, the System Information section SHALL appear before
	 * the Log Entries section in the output.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_appears_before_entries(): void {
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

				// Find positions of sections
				$system_info_pos = strpos( $formatted, '## System Information' );
				$log_entries_pos = strpos( $formatted, '## Log Entries' );

				// Verify system info appears before log entries
				$this->assertNotFalse(
					$system_info_pos,
					'System Information section should exist'
				);

				$this->assertNotFalse(
					$log_entries_pos,
					'Log Entries section should exist'
				);

				$this->assertLessThan(
					$log_entries_pos,
					$system_info_pos,
					'System Information section should appear before Log Entries section'
				);
			}
		);
	}

	/**
	 * Property 17: System Info Inclusion - Header structure
	 *
	 * For any formatted log export, the output SHALL start with the title and include
	 * the System Information section as the first major section.
	 *
	 * **Validates: Requirement 10.1**
	 *
	 * @return void
	 */
	public function test_system_info_header_structure(): void {
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

				// Verify structure: title -> system info -> entries
				$this->assertStringStartsWith(
					'# MeowSEO Debug Log Export',
					$formatted,
					'Output should start with title'
				);

				// Find the order of sections
				$title_pos = strpos( $formatted, '# MeowSEO Debug Log Export' );
				$system_info_pos = strpos( $formatted, '## System Information' );
				$log_entries_pos = strpos( $formatted, '## Log Entries' );

				// Verify order
				$this->assertLessThan(
					$system_info_pos,
					$title_pos,
					'Title should appear before System Information'
				);

				$this->assertLessThan(
					$log_entries_pos,
					$system_info_pos,
					'System Information should appear before Log Entries'
				);
			}
		);
	}
}
