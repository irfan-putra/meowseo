<?php
/**
 * Settings_Manager class for MeowSEO plugin.
 *
 * Handles rendering of tabbed settings interface with validation, sanitization,
 * and logging of settings changes.
 *
 * @package MeowSEO
 * @subpackage MeowSEO\Admin
 * @since 1.0.0
 */

namespace MeowSEO\Admin;

use MeowSEO\Options;
use MeowSEO\Module_Manager;
use MeowSEO\Helpers\Logger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings_Manager class
 *
 * Renders tabbed settings interface with validation and sanitization.
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
 *
 * @since 1.0.0
 */
class Settings_Manager {

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Module_Manager instance
	 *
	 * @since 1.0.0
	 * @var Module_Manager
	 */
	private Module_Manager $module_manager;

	/**
	 * Settings tabs definition
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private array $tabs;

	/**
	 * Validation errors
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private array $errors = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options        $options        Options instance.
	 * @param Module_Manager $module_manager Module_Manager instance.
	 */
	public function __construct( Options $options, Module_Manager $module_manager ) {
		$this->options        = $options;
		$this->module_manager = $module_manager;

		$this->tabs = array(
			'general'         => array(
				'title'  => __( 'General', 'meowseo' ),
				'icon'   => 'dashicons-admin-settings',
				'method' => 'render_general_tab',
			),
			'social-profiles' => array(
				'title'  => __( 'Social Profiles', 'meowseo' ),
				'icon'   => 'dashicons-share',
				'method' => 'render_social_profiles_tab',
			),
			'modules'         => array(
				'title'  => __( 'Modules', 'meowseo' ),
				'icon'   => 'dashicons-admin-plugins',
				'method' => 'render_modules_tab',
			),
			'advanced'        => array(
				'title'  => __( 'Advanced', 'meowseo' ),
				'icon'   => 'dashicons-admin-tools',
				'method' => 'render_advanced_tab',
			),
			'breadcrumbs'     => array(
				'title'  => __( 'Breadcrumbs', 'meowseo' ),
				'icon'   => 'dashicons-admin-links',
				'method' => 'render_breadcrumbs_tab',
			),
		);
	}

