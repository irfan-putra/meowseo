<?php
/**
 * Main Plugin class.
 *
 * Singleton that holds references to Module_Manager, Options, and Installer.
 *
 * @package MeowSEO
 */

namespace MeowSEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin class.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Module Manager instance.
	 *
	 * @var Module_Manager|null
	 */
	private ?Module_Manager $module_manager = null;

	/**
	 * Options instance.
	 *
	 * @var Options|null
	 */
	private ?Options $options = null;

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// Initialize Options.
		$this->options = new Options();
	}

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the plugin.
	 *
	 * Initializes Module_Manager and triggers module loading.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Initialize Module_Manager.
		$this->module_manager = new Module_Manager( $this->options );

		// Boot all enabled modules.
		$this->module_manager->boot();
	}

	/**
	 * Get Module_Manager instance.
	 *
	 * @return Module_Manager|null
	 */
	public function get_module_manager(): ?Module_Manager {
		return $this->module_manager;
	}

	/**
	 * Get Options instance.
	 *
	 * @return Options
	 */
	public function get_options(): Options {
		return $this->options;
	}

	/**
	 * Prevent cloning of the instance.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the instance.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
