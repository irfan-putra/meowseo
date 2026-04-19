<?php
/**
 * Robots.txt Editor Class
 *
 * Provides admin interface for editing virtual robots.txt content.
 * Includes syntax validation and default content generation.
 *
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 * @since 2.0.0
 */

namespace MeowSEO\Modules\Meta;

use MeowSEO\Options;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Robots_Txt_Editor class
 *
 * Handles robots.txt editing, validation, and storage.
 */
class Robots_Txt_Editor {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Robots_Txt instance
	 *
	 * @var Robots_Txt
	 */
	private Robots_Txt $robots_txt;

	/**
	 * Maximum file size (500KB)
	 *
	 * @var int
	 */
	private const MAX_SIZE = 512000;

	/**
	 * Constructor
	 *
	 * @param Options    $options    Options instance.
	 * @param Robots_Txt $robots_txt Robots_Txt instance.
	 */
	public function __construct( Options $options, Robots_Txt $robots_txt ) {
		$this->options    = $options;
		$this->robots_txt = $robots_txt;
	}

	/**
	 * Render editor UI
	 *
	 * Renders the robots.txt editor interface for admin.
	 * Requirement 4.1: Provide admin interface for editing robots.txt.
	 *
	 * @return void
	 */
	public function render_editor_ui(): void {
		$current_content = $this->get_current_content();
		$preview_url     = home_url( '/robots.txt' );
		?>
		<tr>
			<th scope="row">
				<label for="robots_txt_content">
					<?php esc_html_e( 'Robots.txt Content', 'meowseo' ); ?>
				</label>
			</th>
			<td>
				<textarea 
					name="robots_txt_content" 
					id="robots_txt_content" 
					rows="20" 
					class="large-text code"
					spellcheck="false"
					style="font-family: monospace; font-size: 13px;"
				><?php echo esc_textarea( $current_content ); ?></textarea>
				
				<p class="description">
					<?php esc_html_e( 'Edit your robots.txt file content. Valid directives: User-agent, Disallow, Allow, Sitemap, Crawl-delay.', 'meowseo' ); ?>
					<br>
					<a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Preview robots.txt', 'meowseo' ); ?>
					</a>
				</p>

				<p>
					<button type="button" 
							id="meowseo-reset-robots-txt" 
							class="button"
							onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to reset to default content? This cannot be undone.', 'meowseo' ) ); ?>');">
						<?php esc_html_e( 'Reset to Default', 'meowseo' ); ?>
					</button>
				</p>

				<div id="meowseo-robots-txt-help" style="margin-top: 15px; padding: 10px; background: #f7f7f7; border-left: 4px solid #2271b1;">
					<h4 style="margin-top: 0;"><?php esc_html_e( 'Common Directives', 'meowseo' ); ?></h4>
					<ul style="margin-bottom: 0;">
						<li><code>User-agent: *</code> - <?php esc_html_e( 'Applies to all crawlers', 'meowseo' ); ?></li>
						<li><code>Disallow: /path/</code> - <?php esc_html_e( 'Blocks access to a path', 'meowseo' ); ?></li>
						<li><code>Allow: /path/</code> - <?php esc_html_e( 'Allows access to a path', 'meowseo' ); ?></li>
						<li><code>Sitemap: URL</code> - <?php esc_html_e( 'Points to your sitemap', 'meowseo' ); ?></li>
						<li><code>Crawl-delay: 10</code> - <?php esc_html_e( 'Delay between requests (seconds)', 'meowseo' ); ?></li>
					</ul>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save robots.txt content
	 *
	 * Validates and saves robots.txt content to options.
	 * Requirement 4.2: Validate syntax before saving.
	 *
	 * @param string $content Robots.txt content to save.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function save_robots_txt( string $content ): bool|WP_Error {
		// Requirement 4.4: Validate syntax before saving.
		$validation = $this->validate_syntax( $content );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Requirement 4.3: Store content in options.
		$this->options->set( 'robots_txt_content', $content );

		return true;
	}

	/**
	 * Reset to default content
	 *
	 * Resets robots.txt content to default WordPress content.
	 * Requirement 4.7: Provide reset functionality.
	 *
	 * @return bool True on success.
	 */
	public function reset_to_default(): bool {
		$default_content = $this->get_default_content();
		$this->options->set( 'robots_txt_content', $default_content );

		return true;
	}

