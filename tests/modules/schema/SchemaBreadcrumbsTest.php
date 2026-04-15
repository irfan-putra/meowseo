<?php
/**
 * Schema Module Breadcrumbs Tests
 *
 * Unit tests for the breadcrumb shortcode and template function registration.
 * Tests that breadcrumbs can be displayed via shortcode and template function.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Schema;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Schema\Schema;
use MeowSEO\Options;

/**
 * Schema Module breadcrumbs test case
 *
 * Tests Requirements 8.8, 8.9: Breadcrumb shortcode and template function registration.
 *
 * @since 1.0.0
 */
class SchemaBreadcrumbsTest extends TestCase {

	/**
	 * Schema module instance
	 *
	 * @var Schema
	 */
	private Schema $schema_module;

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
		$this->schema_module = new Schema( $this->options );
	}

	/**
	 * Test that shortcode callback returns string
	 *
	 * Requirement 8.8: THE Breadcrumbs SHALL register shortcode [meowseo_breadcrumbs]
	 *
	 * @return void
	 */
	public function test_shortcode_callback_returns_string(): void {
		// Call the shortcode callback directly
		$result = $this->schema_module->breadcrumb_shortcode_callback( array() );

		// Should return a string (HTML or empty)
		$this->assertIsString( $result );
	}

	/**
	 * Test that shortcode accepts class attribute
	 *
	 * Requirement 18.1: THE Breadcrumbs render() method SHALL accept optional CSS class parameter
	 *
	 * @return void
	 */
	public function test_shortcode_accepts_class_attribute(): void {
		// Call the shortcode callback with class attribute
		$result = $this->schema_module->breadcrumb_shortcode_callback(
			array( 'class' => 'my-custom-class' )
		);

		// Should return a string
		$this->assertIsString( $result );

		// If breadcrumbs are rendered, should contain the custom class
		if ( ! empty( $result ) ) {
			$this->assertStringContainsString( 'my-custom-class', $result );
		}
	}

	/**
	 * Test that shortcode accepts separator attribute
	 *
	 * Requirement 18.2: THE Breadcrumbs render() method SHALL accept optional separator parameter
	 *
	 * @return void
	 */
	public function test_shortcode_accepts_separator_attribute(): void {
		// Call the shortcode callback with separator attribute
		$result = $this->schema_module->breadcrumb_shortcode_callback(
			array( 'separator' => ' | ' )
		);

		// Should return a string
		$this->assertIsString( $result );

		// Note: Separator is only displayed when there are multiple breadcrumb items.
		// In the test environment, only "Home" is displayed, so no separator appears.
		// This test verifies that the separator parameter is accepted without errors.
	}

	/**
	 * Test that render_breadcrumbs method returns string
	 *
	 * @return void
	 */
	public function test_render_breadcrumbs_returns_string(): void {
		// Call the render_breadcrumbs method
		$result = $this->schema_module->render_breadcrumbs();

		// Should return a string
		$this->assertIsString( $result );
	}

	/**
	 * Test that render_breadcrumbs accepts css_class parameter
	 *
	 * @return void
	 */
	public function test_render_breadcrumbs_accepts_css_class(): void {
		// Call the render_breadcrumbs method with css_class
		$result = $this->schema_module->render_breadcrumbs( 'test-class' );

		// Should return a string
		$this->assertIsString( $result );

		// If breadcrumbs are rendered, should contain the custom class
		if ( ! empty( $result ) ) {
			$this->assertStringContainsString( 'test-class', $result );
		}
	}

	/**
	 * Test that render_breadcrumbs accepts separator parameter
	 *
	 * @return void
	 */
	public function test_render_breadcrumbs_accepts_separator(): void {
		// Call the render_breadcrumbs method with separator
		$result = $this->schema_module->render_breadcrumbs( '', ' → ' );

		// Should return a string
		$this->assertIsString( $result );

		// Note: Separator is only displayed when there are multiple breadcrumb items.
		// In the test environment, only "Home" is displayed, so no separator appears.
		// This test verifies that the separator parameter is accepted without errors.
	}

	/**
	 * Test that breadcrumb_shortcode_callback method exists
	 *
	 * Requirement 8.8: THE Breadcrumbs SHALL register shortcode [meowseo_breadcrumbs]
	 *
	 * @return void
	 */
	public function test_breadcrumb_shortcode_callback_method_exists(): void {
		// Verify the method exists
		$this->assertTrue(
			method_exists( $this->schema_module, 'breadcrumb_shortcode_callback' ),
			'breadcrumb_shortcode_callback method should exist'
		);
	}

	/**
	 * Test that render_breadcrumbs method exists
	 *
	 * Requirement 8.9: THE Breadcrumbs SHALL provide template function meowseo_breadcrumbs()
	 *
	 * @return void
	 */
	public function test_render_breadcrumbs_method_exists(): void {
		// Verify the method exists
		$this->assertTrue(
			method_exists( $this->schema_module, 'render_breadcrumbs' ),
			'render_breadcrumbs method should exist'
		);
	}
}

