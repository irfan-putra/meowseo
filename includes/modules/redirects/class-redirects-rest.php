<?php
/**
 * Redirects REST API Handler
 *
 * Provides REST endpoints for redirect CRUD operations.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Redirects;

use MeowSEO\Helpers\Logger;
use MeowSEO\Options;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirects REST API class
 *
 * Handles REST endpoints for redirect management.
 * All endpoints require manage_options capability and nonce verification.
 *
 * @since 1.0.0
 */
class Redirects_REST {

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
	 * Requirement 7.7: REST endpoints for redirect CRUD operations
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /meowseo/v1/redirects - List redirects
		register_rest_route(
			self::NAMESPACE,
			'/redirects',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_redirects' ),
				'permission_callback' => array( $this, 'check_manage_options' ),
			)
		);

		// POST /meowseo/v1/redirects - Create redirect
		register_rest_route(
			self::NAMESPACE,
			'/redirects',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_redirect' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => $this->get_redirect_schema(),
			)
		);

		// PUT /meowseo/v1/redirects/{id} - Update redirect
		register_rest_route(
			self::NAMESPACE,
			'/redirects/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_redirect' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
					$this->get_redirect_schema()
				),
			)
		);

		// DELETE /meowseo/v1/redirects/{id} - Delete redirect
		register_rest_route(
			self::NAMESPACE,
			'/redirects/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_redirect' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /meowseo/v1/redirects/import - Import redirects from CSV
		register_rest_route(
			self::NAMESPACE,
			'/redirects/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_csv' ),
				'permission_callback' => array( $this, 'check_manage_options_and_nonce' ),
			)
		);
	}

	/**
	 * Get redirect schema for validation
	 *
	 * @return array Schema definition.
	 */
	private function get_redirect_schema(): array {
		return array(
			'source_url'    => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function( $value ) {
					return ! empty( $value );
				},
			),
			'target_url'    => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => function( $value ) {
					return ! empty( $value );
				},
			),
			'redirect_type' => array(
				'required'          => false,
				'type'              => 'integer',
				'default'           => 301,
				'sanitize_callback' => 'absint',
				'validate_callback' => function( $value ) {
					return in_array( (int) $value, array( 301, 302, 307, 410 ), true );
				},
			),
			'is_regex'      => array(
				'required'          => false,
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'status'        => array(
				'required'          => false,
				'type'              => 'string',
				'default'           => 'active',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function( $value ) {
					return in_array( $value, array( 'active', 'inactive' ), true );
				},
			),
		);
	}

	/**
	 * Check manage_options capability
	 *
	 * Requirement 15.3: Verify user capability
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function check_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check manage_options capability and nonce
	 *
	 * Requirements 15.2, 15.3: Verify nonce and capability
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function check_manage_options_and_nonce( WP_REST_Request $request ) {
		// Check capability first
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage redirects.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__( 'Cookie nonce is invalid.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get redirects list
	 *
	 * Supports pagination via page and per_page query parameters.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_redirects( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Pagination parameters
		$page = max( 1, $request->get_param( 'page' ) ?? 1 );
		$per_page = max( 1, min( 100, $request->get_param( 'per_page' ) ?? 50 ) );
		$offset = ( $page - 1 ) * $per_page;

		// Get total count
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		// Get redirects with pagination
		$redirects = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		// Calculate total pages
		$total_pages = ceil( $total / $per_page );

		$response = new WP_REST_Response( $redirects );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Create redirect
	 *
	 * Requirement 7.7: Create redirect rule via REST API
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function create_redirect( WP_REST_Request $request ) {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Prepare data
		$data = array(
			'source_url'    => $request->get_param( 'source_url' ),
			'target_url'    => $request->get_param( 'target_url' ),
			'redirect_type' => $request->get_param( 'redirect_type' ) ?? 301,
			'is_regex'      => $request->get_param( 'is_regex' ) ? 1 : 0,
			'status'        => $request->get_param( 'status' ) ?? 'active',
		);

		$format = array( '%s', '%s', '%d', '%d', '%s' );

		// Insert redirect
		$result = $wpdb->insert( $table, $data, $format );

		if ( false === $result ) {
			return new WP_Error(
				'db_insert_error',
				__( 'Failed to create redirect.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		// Update has_regex_rules flag if this is a regex rule
		if ( $data['is_regex'] ) {
			$this->update_regex_rules_flag();
		}

		// Get created redirect
		$redirect_id = $wpdb->insert_id;
		$redirect = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $redirect_id ),
			ARRAY_A
		);

		$response = new WP_REST_Response( $redirect, 201 );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Update redirect
	 *
	 * Requirement 7.7: Update redirect rule via REST API
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function update_redirect( WP_REST_Request $request ) {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';
		$redirect_id = $request->get_param( 'id' );

		// Check if redirect exists
		$exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $redirect_id )
		);

		if ( ! $exists ) {
			return new WP_Error(
				'redirect_not_found',
				__( 'Redirect not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Prepare data
		$data = array();
		$format = array();

		if ( $request->has_param( 'source_url' ) ) {
			$data['source_url'] = $request->get_param( 'source_url' );
			$format[] = '%s';
		}

		if ( $request->has_param( 'target_url' ) ) {
			$data['target_url'] = $request->get_param( 'target_url' );
			$format[] = '%s';
		}

		if ( $request->has_param( 'redirect_type' ) ) {
			$data['redirect_type'] = $request->get_param( 'redirect_type' );
			$format[] = '%d';
		}

		if ( $request->has_param( 'is_regex' ) ) {
			$data['is_regex'] = $request->get_param( 'is_regex' ) ? 1 : 0;
			$format[] = '%d';
		}

		if ( $request->has_param( 'status' ) ) {
			$data['status'] = $request->get_param( 'status' );
			$format[] = '%s';
		}

		// Update redirect
		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => $redirect_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_update_error',
				__( 'Failed to update redirect.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		// Update has_regex_rules flag
		$this->update_regex_rules_flag();

		// Get updated redirect
		$redirect = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $redirect_id ),
			ARRAY_A
		);

		$response = new WP_REST_Response( $redirect );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Delete redirect
	 *
	 * Requirement 7.7: Delete redirect rule via REST API
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_redirect( WP_REST_Request $request ) {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';
		$redirect_id = $request->get_param( 'id' );

		// Check if redirect exists
		$redirect = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $redirect_id ),
			ARRAY_A
		);

		if ( ! $redirect ) {
			return new WP_Error(
				'redirect_not_found',
				__( 'Redirect not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Delete redirect
		$result = $wpdb->delete(
			$table,
			array( 'id' => $redirect_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_delete_error',
				__( 'Failed to delete redirect.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		// Update has_regex_rules flag
		$this->update_regex_rules_flag();

		$response = new WP_REST_Response(
			array(
				'deleted' => true,
				'redirect' => $redirect,
			)
		);
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Update has_regex_rules option flag
	 *
	 * Checks if any active regex rules exist and updates the option flag.
	 * This flag is used to skip regex matching when no regex rules exist.
	 *
	 * @return void
	 */
	private function update_regex_rules_flag(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		// Check if any active regex rules exist
		$has_regex = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE is_regex = 1 AND status = 'active'"
		);

		// Update option flag
		$this->options->set( 'has_regex_rules', $has_regex > 0 );
		$this->options->save();
	}

	/**
	 * Import redirects from CSV file
	 *
	 * Expected CSV format: source_url,target_url,redirect_type,is_regex
	 * Example: /old-page,/new-page,301,0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function import_csv( WP_REST_Request $request ) {
		$files = $request->get_file_params();

		if ( empty( $files['file'] ) ) {
			Logger::error(
				'CSV import failed: No file provided',
				[
					'file_name' => '',
					'error'     => 'No file uploaded',
				]
			);

			return new WP_Error(
				'no_file',
				__( 'No file provided for import.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		$file = $files['file'];
		$file_name = $file['name'];

		// Validate file type
		$file_ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext ) {
			Logger::error(
				'CSV import failed: Invalid file type',
				[
					'file_name' => $file_name,
					'error'     => 'File must be CSV format',
				]
			);

			return new WP_Error(
				'invalid_file_type',
				__( 'File must be in CSV format.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		// Read CSV file
		$file_path = $file['tmp_name'];
		$handle = fopen( $file_path, 'r' );

		if ( false === $handle ) {
			Logger::error(
				'CSV import failed: Could not open file',
				[
					'file_name' => $file_name,
					'error'     => 'Failed to open file for reading',
				]
			);

			return new WP_Error(
				'file_read_error',
				__( 'Could not read the uploaded file.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';

		$imported_count = 0;
		$skipped_count = 0;
		$row_number = 0;
		$errors = [];

		// Skip header row if present
		$first_row = fgetcsv( $handle );
		if ( $first_row && ( 'source_url' === strtolower( $first_row[0] ) || 'source' === strtolower( $first_row[0] ) ) ) {
			// Header row detected, continue to next row
			$row_number++;
		} else {
			// No header, rewind to start
			rewind( $handle );
		}

		// Process each row
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_number++;

			// Skip empty rows
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			// Validate row has at least 2 columns (source and target)
			if ( count( $row ) < 2 ) {
				$skipped_count++;
				$errors[] = sprintf( 'Row %d: Missing required columns', $row_number );
				continue;
			}

			$source_url = trim( $row[0] );
			$target_url = trim( $row[1] );
			$redirect_type = isset( $row[2] ) ? absint( $row[2] ) : 301;
			$is_regex = isset( $row[3] ) ? (bool) $row[3] : false;

			// Validate required fields
			if ( empty( $source_url ) || empty( $target_url ) ) {
				$skipped_count++;
				$errors[] = sprintf( 'Row %d: Empty source or target URL', $row_number );
				continue;
			}

			// Validate redirect type
			if ( ! in_array( $redirect_type, array( 301, 302, 307, 410 ), true ) ) {
				$redirect_type = 301; // Default to 301
			}

			// Insert redirect
			$result = $wpdb->insert(
				$table,
				[
					'source_url'    => $source_url,
					'target_url'    => $target_url,
					'redirect_type' => $redirect_type,
					'is_regex'      => $is_regex ? 1 : 0,
					'status'        => 'active',
				],
				[ '%s', '%s', '%d', '%d', '%s' ]
			);

			if ( false === $result ) {
				$skipped_count++;
				$errors[] = sprintf( 'Row %d: Database insert failed', $row_number );
			} else {
				$imported_count++;
			}
		}

		fclose( $handle );

		// Update regex rules flag
		$this->update_regex_rules_flag();

		// Log result
		if ( $imported_count > 0 ) {
			Logger::info(
				'CSV import completed successfully',
				[
					'file_name'      => $file_name,
					'row_count'      => $imported_count,
					'skipped_count'  => $skipped_count,
				]
			);
		} else {
			Logger::error(
				'CSV import failed: No rows imported',
				[
					'file_name' => $file_name,
					'error'     => 'All rows were skipped or invalid',
					'errors'    => $errors,
				]
			);

			return new WP_Error(
				'import_failed',
				__( 'No redirects were imported. Please check the CSV format.', 'meowseo' ),
				array( 'status' => 400 )
			);
		}

		$response = new WP_REST_Response(
			[
				'success'        => true,
				'imported_count' => $imported_count,
				'skipped_count'  => $skipped_count,
				'errors'         => $errors,
			]
		);
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}
}
