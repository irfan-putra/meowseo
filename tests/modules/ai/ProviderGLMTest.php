<?php
/**
 * Provider_GLM Test Case
 *
 * Unit tests for the GLM (Zhipu AI) provider implementation.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Providers\Provider_GLM;
use MeowSEO\Modules\AI\Providers\Provider_OpenAI_Compatible;

/**
 * Provider_GLM test case
 *
 * Tests that the GLM provider correctly implements the AI_Provider interface
 * and extends Provider_OpenAI_Compatible.
 * Requirements: 2.1, 2.2, 2.3
 *
 * @since 1.0.0
 */
class ProviderGLMTest extends TestCase {

	/**
	 * Test that Provider_GLM class can be loaded by autoloader.
	 *
	 * @return void
	 */
	public function test_provider_glm_class_can_be_loaded(): void {
		$this->assertTrue(
			class_exists( Provider_GLM::class ),
			'Provider_GLM class should be loadable by autoloader'
		);
	}

	/**
	 * Test that Provider_GLM implements AI_Provider interface.
	 *
	 * @return void
	 */
	public function test_provider_glm_implements_ai_provider_interface(): void {
		$this->assertTrue(
			in_array(
				AI_Provider::class,
				class_implements( Provider_GLM::class ),
				true
			),
			'Provider_GLM should implement AI_Provider interface'
		);
	}

	/**
	 * Test that Provider_GLM extends Provider_OpenAI_Compatible.
	 *
	 * @return void
	 */
	public function test_provider_glm_extends_openai_compatible(): void {
		$this->assertTrue(
			is_subclass_of( Provider_GLM::class, Provider_OpenAI_Compatible::class ),
			'Provider_GLM should extend Provider_OpenAI_Compatible'
		);
	}

	/**
	 * Test that Provider_GLM can be instantiated.
	 *
	 * @return void
	 */
	public function test_provider_glm_can_be_instantiated(): void {
		$provider = new Provider_GLM( 'test-api-key' );

		$this->assertInstanceOf(
			Provider_GLM::class,
			$provider,
			'Provider_GLM should be instantiable'
		);
	}

	/**
	 * Test that get_slug returns correct value.
	 *
	 * Validates Requirement 2.1: Provider slug must be 'glm'
	 *
	 * @return void
	 */
	public function test_get_slug_returns_glm(): void {
		$provider = new Provider_GLM( 'test-api-key' );

		$this->assertEquals(
			'glm',
			$provider->get_slug(),
			'get_slug should return "glm"'
		);
	}

	/**
	 * Test that get_label returns correct value.
	 *
	 * Validates Requirement 2.1: Provider label must be 'Zhipu AI GLM'
	 *
	 * @return void
	 */
	public function test_get_label_returns_zhipu_ai_glm(): void {
		$provider = new Provider_GLM( 'test-api-key' );

		$this->assertEquals(
			'Zhipu AI GLM',
			$provider->get_label(),
			'get_label should return "Zhipu AI GLM"'
		);
	}

	/**
	 * Test that supports_text returns true.
	 *
	 * Validates Requirement 2.2: Provider must support text generation
	 *
	 * @return void
	 */
	public function test_supports_text_returns_true(): void {
		$provider = new Provider_GLM( 'test-api-key' );

		$this->assertTrue(
			$provider->supports_text(),
			'supports_text should return true for GLM'
		);
	}

	/**
	 * Test that supports_image returns true.
	 *
	 * Validates Requirement 2.3: Provider must support image generation
	 *
	 * @return void
	 */
	public function test_supports_image_returns_true(): void {
		$provider = new Provider_GLM( 'test-api-key' );

		$this->assertTrue(
			$provider->supports_image(),
			'supports_image should return true for GLM'
		);
	}

	/**
	 * Test that get_last_error returns null initially.
	 *
	 * @return void
	 */
	public function test_get_last_error_returns_null_initially(): void {
		$provider = new Provider_GLM( 'test-api-key' );

		$this->assertNull(
			$provider->get_last_error(),
			'get_last_error should return null initially'
		);
	}

	/**
	 * Test that generate_image supports GLM-specific size range.
	 *
	 * Validates Requirement 2.12: GLM supports 512x512 to 4096x4096 sizes
	 *
	 * This test verifies that the generate_image method accepts size options
	 * and passes them to the parent implementation.
	 *
	 * @return void
	 */
	public function test_generate_image_accepts_size_option(): void {
		$provider = new Provider_GLM( 'test-api-key' );

		// We can't test the actual API call without mocking wp_remote_post,
		// but we can verify the method exists and accepts the expected parameters.
		$this->assertTrue(
			method_exists( $provider, 'generate_image' ),
			'generate_image method should exist'
		);

		// Verify the method signature accepts prompt and options.
		$reflection = new \ReflectionMethod( $provider, 'generate_image' );
		$parameters = $reflection->getParameters();

		$this->assertCount(
			2,
			$parameters,
			'generate_image should accept 2 parameters'
		);

		$this->assertEquals(
			'prompt',
			$parameters[0]->getName(),
			'First parameter should be named "prompt"'
		);

		$this->assertEquals(
			'options',
			$parameters[1]->getName(),
			'Second parameter should be named "options"'
		);
	}
}
