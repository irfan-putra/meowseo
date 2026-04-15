<?php
/**
 * Tests for Gutenberg_Assets class
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use MeowSEO\Modules\Meta\Gutenberg_Assets;
use PHPUnit\Framework\TestCase;

/**
 * Class GutenbergAssetsTest
 *
 * Tests asset enqueuing, script localization, and postmeta registration
 * for the Gutenberg editor integration.
 */
class GutenbergAssetsTest extends TestCase {

	/**
	 * Gutenberg_Assets instance
	 *
	 * @var Gutenberg_Assets
	 */
	private Gutenberg_Assets $assets;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assets = new Gutenberg_Assets();
	}

	/**
	 * Test that init() method exists and is callable
	 *
	 * @covers Gutenberg_Assets::init
	 */
	public function test_init_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->assets, 'init' ),
			'init method should exist'
		);
		$this->assertTrue(
			is_callable( array( $this->assets, 'init' ) ),
			'init method should be callable'
		);
	}

	/**
	 * Test that enqueue_editor_assets() method exists and is callable
	 *
	 * @covers Gutenberg_Assets::enqueue_editor_assets
	 */
	public function test_enqueue_editor_assets_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->assets, 'enqueue_editor_assets' ),
			'enqueue_editor_assets method should exist'
		);
		$this->assertTrue(
			is_callable( array( $this->assets, 'enqueue_editor_assets' ) ),
			'enqueue_editor_assets method should be callable'
		);
	}

	/**
	 * Test that register_postmeta() method exists and is callable
	 *
	 * @covers Gutenberg_Assets::register_postmeta
	 */
	public function test_register_postmeta_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->assets, 'register_postmeta' ),
			'register_postmeta method should exist'
		);
		$this->assertTrue(
			is_callable( array( $this->assets, 'register_postmeta' ) ),
			'register_postmeta method should be callable'
		);
	}

	/**
	 * Test that class has correct structure
	 *
	 * @covers Gutenberg_Assets
	 */
	public function test_class_structure(): void {
		$this->assertInstanceOf(
			Gutenberg_Assets::class,
			$this->assets,
			'Should be instance of Gutenberg_Assets'
		);
	}

	/**
	 * Test that get_meta_keys returns expected structure
	 *
	 * Uses reflection to test private method
	 */
	public function test_get_meta_keys_returns_array(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		$this->assertIsArray( $meta_keys, 'get_meta_keys should return an array' );
		$this->assertNotEmpty( $meta_keys, 'get_meta_keys should not be empty' );
	}

	/**
	 * Test that all expected postmeta keys are defined
	 */
	public function test_all_postmeta_keys_defined(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		$expected_keys = array(
			'_meowseo_title',
			'_meowseo_description',
			'_meowseo_focus_keyword',
			'_meowseo_direct_answer',
			'_meowseo_og_title',
			'_meowseo_og_description',
			'_meowseo_og_image_id',
			'_meowseo_twitter_title',
			'_meowseo_twitter_description',
			'_meowseo_twitter_image_id',
			'_meowseo_use_og_for_twitter',
			'_meowseo_schema_type',
			'_meowseo_schema_config',
			'_meowseo_robots_noindex',
			'_meowseo_robots_nofollow',
			'_meowseo_canonical',
			'_meowseo_gsc_last_submit',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey(
				$key,
				$meta_keys,
				"Meta keys should include {$key}"
			);
		}
	}

	/**
	 * Test that meta keys have correct configuration structure
	 */
	public function test_meta_keys_have_correct_structure(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		foreach ( $meta_keys as $key => $config ) {
			$this->assertArrayHasKey(
				'type',
				$config,
				"Meta key {$key} should have 'type' defined"
			);
			$this->assertArrayHasKey(
				'description',
				$config,
				"Meta key {$key} should have 'description' defined"
			);
			$this->assertArrayHasKey(
				'default',
				$config,
				"Meta key {$key} should have 'default' defined"
			);
		}
	}

	/**
	 * Test that string meta keys have correct type
	 */
	public function test_string_meta_keys_have_correct_type(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		$string_keys = array(
			'_meowseo_title',
			'_meowseo_description',
			'_meowseo_focus_keyword',
			'_meowseo_direct_answer',
		);

		foreach ( $string_keys as $key ) {
			$this->assertEquals(
				'string',
				$meta_keys[ $key ]['type'],
				"Meta key {$key} should have type 'string'"
			);
			$this->assertEquals(
				'',
				$meta_keys[ $key ]['default'],
				"Meta key {$key} should have empty string default"
			);
		}
	}

	/**
	 * Test that integer meta keys have correct type
	 */
	public function test_integer_meta_keys_have_correct_type(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		$integer_keys = array(
			'_meowseo_og_image_id',
			'_meowseo_twitter_image_id',
		);

		foreach ( $integer_keys as $key ) {
			$this->assertEquals(
				'integer',
				$meta_keys[ $key ]['type'],
				"Meta key {$key} should have type 'integer'"
			);
			$this->assertEquals(
				0,
				$meta_keys[ $key ]['default'],
				"Meta key {$key} should have 0 default"
			);
		}
	}

	/**
	 * Test that boolean meta keys have correct type
	 */
	public function test_boolean_meta_keys_have_correct_type(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		$boolean_keys = array(
			'_meowseo_use_og_for_twitter',
			'_meowseo_robots_noindex',
			'_meowseo_robots_nofollow',
		);

		foreach ( $boolean_keys as $key ) {
			$this->assertEquals(
				'boolean',
				$meta_keys[ $key ]['type'],
				"Meta key {$key} should have type 'boolean'"
			);
			$this->assertFalse(
				$meta_keys[ $key ]['default'],
				"Meta key {$key} should have false default"
			);
		}
	}

	/**
	 * Test schema configuration JSON validation.
	 *
	 * Requirement 18.8: Validate schema configuration JSON before storage
	 *
	 * @covers Gutenberg_Assets::sanitize_schema_config
	 */
	public function test_schema_config_validation(): void {
		// Test valid JSON.
		$valid_json = '{"type":"Article","headline":"Test"}';
		$sanitized  = $this->assets->sanitize_schema_config( $valid_json );
		$this->assertNotEmpty( $sanitized, 'Valid JSON should not be empty after sanitization' );
		$decoded    = json_decode( $sanitized, true );
		$this->assertIsArray( $decoded, 'Sanitized JSON should be decodable to array' );

		// Test invalid JSON.
		$invalid_json = '{invalid json}';
		$sanitized    = $this->assets->sanitize_schema_config( $invalid_json );
		$this->assertEmpty( $sanitized, 'Invalid JSON should return empty string' );

		// Test non-JSON string.
		$non_json  = 'not json at all';
		$sanitized = $this->assets->sanitize_schema_config( $non_json );
		$this->assertEmpty( $sanitized, 'Non-JSON string should return empty string' );

		// Test empty string.
		$sanitized = $this->assets->sanitize_schema_config( '' );
		$this->assertEmpty( $sanitized, 'Empty string should return empty string' );

		// Test JSON with XSS attempt.
		$xss_json  = '{"type":"Article","headline":"<script>alert(1)</script>"}';
		$sanitized = $this->assets->sanitize_schema_config( $xss_json );
		$this->assertNotEmpty( $sanitized, 'JSON with script tags should still be valid JSON' );
		$decoded   = json_decode( $sanitized, true );
		$this->assertIsArray( $decoded, 'JSON with script tags should be decodable' );
		// Note: The script tags are preserved in JSON but will be escaped when output
	}

	/**
	 * Test that all meta keys have sanitize_callback defined.
	 *
	 * Requirement 18.6: Sanitize all user input before storage
	 *
	 * @covers Gutenberg_Assets::get_meta_keys
	 */
	public function test_all_meta_keys_have_sanitize_callback(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		foreach ( $meta_keys as $key => $config ) {
			$this->assertArrayHasKey(
				'sanitize_callback',
				$config,
				"Meta key {$key} should have 'sanitize_callback' defined"
			);
			$this->assertNotEmpty(
				$config['sanitize_callback'],
				"Meta key {$key} sanitize_callback should not be empty"
			);
		}
	}

	/**
	 * Test that text fields use appropriate sanitization.
	 *
	 * Requirement 18.6: Sanitize all user input before storage using sanitize_text_field
	 *
	 * @covers Gutenberg_Assets::get_meta_keys
	 */
	public function test_text_fields_use_sanitize_text_field(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		$text_field_keys = array(
			'_meowseo_title',
			'_meowseo_focus_keyword',
			'_meowseo_og_title',
			'_meowseo_twitter_title',
			'_meowseo_schema_type',
			'_meowseo_gsc_last_submit',
		);

		foreach ( $text_field_keys as $key ) {
			$this->assertEquals(
				'sanitize_text_field',
				$meta_keys[ $key ]['sanitize_callback'],
				"Meta key {$key} should use sanitize_text_field"
			);
		}
	}

	/**
	 * Test that URL fields use esc_url_raw sanitization.
	 *
	 * Requirement 18.6: Sanitize all user input before storage using esc_url_raw
	 *
	 * @covers Gutenberg_Assets::get_meta_keys
	 */
	public function test_url_fields_use_esc_url_raw(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		$this->assertEquals(
			'esc_url_raw',
			$meta_keys['_meowseo_canonical']['sanitize_callback'],
			'Canonical URL should use esc_url_raw sanitization'
		);
	}

	/**
	 * Test that schema config uses custom sanitization.
	 *
	 * Requirement 18.8: Validate schema configuration JSON before storage
	 *
	 * @covers Gutenberg_Assets::get_meta_keys
	 */
	public function test_schema_config_uses_custom_sanitization(): void {
		$reflection = new \ReflectionClass( $this->assets );
		$method = $reflection->getMethod( 'get_meta_keys' );
		$method->setAccessible( true );

		$meta_keys = $method->invoke( $this->assets );

		$this->assertIsArray(
			$meta_keys['_meowseo_schema_config']['sanitize_callback'],
			'Schema config should use custom sanitization callback'
		);
		$this->assertEquals(
			array( $this->assets, 'sanitize_schema_config' ),
			$meta_keys['_meowseo_schema_config']['sanitize_callback'],
			'Schema config should use sanitize_schema_config method'
		);
	}
}
