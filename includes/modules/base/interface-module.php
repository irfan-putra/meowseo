<?php
/**
 * Base Module Interface
 *
 * Defines the contract for all MeowSEO modules to ensure consistent initialization
 * and lifecycle management.
 *
 * @package MeowSEO
 * @subpackage Modules
 */

namespace MeowSEO\Modules;

/**
 * Module Interface
 *
 * All MeowSEO modules must implement this interface to ensure consistent
 * initialization, activation, and deactivation behavior.
 */
interface Module {

	/**
	 * Initialize the module
	 *
	 * Called when the module is first loaded. Should register hooks, set up
	 * database tables, and perform other initialization tasks.
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Get the module name
	 *
	 * @return string The module identifier (e.g., 'role-manager', 'multilingual')
	 */
	public function get_name(): string;

	/**
	 * Get the module version
	 *
	 * @return string The module version (e.g., '1.0.0')
	 */
	public function get_version(): string;

	/**
	 * Check if the module is enabled
	 *
	 * @return bool True if the module is enabled, false otherwise
	 */
	public function is_enabled(): bool;

	/**
	 * Activate the module
	 *
	 * Called when the module is activated. Should perform any necessary setup.
	 *
	 * @return void
	 */
	public function activate(): void;

	/**
	 * Deactivate the module
	 *
	 * Called when the module is deactivated. Should perform any necessary cleanup.
	 *
	 * @return void
	 */
	public function deactivate(): void;
}
