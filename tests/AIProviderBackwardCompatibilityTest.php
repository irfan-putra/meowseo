<?php
/**
 * AI Provider Backward Compatibility Tests
 *
 * Tests to verify that existing provider configurations remain functional
 * after adding new providers (DeepSeek, GLM, Qwen) and updating Gemini
 * with image support.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Options;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Modules\AI\Providers\Provider_Gemini;
use MeowSEO\Modules\AI\Providers\Provider_OpenAI;
use MeowSEO\Modules\AI\Providers\Provider_Anthropic;
use MeowSEO\Modules\AI\Providers\Provider_Imagen;
use MeowSEO\Modules\AI\Providers\Provider_Dalle;
use MeowSEO\Modules\AI\Providers\Provider_DeepSeek;
use MeowSEO\Modules\AI\Providers\Provider_GLM;
use MeowSEO\Modules\AI\Providers\Provider_Qwen;

/**
 * Test backward compatibility of AI provider configurations
 *
 * Validates Requirements 8.1, 8.4:
 * - Existing provider configurations remain unchanged
 * - Existing provider slugs continue to function without modification
 *
 * @since 1.0.0
 */
class AIProviderBackwardCompatibilityTest extends TestCase {

	/**
	 * Options instance for testing
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Create a mock Options instance
		$this->options = $this->createMock( Options::class );
		
		// Clear WordPress cache
		wp_cache_flush();
	}

	/**
	 * Test that existing API keys remain valid after update
	 *
	 * Validates Requirement 8.1: Existing provider configurations remain unchanged
	 *
	 * @return void
	 */
	public function testExistingApiKeysRemainValid(): void {
		global $wp_options_storage;
		
		// Simulate existing configuration with only old providers
		// Use encrypted keys that will decrypt properly
		$manager_temp = new AI_Provider_Manager( $this->options );
		$wp_options_storage['meowseo_ai_gemini_api_key'] = $manager_temp->encrypt_key( 'test_gemini_key' );
		$wp_options_storage['meowseo_ai_openai_api_key'] = $manager_temp->encrypt_key( 'test_openai_key' );
		$wp_options_storage['meowseo_ai_anthropic_api_key'] = $manager_temp->encrypt_key( 'test_anthropic_key' );
		$wp_options_storage['meowseo_ai_imagen_api_key'] = $manager_temp->encrypt_key( 'test_imagen_key' );
		$wp_options_storage['meowseo_ai_dalle_api_key'] = $manager_temp->encrypt_key( 'test_dalle_key' );

		// Mock Options for provider order and active providers
		$this->options->method( 'get' )
			->willReturnCallback( function ( $key, $default = null ) {
				// Return default provider order and active providers
				if ( 'ai_provider_order' === $key ) {
					return [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];
				}
				
				if ( 'ai_active_providers' === $key ) {
					return [ 'gemini', 'openai', 'anthropic' ];
				}
				
				return $default;
			} );

		$manager = new AI_Provider_Manager( $this->options );
		$statuses = $manager->get_provider_statuses();

		// Verify existing providers have API keys
		$this->assertTrue( $statuses['gemini']['has_api_key'], 'Gemini should have API key' );
		$this->assertTrue( $statuses['openai']['has_api_key'], 'OpenAI should have API key' );
		$this->assertTrue( $statuses['anthropic']['has_api_key'], 'Anthropic should have API key' );
		$this->assertTrue( $statuses['imagen']['has_api_key'], 'Imagen should have API key' );
		$this->assertTrue( $statuses['dalle']['has_api_key'], 'DALL-E should have API key' );

		// Verify new providers don't have API keys (not configured yet)
		$this->assertFalse( $statuses['deepseek']['has_api_key'], 'DeepSeek should not have API key' );
		$this->assertFalse( $statuses['glm']['has_api_key'], 'GLM should not have API key' );
		$this->assertFalse( $statuses['qwen']['has_api_key'], 'Qwen should not have API key' );
	}

