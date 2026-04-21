<?php
/**
 * Tests for AI_Provider interface stability.
 *
 * Verifies that the AI_Provider interface remains unchanged and that all providers
 * (existing and new) continue to implement it correctly. This ensures backward
 * compatibility after adding new providers and enhancing existing ones.
 *
 * @package MeowSEO\Tests\Modules\AI
 */

namespace MeowSEO\Tests\Modules\AI;

use MeowSEO\Modules\AI\Contracts\AI_Provider;
use MeowSEO\Modules\AI\Providers\Provider_Gemini;
use MeowSEO\Modules\AI\Providers\Provider_OpenAI;
use MeowSEO\Modules\AI\Providers\Provider_Anthropic;
use MeowSEO\Modules\AI\Providers\Provider_Imagen;
use MeowSEO\Modules\AI\Providers\Provider_Dalle;
use MeowSEO\Modules\AI\Providers\Provider_DeepSeek;
use MeowSEO\Modules\AI\Providers\Provider_GLM;
use MeowSEO\Modules\AI\Providers\Provider_Qwen;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class InterfaceStabilityTest
 *
 * Tests to verify AI_Provider interface stability and backward compatibility.
 *
 * Requirements tested:
 * - 8.5: AI_Provider interface remains unchanged
 * - 8.6: Gemini text generation remains backward compatible
 * - All providers implement the interface correctly
 *
 * @since 1.0.0
 */
class InterfaceStabilityTest extends TestCase {

	/**
	 * Test that AI_Provider interface has the expected methods.
	 *
	 * Verifies that the interface hasn't changed by checking all required methods exist
	 * with the correct signatures.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function test_ai_provider_interface_methods() {
		$reflection = new ReflectionClass( AI_Provider::class );

		// Expected interface methods.
		$expected_methods = [
			'get_slug',
			'get_label',
			'supports_text',
			'supports_image',
			'generate_text',
			'generate_image',
			'validate_api_key',
			'get_last_error',
		];

		// Get actual interface methods.
		$actual_methods = array_map(
			function ( ReflectionMethod $method ) {
				return $method->getName();
			},
			$reflection->getMethods()
		);

		// Verify all expected methods exist.
		foreach ( $expected_methods as $method ) {
			$this->assertContains(
				$method,
				$actual_methods,
				"AI_Provider interface is missing method: {$method}"
			);
		}

		// Verify no unexpected methods were added.
		$this->assertCount(
			count( $expected_methods ),
			$actual_methods,
			'AI_Provider interface has unexpected methods'
		);
	}

	/**
	 * Test that AI_Provider interface method signatures are correct.
	 *
	 * Verifies return types and parameter counts for all interface methods.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function test_ai_provider_interface_signatures() {
		$reflection = new ReflectionClass( AI_Provider::class );

		// Test get_slug() signature.
		$method = $reflection->getMethod( 'get_slug' );
		$this->assertTrue( $method->hasReturnType(), 'get_slug() should have return type' );
		$this->assertEquals( 'string', $method->getReturnType()->getName(), 'get_slug() should return string' );
		$this->assertEquals( 0, $method->getNumberOfParameters(), 'get_slug() should have no parameters' );

		// Test get_label() signature.
		$method = $reflection->getMethod( 'get_label' );
		$this->assertTrue( $method->hasReturnType(), 'get_label() should have return type' );
		$this->assertEquals( 'string', $method->getReturnType()->getName(), 'get_label() should return string' );
		$this->assertEquals( 0, $method->getNumberOfParameters(), 'get_label() should have no parameters' );

		// Test supports_text() signature.
		$method = $reflection->getMethod( 'supports_text' );
		$this->assertTrue( $method->hasReturnType(), 'supports_text() should have return type' );
		$this->assertEquals( 'bool', $method->getReturnType()->getName(), 'supports_text() should return bool' );
		$this->assertEquals( 0, $method->getNumberOfParameters(), 'supports_text() should have no parameters' );

		// Test supports_image() signature.
		$method = $reflection->getMethod( 'supports_image' );
		$this->assertTrue( $method->hasReturnType(), 'supports_image() should have return type' );
		$this->assertEquals( 'bool', $method->getReturnType()->getName(), 'supports_image() should return bool' );
		$this->assertEquals( 0, $method->getNumberOfParameters(), 'supports_image() should have no parameters' );

		// Test generate_text() signature.
		$method = $reflection->getMethod( 'generate_text' );
		$this->assertTrue( $method->hasReturnType(), 'generate_text() should have return type' );
		$this->assertEquals( 'array', $method->getReturnType()->getName(), 'generate_text() should return array' );
		$this->assertEquals( 2, $method->getNumberOfParameters(), 'generate_text() should have 2 parameters' );

		// Test generate_image() signature.
		$method = $reflection->getMethod( 'generate_image' );
		$this->assertTrue( $method->hasReturnType(), 'generate_image() should have return type' );
		$this->assertEquals( 'array', $method->getReturnType()->getName(), 'generate_image() should return array' );
		$this->assertEquals( 2, $method->getNumberOfParameters(), 'generate_image() should have 2 parameters' );

		// Test validate_api_key() signature.
		$method = $reflection->getMethod( 'validate_api_key' );
		$this->assertTrue( $method->hasReturnType(), 'validate_api_key() should have return type' );
		$this->assertEquals( 'bool', $method->getReturnType()->getName(), 'validate_api_key() should return bool' );
		$this->assertEquals( 1, $method->getNumberOfParameters(), 'validate_api_key() should have 1 parameter' );

		// Test get_last_error() signature.
		$method = $reflection->getMethod( 'get_last_error' );
		$this->assertTrue( $method->hasReturnType(), 'get_last_error() should have return type' );
		$return_type = $method->getReturnType();
		$this->assertTrue( $return_type->allowsNull(), 'get_last_error() should allow null' );
	}

	/**
	 * Data provider for all provider classes.
	 *
	 * Returns all provider classes (existing and new) for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of provider class names.
	 */
	public function provider_classes_data_provider(): array {
		return [
			'Gemini'    => [ Provider_Gemini::class, 'gemini', 'Google Gemini' ],
			'OpenAI'    => [ Provider_OpenAI::class, 'openai', 'OpenAI' ],
			'Anthropic' => [ Provider_Anthropic::class, 'anthropic', 'Anthropic Claude' ],
			'Imagen'    => [ Provider_Imagen::class, 'imagen', 'Google Imagen' ],
			'DALL-E'    => [ Provider_Dalle::class, 'dalle', 'DALL-E' ],
			'DeepSeek'  => [ Provider_DeepSeek::class, 'deepseek', 'DeepSeek' ],
			'GLM'       => [ Provider_GLM::class, 'glm', 'Zhipu AI GLM' ],
			'Qwen'      => [ Provider_Qwen::class, 'qwen', 'Alibaba Qwen' ],
		];
	}

