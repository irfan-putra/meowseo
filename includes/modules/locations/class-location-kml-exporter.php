<?php
/**
 * Location KML Exporter
 *
 * Generates KML export files for locations.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Locations;

use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Location_KML_Exporter class
 *
 * Generates KML XML files containing all locations for Google Maps import.
 * Requirements 4.9, 4.10: Generate KML export with valid XML format.
 *
 * @since 1.0.0
 */
class Location_KML_Exporter {

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
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->location_cpt = new Location_CPT( $options );
	}

	/**
	 * Boot the module
	 *
	 * Registers REST endpoint and admin hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'add_export_button' ) );
		add_action( 'admin_init', array( $this, 'handle_kml_export' ) );
	}

	/**
	 * Generate KML XML
	 *
	 * Requirements 4.9, 4.10: Generate valid KML XML document with Placemark elements.
	 *
	 * @since 1.0.0
	 * @return string KML XML content.
	 */
	public function generate_kml(): string {
		$locations = $this->location_cpt->get_all_locations();

		// Start KML document.
		$kml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$kml .= '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";
		$kml .= '<Document>' . "\n";
		$kml .= '<name>' . esc_xml( get_bloginfo( 'name' ) ) . ' - ' . esc_html__( 'Business Locations', 'meowseo' ) . '</name>' . "\n";
		$kml .= '<description>' . esc_xml( get_bloginfo( 'description' ) ) . '</description>' . "\n";

		// Add placemarks for each location.
		foreach ( $locations as $location ) {
			$location_data = $this->location_cpt->get_location_data( $location->ID );

			// Skip locations without coordinates.
			if ( empty( $location_data['latitude'] ) || empty( $location_data['longitude'] ) ) {
				continue;
			}

			$kml .= $this->generate_placemark( $location, $location_data );
		}

		$kml .= '</Document>' . "\n";
		$kml .= '</kml>';

		return $kml;
	}

	/**
	 * Generate KML Placemark element
	 *
	 * @since 1.0.0
	 * @param \WP_Post $location Location post.
	 * @param array    $location_data Location data.
	 * @return string KML Placemark XML.
	 */
	private function generate_placemark( \WP_Post $location, array $location_data ): string {
		$placemark = '<Placemark>' . "\n";

		// Name.
		$name = $location_data['business_name'] ?: $location->post_title;
		$placemark .= '<name>' . esc_xml( $name ) . '</name>' . "\n";

		// Description with address and contact info.
		$description = $this->build_placemark_description( $location_data );
		$placemark .= '<description>' . esc_xml( $description ) . '</description>' . "\n";

		// Point with coordinates (longitude, latitude, altitude).
		$placemark .= '<Point>' . "\n";
		$placemark .= '<coordinates>' . floatval( $location_data['longitude'] ) . ',' . floatval( $location_data['latitude'] ) . ',0</coordinates>' . "\n";
		$placemark .= '</Point>' . "\n";

		$placemark .= '</Placemark>' . "\n";

		return $placemark;
	}

	/**
	 * Build placemark description
	 *
	 * @since 1.0.0
	 * @param array $location_data Location data.
	 * @return string Description text.
	 */
	private function build_placemark_description( array $location_data ): string {
		$description_parts = array();

		// Address.
		$address_parts = array();
		if ( ! empty( $location_data['street_address'] ) ) {
			$address_parts[] = $location_data['street_address'];
		}
		if ( ! empty( $location_data['city'] ) ) {
			$address_parts[] = $location_data['city'];
		}
		if ( ! empty( $location_data['state'] ) ) {
			$address_parts[] = $location_data['state'];
		}
		if ( ! empty( $location_data['postal_code'] ) ) {
			$address_parts[] = $location_data['postal_code'];
		}
		if ( ! empty( $location_data['country'] ) ) {
			$address_parts[] = $location_data['country'];
		}

		if ( ! empty( $address_parts ) ) {
			$description_parts[] = implode( ', ', $address_parts );
		}

		// Phone.
		if ( ! empty( $location_data['phone'] ) ) {
			$description_parts[] = 'Phone: ' . $location_data['phone'];
		}

		// Email.
		if ( ! empty( $location_data['email'] ) ) {
			$description_parts[] = 'Email: ' . $location_data['email'];
		}

		return implode( "\n", $description_parts );
	}

	/**
	 * Add export button to admin
	 *
	 * Requirements 4.10: Add "Export to KML" button in admin interface.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_export_button(): void {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'meowseo_location' !== $_GET['post_type'] ) {
			return;
		}

		if ( ! current_user_can( 'meowseo_manage_locations' ) ) {
			return;
		}

		// Add export button via JavaScript.
		add_action( 'admin_footer', array( $this, 'render_export_button' ) );
	}

	/**
	 * Render export button
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_export_button(): void {
		?>
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				const bulkActions = document.querySelector('select[name="action"]');
				if (bulkActions) {
					const option = document.createElement('option');
					option.value = 'meowseo_export_kml';
					option.textContent = '<?php esc_html_e( 'Export to KML', 'meowseo' ); ?>';
					bulkActions.appendChild(option);
				}
			});
		</script>
		<?php
	}

	/**
	 * Handle KML export request
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_kml_export(): void {
		// Check if export is requested via query parameter.
		if ( ! isset( $_GET['meowseo_export_kml'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'meowseo_export_kml_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'meowseo_manage_locations' ) ) {
			wp_die( esc_html__( 'You do not have permission to export locations.', 'meowseo' ) );
		}

		// Generate and output KML.
		$kml = $this->generate_kml();

		header( 'Content-Type: application/vnd.google-earth.kml+xml' );
		header( 'Content-Disposition: attachment; filename="locations-' . gmdate( 'Y-m-d' ) . '.kml"' );
		header( 'Content-Length: ' . strlen( $kml ) );

		echo $kml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Get KML export URL
	 *
	 * @since 1.0.0
	 * @return string Export URL.
	 */
	public function get_export_url(): string {
		$nonce = wp_create_nonce( 'meowseo_export_kml_nonce' );
		return add_query_arg(
			array(
				'meowseo_export_kml' => '1',
				'_wpnonce'           => $nonce,
			),
			admin_url( 'admin.php' )
		);
	}
}

/**
 * Escape XML special characters
 *
 * @since 1.0.0
 * @param string $text Text to escape.
 * @return string Escaped text.
 */
function esc_xml( string $text ): string {
	$text = str_replace( '&', '&amp;', $text );
	$text = str_replace( '<', '&lt;', $text );
	$text = str_replace( '>', '&gt;', $text );
	$text = str_replace( '"', '&quot;', $text );
	$text = str_replace( "'", '&apos;', $text );
	return $text;
}
