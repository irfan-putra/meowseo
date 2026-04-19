<?php
/**
 * CSV Importer Class
 *
 * Handles importing redirects from CSV files with validation and error handling.
 *
 * Requirements: 5.1, 5.3, 5.4, 5.5, 5.6, 5.7, 5.12
 *
 * @package MeowSEO
 * @subpackage Modules\Redirects
 * @since 2.0.0
 */

namespace MeowSEO\Modules\Redirects;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV_Importer class
 *
 * Parses CSV files and creates redirect records with validation.
 */
class CSV_Importer {

	/**
	 * Redirects_Admin instance
	 *
	 * @var Redirects_Admin
	 */
	private Redirects_Admin $redirect_manager;

	/**
	 * Import statistics
	 *
	 * @var array
	 */
	private array $stats = array(
		'imported' => 0,
		'skipped'  => 0,
		'errors'   => array(),
	);

	/**
	 * Constructor
	 *
	 * @param Redirects_Admin $redirect_manager Redirect manager instance.
	 */
	public function __construct( Redirects_Admin $redirect_manager ) {
		$this->redirect_manager = $redirect_manager;
	}

	/**
	 * Import from file
	 *
	 * Imports redirects from an uploaded CSV file.
	 * Requirement 5.1: Accept $_FILES array.
	 *
	 * @param array $file $_FILES array element.
	 * @return array|WP_Error Import results or error.
	 */
	public function import_from_file( array $file ): array|WP_Error {
		// Validate file type.
		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext ) {
			return new WP_Error(
				'invalid_file_type',
				__( 'File must be in CSV format.', 'meowseo' )
			);
		}

		// Read file content.
		$content = file_get_contents( $file['tmp_name'] );
		if ( false === $content ) {
			return new WP_Error(
				'file_read_error',
				__( 'Could not read the uploaded file.', 'meowseo' )
			);
		}

