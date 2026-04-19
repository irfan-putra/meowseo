<?php
/**
 * Pattern Processor
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Image_SEO;

/**
 * Pattern_Processor class
 *
 * Processes pattern templates with variable substitution.
 */
class Pattern_Processor {
	/**
	 * Process pattern with variables
	 *
	 * @param string $pattern   Pattern template.
	 * @param array  $variables Variables to substitute.
	 * @return string Processed output.
	 */
	public function process( string $pattern, array $variables ): string {
		$output = $pattern;

		foreach ( $variables as $variable => $value ) {
			$output = str_replace( $variable, $value, $output );
		}

		return $this->sanitize_output( $output );
	}

	/**
	 * Get available pattern variables
	 *
	 * @return array Available variables with descriptions.
	 */
	public function get_available_variables(): array {
		return array(
			'%imagetitle%' => __( 'Image title', 'meowseo' ),
			'%imagealt%'   => __( 'Existing alt text', 'meowseo' ),
			'%sitename%'   => __( 'Site name', 'meowseo' ),
		);
	}

	/**
	 * Sanitize output text
	 *
	 * @param string $text Text to sanitize.
	 * @return string Sanitized text.
	 */
	private function sanitize_output( string $text ): string {
		// Remove HTML tags.
		$text = wp_strip_all_tags( $text );

		// Trim whitespace.
		$text = trim( $text );

		// Limit length to 125 characters (recommended alt text length).
		if ( strlen( $text ) > 125 ) {
			$text = substr( $text, 0, 122 ) . '...';
		}

		return $text;
	}
}
