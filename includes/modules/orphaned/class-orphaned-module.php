<?php
/**
 * Orphaned Module class.
 *
 * Bootstrap class for the Orphaned Detector module.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Orphaned;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orphaned Module class.
 *
 * Initializes the Orphaned Detector module with all its components.
 */
class Orphaned_Module implements Module {

	/**
	 * Orphaned Detector instance.
	 *
	 * @var Orphaned_Detector
	 */
	private Orphaned_Detector $detector;

	/**
	 * Orphaned Admin instance.
	 *
	 * @var Orphaned_Admin
	 */
	private Orphaned_Admin $admin;

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		// Initialize components.
		$this->detector = new Orphaned_Detector( $options );
		$this->admin    = new Orphaned_Admin( $this->detector, $options );
	}

	/**
	 * Boot the module.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Boot detector.
		$this->detector->boot();

		// Boot admin interface.
		if ( is_admin() ) {
			$this->admin->boot();
		}
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'orphaned';
	}

	/**
	 * Get detector instance.
	 *
	 * @return Orphaned_Detector Detector instance.
	 */
	public function get_detector(): Orphaned_Detector {
		return $this->detector;
	}

	/**
	 * Get admin instance.
	 *
	 * @return Orphaned_Admin Admin instance.
	 */
	public function get_admin(): Orphaned_Admin {
		return $this->admin;
	}
}