	/**
	 * Test that existing provider order is preserved
	 *
	 * Validates Requirement 8.1: Existing provider configurations remain unchanged
	 *
	 * @return void
	 */
	public function testExistingProviderOrderIsPreserved(): void {
		// Simulate existing provider order (before new providers were added)
		$existing_order = [ 'openai', 'gemini', 'anthropic', 'dalle', 'imagen' ];

		$this->options->method( 'get' )
			->willReturnCallback( function ( $key, $default = null ) use ( $existing_order ) {
				if ( 'ai_provider_order' === $key ) {
					return $existing_order;
				}
				
				if ( 'ai_active_providers' === $key ) {
					return [ 'openai', 'gemini', 'anthropic' ];
				}
				
				return $default;
			} );

		$manager = new AI_Provider_Manager( $this->options );
		$statuses = $manager->get_provider_statuses();

		// Verify existing providers maintain their priority order
		$this->assertEquals( 0, $statuses['openai']['priority'], 'OpenAI should be priority 0' );
		$this->assertEquals( 1, $statuses['gemini']['priority'], 'Gemini should be priority 1' );
		$this->assertEquals( 2, $statuses['anthropic']['priority'], 'Anthropic should be priority 2' );
		$this->assertEquals( 3, $statuses['dalle']['priority'], 'DALL-E should be priority 3' );
		$this->assertEquals( 4, $statuses['imagen']['priority'], 'Imagen should be priority 4' );

		// New providers should have default priority (999) since they're not in the order
		$this->assertEquals( 999, $statuses['deepseek']['priority'], 'DeepSeek should have default priority' );
		$this->assertEquals( 999, $statuses['glm']['priority'], 'GLM should have default priority' );
		$this->assertEquals( 999, $statuses['qwen']['priority'], 'Qwen should have default priority' );
	}

	/**
	 * Test that existing active providers list is preserved
	 *
	 * Validates Requirement 8.1: Existing provider configurations remain unchanged
	 *
	 * @return void
	 */
	public function testExistingActiveProvidersListIsPreserved(): void {
		// Simulate existing active providers (subset of all providers)
		$existing_active = [ 'gemini', 'openai' ];

		$this->options->method( 'get' )
			->willReturnCallback( function ( $key, $default = null ) use ( $existing_active ) {
				if ( 'ai_active_providers' === $key ) {
					return $existing_active;
				}
				
				if ( 'ai_provider_order' === $key ) {
					return [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];
				}
				
				return $default;
			} );

		$manager = new AI_Provider_Manager( $this->options );
		$statuses = $manager->get_provider_statuses();

		// Verify existing active providers remain active
		$this->assertTrue( $statuses['gemini']['active'], 'Gemini should be active' );
		$this->assertTrue( $statuses['openai']['active'], 'OpenAI should be active' );

		// Verify existing inactive providers remain inactive
		$this->assertFalse( $statuses['anthropic']['active'], 'Anthropic should be inactive' );
		$this->assertFalse( $statuses['imagen']['active'], 'Imagen should be inactive' );
		$this->assertFalse( $statuses['dalle']['active'], 'DALL-E should be inactive' );

		// New providers should be inactive by default
		$this->assertFalse( $statuses['deepseek']['active'], 'DeepSeek should be inactive by default' );
		$this->assertFalse( $statuses['glm']['active'], 'GLM should be inactive by default' );
		$this->assertFalse( $statuses['qwen']['active'], 'Qwen should be inactive by default' );
	}

	/**
	 * Test that existing provider slugs continue to function
	 *
	 * Validates Requirement 8.4: Existing provider slugs continue to function without modification
	 *
	 * @return void
	 */
	public function testExistingProviderSlugsContinueToFunction(): void {
		global $wp_options_storage;
		
		// Set up API keys so we get labels from provider instances
		$manager_temp = new AI_Provider_Manager( $this->options );
		$wp_options_storage['meowseo_ai_gemini_api_key'] = $manager_temp->encrypt_key( 'test_gemini_key' );
		$wp_options_storage['meowseo_ai_openai_api_key'] = $manager_temp->encrypt_key( 'test_openai_key' );
		$wp_options_storage['meowseo_ai_anthropic_api_key'] = $manager_temp->encrypt_key( 'test_anthropic_key' );
		$wp_options_storage['meowseo_ai_imagen_api_key'] = $manager_temp->encrypt_key( 'test_imagen_key' );
		$wp_options_storage['meowseo_ai_dalle_api_key'] = $manager_temp->encrypt_key( 'test_dalle_key' );
		
		$this->options->method( 'get' )
			->willReturnCallback( function ( $key, $default = null ) {
				if ( 'ai_provider_order' === $key ) {
					return [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];
				}
				
				if ( 'ai_active_providers' === $key ) {
					return [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];
				}
				
				return $default;
			} );

		$manager = new AI_Provider_Manager( $this->options );
		$statuses = $manager->get_provider_statuses();

		// Verify all existing provider slugs are present
		$this->assertArrayHasKey( 'gemini', $statuses, 'Gemini slug should exist' );
		$this->assertArrayHasKey( 'openai', $statuses, 'OpenAI slug should exist' );
		$this->assertArrayHasKey( 'anthropic', $statuses, 'Anthropic slug should exist' );
		$this->assertArrayHasKey( 'imagen', $statuses, 'Imagen slug should exist' );
		$this->assertArrayHasKey( 'dalle', $statuses, 'DALL-E slug should exist' );

		// Verify existing provider labels are correct (from provider instances)
		$this->assertEquals( 'Google Gemini', $statuses['gemini']['label'], 'Gemini label should be correct' );
		$this->assertEquals( 'OpenAI', $statuses['openai']['label'], 'OpenAI label should be correct' );
		$this->assertEquals( 'Anthropic Claude', $statuses['anthropic']['label'], 'Anthropic label should be correct' );
		$this->assertEquals( 'Google Imagen', $statuses['imagen']['label'], 'Imagen label should be correct' );
		$this->assertEquals( 'DALL-E', $statuses['dalle']['label'], 'DALL-E label should be correct' );
	}