	/**
	 * Get current content
	 *
	 * Returns current robots.txt content or default if empty.
	 * Requirement 4.6: Use default content if custom content is empty.
	 *
	 * @return string Current robots.txt content.
	 */
	public function get_current_content(): string {
		$content = $this->options->get( 'robots_txt_content', '' );

		// Return default content if empty.
		if ( empty( trim( $content ) ) ) {
			return $this->get_default_content();
		}

		return $content;
	}

	/**
	 * Get default content
	 *
	 * Generates default robots.txt content.
	 * Requirement 4.7: Generate default content with sensible directives.
	 *
	 * @return string Default robots.txt content.
	 */
	public function get_default_content(): string {
		$sitemap_url = home_url( '/meowseo-sitemap.xml' );

		$lines = array(
			'User-agent: *',
			'Disallow: /wp-admin/',
			'Allow: /wp-admin/admin-ajax.php',
			'',
			'Sitemap: ' . $sitemap_url,
		);

		return implode( "\n", $lines );
	}

	/**
	 * Validate robots.txt syntax
	 *
	 * Validates robots.txt content for correct syntax.
	 * Requirements 4.4, 4.5: Validate syntax and return detailed errors.
	 *
	 * @param string $content Content to validate.
	 * @return bool|WP_Error True if valid, WP_Error with details if invalid.
	 */
	public function validate_syntax( string $content ): bool|WP_Error {
		// Requirement 4.5: Check size limit (500KB max).
		if ( strlen( $content ) > self::MAX_SIZE ) {
			return new WP_Error(
				'robots_txt_too_large',
				__( 'Robots.txt content exceeds 500KB limit.', 'meowseo' )
			);
		}

		// Requirement 4.5: Check for HTML tags.
		if ( $content !== wp_strip_all_tags( $content ) ) {
			return new WP_Error(
				'robots_txt_contains_html',
				__( 'Robots.txt cannot contain HTML tags.', 'meowseo' )
			);
		}

		// Requirement 4.4: Check for at least one User-agent directive.
		if ( ! preg_match( '/^User-agent:/mi', $content ) ) {
			return new WP_Error(
				'robots_txt_no_user_agent',
				__( 'Robots.txt must contain at least one User-agent directive.', 'meowseo' )
			);
		}

		// Requirement 4.4: Validate directive types and paths.
		$lines       = explode( "\n", $content );
		$line_number = 0;

		foreach ( $lines as $line ) {
			$line_number++;
			$line = trim( $line );

			// Skip empty lines and comments.
			if ( empty( $line ) || strpos( $line, '#' ) === 0 ) {
				continue;
			}

			// Check if line contains a colon (required for directives).
			if ( strpos( $line, ':' ) === false ) {
				return new WP_Error(
					'robots_txt_invalid_directive',
					sprintf(
						/* translators: 1: Line number, 2: Line content */
						__( 'Invalid directive on line %1$d: %2$s', 'meowseo' ),
						$line_number,
						$line
					)
				);
			}

			// Split directive and value.
			list( $directive, $value ) = array_map( 'trim', explode( ':', $line, 2 ) );

			// Requirement 4.4: Validate directive types.
			$valid_directives = array( 'User-agent', 'Disallow', 'Allow', 'Sitemap', 'Crawl-delay' );
			if ( ! in_array( $directive, $valid_directives, true ) ) {
				return new WP_Error(
					'robots_txt_invalid_directive',
					sprintf(
						/* translators: 1: Line number, 2: Directive name */
						__( 'Invalid directive on line %1$d: %2$s. Valid directives are: User-agent, Disallow, Allow, Sitemap, Crawl-delay', 'meowseo' ),
						$line_number,
						$directive
					)
				);
			}

			// Requirement 4.4: Validate paths for Disallow and Allow directives.
			if ( in_array( $directive, array( 'Disallow', 'Allow' ), true ) ) {
				// Empty value is valid for Disallow/Allow.
				if ( ! empty( $value ) && $value !== '*' && strpos( $value, '/' ) !== 0 ) {
					return new WP_Error(
						'robots_txt_invalid_path',
						sprintf(
							/* translators: 1: Line number, 2: Path value */
							__( 'Invalid path on line %1$d: %2$s. Paths must start with / or be *', 'meowseo' ),
							$line_number,
							$value
						)
					);
				}
			}
		}

		return true;
	}
}
