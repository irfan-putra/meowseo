<?php
/**
 * Uninstall script.
 *
 * Fired when the plugin is uninstalled.
 *
 * @package MeowSEO
 */

// Exit if accessed directly or not uninstalling.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the autoloader and Installer class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-autoloader.php';
MeowSEO\Autoloader::register();

// Run uninstall.
MeowSEO\Installer::uninstall();