	/**
	 * Render settings tabs navigation
	 *
	 * Requirements: 4.1, 4.3, 4.4
	 *
	 * @since 1.0.0
	 * @param string $active_tab Currently active tab slug.
	 * @return void
	 */
	public function render_settings_tabs( string $active_tab = 'general' ): void {
		?>
		<div class="meowseo-settings-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings tabs', 'meowseo' ); ?>">
			<?php foreach ( $this->tabs as $tab_slug => $tab_config ) : ?>
				<button
					type="button"
					role="tab"
					class="meowseo-tab-button <?php echo $active_tab === $tab_slug ? 'active' : ''; ?>"
					id="meowseo-tab-<?php echo esc_attr( $tab_slug ); ?>"
					aria-selected="<?php echo $active_tab === $tab_slug ? 'true' : 'false'; ?>"
					aria-controls="meowseo-tabpanel-<?php echo esc_attr( $tab_slug ); ?>"
					data-tab="<?php echo esc_attr( $tab_slug ); ?>"
				>
					<span class="<?php echo esc_attr( $tab_config['icon'] ); ?> meowseo-tab-icon"></span>
					<span class="meowseo-tab-title"><?php echo esc_html( $tab_config['title'] ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
		<?php
		$this->render_tab_switching_script();
	}

	/**
	 * Render tab switching JavaScript
	 *
	 * Requirement: 4.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_switching_script(): void {
		?>
		<script>
		(function() {
			document.addEventListener('DOMContentLoaded', function() {
				var tabs = document.querySelectorAll('.meowseo-tab-button');
				var panels = document.querySelectorAll('.meowseo-tab-panel');

				tabs.forEach(function(tab) {
					tab.addEventListener('click', function() {
						var targetTab = this.getAttribute('data-tab');
						tabs.forEach(function(t) {
							t.classList.remove('active');
							t.setAttribute('aria-selected', 'false');
						});
						this.classList.add('active');
						this.setAttribute('aria-selected', 'true');
						panels.forEach(function(panel) {
							if (panel.getAttribute('data-tab') === targetTab) {
								panel.classList.add('active');
								panel.removeAttribute('hidden');
							} else {
								panel.classList.remove('active');
								panel.setAttribute('hidden', '');
							}
						});
						if (history.pushState) {
							history.pushState(null, null, '#tab-' + targetTab);
						}
					});
				});

				var hash = window.location.hash.replace('#tab-', '');
				if (hash) {
					var targetTab = document.querySelector('[data-tab="' + hash + '"]');
					if (targetTab) {
						targetTab.click();
					}
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Render settings form wrapper
	 *
	 * Requirements: 4.2, 28.1, 28.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_form(): void {
		$active_tab = 'general';
		if ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			if ( isset( $this->tabs[ $tab ] ) ) {
				$active_tab = $tab;
			}
		}

		// Generate unique nonce for this settings page (Requirement 28.5).
		$nonce_action = 'meowseo_settings_' . $active_tab . '_save';

		$this->render_admin_notices();
		?>
		<div class="meowseo-settings-container">
			<?php $this->render_settings_tabs( $active_tab ); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="meowseo-settings-form" id="meowseo-settings-form">
				<?php wp_nonce_field( $nonce_action, 'meowseo_settings_nonce' ); ?>
				<input type="hidden" name="action" value="meowseo_save_settings">
				<input type="hidden" name="meowseo_active_tab" value="<?php echo esc_attr( $active_tab ); ?>">
				<input type="hidden" name="meowseo_nonce_action" value="<?php echo esc_attr( $nonce_action ); ?>">

				<div class="meowseo-tab-panels">
					<?php foreach ( $this->tabs as $tab_slug => $tab_config ) : ?>
						<div
							class="meowseo-tab-panel <?php echo $active_tab === $tab_slug ? 'active' : ''; ?>"
							id="meowseo-tabpanel-<?php echo esc_attr( $tab_slug ); ?>"
							role="tabpanel"
							aria-labelledby="meowseo-tab-<?php echo esc_attr( $tab_slug ); ?>"
							data-tab="<?php echo esc_attr( $tab_slug ); ?>"
							<?php echo $active_tab !== $tab_slug ? 'hidden' : ''; ?>
						>
							<?php
							if ( method_exists( $this, $tab_config['method'] ) ) {
								call_user_func( array( $this, $tab_config['method'] ) );
							}
							?>
						</div>
					<?php endforeach; ?>
				</div>

				<?php submit_button( __( 'Save Settings', 'meowseo' ), 'primary', 'meowseo_submit', true ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render admin notices
	 *
	 * Requirements: 4.6, 4.7
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_admin_notices(): void {
		if ( isset( $_GET['meowseo_settings_saved'] ) && '1' === $_GET['meowseo_settings_saved'] ) {
			echo '<div class="notice notice-success is-dismissible" role="alert"><p>' . esc_html__( 'Settings saved successfully.', 'meowseo' ) . '</p></div>';
		}

		if ( isset( $_GET['meowseo_settings_error'] ) ) {
			$error_message = sanitize_text_field( wp_unslash( $_GET['meowseo_settings_error'] ) );
			echo '<div class="notice notice-error is-dismissible" role="alert"><p>' . esc_html( $error_message ) . '</p></div>';
		}

		$errors = get_transient( 'meowseo_settings_errors' );
		if ( $errors && is_array( $errors ) ) {
			delete_transient( 'meowseo_settings_errors' );
			foreach ( $errors as $field => $message ) {
				echo '<div class="notice notice-error is-dismissible" role="alert"><p><strong>' . esc_html( $field ) . ':</strong> ' . esc_html( $message ) . '</p></div>';
			}
		}
	}

	/**
	 * Render General settings tab
	 *
	 * Requirements: 5.1, 5.2, 5.3
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_general_tab(): void {
		$homepage_title         = $this->options->get( 'homepage_title', '' );
		$homepage_description   = $this->options->get( 'homepage_description', '' );
		$separator              = $this->options->get( 'separator', '|' );
		$title_pattern_post     = $this->options->get( 'title_pattern_post', '%title% %sep% %sitename%' );
		$title_pattern_page     = $this->options->get( 'title_pattern_page', '%title% %sep% %sitename%' );
		$title_pattern_category = $this->options->get( 'title_pattern_category', '%title% %sep% %sitename%' );
		$title_pattern_tag      = $this->options->get( 'title_pattern_tag', '%title% %sep% %sitename%' );
		$title_pattern_archive  = $this->options->get( 'title_pattern_archive', '%title% %sep% %sitename%' );
		$title_pattern_search   = $this->options->get( 'title_pattern_search', 'Search Results for %search_query% %sep% %sitename%' );

		$separators = array(
			'|' => __( 'Pipe (|)', 'meowseo' ),
			'-' => __( 'Hyphen (-)', 'meowseo' ),
			'–' => __( 'En Dash (–)', 'meowseo' ),
			'—' => __( 'Em Dash (—)', 'meowseo' ),
			'·' => __( 'Middle Dot (·)', 'meowseo' ),
			'•' => __( 'Bullet (•)', 'meowseo' ),
		);

		$pattern_variables = array( '%title%', '%sitename%', '%sep%', '%page%', '%category%', '%date%', '%search_query%' );
		?>
		<h2><?php esc_html_e( 'General Settings', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure homepage SEO and title patterns for your site.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Homepage Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><label for="homepage_title"><?php esc_html_e( 'Homepage Title', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="homepage_title" id="homepage_title" value="<?php echo esc_attr( $homepage_title ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Custom title for your homepage. Leave empty to use the site title.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="homepage_description"><?php esc_html_e( 'Homepage Description', 'meowseo' ); ?></label></th>
				<td>
					<textarea name="homepage_description" id="homepage_description" rows="3" class="large-text"><?php echo esc_textarea( $homepage_description ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Meta description for your homepage. Recommended length: 150-160 characters.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="separator"><?php esc_html_e( 'Title Separator', 'meowseo' ); ?></label></th>
				<td>
					<select name="separator" id="separator">
						<?php foreach ( $separators as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $separator, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Character used to separate title parts.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Title Patterns', 'meowseo' ); ?></h3></th></tr>
			<tr><th scope="row" colspan="2"><p class="description"><?php esc_html_e( 'Available pattern variables:', 'meowseo' ); ?> <code><?php echo implode( '</code>, <code>', $pattern_variables ); ?></code></p></th></tr>
			<tr>
				<th scope="row"><label for="title_pattern_post"><?php esc_html_e( 'Posts', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_post" id="title_pattern_post" value="<?php echo esc_attr( $title_pattern_post ); ?>" class="regular-text" data-preview="post"><p class="description meowseo-title-preview" data-for="title_pattern_post"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_page"><?php esc_html_e( 'Pages', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_page" id="title_pattern_page" value="<?php echo esc_attr( $title_pattern_page ); ?>" class="regular-text" data-preview="page"><p class="description meowseo-title-preview" data-for="title_pattern_page"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_category"><?php esc_html_e( 'Categories', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_category" id="title_pattern_category" value="<?php echo esc_attr( $title_pattern_category ); ?>" class="regular-text" data-preview="category"><p class="description meowseo-title-preview" data-for="title_pattern_category"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_tag"><?php esc_html_e( 'Tags', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_tag" id="title_pattern_tag" value="<?php echo esc_attr( $title_pattern_tag ); ?>" class="regular-text" data-preview="tag"><p class="description meowseo-title-preview" data-for="title_pattern_tag"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_archive"><?php esc_html_e( 'Archives', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_archive" id="title_pattern_archive" value="<?php echo esc_attr( $title_pattern_archive ); ?>" class="regular-text" data-preview="archive"><p class="description meowseo-title-preview" data-for="title_pattern_archive"></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="title_pattern_search"><?php esc_html_e( 'Search Results', 'meowseo' ); ?></label></th>
				<td><input type="text" name="title_pattern_search" id="title_pattern_search" value="<?php echo esc_attr( $title_pattern_search ); ?>" class="regular-text" data-preview="search"><p class="description meowseo-title-preview" data-for="title_pattern_search"></p></td>
			</tr>
		</table>
		<?php
		$this->render_title_preview_script();
	}

	/**
	 * Render title pattern preview JavaScript
	 *
	 * Requirements: 5.4, 5.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_title_preview_script(): void {
		$separator = $this->options->get( 'separator', '|' );
		$sitename  = get_bloginfo( 'name' );
		?>
		<script>
		(function() {
			var separator = <?php echo wp_json_encode( $separator ); ?>;
			var sitename = <?php echo wp_json_encode( $sitename ); ?>;
			var exampleTitles = { post: 'Example Post Title', page: 'Example Page Title', category: 'Example Category', tag: 'Example Tag', archive: 'Author Name', search: 'wordpress' };
			function updatePreview(input) {
				var pattern = input.value;
				var type = input.getAttribute('data-preview');
				var preview = document.querySelector('.meowseo-title-preview[data-for="' + input.id + '"]');
				if (!preview) return;
				var title = exampleTitles[type] || 'Example Title';
				var rendered = pattern.replace(/%title%/g, title).replace(/%sitename%/g, sitename).replace(/%sep%/g, separator).replace(/%page%/g, 'Page 2').replace(/%category%/g, 'Category Name').replace(/%date%/g, 'January 1, 2024').replace(/%search_query%/g, exampleTitles.search);
				preview.textContent = '<?php esc_html_e( 'Preview:', 'meowseo' ); ?> ' + rendered;
			}
			document.addEventListener('DOMContentLoaded', function() {
				var inputs = document.querySelectorAll('[data-preview]');
				inputs.forEach(function(input) {
					updatePreview(input);
					input.addEventListener('input', function() { updatePreview(this); });
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Render Social Profiles settings tab
	 *
	 * Requirements: 6.1, 6.2
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_social_profiles_tab(): void {
		$social_profiles    = $this->options->get( 'meowseo_schema_social_profiles', array() );
		$facebook_url       = $social_profiles['facebook'] ?? '';
		$twitter_username   = $this->options->get( 'twitter_username', '' );
		$instagram_url      = $social_profiles['instagram'] ?? '';
		$linkedin_url       = $social_profiles['linkedin'] ?? '';
		$youtube_url        = $social_profiles['youtube'] ?? '';
		?>
		<h2><?php esc_html_e( 'Social Profiles', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure social media profiles for schema markup. These will appear in search results.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="facebook_url"><?php esc_html_e( 'Facebook URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="facebook_url" id="facebook_url" value="<?php echo esc_url( $facebook_url ); ?>" class="regular-text" placeholder="https://facebook.com/yourpage">
					<p class="description"><?php esc_html_e( 'Full URL to your Facebook page.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="twitter_username"><?php esc_html_e( 'Twitter Username', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="twitter_username" id="twitter_username" value="<?php echo esc_attr( $twitter_username ); ?>" class="regular-text" placeholder="username">
					<p class="description"><?php esc_html_e( 'Twitter username without the @ symbol.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="instagram_url"><?php esc_html_e( 'Instagram URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="instagram_url" id="instagram_url" value="<?php echo esc_url( $instagram_url ); ?>" class="regular-text" placeholder="https://instagram.com/yourprofile">
					<p class="description"><?php esc_html_e( 'Full URL to your Instagram profile.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="linkedin_url"><?php esc_html_e( 'LinkedIn URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="linkedin_url" id="linkedin_url" value="<?php echo esc_url( $linkedin_url ); ?>" class="regular-text" placeholder="https://linkedin.com/company/yourcompany">
					<p class="description"><?php esc_html_e( 'Full URL to your LinkedIn company page or profile.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="youtube_url"><?php esc_html_e( 'YouTube URL', 'meowseo' ); ?></label></th>
				<td>
					<input type="url" name="youtube_url" id="youtube_url" value="<?php echo esc_url( $youtube_url ); ?>" class="regular-text" placeholder="https://youtube.com/@yourchannel">
					<p class="description"><?php esc_html_e( 'Full URL to your YouTube channel.', 'meowseo' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Modules settings tab
	 *
	 * Requirements: 7.1, 7.2, 7.3, 7.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_modules_tab(): void {
		$enabled_modules = $this->options->get_enabled_modules();

		$modules = array(
			'meta'          => array( 'name' => __( 'Meta Tags', 'meowseo' ), 'description' => __( 'Manage title tags, meta descriptions, and robots meta for all content types.', 'meowseo' ) ),
			'schema'        => array( 'name' => __( 'Schema Markup', 'meowseo' ), 'description' => __( 'Generate structured data (JSON-LD) for rich results in search engines.', 'meowseo' ) ),
			'sitemap'       => array( 'name' => __( 'XML Sitemaps', 'meowseo' ), 'description' => __( 'Generate XML sitemaps for better search engine crawling.', 'meowseo' ) ),
			'redirects'     => array( 'name' => __( 'Redirects', 'meowseo' ), 'description' => __( 'Manage URL redirects to prevent 404 errors and preserve SEO value.', 'meowseo' ) ),
			'monitor_404'   => array( 'name' => __( '404 Monitor', 'meowseo' ), 'description' => __( 'Track 404 errors and identify broken links on your site.', 'meowseo' ) ),
			'internal_links' => array( 'name' => __( 'Internal Links', 'meowseo' ), 'description' => __( 'Suggest internal links while editing content to improve SEO structure.', 'meowseo' ) ),
			'gsc'           => array( 'name' => __( 'Google Search Console', 'meowseo' ), 'description' => __( 'Connect to Google Search Console for search analytics and indexing.', 'meowseo' ) ),
			'social'        => array( 'name' => __( 'Social Meta', 'meowseo' ), 'description' => __( 'Add Open Graph and Twitter Card meta tags for social sharing.', 'meowseo' ) ),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$modules['woocommerce'] = array( 'name' => __( 'WooCommerce SEO', 'meowseo' ), 'description' => __( 'Enhanced SEO features for WooCommerce products and categories.', 'meowseo' ) );
		}
		?>
		<h2><?php esc_html_e( 'Modules', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Enable or disable plugin modules. Disabled modules will not load on next page load.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<?php foreach ( $modules as $module_id => $module_config ) : ?>
				<tr>
					<th scope="row"><label for="module_<?php echo esc_attr( $module_id ); ?>"><?php echo esc_html( $module_config['name'] ); ?></label></th>
					<td>
						<fieldset>
							<label for="module_<?php echo esc_attr( $module_id ); ?>">
								<input type="checkbox" name="enabled_modules[]" id="module_<?php echo esc_attr( $module_id ); ?>" value="<?php echo esc_attr( $module_id ); ?>" <?php checked( in_array( $module_id, $enabled_modules, true ) ); ?>>
							</label>
							<p class="description"><?php echo esc_html( $module_config['description'] ); ?></p>
						</fieldset>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Render Advanced settings tab
	 *
	 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_advanced_tab(): void {
		$noindex_post_types     = $this->options->get( 'noindex_post_types', array() );
		$noindex_taxonomies     = $this->options->get( 'noindex_taxonomies', array() );
		$noindex_archives       = $this->options->get( 'noindex_archives', array() );
		$force_trailing_slash   = $this->options->get( 'canonical_force_trailing_slash', false );
		$force_https            = $this->options->get( 'canonical_force_https', false );
		$rss_before_content     = $this->options->get( 'rss_before_content', '' );
		$rss_after_content      = $this->options->get( 'rss_after_content', '' );
		$delete_on_uninstall    = $this->options->get( 'delete_on_uninstall', false );

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$archive_types = array( 'author' => __( 'Author Archives', 'meowseo' ), 'date' => __( 'Date Archives', 'meowseo' ) );
		?>
		<h2><?php esc_html_e( 'Advanced Settings', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure advanced SEO options for your site.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Noindex Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Noindex Post Types', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $post_types as $post_type ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="noindex_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $noindex_post_types, true ) ); ?>>
								<?php echo esc_html( $post_type->label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Prevent search engines from indexing selected post types.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Noindex Taxonomies', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $taxonomies as $taxonomy ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="noindex_taxonomies[]" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php checked( in_array( $taxonomy->name, $noindex_taxonomies, true ) ); ?>>
								<?php echo esc_html( $taxonomy->label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Prevent search engines from indexing selected taxonomies.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Noindex Archives', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $archive_types as $archive_value => $archive_label ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="noindex_archives[]" value="<?php echo esc_attr( $archive_value ); ?>" <?php checked( in_array( $archive_value, $noindex_archives, true ) ); ?>>
								<?php echo esc_html( $archive_label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Prevent search engines from indexing selected archive types.', 'meowseo' ); ?></p>
				</td>
			</tr>

			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Canonical URL Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Force Trailing Slash', 'meowseo' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="canonical_force_trailing_slash" value="1" <?php checked( $force_trailing_slash ); ?>>
						<?php esc_html_e( 'Add trailing slash to all URLs', 'meowseo' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Ensures all canonical URLs end with a trailing slash.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Force HTTPS', 'meowseo' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="canonical_force_https" value="1" <?php checked( $force_https ); ?>>
						<?php esc_html_e( 'Force HTTPS for all canonical URLs', 'meowseo' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Ensures all canonical URLs use HTTPS protocol.', 'meowseo' ); ?></p>
				</td>
			</tr>

			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'RSS Feed Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><label for="rss_before_content"><?php esc_html_e( 'Content Before Posts', 'meowseo' ); ?></label></th>
				<td>
					<textarea name="rss_before_content" id="rss_before_content" rows="2" class="large-text"><?php echo esc_textarea( $rss_before_content ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Content to add before each post in RSS feeds. HTML allowed.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rss_after_content"><?php esc_html_e( 'Content After Posts', 'meowseo' ); ?></label></th>
				<td>
					<textarea name="rss_after_content" id="rss_after_content" rows="2" class="large-text"><?php echo esc_textarea( $rss_after_content ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Content to add after each post in RSS feeds. HTML allowed.', 'meowseo' ); ?></p>
				</td>
			</tr>

			<tr><th scope="row" colspan="2"><h3><?php esc_html_e( 'Data Settings', 'meowseo' ); ?></h3></th></tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Delete on Uninstall', 'meowseo' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( $delete_on_uninstall ); ?>>
						<?php esc_html_e( 'Delete all plugin data on uninstall', 'meowseo' ); ?>
					</label>
					<p class="description" style="color: #d63638;"><?php esc_html_e( 'Warning: This will permanently delete all settings, redirects, and 404 logs when the plugin is uninstalled.', 'meowseo' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Breadcrumbs settings tab
	 *
	 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_breadcrumbs_tab(): void {
		$breadcrumbs_enabled           = $this->options->get( 'breadcrumbs_enabled', false );
		$breadcrumbs_separator         = $this->options->get( 'breadcrumbs_separator', ' &gt; ' );
		$breadcrumbs_home_label        = $this->options->get( 'breadcrumbs_home_label', __( 'Home', 'meowseo' ) );
		$breadcrumbs_prefix            = $this->options->get( 'breadcrumbs_prefix', '' );
		$breadcrumbs_position          = $this->options->get( 'breadcrumbs_position', 'before_content' );
		$breadcrumbs_show_on_post_types = $this->options->get( 'breadcrumbs_show_on_post_types', array( 'post', 'page' ) );
		$breadcrumbs_show_on_taxonomies = $this->options->get( 'breadcrumbs_show_on_taxonomies', array( 'category', 'post_tag' ) );

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		$position_options = array(
			'before_content' => __( 'Before Content', 'meowseo' ),
			'after_content'  => __( 'After Content', 'meowseo' ),
			'manual'         => __( 'Manual (use shortcode or PHP)', 'meowseo' ),
		);
		?>
		<h2><?php esc_html_e( 'Breadcrumbs Settings', 'meowseo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Configure breadcrumb navigation for your site.', 'meowseo' ); ?></p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Breadcrumbs', 'meowseo' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="breadcrumbs_enabled" value="1" <?php checked( $breadcrumbs_enabled ); ?>>
						<?php esc_html_e( 'Enable breadcrumb navigation', 'meowseo' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="breadcrumbs_separator"><?php esc_html_e( 'Separator', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="breadcrumbs_separator" id="breadcrumbs_separator" value="<?php echo esc_attr( $breadcrumbs_separator ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( 'Character(s) used to separate breadcrumb items. Example: &gt; / »', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="breadcrumbs_home_label"><?php esc_html_e( 'Home Label', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="breadcrumbs_home_label" id="breadcrumbs_home_label" value="<?php echo esc_attr( $breadcrumbs_home_label ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Label for the home breadcrumb item.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="breadcrumbs_prefix"><?php esc_html_e( 'Prefix', 'meowseo' ); ?></label></th>
				<td>
					<input type="text" name="breadcrumbs_prefix" id="breadcrumbs_prefix" value="<?php echo esc_attr( $breadcrumbs_prefix ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Text to display before the breadcrumb trail. Example: "You are here:"', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="breadcrumbs_position"><?php esc_html_e( 'Position', 'meowseo' ); ?></label></th>
				<td>
					<select name="breadcrumbs_position" id="breadcrumbs_position">
						<?php foreach ( $position_options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $breadcrumbs_position, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Where to display breadcrumbs on the page.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show on Post Types', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $post_types as $post_type ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="breadcrumbs_show_on_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $breadcrumbs_show_on_post_types, true ) ); ?>>
								<?php echo esc_html( $post_type->label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Show breadcrumbs on selected post types.', 'meowseo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show on Taxonomies', 'meowseo' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $taxonomies as $taxonomy ) : ?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" name="breadcrumbs_show_on_taxonomies[]" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php checked( in_array( $taxonomy->name, $breadcrumbs_show_on_taxonomies, true ) ); ?>>
								<?php echo esc_html( $taxonomy->label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Show breadcrumbs on selected taxonomy archives.', 'meowseo' ); ?></p>
				</td>
			</tr>
		</table>

		<?php if ( $breadcrumbs_enabled ) : ?>
		<div class="meowseo-breadcrumb-example" style="margin-top: 20px; padding: 15px; background: #f7f7f7; border: 1px solid #ddd; border-radius: 4px;">
			<h4><?php esc_html_e( 'Example Output', 'meowseo' ); ?></h4>
			<p class="description">
				<?php
				$example = array();
				if ( $breadcrumbs_prefix ) {
					$example[] = esc_html( $breadcrumbs_prefix );
				}
				$example[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( $breadcrumbs_home_label ) . '</a>';
				$example[] = '<a href="#">' . esc_html__( 'Category', 'meowseo' ) . '</a>';
				$example[] = esc_html__( 'Example Post', 'meowseo' );
				echo implode( esc_html( $breadcrumbs_separator ), $example );
				?>
			</p>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Validate settings
	 *
	 * Validates all settings and returns validated array or WP_Error.
	 * Requirements: 4.5, 4.6, 30.1, 30.2, 30.3, 30.4
	 *
	 * @since 1.0.0
	 * @param array $settings Settings to validate.
	 * @return array|\WP_Error Validated settings or WP_Error on failure.
	 */
	public function validate_settings( array $settings ) {
		$this->errors = array();
		$validated    = array();

		// Validate homepage title.
		if ( isset( $settings['homepage_title'] ) ) {
			$validated['homepage_title'] = sanitize_text_field( $settings['homepage_title'] );
		}

		// Validate homepage description.
		if ( isset( $settings['homepage_description'] ) ) {
			$validated['homepage_description'] = sanitize_textarea_field( $settings['homepage_description'] );
		}

		// Validate separator.
		if ( isset( $settings['separator'] ) ) {
			$valid_separators = array( '|', '-', '–', '—', '·', '•' );
			if ( in_array( $settings['separator'], $valid_separators, true ) ) {
				$validated['separator'] = $settings['separator'];
			} else {
				$this->errors['separator'] = __( 'Invalid separator selected.', 'meowseo' );
			}
		}

		// Validate title patterns.
		$title_pattern_fields = array(
			'title_pattern_post',
			'title_pattern_page',
			'title_pattern_category',
			'title_pattern_tag',
			'title_pattern_archive',
			'title_pattern_search',
		);

		foreach ( $title_pattern_fields as $field ) {
			if ( isset( $settings[ $field ] ) ) {
				$validated[ $field ] = sanitize_text_field( $settings[ $field ] );
			}
		}

		// Validate social URLs.
		$social_url_fields = array( 'facebook_url', 'instagram_url', 'linkedin_url', 'youtube_url' );
		$social_profiles   = array();

		foreach ( $social_url_fields as $field ) {
			if ( isset( $settings[ $field ] ) && ! empty( $settings[ $field ] ) ) {
				$sanitized_url = $this->sanitize_social_url( $settings[ $field ] );
				if ( is_wp_error( $sanitized_url ) ) {
					$this->errors[ $field ] = $sanitized_url->get_error_message();
				} else {
					$network = str_replace( '_url', '', $field );
					if ( ! empty( $sanitized_url ) ) {
						$social_profiles[ $network ] = $sanitized_url;
					}
				}
			}
		}

		if ( ! empty( $social_profiles ) ) {
			$validated['meowseo_schema_social_profiles'] = $social_profiles;
		}

		// Validate Twitter username.
		if ( isset( $settings['twitter_username'] ) ) {
			$twitter_username = sanitize_text_field( $settings['twitter_username'] );
			// Remove @ if present.
			$twitter_username = ltrim( $twitter_username, '@' );
			$validated['twitter_username'] = $twitter_username;
		}

		// Validate enabled modules.
		if ( isset( $settings['enabled_modules'] ) && is_array( $settings['enabled_modules'] ) ) {
			$valid_modules = array( 'meta', 'schema', 'sitemap', 'redirects', 'monitor_404', 'internal_links', 'gsc', 'social', 'woocommerce' );
			$validated['enabled_modules'] = array_values( array_intersect( $settings['enabled_modules'], $valid_modules ) );
		}

		// Validate noindex settings.
		$noindex_fields = array( 'noindex_post_types', 'noindex_taxonomies', 'noindex_archives' );
		foreach ( $noindex_fields as $field ) {
			if ( isset( $settings[ $field ] ) && is_array( $settings[ $field ] ) ) {
				$validated[ $field ] = array_map( 'sanitize_key', $settings[ $field ] );
			} else {
				$validated[ $field ] = array();
			}
		}

		// Validate canonical settings.
		$validated['canonical_force_trailing_slash'] = ! empty( $settings['canonical_force_trailing_slash'] );
		$validated['canonical_force_https'] = ! empty( $settings['canonical_force_https'] );

		// Validate RSS settings.
		if ( isset( $settings['rss_before_content'] ) ) {
			$validated['rss_before_content'] = wp_kses_post( $settings['rss_before_content'] );
		}
		if ( isset( $settings['rss_after_content'] ) ) {
			$validated['rss_after_content'] = wp_kses_post( $settings['rss_after_content'] );
		}

		// Validate delete on uninstall.
		$validated['delete_on_uninstall'] = ! empty( $settings['delete_on_uninstall'] );

		// Validate breadcrumbs settings.
		$validated['breadcrumbs_enabled'] = ! empty( $settings['breadcrumbs_enabled'] );
		if ( isset( $settings['breadcrumbs_separator'] ) ) {
			$validated['breadcrumbs_separator'] = sanitize_text_field( $settings['breadcrumbs_separator'] );
		}
		if ( isset( $settings['breadcrumbs_home_label'] ) ) {
			$validated['breadcrumbs_home_label'] = sanitize_text_field( $settings['breadcrumbs_home_label'] );
		}
		if ( isset( $settings['breadcrumbs_prefix'] ) ) {
			$validated['breadcrumbs_prefix'] = sanitize_text_field( $settings['breadcrumbs_prefix'] );
		}
		if ( isset( $settings['breadcrumbs_position'] ) ) {
			$valid_positions = array( 'before_content', 'after_content', 'manual' );
			$validated['breadcrumbs_position'] = in_array( $settings['breadcrumbs_position'], $valid_positions, true ) ? $settings['breadcrumbs_position'] : 'before_content';
		}
		if ( isset( $settings['breadcrumbs_show_on_post_types'] ) && is_array( $settings['breadcrumbs_show_on_post_types'] ) ) {
			$validated['breadcrumbs_show_on_post_types'] = array_map( 'sanitize_key', $settings['breadcrumbs_show_on_post_types'] );
		} else {
			$validated['breadcrumbs_show_on_post_types'] = array();
		}
		if ( isset( $settings['breadcrumbs_show_on_taxonomies'] ) && is_array( $settings['breadcrumbs_show_on_taxonomies'] ) ) {
			$validated['breadcrumbs_show_on_taxonomies'] = array_map( 'sanitize_key', $settings['breadcrumbs_show_on_taxonomies'] );
		} else {
			$validated['breadcrumbs_show_on_taxonomies'] = array();
		}

		// Return errors if any.
		if ( ! empty( $this->errors ) ) {
			return new \WP_Error( 'validation_failed', __( 'Settings validation failed.', 'meowseo' ), $this->errors );
		}

		return $validated;
	}

	/**
	 * Sanitize social URL
	 *
	 * Validates and sanitizes a social media profile URL.
	 * Requirements: 6.3, 6.4, 6.5
	 *
	 * @since 1.0.0
	 * @param string $url URL to sanitize.
	 * @return string|\WP_Error Sanitized URL or WP_Error on failure.
	 */
	public function sanitize_social_url( string $url ) {
		// Allow empty URLs.
		if ( empty( $url ) ) {
			return '';
		}

		// Sanitize URL.
		$url = esc_url_raw( trim( $url ) );

		if ( empty( $url ) ) {
			return new \WP_Error( 'invalid_url', __( 'Please enter a valid URL starting with https://', 'meowseo' ) );
		}

		// Validate URL format.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new \WP_Error( 'invalid_url', __( 'The URL format is not valid.', 'meowseo' ) );
		}

		// Ensure HTTPS.
		if ( strpos( $url, 'https://' ) !== 0 ) {
			return new \WP_Error( 'insecure_url', __( 'Please use a secure URL (https://).', 'meowseo' ) );
		}

		return $url;
	}

