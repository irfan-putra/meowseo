<?php
/**
 * CSV Exporter Class
 *
 * Handles exporting redirects to CSV files with proper formatting.
 *
 * Requirements: 5.2, 5.8, 5.9, 5.10
 *
 * @package MeowSEO
 * @subpackage Modules\Redirects
 * @since 2.0.0
 */

namespace MeowSEO\Modules\Redirects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV_Exporter class
 *
 * Generates CSV files from existing redirect records.
 */
class CSV_Exporter {

	/**
	 * Redirects_Admin instance
	 *
	 * @var Redirects_Admin
	 */
	private Redirects_Admin $redirect_manager;

	/**
	 * Constructor
	 *
	 * @param Redirects_Admin $redirect_manager Redirect manager instance.
	 */
	public function __construct( Redirects_Admin $redirect_manager ) {
		$this->redirect_manager = $redirect_manager;
	}

	/**
	 * Export to file
	 *
	 * Exports redirects to a CSV file and triggers download.
	 * Requirement 5.2: Export to file with download headers.
	 *
	 * @return string File path (for testing) or exits after download.
	 */
	public function export_to_file(): string {
		$csv_content = $this->export_to_string();
		$filename    = $this->generate_filename();

		// Set headers for download (Requirement 5.10).
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output CSV content.
		echo $csv_content;
		exit;
	}

	/**
	 * Export to string
	 *
	 * Exports redirects to CSV content string.
	 * Requirement 5.2: Return CSV content as string.
	 *
	 * @return string CSV content.
	 */
	public function export_to_string(): string {
		$redirects = $this->get_all_redirects();
		return $this->generate_csv_content( $redirects );
	}

	/**
	 * Get all redirects
	 *
	 * Retrieves all redirect records from database.
	 * Requirement 5.9: Include all existing redirects.
	 *
	 * @return array Array of redirect records.
	 */
	private function get_all_redirects(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'meowseo_redirects';

		$redirects = $wpdb->get_results(
			"SELECT source_url, target_url, redirect_type, is_regex, hits, created_at, last_accessed 
			FROM {$table} 
			ORDER BY id ASC",
			ARRAY_A
		);

		return $redirects ?: array();
	}

	/**
	 * Format redirect row
	 *
	 * Formats a redirect record as CSV row.
	 * Requirement 5.8: Format columns correctly.
	 *
	 * @param array $redirect Redirect record.
	 * @return array CSV row data.
	 */
	private function format_redirect_row( array $redirect ): array {
		// Add regex: prefix if needed.
		$source_url = $redirect['source_url'];
		if ( ! empty( $redirect['is_regex'] ) ) {
			$source_url = 'regex:' . $source_url;
		}

		// Requirement 5.9: Format dates as YYYY-MM-DD HH:MM:SS.
		$created_date  = ! empty( $redirect['created_at'] ) ? $redirect['created_at'] : '';
		$last_accessed = ! empty( $redirect['last_accessed'] ) ? $redirect['last_accessed'] : '';

		// Requirement 5.8: Export columns in correct order.
		return array(
			$source_url,
			$redirect['target_url'],
			$redirect['redirect_type'],
			$redirect['hits'] ?? 0,
			$created_date,
			$last_accessed,
		);
	}

	/**
	 * Generate CSV content
	 *
	 * Generates complete CSV content with header and data rows.
	 * Requirement 5.8: Include header row.
	 *
	 * @param array $redirects Array of redirect records.
	 * @return string CSV content.
	 */
	private function generate_csv_content( array $redirects ): string {
		$output = fopen( 'php://temp', 'r+' );

		// Requirement 5.8: Write header row.
		fputcsv(
			$output,
			array(
				'source_url',
				'target_url',
				'status_code',
				'hits',
				'created_date',
				'last_accessed',
			)
		);

		// Write data rows.
		foreach ( $redirects as $redirect ) {
			$row = $this->format_redirect_row( $redirect );
			fputcsv( $output, $row );
		}

		// Get content.
		rewind( $output );
		$csv_content = stream_get_contents( $output );
		fclose( $output );

		return $csv_content;
	}

	/**
	 * Generate filename
	 *
	 * Generates filename for CSV export.
	 * Requirement 5.10: Generate filename "meowseo-redirects-YYYY-MM-DD.csv".
	 *
	 * @return string Filename.
	 */
	private function generate_filename(): string {
		$date = gmdate( 'Y-m-d' );
		return "meowseo-redirects-{$date}.csv";
	}
}
