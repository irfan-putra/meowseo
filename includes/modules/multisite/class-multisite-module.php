<?php
/**
 * Multisite Module class for WordPress multisite support.
 *
 * Handles network activation, per-site settings isolation, and network admin interface.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Multisite;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multisite Module class.
 *
 * Manages WordPress multisite network activation and per-site configuration.
 */
class Multisite_Module implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Network settings option key.
	 *
	 * @var string
	 */
	private const NETWORK_SETTINGS_KEY = 'meowseo_network_settings';

	/**
	 * Site settings option key prefix.
	 *
	 * @var string
	 */
	private const SITE_SETTINGS_PREFIX = 'meowseo_site_settings_';

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Only initialize if multisite is enabled.
		if ( ! is_multisite() ) {
			return;
		}

		// Hook into new site creation.
		add_action( 'wpmu_new_blog', array( $this, 'initialize_new_site' ), 10, 1 );

		// Register network admin menu if network activated.
		if ( $this->is_network_activated() ) {
			add_action( 'network_admin_menu', array( $this, 'register_network_admin_menu' ) );
		}
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'multisite';
	}

	/**
	 * Check if plugin is network activated.
	 *
	 * Validates: Requirement 3.1
	 *
	 * @return bool True if network activated, false otherwise.
	 */
	public function is_network_activated(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		// Check if plugin is in network active plugins list.
		$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$plugin_file             = plugin_basename( MEOWSEO_PLUGIN_FILE );

		return isset( $network_active_plugins[ $plugin_file ] );
	}

	/**
	 * Get network-level settings.
	 *
	 * Validates: Requirement 3.4
	 *
	 * @return array Network settings array.
	 */
	public function get_network_settings(): array {
		$settings = get_site_option( self::NETWORK_SETTINGS_KEY, array() );

		// Set defaults if not already set.
		if ( empty( $settings ) ) {
			$settings = $this->get_default_network_settings();
		}

		return $settings;
	}

	/**
	 * Get default network settings.
	 *
	 * @return array Default network settings.
	 */
	private function get_default_network_settings(): array {
		return array(
			'default_settings'    => array(),
			'disabled_features'   => array(),
			'network_admin_email' => get_site_option( 'admin_email' ),
		);
	}

	/**
	 * Update network-level settings.
	 *
	 * Validates: Requirement 3.4, 3.6
	 *
	 * @param array $settings Settings to update.
	 * @return bool True on success, false on failure.
	 */
	public function update_network_settings( array $settings ): bool {
		// Validate settings structure.
		$validated_settings = array(
			'default_settings'    => isset( $settings['default_settings'] ) && is_array( $settings['default_settings'] ) ? $settings['default_settings'] : array(),
			'disabled_features'   => isset( $settings['disabled_features'] ) && is_array( $settings['disabled_features'] ) ? $settings['disabled_features'] : array(),
			'network_admin_email' => isset( $settings['network_admin_email'] ) ? sanitize_email( $settings['network_admin_email'] ) : get_site_option( 'admin_email' ),
		);

		return update_site_option( self::NETWORK_SETTINGS_KEY, $validated_settings );
	}

	/**
	 * Get settings for a specific site.
	 *
	 * Validates: Requirement 3.2
	 *
	 * @param int $site_id Site ID (defaults to current site).
	 * @return array Site-specific settings.
	 */
	public function get_site_settings( int $site_id = 0 ): array {
		if ( 0 === $site_id ) {
			$site_id = get_current_blog_id();
		}

		$option_key = self::SITE_SETTINGS_PREFIX . $site_id;
		$settings   = get_site_option( $option_key, array() );

		// If no site-specific settings, use network defaults.
		if ( empty( $settings ) ) {
			$network_settings = $this->get_network_settings();
			$settings         = $network_settings['default_settings'] ?? array();
		}

		return $settings;
	}

	/**
	 * Update settings for a specific site.
	 *
	 * Validates: Requirement 3.2
	 *
	 * @param array $settings Settings to update.
	 * @param int   $site_id  Site ID (defaults to current site).
	 * @return bool True on success, false on failure.
	 */
	public function update_site_settings( array $settings, int $site_id = 0 ): bool {
		if ( 0 === $site_id ) {
			$site_id = get_current_blog_id();
		}

		$option_key = self::SITE_SETTINGS_PREFIX . $site_id;

		return update_site_option( $option_key, $settings );
	}

	/**
	 * Initialize settings for a new site.
	 *
	 * Validates: Requirement 3.5
	 *
	 * @param int $blog_id New blog ID.
	 * @return void
	 */
	public function initialize_new_site( int $blog_id ): void {
		// Get network default settings.
		$network_settings = $this->get_network_settings();
		$default_settings = $network_settings['default_settings'] ?? array();

		// Initialize site with default settings.
		$option_key = self::SITE_SETTINGS_PREFIX . $blog_id;
		update_site_option( $option_key, $default_settings );
	}

	/**
	 * Get network-wide disabled features.
	 *
	 * Validates: Requirement 3.6
	 *
	 * @return array Array of disabled feature IDs.
	 */
	public function get_network_disabled_features(): array {
		$network_settings = $this->get_network_settings();
		return $network_settings['disabled_features'] ?? array();
	}

	/**
	 * Check if a feature is disabled network-wide.
	 *
	 * Validates: Requirement 3.6
	 *
	 * @param string $feature_id Feature ID to check.
	 * @return bool True if disabled, false otherwise.
	 */
	public function is_feature_disabled( string $feature_id ): bool {
		$disabled_features = $this->get_network_disabled_features();
		return in_array( $feature_id, $disabled_features, true );
	}

	/**
	 * Register network admin menu.
	 *
	 * Validates: Requirement 3.3
	 *
	 * @return void
	 */
	public function register_network_admin_menu(): void {
		add_menu_page(
			'MeowSEO Network Settings',
			'MeowSEO',
			'manage_network',
			'meowseo-network',
			array( $this, 'render_network_settings_page' ),
			'dashicons-search',
			25
		);

		add_submenu_page(
			'meowseo-network',
			'Network Settings',
			'Network Settings',
			'manage_network',
			'meowseo-network',
			array( $this, 'render_network_settings_page' )
		);
	}

	/**
	 * Render network settings page.
	 *
	 * Validates: Requirement 3.4, 3.6
	 *
	 * @return void
	 */
	public function render_network_settings_page(): void {
		// Check capability.
		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'meowseo' ) );
		}

		// Handle form submission.
		if ( isset( $_POST['meowseo_network_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meowseo_network_nonce'] ) ), 'meowseo_network_settings' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'meowseo' ) );
			}

			$settings = array(
				'default_settings'  => isset( $_POST['default_settings'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['default_settings'] ) ) : array(),
				'disabled_features' => isset( $_POST['disabled_features'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['disabled_features'] ) ) : array(),
			);

			if ( $this->update_network_settings( $settings ) ) {
				add_settings_error( 'meowseo_network', 'settings_updated', __( 'Network settings updated successfully.', 'meowseo' ), 'updated' );
			}
		}

		$network_settings = $this->get_network_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MeowSEO Network Settings', 'meowseo' ); ?></h1>
			<?php settings_errors( 'meowseo_network' ); ?>
			<form method="post">
				<?php wp_nonce_field( 'meowseo_network_settings', 'meowseo_network_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="disabled_features"><?php esc_html_e( 'Disabled Features', 'meowseo' ); ?></label>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Disabled Features', 'meowseo' ); ?></legend>
								<p><?php esc_html_e( 'Disable features network-wide. Disabled features will not be available on any site in the network.', 'meowseo' ); ?></p>
								<label>
									<input type="checkbox" name="disabled_features[]" value="ai_generation" <?php checked( in_array( 'ai_generation', $network_settings['disabled_features'] ?? array(), true ) ); ?> />
									<?php esc_html_e( 'AI Generation', 'meowseo' ); ?>
								</label><br />
								<label>
									<input type="checkbox" name="disabled_features[]" value="analytics" <?php checked( in_array( 'analytics', $network_settings['disabled_features'] ?? array(), true ) ); ?> />
									<?php esc_html_e( 'Analytics Integration', 'meowseo' ); ?>
								</label><br />
								<label>
									<input type="checkbox" name="disabled_features[]" value="ai_optimizer" <?php checked( in_array( 'ai_optimizer', $network_settings['disabled_features'] ?? array(), true ) ); ?> />
									<?php esc_html_e( 'AI Optimizer', 'meowseo' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
