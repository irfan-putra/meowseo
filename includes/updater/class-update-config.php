<?php
/**
 * Update Configuration Class
 *
 * Manages configuration settings for the GitHub auto-update system.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Updater;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update_Config class.
 *
 * Handles storage and retrieval of update configuration settings.
 */
class Update_Config {

	/**
	 * Option name for storing configuration in WordPress options table.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'meowseo_github_update_config';

	/**
	 * Default configuration values.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULTS = array(
		'repo_owner'           => 'akbarbahaulloh',
		'repo_name'            => 'meowseo',
		'branch'               => 'main',
		'auto_update_enabled'  => true,
		'check_frequency'      => 43200, // 12 hours in seconds.
		'last_check'           => 0,
	);

	/**
	 * Cached configuration data.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $config = null;

	/**
	 * Get repository owner.
	 *
	 * @return string Repository owner username.
	 */
	public function get_repo_owner(): string {
		$config = $this->get_all();
		return $config['repo_owner'];
	}

	/**
	 * Get repository name.
	 *
	 * @return string Repository name.
	 */
	public function get_repo_name(): string {
		$config = $this->get_all();
		return $config['repo_name'];
	}

	/**
	 * Get branch to track for updates.
	 *
	 * @return string Branch name (e.g., 'main', 'master', 'develop').
	 */
	public function get_branch(): string {
		$config = $this->get_all();
		return $config['branch'];
	}

	/**
	 * Check if automatic updates are enabled.
	 *
	 * @return bool True if auto-updates are enabled, false otherwise.
	 */
	public function is_auto_update_enabled(): bool {
		$config = $this->get_all();
		return (bool) $config['auto_update_enabled'];
	}

	/**
	 * Get update check frequency in seconds.
	 *
	 * @return int Frequency in seconds (default: 43200 = 12 hours).
	 */
	public function get_check_frequency(): int {
		$config = $this->get_all();
		return (int) $config['check_frequency'];
	}

	/**
	 * Get timestamp of last update check.
	 *
	 * @return int Unix timestamp of last check, or 0 if never checked.
	 */
	public function get_last_check(): int {
		$config = $this->get_all();
		return (int) $config['last_check'];
	}

	/**
	 * Save configuration settings.
	 *
	 * Validates and sanitizes input before saving to WordPress options.
	 * Note: repo_owner and repo_name are read-only and cannot be changed.
	 *
	 * @param array<string, mixed> $config Configuration array to save.
	 * @return bool True on success, false on failure.
	 */
	public function save( array $config ): bool {
		// Get current config to merge with new values.
		$current = $this->get_all();

		// Sanitize and validate inputs.
		// Note: repo_owner and repo_name are always taken from defaults (read-only).
		$sanitized = array(
			'repo_owner'          => self::DEFAULTS['repo_owner'],
			'repo_name'           => self::DEFAULTS['repo_name'],
			'branch'              => isset( $config['branch'] ) ? sanitize_text_field( $config['branch'] ) : $current['branch'],
			'auto_update_enabled' => isset( $config['auto_update_enabled'] ) ? (bool) $config['auto_update_enabled'] : $current['auto_update_enabled'],
			'check_frequency'     => isset( $config['check_frequency'] ) ? absint( $config['check_frequency'] ) : $current['check_frequency'],
			'last_check'          => isset( $config['last_check'] ) ? absint( $config['last_check'] ) : $current['last_check'],
		);

		// Validate repository owner format (alphanumeric, hyphens allowed).
		if ( ! $this->validate_repo_owner( $sanitized['repo_owner'] ) ) {
			return false;
		}

		// Validate repository name format (alphanumeric, dots, underscores, hyphens allowed).
		if ( ! $this->validate_repo_name( $sanitized['repo_name'] ) ) {
			return false;
		}

		// Validate branch name format (alphanumeric, slashes, underscores, hyphens, dots allowed).
		if ( ! $this->validate_branch_name( $sanitized['branch'] ) ) {
			return false;
		}

		// Ensure check frequency is at least 1 hour (3600 seconds) to respect rate limits.
		if ( $sanitized['check_frequency'] < 3600 ) {
			$sanitized['check_frequency'] = 3600;
		}

		// Save to WordPress options.
		$result = update_option( self::OPTION_NAME, $sanitized );

		// Clear cached config.
		$this->config = null;

		return $result;
	}

	/**
	 * Get all configuration settings.
	 *
	 * Returns merged configuration with defaults for any missing values.
	 *
	 * @return array<string, mixed> Configuration array.
	 */
	public function get_all(): array {
		// Return cached config if available.
		if ( null !== $this->config ) {
			return $this->config;
		}

		// Get config from WordPress options.
		$stored = get_option( self::OPTION_NAME, array() );

		// Merge with defaults to ensure all keys exist.
		$this->config = wp_parse_args( $stored, self::DEFAULTS );

		return $this->config;
	}

	/**
	 * Validate repository accessibility via GitHub API.
	 *
	 * Makes a lightweight API request to verify the repository exists and is accessible.
	 *
	 * @return bool True if repository is accessible, false otherwise.
	 */
	public function validate_repository(): bool {
		$owner = $this->get_repo_owner();
		$repo  = $this->get_repo_name();

		// Build API endpoint URL.
		$url = sprintf(
			'https://api.github.com/repos/%s/%s',
			rawurlencode( $owner ),
			rawurlencode( $repo )
		);

		// Make API request.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => 'MeowSEO-Updater/1.0 (WordPress Plugin)',
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// Check response code (200 = success).
		$response_code = wp_remote_retrieve_response_code( $response );
		return 200 === $response_code;
	}

	/**
	 * Update last check timestamp.
	 *
	 * @param int|null $timestamp Unix timestamp, or null to use current time.
	 * @return bool True on success, false on failure.
	 */
	public function update_last_check( ?int $timestamp = null ): bool {
		$config = $this->get_all();
		$config['last_check'] = $timestamp ?? time();
		return $this->save( $config );
	}

	/**
	 * Validate repository owner format.
	 *
	 * GitHub usernames can contain alphanumeric characters and hyphens,
	 * but cannot start or end with a hyphen.
	 *
	 * @param string $owner Repository owner to validate.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_repo_owner( string $owner ): bool {
		// Must not be empty.
		if ( empty( $owner ) ) {
			return false;
		}

		// Must match GitHub username format.
		return (bool) preg_match( '/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$/', $owner );
	}

	/**
	 * Validate repository name format.
	 *
	 * GitHub repository names can contain alphanumeric characters, dots,
	 * underscores, and hyphens.
	 *
	 * @param string $name Repository name to validate.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_repo_name( string $name ): bool {
		// Must not be empty.
		if ( empty( $name ) ) {
			return false;
		}

		// Must match GitHub repository name format.
		return (bool) preg_match( '/^[a-zA-Z0-9._-]+$/', $name );
	}

	/**
	 * Validate branch name format.
	 *
	 * Git branch names can contain alphanumeric characters, slashes,
	 * underscores, hyphens, and dots.
	 *
	 * @param string $branch Branch name to validate.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_branch_name( string $branch ): bool {
		// Must not be empty.
		if ( empty( $branch ) ) {
			return false;
		}

		// Must match Git branch name format (simplified).
		return (bool) preg_match( '/^[a-zA-Z0-9\/_.-]+$/', $branch );
	}

	/**
	 * Reset configuration to defaults.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function reset(): bool {
		$this->config = null;
		return update_option( self::OPTION_NAME, self::DEFAULTS );
	}

	/**
	 * Delete configuration from database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete(): bool {
		$this->config = null;
		return delete_option( self::OPTION_NAME );
	}
}
