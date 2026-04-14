<?php
/**
 * Plugin Name: MeowSEO
 * Plugin URI: https://github.com/akbarbahaulloh/meowseo
 * Description: A modular WordPress SEO plugin optimized for Google Discover, AI Overviews, and headless WordPress deployments.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Your Name
 * Author URI: https://puskomedia.id
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: meowseo
 * Domain Path: /languages
 *
 * @package MeowSEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'MEOWSEO_VERSION', '1.0.0' );
define( 'MEOWSEO_FILE', __FILE__ );
define( 'MEOWSEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEOWSEO_URL', plugin_dir_url( __FILE__ ) );
define( 'MEOWSEO_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'build/' );

// PSR-4 autoloader for MeowSEO namespace.
spl_autoload_register( function ( $class ) {
	// Only handle MeowSEO namespace.
	if ( 0 !== strpos( $class, 'MeowSEO\\' ) ) {
		return;
	}

	// Remove namespace prefix.
	$class = str_replace( 'MeowSEO\\', '', $class );

	// Convert namespace separators to directory separators.
	$class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );

	// Convert class name to file name (WordPress convention: class-{name}.php).
	$parts = explode( DIRECTORY_SEPARATOR, $class );
	$last_part = array_pop( $parts );
	
	// Convert CamelCase to kebab-case for file name.
	$file_name = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $last_part ) );
	$file_name = 'class-' . $file_name . '.php';
	
	// Rebuild path.
	$parts[] = $file_name;
	$file = MEOWSEO_PATH . 'includes' . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $parts );

	// Load file if it exists.
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// Register activation, deactivation, and uninstall hooks.
register_activation_hook( __FILE__, array( 'MeowSEO\Core\Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MeowSEO\Core\Install', 'deactivate' ) );

// Initialize the plugin on plugins_loaded hook at priority 10.
add_action( 'plugins_loaded', function() {
	\MeowSEO\Core\MeowSEO::get_instance()->init();
}, 10 );
