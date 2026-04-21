<?php
/**
 * MeowSEO License Manager
 *
 * Handles license validation against the Nexia Enterprise server.
 * Checks every 24 hours via WP Cron, with 7-day grace period.
 *
 * @package MeowSEO
 * @since   1.1.0
 */

namespace MeowSEO\License;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LicenseManager {

	/**
	 * Nexia Enterprise API endpoint for license validation.
	 */
	const API_URL = 'https://erp.pustekno.id/api/v1/license/validate';

	/**
	 * WordPress option keys.
	 */
	const OPTION_KEY     = 'meowseo_license_key';
	const STATUS_KEY     = 'meowseo_license_status';
	const CACHE_KEY      = 'meowseo_license_cache';
	const ERROR_KEY      = 'meowseo_license_error';
	const FAIL_DATE_KEY  = 'meowseo_license_fail_date';
	const PRODUCT_SLUG   = 'meowseo';
	const GRACE_DAYS     = 7;

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Boot the license system.
	 */
	public function boot(): void {
		// Schedule daily license check.
		if ( ! wp_next_scheduled( 'meowseo_license_check' ) ) {
			wp_schedule_event( time() + 60, 'daily', 'meowseo_license_check' );
		}
		add_action( 'meowseo_license_check', array( $this, 'validate_remote' ) );

		// Admin menu.
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );

		// AJAX handlers.
		add_action( 'wp_ajax_meowseo_verify_license', array( $this, 'ajax_verify' ) );
		add_action( 'wp_ajax_meowseo_deactivate_license', array( $this, 'ajax_deactivate' ) );

