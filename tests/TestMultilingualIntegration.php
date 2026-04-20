<?php
/**
 * Integration tests for Multilingual_Module with Module Manager
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use MeowSEO\Module_Manager;
use MeowSEO\Options;
use MeowSEO\Modules\Multilingual\Multilingual_Module;
use PHPUnit\Framework\TestCase;

/**
 * Test Multilingual_Module integration with Module Manager
 */
class TestMultilingualIntegration extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Module Manager instance
	 *
	 * @var Module_Manager
	 */
	private Module_Manager $module_manager;

	/**
	 * Set up test
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = new Options();
		$this->module_manager = new Module_Manager( $this->options );
	}

	/**
	 * Test multilingual module can be loaded by module manager
	 */
	public function test_multilingual_module_can_be_loaded(): void {
		// Boot the module manager.
		$this->module_manager->boot();

		// Check if multilingual module is active.
		$is_active = $this->module_manager->is_active( 'multilingual' );

		// Should be active if enabled in options.
		$this->assertTrue( $is_active );
	}

	/**
	 * Test multilingual module is registered in module manager
	 */
	public function test_multilingual_module_is_registered(): void {
		// Get the multilingual module.
		$this->module_manager->boot();
		$module = $this->module_manager->get_module( 'multilingual' );

		// Should be an instance of Multilingual_Module.
		$this->assertInstanceOf( Multilingual_Module::class, $module );
	}

	/**
	 * Test multilingual module implements Module interface
	 */
	public function test_multilingual_module_implements_interface(): void {
		$module = new Multilingual_Module( $this->options );

		// Should implement Module interface.
		$this->assertInstanceOf( \MeowSEO\Contracts\Module::class, $module );
	}

	/**
	 * Test multilingual module has correct ID
	 */
	public function test_multilingual_module_has_correct_id(): void {
		$module = new Multilingual_Module( $this->options );

		$this->assertEquals( 'multilingual', $module->get_id() );
	}

	/**
	 * Test multilingual module can be booted
	 */
	public function test_multilingual_module_can_be_booted(): void {
		$module = new Multilingual_Module( $this->options );

		// Should not throw an error.
		try {
			$module->boot();
			$this->assertTrue( true );
		} catch ( \Exception $e ) {
			$this->fail( 'Module boot threw an exception: ' . $e->getMessage() );
		}
	}

	/**
	 * Test multilingual module methods are callable
	 */
	public function test_multilingual_module_methods_are_callable(): void {
		$module = new Multilingual_Module( $this->options );

		// All public methods should be callable.
		$this->assertTrue( method_exists( $module, 'detect_translation_plugin' ) );
		$this->assertTrue( method_exists( $module, 'get_translations' ) );
		$this->assertTrue( method_exists( $module, 'get_default_language' ) );
		$this->assertTrue( method_exists( $module, 'get_current_language' ) );
		$this->assertTrue( method_exists( $module, 'output_hreflang_tags' ) );
		$this->assertTrue( method_exists( $module, 'get_translated_metadata' ) );
		$this->assertTrue( method_exists( $module, 'sync_schema_translations' ) );
	}

	/**
	 * Test multilingual module is in enabled modules by default
	 */
	public function test_multilingual_module_is_enabled_by_default(): void {
		$enabled_modules = $this->options->get_enabled_modules();

		// Multilingual module should be in the enabled modules list.
		$this->assertContains( 'multilingual', $enabled_modules );
	}
}
