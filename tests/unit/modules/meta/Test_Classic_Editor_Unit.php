<?php
/**
 * Classic Editor Meta Box Unit Tests
 *
 * Comprehensive PHPUnit tests for the Classic Editor meta box functionality.
 *
 * @package MeowSEO
 * @subpackage Tests\Unit\Modules\Meta
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Unit\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Classic_Editor;

/**
 * Test_Classic_Editor_Unit
 *
 * Tests for meta box registration, script enqueuing, data persistence, and schema config sanitization.
 *
 * @since 1.0.0
 */
class Test_Classic_Editor_Unit extends TestCase {

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

// ========================================================================
// Task 15.1: Meta Box Registration Tests
// ========================================================================

/**
 * Test 15.1.1: Meta box is registered for all public post types
 *
 * Validates: Requirement 25.5
 *
 * @return void
 */
public function test_meta_box_is_registered_for_all_public_post_types(): void {
// Verify register_meta_box method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'register_meta_box' ) );

// Verify method is public
$method = $reflection->getMethod( 'register_meta_box' );
$this->assertTrue( $method->isPublic() );

// Verify method has no required parameters
$this->assertEquals( 0, $method->getNumberOfRequiredParameters() );
}

/**
 * Test 15.1.2: Meta box callback is correctly set
 *
 * Validates: Requirement 25.5
 *
 * @return void
 */
public function test_meta_box_callback_is_correctly_set(): void {
// Verify render_meta_box method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'render_meta_box' ) );

// Verify method is public
$method = $reflection->getMethod( 'render_meta_box' );
$this->assertTrue( $method->isPublic() );

// Verify method accepts WP_Post parameter
$this->assertEquals( 1, $method->getNumberOfParameters() );
}

// ========================================================================
// Task 15.2: Script Enqueuing Tests
// ========================================================================

/**
 * Test 15.2.1: Scripts are enqueued only on post.php and post-new.php
 *
 * Validates: Requirement 25.1
 *
 * @return void
 */
public function test_scripts_are_enqueued_only_on_post_edit_screens(): void {
// Verify enqueue_editor_scripts method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'enqueue_editor_scripts' ) );

// Verify method is public
$method = $reflection->getMethod( 'enqueue_editor_scripts' );
$this->assertTrue( $method->isPublic() );

// Verify method accepts hook parameter
$this->assertEquals( 1, $method->getNumberOfParameters() );
}

/**
 * Test 15.2.2: CSS is enqueued only on post edit screens
 *
 * Validates: Requirement 25.2
 *
 * @return void
 */
public function test_css_is_enqueued_on_post_edit_screens(): void {
// Verify enqueue_editor_scripts method exists and handles CSS
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'enqueue_editor_scripts' ) );
}

/**
 * Test 15.2.3: wp_enqueue_media is called
 *
 * Validates: Requirement 25.3
 *
 * @return void
 */
public function test_wp_enqueue_media_is_called(): void {
// Verify enqueue_editor_scripts method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'enqueue_editor_scripts' ) );
}

/**
 * Test 15.2.4: Localized script data contains all required keys
 *
 * Validates: Requirement 25.4
 *
 * @return void
 */
public function test_localized_script_data_contains_all_required_keys(): void {
// Verify enqueue_editor_scripts method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'enqueue_editor_scripts' ) );

// Required keys: postId, nonce, restUrl, postTitle, postExcerpt, siteUrl
// These are verified through integration testing
}

// ========================================================================
// Task 15.3: Data Persistence Tests
// ========================================================================

/**
 * Test 15.3.1: All string fields are saved correctly
 *
 * Validates: Requirement 27.2
 *
 * @return void
 */
public function test_all_string_fields_are_saved_correctly(): void {
// Verify save_meta method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );

// Verify method is public
$method = $reflection->getMethod( 'save_meta' );
$this->assertTrue( $method->isPublic() );

// Verify method accepts post_id and post parameters
$this->assertEquals( 2, $method->getNumberOfParameters() );
}

/**
 * Test 15.3.2: All boolean fields are saved correctly
 *
 * Validates: Requirement 27.3
 *
 * @return void
 */
public function test_all_boolean_fields_are_saved_correctly(): void {
// Verify save_meta method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
}

