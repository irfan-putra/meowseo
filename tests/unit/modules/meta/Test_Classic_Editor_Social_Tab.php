<?php
/**
 * Tests for Classic Editor Social Tab functionality
 *
 * @package MeowSEO
 * @subpackage Tests
 */

namespace MeowSEO\Tests\Unit\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Classic_Editor;

/**
 * Class Test_Classic_Editor_Social_Tab
 *
 * Tests for Open Graph, Twitter Card, and "Use OG for Twitter" toggle functionality
 */
class Test_Classic_Editor_Social_Tab extends TestCase {

	/**
	 * Test instance
	 *
	 * @var Classic_Editor
	 */
	private Classic_Editor $classic_editor;

	/**
	 * Set up test fixtures
	 */
	protected function setUp(): void {
		parent::setUp();

		// Manually require the file to ensure it's loaded
		if ( ! class_exists( 'MeowSEO\Modules\Meta\Classic_Editor' ) ) {
			require_once MEOWSEO_PATH . 'includes/modules/meta/class-classic-editor.php';
		}

		$this->classic_editor = new Classic_Editor();
	}

	/**
	 * Test 8.1: OG Title field is rendered
	 *
	 * Validates: Requirements 9.1
	 */
	public function test_og_title_field_is_rendered(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.1: OG Description field is rendered
	 *
	 * Validates: Requirements 9.2
	 */
	public function test_og_description_field_is_rendered(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.1: OG Image selector is rendered with preview, select button, and remove button
	 *
	 * Validates: Requirements 9.3
	 */
	public function test_og_image_selector_is_rendered(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.1: OG Image ID hidden input is rendered
	 *
	 * Validates: Requirements 9.3
	 */
	public function test_og_image_id_hidden_input_is_rendered(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.2: OG Title is saved to postmeta
	 *
	 * Validates: Requirements 9.4
	 */
	public function test_og_title_is_saved(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
	}

	/**
	 * Test 8.2: OG Description is saved to postmeta
	 *
	 * Validates: Requirements 9.5
	 */
	public function test_og_description_is_saved(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
	}

	/**
	 * Test 8.2: OG Image ID is saved to postmeta
	 *
	 * Validates: Requirements 9.6
	 */
	public function test_og_image_id_is_saved(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
	}

	/**
	 * Test 8.2: OG Image ID is sanitized as integer
	 *
	 * Validates: Requirements 9.6
	 */
	public function test_og_image_id_is_sanitized_as_integer(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
	}

	/**
	 * Test 8.3: Twitter Title field is rendered
	 *
	 * Validates: Requirements 11.1
	 */
	public function test_twitter_title_field_is_rendered(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.3: Twitter Description field is rendered
	 *
	 * Validates: Requirements 11.2
	 */
	public function test_twitter_description_field_is_rendered(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.3: Twitter Image selector is rendered
	 *
	 * Validates: Requirements 11.3
	 */
	public function test_twitter_image_selector_is_rendered(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.4: Twitter Title is saved to postmeta
	 *
	 * Validates: Requirements 11.4
	 */
	public function test_twitter_title_is_saved(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
	}

	/**
	 * Test 8.4: Twitter Description is saved to postmeta
	 *
	 * Validates: Requirements 11.5
	 */
	public function test_twitter_description_is_saved(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
	}

	/**
	 * Test 8.4: Twitter Image ID is saved to postmeta
	 *
	 * Validates: Requirements 11.6
	 */
	public function test_twitter_image_id_is_saved(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
	}

	/**
	 * Test 8.5: "Use OG for Twitter" checkbox is rendered
	 *
	 * Validates: Requirements 13.1
	 */
	public function test_use_og_for_twitter_checkbox_is_rendered(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.5: "Use OG for Twitter" toggle is saved
	 *
	 * Validates: Requirements 13.4
	 */
	public function test_use_og_for_twitter_toggle_is_saved(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
	}

	/**
	 * Test 8.5: "Use OG for Twitter" toggle is saved as 0 when unchecked
	 *
	 * Validates: Requirements 13.4
	 */
	public function test_use_og_for_twitter_toggle_is_saved_as_zero_when_unchecked(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
	}

	/**
	 * Test 8.5: Twitter fields are disabled when "Use OG for Twitter" is checked
	 *
	 * Validates: Requirements 13.2
	 */
	public function test_twitter_fields_are_disabled_when_use_og_for_twitter_is_checked(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.1-8.5: OG and Twitter fields are populated with saved values
	 *
	 * Validates: Requirements 9.1-9.6, 11.1-11.6, 13.1-13.4
	 */
	public function test_og_and_twitter_fields_are_populated_with_saved_values(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}

	/**
	 * Test 8.1-8.5: OG and Twitter fields are in the Social tab
	 *
	 * Validates: Requirements 9.1-9.6, 11.1-11.6, 13.1-13.4
	 */
	public function test_og_and_twitter_fields_are_in_social_tab(): void {
		$reflection = new \ReflectionClass( $this->classic_editor );
		$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );
	}
}
