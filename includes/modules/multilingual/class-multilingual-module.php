<?php
/**
 * Multilingual Module
 *
 * Integrates with WPML and Polylang for multilingual SEO support.
 * Provides hreflang tag generation, per-language metadata storage, and translation plugin detection.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Multilingual;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multilingual module class
 *
 * Implements the Module interface to provide multilingual SEO support.
 *
 * @since 1.0.0
 */
class Multilingual_Module implements Module {

	/**
	 * Module ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const MODULE_ID = 'multilingual';

	/**
	 * Postmeta key prefix
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const META_PREFIX = 'meowseo_';

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Detected translation plugin
	 *
	 * @since 1.0.0
	 * @var ?string
	 */
	private ?string $detected_plugin = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module
	 *
	 * Register hooks and initialize module functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		// Output hreflang tags in wp_head.
		add_action( 'wp_head', array( $this, 'output_hreflang_tags' ), 5 );

		// Invalidate cache on post save.
		add_action( 'save_post', array( $this, 'invalidate_cache' ), 10, 1 );
	}

	/**
	 * Get module ID
	 *
	 * @since 1.0.0
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return self::MODULE_ID;
	}

	/**
	 * Detect translation plugin
	 *
	 * Identifies if WPML or Polylang is active and returns the plugin name.
	 * Caches the result to avoid repeated function_exists checks.
	 *
	 * Requirements: 2.1, 2.2
	 *
	 * @since 1.0.0
	 * @return ?string Plugin name ('wpml', 'polylang') or null if none detected.
	 */
	public function detect_translation_plugin(): ?string {
		// Return cached result if already detected.
		if ( null !== $this->detected_plugin ) {
			return $this->detected_plugin;
		}

		// Check for WPML.
		if ( function_exists( 'wpml_get_language_information' ) ) {
			$this->detected_plugin = 'wpml';
			return 'wpml';
		}

		// Check for Polylang.
		if ( function_exists( 'pll_get_post_translations' ) ) {
			$this->detected_plugin = 'polylang';
			return 'polylang';
		}

		// No translation plugin detected.
		$this->detected_plugin = false;
		return null;
	}

	/**
	 * Get translations for a post
	 *
	 * Returns an array of post IDs for each language version of the given post.
	 * Format: ['en' => 123, 'es' => 456, 'fr' => 789]
	 *
	 * Requirements: 2.1, 2.2
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Translations array with language codes as keys and post IDs as values.
	 */
	public function get_translations( int $post_id ): array {
		$plugin = $this->detect_translation_plugin();

		if ( 'wpml' === $plugin ) {
			return $this->get_wpml_translations( $post_id );
		}

		if ( 'polylang' === $plugin ) {
			return $this->get_polylang_translations( $post_id );
		}

		// No translation plugin, return current post only.
		return array( $this->get_current_language() => $post_id );
	}

	/**
	 * Get WPML translations
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Translations array.
	 */
	private function get_wpml_translations( int $post_id ): array {
		if ( ! function_exists( 'wpml_get_element_translations' ) ) {
			return array();
		}

		$translations = array();
		$element_translations = wpml_get_element_translations( $post_id, 'post_' . get_post_type( $post_id ) );

		if ( is_array( $element_translations ) ) {
			foreach ( $element_translations as $translation ) {
				if ( isset( $translation->language_code, $translation->element_id ) ) {
					$translations[ $translation->language_code ] = (int) $translation->element_id;
				}
			}
		}

		return $translations;
	}

	/**
	 * Get Polylang translations
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Translations array.
	 */
	private function get_polylang_translations( int $post_id ): array {
		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return array();
		}

		$translations = pll_get_post_translations( $post_id );

		// Polylang returns array with language codes as keys and post IDs as values.
		return is_array( $translations ) ? $translations : array();
	}

	/**
	 * Get default language
	 *
	 * Returns the default language code for the site.
	 *
	 * Requirements: 2.1, 2.2
	 *
	 * @since 1.0.0
	 * @return string Language code (e.g., 'en', 'es', 'fr').
	 */
	public function get_default_language(): string {
		$plugin = $this->detect_translation_plugin();

		if ( 'wpml' === $plugin ) {
			if ( function_exists( 'wpml_get_language_information' ) ) {
				$info = wpml_get_language_information();
				if ( isset( $info['default_language'] ) ) {
					return $info['default_language'];
				}
			}
		}

		if ( 'polylang' === $plugin ) {
			if ( function_exists( 'pll_default_language' ) ) {
				$default = pll_default_language();
				if ( ! empty( $default ) ) {
					return $default;
				}
			}
		}

		// Fallback to WordPress locale.
		$locale = get_locale();
		// Extract language code from locale (e.g., 'en_US' -> 'en').
		return substr( $locale, 0, 2 );
	}

	/**
	 * Get current language
	 *
	 * Returns the currently active language code.
	 *
	 * Requirements: 2.1, 2.2
	 *
	 * @since 1.0.0
	 * @return string Language code (e.g., 'en', 'es', 'fr').
	 */
	public function get_current_language(): string {
		$plugin = $this->detect_translation_plugin();

		if ( 'wpml' === $plugin ) {
			if ( function_exists( 'wpml_get_language_information' ) ) {
				$info = wpml_get_language_information();
				if ( isset( $info['current_language'] ) ) {
					return $info['current_language'];
				}
			}
		}

		if ( 'polylang' === $plugin ) {
			if ( function_exists( 'pll_current_language' ) ) {
				$current = pll_current_language();
				if ( ! empty( $current ) ) {
					return $current;
				}
			}
		}

		// Fallback to WordPress locale.
		$locale = get_locale();
		// Extract language code from locale (e.g., 'en_US' -> 'en').
		return substr( $locale, 0, 2 );
	}

	/**
	 * Output hreflang tags
	 *
	 * Outputs hreflang link tags in the HTML head for each language version of the current post.
	 * Includes x-default tag pointing to the default language version.
	 *
	 * Requirements: 2.3, 2.4
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_hreflang_tags(): void {
		// Only output on singular posts.
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		// Get translations for current post.
		$translations = $this->get_translations( $post_id );

		if ( empty( $translations ) ) {
			return;
		}

		// Output hreflang tags for each translation.
		foreach ( $translations as $language => $translated_post_id ) {
			$url = get_permalink( $translated_post_id );
			if ( $url ) {
				echo '<link rel="alternate" hreflang="' . esc_attr( $language ) . '" href="' . esc_url( $url ) . '">' . "\n";
			}
		}

		// Output x-default tag pointing to default language version.
		$default_language = $this->get_default_language();
		if ( isset( $translations[ $default_language ] ) ) {
			$default_url = get_permalink( $translations[ $default_language ] );
			if ( $default_url ) {
				echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $default_url ) . '">' . "\n";
			}
		}
	}

	/**
	 * Get translated metadata
	 *
	 * Retrieves SEO metadata for a specific language version of a post.
	 * Uses language-suffixed postmeta keys (e.g., _meowseo_title_en, _meowseo_title_es).
	 *
	 * Requirements: 2.5, 2.6
	 *
	 * @since 1.0.0
	 * @param int    $post_id  Post ID.
	 * @param string $language Language code (e.g., 'en', 'es').
	 * @return array Translated metadata array.
	 */
	public function get_translated_metadata( int $post_id, string $language ): array {
		$metadata = array();

		// List of SEO metadata keys to retrieve.
		$meta_keys = array(
			'title',
			'description',
			'keywords',
			'robots',
			'canonical',
			'social_title',
			'social_description',
		);

		foreach ( $meta_keys as $key ) {
			// Build language-suffixed key.
			$meta_key = self::META_PREFIX . $key . '_' . $language;
			$value = get_post_meta( $post_id, $meta_key, true );

			if ( ! empty( $value ) ) {
				$metadata[ $key ] = $value;
			}
		}

		return $metadata;
	}

	/**
	 * Sync schema translations
	 *
	 * Synchronizes schema properties across language versions of a post.
	 * Ensures schema data is consistent and properly translated.
	 *
	 * Requirements: 2.5, 2.6
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function sync_schema_translations( int $post_id ): void {
		// Get all translations for this post.
		$translations = $this->get_translations( $post_id );

		if ( empty( $translations ) ) {
			return;
		}

		// Get schema data from the primary post.
		$schema_data = get_post_meta( $post_id, self::META_PREFIX . 'schema', true );

		if ( empty( $schema_data ) ) {
			return;
		}

		// Sync schema to all translated versions.
		foreach ( $translations as $language => $translated_post_id ) {
			if ( $translated_post_id === $post_id ) {
				// Skip the primary post itself.
				continue;
			}

			// Store schema with language suffix.
			$schema_key = self::META_PREFIX . 'schema_' . $language;
			update_post_meta( $translated_post_id, $schema_key, $schema_data );
		}
	}

	/**
	 * Invalidate cache on post save
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function invalidate_cache( int $post_id ): void {
		// Cache invalidation can be extended here if needed.
		// Currently, hreflang tags are generated on-demand.
	}
}
