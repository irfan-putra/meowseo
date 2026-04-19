<?php
/**
 * Image SEO Handler
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Image_SEO;

use MeowSEO\Options;
use WP_Post;

/**
 * Image_SEO_Handler class
 *
 * Automatically generates alt text for images using pattern-based templates.
 */
class Image_SEO_Handler {
	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Pattern processor instance
	 *
	 * @var Pattern_Processor
	 */
	private Pattern_Processor $pattern_processor;

	/**
	 * Constructor
	 *
	 * @param Options           $options           Options instance.
	 * @param Pattern_Processor $pattern_processor Pattern processor instance.
	 */
	public function __construct( Options $options, Pattern_Processor $pattern_processor ) {
		$this->options           = $options;
		$this->pattern_processor = $pattern_processor;
	}

	/**
	 * Boot the handler
	 *
	 * Hooks into WordPress filters if enabled.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Hook into image attribute filter.
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'filter_image_attributes' ), 10, 2 );
	}

	/**
	 * Filter image attributes to add alt text
	 *
	 * Requirements: 4.1, 4.5, 4.9, 4.10
	 *
	 * @param array   $attr       Image attributes.
	 * @param WP_Post $attachment Attachment post object.
	 * @return array Modified image attributes.
	 */
	public function filter_image_attributes( array $attr, WP_Post $attachment ): array {
		// Skip if alt text exists and we're not overriding.
		if ( ! empty( $attr['alt'] ) && ! $this->should_override_existing() ) {
			return $attr;
		}

		// Generate alt text from pattern.
		$alt_text = $this->generate_alt_text( $attachment );

		if ( ! empty( $alt_text ) ) {
			$attr['alt'] = $alt_text;
		}

		return $attr;
	}

	/**
	 * Check if image SEO is enabled
	 *
	 * Requirement: 4.7, 4.8
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		return (bool) $this->options->get( 'image_seo_enabled', false );
	}

	/**
	 * Check if existing alt text should be overridden
	 *
	 * Requirement: 4.10
	 *
	 * @return bool True if should override, false otherwise.
	 */
	public function should_override_existing(): bool {
		return (bool) $this->options->get( 'image_seo_override_existing', false );
	}

	/**
	 * Generate alt text for an attachment
	 *
	 * Requirements: 4.1, 4.6
	 *
	 * @param WP_Post $attachment Attachment post object.
	 * @return string Generated alt text.
	 */
	private function generate_alt_text( WP_Post $attachment ): string {
		$pattern = $this->options->get( 'image_seo_alt_pattern', '%imagetitle%' );

		$variables = array(
			'%imagetitle%' => get_the_title( $attachment ),
			'%imagealt%'   => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'%sitename%'   => get_bloginfo( 'name' ),
		);

		return $this->pattern_processor->process( $pattern, $variables );
	}
}