	/**
	 * Test that all providers implement AI_Provider interface.
	 *
	 * Verifies that each provider class implements the AI_Provider interface.
	 *
	 * @dataProvider provider_classes_data_provider
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Provider class name.
	 * @param string $expected_slug Expected provider slug.
	 * @param string $expected_label Expected provider label.
	 * @return void
	 */
	public function test_provider_implements_interface( string $class_name, string $expected_slug, string $expected_label ) {
		$reflection = new ReflectionClass( $class_name );

		$this->assertTrue(
			$reflection->implementsInterface( AI_Provider::class ),
			"{$class_name} must implement AI_Provider interface"
		);
	}

	/**
	 * Test that all providers have correct slug and label.
	 *
	 * Verifies that each provider returns the expected slug and label.
	 *
	 * @dataProvider provider_classes_data_provider
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Provider class name.
	 * @param string $expected_slug Expected provider slug.
	 * @param string $expected_label Expected provider label.
	 * @return void
	 */
	public function test_provider_slug_and_label( string $class_name, string $expected_slug, string $expected_label ) {
		$provider = new $class_name( 'test-api-key' );

		$this->assertEquals(
			$expected_slug,
			$provider->get_slug(),
			"{$class_name} should return correct slug"
		);

		$this->assertEquals(
			$expected_label,
			$provider->get_label(),
			"{$class_name} should return correct label"
		);
	}

