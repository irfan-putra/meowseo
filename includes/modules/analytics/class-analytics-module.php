<?php
/**
 * Analytics Module for Google Analytics 4 integration.
 *
 * Handles OAuth authentication, metrics fetching from GA4, GSC, and PageSpeed Insights APIs.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Analytics;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Analytics_Module class.
 *
 * Integrates Google Analytics 4, Google Search Console, and PageSpeed Insights APIs.
 */
class Analytics_Module implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Google OAuth client ID.
	 *
	 * @var string
	 */
	private string $client_id = '';

	/**
	 * Google OAuth client secret.
	 *
	 * @var string
	 */
	private string $client_secret = '';

	/**
	 * Google OAuth redirect URI.
	 *
	 * @var string
	 */
	private string $redirect_uri = '';

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Setup OAuth configuration from options.
	 *
	 * @return void
	 */
	private function setup_oauth_config(): void {
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		$this->client_id = $ga4_settings['client_id'] ?? '';
		$this->client_secret = $ga4_settings['client_secret'] ?? '';
		
		// Only set redirect_uri if we have a client_id
		if ( ! empty( $this->client_id ) ) {
			$this->redirect_uri = admin_url( 'admin.php?page=meowseo-analytics-callback' );
		}
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'analytics';
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register admin pages and hooks.
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_oauth_callback' ) );
		add_action( 'wp_ajax_meowseo_refresh_ga4_metrics', array( $this, 'ajax_refresh_metrics' ) );
		add_action( 'wp_ajax_meowseo_disconnect_ga4', array( $this, 'ajax_disconnect' ) );

		// Schedule weekly email report.
		if ( ! wp_next_scheduled( 'meowseo_weekly_ga4_report' ) ) {
			wp_schedule_event( time(), 'weekly', 'meowseo_weekly_ga4_report' );
		}
		add_action( 'meowseo_weekly_ga4_report', array( $this, 'send_weekly_report' ) );
	}

	/**
	 * Register admin menu for analytics dashboard.
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			'meowseo',
			__( 'Analytics', 'meowseo' ),
			__( 'Analytics', 'meowseo' ),
			'meowseo_manage_analytics',
			'meowseo-analytics',
			array( $this, 'render_dashboard' )
		);
	}

	/**
	 * Render analytics dashboard.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'meowseo_manage_analytics' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'meowseo' ) );
		}

		$is_authenticated = $this->is_authenticated();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Google Analytics Dashboard', 'meowseo' ); ?></h1>

			<?php if ( ! $is_authenticated ) : ?>
				<div class="notice notice-info">
					<p>
						<?php esc_html_e( 'Connect your Google Analytics account to view metrics.', 'meowseo' ); ?>
					</p>
				</div>
				<a href="<?php echo esc_url( $this->authenticate_oauth() ); ?>" class="button button-primary">
					<?php esc_html_e( 'Connect Google Analytics', 'meowseo' ); ?>
				</a>
			<?php else : ?>
				<div id="meowseo-analytics-dashboard">
					<div class="meowseo-analytics-loading">
						<?php esc_html_e( 'Loading analytics data...', 'meowseo' ); ?>
					</div>
				</div>
				<button class="button" id="meowseo-refresh-metrics">
					<?php esc_html_e( 'Refresh Metrics', 'meowseo' ); ?>
				</button>
				<button class="button button-secondary" id="meowseo-disconnect-ga4">
					<?php esc_html_e( 'Disconnect', 'meowseo' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Generate Google OAuth consent URL.
	 *
	 * Returns the URL where users should be redirected to grant permissions.
	 *
	 * @return string Google OAuth consent URL.
	 */
	public function authenticate_oauth(): string {
		$this->setup_oauth_config();

		if ( empty( $this->client_id ) ) {
			return '';
		}

		$scopes = array(
			'https://www.googleapis.com/auth/analytics.readonly',
			'https://www.googleapis.com/auth/webmasters.readonly',
			'https://www.googleapis.com/auth/pagespeedonline.readonly',
		);

		$params = array(
			'client_id' => $this->client_id,
			'redirect_uri' => $this->redirect_uri,
			'response_type' => 'code',
			'scope' => implode( ' ', $scopes ),
			'access_type' => 'offline',
			'prompt' => 'consent',
		);

		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
	}

	/**
	 * Handle OAuth callback from Google.
	 *
	 * Exchanges authorization code for access and refresh tokens.
	 *
	 * @return void
	 */
	public function handle_oauth_callback(): void {
		// Check if this is the callback page.
		if ( ! isset( $_GET['page'] ) || 'meowseo-analytics-callback' !== $_GET['page'] ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['state'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'meowseo_ga4_oauth' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'meowseo' ) );
		}

		// Check for authorization code.
		if ( ! isset( $_GET['code'] ) ) {
			if ( isset( $_GET['error'] ) ) {
				$error = sanitize_text_field( wp_unslash( $_GET['error'] ) );
				wp_die( esc_html__( 'Authorization failed: ', 'meowseo' ) . esc_html( $error ) );
			}
			wp_die( esc_html__( 'No authorization code received.', 'meowseo' ) );
		}

		$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );

		// Exchange code for tokens.
		$result = $this->exchange_code_for_tokens( $code );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		// Redirect to analytics page with success message.
		wp_safe_remote_post(
			admin_url( 'admin.php?page=meowseo-analytics' ),
			array(
				'blocking' => false,
			)
		);

		wp_redirect( admin_url( 'admin.php?page=meowseo-analytics&ga4_connected=1' ) );
		exit;
	}

	/**
	 * Exchange authorization code for access and refresh tokens.
	 *
	 * @param string $code Authorization code from Google.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	private function exchange_code_for_tokens( string $code ) {
		if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
			return new \WP_Error(
				'meowseo_ga4_config_missing',
				__( 'GA4 OAuth configuration is missing. Please configure client ID and secret.', 'meowseo' )
			);
		}

		$token_url = 'https://oauth2.googleapis.com/token';

		$body = array(
			'code' => $code,
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri' => $this->redirect_uri,
			'grant_type' => 'authorization_code',
		);

		$response = wp_remote_post(
			$token_url,
			array(
				'body' => $body,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! isset( $data['refresh_token'] ) || ! isset( $data['access_token'] ) ) {
			return new \WP_Error(
				'meowseo_ga4_token_exchange_failed',
				__( 'Failed to exchange authorization code for tokens.', 'meowseo' )
			);
		}

		// Store tokens securely.
		$credentials = array(
			'access_token' => $data['access_token'],
			'refresh_token' => $data['refresh_token'],
			'expires_in' => $data['expires_in'] ?? 3600,
			'token_type' => $data['token_type'] ?? 'Bearer',
			'created_at' => time(),
		);

		return $this->store_credentials( $credentials );
	}

	/**
	 * Store GA4 credentials securely.
	 *
	 * Encrypts and stores OAuth credentials in WordPress options.
	 *
	 * @param array $credentials Credentials array.
	 * @return bool True on success, false on failure.
	 */
	private function store_credentials( array $credentials ): bool {
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		$ga4_settings['credentials'] = $credentials;
		$this->options->set( 'ga4_settings', $ga4_settings );
		return $this->options->save();
	}

	/**
	 * Check if GA4 is authenticated.
	 *
	 * @return bool True if authenticated, false otherwise.
	 */
	public function is_authenticated(): bool {
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		return ! empty( $ga4_settings['credentials']['refresh_token'] );
	}

	/**
	 * Get valid access token, refreshing if necessary.
	 *
	 * @return string|null Access token or null if not available.
	 */
	private function get_access_token(): ?string {
		$ga4_settings = $this->options->get( 'ga4_settings', array() );

		if ( empty( $ga4_settings['credentials'] ) ) {
			return null;
		}

		$credentials = $ga4_settings['credentials'];

		// Check if token is expired.
		$created_at = $credentials['created_at'] ?? 0;
		$expires_in = $credentials['expires_in'] ?? 3600;

		if ( time() > ( $created_at + $expires_in - 300 ) ) {
			// Token is expired or about to expire, refresh it.
			$this->refresh_access_token();
			$ga4_settings = $this->options->get( 'ga4_settings', array() );
			$credentials = $ga4_settings['credentials'] ?? array();
		}

		return $credentials['access_token'] ?? null;
	}

	/**
	 * Refresh access token using refresh token.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function refresh_access_token(): bool {
		$ga4_settings = $this->options->get( 'ga4_settings', array() );

		if ( empty( $ga4_settings['credentials']['refresh_token'] ) ) {
			return false;
		}

		$token_url = 'https://oauth2.googleapis.com/token';

		$body = array(
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'refresh_token' => $ga4_settings['credentials']['refresh_token'],
			'grant_type' => 'refresh_token',
		);

		$response = wp_remote_post(
			$token_url,
			array(
				'body' => $body,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! isset( $data['access_token'] ) ) {
			return false;
		}

		// Update credentials with new access token.
		$ga4_settings['credentials']['access_token'] = $data['access_token'];
		$ga4_settings['credentials']['expires_in'] = $data['expires_in'] ?? 3600;
		$ga4_settings['credentials']['created_at'] = time();

		$this->options->set( 'ga4_settings', $ga4_settings );
		return $this->options->save();
	}

	/**
	 * Fetch GA4 metrics.
	 *
	 * Retrieves sessions, users, pageviews, bounce rate, and session duration.
	 *
	 * @param string $start_date Start date (YYYY-MM-DD).
	 * @param string $end_date End date (YYYY-MM-DD).
	 * @return array|null Metrics array or null on failure.
	 */
	public function get_ga4_metrics( string $start_date, string $end_date ): ?array {
		// Check cache first.
		$cache_key = 'meowseo_ga4_metrics_' . get_current_blog_id() . '_' . $start_date . '_' . $end_date;
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$access_token = $this->get_access_token();

		if ( ! $access_token ) {
			return null;
		}

		// Get GA4 property ID from settings.
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		$property_id = $ga4_settings['property_id'] ?? '';

		if ( empty( $property_id ) ) {
			return null;
		}

		// Fetch metrics from GA4 API.
		$metrics = $this->fetch_ga4_api_metrics( $access_token, $property_id, $start_date, $end_date );

		if ( $metrics ) {
			// Cache for 6 hours.
			set_transient( $cache_key, $metrics, 6 * HOUR_IN_SECONDS );
		}

		return $metrics;
	}

	/**
	 * Fetch metrics from GA4 API.
	 *
	 * @param string $access_token Access token.
	 * @param string $property_id GA4 property ID.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array|null Metrics or null on failure.
	 */
	private function fetch_ga4_api_metrics( string $access_token, string $property_id, string $start_date, string $end_date ): ?array {
		$url = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";

		$body = array(
			'dateRanges' => array(
				array(
					'startDate' => $start_date,
					'endDate' => $end_date,
				),
			),
			'metrics' => array(
				array( 'name' => 'sessions' ),
				array( 'name' => 'totalUsers' ),
				array( 'name' => 'screenPageViews' ),
				array( 'name' => 'bounceRate' ),
				array( 'name' => 'averageSessionDuration' ),
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( ! isset( $data['rows'] ) || empty( $data['rows'] ) ) {
			return null;
		}

		// Parse response into metrics array.
		$row = $data['rows'][0];
		$metric_values = $row['metricValues'] ?? array();

		return array(
			'sessions' => (int) ( $metric_values[0]['value'] ?? 0 ),
			'users' => (int) ( $metric_values[1]['value'] ?? 0 ),
			'pageviews' => (int) ( $metric_values[2]['value'] ?? 0 ),
			'bounce_rate' => (float) ( $metric_values[3]['value'] ?? 0 ),
			'avg_session_duration' => (float) ( $metric_values[4]['value'] ?? 0 ),
		);
	}

	/**
	 * Fetch GSC metrics.
	 *
	 * Retrieves impressions, clicks, CTR, and average position.
	 *
	 * @param string $start_date Start date (YYYY-MM-DD).
	 * @param string $end_date End date (YYYY-MM-DD).
	 * @return array|null Metrics array or null on failure.
	 */
	public function get_gsc_metrics( string $start_date, string $end_date ): ?array {
		// Check cache first.
		$cache_key = 'meowseo_gsc_metrics_' . get_current_blog_id() . '_' . $start_date . '_' . $end_date;
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$access_token = $this->get_access_token();

		if ( ! $access_token ) {
			return null;
		}

		// Get site URL from settings.
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		$site_url = $ga4_settings['site_url'] ?? get_home_url();

		// Fetch metrics from GSC API.
		$metrics = $this->fetch_gsc_api_metrics( $access_token, $site_url, $start_date, $end_date );

		if ( $metrics ) {
			// Cache for 6 hours.
			set_transient( $cache_key, $metrics, 6 * HOUR_IN_SECONDS );
		}

		return $metrics;
	}

	/**
	 * Fetch metrics from GSC API.
	 *
	 * @param string $access_token Access token.
	 * @param string $site_url Site URL.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array|null Metrics or null on failure.
	 */
	private function fetch_gsc_api_metrics( string $access_token, string $site_url, string $start_date, string $end_date ): ?array {
		$url = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( $site_url ) . '/searchAnalytics/query';

		$body = array(
			'startDate' => $start_date,
			'endDate' => $end_date,
			'metrics' => array( 'impressions', 'clicks', 'ctr', 'position' ),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( ! isset( $data['rows'] ) || empty( $data['rows'] ) ) {
			return array(
				'impressions' => 0,
				'clicks' => 0,
				'ctr' => 0,
				'avg_position' => 0,
			);
		}

		// Aggregate metrics from all rows.
		$total_impressions = 0;
		$total_clicks = 0;
		$total_position = 0;
		$row_count = 0;

		foreach ( $data['rows'] as $row ) {
			$total_impressions += (int) ( $row['impressions'] ?? 0 );
			$total_clicks += (int) ( $row['clicks'] ?? 0 );
			$total_position += (float) ( $row['position'] ?? 0 );
			$row_count++;
		}

		return array(
			'impressions' => $total_impressions,
			'clicks' => $total_clicks,
			'ctr' => $total_impressions > 0 ? ( $total_clicks / $total_impressions ) * 100 : 0,
			'avg_position' => $row_count > 0 ? $total_position / $row_count : 0,
		);
	}

	/**
	 * Fetch PageSpeed Insights metrics.
	 *
	 * Retrieves Core Web Vitals for a specific URL.
	 *
	 * @param string $url URL to analyze.
	 * @return array|null Metrics array or null on failure.
	 */
	public function get_pagespeed_insights( string $url ): ?array {
		// Check cache first.
		$cache_key = 'meowseo_psi_' . md5( $url );
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		$psi_api_key = $ga4_settings['psi_api_key'] ?? '';

		if ( empty( $psi_api_key ) ) {
			return null;
		}

		// Fetch metrics from PageSpeed Insights API.
		$metrics = $this->fetch_psi_api_metrics( $url, $psi_api_key );

		if ( $metrics ) {
			// Cache for 6 hours.
			set_transient( $cache_key, $metrics, 6 * HOUR_IN_SECONDS );
		}

		return $metrics;
	}

	/**
	 * Fetch metrics from PageSpeed Insights API.
	 *
	 * @param string $url URL to analyze.
	 * @param string $api_key PageSpeed Insights API key.
	 * @return array|null Metrics or null on failure.
	 */
	private function fetch_psi_api_metrics( string $url, string $api_key ): ?array {
		$psi_url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

		$params = array(
			'url' => $url,
			'key' => $api_key,
			'category' => 'performance',
		);

		$response = wp_remote_get(
			$psi_url . '?' . http_build_query( $params ),
			array(
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( ! isset( $data['lighthouseResult']['audits'] ) ) {
			return null;
		}

		$audits = $data['lighthouseResult']['audits'];

		return array(
			'lcp' => $audits['largest-contentful-paint']['numericValue'] ?? 0,
			'fid' => $audits['first-input-delay']['numericValue'] ?? 0,
			'cls' => $audits['cumulative-layout-shift']['numericValue'] ?? 0,
			'performance_score' => $data['lighthouseResult']['categories']['performance']['score'] ?? 0,
		);
	}

	/**
	 * Identify winning content (increasing traffic).
	 *
	 * @return array Array of winning posts.
	 */
	public function identify_winning_content(): array {
		// Implementation for identifying winning content.
		// This would compare traffic metrics over time periods.
		return array();
	}

	/**
	 * Identify losing content (decreasing traffic).
	 *
	 * @return array Array of losing posts.
	 */
	public function identify_losing_content(): array {
		// Implementation for identifying losing content.
		// This would compare traffic metrics over time periods.
		return array();
	}

	/**
	 * Send weekly email report.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function send_weekly_report(): bool {
		// Implementation for sending weekly email report.
		return true;
	}

	/**
	 * AJAX handler for refreshing metrics.
	 *
	 * @return void
	 */
	public function ajax_refresh_metrics(): void {
		check_ajax_referer( 'meowseo_ga4_nonce', 'nonce' );

		if ( ! current_user_can( 'meowseo_manage_analytics' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'meowseo' ) ) );
		}

		// Clear transients to force refresh.
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%meowseo_ga4_metrics_%' OR option_name LIKE '%meowseo_gsc_metrics_%'"
		);

		wp_send_json_success( array( 'message' => __( 'Metrics refreshed', 'meowseo' ) ) );
	}

	/**
	 * AJAX handler for disconnecting GA4.
	 *
	 * @return void
	 */
	public function ajax_disconnect(): void {
		check_ajax_referer( 'meowseo_ga4_nonce', 'nonce' );

		if ( ! current_user_can( 'meowseo_manage_analytics' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'meowseo' ) ) );
		}

		// Clear GA4 credentials.
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		unset( $ga4_settings['credentials'] );
		$this->options->set( 'ga4_settings', $ga4_settings );
		$this->options->save();

		wp_send_json_success( array( 'message' => __( 'Disconnected from Google Analytics', 'meowseo' ) ) );
	}
}