	/**
	 * Test that Gemini text generation remains backward compatible
	 *
	 * Validates Requirement 8.6: Gemini text generation functionality remains backward compatible
	 *
	 * @return void
	 */
	public function testGeminiTextGenerationRemainsBackwardCompatible(): void {
		$this->options->method( 'get' )
			->willReturnCallback( function ( $key, $default = null ) {
				return $default;
			} );

		$manager = new AI_Provider_Manager( $this->options );
		$statuses = $manager->get_provider_statuses();

		// Verify Gemini still supports text generation
		$this->assertTrue( $statuses['gemini']['supports_text'], 'Gemini should support text generation' );
		
		// Verify Gemini now also supports image generation (new feature)
		$this->assertTrue( $statuses['gemini']['supports_image'], 'Gemini should support image generation' );
	}

	/**
	 * Test that existing providers maintain their capabilities
	 *
	 * Validates Requirement 8.4: Existing provider slugs continue to function without modification
	 *
	 * @return void
	 */
	public function testExistingProvidersMaintainCapabilities(): void {
		$this->options->method( 'get' )
			->willReturnCallback( function ( $key, $default = null ) {
				return $default;
			} );

		$manager = new AI_Provider_Manager( $this->options );
		$statuses = $manager->get_provider_statuses();

		// Verify text-only providers
		$this->assertTrue( $statuses['anthropic']['supports_text'], 'Anthropic should support text' );
		$this->assertFalse( $statuses['anthropic']['supports_image'], 'Anthropic should not support image' );

		// Verify image-only providers
		$this->assertFalse( $statuses['imagen']['supports_text'], 'Imagen should not support text' );
		$this->assertTrue( $statuses['imagen']['supports_image'], 'Imagen should support image' );

		$this->assertFalse( $statuses['dalle']['supports_text'], 'DALL-E should not support text' );
		$this->assertTrue( $statuses['dalle']['supports_image'], 'DALL-E should support image' );

		// Verify dual-capability providers
		$this->assertTrue( $statuses['openai']['supports_text'], 'OpenAI should support text' );
		$this->assertTrue( $statuses['openai']['supports_image'], 'OpenAI should support image' );

		$this->assertTrue( $statuses['gemini']['supports_text'], 'Gemini should support text' );
		$this->assertTrue( $statuses['gemini']['supports_image'], 'Gemini should support image' );
	}

	/**
	 * Test that new providers are correctly identified
	 *
	 * Validates that new providers (DeepSeek, GLM, Qwen) are present in the system
	 *
	 * @return void
	 */
	public function testNewProvidersArePresent(): void {
		$this->options->method( 'get' )
			->willReturnCallback( function ( $key, $default = null ) {
				return $default;
			} );

		$manager = new AI_Provider_Manager( $this->options );
		$statuses = $manager->get_provider_statuses();

		// Verify new provider slugs exist
		$this->assertArrayHasKey( 'deepseek', $statuses, 'DeepSeek slug should exist' );
		$this->assertArrayHasKey( 'glm', $statuses, 'GLM slug should exist' );
		$this->assertArrayHasKey( 'qwen', $statuses, 'Qwen slug should exist' );

		// Verify new provider labels
		$this->assertEquals( 'DeepSeek', $statuses['deepseek']['label'], 'DeepSeek label should be correct' );
		$this->assertEquals( 'Zhipu AI GLM', $statuses['glm']['label'], 'GLM label should be correct' );
		$this->assertEquals( 'Alibaba Qwen', $statuses['qwen']['label'], 'Qwen label should be correct' );

		// Verify new providers support both text and image
		$this->assertTrue( $statuses['deepseek']['supports_text'], 'DeepSeek should support text' );
		$this->assertTrue( $statuses['deepseek']['supports_image'], 'DeepSeek should support image' );

		$this->assertTrue( $statuses['glm']['supports_text'], 'GLM should support text' );
		$this->assertTrue( $statuses['glm']['supports_image'], 'GLM should support image' );

		$this->assertTrue( $statuses['qwen']['supports_text'], 'Qwen should support text' );
		$this->assertTrue( $statuses['qwen']['supports_image'], 'Qwen should support image' );
	}

