<?php
/**
 * Abstract Module Base Class
 *
 * Provides common functionality for all MeowSEO modules.
 *
 * @package MeowSEO
 * @subpackage Modules
 */

namespace MeowSEO\Modules;

/**
 * Abstract Module Class
 *
 * Base class for all MeowSEO modules. Provides common initialization,
 * activation, and deactivation logic.
 */
abstract class Abstract_Module implements Module {

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected string $version = '1.0.0';

	/**
	 * Whether the module is enabled
	 *
	 * @var bool
	 */
	protected bool $enabled = true;

	/**
	 * Constructor
	 *
	 * @param string $name The module name.
	 */
	public function __construct( string $name ) {
		$this->name = $name;
	}

	/**
	 * Initialize the module
	 *
	 * @return void
	 */
	public function init(): void {
		// Override in child classes to add module-specific initialization
	}

	/**
	 * Get the module name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the module version
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Check if the module is enabled
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * Activate the module
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->enabled = true;
		// Override in child classes to add module-specific activation logic
	}

	/**
	 * Deactivate the module
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->enabled = false;
		// Override in child classes to add module-specific deactivation logic
	}

	/**
	 * Set module version
	 *
	 * @param string $version The module version.
	 * @return void
	 */
	protected function set_version( string $version ): void {
		$this->version = $version;
	}
}
