<?php
/**
 * Uninstall script for MeowSEO plugin.
 *
 * Handles plugin data cleanup when the plugin is uninstalled.
 * Only runs when the plugin is deleted via WordPress admin.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

// Exit if accessed directly or not in uninstall context.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the autoloader and Installer class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-autoloader.php';
MeowSEO\Autoloader::register();

// Call the uninstall method.
MeowSEO\Installer::uninstall();
