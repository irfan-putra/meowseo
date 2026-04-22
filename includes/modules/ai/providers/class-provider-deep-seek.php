<?php
/**
 * DeepSeek AI Provider.
 *
 * Provides integration with DeepSeek's AI models for text and image generation.
 *
 * @package MeowSEO\Modules\AI\Providers
 */

namespace MeowSEO\Modules\AI\Providers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Provider_DeepSeek
 *
 * DeepSeek provider implementation using OpenAI-compatible API format.
 *
 * DeepSeek offers frontier-level AI models at significantly reduced costs:
 * - Text: DeepSeek-V3.2 (deepseek-chat) - 128K context window
 * - Image: Janus-Pro-7B (janus-pro-7b) - Unified multimodal model
 *
 * API Documentation: https://api-docs.deepseek.com/
 *
 * @since 1.0.0
 */
class Provider_DeepSeek extends Provider_OpenAI_Compatible {

	/**
	 * DeepSeek API base URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const API_BASE_URL = 'https://api.deepseek.com/v1';

	/**
	 * DeepSeek image generation API URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const IMAGE_API_URL = 'https://api.deepseek.com/v1/images/generations';

	/**
	 * Default text model (DeepSeek-V3.2).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_TEXT_MODEL = 'deepseek-chat';

	/**
	 * Default image model (Janus-Pro-7B).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_IMAGE_MODEL = 'janus-pro-7b';

	/**
	 * Get the provider slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider slug 'deepseek'.
	 */
	public function get_slug(): string {
		return 'deepseek';
	}

	/**
	 * Get the provider label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider label 'DeepSeek'.
	 */
	public function get_label(): string {
		return 'DeepSeek';
	}

	/**
	 * Get the API base URL for chat completions.
	 *
	 * @since 1.0.0
	 *
	 * @return string The API base URL.
	 */
	protected function get_api_base_url(): string {
		return self::API_BASE_URL;
	}

	/**
	 * Get the default text model for generation.
	 *
	 * @since 1.0.0
	 *
	 * @return string The text model identifier 'deepseek-chat'.
	 */
	protected function get_text_model(): string {
		return self::DEFAULT_TEXT_MODEL;
	}

	/**
	 * Get the default image model for generation.
	 *
	 * @since 1.0.0
	 *
	 * @return string The image model identifier 'janus-pro-7b'.
	 */
	protected function get_image_model(): string {
		return self::DEFAULT_IMAGE_MODEL;
	}

	/**
	 * Get the image generation API URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string The image API URL.
	 */
	protected function get_image_api_url(): string {
		return self::IMAGE_API_URL;
	}
}
