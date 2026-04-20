<?php
/**
 * Admin Bar Module Tests
 *
 * Unit tests for the Admin Bar Module.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Unit\Modules\AdminBar;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AdminBar\Admin_Bar_Module;
use MeowSEO\Options;

/**
 * Admin Bar Module test case
 *
 * @since 1.0.0
 */
class Test_Admin_Bar_Module extends TestCase {

	/**
	 * Admin Bar Module instance
	 *
	 * @var Admin_Bar_Module
	 */
	private Admin_Bar_Module $module;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Manually require the file to ensure it's loaded
		require_once MEOWSEO_PATH . 'includes/modules/admin-bar/class-admin-bar-module.php';

		$this->options = new Options();
		$this->module = new Admin_Bar_Module( $this->options );
	}

	/**
	 * Test module instantiation
	 *
	 * @return void
	 */
	public function test_module_instantiation(): void {
		// Manually require the file to ensure it's loaded
		require_once MEOWSEO_PATH . 'includes/modules/admin-bar/class-admin-bar-module.php';
		
		$this->assertInstanceOf( Admin_Bar_Module::class, $this->module );
	}

	/**
	 * Test module ID
	 *
	 * @return void
	 */
	public function test_module_id(): void {
		$this->assertEquals( 'admin-bar', $this->module->get_id() );
	}

	/**
	 * Test module name
	 *
	 * @return void
	 */
	public function test_module_name(): void {
		$this->assertEquals( 'Admin Bar Module', $this->module->get_name() );
	}

	/**
	 * Test module version
	 *
	 * @return void
	 */
	public function test_module_version(): void {
		$this->assertEquals( '1.0.0', $this->module->get_version() );
	}

	/**
	 * Test module is enabled
	 *
	 * @return void
	 */
	public function test_module_is_enabled(): void {
		$this->assertTrue( $this->module->is_enabled() );
	}

	/**
	 * Test score color for red range (0-49)
	 *
	 * Validates: Requirement 7.2
	 *
	 * @return void
	 */
	public function test_score_color_red(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'get_score_color' );
		$method->setAccessible( true );

		// Test red range (0-49).
		$this->assertEquals( '#dc3545', $method->invoke( $this->module, 0 ) );
		$this->assertEquals( '#dc3545', $method->invoke( $this->module, 25 ) );
		$this->assertEquals( '#dc3545', $method->invoke( $this->module, 49 ) );
	}

	/**
	 * Test score color for orange range (50-79)
	 *
	 * Validates: Requirement 7.2
	 *
	 * @return void
	 */
	public function test_score_color_orange(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'get_score_color' );
		$method->setAccessible( true );

		// Test orange range (50-79).
		$this->assertEquals( '#fd7e14', $method->invoke( $this->module, 50 ) );
		$this->assertEquals( '#fd7e14', $method->invoke( $this->module, 65 ) );
		$this->assertEquals( '#fd7e14', $method->invoke( $this->module, 79 ) );
	}

	/**
	 * Test score color for green range (80-100)
	 *
	 * Validates: Requirement 7.2
	 *
	 * @return void
	 */
	public function test_score_color_green(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'get_score_color' );
		$method->setAccessible( true );

		// Test green range (80-100).
		$this->assertEquals( '#28a745', $method->invoke( $this->module, 80 ) );
		$this->assertEquals( '#28a745', $method->invoke( $this->module, 90 ) );
		$this->assertEquals( '#28a745', $method->invoke( $this->module, 100 ) );
	}

	/**
	 * Test boot method does not error
	 *
	 * @return void
	 */
	public function test_boot_method(): void {
		// Boot should not throw an exception.
		try {
			$this->module->boot();
			$this->assertTrue( true );
		} catch ( \Exception $e ) {
			$this->fail( 'boot() threw an exception: ' . $e->getMessage() );
		}
	}
}
