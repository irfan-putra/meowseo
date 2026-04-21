<?php
/**
 * Provider_Qwen Test Case
 *
 * Unit tests for the Qwen (Alibaba) provider implementation.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Providers\Provider_Qwen;
use MeowSEO\Modules\AI\Providers\Provider_OpenAI_Compatible;

/**
 * Provider_Qwen test case
 *
 * Tests that the Qwen provider correctly implements the AI_Provider interface
 * and extends Provider_OpenAI_Compatible.
 * Requirements: 3.1, 3.2, 3.3
 *
 * @since 1.0.0
 */
class ProviderQwenTest extends TestCase {

	/**
	 * Test that Provider_Qwen class can be loaded by autoloader.
	 *
	 * @return void
	 */
	public function test_provider_qwen_class_can_be_loaded(): void {
		$this->assertTrue(
			class_exists( Provider_Qwen::class ),
			'Provider_Qwen class should be loadable by autoloader'
		);
	}

	/**
	 * Test that Provider_Qwen implements AI_Provider interface.
	 *
	 * @return void
	 */
	public function test_provider_qwen_implements_ai_provider_interface(): void {
		$this->assertTrue(
			in_array(
				AI_Provider::class,
				class_implements( Provider_Qwen::class ),
				true
			),
			'Provider_Qwen should implement AI_Provider interface'
		);
	}

	/**
	 * Test that Provider_Qwen extends Provider_OpenAI_Compatible.
	 *
	 * @return void
	 */
	public function test_provider_qwen_extends_openai_compatible(): void {
		$this->assertTrue(
			is_subclass_of( Provider_Qwen::class, Provider_OpenAI_Compatible::class ),
			'Provider_Qwen should extend Provider_OpenAI_Compatible'
		);
	}

	/**
	 * Test that Provider_Qwen can be instantiated.
	 *
	 * @return void
	 */
	public function test_provider_qwen_can_be_instantiated(): void {
		$provider = new Provider_Qwen( 'test-api-key' );

		$this->assertInstanceOf(
			Provider_Qwen::class,
			$provider,
			'Provider_Qwen should be instantiable'
		);
	}

	/**
	 * Test that get_slug returns correct value.
	 *
	 * Validates Requirement 3.1: Provider slug must be 'qwen'
	 *
	 * @return void
	 */
	public function test_get_slug_returns_qwen(): void {
		$provider = new Provider_Qwen( 'test-api-key' );

		$this->assertEquals(
			'qwen',
			$provider->get_slug(),
			'get_slug should return "qwen"'
		);
	}

	/**
	 * Test that get_label returns correct value.
	 *
	 * Validates Requirement 3.1: Provider label must be 'Alibaba Qwen'
	 *
	 * @return void
	 */
	public function test_get_label_returns_alibaba_qwen(): void {
		$provider = new Provider_Qwen( 'test-api-key' );

		$this->assertEquals(
			'Alibaba Qwen',
			$provider->get_label(),
			'get_label should return "Alibaba Qwen"'
		);
	}

	/**
	 * Test that supports_text returns true.
	 *
	 * Validates Requirement 3.2: Provider must support text generation
	 *
	 * @return void
	 */
	public function test_supports_text_returns_true(): void {
		$provider = new Provider_Qwen( 'test-api-key' );

		$this->assertTrue(
			$provider->supports_text(),
			'supports_text should return true for Qwen'
		);
	}

	/**
	 * Test that supports_image returns true.
	 *
	 * Validates Requirement 3.3: Provider must support image generation
	 *
	 * @return void
	 */
	public function test_supports_image_returns_true(): void {
		$provider = new Provider_Qwen( 'test-api-key' );

		$this->assertTrue(
			$provider->supports_image(),
			'supports_image should return true for Qwen'
		);
	}

	/**
	 * Test that get_last_error returns null initially.
	 *
	 * @return void
	 */
	public function test_get_last_error_returns_null_initially(): void {
		$provider = new Provider_Qwen( 'test-api-key' );

		$this->assertNull(
			$provider->get_last_error(),
			'get_last_error should return null initially'
		);
	}

	/**
	 * Test that get_auth_headers uses X-DashScope-Authorization header.
	 *
	 * Validates Requirement 3.6: Qwen uses X-DashScope-Authorization header
	 *
	 * This test verifies that the get_auth_headers method is overridden
	 * to use the DashScope-specific authentication header.
	 *
	 * @return void
	 */
	public function test_get_auth_headers_uses_dashscope_header(): void {
		$provider = new Provider_Qwen( 'test-api-key-123' );

		// Use reflection to access protected method
		$reflection = new \ReflectionMethod( $provider, 'get_auth_headers' );
		$reflection->setAccessible( true );
		$headers = $reflection->invoke( $provider );

		$this->assertIsArray(
			$headers,
			'get_auth_headers should return an array'
		);

		$this->assertArrayHasKey(
			'X-DashScope-Authorization',
			$headers,
			'Headers should include X-DashScope-Authorization'
		);

		$this->assertEquals(
			'Bearer test-api-key-123',
			$headers['X-DashScope-Authorization'],
			'X-DashScope-Authorization should contain Bearer token'
		);

		$this->assertArrayHasKey(
			'Content-Type',
			$headers,
			'Headers should include Content-Type'
		);

		$this->assertEquals(
			'application/json',
			$headers['Content-Type'],
			'Content-Type should be application/json'
		);
	}
}
