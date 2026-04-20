<?php
/**
 * CSV Generator class for RFC 4180 compliant CSV generation.
 *
 * Handles proper escaping and formatting of CSV data.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Bulk;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Generator class.
 *
 * Generates RFC 4180 compliant CSV output with proper escaping.
 */
class CSV_Generator {

	/**
	 * CSV rows.
	 *
	 * @var array
	 */
	private array $rows = array();

	/**
	 * Add a row to the CSV.
	 *
	 * @param array $row Array of values for the row.
	 * @return void
	 */
	public function add_row( array $row ): void {
		$this->rows[] = $row;
	}

	/**
	 * Generate RFC 4180 compliant CSV string.
	 *
	 * Validates: Requirements 5.5, 5.7
	 *
	 * @return string CSV formatted string.
	 */
	public function generate(): string {
		$csv = '';

		foreach ( $this->rows as $row ) {
			$csv .= $this->escape_row( $row ) . "\r\n";
		}

		return $csv;
	}

	/**
	 * Escape a row according to RFC 4180.
	 *
	 * @param array $row Array of values.
	 * @return string Escaped row as CSV line.
	 */
	private function escape_row( array $row ): string {
		$escaped_fields = array();

		foreach ( $row as $field ) {
			$escaped_fields[] = $this->escape_field( (string) $field );
		}

		return implode( ',', $escaped_fields );
	}

	/**
	 * Escape a field according to RFC 4180.
	 *
	 * RFC 4180 rules:
	 * 1. Fields containing line breaks, double quotes, and commas should be enclosed in double quotes
	 * 2. If double quotes are used to enclose fields, then a double quote appearing inside a field
	 *    must be escaped by preceding it with another double quote
	 *
	 * @param string $field Field value to escape.
	 * @return string Escaped field.
	 */
	private function escape_field( string $field ): string {
		// Check if field needs escaping.
		if ( strpos( $field, '"' ) !== false || strpos( $field, ',' ) !== false || strpos( $field, "\n" ) !== false || strpos( $field, "\r" ) !== false ) {
			// Escape double quotes by doubling them.
			$field = str_replace( '"', '""', $field );

			// Enclose field in double quotes.
			$field = '"' . $field . '"';
		}

		return $field;
	}
}
