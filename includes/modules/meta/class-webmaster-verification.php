<?php
/**
 * Webmaster Verification Class
 *
 * Outputs verification meta tags for webmaster tools in document head.
 * Supports Google Search Console, Bing Webmaster Tools, and Yandex Webmaster.
 *
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9
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
 * Webmaster_Verification class
 *
 * Handles verification meta tag output for webmaster tools.
 */
class Webmaster_Verification {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Output verification meta tags
	 *
	 * Outputs verification meta tags for configured webmaster tools.
	 * Requirements 3.4, 3.5, 3.6, 3.7, 3.9: Output meta tags when configured.
	 *
	 * @return void
	 */
	public function output_verification_tags(): void {
		// Get verification codes from options.
		$verification = $this->options->get( 'webmaster_verification', array() );

		// Requirement 3.4: Output Google meta tag when configured.
		$this->output_google_verification( $verification );

		// Requirement 3.5: Output Bing meta tag when configured.
		$this->output_bing_verification( $verification );

		// Requirement 3.6: Output Yandex meta tag when configured.
		$this->output_yandex_verification( $verification );
	}

	/**
	 * Output Google Search Console verification meta tag
	 *
	 * Requirement 3.4: Output Google meta tag with name="google-site-verification".
	 * Requirement 3.7: Omit meta tag when verification code is empty.
	 *
	 * @param array $verification Verification codes array.
	 * @return void
	 */
	private function output_google_verification( array $verification ): void {
		if ( empty( $verification['google'] ) ) {
			return;
		}

		$code = $this->sanitize_verification_code( $verification['google'] );
		if ( is_wp_error( $code ) || empty( $code ) ) {
			return;
		}

		echo '<meta name="google-site-verification" content="' . esc_attr( $code ) . '">' . "\n";
	}

	/**
	 * Output Bing Webmaster Tools verification meta tag
	 *
	 * Requirement 3.5: Output Bing meta tag with name="msvalidate.01".
	 * Requirement 3.7: Omit meta tag when verification code is empty.
	 *
	 * @param array $verification Verification codes array.
	 * @return void
	 */
	private function output_bing_verification( array $verification ): void {
		if ( empty( $verification['bing'] ) ) {
			return;
		}

		$code = $this->sanitize_verification_code( $verification['bing'] );
		if ( is_wp_error( $code ) || empty( $code ) ) {
			return;
		}

		echo '<meta name="msvalidate.01" content="' . esc_attr( $code ) . '">' . "\n";
	}

	/**
	 * Output Yandex Webmaster verification meta tag
	 *
	 * Requirement 3.6: Output Yandex meta tag with name="yandex-verification".
	 * Requirement 3.7: Omit meta tag when verification code is empty.
	 *
	 * @param array $verification Verification codes array.
	 * @return void
	 */
	private function output_yandex_verification( array $verification ): void {
		if ( empty( $verification['yandex'] ) ) {
			return;
		}

		$code = $this->sanitize_verification_code( $verification['yandex'] );
		if ( is_wp_error( $code ) || empty( $code ) ) {
			return;
		}

		echo '<meta name="yandex-verification" content="' . esc_attr( $code ) . '">' . "\n";
	}

	/**
	 * Sanitize verification code
	 *
	 * Requirement 3.8: Sanitize verification codes to prevent XSS.
	 * - Strip HTML tags with wp_strip_all_tags()
	 * - Remove whitespace with trim()
	 * - Validate alphanumeric + hyphens + underscores only
	 * - Max length: 100 characters
	 *
	 * @param string $code Verification code to sanitize.
	 * @return string|WP_Error Sanitized code or WP_Error if invalid.
	 */
	public function sanitize_verification_code( string $code ): string|WP_Error {
		// Strip HTML tags.
		$code = wp_strip_all_tags( $code );

		// Remove whitespace.
		$code = trim( $code );

		// Empty codes are valid (will be omitted from output).
		if ( empty( $code ) ) {
			return '';
		}

		// Validate format: alphanumeric, hyphens, underscores only, max 100 chars.
		if ( ! preg_match( '/^[a-zA-Z0-9_-]{1,100}$/', $code ) ) {
			return new WP_Error(
				'invalid_verification_code',
				__( 'Verification code must contain only letters, numbers, hyphens, and underscores (max 100 characters).', 'meowseo' )
			);
		}

		return $code;
	}

	/**
	 * Get Google verification code
	 *
	 * @return string Google verification code or empty string.
	 */
	private function get_google_verification_code(): string {
		$verification = $this->options->get( 'webmaster_verification', array() );
		return $verification['google'] ?? '';
	}

	/**
	 * Get Bing verification code
	 *
	 * @return string Bing verification code or empty string.
	 */
	private function get_bing_verification_code(): string {
		$verification = $this->options->get( 'webmaster_verification', array() );
		return $verification['bing'] ?? '';
	}

	/**
	 * Get Yandex verification code
	 *
	 * @return string Yandex verification code or empty string.
	 */
	private function get_yandex_verification_code(): string {
		$verification = $this->options->get( 'webmaster_verification', array() );
		return $verification['yandex'] ?? '';
	}
}
