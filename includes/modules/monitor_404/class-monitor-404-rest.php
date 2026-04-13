<?php
/**
 * Monitor 404 REST API
 *
 * Provides REST endpoints for accessing and managing 404 log data.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\Monitor_404
 */

namespace MeowSEO\Modules\Monitor_404;

use MeowSEO\Helpers\DB;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Monitor 404 REST API class
 *
 * Handles REST endpoint registration and request processing.
 */
class Monitor_404_REST {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'meowseo/v1';

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /meowseo/v1/404-log - Get paginated 404 log entries.
		register_rest_route(
			self::NAMESPACE,
			'/404-log',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_404_log' ),
				'permission_callback' => array( $this, 'check_manage_options' ),
				'args'                => array(
					'page'     => array(
						'type'              => 'integer',
						'default'           => 1,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'type'              => 'integer',
						'default'           => 50,
						'minimum'           => 1,
						'maximum'           => 100,
						'sanitize_callback' => 'absint',
					),
					'orderby'  => array(
						'type'              => 'string',
						'default'           => 'last_seen',
						'enum'              => array( 'id', 'url', 'hit_count', 'first_seen', 'last_seen' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'order'    => array(
						'type'              => 'string',
						'default'           => 'DESC',
						'enum'              => array( 'ASC', 'DESC' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// DELETE /meowseo/v1/404-log/{id} - Delete a 404 log entry.
		register_rest_route(
			self::NAMESPACE,
			'/404-log/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_404_entry' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get paginated 404 log entries.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_404_log( WP_REST_Request $request ) {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$orderby  = $request->get_param( 'orderby' );
		$order    = $request->get_param( 'order' );

		$offset = ( $page - 1 ) * $per_page;

		$args = array(
			'limit'   => $per_page,
			'offset'  => $offset,
			'orderby' => $orderby,
			'order'   => $order,
		);

		$entries = DB::get_404_log( $args );

		// Get total count for pagination.
		global $wpdb;
		$table       = $wpdb->prefix . 'meowseo_404_log';
		$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		$response = new WP_REST_Response(
			array(
				'entries'    => $entries,
				'pagination' => array(
					'page'        => $page,
					'per_page'    => $per_page,
					'total'       => (int) $total_count,
					'total_pages' => ceil( $total_count / $per_page ),
				),
			)
		);

		// Add cache control headers for CDN/edge caching.
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Delete a 404 log entry.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_404_entry( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_404_log';

		$deleted = $wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete 404 log entry.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		if ( 0 === $deleted ) {
			return new WP_Error(
				'not_found',
				__( '404 log entry not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( '404 log entry deleted successfully.', 'meowseo' ),
			)
		);

		// No caching for mutation endpoints.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Check if user has manage_options capability.
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function check_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if user has manage_options capability and valid nonce.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function check_manage_options_and_nonce( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce from request header.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__( 'Invalid nonce.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
