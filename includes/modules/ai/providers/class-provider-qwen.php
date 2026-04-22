<?php
/**
 * Qwen (Alibaba) AI Provider.
 *
 * Provides integration with Alibaba's Qwen models for text and image generation.
 *
 * @package MeowSEO\Modules\AI\Providers
 */

namespace MeowSEO\Modules\AI\Providers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Provider_Qwen
 *
 * Qwen provider implementation using OpenAI-compatible API format.
 *
 * Qwen (Alibaba Cloud) offers high-performance multilingual models:
 * - Text: Qwen-Plus - 128K context window with strong Chinese/English support
 * - Image: Qwen-Image (20B) - Native Chinese/English text rendering, up to 3584x3584
 *
 * Note: Qwen uses X-DashScope-Authorization header instead of standard Authorization.
 *
 * API Documentation: https://help.aliyun.com/zh/model-studio/
 *
 * @since 1.0.0
 */
class Provider_Qwen extends Provider_OpenAI_Compatible {

	/**
	 * Qwen API base URL (DashScope compatible mode).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const API_BASE_URL = 'https://dashscope.aliyuncs.com/compatible-mode/v1';

	/**
	 * Qwen image generation API URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const IMAGE_API_URL = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis';

	/**
	 * Default text model (Qwen-Plus).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_TEXT_MODEL = 'qwen-plus';

	/**
	 * Default image model (Qwen-Image 20B).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_IMAGE_MODEL = 'qwen-image';

	/**
	 * Get the provider slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider slug 'qwen'.
	 */
	public function get_slug(): string {
		return 'qwen';
	}

	/**
	 * Get the provider label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider label 'Alibaba Qwen'.
	 */
	public function get_label(): string {
		return 'Alibaba Qwen';
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
	 * @return string The text model identifier 'qwen-plus'.
	 */
	protected function get_text_model(): string {
		return self::DEFAULT_TEXT_MODEL;
	}

	/**
	 * Get the default image model for generation.
	 *
	 * @since 1.0.0
	 *
	 * @return string The image model identifier 'qwen-image'.
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

	/**
	 * Get authentication headers for API requests.
	 *
	 * Qwen uses X-DashScope-Authorization header instead of standard Authorization.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array of header names to values.
	 */
	protected function get_auth_headers(): array {
		return [
			'Content-Type'              => 'application/json',
			'X-DashScope-Authorization' => 'Bearer ' . $this->api_key,
		];
	}
}
