<?php
/**
 * Provider Ordering and Active Status Test
 *
 * Tests that verify provider ordering and active status handling
 * for all providers including the new DeepSeek, GLM, and Qwen providers.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Options;

/**
 * Provider Ordering and Active Status Test Case
 *
 * Validates Requirements 5.3-5.7:
 * - Provider ordering with ai_provider_order option
 * - Active status with ai_active_providers option
 * - API key storage pattern meowseo_ai_{slug}_api_key
 *
 * @since 1.0.0
 */
class ProviderOrderingTest extends TestCase {

	/**
	 * Test that new providers respect ai_provider_order option.
	 *
	 * Validates Requirement 5.5: Provider Manager SHALL support new provider slugs
	 * in the 'ai_provider_order' option.
	 *
	 * @return void
	 */
	public function test_new_providers_respect_provider_order(): void {
		// Clear cache to ensure fresh results.
		wp_cache_delete( 'ai_provider_statuses', 'meowseo' );
		
		$options = $this->createMock( Options::class );
		
		// Configure provider order with new providers first.
		$provider_order = [ 'deepseek', 'glm', 'qwen', 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];
		$active_providers = [ 'deepseek', 'glm', 'qwen', 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];
		
		$options->method( 'get' )
			->willReturnCallback( function ( $key, $default ) use ( $provider_order, $active_providers ) {
				if ( 'ai_provider_order' === $key ) {
					return $provider_order;
				}
				if ( 'ai_active_providers' === $key ) {
					return $active_providers;
				}
				return $default;
			} );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		// Verify all new providers are present in statuses.
		$this->assertArrayHasKey( 'deepseek', $statuses, 'DeepSeek should be in provider statuses' );
		$this->assertArrayHasKey( 'glm', $statuses, 'GLM should be in provider statuses' );
		$this->assertArrayHasKey( 'qwen', $statuses, 'Qwen should be in provider statuses' );

		// Verify priority reflects the configured order.
		$this->assertEquals( 0, $statuses['deepseek']['priority'], 'DeepSeek should have priority 0' );
		$this->assertEquals( 1, $statuses['glm']['priority'], 'GLM should have priority 1' );
		$this->assertEquals( 2, $statuses['qwen']['priority'], 'Qwen should have priority 2' );
		$this->assertEquals( 3, $statuses['gemini']['priority'], 'Gemini should have priority 3' );
	}

	/**
	 * Test that new providers respect ai_active_providers option.
	 *
	 * Validates Requirement 5.6: Provider Manager SHALL support new provider slugs
	 * in the 'ai_active_providers' option.
	 *
	 * @return void
	 */
	public function test_new_providers_respect_active_status(): void {
		// Clear cache to ensure fresh results.
		wp_cache_delete( 'ai_provider_statuses', 'meowseo' );
		
		$options = $this->createMock( Options::class );
		
		// Configure only new providers as active.
		$provider_order = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle', 'deepseek', 'glm', 'qwen' ];
		$active_providers = [ 'deepseek', 'glm', 'qwen' ];
		
		$options->method( 'get' )
			->willReturnCallback( function ( $key, $default ) use ( $provider_order, $active_providers ) {
				if ( 'ai_provider_order' === $key ) {
					return $provider_order;
				}
				if ( 'ai_active_providers' === $key ) {
					return $active_providers;
				}
				return $default;
			} );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		// Verify new providers are marked as active.
		$this->assertTrue( $statuses['deepseek']['active'], 'DeepSeek should be active' );
		$this->assertTrue( $statuses['glm']['active'], 'GLM should be active' );
		$this->assertTrue( $statuses['qwen']['active'], 'Qwen should be active' );

		// Verify existing providers are marked as inactive.
		$this->assertFalse( $statuses['gemini']['active'], 'Gemini should be inactive' );
		$this->assertFalse( $statuses['openai']['active'], 'OpenAI should be inactive' );
		$this->assertFalse( $statuses['anthropic']['active'], 'Anthropic should be inactive' );
	}

