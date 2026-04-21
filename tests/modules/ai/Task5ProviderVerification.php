<?php
/**
 * Task 5 Provider Verification Test
 *
 * Comprehensive checkpoint test verifying all three new provider classes
 * (DeepSeek, GLM, Qwen) load correctly and return expected values.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Providers\Provider_DeepSeek;
use MeowSEO\Modules\AI\Providers\Provider_GLM;
use MeowSEO\Modules\AI\Providers\Provider_Qwen;
use MeowSEO\Modules\AI\Providers\Provider_OpenAI_Compatible;

/**
 * Task 5 Provider Verification Test
 *
 * Checkpoint test for Task 5: Verify new provider classes load correctly.
 *
 * This test ensures:
 * - All three provider classes can be instantiated
 * - Each provider returns correct slug, label, and capabilities
 * - All providers properly extend Provider_OpenAI_Compatible
 * - All providers implement AI_Provider interface
 *
 * @since 1.0.0
 */
class Task5ProviderVerification extends TestCase {

	/**
	 * Data provider for all three new providers.
	 *
	 * @return array Array of provider configurations.
	 */
	public function new_providers_data_provider(): array {
		return [
			'DeepSeek' => [
				'class'          => Provider_DeepSeek::class,
				'slug'           => 'deepseek',
				'label'          => 'DeepSeek',
				'supports_text'  => true,
				'supports_image' => true,
			],
			'GLM'      => [
				'class'          => Provider_GLM::class,
				'slug'           => 'glm',
				'label'          => 'Zhipu AI GLM',
				'supports_text'  => true,
				'supports_image' => true,
			],
			'Qwen'     => [
				'class'          => Provider_Qwen::class,
				'slug'           => 'qwen',
				'label'          => 'Alibaba Qwen',
				'supports_text'  => true,
				'supports_image' => true,
			],
		];
	}

	/**
	 * Test that all three new provider classes can be loaded.
	 *
	 * @dataProvider new_providers_data_provider
	 *
	 * @param string $class Provider class name.
	 *
	 * @return void
	 */
	public function test_all_new_providers_can_be_loaded( string $class ): void {
		$this->assertTrue(
			class_exists( $class ),
			"{$class} should be loadable by autoloader"
		);
	}

	/**
	 * Test that all three new providers implement AI_Provider interface.
	 *
	 * @dataProvider new_providers_data_provider
	 *
	 * @param string $class Provider class name.
	 *
	 * @return void
	 */
	public function test_all_new_providers_implement_interface( string $class ): void {
		$this->assertTrue(
			in_array(
				AI_Provider::class,
				class_implements( $class ),
				true
			),
			"{$class} should implement AI_Provider interface"
		);
	}

	/**
	 * Test that all three new providers extend Provider_OpenAI_Compatible.
	 *
	 * @dataProvider new_providers_data_provider
	 *
	 * @param string $class Provider class name.
	 *
	 * @return void
	 */
	public function test_all_new_providers_extend_base_class( string $class ): void {
		$this->assertTrue(
			is_subclass_of( $class, Provider_OpenAI_Compatible::class ),
			"{$class} should extend Provider_OpenAI_Compatible"
		);
	}

	/**
	 * Test that all three new providers can be instantiated.
	 *
	 * @dataProvider new_providers_data_provider
	 *
	 * @param string $class Provider class name.
	 *
	 * @return void
	 */
	public function test_all_new_providers_can_be_instantiated( string $class ): void {
		$provider = new $class( 'test-api-key' );

		$this->assertInstanceOf(
			$class,
			$provider,
			"{$class} should be instantiable"
		);

		$this->assertInstanceOf(
			AI_Provider::class,
			$provider,
			"{$class} instance should be an AI_Provider"
		);
	}

	/**
	 * Test that all three new providers return correct slug.
	 *
	 * @dataProvider new_providers_data_provider
	 *
	 * @param string $class Provider class name.
	 * @param string $slug Expected slug.
	 *
	 * @return void
	 */
	public function test_all_new_providers_return_correct_slug(
		string $class,
		string $slug
	): void {
		$provider = new $class( 'test-api-key' );

		$this->assertEquals(
			$slug,
			$provider->get_slug(),
			"{$class}::get_slug() should return '{$slug}'"
		);
	}

