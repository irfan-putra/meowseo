<?php
/**
 * Tests for Multilingual_Module hreflang tag generation
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use MeowSEO\Modules\Multilingual\Multilingual_Module;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Test Multilingual_Module hreflang tag generation
 */
class TestMultilingualHreflang extends TestCase {

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
	 * Test output_hreflang_tags does not output on non-singular pages
	 */
	public function test_output_hreflang_tags_not_singular(): void {
		// Capture output.
		ob_start();
		$this->module->output_hreflang_tags();
		$output = ob_get_clean();

		// Should not output anything on non-singular pages.
		$this->assertEmpty( $output );
	}

	/**
	 * Test get_default_language returns valid language code
	 */
	public function test_get_default_language_returns_valid_code(): void {
		$language = $this->module->get_default_language();

		// Should return a 2-character language code.
		$this->assertIsString( $language );
		$this->assertEquals( 2, strlen( $language ) );
		$this->assertTrue( ctype_lower( $language ) );
	}

	/**
	 * Test get_current_language returns valid language code
	 */
	public function test_get_current_language_returns_valid_code(): void {
		$language = $this->module->get_current_language();

		// Should return a 2-character language code.
		$this->assertIsString( $language );
		$this->assertEquals( 2, strlen( $language ) );
		$this->assertTrue( ctype_lower( $language ) );
	}

	/**
	 * Test get_translations returns array with current language
	 */
	public function test_get_translations_returns_array(): void {
		$post_id = 123;
		$translations = $this->module->get_translations( $post_id );

		// Should return an array.
		$this->assertIsArray( $translations );

		// Should contain at least the current language.
		$this->assertNotEmpty( $translations );

		// Should contain the post ID.
		$this->assertContains( $post_id, $translations );
	}

	/**
	 * Test get_translated_metadata returns array
	 */
	public function test_get_translated_metadata_returns_array(): void {
		$post_id = 123;
		$metadata = $this->module->get_translated_metadata( $post_id, 'en' );

		// Should return an array.
		$this->assertIsArray( $metadata );
	}

	/**
	 * Test sync_schema_translations does not throw error
	 */
	public function test_sync_schema_translations_no_error(): void {
		$post_id = 123;

		// Should not throw an error.
		try {
			$this->module->sync_schema_translations( $post_id );
			$this->assertTrue( true );
		} catch ( \Exception $e ) {
			$this->fail( 'sync_schema_translations threw an exception: ' . $e->getMessage() );
		}
	}

	/**
	 * Test detect_translation_plugin returns null when no plugin active
	 */
	public function test_detect_translation_plugin_returns_null(): void {
		$result = $this->module->detect_translation_plugin();

		// Should return null when no plugin is active.
		$this->assertNull( $result );
	}

	/**
	 * Test detect_translation_plugin caches result
	 */
	public function test_detect_translation_plugin_caches_result(): void {
		// First call.
		$result1 = $this->module->detect_translation_plugin();

		// Second call should return same result (cached).
		$result2 = $this->module->detect_translation_plugin();

		$this->assertEquals( $result1, $result2 );
	}

	/**
	 * Test boot method registers hooks
	 */
	public function test_boot_registers_hooks(): void {
		$this->module->boot();

		// Verify hooks are registered (has_action returns priority or false).
		$this->assertNotFalse( has_action( 'wp_head', array( $this->module, 'output_hreflang_tags' ) ) );
		$this->assertNotFalse( has_action( 'save_post', array( $this->module, 'invalidate_cache' ) ) );
	}

	/**
	 * Test module ID is correct
	 */
	public function test_module_id_is_multilingual(): void {
		$this->assertEquals( 'multilingual', $this->module->get_id() );
	}
}
