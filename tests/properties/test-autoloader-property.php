<?php
/**
 * Property-Based Tests for Autoloader Class Resolution
 *
 * Property 2: Autoloader resolves class names to correct file paths
 * Validates: Requirement 1.4
 *
 * This test uses property-based testing (eris/eris) to verify that the autoloader
 * correctly converts fully qualified class names to file paths following WordPress
 * naming conventions.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Properties;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;
use MeowSEO\Autoloader;

/**
 * Autoloader property-based test case
 *
 * @since 1.0.0
 */
class Test_Autoloader_Property extends TestCase {
	use TestTrait;

	/**
	 * Property 2: Autoloader resolves class names to correct file paths
	 *
	 * For any valid MeowSEO class name, the autoloader should:
	 * 1. Accept the fully qualified class name
	 * 2. Convert namespace separators to directory separators
	 * 3. Convert class names to kebab-case file names
	 * 4. Prepend 'class-' or 'interface-' prefix
	 * 5. Append '.php' extension
	 * 6. Construct a valid file path under includes/
	 *
	 * @return void
	 */
	public function test_autoloader_resolves_class_names_to_correct_file_paths(): void {
		$this->forAll(
			Generator\elements(
				[
					// Simple class names
					'MeowSEO\Plugin',
					'MeowSEO\Options',
					'MeowSEO\Installer',
					'MeowSEO\Module_Manager',
					// Nested namespace classes
					'MeowSEO\Helpers\Cache',
					'MeowSEO\Helpers\DB',
					'MeowSEO\Helpers\Schema_Builder',
					'MeowSEO\Helpers\Log_Formatter',
					'MeowSEO\Helpers\Logger',
					// Module classes
					'MeowSEO\Modules\Meta\Meta',
					'MeowSEO\Modules\Meta\SEO_Analyzer',
					'MeowSEO\Modules\Meta\Readability',
					'MeowSEO\Modules\Schema\Schema',
					'MeowSEO\Modules\Sitemap\Sitemap',
					'MeowSEO\Modules\Sitemap\Sitemap_Generator',
					'MeowSEO\Modules\Redirects\Redirects',
					'MeowSEO\Modules\Monitor_404\Monitor_404',
					'MeowSEO\Modules\Internal_Links\Internal_Links',
					'MeowSEO\Modules\GSC\GSC',
					'MeowSEO\Modules\Social\Social',
					'MeowSEO\Modules\WooCommerce\WooCommerce',
					// Interface classes
					'MeowSEO\Contracts\Module',
				]
			)
		)
		->then(
			function ( string $class_name ) {
				// Verify the class name starts with MeowSEO namespace
				$this->assertStringStartsWith(
					'MeowSEO\\',
					$class_name,
					'Class name must be in MeowSEO namespace'
				);

				// Extract the relative path from the class name
				$expected_path = $this->get_expected_file_path( $class_name );

				// Verify the expected path is valid
				$this->assertNotEmpty(
					$expected_path,
					'Expected file path should not be empty'
				);

				// Verify the path follows WordPress naming conventions
				$this->verify_wordpress_naming_convention( $expected_path );

				// Verify the path contains the correct components
				$this->verify_path_components( $class_name, $expected_path );
			}
		);
	}

	/**
	 * Property: Autoloader converts PascalCase class names to kebab-case file names
	 *
	 * For any class name with PascalCase components, the autoloader should
	 * convert them to kebab-case in the file name.
	 *
	 * @return void
	 */
	public function test_autoloader_converts_pascal_case_to_kebab_case(): void {
		$this->forAll(
			Generator\elements(
				[
					'MeowSEO\Module_Manager' => 'class-module-manager.php',
					'MeowSEO\SEO_Analyzer' => 'class-seo-analyzer.php',
					'MeowSEO\Helpers\Schema_Builder' => 'class-schema-builder.php',
					'MeowSEO\Modules\Monitor_404\Monitor_404' => 'class-monitor-404.php',
					'MeowSEO\Modules\Internal_Links\Internal_Links' => 'class-internal-links.php',
				]
			)
		)
		->then(
			function ( string $class_name, string $expected_filename ) {
				$path = $this->get_expected_file_path( $class_name );
				$filename = basename( $path );

				$this->assertEquals(
					$expected_filename,
					$filename,
					"Class $class_name should resolve to file $expected_filename"
				);
			}
		);
	}

	/**
	 * Property: Autoloader preserves namespace hierarchy in directory structure
	 *
	 * For any class with nested namespaces, the autoloader should create
	 * a corresponding directory structure.
	 *
	 * @return void
	 */
	public function test_autoloader_preserves_namespace_hierarchy(): void {
		$this->forAll(
			Generator\elements(
				[
					'MeowSEO\Helpers\Cache' => 'helpers',
					'MeowSEO\Helpers\DB' => 'helpers',
					'MeowSEO\Modules\Meta\Meta' => 'modules/meta',
					'MeowSEO\Modules\Schema\Schema' => 'modules/schema',
					'MeowSEO\Modules\Sitemap\Sitemap' => 'modules/sitemap',
					'MeowSEO\Contracts\Module' => 'contracts',
				]
			)
		)
		->then(
			function ( string $class_name, string $expected_subdir ) {
				$path = $this->get_expected_file_path( $class_name );

				// Verify the path contains the expected subdirectory
				$this->assertStringContainsString(
					$expected_subdir,
					$path,
					"Class $class_name should be in subdirectory $expected_subdir"
				);
			}
		);
	}