	/**
	 * Test that all three new providers return correct label.
	 *
	 * @dataProvider new_providers_data_provider
	 *
	 * @param string $class Provider class name.
	 * @param string $slug Expected slug.
	 * @param string $label Expected label.
	 *
	 * @return void
	 */
	public function test_all_new_providers_return_correct_label(
		string $class,
		string $slug,
		string $label
	): void {
		$provider = new $class( 'test-api-key' );

		$this->assertEquals(
			$label,
			$provider->get_label(),
			"{$class}::get_label() should return '{$label}'"
		);
	}

	/**
	 * Test that all three new providers return correct text capability.
	 *
	 * @dataProvider new_providers_data_provider
	 *
	 * @param string $class Provider class name.
	 * @param string $slug Expected slug.
	 * @param string $label Expected label.
	 * @param bool   $supports_text Expected text support.
	 *
	 * @return void
	 */
	public function test_all_new_providers_support_text(
		string $class,
		string $slug,
		string $label,
		bool $supports_text
	): void {
		$provider = new $class( 'test-api-key' );

		$this->assertSame(
			$supports_text,
			$provider->supports_text(),
			"{$class}::supports_text() should return " . ( $supports_text ? 'true' : 'false' )
		);
	}

	/**
	 * Test that all three new providers return correct image capability.
	 *
	 * @dataProvider new_providers_data_provider
	 *
	 * @param string $class Provider class name.
	 * @param string $slug Expected slug.
	 * @param string $label Expected label.
	 * @param bool   $supports_text Expected text support.
	 * @param bool   $supports_image Expected image support.
	 *
	 * @return void
	 */
	public function test_all_new_providers_support_image(
		string $class,
		string $slug,
		string $label,
		bool $supports_text,
		bool $supports_image
	): void {
		$provider = new $class( 'test-api-key' );

		$this->assertSame(
			$supports_image,
			$provider->supports_image(),
			"{$class}::supports_image() should return " . ( $supports_image ? 'true' : 'false' )
		);
	}

	/**
	 * Test that all three new providers have no initial error.
	 *
	 * @dataProvider new_providers_data_provider
	 *
	 * @param string $class Provider class name.
	 *
	 * @return void
	 */
	public function test_all_new_providers_have_no_initial_error( string $class ): void {
		$provider = new $class( 'test-api-key' );

		$this->assertNull(
			$provider->get_last_error(),
			"{$class}::get_last_error() should return null initially"
		);
	}

	/**
	 * Test comprehensive provider verification summary.
	 *
	 * This test provides a summary of all three providers' capabilities.
	 *
	 * @return void
	 */
	public function test_comprehensive_provider_summary(): void {
		$providers = [
			new Provider_DeepSeek( 'test-key-1' ),
			new Provider_GLM( 'test-key-2' ),
			new Provider_Qwen( 'test-key-3' ),
		];

		$summary = [];

		foreach ( $providers as $provider ) {
			$summary[] = [
				'slug'           => $provider->get_slug(),
				'label'          => $provider->get_label(),
				'supports_text'  => $provider->supports_text(),
				'supports_image' => $provider->supports_image(),
				'has_error'      => $provider->get_last_error() !== null,
			];
		}

		// Verify we have exactly 3 providers
		$this->assertCount(
			3,
			$summary,
			'Should have exactly 3 new providers'
		);

		// Verify all providers support both text and image
		foreach ( $summary as $provider_info ) {
			$this->assertTrue(
				$provider_info['supports_text'],
				"{$provider_info['label']} should support text generation"
			);

			$this->assertTrue(
				$provider_info['supports_image'],
				"{$provider_info['label']} should support image generation"
			);

			$this->assertFalse(
				$provider_info['has_error'],
				"{$provider_info['label']} should have no initial error"
			);
		}

		// Verify unique slugs
		$slugs = array_column( $summary, 'slug' );
		$this->assertCount(
			3,
			array_unique( $slugs ),
			'All provider slugs should be unique'
		);

		// Verify expected slugs are present
		$this->assertContains( 'deepseek', $slugs, 'DeepSeek slug should be present' );
		$this->assertContains( 'glm', $slugs, 'GLM slug should be present' );
		$this->assertContains( 'qwen', $slugs, 'Qwen slug should be present' );
	}
}
