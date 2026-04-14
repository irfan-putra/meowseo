<?php
/**
 * Autoloader for MeowSEO plugin classes.
 *
 * Follows WordPress naming convention: class-{name}.php
 *
 * @package MeowSEO
 */

namespace MeowSEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader class.
 *
 * Automatically loads class files based on WordPress naming conventions.
 */
class Autoloader {

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload class files.
	 *
	 * Converts class names to file paths following WordPress conventions:
	 * - MeowSEO\Plugin -> includes/class-plugin.php
	 * - MeowSEO\Helpers\Cache -> includes/helpers/class-cache.php
	 * - MeowSEO\Modules\Meta\Meta -> includes/modules/meta/class-meta.php
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( string $class ): void {
		// Check if the class belongs to our namespace.
		$namespace = 'MeowSEO\\';
		if ( strpos( $class, $namespace ) !== 0 ) {
			return;
		}

		// Remove namespace prefix.
		$class_name = substr( $class, strlen( $namespace ) );

		// Convert namespace separators to directory separators.
		$class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );

		// Convert class name to WordPress file naming convention.
		// Example: Module_Manager -> class-module-manager.php
		$parts = explode( DIRECTORY_SEPARATOR, $class_name );
		$file_name = array_pop( $parts );
		
		// Convert PascalCase or Snake_Case to kebab-case.
		$file_name = self::convert_to_kebab_case( $file_name );
		
		// Determine prefix based on type (interface or class).
		$prefix = 'class-';
		if ( interface_exists( $namespace . str_replace( DIRECTORY_SEPARATOR, '\\', implode( DIRECTORY_SEPARATOR, $parts ) . DIRECTORY_SEPARATOR . $file_name ), false ) === false ) {
			// Check if this might be an interface by checking the original class name.
			$original_file_name = explode( DIRECTORY_SEPARATOR, $class_name );
			$original_file_name = end( $original_file_name );
			if ( strpos( $original_file_name, 'Interface' ) !== false || strpos( $class, '\\Contracts\\' ) !== false ) {
				$prefix = 'interface-';
			}
		}
		
		$file_name = $prefix . $file_name . '.php';

		// Rebuild the path.
		$parts[] = $file_name;
		$relative_path = implode( DIRECTORY_SEPARATOR, $parts );

		// Build full file path.
		$file_path = \MEOWSEO_PATH . 'includes' . DIRECTORY_SEPARATOR . strtolower( $relative_path );

		// Load the file if it exists.
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}

	/**
	 * Convert class name to kebab-case.
	 *
	 * Handles both PascalCase and Snake_Case.
	 *
	 * @param string $name Class name.
	 * @return string Kebab-case name.
	 */
	private static function convert_to_kebab_case( string $name ): string {
		// First, replace underscores with hyphens.
		$name = str_replace( '_', '-', $name );

		// Then, convert PascalCase to kebab-case.
		// Insert a hyphen before uppercase letters and convert to lowercase.
		$name = preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $name );
		$name = strtolower( $name );

		return $name;
	}
}
