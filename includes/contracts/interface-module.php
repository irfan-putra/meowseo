<?php
/**
 * Module interface.
 *
 * All plugin modules must implement this interface.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Contracts;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module interface.
 */
interface Module {

	/**
	 * Boot the module.
	 *
	 * Register hooks and initialize module functionality.
	 *
	 * @return void
	 */
	public function boot(): void;

	/**
	 * Get module ID.
	 *
	 * @return string Module ID (e.g., 'meta', 'sitemap').
	 */
	public function get_id(): string;
}