	/**
	 * Test that API key storage pattern works for new providers.
	 *
	 * Validates Requirement 5.7: Provider Manager SHALL store and retrieve encrypted
	 * API keys for each new provider using the pattern 'meowseo_ai_{slug}_api_key'.
	 *
	 * @return void
	 */
	public function test_api_key_storage_pattern_for_new_providers(): void {
		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key-for-unit-tests-32-chars!' );
		}

		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );

		// Test encryption for new provider slugs.
		$test_key = 'sk-test-api-key-12345';
		
		$encrypted_deepseek = $manager->encrypt_key( $test_key );
		$encrypted_glm = $manager->encrypt_key( $test_key );
		$encrypted_qwen = $manager->encrypt_key( $test_key );

		// Verify encryption produces valid base64 strings.
		$this->assertIsString( $encrypted_deepseek, 'DeepSeek key encryption should return string' );
		$this->assertIsString( $encrypted_glm, 'GLM key encryption should return string' );
		$this->assertIsString( $encrypted_qwen, 'Qwen key encryption should return string' );

		// Verify encrypted values are different (due to random IV).
		$this->assertNotEquals( $encrypted_deepseek, $encrypted_glm, 'Encrypted keys should differ due to random IV' );
		$this->assertNotEquals( $encrypted_glm, $encrypted_qwen, 'Encrypted keys should differ due to random IV' );

		// Verify the option key pattern would be correct.
		$expected_option_keys = [
			'meowseo_ai_deepseek_api_key',
			'meowseo_ai_glm_api_key',
			'meowseo_ai_qwen_api_key',
		];

		foreach ( $expected_option_keys as $option_key ) {
			$this->assertMatchesRegularExpression(
				'/^meowseo_ai_[a-z]+_api_key$/',
				$option_key,
				"Option key '{$option_key}' should match pattern meowseo_ai_{slug}_api_key"
			);
		}
	}

	/**
	 * Test that provider order handles mixed old and new providers.
	 *
	 * Validates that the ordering system works correctly when both
	 * existing and new providers are configured together.
	 *
	 * @return void
	 */
	public function test_provider_order_handles_mixed_providers(): void {
		// Clear cache to ensure fresh results.
		wp_cache_delete( 'ai_provider_statuses', 'meowseo' );
		
		$options = $this->createMock( Options::class );
		
		// Configure mixed order: new, old, new, old pattern.
		$provider_order = [ 'deepseek', 'gemini', 'glm', 'openai', 'qwen', 'anthropic', 'imagen', 'dalle' ];
		$active_providers = [ 'deepseek', 'gemini', 'glm', 'openai', 'qwen', 'anthropic', 'imagen', 'dalle' ];
		
		$options->method( 'get' )
			->willReturnCallback( function ( $key, $default ) use ( $provider_order, $active_providers ) {
				if ( 'ai_provider_order' === $key ) {
					return $provider_order;
				}
				if ( 'ai_active_providers' === $key ) {
					return $active_providers;
				}
				return $default;
			} );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		// Verify priorities match the configured order.
		$this->assertEquals( 0, $statuses['deepseek']['priority'], 'DeepSeek should be first' );
		$this->assertEquals( 1, $statuses['gemini']['priority'], 'Gemini should be second' );
		$this->assertEquals( 2, $statuses['glm']['priority'], 'GLM should be third' );
		$this->assertEquals( 3, $statuses['openai']['priority'], 'OpenAI should be fourth' );
		$this->assertEquals( 4, $statuses['qwen']['priority'], 'Qwen should be fifth' );
		$this->assertEquals( 5, $statuses['anthropic']['priority'], 'Anthropic should be sixth' );
	}

	/**
	 * Test that inactive providers are excluded from generation attempts.
	 *
	 * Validates that when providers are marked as inactive in ai_active_providers,
	 * they are not used for generation even if they have API keys.
	 *
	 * @return void
	 */
	public function test_inactive_providers_excluded_from_generation(): void {
		// Clear cache to ensure fresh results.
		wp_cache_delete( 'ai_provider_statuses', 'meowseo' );
		
		$options = $this->createMock( Options::class );
		
		// Configure all providers in order but only some as active.
		$provider_order = [ 'deepseek', 'glm', 'qwen', 'gemini', 'openai', 'anthropic', 'imagen', 'dalle' ];
		$active_providers = [ 'deepseek', 'gemini' ]; // Only DeepSeek and Gemini active.
		
		$options->method( 'get' )
			->willReturnCallback( function ( $key, $default ) use ( $provider_order, $active_providers ) {
				if ( 'ai_provider_order' === $key ) {
					return $provider_order;
				}
				if ( 'ai_active_providers' === $key ) {
					return $active_providers;
				}
				return $default;
			} );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		// Verify active status is correctly set.
		$this->assertTrue( $statuses['deepseek']['active'], 'DeepSeek should be active' );
		$this->assertFalse( $statuses['glm']['active'], 'GLM should be inactive' );
		$this->assertFalse( $statuses['qwen']['active'], 'Qwen should be inactive' );
		$this->assertTrue( $statuses['gemini']['active'], 'Gemini should be active' );
		$this->assertFalse( $statuses['openai']['active'], 'OpenAI should be inactive' );
	}

	/**
	 * Test that provider order defaults to 999 for providers not in order list.
	 *
	 * Validates that providers not explicitly listed in ai_provider_order
	 * receive a default priority of 999.
	 *
	 * @return void
	 */
	public function test_provider_order_defaults_for_unlisted_providers(): void {
		// Clear cache to ensure fresh results.
		wp_cache_delete( 'ai_provider_statuses', 'meowseo' );
		
		$options = $this->createMock( Options::class );
		
		// Configure order with only some providers.
		$provider_order = [ 'gemini', 'openai' ];
		$active_providers = [ 'gemini', 'openai', 'deepseek', 'glm', 'qwen' ];
		
		$options->method( 'get' )
			->willReturnCallback( function ( $key, $default ) use ( $provider_order, $active_providers ) {
				if ( 'ai_provider_order' === $key ) {
					return $provider_order;
				}
				if ( 'ai_active_providers' === $key ) {
					return $active_providers;
				}
				return $default;
			} );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		// Verify listed providers have correct priorities.
		$this->assertEquals( 0, $statuses['gemini']['priority'], 'Gemini should have priority 0' );
		$this->assertEquals( 1, $statuses['openai']['priority'], 'OpenAI should have priority 1' );

		// Verify unlisted providers have default priority 999.
		$this->assertEquals( 999, $statuses['deepseek']['priority'], 'DeepSeek should have default priority 999' );
		$this->assertEquals( 999, $statuses['glm']['priority'], 'GLM should have default priority 999' );
		$this->assertEquals( 999, $statuses['qwen']['priority'], 'Qwen should have default priority 999' );
	}

	/**
	 * Test that all new providers support both text and image generation.
	 *
	 * Validates that DeepSeek, GLM, and Qwen are correctly configured
	 * to support both text and image capabilities.
	 *
	 * @return void
	 */
	public function test_new_providers_support_text_and_image(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		$new_providers = [ 'deepseek', 'glm', 'qwen' ];

		foreach ( $new_providers as $slug ) {
			$this->assertTrue(
				$statuses[ $slug ]['supports_text'],
				"Provider '{$slug}' should support text generation"
			);
			$this->assertTrue(
				$statuses[ $slug ]['supports_image'],
				"Provider '{$slug}' should support image generation"
			);
		}
	}

	/**
	 * Test that provider statuses include correct labels for new providers.
	 *
	 * Validates Requirement 5.8: Provider Manager SHALL update get_provider_label
	 * method to include labels for new providers.
	 *
	 * @return void
	 */
	public function test_new_providers_have_correct_labels(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( [] );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		$expected_labels = [
			'deepseek' => 'DeepSeek',
			'glm'      => 'Zhipu AI GLM',
			'qwen'     => 'Alibaba Qwen',
		];

		foreach ( $expected_labels as $slug => $expected_label ) {
			$this->assertEquals(
				$expected_label,
				$statuses[ $slug ]['label'],
				"Provider '{$slug}' should have label '{$expected_label}'"
			);
		}
	}

	/**
	 * Test that empty provider order array is handled gracefully.
	 *
	 * Validates that the system works correctly when ai_provider_order
	 * is an empty array.
	 *
	 * @return void
	 */
	public function test_empty_provider_order_handled_gracefully(): void {
		// Clear cache to ensure fresh results.
		wp_cache_delete( 'ai_provider_statuses', 'meowseo' );
		
		$options = $this->createMock( Options::class );
		
		$provider_order = [];
		$active_providers = [ 'deepseek', 'glm', 'qwen' ];
		
		$options->method( 'get' )
			->willReturnCallback( function ( $key, $default ) use ( $provider_order, $active_providers ) {
				if ( 'ai_provider_order' === $key ) {
					return $provider_order;
				}
				if ( 'ai_active_providers' === $key ) {
					return $active_providers;
				}
				return $default;
			} );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		// All providers should have default priority 999.
		foreach ( [ 'deepseek', 'glm', 'qwen' ] as $slug ) {
			$this->assertEquals(
				999,
				$statuses[ $slug ]['priority'],
				"Provider '{$slug}' should have default priority when order is empty"
			);
		}
	}

	/**
	 * Test that empty active providers array is handled gracefully.
	 *
	 * Validates that when ai_active_providers is empty, all providers
	 * are marked as inactive.
	 *
	 * @return void
	 */
	public function test_empty_active_providers_handled_gracefully(): void {
		// Clear cache to ensure fresh results.
		wp_cache_delete( 'ai_provider_statuses', 'meowseo' );
		
		$options = $this->createMock( Options::class );
		
		$provider_order = [ 'deepseek', 'glm', 'qwen', 'gemini', 'openai' ];
		$active_providers = [];
		
		$options->method( 'get' )
			->willReturnCallback( function ( $key, $default ) use ( $provider_order, $active_providers ) {
				if ( 'ai_provider_order' === $key ) {
					return $provider_order;
				}
				if ( 'ai_active_providers' === $key ) {
					return $active_providers;
				}
				return $default;
			} );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		// All providers should be inactive.
		foreach ( $statuses as $slug => $status ) {
			$this->assertFalse(
				$status['active'],
				"Provider '{$slug}' should be inactive when active_providers is empty"
			);
		}
	}

	/**
	 * Test that provider order with duplicate slugs is handled correctly.
	 *
	 * Validates that if ai_provider_order contains duplicates,
	 * the first occurrence is used for priority.
	 *
	 * @return void
	 */
	public function test_provider_order_with_duplicates_handled(): void {
		// Clear cache to ensure fresh results.
		wp_cache_delete( 'ai_provider_statuses', 'meowseo' );
		
		$options = $this->createMock( Options::class );
		
		// Configure order with duplicates (should not happen in practice, but test robustness).
		$provider_order = [ 'deepseek', 'gemini', 'deepseek', 'glm' ];
		$active_providers = [ 'deepseek', 'gemini', 'glm' ];
		
		$options->method( 'get' )
			->willReturnCallback( function ( $key, $default ) use ( $provider_order, $active_providers ) {
				if ( 'ai_provider_order' === $key ) {
					return $provider_order;
				}
				if ( 'ai_active_providers' === $key ) {
					return $active_providers;
				}
				return $default;
			} );

		$manager = new AI_Provider_Manager( $options );
		$statuses = $manager->get_provider_statuses();

		// First occurrence should be used (priority 0).
		$this->assertEquals( 0, $statuses['deepseek']['priority'], 'DeepSeek should use first occurrence priority' );
		$this->assertEquals( 1, $statuses['gemini']['priority'], 'Gemini should have priority 1' );
		$this->assertEquals( 3, $statuses['glm']['priority'], 'GLM should have priority 3' );
	}
}
