<?php
/**
 * GA4 REST API endpoints.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Analytics;

use WP_REST_Request;
use WP_REST_Response;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GA4 REST API class.
 */
class GA4_REST {

	/**
	 * GA4 Module instance.
	 *
	 * @var GA4_Module
	 */
	private GA4_Module $ga4_module;

	/**
	 * Constructor.
	 *
	 * @param GA4_Module $ga4_module GA4 Module instance.
	 */
	public function __construct( GA4_Module $ga4_module ) {
		$this->ga4_module = $ga4_module;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'meowseo/v1',
			'/analytics/ga4-metrics',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_ga4_metrics' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'meowseo/v1',
			'/analytics/gsc-metrics',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_gsc_metrics' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'meowseo/v1',
			'/analytics/pagespeed',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_pagespeed_insights' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'meowseo/v1',
			'/analytics/winning-content',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_winning_content' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'meowseo/v1',
			'/analytics/losing-content',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_losing_content' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check permission for analytics endpoints.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public function check_permission(): bool {
		return current_user_can( 'meowseo_manage_analytics' );
	}

	/**
	 * Get GA4 metrics endpoint.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response REST response.
	 */
	public function get_ga4_metrics( WP_REST_Request $request ): WP_REST_Response {
		$start_date = $request->get_param( 'start_date' ) ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date = $request->get_param( 'end_date' ) ?? gmdate( 'Y-m-d' );

		$metrics = $this->ga4_module->get_ga4_metrics( $start_date, $end_date );

		if ( ! $metrics ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Failed to fetch GA4 metrics', 'meowseo' ) ),
				400
			);
		}

		return new WP_REST_Response( $metrics );
	}

	/**
	 * Get GSC metrics endpoint.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response REST response.
	 */
	public function get_gsc_metrics( WP_REST_Request $request ): WP_REST_Response {
		$start_date = $request->get_param( 'start_date' ) ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date = $request->get_param( 'end_date' ) ?? gmdate( 'Y-m-d' );

		$metrics = $this->ga4_module->get_gsc_metrics( $start_date, $end_date );

		if ( ! $metrics ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Failed to fetch GSC metrics', 'meowseo' ) ),
				400
			);
		}

		return new WP_REST_Response( $metrics );
	}

	/**
	 * Get PageSpeed Insights endpoint.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response REST response.
	 */
	public function get_pagespeed_insights( WP_REST_Request $request ): WP_REST_Response {
		$url = $request->get_param( 'url' );

		if ( ! $url ) {
			return new WP_REST_Response(
				array( 'error' => __( 'URL parameter is required', 'meowseo' ) ),
				400
			);
		}

		$metrics = $this->ga4_module->get_pagespeed_insights( $url );

		if ( ! $metrics ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Failed to fetch PageSpeed Insights metrics', 'meowseo' ) ),
				400
			);
		}

		return new WP_REST_Response( $metrics );
	}

	/**
	 * Get winning content endpoint.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response REST response.
	 */
	public function get_winning_content( WP_REST_Request $request ): WP_REST_Response {
		$content = $this->ga4_module->identify_winning_content();
		return new WP_REST_Response( $content );
	}

	/**
	 * Get losing content endpoint.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response REST response.
	 */
	public function get_losing_content( WP_REST_Request $request ): WP_REST_Response {
		$content = $this->ga4_module->identify_losing_content();
		return new WP_REST_Response( $content );
	}
}
