<?php
/**
 * Update Security Class
 *
 * Handles security validation, input sanitization, and output escaping
 * for the GitHub auto-update system.
 *
 * @package MeowSEO
 * @subpackage Updater
 * @since 1.0.0
 */

namespace MeowSEO\Updater;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Update_Security
 *
 * Provides security utilities for input validation, sanitization, and output escaping.
 *
 * @since 1.0.0
 */
class Update_Security {

	/**
	 * Validate commit ID format
	 *
	 * Validates that a commit ID matches the expected format (7-40 hexadecimal characters).
	 *
	 * @since 1.0.0
	 *
	 * @param string $commit_id Commit ID to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_commit_id( string $commit_id ): bool {
		// Must be 7-40 hexadecimal characters.
		return (bool) preg_match( '/^[a-f0-9]{7,40}$/i', $commit_id );
	}

	/**
	 * Validate branch name format
	 *
	 * Validates that a branch name matches the expected format.
	 * Allows alphanumeric characters, slashes, underscores, hyphens, and dots.
	 *
	 * @since 1.0.0
	 *
	 * @param string $branch Branch name to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_branch_name( string $branch ): bool {
		// Must not be empty.
		if ( empty( $branch ) ) {
			return false;
		}

		// Must match Git branch name format.
		return (bool) preg_match( '/^[a-zA-Z0-9\/_.-]+$/', $branch );
	}

	/**
	 * Validate repository owner format
	 *
	 * Validates that a repository owner matches GitHub username format.
	 * GitHub usernames can contain alphanumeric characters and hyphens,
	 * but cannot start or end with a hyphen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $owner Repository owner to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_repo_owner( string $owner ): bool {
		// Must not be empty.
		if ( empty( $owner ) ) {
			return false;
		}

		// Must match GitHub username format.
		return (bool) preg_match( '/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$/', $owner );
	}

	/**
	 * Validate repository name format
	 *
	 * Validates that a repository name matches GitHub repository name format.
	 * GitHub repository names can contain alphanumeric characters, dots,
	 * underscores, and hyphens.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Repository name to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_repo_name( string $name ): bool {
		// Must not be empty.
		if ( empty( $name ) ) {
			return false;
		}

		// Must match GitHub repository name format.
		return (bool) preg_match( '/^[a-zA-Z0-9._-]+$/', $name );
	}

	/**
	 * Validate GitHub API URL
	 *
	 * Validates that a URL is a valid GitHub API URL to prevent SSRF attacks.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL to validate.
	 * @return bool True if valid GitHub API URL, false otherwise.
	 */
	public static function validate_github_api_url( string $url ): bool {
		// Must start with https://api.github.com.
		if ( 0 !== strpos( $url, 'https://api.github.com' ) ) {
			return false;
		}

		// Must be a valid URL using filter_var.
		return false !== filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * Validate GitHub archive URL
	 *
	 * Validates that a URL is a valid GitHub archive URL to prevent SSRF attacks.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL to validate.
	 * @return bool True if valid GitHub archive URL, false otherwise.
	 */
	public static function validate_github_archive_url( string $url ): bool {
		// Must start with https://github.com.
		if ( 0 !== strpos( $url, 'https://github.com' ) ) {
			return false;
		}

		// Must contain /archive/ and end with .zip.
		if ( false === strpos( $url, '/archive/' ) || ! str_ends_with( $url, '.zip' ) ) {
			return false;
		}

		// Must be a valid URL using filter_var.
		return false !== filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * Sanitize commit ID
	 *
	 * Sanitizes a commit ID by removing any non-hexadecimal characters.
	 *
	 * @since 1.0.0
	 *
	 * @param string $commit_id Commit ID to sanitize.
	 * @return string Sanitized commit ID.
	 */
	public static function sanitize_commit_id( string $commit_id ): string {
		// Remove any non-hexadecimal characters.
		return preg_replace( '/[^a-f0-9]/i', '', $commit_id );
	}

	/**
	 * Sanitize branch name
	 *
	 * Sanitizes a branch name by removing invalid characters.
	 *
	 * @since 1.0.0
	 *
	 * @param string $branch Branch name to sanitize.
	 * @return string Sanitized branch name.
	 */
	public static function sanitize_branch_name( string $branch ): string {
		// Remove any invalid characters.
		return preg_replace( '/[^a-zA-Z0-9\/_.-]/', '', $branch );
	}

	/**
	 * Escape HTML output
	 *
	 * Escapes HTML special characters for safe output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	public static function escape_html( string $text ): string {
		return esc_html( $text );
	}

	/**
	 * Escape URL output
	 *
	 * Escapes URL for safe output in href attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL to escape.
	 * @return string Escaped URL.
	 */
	public static function escape_url( string $url ): string {
		return esc_url( $url );
	}

	/**
	 * Escape attribute output
	 *
	 * Escapes text for safe output in HTML attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	public static function escape_attr( string $text ): string {
		return esc_attr( $text );
	}

	/**
	 * Verify nonce
	 *
	 * Verifies a WordPress nonce for security.
	 *
	 * @since 1.0.0
	 *
	 * @param string $nonce Nonce value to verify.
	 * @param string $action Nonce action.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	public static function verify_nonce( string $nonce, string $action ): bool {
		return wp_verify_nonce( $nonce, $action ) !== false;
	}

	/**
	 * Check user capability
	 *
	 * Checks if the current user has the required capability.
	 *
	 * @since 1.0.0
	 *
	 * @param string $capability Capability to check.
	 * @return bool True if user has capability, false otherwise.
	 */
	public static function check_capability( string $capability ): bool {
		return current_user_can( $capability );
	}

	/**
	 * Validate ZIP file
	 *
	 * Validates that a file is a valid ZIP archive and contains expected plugin files.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_path Path to the ZIP file to validate.
	 * @return bool True if valid ZIP file, false otherwise.
	 */
	public static function validate_zip_file( string $file_path ): bool {
		// Check if file exists and is readable.
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return false;
		}

		// Check if file is a valid ZIP archive.
		$zip = new \ZipArchive();
		$result = $zip->open( $file_path );

		if ( true !== $result ) {
			return false;
		}

		// Check if ZIP contains expected plugin files (meowseo.php).
		$has_plugin_file = false;
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$filename = $zip->getNameIndex( $i );
			if ( false !== strpos( $filename, 'meowseo.php' ) ) {
				$has_plugin_file = true;
				break;
			}
		}

		$zip->close();

		return $has_plugin_file;
	}

	/**
	 * Validate ZIP structure
	 *
	 * Validates that a ZIP file has the expected structure (nested directory).
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_path Path to the ZIP file to validate.
	 * @return bool True if ZIP structure is valid, false otherwise.
	 */
	public static function validate_zip_structure( string $file_path ): bool {
		// Check if file exists and is readable.
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return false;
		}

		// Check if file is a valid ZIP archive.
		$zip = new \ZipArchive();
		$result = $zip->open( $file_path );

		if ( true !== $result ) {
			return false;
		}

		// GitHub archives have a nested directory structure like: meowseo-abc1234/
		// Check if all files are in a subdirectory.
		$root_dir = null;
		$valid_structure = true;

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$filename = $zip->getNameIndex( $i );

			// Skip empty entries.
			if ( empty( $filename ) ) {
				continue;
			}

			// Extract the root directory name.
			$parts = explode( '/', $filename );
			$current_root = $parts[0];

			if ( null === $root_dir ) {
				$root_dir = $current_root;
			} elseif ( $root_dir !== $current_root ) {
				// Files are not all in the same root directory.
				$valid_structure = false;
				break;
			}
		}

		$zip->close();

		return $valid_structure && null !== $root_dir;
	}
}
