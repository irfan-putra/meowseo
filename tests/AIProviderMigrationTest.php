<?php
/**
 * Test AI Provider Migration Behavior
 *
 * Tests for Requirements 8.2-8.3:
 * - New providers appended to order if not present
 * - Providers without API keys are not instantiated
 *
 * @package MeowSEO
 * @subpackage Tests
 */

namespace MeowSEO\Tests;

use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Modules\AI\AI_Settings;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Test AI Provider Migration Behavior
 */
class AIProviderMigrationTest extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Provider Manager instance
	 *
	 * @var AI_Provider_Manager
	 */
	private AI_Provider_Manager $provider_manager;

	/**
	 * AI Settings instance
	 *
	 * @var AI_Settings
	 */
	private AI_Settings $settings;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset global storage
		global $wp_options_storage, $wp_cache_storage;
		$wp_options_storage = [];
		$wp_cache_storage = [];

		// Initialize Options
		$this->options = new Options();

		// Initialize Provider Manager
		$this->provider_manager = new AI_Provider_Manager( $this->options );

		// Initialize AI Settings
		$this->settings = new AI_Settings( $this->options, $this->provider_manager );
	}

	/**
	 * Test that new providers are appended to order if not present
	 *
	 * Requirement 8.2: When the 'ai_provider_order' option does not include new providers,
	 * THE Provider_Manager SHALL append new providers at the end of the order
	 *
	 * @return void
	 */
	public function test_new_providers_appended_to_order_if_not_present(): void {
		// Simulate old provider order (before new providers were added)
		$old_order = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];
		update_option( 'meowseo_ai_provider_order', $old_order );

		// Sanitize the order (this is what happens when settings are saved)
		$sanitized_order = $this->settings->sanitize_provider_order( $old_order );

		// Verify new providers are appended
		$this->assertContains( 'deepseek', $sanitized_order, 'DeepSeek should be appended to order' );
		$this->assertContains( 'glm', $sanitized_order, 'GLM should be appended to order' );
		$this->assertContains( 'qwen', $sanitized_order, 'Qwen should be appended to order' );

		// Verify old providers are preserved
		$this->assertContains( 'gemini', $sanitized_order, 'Gemini should be preserved' );
		$this->assertContains( 'openai', $sanitized_order, 'OpenAI should be preserved' );
		$this->assertContains( 'anthropic', $sanitized_order, 'Anthropic should be preserved' );
		$this->assertContains( 'imagen', $sanitized_order, 'Imagen should be preserved' );
		$this->assertContains( 'dalle', $sanitized_order, 'DALL-E should be preserved' );

		// Verify order is preserved for existing providers
		$this->assertEquals( 'gemini', $sanitized_order[0], 'Gemini should be first' );
		$this->assertEquals( 'openai', $sanitized_order[1], 'OpenAI should be second' );
		$this->assertEquals( 'anthropic', $sanitized_order[2], 'Anthropic should be third' );
		$this->assertEquals( 'imagen', $sanitized_order[3], 'Imagen should be fourth' );
		$this->assertEquals( 'dalle', $sanitized_order[4], 'DALL-E should be fifth' );

		// Verify new providers are at the end
		$deepseek_index = array_search( 'deepseek', $sanitized_order, true );
		$glm_index = array_search( 'glm', $sanitized_order, true );
		$qwen_index = array_search( 'qwen', $sanitized_order, true );

		$this->assertGreaterThan( 4, $deepseek_index, 'DeepSeek should be after existing providers' );
		$this->assertGreaterThan( 4, $glm_index, 'GLM should be after existing providers' );
		$this->assertGreaterThan( 4, $qwen_index, 'Qwen should be after existing providers' );
	}

	/**
	 * Test that providers without API keys are not instantiated
	 *
	 * Requirement 8.3: When no API key is configured for a new provider,
	 * THE Provider_Manager SHALL NOT attempt to instantiate that provider
	 *
	 * @return void
	 */
	public function test_providers_without_api_keys_not_instantiated(): void {
		// Set up scenario: only Gemini has an API key
		update_option( 'meowseo_ai_gemini_api_key', $this->encrypt_test_key( 'test-gemini-key' ) );

		// Ensure new providers do NOT have API keys
		delete_option( 'meowseo_ai_deepseek_api_key' );
		delete_option( 'meowseo_ai_glm_api_key' );
		delete_option( 'meowseo_ai_qwen_api_key' );

		// Create a new Provider Manager instance (simulates plugin load)
		$manager = new AI_Provider_Manager( $this->options );

		// Get loaded providers
		$loaded_providers = $manager->get_providers();

		// Verify only Gemini is instantiated
		$this->assertArrayHasKey( 'gemini', $loaded_providers, 'Gemini should be instantiated (has API key)' );

		// Verify new providers are NOT instantiated
		$this->assertArrayNotHasKey( 'deepseek', $loaded_providers, 'DeepSeek should NOT be instantiated (no API key)' );
		$this->assertArrayNotHasKey( 'glm', $loaded_providers, 'GLM should NOT be instantiated (no API key)' );
		$this->assertArrayNotHasKey( 'qwen', $loaded_providers, 'Qwen should NOT be instantiated (no API key)' );
	}

	/**
	 * Test that provider statuses include providers without API keys
	 *
	 * Requirement 8.3: Provider statuses should show all providers,
	 * but has_api_key should be false for providers without keys
	 *
	 * @return void
	 */
	public function test_provider_statuses_include_providers_without_keys(): void {
		// Set up scenario: only Gemini has an API key
		update_option( 'meowseo_ai_gemini_api_key', $this->encrypt_test_key( 'test-gemini-key' ) );

		// Ensure new providers do NOT have API keys
		delete_option( 'meowseo_ai_deepseek_api_key' );
		delete_option( 'meowseo_ai_glm_api_key' );
		delete_option( 'meowseo_ai_qwen_api_key' );

		// Create a new Provider Manager instance
		$manager = new AI_Provider_Manager( $this->options );

		// Get provider statuses
		$statuses = $manager->get_provider_statuses();

		// Verify all providers are in statuses
		$this->assertArrayHasKey( 'gemini', $statuses, 'Gemini should be in statuses' );
		$this->assertArrayHasKey( 'deepseek', $statuses, 'DeepSeek should be in statuses' );
		$this->assertArrayHasKey( 'glm', $statuses, 'GLM should be in statuses' );
		$this->assertArrayHasKey( 'qwen', $statuses, 'Qwen should be in statuses' );

		// Verify has_api_key flag
		$this->assertTrue( $statuses['gemini']['has_api_key'], 'Gemini should have API key' );
		$this->assertFalse( $statuses['deepseek']['has_api_key'], 'DeepSeek should NOT have API key' );
		$this->assertFalse( $statuses['glm']['has_api_key'], 'GLM should NOT have API key' );
		$this->assertFalse( $statuses['qwen']['has_api_key'], 'Qwen should NOT have API key' );

		// Verify labels are correct
		$this->assertEquals( 'Google Gemini', $statuses['gemini']['label'], 'Gemini label should be correct' );
		$this->assertEquals( 'DeepSeek', $statuses['deepseek']['label'], 'DeepSeek label should be correct' );
		$this->assertEquals( 'Zhipu AI GLM', $statuses['glm']['label'], 'GLM label should be correct' );
		$this->assertEquals( 'Alibaba Qwen', $statuses['qwen']['label'], 'Qwen label should be correct' );

		// Verify capabilities are correct
		$this->assertTrue( $statuses['deepseek']['supports_text'], 'DeepSeek should support text' );
		$this->assertTrue( $statuses['deepseek']['supports_image'], 'DeepSeek should support image' );
		$this->assertTrue( $statuses['glm']['supports_text'], 'GLM should support text' );
		$this->assertTrue( $statuses['glm']['supports_image'], 'GLM should support image' );
		$this->assertTrue( $statuses['qwen']['supports_text'], 'Qwen should support text' );
		$this->assertTrue( $statuses['qwen']['supports_image'], 'Qwen should support image' );
	}

	/**
	 * Test that migration works with empty provider order
	 *
	 * @return void
	 */
	public function test_migration_with_empty_provider_order(): void {
		// Simulate empty provider order (fresh install)
		update_option( 'meowseo_ai_provider_order', [] );

		// Sanitize the order
		$sanitized_order = $this->settings->sanitize_provider_order( [] );

		// Verify all providers are added
		$expected_providers = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle', 'deepseek', 'glm', 'qwen' ];
		$this->assertCount( count( $expected_providers ), $sanitized_order, 'All providers should be added' );

		foreach ( $expected_providers as $provider ) {
			$this->assertContains( $provider, $sanitized_order, "{$provider} should be in order" );
		}
	}

	/**
	 * Test that migration preserves custom order
	 *
	 * @return void
	 */
	public function test_migration_preserves_custom_order(): void {
		// Simulate custom provider order (user reordered providers)
		$custom_order = [ 'anthropic', 'gemini', 'openai', 'dalle', 'imagen' ];
		update_option( 'meowseo_ai_provider_order', $custom_order );

		// Sanitize the order
		$sanitized_order = $this->settings->sanitize_provider_order( $custom_order );

		// Verify custom order is preserved
		$this->assertEquals( 'anthropic', $sanitized_order[0], 'Anthropic should be first (custom order)' );
		$this->assertEquals( 'gemini', $sanitized_order[1], 'Gemini should be second (custom order)' );
		$this->assertEquals( 'openai', $sanitized_order[2], 'OpenAI should be third (custom order)' );
		$this->assertEquals( 'dalle', $sanitized_order[3], 'DALL-E should be fourth (custom order)' );
		$this->assertEquals( 'imagen', $sanitized_order[4], 'Imagen should be fifth (custom order)' );

		// Verify new providers are appended after custom order
		$deepseek_index = array_search( 'deepseek', $sanitized_order, true );
		$glm_index = array_search( 'glm', $sanitized_order, true );
		$qwen_index = array_search( 'qwen', $sanitized_order, true );

		$this->assertGreaterThan( 4, $deepseek_index, 'DeepSeek should be after custom ordered providers' );
		$this->assertGreaterThan( 4, $glm_index, 'GLM should be after custom ordered providers' );
		$this->assertGreaterThan( 4, $qwen_index, 'Qwen should be after custom ordered providers' );
	}

	/**
	 * Test that providers with API keys are instantiated
	 *
	 * @return void
	 */
	public function test_providers_with_api_keys_are_instantiated(): void {
		// Set up scenario: all new providers have API keys
		update_option( 'meowseo_ai_deepseek_api_key', $this->encrypt_test_key( 'test-deepseek-key' ) );
		update_option( 'meowseo_ai_glm_api_key', $this->encrypt_test_key( 'test-glm-key' ) );
		update_option( 'meowseo_ai_qwen_api_key', $this->encrypt_test_key( 'test-qwen-key' ) );

		// Create a new Provider Manager instance
		$manager = new AI_Provider_Manager( $this->options );

		// Get loaded providers
		$loaded_providers = $manager->get_providers();

		// Verify new providers ARE instantiated
		$this->assertArrayHasKey( 'deepseek', $loaded_providers, 'DeepSeek should be instantiated (has API key)' );
		$this->assertArrayHasKey( 'glm', $loaded_providers, 'GLM should be instantiated (has API key)' );
		$this->assertArrayHasKey( 'qwen', $loaded_providers, 'Qwen should be instantiated (has API key)' );

		// Verify provider instances are correct type
		$this->assertEquals( 'deepseek', $loaded_providers['deepseek']->get_slug(), 'DeepSeek slug should be correct' );
		$this->assertEquals( 'glm', $loaded_providers['glm']->get_slug(), 'GLM slug should be correct' );
		$this->assertEquals( 'qwen', $loaded_providers['qwen']->get_slug(), 'Qwen slug should be correct' );
	}

	/**
	 * Test that invalid provider slugs are filtered out
	 *
	 * @return void
	 */
	public function test_invalid_provider_slugs_filtered_out(): void {
		// Simulate provider order with invalid slugs
		$order_with_invalid = [ 'gemini', 'invalid-provider', 'openai', 'another-invalid' ];

		// Sanitize the order
		$sanitized_order = $this->settings->sanitize_provider_order( $order_with_invalid );

		// Verify invalid slugs are removed
		$this->assertNotContains( 'invalid-provider', $sanitized_order, 'Invalid provider should be removed' );
		$this->assertNotContains( 'another-invalid', $sanitized_order, 'Another invalid provider should be removed' );

		// Verify valid slugs are preserved
		$this->assertContains( 'gemini', $sanitized_order, 'Gemini should be preserved' );
		$this->assertContains( 'openai', $sanitized_order, 'OpenAI should be preserved' );

		// Verify all valid slugs are present
		$expected_providers = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle', 'deepseek', 'glm', 'qwen' ];
		$this->assertCount( count( $expected_providers ), $sanitized_order, 'All valid providers should be present' );
	}

	/**
	 * Helper method to encrypt a test API key
	 *
	 * @param string $key The API key to encrypt.
	 * @return string Encrypted API key.
	 */
	private function encrypt_test_key( string $key ): string {
		return $this->provider_manager->encrypt_key( $key );
	}
}
