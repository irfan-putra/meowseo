<?php
/**
 * Locations Module
 *
 * Main module class for managing location custom post type and related functionality.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Locations;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locations_Module class
 *
 * Initializes and manages the Location CPT module with all subcomponents.
 *
 * @since 1.0.0
 */
class Locations_Module implements Module {

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Location_CPT instance
	 *
	 * @since 1.0.0
	 * @var Location_CPT
	 */
	private Location_CPT $location_cpt;

	/**
	 * Location_Shortcodes instance
	 *
	 * @since 1.0.0
	 * @var Location_Shortcodes
	 */
	private Location_Shortcodes $shortcodes;

	/**
	 * Location_KML_Exporter instance
	 *
	 * @since 1.0.0
	 * @var Location_KML_Exporter
	 */
	private Location_KML_Exporter $kml_exporter;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->location_cpt = new Location_CPT( $options );
		$this->shortcodes = new Location_Shortcodes( $options );
		$this->kml_exporter = new Location_KML_Exporter( $options );
	}

	/**
	 * Get module ID
	 *
	 * @since 1.0.0
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'locations';
	}

	/**
	 * Boot the module
	 *
	 * Initializes all subcomponents.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		$this->location_cpt->boot();
		$this->shortcodes->boot();
		$this->kml_exporter->boot();
	}

	/**
	 * Get Location_CPT instance
	 *
	 * @since 1.0.0
	 * @return Location_CPT Location_CPT instance.
	 */
	public function get_location_cpt(): Location_CPT {
		return $this->location_cpt;
	}

	/**
	 * Get Location_Shortcodes instance
	 *
	 * @since 1.0.0
	 * @return Location_Shortcodes Location_Shortcodes instance.
	 */
	public function get_shortcodes(): Location_Shortcodes {
		return $this->shortcodes;
	}

	/**
	 * Get Location_KML_Exporter instance
	 *
	 * @since 1.0.0
	 * @return Location_KML_Exporter Location_KML_Exporter instance.
	 */
	public function get_kml_exporter(): Location_KML_Exporter {
		return $this->kml_exporter;
	}
}
