<?php
/**
 * Update Settings Page Class
 *
 * Provides an admin settings page for configuring and managing the GitHub
 * auto-update system.
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
 * Class Update_Settings_Page
 *
 * Manages the WordPress admin settings page for the GitHub update system.
 * Allows administrators to configure update settings, view status, check for
 * updates manually, and view update logs.
 *
 * @since 1.0.0
 */
class Update_Settings_Page {

	/**
	 * Configuration instance
	 *
	 * Manages update configuration settings (repository, branch, frequency, etc.)
	 *
	 * @since 1.0.0
	 * @var Update_Config
	 */
	private Update_Config $config;

	/**
	 * Update checker instance
	 *
	 * Handles checking for updates from GitHub
	 *
	 * @since 1.0.0
	 * @var GitHub_Update_Checker
	 */
	private GitHub_Update_Checker $checker;

	/**
	 * Logger instance
	 *
	 * Handles logging of update events, API requests, and errors
	 *
	 * @since 1.0.0
	 * @var Update_Logger
	 */
	private Update_Logger $logger;

	/**
	 * Constructor
	 *
	 * Initializes the settings page with required dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @param Update_Config         $config  Configuration instance.
	 * @param GitHub_Update_Checker $checker Update checker instance.
	 * @param Update_Logger         $logger  Logger instance.
	 */
	public function __construct( Update_Config $config, GitHub_Update_Checker $checker, Update_Logger $logger ) {
		$this->config  = $config;
		$this->checker = $checker;
		$this->logger  = $logger;
	}