	/**
	 * Get validation errors
	 *
	 * @since 1.0.0
	 * @return array<string, string> Validation errors.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Save settings
	 *
	 * Handles settings form submission with nonce verification and logging.
	 * Requirements: 28.1, 28.4, 33.1
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save_settings(): void {
		// Get the nonce action from the form (Requirement 28.5).
		$nonce_action = isset( $_POST['meowseo_nonce_action'] ) ? sanitize_key( $_POST['meowseo_nonce_action'] ) : 'meowseo_settings_save';

		// Verify nonce (Requirement 28.1).
		if ( ! isset( $_POST['meowseo_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['meowseo_settings_nonce'] ), $nonce_action ) ) {
			// Return HTTP 403 for failed nonce verification (Requirement 28.4).
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'meowseo' ),
				esc_html__( 'Security Error', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Verify user capabilities (Requirement 29.1).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'meowseo' ),
				esc_html__( 'Permission Denied', 'meowseo' ),
				array( 'response' => 403 )
			);
		}

		// Get current settings for comparison.
		$old_settings = $this->options->get_all();

		// Validate settings.
		$validated = $this->validate_settings( $_POST );

		if ( is_wp_error( $validated ) ) {
			$errors = $validated->get_error_data();
			if ( is_array( $errors ) ) {
				set_transient( 'meowseo_settings_errors', $errors, 30 );
			}
			wp_safe_redirect( add_query_arg( 'meowseo_settings_error', urlencode( $validated->get_error_message() ), wp_get_referer() ) );
			exit;
		}

		// Track changed fields for logging.
		$changed_fields = array();

		foreach ( $validated as $key => $value ) {
			$old_value = $old_settings[ $key ] ?? null;

			// Compare values (handle arrays specially).
			if ( is_array( $value ) && is_array( $old_value ) ) {
				if ( ! empty( array_diff( $value, $old_value ) ) || ! empty( array_diff( $old_value, $value ) ) ) {
					$changed_fields[] = $key;
				}
			} elseif ( $value !== $old_value ) {
				$changed_fields[] = $key;
			}

			// Save setting.
			$this->options->set( $key, $value );
		}

		// Save to database.
		$saved = $this->options->save();

		if ( $saved ) {
			// Log settings save (Requirement 33.1).
			Logger::info(
				'Settings saved',
				array(
					'user_id'       => get_current_user_id(),
					'changed_fields' => $changed_fields,
					'tab'           => sanitize_key( $_POST['meowseo_active_tab'] ?? 'general' ),
				)
			);

			wp_safe_redirect( add_query_arg( 'meowseo_settings_saved', '1', wp_get_referer() ) );
		} else {
			wp_safe_redirect( add_query_arg( 'meowseo_settings_error', urlencode( __( 'Failed to save settings. Please try again.', 'meowseo' ) ), wp_get_referer() ) );
		}

		exit;
	}

	/**
	 * Register admin-post action handler
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_handlers(): void {
		add_action( 'admin_post_meowseo_save_settings', array( $this, 'save_settings' ) );
	}
}