	/**
	 * Test that all providers implement all interface methods.
	 *
	 * Verifies that each provider has all required methods from the interface.
	 *
	 * @dataProvider provider_classes_data_provider
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Provider class name.
	 * @param string $expected_slug Expected provider slug.
	 * @param string $expected_label Expected provider label.
	 * @return void
	 */
	public function test_provider_has_all_interface_methods( string $class_name, string $expected_slug, string $expected_label ) {
		$reflection = new ReflectionClass( $class_name );

		$required_methods = [
			'get_slug',
			'get_label',
			'supports_text',
			'supports_image',
			'generate_text',
			'generate_image',
			'validate_api_key',
			'get_last_error',
		];

		foreach ( $required_methods as $method ) {
			$this->assertTrue(
				$reflection->hasMethod( $method ),
				"{$class_name} must implement {$method}() method"
			);

			$method_reflection = $reflection->getMethod( $method );
			$this->assertTrue(
				$method_reflection->isPublic(),
				"{$class_name}::{$method}() must be public"
			);
		}
	}

	/**
	 * Test that providers return correct capability flags.
	 *
	 * Verifies that each provider correctly reports text and image generation capabilities.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function test_provider_capabilities() {
		// Text-only providers.
		$anthropic = new Provider_Anthropic( 'test-key' );
		$this->assertTrue( $anthropic->supports_text(), 'Anthropic should support text' );
		$this->assertFalse( $anthropic->supports_image(), 'Anthropic should not support image' );

		// Image-only providers.
		$imagen = new Provider_Imagen( 'test-key' );
		$this->assertFalse( $imagen->supports_text(), 'Imagen should not support text' );
		$this->assertTrue( $imagen->supports_image(), 'Imagen should support image' );

		$dalle = new Provider_Dalle( 'test-key' );
		$this->assertFalse( $dalle->supports_text(), 'DALL-E should not support text' );
		$this->assertTrue( $dalle->supports_image(), 'DALL-E should support image' );

		// Text + Image providers (existing).
		$openai = new Provider_OpenAI( 'test-key' );
		$this->assertTrue( $openai->supports_text(), 'OpenAI should support text' );
		$this->assertTrue( $openai->supports_image(), 'OpenAI should support image' );

		// Gemini - now supports both text and image (Requirement 8.6).
		$gemini = new Provider_Gemini( 'test-key' );
		$this->assertTrue( $gemini->supports_text(), 'Gemini should support text' );
		$this->assertTrue( $gemini->supports_image(), 'Gemini should support image after enhancement' );

		// New providers - all support text + image.
		$deepseek = new Provider_DeepSeek( 'test-key' );
		$this->assertTrue( $deepseek->supports_text(), 'DeepSeek should support text' );
		$this->assertTrue( $deepseek->supports_image(), 'DeepSeek should support image' );

		$glm = new Provider_GLM( 'test-key' );
		$this->assertTrue( $glm->supports_text(), 'GLM should support text' );
		$this->assertTrue( $glm->supports_image(), 'GLM should support image' );

		$qwen = new Provider_Qwen( 'test-key' );
		$this->assertTrue( $qwen->supports_text(), 'Qwen should support text' );
		$this->assertTrue( $qwen->supports_image(), 'Qwen should support image' );
	}

	/**
	 * Test Gemini text generation backward compatibility.
	 *
	 * Verifies that Gemini's text generation functionality remains unchanged
	 * after adding image generation support (Requirement 8.6).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function test_gemini_text_generation_backward_compatibility() {
		$gemini = new Provider_Gemini( 'test-api-key' );

		// Verify text generation method exists and is public.
		$reflection = new ReflectionClass( $gemini );
		$this->assertTrue(
			$reflection->hasMethod( 'generate_text' ),
			'Gemini must have generate_text() method'
		);

		$method = $reflection->getMethod( 'generate_text' );
		$this->assertTrue(
			$method->isPublic(),
			'Gemini::generate_text() must be public'
		);

		// Verify method signature hasn't changed.
		$this->assertEquals(
			2,
			$method->getNumberOfParameters(),
			'Gemini::generate_text() should have 2 parameters'
		);

		$params = $method->getParameters();
		$this->assertEquals(
			'prompt',
			$params[0]->getName(),
			'First parameter should be $prompt'
		);
		$this->assertEquals(
			'options',
			$params[1]->getName(),
			'Second parameter should be $options'
		);
		$this->assertTrue(
			$params[1]->isOptional(),
			'$options parameter should be optional'
		);

		// Verify return type.
		$this->assertTrue(
			$method->hasReturnType(),
			'Gemini::generate_text() should have return type'
		);
		$this->assertEquals(
			'array',
			$method->getReturnType()->getName(),
			'Gemini::generate_text() should return array'
		);
	}

	/**
	 * Test that Gemini still supports text generation.
	 *
	 * Verifies that Gemini's supports_text() method still returns true
	 * after adding image generation support.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function test_gemini_still_supports_text() {
		$gemini = new Provider_Gemini( 'test-api-key' );

		$this->assertTrue(
			$gemini->supports_text(),
			'Gemini must still support text generation after adding image support'
		);
	}

	/**
	 * Test that all providers can be instantiated.
	 *
	 * Verifies that all provider classes can be instantiated with an API key.
	 *
	 * @dataProvider provider_classes_data_provider
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Provider class name.
	 * @param string $expected_slug Expected provider slug.
	 * @param string $expected_label Expected provider label.
	 * @return void
	 */
	public function test_provider_can_be_instantiated( string $class_name, string $expected_slug, string $expected_label ) {
		$provider = new $class_name( 'test-api-key' );

		$this->assertInstanceOf(
			AI_Provider::class,
			$provider,
			"{$class_name} instance should implement AI_Provider"
		);

		$this->assertInstanceOf(
			$class_name,
			$provider,
			"Instance should be of type {$class_name}"
		);
	}