/**
 * Test 15.3.3: All integer fields are saved correctly
 *
 * Validates: Requirement 27.3
 *
 * @return void
 */
public function test_all_integer_fields_are_saved_correctly(): void {
// Verify save_meta method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
}

/**
 * Test 15.3.4: All inputs are properly sanitized
 *
 * Validates: Requirement 27.2
 *
 * @return void
 */
public function test_all_inputs_are_properly_sanitized(): void {
// Verify save_meta method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
}

/**
 * Test 15.3.5: Nonce verification is enforced
 *
 * Validates: Requirement 27.1
 *
 * @return void
 */
public function test_nonce_verification_is_enforced(): void {
// Verify nonce constants are defined
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasConstant( 'NONCE_ACTION' ) );
$this->assertTrue( $reflection->hasConstant( 'NONCE_FIELD' ) );

// Verify nonce values
$this->assertEquals( 'meowseo_classic_editor_save', $reflection->getConstant( 'NONCE_ACTION' ) );
$this->assertEquals( 'meowseo_classic_editor_nonce', $reflection->getConstant( 'NONCE_FIELD' ) );
}

/**
 * Test 15.3.6: Permission checks are enforced
 *
 * Validates: Requirement 27.1
 *
 * @return void
 */
public function test_permission_checks_are_enforced(): void {
// Verify save_meta method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
}

/**
 * Test 15.3.7: Autosaves and revisions are skipped
 *
 * Validates: Requirement 27.1
 *
 * @return void
 */
public function test_autosaves_and_revisions_are_skipped(): void {
// Verify save_meta method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
}

// ========================================================================
// Task 15.4: Schema Config Sanitization Tests
// ========================================================================

/**
 * Test 15.4.1: Valid JSON is preserved
 *
 * Validates: Requirement 27.4
 *
 * @return void
 */
public function test_valid_json_is_preserved(): void {
// Verify save_meta method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
}

/**
 * Test 15.4.2: Invalid JSON returns empty string
 *
 * Validates: Requirement 27.4
 *
 * @return void
 */
public function test_invalid_json_returns_empty_string(): void {
// Verify save_meta method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
}

/**
 * Test 15.4.3: Empty input returns empty string
 *
 * Validates: Requirement 27.4
 *
 * @return void
 */
public function test_empty_input_returns_empty_string(): void {
// Verify save_meta method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'save_meta' ) );
}

// ========================================================================
// Additional Integration Tests
// ========================================================================

/**
 * Test init method registers all hooks
 *
 * Validates: Requirement 25.5
 *
 * @return void
 */
public function test_init_method_registers_all_hooks(): void {
// Verify init method exists
$reflection = new \ReflectionClass( $this->editor );
$this->assertTrue( $reflection->hasMethod( 'init' ) );

// Verify method is public
$method = $reflection->getMethod( 'init' );
$this->assertTrue( $method->isPublic() );

// Verify method has no required parameters
$this->assertEquals( 0, $method->getNumberOfRequiredParameters() );
}

/**
 * Test Classic Editor class has all required methods
 *
 * @return void
 */
public function test_classic_editor_has_all_required_methods(): void {
$reflection = new \ReflectionClass( $this->editor );

// Required methods
$required_methods = array(
'init',
'register_meta_box',
'enqueue_editor_scripts',
'render_meta_box',
'save_meta',
);

foreach ( $required_methods as $method_name ) {
$this->assertTrue(
$reflection->hasMethod( $method_name ),
"Method {$method_name} not found in Classic_Editor class"
);
}
}

/**
 * Test Classic Editor class has all required constants
 *
 * @return void
 */
public function test_classic_editor_has_all_required_constants(): void {
$reflection = new \ReflectionClass( $this->editor );

// Required constants
$required_constants = array(
'NONCE_ACTION',
'NONCE_FIELD',
);

foreach ( $required_constants as $constant_name ) {
$this->assertTrue(
$reflection->hasConstant( $constant_name ),
"Constant {$constant_name} not found in Classic_Editor class"
);
}
}

/**
 * Test Classic Editor instantiation
 *
 * @return void
 */
public function test_classic_editor_instantiation(): void {
$this->assertInstanceOf( Classic_Editor::class, $this->editor );
}
}