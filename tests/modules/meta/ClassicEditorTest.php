<?php
/**
 * Tests for Classic_Editor class
 *
 * @package MeowSEO
 * @subpackage Tests
 */

namespace MeowSEO\Tests\Modules\Meta;

use MeowSEO\Modules\Meta\Classic_Editor;
use PHPUnit\Framework\TestCase;

/**
 * Class ClassicEditorTest
 *
 * Tests for the Classic_Editor meta box implementation
 */
class ClassicEditorTest extends TestCase {

	/**
	 * Instance of Classic_Editor
	 *
	 * @var Classic_Editor
	 */
	private $classic_editor;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->classic_editor = new Classic_Editor();
	}

	/**
	 * Test that init() method exists and is callable
	 */
	public function test_init_method_exists() {
		$this->assertTrue(
			method_exists( $this->classic_editor, 'init' ),
			'init method should exist'
		);
		$this->assertTrue(
			is_callable( array( $this->classic_editor, 'init' ) ),
			'init method should be callable'
		);
	}

	/**
	 * Test that register_meta_box method exists and is callable
	 */
	public function test_register_meta_box_method_exists() {
		$this->assertTrue(
			method_exists( $this->classic_editor, 'register_meta_box' ),
			'register_meta_box method should exist'
		);
		$this->assertTrue(
			is_callable( array( $this->classic_editor, 'register_meta_box' ) ),
			'register_meta_box method should be callable'
		);
	}

	/**
	 * Test that enqueue_editor_scripts method exists and is callable
	 */
	public function test_enqueue_editor_scripts_method_exists() {
		$this->assertTrue(
			method_exists( $this->classic_editor, 'enqueue_editor_scripts' ),
			'enqueue_editor_scripts method should exist'
		);
		$this->assertTrue(
			is_callable( array( $this->classic_editor, 'enqueue_editor_scripts' ) ),
			'enqueue_editor_scripts method should be callable'
		);
	}

	/**
	 * Test that render_meta_box method exists and is callable
	 */
	public function test_render_meta_box_method_exists() {
		$this->assertTrue(
			method_exists( $this->classic_editor, 'render_meta_box' ),
			'render_meta_box method should exist'
		);
		$this->assertTrue(
			is_callable( array( $this->classic_editor, 'render_meta_box' ) ),
			'render_meta_box method should be callable'
		);
	}

	/**
	 * Test that save_meta method exists and is callable
	 */
	public function test_save_meta_method_exists() {
		$this->assertTrue(
			method_exists( $this->classic_editor, 'save_meta' ),
			'save_meta method should exist'
		);
		$this->assertTrue(
			is_callable( array( $this->classic_editor, 'save_meta' ) ),
			'save_meta method should be callable'
		);
	}

	/**
	 * Test that NONCE_ACTION constant is defined
	 */
	public function test_nonce_action_constant_defined() {
		$this->assertTrue(
			defined( 'MeowSEO\Modules\Meta\Classic_Editor::NONCE_ACTION' ),
			'NONCE_ACTION constant should be defined'
		);
		$this->assertEquals(
			'meowseo_classic_editor_save',
			Classic_Editor::NONCE_ACTION,
			'NONCE_ACTION should have correct value'
		);
	}

	/**
	 * Test that NONCE_FIELD constant is defined
	 */
	public function test_nonce_field_constant_defined() {
		$this->assertTrue(
			defined( 'MeowSEO\Modules\Meta\Classic_Editor::NONCE_FIELD' ),
			'NONCE_FIELD constant should be defined'
		);
		$this->assertEquals(
			'meowseo_classic_editor_nonce',
			Classic_Editor::NONCE_FIELD,
			'NONCE_FIELD should have correct value'
		);
	}

	/**
	 * Test that class structure is correct
	 */
	public function test_class_structure() {
		$this->assertInstanceOf(
			Classic_Editor::class,
			$this->classic_editor,
			'Should be instance of Classic_Editor'
		);
	}
}