	/**
	 * Test that get_last_error() returns null initially.
	 *
	 * Verifies that all providers return null from get_last_error() when no error has occurred.
	 *
	 * @dataProvider provider_classes_data_provider
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Provider class name.
	 * @param string $expected_slug Expected provider slug.
	 * @param string $expected_label Expected provider label.
	 * @return void
	 */
	public function test_provider_initial_error_state( string $class_name, string $expected_slug, string $expected_label ) {
		$provider = new $class_name( 'test-api-key' );

		$this->assertNull(
			$provider->get_last_error(),
			"{$class_name} should return null from get_last_error() initially"
		);
	}

	/**
	 * Test that interface is truly an interface.
	 *
	 * Verifies that AI_Provider is defined as an interface, not a class or trait.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function test_ai_provider_is_interface() {
		$reflection = new ReflectionClass( AI_Provider::class );

		$this->assertTrue(
			$reflection->isInterface(),
			'AI_Provider must be an interface'
		);

		$this->assertFalse(
			$reflection->isAbstract() && ! $reflection->isInterface(),
			'AI_Provider should not be an abstract class'
		);

		$this->assertFalse(
			$reflection->isTrait(),
			'AI_Provider should not be a trait'
		);
	}

	/**
	 * Test that new providers extend the correct base class.
	 *
	 * Verifies that DeepSeek, GLM, and Qwen extend Provider_OpenAI_Compatible.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function test_new_providers_extend_base_class() {
		$new_providers = [
			Provider_DeepSeek::class,
			Provider_GLM::class,
			Provider_Qwen::class,
		];

		foreach ( $new_providers as $provider_class ) {
			$reflection = new ReflectionClass( $provider_class );
			$parent     = $reflection->getParentClass();

			$this->assertNotFalse(
				$parent,
				"{$provider_class} should extend a base class"
			);

			$this->assertEquals(
				'MeowSEO\Modules\AI\Providers\Provider_OpenAI_Compatible',
				$parent->getName(),
				"{$provider_class} should extend Provider_OpenAI_Compatible"
			);
		}
	}

	/**
	 * Test that existing providers don't extend Provider_OpenAI_Compatible.
	 *
	 * Verifies that existing providers maintain their original implementation
	 * and don't extend the new base class.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function test_existing_providers_unchanged() {
		$existing_providers = [
			Provider_Gemini::class,
			Provider_OpenAI::class,
			Provider_Anthropic::class,
		];

		foreach ( $existing_providers as $provider_class ) {
			$reflection = new ReflectionClass( $provider_class );
			$parent     = $reflection->getParentClass();

			// Existing providers should not have a parent class (they implement interface directly).
			$this->assertFalse(
				$parent,
				"{$provider_class} should not extend any base class (backward compatibility)"
			);

			// But they should still implement the interface.
			$this->assertTrue(
				$reflection->implementsInterface( AI_Provider::class ),
				"{$provider_class} must still implement AI_Provider interface"
			);
		}
	}
}
