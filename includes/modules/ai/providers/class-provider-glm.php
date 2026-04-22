<?php
/**
 * GLM (Zhipu AI) Provider.
 *
 * Provides integration with Zhipu AI's GLM models for text and image generation.
 *
 * @package MeowSEO\Modules\AI\Providers
 */

namespace MeowSEO\Modules\AI\Providers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Provider_GLM
 *
 * GLM provider implementation using OpenAI-compatible API format.
 *
 * Zhipu AI offers GLM series models with flexible pricing including free tiers:
 * - Text: GLM-4.7-flash (glm-4.7-flash) - 128K context window, free tier available
 * - Image: GLM-Image (glm-image) - 16B parameter model with exceptional text rendering
 *
 * API Documentation: https://open.bigmodel.cn/dev/api
 *
 * @since 1.0.0
 */
class Provider_GLM extends Provider_OpenAI_Compatible {

	/**
	 * GLM API base URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const API_BASE_URL = 'https://api.z.ai/api/paas/v4';

	/**
	 * GLM image generation API URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const IMAGE_API_URL = 'https://api.z.ai/api/paas/v4/images/generations';

	/**
	 * Default text model (GLM-4.7-flash).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_TEXT_MODEL = 'glm-4.7-flash';

	/**
	 * Default image model (GLM-Image 16B).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_IMAGE_MODEL = 'glm-image';

	/**
	 * Get the provider slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider slug 'glm'.
	 */
	public function get_slug(): string {
		return 'glm';
	}

	/**
	 * Get the provider label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Provider label 'Zhipu AI GLM'.
	 */
	public function get_label(): string {
		return 'Zhipu AI GLM';
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
	 * @return string The text model identifier 'glm-4.7-flash'.
	 */
	protected function get_text_model(): string {
		return self::DEFAULT_TEXT_MODEL;
	}

	/**
	 * Get the default image model for generation.
	 *
	 * @since 1.0.0
	 *
	 * @return string The image model identifier 'glm-image'.
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
	 * Generate image with GLM-specific size support.
	 *
	 * GLM supports image sizes from 512x512 to 4096x4096 pixels.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prompt Image generation prompt describing the desired image.
	 * @param array  $options {
	 *     Optional. Provider-specific options.
	 *
	 *     @type string $model Model to use. Default 'glm-image'.
	 *     @type string $size  Image dimensions (512x512 to 4096x4096). Default '1024x1024'.
	 * }
	 * @return array {
	 *     Generated image data.
	 *
	 *     @type string $url            URL of the generated image.
	 *     @type string $revised_prompt The actual prompt used by the provider (if revised).
	 * }
	 * @throws Provider_Exception When generation fails.
	 * @throws Provider_Rate_Limit_Exception When rate limited (HTTP 429).
	 * @throws Provider_Auth_Exception When authentication fails (HTTP 401/403).
	 */
	public function generate_image( string $prompt, array $options = [] ): array {
		// GLM supports 512x512 to 4096x4096.
		$options['size'] = $options['size'] ?? '1024x1024';
		return parent::generate_image( $prompt, $options );
	}
}
