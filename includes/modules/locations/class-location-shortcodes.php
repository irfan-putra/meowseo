<?php
/**
 * Location Shortcodes
 *
 * Handles all location-related shortcodes.
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
 * Location_Shortcodes class
 *
 * Implements shortcodes for displaying location information:
 * - [meowseo_address id="123"]
 * - [meowseo_map id="123" width="600" height="400"]
 * - [meowseo_opening_hours id="123"]
 * - [meowseo_store_locator zoom="10" center="lat,lng"]
 *
 * Requirements 4.5, 4.6, 4.7, 4.8: Implement location shortcodes.
 *
 * @since 1.0.0
 */
class Location_Shortcodes {

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
	 * Registers shortcodes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		add_shortcode( 'meowseo_address', array( $this, 'address_shortcode' ) );
		add_shortcode( 'meowseo_map', array( $this, 'map_shortcode' ) );
		add_shortcode( 'meowseo_opening_hours', array( $this, 'opening_hours_shortcode' ) );
		add_shortcode( 'meowseo_store_locator', array( $this, 'store_locator_shortcode' ) );
	}

	/**
	 * Address shortcode callback
	 *
	 * Requirements 4.5: Implement [meowseo_address] shortcode for formatted address output.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Formatted address HTML.
	 */
	public function address_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'meowseo_address'
		);

		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}

		$location_data = $this->location_cpt->get_location_data( $post_id );

		// Build address HTML.
		$address_parts = array();

		if ( ! empty( $location_data['street_address'] ) ) {
			$address_parts[] = esc_html( $location_data['street_address'] );
		}

		if ( ! empty( $location_data['city'] ) ) {
			$address_parts[] = esc_html( $location_data['city'] );
		}

		if ( ! empty( $location_data['state'] ) ) {
			$address_parts[] = esc_html( $location_data['state'] );
		}

		if ( ! empty( $location_data['postal_code'] ) ) {
			$address_parts[] = esc_html( $location_data['postal_code'] );
		}

		if ( ! empty( $location_data['country'] ) ) {
			$address_parts[] = esc_html( $location_data['country'] );
		}

		if ( empty( $address_parts ) ) {
			return '';
		}

		$html = '<div class="meowseo-address">';
		$html .= '<address>' . implode( '<br />', $address_parts ) . '</address>';

		if ( ! empty( $location_data['phone'] ) ) {
			$html .= '<p class="meowseo-phone"><a href="tel:' . esc_attr( $location_data['phone'] ) . '">' . esc_html( $location_data['phone'] ) . '</a></p>';
		}

		if ( ! empty( $location_data['email'] ) ) {
			$html .= '<p class="meowseo-email"><a href="mailto:' . esc_attr( $location_data['email'] ) . '">' . esc_html( $location_data['email'] ) . '</a></p>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Map shortcode callback
	 *
	 * Requirements 4.6: Implement [meowseo_map] shortcode for Google Maps iframe embed.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Google Maps iframe HTML.
	 */
	public function map_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'width'  => 600,
				'height' => 400,
			),
			$atts,
			'meowseo_map'
		);

		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}

		$location_data = $this->location_cpt->get_location_data( $post_id );

		// Coordinates are required for map.
		if ( empty( $location_data['latitude'] ) || empty( $location_data['longitude'] ) ) {
			return '';
		}

		$width = absint( $atts['width'] );
		$height = absint( $atts['height'] );

		// Build Google Maps embed URL.
		$map_url = sprintf(
			'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3000!2d%f!3d%f!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0:0x0!2z%f,%f',
			$location_data['longitude'],
			$location_data['latitude'],
			$location_data['latitude'],
			$location_data['longitude']
		);

		$html = sprintf(
			'<div class="meowseo-map"><iframe width="%d" height="%d" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="%s"></iframe></div>',
			$width,
			$height,
			esc_url( $map_url )
		);

		return $html;
	}

	/**
	 * Opening hours shortcode callback
	 *
	 * Requirements 4.7: Implement [meowseo_opening_hours] shortcode for structured hours display.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Opening hours HTML.
	 */
	public function opening_hours_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'meowseo_opening_hours'
		);

		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}

		$location_data = $this->location_cpt->get_location_data( $post_id );

		if ( empty( $location_data['opening_hours'] ) || ! is_array( $location_data['opening_hours'] ) ) {
			return '';
		}

		$html = '<div class="meowseo-opening-hours"><table>';
		$html .= '<thead><tr><th>' . esc_html__( 'Day', 'meowseo' ) . '</th><th>' . esc_html__( 'Hours', 'meowseo' ) . '</th></tr></thead>';
		$html .= '<tbody>';

		foreach ( $location_data['opening_hours'] as $hours ) {
			if ( ! isset( $hours['day'] ) || ! isset( $hours['open'] ) || ! isset( $hours['close'] ) ) {
				continue;
			}

			$html .= sprintf(
				'<tr><td>%s</td><td>%s - %s</td></tr>',
				esc_html( $hours['day'] ),
				esc_html( $hours['open'] ),
				esc_html( $hours['close'] )
			);
		}

		$html .= '</tbody></table></div>';

		return $html;
	}

	/**
	 * Store locator shortcode callback
	 *
	 * Requirements 4.8: Implement [meowseo_store_locator] shortcode for interactive map with all locations.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Store locator HTML.
	 */
	public function store_locator_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			array(
				'zoom'   => 10,
				'center' => '',
			),
			$atts,
			'meowseo_store_locator'
		);

		$locations = $this->location_cpt->get_all_locations();

		if ( empty( $locations ) ) {
			return '';
		}

		// Build locations JSON for JavaScript.
		$locations_data = array();
		foreach ( $locations as $location ) {
			$location_data = $this->location_cpt->get_location_data( $location->ID );

			if ( empty( $location_data['latitude'] ) || empty( $location_data['longitude'] ) ) {
				continue;
			}

			$locations_data[] = array(
				'id'       => $location->ID,
				'name'     => $location_data['business_name'] ?: $location->post_title,
				'latitude' => floatval( $location_data['latitude'] ),
				'longitude' => floatval( $location_data['longitude'] ),
				'address'  => $this->format_address( $location_data ),
				'phone'    => $location_data['phone'] ?: '',
				'email'    => $location_data['email'] ?: '',
			);
		}

		if ( empty( $locations_data ) ) {
			return '';
		}

		$zoom = absint( $atts['zoom'] );
		$center_parts = explode( ',', $atts['center'] );
		$center_lat = ! empty( $center_parts[0] ) ? floatval( $center_parts[0] ) : $locations_data[0]['latitude'];
		$center_lng = ! empty( $center_parts[1] ) ? floatval( $center_parts[1] ) : $locations_data[0]['longitude'];

		$html = '<div class="meowseo-store-locator" data-locations="' . esc_attr( wp_json_encode( $locations_data ) ) . '" data-zoom="' . $zoom . '" data-center-lat="' . $center_lat . '" data-center-lng="' . $center_lng . '"></div>';

		return $html;
	}

	/**
	 * Format address from location data
	 *
	 * @since 1.0.0
	 * @param array $location_data Location data.
	 * @return string Formatted address string.
	 */
	private function format_address( array $location_data ): string {
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

		return implode( ', ', $address_parts );
	}
}
