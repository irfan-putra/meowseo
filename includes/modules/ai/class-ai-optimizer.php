<?php
/**
 * AI Optimizer
 *
 * Generates AI-powered suggestions for fixing failing SEO checks.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\AI;

use MeowSEO\Core\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI_Optimizer class
 *
 * Provides AI-powered suggestions for improving SEO check results.
 *
 * @since 1.0.0
 */
class AI_Optimizer {

	/**
	 * AI Provider Manager instance
	 *
	 * @var AI_Provider_Manager
	 */
	private AI_Provider_Manager $provider_manager;

	/**
	 * Cache duration for suggestions (1 hour)
	 *
	 * @var int
	 */
	private const CACHE_DURATION = HOUR_IN_SECONDS;

	/**
	 * Supported SEO checks
	 *
	 * @var array
	 */
	private const SUPPORTED_CHECKS = [
		'keyword_density',
		'keyword_in_title',
		'keyword_in_headings',
		'keyword_in_first_paragraph',
		'keyword_in_meta_description',
		'title_length',
		'description_length',
		'content_length',
		'internal_links',
		'external_links',
		'image_alt_text',
	];

	/**
	 * Constructor
	 *
	 * @param AI_Provider_Manager $provider_manager AI provider manager instance.
	 */
	public function __construct( AI_Provider_Manager $provider_manager ) {
		$this->provider_manager = $provider_manager;
	}

	/**
	 * Get AI suggestion for a failing SEO check
	 *
	 * @param string $check_name Check name (e.g., 'keyword_in_title').
	 * @param string $content    Current content excerpt.
	 * @param string $keyword    Focus keyword.
	 * @param int    $post_id    Post ID for caching.
	 * @return string|WP_Error Suggestion text or WP_Error on failure.
	 */
	public function get_suggestion( string $check_name, string $content, string $keyword, int $post_id = 0 ) {
		// Check if check is supported
		if ( ! in_array( $check_name, self::SUPPORTED_CHECKS, true ) ) {
			return new \WP_Error(
				'unsupported_check',
				__( 'This SEO check is not supported for AI suggestions.', 'meowseo' )
			);
		}

		// Try to get cached suggestion
		if ( $post_id > 0 ) {
			$cached = $this->get_cached_suggestion( $post_id, $check_name );
			if ( null !== $cached ) {
				return $cached;
			}
		}

		// Construct prompt
		$prompt = $this->build_prompt( $check_name, $content, $keyword );

		// Call AI provider
		$result = $this->provider_manager->generate_text( $prompt, [
			'max_tokens' => 200,
			'temperature' => 0.7,
		] );

		if ( is_wp_error( $result ) ) {
			Logger::error(
				'AI Optimizer suggestion generation failed',
				[
					'module' => 'ai',
					'check_name' => $check_name,
					'error' => $result->get_error_message(),
				]
			);

			return $result;
		}

		$suggestion = $result['content'] ?? '';

		// Cache the suggestion
		if ( $post_id > 0 && ! empty( $suggestion ) ) {
			$this->cache_suggestion( $post_id, $check_name, $suggestion );
		}

		return $suggestion;
	}

	/**
	 * Get cached suggestion
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $check_name Check name.
	 * @return string|null Cached suggestion or null if not found.
	 */
	public function get_cached_suggestion( int $post_id, string $check_name ): ?string {
		$cache_key = $this->get_cache_key( $post_id, $check_name );
		$cached = get_transient( $cache_key );

		return false !== $cached ? $cached : null;
	}

	/**
	 * Cache suggestion
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $check_name Check name.
	 * @param string $suggestion Suggestion text.
	 * @return bool True on success, false on failure.
	 */
	private function cache_suggestion( int $post_id, string $check_name, string $suggestion ): bool {
		$cache_key = $this->get_cache_key( $post_id, $check_name );
		return set_transient( $cache_key, $suggestion, self::CACHE_DURATION );
	}

	/**
	 * Clear suggestion cache for a post
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function clear_suggestion_cache( int $post_id ): void {
		foreach ( self::SUPPORTED_CHECKS as $check_name ) {
			$cache_key = $this->get_cache_key( $post_id, $check_name );
			delete_transient( $cache_key );
		}
	}

	/**
	 * Get cache key for a suggestion
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $check_name Check name.
	 * @return string Cache key.
	 */
	private function get_cache_key( int $post_id, string $check_name ): string {
		return 'meowseo_ai_suggestion_' . $post_id . '_' . $check_name;
	}

	/**
	 * Build prompt for AI provider
	 *
	 * @param string $check_name Check name.
	 * @param string $content    Current content excerpt.
	 * @param string $keyword    Focus keyword.
	 * @return string Prompt text.
	 */
	private function build_prompt( string $check_name, string $content, string $keyword ): string {
		$check_label = $this->get_check_label( $check_name );

		$prompt = sprintf(
			"This content is failing the %s SEO check.\n\nFocus keyword: %s\n\nCurrent content: %s\n\nProvide a specific, actionable suggestion to fix this issue. Be concise and practical.",
			$check_label,
			$keyword,
			$this->truncate_content( $content, 500 )
		);

		return $prompt;
	}

	/**
	 * Get human-readable label for check name
	 *
	 * @param string $check_name Check name.
	 * @return string Human-readable label.
	 */
	private function get_check_label( string $check_name ): string {
		$labels = [
			'keyword_density' => 'keyword density',
			'keyword_in_title' => 'keyword in title',
			'keyword_in_headings' => 'keyword in headings',
			'keyword_in_first_paragraph' => 'keyword in first paragraph',
			'keyword_in_meta_description' => 'keyword in meta description',
			'title_length' => 'title length',
			'description_length' => 'description length',
			'content_length' => 'content length',
			'internal_links' => 'internal links',
			'external_links' => 'external links',
			'image_alt_text' => 'image alt text',
		];

		return $labels[ $check_name ] ?? $check_name;
	}

	/**
	 * Truncate content to specified length
	 *
	 * @param string $content Content to truncate.
	 * @param int    $length  Maximum length.
	 * @return string Truncated content.
	 */
	private function truncate_content( string $content, int $length ): string {
		$content = wp_strip_all_tags( $content );
		$content = trim( $content );

		if ( mb_strlen( $content ) <= $length ) {
			return $content;
		}

		return mb_substr( $content, 0, $length ) . '...';
	}

	/**
	 * Get supported checks
	 *
	 * @return array List of supported check names.
	 */
	public function get_supported_checks(): array {
		return self::SUPPORTED_CHECKS;
	}
}
