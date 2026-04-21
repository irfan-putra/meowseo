<?php
/**
 * Error Aggregation Verification Test
 *
 * Verifies that when all providers fail, the system returns a WP_Error
 * with aggregated errors and actionable guidance.
 *
 * Task 12.2: Verify aggregated error responses
 * Requirements: 7.2, 7.6
 *
 * @package MeowSEO\Tests\Modules\AI
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Options;
use WP_Error;

/**
 * Class ErrorAggregationVerificationTest
 *
 * Tests error aggregation when all providers fail.
 *
 * @since 1.0.0
 */
class ErrorAggregationVerificationTest extends TestCase {

	/**
	 * Provider Manager instance.
	 *
	 * @var AI_Provider_Manager
	 */
	private AI_Provider_Manager $manager;

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Clear any existing API keys.
		delete_option( 'meowseo_ai_gemini_api_key' );
		delete_option( 'meowseo_ai_openai_api_key' );
		delete_option( 'meowseo_ai_anthropic_api_key' );
		delete_option( 'meowseo_ai_deepseek_api_key' );
		delete_option( 'meowseo_ai_glm_api_key' );
		delete_option( 'meowseo_ai_qwen_api_key' );
		delete_option( 'meowseo_ai_imagen_api_key' );
		delete_option( 'meowseo_ai_dalle_api_key' );

		// Clear cache.
		wp_cache_flush();

		$this->options = $this->createMock( Options::class );
		$this->options->method( 'get' )->willReturn( [] );

