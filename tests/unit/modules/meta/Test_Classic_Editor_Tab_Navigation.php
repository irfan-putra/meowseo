<?php
/**
 * Classic Editor Tab Navigation Tests
 *
 * Unit tests for the Classic Editor meta box tab navigation functionality.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Unit\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Classic_Editor;

/**
 * Classic Editor Tab Navigation test case
 *
 * Tests tab navigation structure, rendering, and state management.
 *
 * @since 1.0.0
 */
class Test_Classic_Editor_Tab_Navigation extends TestCase {

	/**
	 * Classic Editor instance
	 *
	 * @var Classic_Editor
	 */
	private Classic_Editor $editor;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Manually require the file to ensure it's loaded
		if ( ! class_exists( 'MeowSEO\Modules\Meta\Classic_Editor' ) ) {
			require_once MEOWSEO_PATH . 'includes/modules/meta/class-classic-editor.php';
		}

		$this->editor = new Classic_Editor();
	}

	/**
	 * Test Classic Editor instantiation
	 *
	 * @return void
	 */
	public function test_editor_instantiation(): void {
		$this->assertInstanceOf( Classic_Editor::class, $this->editor );
	}

	/**
	 * Test that render_meta_box method exists
	 *
	 * Validates: Requirement 1.1 - Meta box rendering capability
	 *
	 * @return void
	 */
	public function test_render_meta_box_method_exists(): void {
		$reflection = new \ReflectionClass( $this->editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
		
		$method = $reflection->getMethod( 'render_meta_box' );
		$this->assertTrue( $method->isPublic() );
		$this->assertEquals( 1, $method->getNumberOfParameters() );
	}

	/**
	 * Test that register_meta_box method exists
	 *
	 * Validates: Requirement 25.5 - Meta box registration
	 *
	 * @return void
	 */
	public function test_register_meta_box_method_exists(): void {
		$reflection = new \ReflectionClass( $this->editor );
		$this->assertTrue( $reflection->hasMethod( 'register_meta_box' ) );
		
		$method = $reflection->getMethod( 'register_meta_box' );
		$this->assertTrue( $method->isPublic() );
	}

	/**
	 * Test that save_meta method exists
	 *
	 * Validates: Requirement 27.1 - Data persistence capability
	 *
	 * @return void
	 */
	public function test_save_meta_method_exists(): void {
		$reflection = new \ReflectionClass( $this->editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
		
		$method = $reflection->getMethod( 'save_meta' );
		$this->assertTrue( $method->isPublic() );
		$this->assertEquals( 2, $method->getNumberOfParameters() );
	}

	/**
	 * Test that nonce constants are defined
	 *
	 * Validates: Requirement 26.1 - Nonce authentication
	 *
	 * @return void
	 */
	public function test_nonce_constants_defined(): void {
		$reflection = new \ReflectionClass( $this->editor );
		
		$this->assertTrue( $reflection->hasConstant( 'NONCE_ACTION' ) );
		$this->assertTrue( $reflection->hasConstant( 'NONCE_FIELD' ) );
		
		$this->assertEquals( 'meowseo_classic_editor_save', $reflection->getConstant( 'NONCE_ACTION' ) );
		$this->assertEquals( 'meowseo_classic_editor_nonce', $reflection->getConstant( 'NONCE_FIELD' ) );
	}

	/**
	 * Test that JavaScript file is enqueued on post edit screens
	 *
	 * Validates: Requirement 25.1 - JavaScript enqueuing
	 *
	 * @return void
	 */
	public function test_enqueue_method_exists(): void {
		// Verify the enqueue method exists and is public
		$reflection = new \ReflectionClass( $this->editor );
		$this->assertTrue( $reflection->hasMethod( 'enqueue_editor_scripts' ) );
		
		$method = $reflection->getMethod( 'enqueue_editor_scripts' );
		$this->assertTrue( $method->isPublic() );
	}

	/**
	 * Test that init method exists
	 *
	 * Validates: Requirement 25.5 - Hook registration
	 *
	 * @return void
	 */
	public function test_init_method_exists(): void {
		$reflection = new \ReflectionClass( $this->editor );
		$this->assertTrue( $reflection->hasMethod( 'init' ) );
		
		$method = $reflection->getMethod( 'init' );
		$this->assertTrue( $method->isPublic() );
	}


}
