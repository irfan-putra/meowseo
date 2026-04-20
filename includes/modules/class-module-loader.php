<?php
/**
 * Module Loader
 *
 * Loads and initializes all MeowSEO modules.
 *
 * @package MeowSEO
 * @subpackage Modules
 */

namespace MeowSEO\Modules;

/**
 * Module Loader Class
 *
 * Responsible for loading module files and initializing the module system.
 */
class Module_Loader {

	/**
	 * Module registry
	 *
	 * @var Module_Registry
	 */
	private Module_Registry $registry;

	/**
	 * Modules directory path
	 *
	 * @var string
	 */
	private string $modules_dir;

	/**
	 * Constructor
	 *
	 * @param string $modules_dir The path to the modules directory.
	 */
	public function __construct( string $modules_dir ) {
		$this->modules_dir = $modules_dir;
		$this->registry    = new Module_Registry();
	}

	/**
	 * Load all modules
	 *
	 * Includes module files and initializes the module registry.
	 *
	 * @return Module_Registry The module registry.
	 */
	public function load(): Module_Registry {
		// Load autoloader
		require_once $this->modules_dir . '/autoloader.php';

		// Initialize registry
		$this->registry->init_all();

		return $this->registry;
	}

	/**
	 * Get the module registry
	 *
	 * @return Module_Registry
	 */
	public function get_registry(): Module_Registry {
		return $this->registry;
	}
}
