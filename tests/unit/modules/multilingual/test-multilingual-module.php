<?php
/**
 * Tests for Multilingual_Module
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Unit\Modules\Multilingual;

use MeowSEO\Modules\Multilingual\Multilingual_Module;
use MeowSEO\Options;
use WP_UnitTestCase;

/**
 * Test Multilingual_Module class
 */
class Test_Multilingual_Module extends WP_UnitTestCase {

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
	public function setUp(): void {
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
		$post_id = self::factory()->post->create();
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
		$post_id = self::factory()->post->create();

		// Set some language-suffixed metadata.
		update_post_meta( $post_id, 'meowseo_title_en', 'English Title' );
		update_post_meta( $post_id, 'meowseo_description_en', 'English Description' );

		$metadata = $this->module->get_translated_metadata( $post_id, 'en' );

		$this->assertIsArray( $metadata );
		$this->assertEquals( 'English Title', $metadata['title'] );
		$this->assertEquals( 'English Description', $metadata['description'] );
	}

	/**
	 * Test get translated metadata empty
	 */
	public function test_get_translated_metadata_empty(): void {
		$post_id = self::factory()->post->create();
		$metadata = $this->module->get_translated_metadata( $post_id, 'es' );

		$this->assertIsArray( $metadata );
		$this->assertEmpty( $metadata );
	}

	/**
	 * Test sync schema translations
	 */
	public function test_sync_schema_translations(): void {
		$post_id = self::factory()->post->create();
		$schema_data = array( 'type' => 'Article', 'name' => 'Test' );

		// Set schema on primary post.
		update_post_meta( $post_id, 'meowseo_schema', $schema_data );

		// Sync schema.
		$this->module->sync_schema_translations( $post_id );

		// Verify schema is stored (even though no translations exist).
		$stored_schema = get_post_meta( $post_id, 'meowseo_schema', true );
		$this->assertEquals( $schema_data, $stored_schema );
	}

	/**
	 * Test boot method
	 */
	public function test_boot(): void {
		$this->module->boot();

		// Verify hooks are registered.
		$this->assertTrue( has_action( 'wp_head', array( $this->module, 'output_hreflang_tags' ) ) );
		$this->assertTrue( has_action( 'save_post', array( $this->module, 'invalidate_cache' ) ) );
	}
}