		$this->manager = new AI_Provider_Manager( $this->options );
	}

	/**
	 * Test that generate_text returns WP_Error when no providers are configured.
	 *
	 * Requirement 7.2: WP_Error includes all provider errors when all fail.
	 *
	 * @return void
	 */
	public function test_generate_text_no_providers_returns_wp_error(): void {
		$result = $this->manager->generate_text( 'Test prompt' );

		$this->assertInstanceOf(
			WP_Error::class,
			$result,
			'generate_text should return WP_Error when no providers are configured'
		);
	}

	/**
	 * Test that generate_text error includes correct error code.
	 *
	 * @return void
	 */
	public function test_generate_text_error_has_correct_code(): void {
		$result = $this->manager->generate_text( 'Test prompt' );

		$this->assertEquals(
			'no_providers',
			$result->get_error_code(),
			'Error code should be "no_providers" when no providers are configured'
		);
	}

	/**
	 * Test that generate_text error message includes actionable guidance.
	 *
	 * Requirement 7.6: Error messages include actionable guidance for common issues.
	 *
	 * @return void
	 */
	public function test_generate_text_error_includes_actionable_guidance(): void {
		$result = $this->manager->generate_text( 'Test prompt' );

		$message = $result->get_error_message();

		// Should mention that no providers are configured.
		$this->assertStringContainsString(
			'No AI providers configured',
			$message,
			'Error message should explain that no providers are configured'
		);

		// Should include actionable guidance about adding API keys.
		$this->assertStringContainsString(
			'add API keys in settings',
			$message,
			'Error message should include actionable guidance about adding API keys'
		);
	}

	/**
	 * Test that WP_Error includes aggregated errors in data.
	 *
	 * Requirement 7.2: WP_Error includes all provider errors when all fail.
	 *
	 * @return void
	 */
	public function test_wp_error_includes_errors_array_in_data(): void {
		$result = $this->manager->generate_text( 'Test prompt' );

		$error_data = $result->get_error_data();

		$this->assertIsArray(
			$error_data,
			'Error data should be an array'
		);

		$this->assertArrayHasKey(
			'errors',
			$error_data,
			'Error data should include "errors" key for aggregated errors'
		);

		$this->assertIsArray(
			$error_data['errors'],
			'Errors should be an array'
		);
	}

	/**
	 * Test that generate_image returns WP_Error when no providers are configured.
	 *
	 * Requirement 7.2: WP_Error includes all provider errors when all fail.
	 *
	 * @return void
	 */
	public function test_generate_image_no_providers_returns_wp_error(): void {
		$result = $this->manager->generate_image( 'Test prompt' );

		$this->assertInstanceOf(
			WP_Error::class,
			$result,
			'generate_image should return WP_Error when no providers are configured'
		);
	}

	/**
	 * Test that generate_image error includes correct error code.
	 *
	 * @return void
	 */
	public function test_generate_image_error_has_correct_code(): void {
		$result = $this->manager->generate_image( 'Test prompt' );

		$this->assertEquals(
			'no_image_providers',
			$result->get_error_code(),
			'Error code should be "no_image_providers" when no image providers are configured'
		);
	}

	/**
	 * Test that generate_image error message includes actionable guidance.
	 *
	 * Requirement 7.6: Error messages include actionable guidance for common issues.
	 *
	 * @return void
	 */
	public function test_generate_image_error_includes_actionable_guidance(): void {
		$result = $this->manager->generate_image( 'Test prompt' );

		$message = $result->get_error_message();

		// Should mention that no image providers are configured.
		$this->assertStringContainsString(
			'No image providers configured',
			$message,
			'Error message should explain that no image providers are configured'
		);

		// Should include actionable guidance about adding API keys.
		$this->assertTrue(
			strpos( $message, 'add API keys' ) !== false || strpos( $message, 'check your API keys' ) !== false,
			'Error message should include actionable guidance about API keys'
		);
	}

	/**
	 * Test that generate_image WP_Error includes aggregated errors in data.
	 *
	 * Requirement 7.2: WP_Error includes all provider errors when all fail.
	 *
	 * @return void
	 */
	public function test_generate_image_wp_error_includes_errors_array(): void {
		$result = $this->manager->generate_image( 'Test prompt' );

		$error_data = $result->get_error_data();

		$this->assertIsArray(
			$error_data,
			'Error data should be an array'
		);

		$this->assertArrayHasKey(
			'errors',
			$error_data,
			'Error data should include "errors" key for aggregated errors'
		);

		$this->assertIsArray(
			$error_data['errors'],
			'Errors should be an array'
		);
	}

	/**
	 * Test that all new provider slugs are included in provider statuses.
	 *
	 * Verifies that deepseek, glm, and qwen are recognized by the system.
	 *
	 * @return void
	 */
	public function test_new_provider_slugs_in_statuses(): void {
		$statuses = $this->manager->get_provider_statuses();

		// Verify new providers are included.
		$this->assertArrayHasKey(
			'deepseek',
			$statuses,
			'Provider statuses should include deepseek'
		);

		$this->assertArrayHasKey(
			'glm',
			$statuses,
			'Provider statuses should include glm'
		);

		$this->assertArrayHasKey(
			'qwen',
			$statuses,
			'Provider statuses should include qwen'
		);
	}

	/**
	 * Test that new provider labels are correct.
	 *
	 * @return void
	 */
	public function test_new_provider_labels(): void {
		$statuses = $this->manager->get_provider_statuses();

		$this->assertEquals(
			'DeepSeek',
			$statuses['deepseek']['label'],
			'DeepSeek label should be correct'
		);

		$this->assertEquals(
			'Zhipu AI GLM',
			$statuses['glm']['label'],
			'GLM label should be correct'
		);

		$this->assertEquals(
			'Alibaba Qwen',
			$statuses['qwen']['label'],
			'Qwen label should be correct'
		);
	}

	/**
	 * Test that new providers support both text and image generation.
	 *
	 * @return void
	 */
	public function test_new_providers_support_text_and_image(): void {
		$statuses = $this->manager->get_provider_statuses();

		// DeepSeek.
		$this->assertTrue(
			$statuses['deepseek']['supports_text'],
			'DeepSeek should support text generation'
		);
		$this->assertTrue(
			$statuses['deepseek']['supports_image'],
			'DeepSeek should support image generation'
		);

		// GLM.
		$this->assertTrue(
			$statuses['glm']['supports_text'],
			'GLM should support text generation'
		);
		$this->assertTrue(
			$statuses['glm']['supports_image'],
			'GLM should support image generation'
		);

		// Qwen.
		$this->assertTrue(
			$statuses['qwen']['supports_text'],
			'Qwen should support text generation'
		);
		$this->assertTrue(
			$statuses['qwen']['supports_image'],
			'Qwen should support image generation'
		);
	}

	/**
	 * Test that Gemini now supports image generation.
	 *
	 * Verifies that Gemini's supports_image capability is true after Nano Banana 2 update.
	 *
	 * @return void
	 */
	public function test_gemini_supports_image_after_update(): void {
		$statuses = $this->manager->get_provider_statuses();

		$this->assertArrayHasKey(
			'gemini',
			$statuses,
			'Provider statuses should include gemini'
		);

		$this->assertTrue(
			$statuses['gemini']['supports_image'],
			'Gemini should support image generation after Nano Banana 2 update'
		);
	}

	/**
	 * Test that error aggregation structure is consistent.
	 *
	 * Verifies that the errors array in WP_Error data follows the expected structure.
	 *
	 * @return void
	 */
	public function test_error_aggregation_structure_is_consistent(): void {
		// Test text generation error structure.
		$text_result = $this->manager->generate_text( 'Test prompt' );
		$text_error_data = $text_result->get_error_data();

		$this->assertIsArray(
			$text_error_data,
			'Text error data should be an array'
		);

		$this->assertArrayHasKey(
			'errors',
			$text_error_data,
			'Text error data should have "errors" key'
		);

		// Test image generation error structure.
		$image_result = $this->manager->generate_image( 'Test prompt' );
		$image_error_data = $image_result->get_error_data();

		$this->assertIsArray(
			$image_error_data,
			'Image error data should be an array'
		);

		$this->assertArrayHasKey(
			'errors',
			$image_error_data,
			'Image error data should have "errors" key'
		);
	}

	/**
	 * Test that error messages are user-friendly.
	 *
	 * Requirement 7.6: Error messages include actionable guidance.
	 *
	 * @return void
	 */
	public function test_error_messages_are_user_friendly(): void {
		$text_result = $this->manager->generate_text( 'Test prompt' );
		$text_message = $text_result->get_error_message();

		// Should not contain technical jargon or code.
		$this->assertStringNotContainsString(
			'null',
			$text_message,
			'Error message should not contain technical terms like "null"'
		);

		$this->assertStringNotContainsString(
			'array',
			$text_message,
			'Error message should not contain technical terms like "array"'
		);

		// Should be a complete sentence.
		$this->assertMatchesRegularExpression(
			'/^[A-Z].*\.$/',
			$text_message,
			'Error message should be a complete sentence starting with capital letter and ending with period'
		);
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Clear any test data.
		delete_option( 'meowseo_ai_gemini_api_key' );
		delete_option( 'meowseo_ai_openai_api_key' );
		delete_option( 'meowseo_ai_anthropic_api_key' );
		delete_option( 'meowseo_ai_deepseek_api_key' );
		delete_option( 'meowseo_ai_glm_api_key' );
		delete_option( 'meowseo_ai_qwen_api_key' );
		delete_option( 'meowseo_ai_imagen_api_key' );
		delete_option( 'meowseo_ai_dalle_api_key' );

		wp_cache_flush();

		parent::tearDown();
	}
}
