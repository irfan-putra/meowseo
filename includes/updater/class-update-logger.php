<?php
/**
 * Update Logger Class
 *
 * Handles logging for GitHub update operations.
 *
 * @package MeowSEO
 * @subpackage Updater
 * @since 1.0.0
 */

namespace MeowSEO\Updater;

/**
 * Class Update_Logger
 *
 * Logs update-related events including update checks, API requests,
 * installations, and configuration changes.
 *
 * @since 1.0.0
 */
class Update_Logger {

	/**
	 * Option name for storing logs
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'meowseo_github_update_logs';

	/**
	 * Maximum number of log entries to keep
	 *
	 * @var int
	 */
	private const MAX_ENTRIES = 100;

	/**
	 * Number of days to keep logs
	 *
	 * @var int
	 */
	private const LOG_RETENTION_DAYS = 30;

	/**
	 * Log an update check event
	 *
	 * @since 1.0.0
	 *
	 * @param bool        $success Whether the check was successful.
	 * @param string|null $error   Error message if check failed.
	 * @param array       $context Additional context data.
	 * @return void
	 */
	public function log_check( bool $success, ?string $error = null, array $context = [] ): void {
		$message = $success
			? 'Update check completed successfully'
			: 'Update check failed';

		if ( $error ) {
			$context['error'] = $error;
		}

		$this->write_log( $success ? 'info' : 'error', 'check', $message, $context );
	}

	/**
	 * Log a GitHub API request
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint      API endpoint called.
	 * @param int    $response_code HTTP response code.
	 * @param array  $rate_limit    Rate limit information.
	 * @return void
	 */
	public function log_api_request( string $endpoint, int $response_code, array $rate_limit = [] ): void {
		$context = [
			'endpoint'      => $endpoint,
			'response_code' => $response_code,
		];

		if ( ! empty( $rate_limit ) ) {
			$context['rate_limit'] = $rate_limit;
		}

		$level   = $response_code >= 200 && $response_code < 300 ? 'info' : 'error';
		$message = sprintf( 'GitHub API request to %s returned %d', $endpoint, $response_code );

		$this->write_log( $level, 'api_request', $message, $context );
	}

	/**
	 * Log an update installation event
	 *
	 * @since 1.0.0
	 *
	 * @param bool        $success Whether the installation was successful.
	 * @param string      $version Version being installed.
	 * @param string|null $error   Error message if installation failed.
	 * @param array       $context Additional context data.
	 * @return void
	 */
	public function log_installation( bool $success, string $version, ?string $error = null, array $context = [] ): void {
		$message = $success
			? sprintf( 'Update to version %s installed successfully', $version )
			: sprintf( 'Update to version %s failed', $version );

		$context['version'] = $version;

		if ( $error ) {
			$context['error'] = $error;
		}

		$this->write_log( $success ? 'info' : 'error', 'installation', $message, $context );
	}

	/**
	 * Log a configuration change event
	 *
	 * @since 1.0.0
	 *
	 * @param array $old_config Previous configuration.
	 * @param array $new_config New configuration.
	 * @return void
	 */
	public function log_config_change( array $old_config, array $new_config ): void {
		$changes = [];

		// Identify what changed.
		foreach ( $new_config as $key => $value ) {
			if ( ! isset( $old_config[ $key ] ) || $old_config[ $key ] !== $value ) {
				$changes[ $key ] = [
					'old' => $old_config[ $key ] ?? null,
					'new' => $value,
				];
			}
		}

		$context = [
			'changes' => $changes,
		];

		$message = 'Update configuration changed';

		$this->write_log( 'info', 'config_change', $message, $context );
	}

	/**
	 * Get recent log entries
	 *
	 * @since 1.0.0
	 *
	 * @param int $limit Maximum number of entries to retrieve.
	 * @return array Array of log entries.
	 */
	public function get_recent_logs( int $limit = 50 ): array {
		$logs = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $logs ) ) {
			return [];
		}

		return array_slice( $logs, 0, $limit );
	}

	/**
	 * Clear logs older than specified number of days
	 *
	 * @since 1.0.0
	 *
	 * @param int $days Number of days to keep logs (default: 30).
	 * @return int Number of logs removed.
	 */
	public function clear_old_logs( int $days = self::LOG_RETENTION_DAYS ): int {
		$logs = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $logs ) || empty( $logs ) ) {
			return 0;
		}

		$cutoff_time = time() - ( $days * DAY_IN_SECONDS );
		$initial_count = count( $logs );

		// Filter out old logs.
		$logs = array_filter(
			$logs,
			function ( $log ) use ( $cutoff_time ) {
				if ( ! isset( $log['timestamp'] ) ) {
					return false;
				}

				$log_time = is_numeric( $log['timestamp'] )
					? $log['timestamp']
					: strtotime( $log['timestamp'] );

				return $log_time >= $cutoff_time;
			}
		);

		// Re-index array.
		$logs = array_values( $logs );

		update_option( self::OPTION_NAME, $logs );

		return $initial_count - count( $logs );
	}

	/**
	 * Write a log entry
	 *
	 * @since 1.0.0
	 *
	 * @param string $level   Log level (info, warning, error).
	 * @param string $type    Log type (check, api_request, installation, config_change).
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private function write_log( string $level, string $type, string $message, array $context = [] ): void {
		$log_entry = [
			'timestamp' => current_time( 'mysql' ),
			'level'     => $level,
			'type'      => $type,
			'message'   => $message,
			'context'   => $context,
		];

		// Get existing logs.
		$logs = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $logs ) ) {
			$logs = [];
		}

		// Add new entry at the beginning.
		array_unshift( $logs, $log_entry );

		// Keep only the most recent entries.
		$logs = array_slice( $logs, 0, self::MAX_ENTRIES );

		// Save logs.
		update_option( self::OPTION_NAME, $logs );

		// Also log to WordPress debug log if WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$debug_message = sprintf(
				'MeowSEO Update [%s][%s]: %s',
				strtoupper( $level ),
				$type,
				$message
			);

			if ( ! empty( $context ) ) {
				$debug_message .= ' - ' . wp_json_encode( $context );
			}

			error_log( $debug_message );
		}
	}
}