		return $this->import_from_string( $content );
	}

	/**
	 * Import from string
	 *
	 * Imports redirects from CSV content string.
	 * Requirement 5.1: Accept CSV content string.
	 *
	 * @param string $csv_content CSV content.
	 * @return array|WP_Error Import results or error.
	 */
	public function import_from_string( string $csv_content ): array|WP_Error {
		// Reset statistics.
		$this->stats = array(
			'imported' => 0,
			'skipped'  => 0,
			'errors'   => array(),
		);

		// Parse CSV.
		$rows = $this->parse_csv( $csv_content );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		// Process each row.
		foreach ( $rows as $line_number => $row ) {
			$validation = $this->validate_row( $row, $line_number );
			if ( is_wp_error( $validation ) ) {
				$this->stats['skipped']++;
				$this->stats['errors'][] = sprintf(
					/* translators: 1: Line number, 2: Error message */
					__( 'Line %1$d: %2$s', 'meowseo' ),
					$line_number,
					$validation->get_error_message()
				);
				continue;
			}

			// Check for duplicates (Requirement 5.12).
			if ( $this->is_duplicate( $row['source_url'] ) ) {
				$this->stats['skipped']++;
				$this->stats['errors'][] = sprintf(
					/* translators: 1: Line number, 2: Source URL */
					__( 'Line %1$d: Duplicate source URL: %2$s', 'meowseo' ),
					$line_number,
					$row['source_url']
				);
				continue;
			}

			// Create redirect.
			$result = $this->create_redirect( $row );
			if ( is_wp_error( $result ) ) {
				$this->stats['skipped']++;
				$this->stats['errors'][] = sprintf(
					/* translators: 1: Line number, 2: Error message */
					__( 'Line %1$d: %2$s', 'meowseo' ),
					$line_number,
					$result->get_error_message()
				);
			} else {
				$this->stats['imported']++;
			}
		}

		// Requirement 5.6, 5.7: Return import summary.
		return array(
			'imported' => $this->stats['imported'],
			'skipped'  => $this->stats['skipped'],
			'errors'   => $this->stats['errors'],
		);
	}

	/**
	 * Parse CSV content
	 *
	 * Parses CSV content into array of rows.
	 * Requirement 5.4: Parse CSV with header row detection.
	 *
	 * @param string $content CSV content.
	 * @return array|WP_Error Array of rows or error.
	 */
	private function parse_csv( string $content ): array|WP_Error {
		$lines = str_getcsv( $content, "\n" );
		if ( empty( $lines ) ) {
			return new WP_Error(
				'empty_csv',
				__( 'CSV file is empty.', 'meowseo' )
			);
		}

		$rows        = array();
		$line_number = 0;
		$headers     = null;

		foreach ( $lines as $line ) {
			$line_number++;
			$line = trim( $line );

			// Skip empty lines.
			if ( empty( $line ) ) {
				continue;
			}

			$columns = str_getcsv( $line );

			// Detect header row (Requirement 5.4).
			if ( null === $headers ) {
				$first_column = strtolower( trim( $columns[0] ) );
				if ( in_array( $first_column, array( 'source_url', 'source' ), true ) ) {
					// Header row detected, use it.
					$headers = array_map( 'strtolower', array_map( 'trim', $columns ) );
					continue;
				} else {
					// No header row, use default column names.
					$headers = array( 'source_url', 'target_url', 'status_code' );
				}
			}

			// Map columns to associative array.
			$row = array();
			foreach ( $headers as $index => $header ) {
				$row[ $header ] = isset( $columns[ $index ] ) ? trim( $columns[ $index ] ) : '';
			}

			$rows[ $line_number ] = $row;
		}

		// Requirement 5.4: Validate required columns.
		if ( empty( $rows ) ) {
			return new WP_Error(
				'no_data',
				__( 'CSV file contains no data rows.', 'meowseo' )
			);
		}

		return $rows;
	}

	/**
	 * Validate row
	 *
	 * Validates a CSV row for required fields and correct format.
	 * Requirements 5.4, 5.5: Validate required columns and status codes.
	 *
	 * @param array $row    Row data.
	 * @param int   $line_number Line number for error reporting.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_row( array $row, int $line_number ): bool|WP_Error {
		// Requirement 5.4: Validate required columns.
		if ( empty( $row['source_url'] ) ) {
			return new WP_Error(
				'missing_source_url',
				__( 'Missing source_url', 'meowseo' )
			);
		}

		if ( empty( $row['target_url'] ) ) {
			return new WP_Error(
				'missing_target_url',
				__( 'Missing target_url', 'meowseo' )
			);
		}

		// Requirement 5.4: Validate status_code.
		$status_code = isset( $row['status_code'] ) ? absint( $row['status_code'] ) : 301;
		if ( ! in_array( $status_code, array( 301, 302, 307, 410 ), true ) ) {
			return new WP_Error(
				'invalid_status_code',
				sprintf(
					/* translators: %d: Status code */
					__( 'Invalid status code: %d. Must be 301, 302, 307, or 410.', 'meowseo' ),
					$status_code
				)
			);
		}

		// Requirement 5.5: Validate regex patterns.
		if ( strpos( $row['source_url'], 'regex:' ) === 0 ) {
			$pattern = substr( $row['source_url'], 6 );
			if ( @preg_match( $pattern, '' ) === false ) {
				return new WP_Error(
					'invalid_regex',
					sprintf(
						/* translators: %s: Regex pattern */
						__( 'Invalid regex pattern: %s', 'meowseo' ),
						$pattern
					)
				);
			}
		}

		return true;
	}

	/**
	 * Create redirect
	 *
	 * Creates a redirect record in the database.
	 * Requirement 5.6: Create redirect records for each valid row.
	 *
	 * @param array $row Row data.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function create_redirect( array $row ): bool|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';

		$source_url  = $row['source_url'];
		$target_url  = $row['target_url'];
		$status_code = isset( $row['status_code'] ) ? absint( $row['status_code'] ) : 301;
		$is_regex    = strpos( $source_url, 'regex:' ) === 0 ? 1 : 0;

		// Remove regex: prefix if present.
		if ( $is_regex ) {
			$source_url = substr( $source_url, 6 );
		}

		$result = $wpdb->insert(
			$table,
			array(
				'source_url'    => $source_url,
				'target_url'    => $target_url,
				'redirect_type' => $status_code,
				'is_regex'      => $is_regex,
				'is_active'     => 1,
				'hits'          => 0,
			),
			array( '%s', '%s', '%d', '%d', '%d', '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'database_error',
				__( 'Failed to create redirect in database.', 'meowseo' )
			);
		}

		return true;
	}

	/**
	 * Check if redirect is duplicate
	 *
	 * Checks if a redirect with the same source URL already exists.
	 * Requirement 5.12: Check for existing redirects with same source_url.
	 *
	 * @param string $source_url Source URL to check.
	 * @return bool True if duplicate exists.
	 */
	private function is_duplicate( string $source_url ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';

		// Remove regex: prefix if present.
		if ( strpos( $source_url, 'regex:' ) === 0 ) {
			$source_url = substr( $source_url, 6 );
		}

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE source_url = %s",
				$source_url
			)
		);

		return $count > 0;
	}
}
