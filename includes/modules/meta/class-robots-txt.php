<?php
/**
 * Robots_Txt class for virtual robots.txt management.
 *
 * Manages the virtual robots.txt file via WordPress's robots_txt filter.
 * Does NOT write a physical robots.txt file to the filesystem.
 *
 * @package MeowSEO\Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Robots_Txt class.
 *
 * Handles virtual robots.txt file management through WordPress filters.
 * Automatically includes default directives, custom directives, and sitemap URL.
 */
class Robots_Txt {

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
	 * Register hooks.
	 *
	 * Hooks into the robots_txt filter to manage virtual robots.txt output.
	 * Does NOT write a physical robots.txt file (Requirement 11.1).
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ), 10, 2 );
	}

	/**
	 * Filter robots.txt output.
	 *
	 * Generates the complete robots.txt content with default directives,
	 * custom directives, and sitemap URL (Requirements 11.1-11.6).
	 * Requirement 4.6: Use custom content if configured, otherwise use default.
	 *
	 * @param string $output The default robots.txt output from WordPress.
	 * @param bool   $public Whether the site is public.
	 * @return string The filtered robots.txt output.
	 */
	public function filter_robots_txt( string $output, bool $public ): string {
		// If site is not public, return WordPress default (Disallow: /).
		if ( ! $public ) {
			return $output;
		}

		// Requirement 4.6: Check for custom content from editor.
		$custom_content = $this->get_custom_content();
		if ( ! empty( $custom_content ) ) {
			return $custom_content . "\n";
		}

		// Build sections array.
		$sections = array(
			$this->get_default_directives(),
			$this->get_custom_directives(),
			$this->get_sitemap_url(),
		);

		// Format and return.
		return $this->format_robots_txt( $sections );
	}

	/**
	 * Get default directives.
	 *
	 * Returns sensible default directives for WordPress sites (Requirement 11.4).
	 *
	 * @return string Default directives.
	 */
	private function get_default_directives(): string {
		$directives = array(
			'User-agent: *',
			'Disallow: /wp-admin/',
			'Disallow: /wp-login.php',
			'Disallow: /wp-includes/',
		);

		return implode( "\n", $directives );
	}

	/**
	 * Get custom directives from settings.
	 *
	 * Returns custom directives configured by the user in plugin settings.
	 * Custom directives are appended after default directives (Requirement 11.3, 11.6).
	 *
	 * @return string Custom directives or empty string if none configured.
	 */
	private function get_custom_directives(): string {
		$custom = $this->options->get( 'robots_txt_custom', '' );

		// Trim and return only if non-empty.
		$custom = trim( $custom );

		return ! empty( $custom ) ? $custom : '';
	}

	/**
	 * Get sitemap URL.
	 *
	 * Returns the sitemap index URL to be automatically appended to robots.txt
	 * (Requirement 11.2).
	 *
	 * @return string Sitemap URL line.
	 */
	private function get_sitemap_url(): string {
		$sitemap_url = home_url( '/meowseo-sitemap.xml' );

		return 'Sitemap: ' . $sitemap_url;
	}

	/**
	 * Format robots.txt output.
	 *
	 * Formats the robots.txt output with proper line breaks and structure
	 * (Requirement 11.5).
	 *
	 * @param array $sections Array of content sections (default, custom, sitemap).
	 * @return string Formatted robots.txt output.
	 */
	private function format_robots_txt( array $sections ): string {
		// Filter out empty sections.
		$sections = array_filter(
			$sections,
			function ( $section ) {
				return ! empty( trim( $section ) );
			}
		);

		// Join sections with double line breaks.
		return implode( "\n\n", $sections ) . "\n";
	}

	/**
	 * Get custom content from editor
	 *
	 * Returns custom robots.txt content configured via the editor.
	 * Requirement 4.3: Store content in meowseo_options['robots_txt_content'].
	 *
	 * @return string Custom content or empty string if none configured.
	 */
	public function get_custom_content(): string {
		$content = $this->options->get( 'robots_txt_content', '' );
		return trim( $content );
	}

	/**
	 * Set custom content
	 *
	 * Stores custom robots.txt content in options.
	 * Requirement 4.3: Store content in meowseo_options['robots_txt_content'].
	 *
	 * @param string $content Custom robots.txt content.
	 * @return void
	 */
	public function set_custom_content( string $content ): void {
		$this->options->set( 'robots_txt_content', $content );
	}
}

