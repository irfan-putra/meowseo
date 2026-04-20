<?php
/**
 * Module Registry
 *
 * Manages registration, initialization, and lifecycle of all MeowSEO modules.
 *
 * @package MeowSEO
 * @subpackage Modules
 */

namespace MeowSEO\Modules;

/**
 * Module Registry Class
 *
 * Provides centralized management for all MeowSEO modules including
 * registration, initialization, activation, and deactivation.
 */
class Module_Registry {

	/**
	 * Registered modules
	 *
	 * @var Module[]
	 */
	private array $modules = [];

	/**
	 * Register a module
	 *
	 * @param Module $module The module to register.
	 * @return void
	 */
	public function register( Module $module ): void {
		$this->modules[ $module->get_name() ] = $module;
	}

	/**
	 * Get a registered module
	 *
	 * @param string $name The module name.
	 * @return Module|null The module, or null if not found.
	 */
	public function get( string $name ): ?Module {
		return $this->modules[ $name ] ?? null;
	}

	/**
	 * Get all registered modules
	 *
	 * @return Module[]
	 */
	public function get_all(): array {
		return $this->modules;
	}

	/**
	 * Initialize all registered modules
	 *
	 * @return void
	 */
	public function init_all(): void {
		foreach ( $this->modules as $module ) {
			if ( $module->is_enabled() ) {
				$module->init();
			}
		}
	}

	/**
	 * Activate all registered modules
	 *
	 * @return void
	 */
	public function activate_all(): void {
		foreach ( $this->modules as $module ) {
			$module->activate();
		}
	}

	/**
	 * Deactivate all registered modules
	 *
	 * @return void
	 */
	public function deactivate_all(): void {
		foreach ( $this->modules as $module ) {
			$module->deactivate();
		}
	}

	/**
	 * Check if a module is registered
	 *
	 * @param string $name The module name.
	 * @return bool
	 */
	public function has( string $name ): bool {
		return isset( $this->modules[ $name ] );
	}

	/**
	 * Get count of registered modules
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->modules );
	}
}
