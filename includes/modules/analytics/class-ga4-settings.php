<?php
/**
 * GA4 Settings class for managing GA4 configuration.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Analytics;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GA4 Settings class.
 */
class GA4_Settings {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings_fields' ) );
	}

	/**
	 * Add settings page to admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			'meowseo',
			__( 'GA4 Settings', 'meowseo' ),
			__( 'GA4 Settings', 'meowseo' ),
			'meowseo_manage_analytics',
			'meowseo-ga4-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings fields.
	 *
	 * @return void
	 */
	public function register_settings_fields(): void {
		register_setting( 'meowseo_ga4_settings', 'meowseo_ga4_client_id' );
		register_setting( 'meowseo_ga4_settings', 'meowseo_ga4_client_secret' );
		register_setting( 'meowseo_ga4_settings', 'meowseo_ga4_property_id' );
		register_setting( 'meowseo_ga4_settings', 'meowseo_ga4_psi_api_key' );

		add_settings_section(
			'meowseo_ga4_oauth',
			__( 'OAuth Configuration', 'meowseo' ),
			array( $this, 'render_oauth_section' ),
			'meowseo_ga4_settings'
		);

		add_settings_field(
			'meowseo_ga4_client_id',
			__( 'Client ID', 'meowseo' ),
			array( $this, 'render_client_id_field' ),
			'meowseo_ga4_settings',
			'meowseo_ga4_oauth'
		);

		add_settings_field(
			'meowseo_ga4_client_secret',
			__( 'Client Secret', 'meowseo' ),
			array( $this, 'render_client_secret_field' ),
			'meowseo_ga4_settings',
			'meowseo_ga4_oauth'
		);

		add_settings_field(
			'meowseo_ga4_property_id',
			__( 'GA4 Property ID', 'meowseo' ),
			array( $this, 'render_property_id_field' ),
			'meowseo_ga4_settings',
			'meowseo_ga4_oauth'
		);

		add_settings_field(
			'meowseo_ga4_psi_api_key',
			__( 'PageSpeed Insights API Key', 'meowseo' ),
			array( $this, 'render_psi_api_key_field' ),
			'meowseo_ga4_settings',
			'meowseo_ga4_oauth'
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'meowseo_manage_analytics' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'meowseo' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'GA4 Settings', 'meowseo' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'meowseo_ga4_settings' );
				do_settings_sections( 'meowseo_ga4_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render OAuth section.
	 *
	 * @return void
	 */
	public function render_oauth_section(): void {
		echo wp_kses_post(
			'<p>' . __( 'Configure your Google OAuth credentials to enable GA4 integration.', 'meowseo' ) . '</p>'
		);
	}

	/**
	 * Render Client ID field.
	 *
	 * @return void
	 */
	public function render_client_id_field(): void {
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		$client_id = $ga4_settings['client_id'] ?? '';
		?>
		<input type="text" name="meowseo_ga4_client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Render Client Secret field.
	 *
	 * @return void
	 */
	public function render_client_secret_field(): void {
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		$client_secret = $ga4_settings['client_secret'] ?? '';
		?>
		<input type="password" name="meowseo_ga4_client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Render Property ID field.
	 *
	 * @return void
	 */
	public function render_property_id_field(): void {
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		$property_id = $ga4_settings['property_id'] ?? '';
		?>
		<input type="text" name="meowseo_ga4_property_id" value="<?php echo esc_attr( $property_id ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Render PageSpeed Insights API Key field.
	 *
	 * @return void
	 */
	public function render_psi_api_key_field(): void {
		$ga4_settings = $this->options->get( 'ga4_settings', array() );
		$psi_api_key = $ga4_settings['psi_api_key'] ?? '';
		?>
		<input type="password" name="meowseo_ga4_psi_api_key" value="<?php echo esc_attr( $psi_api_key ); ?>" class="regular-text" />
		<?php
	}
}