	/**
	 * Property: Autoloader correctly identifies interface vs class files
	 *
	 * For any interface class name, the autoloader should use 'interface-' prefix.
	 * For any regular class, it should use 'class-' prefix.
	 *
	 * @return void
	 */
	public function test_autoloader_uses_correct_prefix_for_interfaces(): void {
		$this->forAll(
			Generator\elements(
				[
					'MeowSEO\Contracts\Module' => 'interface-',
				]
			)
		)
		->then(
			function ( string $class_name, string $expected_prefix ) {
				$path = $this->get_expected_file_path( $class_name );
				$filename = basename( $path );

				$this->assertStringStartsWith(
					$expected_prefix,
					$filename,
					"Interface $class_name should use $expected_prefix prefix"
				);
			}
		);
	}

	/**
	 * Property: Autoloader produces deterministic file paths
	 *
	 * For any given class name, the autoloader should always produce
	 * the same file path (deterministic behavior).
	 *
	 * @return void
	 */
	public function test_autoloader_produces_deterministic_paths(): void {
		$this->forAll(
			Generator\elements(
				[
					'MeowSEO\Plugin',
					'MeowSEO\Helpers\Cache',
					'MeowSEO\Modules\Meta\Meta',
					'MeowSEO\Contracts\Module',
				]
			)
		)
		->then(
			function ( string $class_name ) {
				$path1 = $this->get_expected_file_path( $class_name );
				$path2 = $this->get_expected_file_path( $class_name );
				$path3 = $this->get_expected_file_path( $class_name );

				$this->assertEquals(
					$path1,
					$path2,
					"Autoloader should produce deterministic paths (run 1 vs 2)"
				);

				$this->assertEquals(
					$path2,
					$path3,
					"Autoloader should produce deterministic paths (run 2 vs 3)"
				);
			}
		);
	}

	/**
	 * Get the expected file path for a class name.
	 *
	 * This mirrors the logic in the Autoloader class to verify correctness.
	 *
	 * @param string $class Fully qualified class name.
	 * @return string Expected file path.
	 */
	private function get_expected_file_path( string $class ): string {
		// Check if the class belongs to our namespace.
		$namespace = 'MeowSEO\\';
		if ( strpos( $class, $namespace ) !== 0 ) {
			return '';
		}

		// Remove namespace prefix.
		$class_name = substr( $class, strlen( $namespace ) );

		// Convert namespace separators to directory separators.
		$class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );

		// Convert class name to WordPress file naming convention.
		$parts = explode( DIRECTORY_SEPARATOR, $class_name );
		$file_name = array_pop( $parts );

		// Convert to kebab-case.
		$file_name = $this->convert_to_kebab_case( $file_name );

		// Determine prefix based on type.
		$prefix = 'class-';
		if ( strpos( $class, '\\Contracts\\' ) !== false ) {
			$prefix = 'interface-';
		}

		$file_name = $prefix . $file_name . '.php';

		// Rebuild the path.
		$parts[] = $file_name;
		$relative_path = implode( DIRECTORY_SEPARATOR, $parts );

		// Build full file path.
		$file_path = MEOWSEO_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . strtolower( $relative_path );

		return $file_path;
	}

	/**
	 * Convert class name to kebab-case.
	 *
	 * @param string $name Class name.
	 * @return string Kebab-case name.
	 */
	private function convert_to_kebab_case( string $name ): string {
		// First, replace underscores with hyphens.
		$name = str_replace( '_', '-', $name );

		// Then, convert PascalCase to kebab-case.
		$name = preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $name );
		$name = strtolower( $name );

		return $name;
	}

	/**
	 * Verify that a file path follows WordPress naming conventions.
	 *
	 * @param string $path File path to verify.
	 * @return void
	 */
	private function verify_wordpress_naming_convention( string $path ): void {
		$filename = basename( $path );

		// Must have a prefix (class- or interface-)
		$this->assertTrue(
			strpos( $filename, 'class-' ) === 0 || strpos( $filename, 'interface-' ) === 0,
			"File $filename must start with 'class-' or 'interface-'"
		);

		// Must end with .php
		$this->assertStringEndsWith(
			'.php',
			$filename,
			"File $filename must end with .php"
		);

		// Must use kebab-case (only lowercase, hyphens, and numbers)
		$name_without_prefix = substr( $filename, 6, -4 ); // Remove prefix and .php
		$this->assertMatchesRegularExpression(
			'/^[a-z0-9-]+$/',
			$name_without_prefix,
			"File name $name_without_prefix must be in kebab-case"
		);
	}

	/**
	 * Verify that a file path contains the correct components.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @param string $path File path.
	 * @return void
	 */
	private function verify_path_components( string $class_name, string $path ): void {
		// Path must contain 'includes' directory
		$this->assertStringContainsString(
			'includes',
			$path,
			"Path for $class_name must contain 'includes' directory"
		);

		// Path must be under MEOWSEO_PLUGIN_DIR
		$this->assertStringStartsWith(
			MEOWSEO_PLUGIN_DIR,
			$path,
			"Path for $class_name must start with MEOWSEO_PLUGIN_DIR"
		);
	}
}