	/**
	 * Register settings page in WordPress admin
	 *
	 * Adds the settings page to the WordPress Settings menu and registers
	 * all necessary hooks for handling form submissions and actions.
	 *
	 * This method should be called on the 'admin_menu' hook.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		// Add settings page to Settings menu.
		add_options_page(
			__( 'GitHub Updates', 'meowseo' ),           // Page title.
			__( 'GitHub Updates', 'meowseo' ),           // Menu title.
			'manage_options',                             // Required capability.
			'meowseo-github-updates',                     // Menu slug.
			array( $this, 'render_page' )                 // Callback function.
		);

		// Register hooks for form handling.
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
	}

	/**
	 * Render the settings page
	 *
	 * Displays the complete settings page including status information,
	 * configuration form, and recent logs.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		// Display any admin notices (success/error messages).
		$this->display_admin_notices();

		// Render the status section.
		$this->render_status_section();

		// Render the configuration form.
		$this->render_config_form();

		// Render the logs section.
		$this->render_logs_section();
	}

	/**
	 * Display admin notices
	 *
	 * Shows success or error messages after form submissions or actions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function display_admin_notices(): void {
		// Check for success/error messages in URL parameters.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading display messages.
		if ( isset( $_GET['message'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$message = sanitize_text_field( wp_unslash( $_GET['message'] ) );

			$messages = array(
				'settings_saved'  => __( 'Settings saved successfully.', 'meowseo' ),
				'cache_cleared'   => __( 'Cache cleared successfully.', 'meowseo' ),
				'check_completed' => __( 'Update check completed.', 'meowseo' ),
				'logs_cleared'    => __( 'Old logs cleared successfully.', 'meowseo' ),
			);

			if ( isset( $messages[ $message ] ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $message ] ) . '</p></div>';
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading display messages.
		if ( isset( $_GET['error'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error = sanitize_text_field( wp_unslash( $_GET['error'] ) );

			$errors = array(
				'invalid_nonce'      => __( 'Security check failed. Please try again.', 'meowseo' ),
				'save_failed'        => __( 'Failed to save settings. Please try again.', 'meowseo' ),
				'invalid_repository' => __( 'Invalid GitHub repository. Please check the repository settings.', 'meowseo' ),
			);

			if ( isset( $errors[ $error ] ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $errors[ $error ] ) . '</p></div>';
			}
		}
	}

	/**
	 * Render the status section
	 *
	 * Displays current update status including version information, last check time,
	 * next check time, and GitHub rate limit status.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_status_section(): void {
		// Get current version.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = MEOWSEO_FILE;
		$plugin_data = get_plugin_data( $plugin_file, false, false );
		$current_version = $plugin_data['Version'] ?? '1.0.0';

		// Get latest version from cache or checker.
		$latest_commit_data = $this->checker->get_latest_commit();
		$latest_version = '1.0.0';
		if ( null !== $latest_commit_data && ! empty( $latest_commit_data['short_sha'] ) ) {
			$latest_version = '1.0.0-' . $latest_commit_data['short_sha'];
		}

		// Check if update is available.
		$update_available = $current_version !== $latest_version;

		// Get last check time.
		$last_check_time = get_option( 'meowseo_github_last_check', 0 );

		// Calculate next check time.
		$check_frequency = $this->config->get_check_frequency();
		$next_check_time = $last_check_time > 0 ? $last_check_time + $check_frequency : 0;

		// Get rate limit information from logs.
		$rate_limit = array();
		$logs = $this->logger->get_recent_logs( 100 );
		foreach ( $logs as $log ) {
			if ( 'api_request' === $log['type'] && ! empty( $log['context']['rate_limit'] ) ) {
				$rate_limit = $log['context']['rate_limit'];
				break;
			}
		}

		// Prepare variables for the view.
		$config = $this->config;
		$nonce = wp_create_nonce( 'meowseo_update_settings' );

		// Include the view file.
		include MEOWSEO_PATH . 'includes/admin/views/update-settings.php';
	}

	/**
	 * Render the configuration form
	 *
	 * Displays the configuration form for updating update settings.
	 * This method is called by render_status_section() which includes the view file.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_config_form(): void {
		// Configuration form is rendered in the view file included by render_status_section().
		// This method is kept for consistency with the design document.
	}

	/**
	 * Render the logs section
	 *
	 * Displays recent update logs in a table format with expandable details.
	 * Shows timestamp, level, type, and message for each log entry.
	 * Includes a "Clear old logs" button to remove logs older than 30 days.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_logs_section(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Fetch recent logs (limit to 50 most recent).
		$logs = $this->logger->get_recent_logs( 50 );

		// Create nonce for clear logs action.
		$clear_logs_nonce = wp_create_nonce( 'meowseo_clear_old_logs' );

		// Include the logs view.
		include MEOWSEO_PATH . 'includes/admin/views/update-logs.php';
	}

	/**
	 * Handle form submission
	 *
	 * Routes form submissions to appropriate handlers based on the action.
	 * This method is called on the 'admin_init' hook.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_form_submission(): void {
		// Only process POST requests on the settings page.
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in individual handlers.
		if ( ! isset( $_POST['action'] ) && ! isset( $_POST['submit'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in individual handlers.
		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';

		// Route to appropriate handler.
		if ( 'check_update_now' === $action ) {
			$this->handle_check_now();
		} elseif ( 'clear_cache' === $action ) {
			$this->handle_clear_cache();
		} elseif ( 'clear_old_logs' === $action ) {
			$this->handle_clear_old_logs();
		} elseif ( isset( $_POST['submit'] ) ) {
			// Main form submission (settings save).
			$this->handle_save();
		}
	}

	/**
	 * Handle settings form submission
	 *
	 * Processes the main configuration form submission. Validates nonce and user
	 * capabilities, sanitizes inputs, validates repository accessibility, saves
	 * configuration, clears caches, and displays appropriate notices.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_save(): void {
		// Verify nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['meowseo_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meowseo_settings_nonce'] ) ), 'meowseo_update_settings' ) ) {
			wp_safe_remote_post(
				add_query_arg(
					array( 'error' => 'invalid_nonce' ),
					admin_url( 'options-general.php?page=meowseo-github-updates' )
				)
			);
			wp_redirect( add_query_arg( array( 'error' => 'invalid_nonce' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
			exit;
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		// Get current configuration for logging.
		$old_config = $this->config->get_all();

		// Sanitize and validate inputs.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$branch = isset( $_POST['branch'] ) ? sanitize_text_field( wp_unslash( $_POST['branch'] ) ) : $old_config['branch'];
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$auto_update_enabled = isset( $_POST['auto_update_enabled'] ) ? true : false;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$check_frequency = isset( $_POST['check_frequency'] ) ? absint( wp_unslash( $_POST['check_frequency'] ) ) : $old_config['check_frequency'];

		// Validate branch name format.
		if ( ! preg_match( '/^[a-zA-Z0-9\/_.-]+$/', $branch ) ) {
			wp_redirect( add_query_arg( array( 'error' => 'invalid_branch' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
			exit;
		}

		// Validate check frequency is at least 1 hour.
		if ( $check_frequency < 3600 ) {
			$check_frequency = 3600;
		}

		// Prepare new configuration.
		$new_config = array(
			'repo_owner'          => $old_config['repo_owner'],
			'repo_name'           => $old_config['repo_name'],
			'branch'              => $branch,
			'auto_update_enabled' => $auto_update_enabled,
			'check_frequency'     => $check_frequency,
		);

		// Validate repository accessibility using GitHub API.
		if ( ! $this->config->validate_repository() ) {
			wp_redirect( add_query_arg( array( 'error' => 'invalid_repository' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
			exit;
		}

		// Save configuration.
		if ( ! $this->config->save( $new_config ) ) {
			wp_redirect( add_query_arg( array( 'error' => 'save_failed' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
			exit;
		}

		// Clear update caches after successful save.
		$this->checker->clear_cache();

		// Log configuration change.
		$this->logger->log_config_change( $old_config, $new_config );

		// Redirect with success message.
		wp_redirect( add_query_arg( array( 'message' => 'settings_saved' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
		exit;
	}

	/**
	 * Handle manual update check
	 *
	 * Processes the "Check for updates now" button submission. Clears caches,
	 * triggers an immediate update check, and displays the result.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_check_now(): void {
		// Verify nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['meowseo_check_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meowseo_check_nonce'] ) ), 'meowseo_check_update_now' ) ) {
			wp_redirect( add_query_arg( array( 'error' => 'invalid_nonce' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
			exit;
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		// Clear all update caches.
		$this->checker->clear_cache();

		// Trigger immediate update check.
		$transient = get_site_transient( 'update_plugins' );
		$transient = $this->checker->check_for_update( $transient );
		set_site_transient( 'update_plugins', $transient );

		// Log manual check.
		$this->logger->log_check( true, null, array( 'manual' => true ) );

		// Redirect with success message.
		wp_redirect( add_query_arg( array( 'message' => 'check_completed' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
		exit;
	}

	/**
	 * Handle cache clear
	 *
	 * Processes the "Clear cache" button submission. Clears all update-related
	 * caches and displays a success message.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_clear_cache(): void {
		// Verify nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['meowseo_cache_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meowseo_cache_nonce'] ) ), 'meowseo_clear_cache' ) ) {
			wp_redirect( add_query_arg( array( 'error' => 'invalid_nonce' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
			exit;
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		// Clear all update caches.
		$this->checker->clear_cache();

		// Log cache clear action.
		$this->logger->log_check( true, null, array( 'action' => 'cache_cleared' ) );

		// Redirect with success message.
		wp_redirect( add_query_arg( array( 'message' => 'cache_cleared' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
		exit;
	}

	/**
	 * Handle clear old logs
	 *
	 * Processes the "Clear old logs" button submission. Removes logs older than
	 * 30 days and displays a success message with the number of logs removed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_clear_old_logs(): void {
		// Verify nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['meowseo_clear_logs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meowseo_clear_logs_nonce'] ) ), 'meowseo_clear_old_logs' ) ) {
			wp_redirect( add_query_arg( array( 'error' => 'invalid_nonce' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
			exit;
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ) );
		}

		// Clear old logs (older than 30 days).
		$removed_count = $this->logger->clear_old_logs( 30 );

		// Log the action.
		$this->logger->log_check( true, null, array( 'action' => 'logs_cleared', 'removed_count' => $removed_count ) );

		// Redirect with success message.
		wp_redirect( add_query_arg( array( 'message' => 'logs_cleared' ), admin_url( 'options-general.php?page=meowseo-github-updates' ) ) );
		exit;
	}
}
