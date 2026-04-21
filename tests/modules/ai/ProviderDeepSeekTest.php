<?php
/**
 * Provider_DeepSeek Test Case
 *
 * Unit tests for the DeepSeek AI provider implementation.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Providers\Provider_DeepSeek;
use MeowSEO\Modules\AI\Providers\Provider_OpenAI_Compatible;

/**
 * Provider_DeepSeek test case
 *
 * Tests that the DeepSeek provider correctly implements the AI_Provider interface
 * and extends Provider_OpenAI_Compatible.
 * Requirements: 1.1, 1.2, 1.3
 *
 * @since 1.0.0
 */
class ProviderDeepSeekTest extends TestCase {

	/**
	 * Test that Provider_DeepSeek class can be loaded by autoloader.
	 *
	 * @return void
	 */
	public function test_provider_deepseek_class_can_be_loaded(): void {
		$this->assertTrue(
			class_exists( Provider_DeepSeek::class ),
			'Provider_DeepSeek class should be loadable by autoloader'
		);
	}

	/**
	 * Test that Provider_DeepSeek implements AI_Provider interface.
	 *
	 * @return void
	 */
	public function test_provider_deepseek_implements_ai_provider_interface(): void {
		$this->assertTrue(
			in_array(
				AI_Provider::class,
				class_implements( Provider_DeepSeek::class ),
				true
			),
			'Provider_DeepSeek should implement AI_Provider interface'
		);
	}

	/**
	 * Test that Provider_DeepSeek extends Provider_OpenAI_Compatible.
	 *
	 * @return void
	 */
	public function test_provider_deepseek_extends_openai_compatible(): void {
		$this->assertTrue(
			is_subclass_of( Provider_DeepSeek::class, Provider_OpenAI_Compatible::class ),
			'Provider_DeepSeek should extend Provider_OpenAI_Compatible'
		);
	}

	/**
	 * Test that Provider_DeepSeek can be instantiated.
	 *
	 * @return void
	 */
	public function test_provider_deepseek_can_be_instantiated(): void {
		$provider = new Provider_DeepSeek( 'test-api-key' );

		$this->assertInstanceOf(
			Provider_DeepSeek::class,
			$provider,
			'Provider_DeepSeek should be instantiable'
		);
	}

	/**
	 * Test that get_slug returns correct value.
	 *
	 * Validates Requirement 1.1: Provider slug must be 'deepseek'
	 *
	 * @return void
	 */
	public function test_get_slug_returns_deepseek(): void {
		$provider = new Provider_DeepSeek( 'test-api-key' );

		$this->assertEquals(
			'deepseek',
			$provider->get_slug(),
			'get_slug should return "deepseek"'
		);
	}

	/**
	 * Test that get_label returns correct value.
	 *
	 * Validates Requirement 1.1: Provider label must be 'DeepSeek'
	 *
	 * @return void
	 */
	public function test_get_label_returns_deepseek(): void {
		$provider = new Provider_DeepSeek( 'test-api-key' );

		$this->assertEquals(
			'DeepSeek',
			$provider->get_label(),
			'get_label should return "DeepSeek"'
		);
	}

	/**
	 * Test that supports_text returns true.
	 *
	 * Validates Requirement 1.2: Provider must support text generation
	 *
	 * @return void
	 */
	public function test_supports_text_returns_true(): void {
		$provider = new Provider_DeepSeek( 'test-api-key' );

		$this->assertTrue(
			$provider->supports_text(),
			'supports_text should return true for DeepSeek'
		);
	}

	/**
	 * Test that supports_image returns true.
	 *
	 * Validates Requirement 1.3: Provider must support image generation
	 *
	 * @return void
	 */
	public function test_supports_image_returns_true(): void {
		$provider = new Provider_DeepSeek( 'test-api-key' );

		$this->assertTrue(
			$provider->supports_image(),
			'supports_image should return true for DeepSeek'
		);
	}

	/**
	 * Test that get_last_error returns null initially.
	 *
	 * @return void
	 */
	public function test_get_last_error_returns_null_initially(): void {
		$provider = new Provider_DeepSeek( 'test-api-key' );

		$this->assertNull(
			$provider->get_last_error(),
			'get_last_error should return null initially'
		);
	}
}
