<?php
/**
 * Gutenberg Assets Manager
 *
 * Handles enqueuing of JavaScript and CSS assets for the Gutenberg editor integration.
 * Registers postmeta keys for REST API access and localizes script data.
 *
 * @package MeowSEO
 * @subpackage Meta
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Meta;

/**
 * Class Gutenberg_Assets
 *
 * Manages asset enqueuing and postmeta registration for Gutenberg editor.
 */
class Gutenberg_Assets {

	/**
	 * Initialize the Gutenberg assets manager
	 *
	 * Hooks into WordPress to enqueue assets and register postmeta.
	 */
	public function init(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'init', array( $this, 'register_postmeta' ) );
	}

	/**
	 * Enqueue JavaScript and CSS assets for the Gutenberg editor
	 *
	 * Enqueues the compiled bundle and localizes script data including
	 * nonce, post ID, and REST URL.
	 */
	public function enqueue_editor_assets(): void {
		$build_path = MEOWSEO_PATH . 'build/';
		$build_url  = MEOWSEO_URL . 'build/';
		$asset_file_path = $build_path . 'gutenberg.asset.php';

		// Check if asset file exists, use fallback dependencies if missing
		if ( file_exists( $asset_file_path ) ) {
			$asset_file = include $asset_file_path;
		} else {
			$this->log_asset_error( 'build/gutenberg.asset.php', $asset_file_path );
			// Fallback dependencies for Gutenberg editor
			$asset_file = array(
				'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-i18n' ),
				'version'      => '1.0.0',
			);
		}

		// Check if JavaScript bundle (build/gutenberg.js) exists before enqueueing
		$js_file_path = $build_path . 'gutenberg.js';
		if ( file_exists( $js_file_path ) ) {
			// Enqueue JavaScript bundle
			wp_enqueue_script(
				'meowseo-gutenberg',
				$build_url . 'gutenberg.js',
				$asset_file['dependencies'],
				$asset_file['version'],
				true
			);

			// Localize script data
			wp_localize_script(
				'meowseo-gutenberg',
				'meowseoData',
				array(
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'postId'  => get_the_ID(),
					'restUrl' => rest_url( 'meowseo/v1' ),
				)
			);
		} else {
			$this->log_asset_error( 'build/gutenberg.js', $js_file_path );
		}

		// Check if CSS bundle exists before enqueueing
		$css_file_path = $build_path . 'gutenberg.css';
		if ( file_exists( $css_file_path ) ) {
			// Enqueue CSS bundle
			wp_enqueue_style(
				'meowseo-gutenberg',
				$build_url . 'gutenberg.css',
				array( 'wp-components' ),
				$asset_file['version']
			);
		} else {
			$this->log_asset_error( 'build/gutenberg.css', $css_file_path );
		}
	}

	/**
	 * Register postmeta keys for REST API access
	 *
	 * Registers all MeowSEO postmeta keys with show_in_rest enabled
	 * to allow reading and writing via the WordPress REST API.
	 */
	public function register_postmeta(): void {
		$post_types = array( 'post', 'page' );
		$meta_keys  = $this->get_meta_keys();

		foreach ( $post_types as $post_type ) {
			foreach ( $meta_keys as $meta_key => $args ) {
				register_post_meta(
					$post_type,
					$meta_key,
					array_merge(
						array(
							'show_in_rest'  => true,
							'single'        => true,
							'auth_callback' => function () {
								return current_user_can( 'edit_posts' );
							},
						),
						$args
					)
				);
			}
		}
	}

	/**
	 * Get all postmeta keys used by MeowSEO
	 *
	 * Requirements:
	 * - 18.6: Sanitize all user input before storage
	 * - 18.7: Sanitize HTML content with wp_kses_post
	 * - 18.8: Validate schema configuration JSON
	 *
	 * @return array Associative array of meta keys and their configuration
	 */
	private function get_meta_keys(): array {
		return array(
			// General tab
			'_meowseo_title'          => array(
				'type'              => 'string',
				'description'       => 'SEO title override',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_meowseo_description'    => array(
				'type'              => 'string',
				'description'       => 'Meta description',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'_meowseo_focus_keyword'  => array(
				'type'              => 'string',
				'description'       => 'Focus keyword for SEO analysis',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_meowseo_direct_answer'  => array(
				'type'              => 'string',
				'description'       => 'Direct answer for featured snippets',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			),

			// Social tab - Open Graph
			'_meowseo_og_title'       => array(
				'type'              => 'string',
				'description'       => 'Open Graph title',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_meowseo_og_description' => array(
				'type'              => 'string',
				'description'       => 'Open Graph description',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'_meowseo_og_image_id'    => array(
				'type'              => 'integer',
				'description'       => 'Open Graph image attachment ID',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),

			// Social tab - Twitter
			'_meowseo_twitter_title'       => array(
				'type'              => 'string',
				'description'       => 'Twitter card title',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_meowseo_twitter_description' => array(
				'type'              => 'string',
				'description'       => 'Twitter card description',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'_meowseo_twitter_image_id'    => array(
				'type'              => 'integer',
				'description'       => 'Twitter card image attachment ID',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'_meowseo_use_og_for_twitter'  => array(
				'type'              => 'boolean',
				'description'       => 'Use Open Graph data for Twitter',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),

			// Schema tab
			'_meowseo_schema_type'    => array(
				'type'              => 'string',
				'description'       => 'Schema.org type',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_meowseo_schema_config'  => array(
				'type'              => 'string',
				'description'       => 'Schema.org configuration JSON',
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_schema_config' ),
			),

			// Speakable content
			'_meowseo_speakable_block' => array(
				'type'              => 'string',
				'description'       => 'Block ID marked as speakable content',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),

			// Advanced tab
			'_meowseo_robots_noindex' => array(
				'type'              => 'boolean',
				'description'       => 'Robots noindex directive',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'_meowseo_robots_nofollow' => array(
				'type'              => 'boolean',
				'description'       => 'Robots nofollow directive',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'_meowseo_canonical'      => array(
				'type'              => 'string',
				'description'       => 'Canonical URL override',
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			),
			'_meowseo_gsc_last_submit' => array(
				'type'              => 'string',
				'description'       => 'Last Google Search Console submission timestamp',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Sanitize schema configuration JSON
	 *
	 * Requirement 18.8: Validate schema configuration JSON before storage
	 *
	 * @param string $value Schema configuration JSON string.
	 * @return string Sanitized JSON string or empty string if invalid.
	 */
	public function sanitize_schema_config( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		// Decode JSON to validate structure.
		$decoded = json_decode( $value, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			// Invalid JSON, return empty string.
			return '';
		}

		// Validate that it's an array or object.
		if ( ! is_array( $decoded ) ) {
			return '';
		}

		// Re-encode to ensure clean JSON.
		$sanitized = wp_json_encode( $decoded );

		return $sanitized !== false ? $sanitized : '';
	}

	/**
	 * Log asset loading errors
	 *
	 * Centralized error logging for missing asset files.
	 * Logs to WordPress debug log and stores admin notice in transient.
	 *
	 * @param string $file_name Relative file name (e.g., 'build/gutenberg.js').
	 * @param string $file_path Absolute file path for debugging.
	 */
	private function log_asset_error( string $file_name, string $file_path ): void {
		// Log to WordPress debug log
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// Use @ to suppress any potential errors from error_log in test environments
			@error_log(
				sprintf(
					'MeowSEO: Missing asset file %s (expected at: %s). Please run npm run build to generate asset files.',
					$file_name,
					$file_path
				)
			);
		}

		// Store admin notice in transient for display (only if function exists)
		if ( function_exists( 'get_transient' ) && function_exists( 'set_transient' ) ) {
			$notices = @get_transient( 'meowseo_asset_errors' );
			if ( ! is_array( $notices ) ) {
				$notices = array();
			}

			$notice_key = md5( $file_path );
			if ( ! isset( $notices[ $notice_key ] ) ) {
				$notices[ $notice_key ] = array(
					'file_name' => $file_name,
					'file_path' => $file_path,
					'timestamp' => time(),
				);
				$expiration = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400;
				@set_transient( 'meowseo_asset_errors', $notices, $expiration );
			}
		}
	}
}