	/**
	 * Test that provider order can be extended with new providers
	 *
	 * Validates that new providers can be added to existing order without breaking it
	 *
	 * @return void
	 */
	public function testProviderOrderCanBeExtended(): void {
		// Simulate updated order that includes new providers
		$extended_order = [ 'gemini', 'openai', 'deepseek', 'anthropic', 'glm', 'qwen', 'imagen', 'dalle' ];

		$this->options->method( 'get' )
			->willReturnCallback( function ( $key, $default = null ) use ( $extended_order ) {
				if ( 'ai_provider_order' === $key ) {
					return $extended_order;
				}
				
				if ( 'ai_active_providers' === $key ) {
					return [ 'gemini', 'openai', 'deepseek', 'anthropic', 'glm', 'qwen' ];
				}
				
				return $default;
			} );

		$manager = new AI_Provider_Manager( $this->options );
		$statuses = $manager->get_provider_statuses();

		// Verify all providers have correct priorities
		$this->assertEquals( 0, $statuses['gemini']['priority'], 'Gemini should be priority 0' );
		$this->assertEquals( 1, $statuses['openai']['priority'], 'OpenAI should be priority 1' );
		$this->assertEquals( 2, $statuses['deepseek']['priority'], 'DeepSeek should be priority 2' );
		$this->assertEquals( 3, $statuses['anthropic']['priority'], 'Anthropic should be priority 3' );
		$this->assertEquals( 4, $statuses['glm']['priority'], 'GLM should be priority 4' );
		$this->assertEquals( 5, $statuses['qwen']['priority'], 'Qwen should be priority 5' );
		$this->assertEquals( 6, $statuses['imagen']['priority'], 'Imagen should be priority 6' );
		$this->assertEquals( 7, $statuses['dalle']['priority'], 'DALL-E should be priority 7' );
	}

	/**
	 * Test that mixed configuration (old + new providers) works correctly
	 *
	 * Validates that a configuration with both old and new providers functions properly
	 *
	 * @return void
	 */
	public function testMixedConfigurationWorks(): void {
		// Simulate a mixed configuration where some old and some new providers are active
		$mixed_order = [ 'gemini', 'deepseek', 'openai', 'glm', 'anthropic' ];
		$mixed_active = [ 'gemini', 'deepseek', 'openai' ];

		$this->options->method( 'get' )
			->willReturnCallback( function ( $key, $default = null ) use ( $mixed_order, $mixed_active ) {
				if ( 'ai_provider_order' === $key ) {
					return $mixed_order;
				}
				
				if ( 'ai_active_providers' === $key ) {
					return $mixed_active;
				}
				
				return $default;
			} );

		$manager = new AI_Provider_Manager( $this->options );
		$statuses = $manager->get_provider_statuses();

		// Verify active status
		$this->assertTrue( $statuses['gemini']['active'], 'Gemini should be active' );
		$this->assertTrue( $statuses['deepseek']['active'], 'DeepSeek should be active' );
		$this->assertTrue( $statuses['openai']['active'], 'OpenAI should be active' );
		$this->assertFalse( $statuses['glm']['active'], 'GLM should be inactive' );
		$this->assertFalse( $statuses['anthropic']['active'], 'Anthropic should be inactive' );

		// Verify priority order
		$this->assertEquals( 0, $statuses['gemini']['priority'], 'Gemini should be priority 0' );
		$this->assertEquals( 1, $statuses['deepseek']['priority'], 'DeepSeek should be priority 1' );
		$this->assertEquals( 2, $statuses['openai']['priority'], 'OpenAI should be priority 2' );
		$this->assertEquals( 3, $statuses['glm']['priority'], 'GLM should be priority 3' );
		$this->assertEquals( 4, $statuses['anthropic']['priority'], 'Anthropic should be priority 4' );
	}
}
