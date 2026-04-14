<?php
/**
 * Meta_Module Test
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Meta_Module;
use MeowSEO\Options;
use MeowSEO\Contracts\Module;

/**
 * Test Meta_Module class
 */
class MetaModuleTest extends TestCase {
	/**
	 * Meta_Module instance
	 *
	 * @var Meta_Module
	 */
	private Meta_Module $module;

	/**
	 * Options instance
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
		$this->options = new Options();
		$this->module = new Meta_Module( $this->options );
	}

	/**
	 * Test Module interface implementation
	 *
	 * Validates: Requirements 1.1
	 *
	 * @return void
	 */
	public function test_module_interface(): void {
		$this->assertInstanceOf( Module::class, $this->module );
		$this->assertTrue( method_exists( $this->module, 'boot' ) );
		$this->assertTrue( method_exists( $this->module, 'get_id' ) );
	}

	/**
	 * Test get_id returns correct value
	 *
	 * Validates: Requirements 1.1
	 *
	 * @return void
	 */
	public function test_get_id(): void {
		$id = $this->module->get_id();
		$this->assertEquals( 'meta', $id );
		$this->assertIsString( $id );
	}

	/**
	 * Test boot method exists and is callable
	 *
	 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7
	 *
	 * @return void
	 */
	public function test_boot_method_exists(): void {
		$this->assertTrue( method_exists( $this->module, 'boot' ) );
		$this->assertTrue( is_callable( array( $this->module, 'boot' ) ) );
	}

	/**
	 * Test filter_document_title_parts returns empty array
	 *
	 * Validates: Requirements 1.3
	 *
	 * @return void
	 */
	public function test_filter_document_title_parts_returns_empty_array(): void {
		$parts = array(
			'title'  => 'Test Title',
			'tagline' => 'Test Tagline',
		);

		$result = $this->module->filter_document_title_parts( $parts );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test output_head_tags method exists and is callable
	 *
	 * Validates: Requirements 1.2
	 *
	 * @return void
	 */
	public function test_output_head_tags_method_exists(): void {
		$this->assertTrue( method_exists( $this->module, 'output_head_tags' ) );
		$this->assertTrue( is_callable( array( $this->module, 'output_head_tags' ) ) );
	}

	/**
	 * Test handle_save_post method exists and is callable
	 *
	 * Validates: Requirements 1.5
	 *
	 * @return void
	 */
	public function test_handle_save_post_method_exists(): void {
		$this->assertTrue( method_exists( $this->module, 'handle_save_post' ) );
		$this->assertTrue( is_callable( array( $this->module, 'handle_save_post' ) ) );
	}

	/**
	 * Test register_rest_fields method exists and is callable
	 *
	 * Validates: Requirements 1.6
	 *
	 * @return void
	 */
	public function test_register_rest_fields_method_exists(): void {
		$this->assertTrue( method_exists( $this->module, 'register_rest_fields' ) );
		$this->assertTrue( is_callable( array( $this->module, 'register_rest_fields' ) ) );
	}

	/**
	 * Test enqueue_block_editor_assets method exists and is callable
	 *
	 * Validates: Requirements 1.7
	 *
	 * @return void
	 */
	public function test_enqueue_block_editor_assets_method_exists(): void {
		$this->assertTrue( method_exists( $this->module, 'enqueue_block_editor_assets' ) );
		$this->assertTrue( is_callable( array( $this->module, 'enqueue_block_editor_assets' ) ) );
	}

	/**
	 * Test constructor accepts Options dependency
	 *
	 * Validates: Requirements 1.1
	 *
	 * @return void
	 */
	public function test_constructor_accepts_options_dependency(): void {
		$options = new Options();
		$module = new Meta_Module( $options );

		$this->assertInstanceOf( Meta_Module::class, $module );
	}
}
