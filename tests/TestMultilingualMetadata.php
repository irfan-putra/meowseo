<?php
/**
 * Tests for Multilingual_Module per-language metadata storage
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use MeowSEO\Modules\Multilingual\Multilingual_Module;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Test Multilingual_Module per-language metadata storage
 */
class TestMultilingualMetadata extends TestCase {

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
	 * Test get_translated_metadata returns empty array for non-existent metadata
	 */
	public function test_get_translated_metadata_empty(): void {
		$post_id = 123;
		$metadata = $this->module->get_translated_metadata( $post_id, 'en' );

		$this->assertIsArray( $metadata );
		$this->assertEmpty( $metadata );
	}

	/**
	 * Test get_translated_metadata returns array type
	 */
	public function test_get_translated_metadata_returns_array(): void {
		$post_id = 456;
		$metadata = $this->module->get_translated_metadata( $post_id, 'es' );

		$this->assertIsArray( $metadata );
	}

	/**
	 * Test sync_schema_translations with empty translations
	 */
	public function test_sync_schema_translations_empty(): void {
		$post_id = 789;

		// Should not throw an error.
		try {
			$this->module->sync_schema_translations( $post_id );
			$this->assertTrue( true );
		} catch ( \Exception $e ) {
			$this->fail( 'sync_schema_translations threw an exception: ' . $e->getMessage() );
		}
	}

	/**
	 * Test get_translations returns array
	 */
	public function test_get_translations_returns_array(): void {
		$post_id = 999;
		$translations = $this->module->get_translations( $post_id );

		$this->assertIsArray( $translations );
		$this->assertNotEmpty( $translations );
	}

	/**
	 * Test get_translations includes post ID
	 */
	public function test_get_translations_includes_post_id(): void {
		$post_id = 555;
		$translations = $this->module->get_translations( $post_id );

		// Should contain the post ID in the values.
		$this->assertContains( $post_id, $translations );
	}

	/**
	 * Test get_default_language returns string
	 */
	public function test_get_default_language_returns_string(): void {
		$language = $this->module->get_default_language();

		$this->assertIsString( $language );
		$this->assertNotEmpty( $language );
	}

	/**
	 * Test get_current_language returns string
	 */
	public function test_get_current_language_returns_string(): void {
		$language = $this->module->get_current_language();

		$this->assertIsString( $language );
		$this->assertNotEmpty( $language );
	}

	/**
	 * Test detect_translation_plugin returns null or string
	 */
	public function test_detect_translation_plugin_returns_valid_type(): void {
		$result = $this->module->detect_translation_plugin();

		// Should return null or a string.
		$this->assertTrue( is_null( $result ) || is_string( $result ) );
	}

	/**
	 * Test module ID
	 */
	public function test_module_id(): void {
		$this->assertEquals( 'multilingual', $this->module->get_id() );
	}
}