		// Admin notice.
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Validate license against Nexia server.
	 *
	 * @return bool True if license is valid.
	 */
	public function validate_remote(): bool {
		$key = get_option( self::OPTION_KEY );
		if ( empty( $key ) ) {
			update_option( self::STATUS_KEY, 'no_key' );
			return false;
		}

		$response = wp_remote_post( self::API_URL, array(
			'timeout'   => 15,
			'sslverify' => true,
			'headers'   => array( 'Content-Type' => 'application/json' ),
			'body'      => wp_json_encode( array(
				'license_key'  => sanitize_text_field( $key ),
				'domain'       => self::get_domain(),
				'product_slug' => self::PRODUCT_SLUG,
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $this->handle_connection_error();
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 === $code && ! empty( $body['valid'] ) ) {
			update_option( self::STATUS_KEY, 'valid' );
			set_transient( self::CACHE_KEY, $body, self::GRACE_DAYS * DAY_IN_SECONDS );
			delete_option( self::FAIL_DATE_KEY );
			delete_option( self::ERROR_KEY );
			return true;
		}

		update_option( self::STATUS_KEY, 'invalid' );
		update_option( self::ERROR_KEY, $body['error'] ?? 'unknown' );

		if ( ! get_option( self::FAIL_DATE_KEY ) ) {
			update_option( self::FAIL_DATE_KEY, time() );
		}

		return false;
	}

	/**
	 * Handle connection errors.
	 *
	 * @return bool
	 */
	private function handle_connection_error(): bool {
		$cached = get_transient( self::CACHE_KEY );
		return ( false !== $cached && ! empty( $cached['valid'] ) );
	}

	/**
	 * Check if Pro features should be active.
	 *
	 * Usage: if ( LicenseManager::is_pro() ) { ... }
	 *
	 * @return bool
	 */
	public static function is_pro(): bool {
		$status = get_option( self::STATUS_KEY, 'no_key' );
		if ( 'valid' === $status ) {
			return true;
		}

		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached && ! empty( $cached['valid'] ) ) {
			return true;
		}

		$fail_date = get_option( self::FAIL_DATE_KEY );
		if ( $fail_date && ( time() - (int) $fail_date ) < ( self::GRACE_DAYS * DAY_IN_SECONDS ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the site domain without www prefix.
	 *
	 * @return string
	 */
	public static function get_domain(): string {
		$host = wp_parse_url( site_url(), PHP_URL_HOST );
		return preg_replace( '/^www\./', '', $host ?? '' );
	}

	/**
	 * Register admin menu page.
	 */
	public function register_menu(): void {
		add_submenu_page(
			'meowseo',
			esc_html__( 'License', 'meowseo' ),
			'🔑 ' . esc_html__( 'License', 'meowseo' ),
			'manage_options',
			'meowseo-license',
			array( $this, 'render_page' )
		);
	}

	/**
	 * AJAX: Verify license key.
	 */
	public function ajax_verify(): void {
		check_ajax_referer( 'meowseo_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$key = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) );
		if ( empty( $key ) ) {
			wp_send_json_error( array( 'message' => 'License key tidak boleh kosong.' ) );
		}

		update_option( self::OPTION_KEY, $key );
		$result = $this->validate_remote();

		wp_send_json_success( array(
			'valid'   => $result,
			'status'  => get_option( self::STATUS_KEY ),
			'message' => $result
				? 'Lisensi MeowSEO Pro berhasil diaktifkan! ✅'
				: 'Lisensi tidak valid: ' . get_option( self::ERROR_KEY, 'unknown' ),
		) );
	}

	/**
	 * AJAX: Deactivate license.
	 */
	public function ajax_deactivate(): void {
		check_ajax_referer( 'meowseo_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		delete_option( self::OPTION_KEY );
		delete_option( self::STATUS_KEY );
		delete_option( self::ERROR_KEY );
		delete_option( self::FAIL_DATE_KEY );
		delete_transient( self::CACHE_KEY );

		wp_send_json_success( array( 'message' => 'Lisensi berhasil dihapus.' ) );
	}

	/**
	 * Admin notice for users without active license.
	 */
	public function admin_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'meowseo_page_meowseo-license' === $screen->id ) {
			return;
		}

		$status = get_option( self::STATUS_KEY, 'no_key' );
		if ( 'valid' === $status ) {
			return;
		}

		printf(
			'<div class="notice notice-info is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'MeowSEO: Aktifkan lisensi Pro untuk fitur AI Optimizer, Google Discover, dan lainnya.', 'meowseo' ),
			esc_url( admin_url( 'admin.php?page=meowseo-license' ) ),
			esc_html__( 'Aktifkan sekarang →', 'meowseo' )
		);
	}

	/**
	 * Render the license settings page.
	 */
	public function render_page(): void {
		$key    = get_option( self::OPTION_KEY, '' );
		$status = get_option( self::STATUS_KEY, 'no_key' );
		$error  = get_option( self::ERROR_KEY, '' );
		$cached = get_transient( self::CACHE_KEY );

		?>
		<div class="wrap">
			<h1>🔑 <?php esc_html_e( 'Aktivasi Lisensi MeowSEO Pro', 'meowseo' ); ?></h1>

			<div class="card" style="max-width: 650px; padding: 24px; margin-top: 20px;">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'License Key', 'meowseo' ); ?></th>
						<td>
							<input type="text"
								id="meowseo-license-key"
								value="<?php echo esc_attr( $key ); ?>"
								placeholder="NX-XXXX-XXXX-XXXX"
								class="regular-text"
								style="font-family: monospace; font-size: 14px;"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'meowseo' ); ?></th>
						<td>
							<?php if ( 'valid' === $status ) : ?>
								<span style="color: #059669; font-weight: bold;">✅ <?php esc_html_e( 'MeowSEO Pro Aktif', 'meowseo' ); ?></span>
								<?php if ( ! empty( $cached['expires_at'] ) ) : ?>
									<br><small style="color: #64748b;">
										<?php
										printf(
											esc_html__( 'Berlaku hingga: %s', 'meowseo' ),
											esc_html( wp_date( 'j F Y', strtotime( $cached['expires_at'] ) ) )
										);
										?>
									</small>
								<?php endif; ?>
							<?php elseif ( 'invalid' === $status ) : ?>
								<span style="color: #dc2626;">❌ <?php esc_html_e( 'Tidak Valid', 'meowseo' ); ?>
									(<?php echo esc_html( $error ); ?>)
								</span>
							<?php else : ?>
								<span style="color: #94a3b8;">⏳ <?php esc_html_e( 'Belum diaktifkan', 'meowseo' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Domain', 'meowseo' ); ?></th>
						<td>
							<code><?php echo esc_html( self::get_domain() ); ?></code>
						</td>
					</tr>
				</table>

				<p class="submit" style="display: flex; gap: 8px; align-items: center;">
					<button type="button" id="meowseo-verify-btn" class="button button-primary">
						✅ <?php esc_html_e( 'Verifikasi & Aktifkan', 'meowseo' ); ?>
					</button>
					<?php if ( 'valid' === $status ) : ?>
						<button type="button" id="meowseo-deactivate-btn" class="button">
							❌ <?php esc_html_e( 'Hapus Lisensi', 'meowseo' ); ?>
						</button>
					<?php endif; ?>
					<span id="meowseo-license-spinner" class="spinner" style="float: none;"></span>
				</p>

				<p class="description">
					<?php
					printf(
						esc_html__( 'Belum punya lisensi? %sBeli di Nexia Enterprise →%s', 'meowseo' ),
						'<a href="https://erp.pustekno.id" target="_blank" rel="noopener">',
						'</a>'
					);
					?>
				</p>
			</div>

			<?php if ( 'valid' === $status ) : ?>
			<div class="card" style="max-width: 650px; padding: 20px; margin-top: 16px; background: #f0fdf4; border-color: #bbf7d0;">
				<h3 style="margin-top: 0;">🎉 <?php esc_html_e( 'Fitur Pro Aktif:', 'meowseo' ); ?></h3>
				<ul style="columns: 2; margin: 0;">
					<li>✅ AI Content Optimizer</li>
					<li>✅ Google Discover Optimization</li>
					<li>✅ AI Overview Targeting</li>
					<li>✅ Advanced Schema Markup</li>
					<li>✅ Headless WordPress SEO</li>
					<li>✅ WP-CLI Commands</li>
					<li>✅ Bulk SEO Editing</li>
					<li>✅ Priority Support</li>
				</ul>
			</div>
			<?php endif; ?>
		</div>

		<script>
		jQuery(document).ready(function($) {
			var nonce = '<?php echo esc_js( wp_create_nonce( 'meowseo_license_nonce' ) ); ?>';

			$('#meowseo-verify-btn').on('click', function() {
				var btn     = $(this);
				var spinner = $('#meowseo-license-spinner');
				var key     = $('#meowseo-license-key').val().trim();

				if (!key) { alert('Masukkan license key terlebih dahulu.'); return; }

				btn.prop('disabled', true);
				spinner.addClass('is-active');

				$.post(ajaxurl, {
					action:      'meowseo_verify_license',
					license_key: key,
					nonce:       nonce
				}, function(response) {
					btn.prop('disabled', false);
					spinner.removeClass('is-active');
					alert(response.data.message);
					if (response.data.valid) location.reload();
				}).fail(function() {
					btn.prop('disabled', false);
					spinner.removeClass('is-active');
					alert('Gagal menghubungi server.');
				});
			});

			$('#meowseo-deactivate-btn').on('click', function() {
				if (!confirm('Yakin ingin menghapus lisensi?')) return;
				var btn     = $(this);
				var spinner = $('#meowseo-license-spinner');
				btn.prop('disabled', true);
				spinner.addClass('is-active');
				$.post(ajaxurl, { action: 'meowseo_deactivate_license', nonce: nonce }, function(response) {
					btn.prop('disabled', false);
					spinner.removeClass('is-active');
					alert(response.data.message);
					location.reload();
				});
			});
		});
		</script>
		<?php
	}
}
