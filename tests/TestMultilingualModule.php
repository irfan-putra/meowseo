<?php
/**
 * Tests for Multilingual_Module
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use MeowSEO\Modules\Multilingual\Multilingual_Module;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Test Multilingual_Module class
 */
class TestMultilingualModule extends TestCase {

	/**
	 * Module instance
	 *
	 * @var Multilingual_Module
	 */
	private Multilingual_Module $module;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = new Options();
		$this->module = new Multilingual_Module( $this->options );
	}

	/**
	 * Test module ID
	 */
	public function test_get_id(): void {
		$this->assertEquals( 'multilingual', $this->module->get_id() );
	}

	/**
	 * Test detect translation plugin when none is active
	 */
	public function test_detect_translation_plugin_none(): void {
		$result = $this->module->detect_translation_plugin();
		$this->assertNull( $result );
	}

	/**
	 * Test get default language fallback
	 */
	public function test_get_default_language_fallback(): void {
		$language = $this->module->get_default_language();
		// Should return first 2 chars of locale.
		$this->assertIsString( $language );
		$this->assertEquals( 2, strlen( $language ) );
	}

	/**
	 * Test get current language fallback
	 */
	public function test_get_current_language_fallback(): void {
		$language = $this->module->get_current_language();
		// Should return first 2 chars of locale.
		$this->assertIsString( $language );
		$this->assertEquals( 2, strlen( $language ) );
	}

	/**
	 * Test get translations without plugin
	 */
	public function test_get_translations_no_plugin(): void {
		// Create a mock post ID.
		$post_id = 123;

		$translations = $this->module->get_translations( $post_id );

		// Should return current language with post ID.
		$this->assertIsArray( $translations );
		$this->assertNotEmpty( $translations );
		$this->assertContains( $post_id, $translations );
	}

	/**
	 * Test get translated metadata
	 */
	public function test_get_translated_metadata(): void {
		$post_id = 123;

		// Mock metadata retrieval.
		$metadata = $this->module->get_translated_metadata( $post_id, 'en' );

		$this->assertIsArray( $metadata );
	}

	/**
	 * Test get translated metadata empty
	 */
	public function test_get_translated_metadata_empty(): void {
		$post_id = 123;
		$metadata = $this->module->get_translated_metadata( $post_id, 'es' );

		$this->assertIsArray( $metadata );
		$this->assertEmpty( $metadata );
	}

	/**
	 * Test sync schema translations
	 */
	public function test_sync_schema_translations(): void {
		$post_id = 123;
		$schema_data = array( 'type' => 'Article', 'name' => 'Test' );

		// This should not throw an error.
		$this->module->sync_schema_translations( $post_id );

		// Verify no errors occurred.
		$this->assertTrue( true );
	}

	/**
	 * Test boot method
	 */
	public function test_boot(): void {
		$this->module->boot();

		// Verify module boots without errors.
		$this->assertTrue( true );
	}

	/**
	 * Test get translations caching
	 */
	public function test_detect_translation_plugin_caching(): void {
		// First call.
		$result1 = $this->module->detect_translation_plugin();

		// Second call should return same result (cached).
		$result2 = $this->module->detect_translation_plugin();

		$this->assertEquals( $result1, $result2 );
	}
}
