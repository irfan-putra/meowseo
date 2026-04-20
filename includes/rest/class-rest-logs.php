<?php
/**
 * REST_Logs API Handler
 *
 * Provides REST endpoints for log operations including fetching, filtering,
 * deleting, and formatting log entries.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\REST
 */

namespace MeowSEO\REST;

use MeowSEO\Options;
use MeowSEO\Helpers\Log_Formatter;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST_Logs class
 *
 * Handles REST endpoints for log management.
 * All endpoints require manage_options capability.
 *
 * Requirements: 14.1, 14.2, 14.3, 15.1, 15.2, 15.3, 16.1, 16.2
 */
class REST_Logs {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * REST namespace
	 *
	 * @var string
	 */
	private const NAMESPACE = 'meowseo/v1';

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register REST routes
	 *
	 * Requirements: 14.1, 14.2, 14.3
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /meowseo/v1/logs - Fetch logs with filtering
		register_rest_route(
			self::NAMESPACE,
			'/logs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_logs' ),
				'permission_callback' => array( $this, 'manage_options_permission' ),
				'args'                => array(
					'level'      => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'module'     => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'start_date' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'end_date'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'       => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page'   => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 50,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// DELETE /meowseo/v1/logs - Delete logs by IDs
		register_rest_route(
			self::NAMESPACE,
			'/logs',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_logs' ),
				'permission_callback' => array( $this, 'manage_options_permission_with_nonce' ),
				'args'                => array(
					'ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array(
							'type' => 'integer',
						),
						'sanitize_callback' => function( $ids ) {
							return array_map( 'absint', $ids );
						},
						'validate_callback' => function( $ids ) {
							return is_array( $ids ) && ! empty( $ids );
						},
					),
				),
			)
		);

		// GET /meowseo/v1/logs/{id}/formatted - Get formatted single log entry
		register_rest_route(
			self::NAMESPACE,
			'/logs/(?P<id>\d+)/formatted',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_formatted_log' ),
				'permission_callback' => array( $this, 'manage_options_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get logs with filtering and pagination
	 *
	 * Requirements: 14.4, 14.5, 8.2
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_logs( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_logs';

		// Build WHERE clause dynamically based on filters
		$where_clauses = array();
		$where_values = array();

		// Filter by level
		if ( $request->has_param( 'level' ) && ! empty( $request->get_param( 'level' ) ) ) {
			$level = $request->get_param( 'level' );
			$where_clauses[] = 'level = %s';
			$where_values[] = $level;
		}

		// Filter by module
		if ( $request->has_param( 'module' ) && ! empty( $request->get_param( 'module' ) ) ) {
			$module = $request->get_param( 'module' );
			$where_clauses[] = 'module = %s';
			$where_values[] = $module;
		}

		// Filter by start_date
		if ( $request->has_param( 'start_date' ) && ! empty( $request->get_param( 'start_date' ) ) ) {
			$start_date = $request->get_param( 'start_date' );
			$where_clauses[] = 'created_at >= %s';
			$where_values[] = $start_date;
		}

		// Filter by end_date
		if ( $request->has_param( 'end_date' ) && ! empty( $request->get_param( 'end_date' ) ) ) {
			$end_date = $request->get_param( 'end_date' );
			$where_clauses[] = 'created_at <= %s';
			$where_values[] = $end_date;
		}

		// Build WHERE clause
		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Pagination parameters
		$page = max( 1, $request->get_param( 'page' ) ?? 1 );
		$per_page = max( 1, min( 100, $request->get_param( 'per_page' ) ?? 50 ) );
		$offset = ( $page - 1 ) * $per_page;

		// Get total count
		$count_query = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		if ( ! empty( $where_values ) ) {
			$count_query = $wpdb->prepare( $count_query, ...$where_values );
		}
		$total = (int) $wpdb->get_var( $count_query );

		// Get logs with pagination
		$logs_query = "SELECT * FROM {$table} {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$query_values = array_merge( $where_values, array( $per_page, $offset ) );
		$logs_query = $wpdb->prepare( $logs_query, ...$query_values );
		$logs = $wpdb->get_results( $logs_query, ARRAY_A );

		// Calculate total pages (minimum 1 page even if no entries)
		$total_pages = max( 1, ceil( $total / $per_page ) );

		// Return JSON response with logs array and pagination metadata
		$response = new WP_REST_Response(
			array(
				'logs'     => $logs,
				'total'    => $total,
				'pages'    => $total_pages,
				'page'     => $page,
				'per_page' => $per_page,
			)
		);

		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}

	/**
	 * Delete logs by IDs
	 *
	 * Requirements: 9.4, 16.1, 16.2
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_logs( WP_REST_Request $request ) {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_logs';
		$ids = $request->get_param( 'ids' );

		if ( empty( $ids ) ) {
			return new WP_Error(
				'invalid_ids',
				__( 'No log IDs provided.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Build IN clause with placeholders
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$query = "DELETE FROM {$table} WHERE id IN ({$placeholders})";

		// Execute prepared statement
		$deleted = $wpdb->query( $wpdb->prepare( $query, ...$ids ) );

		if ( false === $deleted ) {
			return new WP_Error(
				'db_delete_error',
				__( 'Failed to delete log entries.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'deleted' => $deleted,
			)
		);

		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Get formatted single log entry
	 *
	 * Requirements: 14.3
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_formatted_log( WP_REST_Request $request ) {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_logs';
		$log_id = $request->get_param( 'id' );

		// Fetch single log entry by ID
		$log = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $log_id ),
			ARRAY_A
		);

		if ( ! $log ) {
			return new WP_Error(
				'log_not_found',
				__( 'Log entry not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Format using Log_Formatter::format_single_entry()
		$formatted = Log_Formatter::format_single_entry( $log );

		$response = new WP_REST_Response(
			array(
				'formatted' => $formatted,
			)
		);

		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Permission callback for manage_options capability
	 *
	 * Requirements: 15.1, 15.2, 15.3
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function manage_options_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Permission callback with nonce verification
	 *
	 * Requirements: 15.1, 15.2, 15.3, 16.1, 16.2
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function manage_options_permission_with_nonce( WP_REST_Request $request ) {
		// Check capability first
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage logs.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce
		if ( ! $this->verify_nonce( $request ) ) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__( 'Cookie nonce is invalid.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Verify nonce from request
	 *
	 * Requirements: 16.1, 16.2
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	private function verify_nonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( empty( $nonce ) ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}
}
