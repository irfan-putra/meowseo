<?php
/**
 * Preservation Property Test - Duplicate Menu Registration Fix
 *
 * Property 2: Preservation - AJAX Handlers and Functionality Preservation
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
 *
 * IMPORTANT: Follow observation-first methodology
 * Observe behavior on UNFIXED code for non-buggy inputs (all functionality that does NOT involve menu registration)
 *
 * Observations to Make:
 * - Observe: CSV import/export AJAX handlers in Redirects_Admin work correctly
 * - Observe: Redirect creation, URL ignoring, log clearing AJAX handlers in Monitor_404_Admin work correctly
 * - Observe: Page rendering methods in both module admin classes work correctly
 * - Observe: Admin scripts enqueuing in Monitor_404_Admin works correctly
 * - Observe: Dashboard, Settings, Tools menu items appear correctly under main MeowSEO menu
 *
 * Tests to Write (capturing observed behavior patterns):
 * - Test that `handle_csv_import()` and `handle_csv_export()` AJAX handlers are registered in Redirects_Admin
 * - Test that `handle_create_redirect()`, `handle_ignore_url()`, `handle_clear_all()` AJAX handlers are registered in Monitor_404_Admin
 * - Test that `enqueue_scripts()` hook is registered in Monitor_404_Admin
 * - Test that `render_page()` methods exist and are callable in both module admin classes
 * - Test that Dashboard, Settings, Search Console, Tools menu items are registered only once in Admin class
 *
 * EXPECTED OUTCOME: Tests PASS on unfixed code (confirms baseline behavior to preserve)
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Bugfix;

use WP_UnitTestCase;
use MeowSEO\Admin;
use MeowSEO\Options;
use MeowSEO\Module_Manager;
use MeowSEO\Modules\Redirects\Redirects_Admin;
use MeowSEO\Modules\Monitor_404\Monitor_404_Admin;

/**
 * Test for preservation of AJAX handlers and functionality during duplicate menu registration fix
 *
 * This test verifies that all AJAX handlers, page rendering methods, and other functionality
 * remain intact and functional after removing duplicate menu registrations.
 * These tests should PASS on both UNFIXED and FIXED code.
 */
class DuplicateMenuRegistrationPreservationTest extends WP_UnitTestCase {

	/**
	 * Admin instance
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Redirects_Admin instance
	 *
	 * @var Redirects_Admin
	 */
	private $redirects_admin;

	/**
	 * Monitor_404_Admin instance
	 *
	 * @var Monitor_404_Admin
	 */
	private $monitor_404_admin;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Module_Manager instance
	 *
	 * @var Module_Manager
	 */
	private $module_manager;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Create Options instance
		$this->options = new Options();

		// Create Module_Manager instance
		$this->module_manager = new Module_Manager( $this->options );

		// Create Admin instance
		$this->admin = new Admin( $this->options, $this->module_manager );

		// Create module admin instances
		$this->redirects_admin = new Redirects_Admin( $this->options );
		$this->monitor_404_admin = new Monitor_404_Admin( $this->options );
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Property 2.1: Preservation - CSV Import AJAX Handler Registration
	 *
	 * **Validates: Requirements 3.1, 3.4**
	 *
	 * This test verifies that the CSV import AJAX handler is registered correctly
	 * in Redirects_Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_csv_import_ajax_handler_is_registered() {
		global $wp_filter;

		// Boot Redirects_Admin to register hooks
		$this->redirects_admin->boot();

		// Check if wp_ajax_meowseo_import_redirects action has registered callbacks
		$this->assertArrayHasKey(
			'wp_ajax_meowseo_import_redirects',
			$wp_filter,
			'wp_ajax_meowseo_import_redirects action should have registered callbacks'
		);

		// Get all callbacks registered for wp_ajax_meowseo_import_redirects
		$ajax_callbacks = $wp_filter['wp_ajax_meowseo_import_redirects'];

		// Find the callback from Redirects_Admin
		$redirects_admin_callback_found = false;
		foreach ( $ajax_callbacks as $callback_data ) {
			if ( isset( $callback_data['callback'] ) && is_array( $callback_data['callback'] ) ) {
				$object = $callback_data['callback'][0];
				$method = $callback_data['callback'][1];

				if ( $object instanceof Redirects_Admin && $method === 'handle_csv_import' ) {
					$redirects_admin_callback_found = true;
					break;
				}
			}
		}

		$this->assertTrue(
			$redirects_admin_callback_found,
			'Redirects_Admin::handle_csv_import() should be registered for wp_ajax_meowseo_import_redirects action'
		);
	}

	/**
	 * Property 2.2: Preservation - CSV Export AJAX Handler Registration
	 *
	 * **Validates: Requirements 3.1, 3.4**
	 *
	 * This test verifies that the CSV export AJAX handler is registered correctly
	 * in Redirects_Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_csv_export_ajax_handler_is_registered() {
		global $wp_filter;

		// Boot Redirects_Admin to register hooks
		$this->redirects_admin->boot();

		// Check if wp_ajax_meowseo_export_redirects action has registered callbacks
		$this->assertArrayHasKey(
			'wp_ajax_meowseo_export_redirects',
			$wp_filter,
			'wp_ajax_meowseo_export_redirects action should have registered callbacks'
		);

		// Get all callbacks registered for wp_ajax_meowseo_export_redirects
		$ajax_callbacks = $wp_filter['wp_ajax_meowseo_export_redirects'];

		// Find the callback from Redirects_Admin
		$redirects_admin_callback_found = false;
		foreach ( $ajax_callbacks as $callback_data ) {
			if ( isset( $callback_data['callback'] ) && is_array( $callback_data['callback'] ) ) {
				$object = $callback_data['callback'][0];
				$method = $callback_data['callback'][1];

				if ( $object instanceof Redirects_Admin && $method === 'handle_csv_export' ) {
					$redirects_admin_callback_found = true;
					break;
				}
			}
		}

		$this->assertTrue(
			$redirects_admin_callback_found,
			'Redirects_Admin::handle_csv_export() should be registered for wp_ajax_meowseo_export_redirects action'
		);
	}

	/**
	 * Property 2.3: Preservation - Create Redirect AJAX Handler Registration
	 *
	 * **Validates: Requirements 3.2, 3.5**
	 *
	 * This test verifies that the create redirect AJAX handler is registered correctly
	 * in Monitor_404_Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_create_redirect_ajax_handler_is_registered() {
		global $wp_filter;

		// Boot Monitor_404_Admin to register hooks
		$this->monitor_404_admin->boot();

		// Check if wp_ajax_meowseo_create_redirect_from_404 action has registered callbacks
		$this->assertArrayHasKey(
			'wp_ajax_meowseo_create_redirect_from_404',
			$wp_filter,
			'wp_ajax_meowseo_create_redirect_from_404 action should have registered callbacks'
		);

		// Get all callbacks registered for wp_ajax_meowseo_create_redirect_from_404
		$ajax_callbacks = $wp_filter['wp_ajax_meowseo_create_redirect_from_404'];

		// Find the callback from Monitor_404_Admin
		$monitor_404_admin_callback_found = false;
		foreach ( $ajax_callbacks as $callback_data ) {
			if ( isset( $callback_data['callback'] ) && is_array( $callback_data['callback'] ) ) {
				$object = $callback_data['callback'][0];
				$method = $callback_data['callback'][1];

				if ( $object instanceof Monitor_404_Admin && $method === 'handle_create_redirect' ) {
					$monitor_404_admin_callback_found = true;
					break;
				}
			}
		}

		$this->assertTrue(
			$monitor_404_admin_callback_found,
			'Monitor_404_Admin::handle_create_redirect() should be registered for wp_ajax_meowseo_create_redirect_from_404 action'
		);
	}

	/**
	 * Property 2.4: Preservation - Ignore URL AJAX Handler Registration
	 *
	 * **Validates: Requirements 3.2, 3.5**
	 *
	 * This test verifies that the ignore URL AJAX handler is registered correctly
	 * in Monitor_404_Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_ignore_url_ajax_handler_is_registered() {
		global $wp_filter;

		// Boot Monitor_404_Admin to register hooks
		$this->monitor_404_admin->boot();

		// Check if wp_ajax_meowseo_ignore_404_url action has registered callbacks
		$this->assertArrayHasKey(
			'wp_ajax_meowseo_ignore_404_url',
			$wp_filter,
			'wp_ajax_meowseo_ignore_404_url action should have registered callbacks'
		);

		// Get all callbacks registered for wp_ajax_meowseo_ignore_404_url
		$ajax_callbacks = $wp_filter['wp_ajax_meowseo_ignore_404_url'];

		// Find the callback from Monitor_404_Admin
		$monitor_404_admin_callback_found = false;
		foreach ( $ajax_callbacks as $callback_data ) {
			if ( isset( $callback_data['callback'] ) && is_array( $callback_data['callback'] ) ) {
				$object = $callback_data['callback'][0];
				$method = $callback_data['callback'][1];

				if ( $object instanceof Monitor_404_Admin && $method === 'handle_ignore_url' ) {
					$monitor_404_admin_callback_found = true;
					break;
				}
			}
		}

		$this->assertTrue(
			$monitor_404_admin_callback_found,
			'Monitor_404_Admin::handle_ignore_url() should be registered for wp_ajax_meowseo_ignore_404_url action'
		);
	}

	/**
	 * Property 2.5: Preservation - Clear All AJAX Handler Registration
	 *
	 * **Validates: Requirements 3.2, 3.5**
	 *
	 * This test verifies that the clear all AJAX handler is registered correctly
	 * in Monitor_404_Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_clear_all_ajax_handler_is_registered() {
		global $wp_filter;

		// Boot Monitor_404_Admin to register hooks
		$this->monitor_404_admin->boot();

		// Check if wp_ajax_meowseo_clear_all_404 action has registered callbacks
		$this->assertArrayHasKey(
			'wp_ajax_meowseo_clear_all_404',
			$wp_filter,
			'wp_ajax_meowseo_clear_all_404 action should have registered callbacks'
		);

		// Get all callbacks registered for wp_ajax_meowseo_clear_all_404
		$ajax_callbacks = $wp_filter['wp_ajax_meowseo_clear_all_404'];

		// Find the callback from Monitor_404_Admin
		$monitor_404_admin_callback_found = false;
		foreach ( $ajax_callbacks as $callback_data ) {
			if ( isset( $callback_data['callback'] ) && is_array( $callback_data['callback'] ) ) {
				$object = $callback_data['callback'][0];
				$method = $callback_data['callback'][1];

				if ( $object instanceof Monitor_404_Admin && $method === 'handle_clear_all' ) {
					$monitor_404_admin_callback_found = true;
					break;
				}
			}
		}

		$this->assertTrue(
			$monitor_404_admin_callback_found,
			'Monitor_404_Admin::handle_clear_all() should be registered for wp_ajax_meowseo_clear_all_404 action'
		);
	}

	/**
	 * Property 2.6: Preservation - Admin Scripts Enqueuing Hook Registration
	 *
	 * **Validates: Requirements 3.2, 3.6**
	 *
	 * This test verifies that the admin scripts enqueuing hook is registered correctly
	 * in Monitor_404_Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_admin_scripts_enqueuing_hook_is_registered() {
		global $wp_filter;

		// Boot Monitor_404_Admin to register hooks
		$this->monitor_404_admin->boot();

		// Check if admin_enqueue_scripts action has registered callbacks
		$this->assertArrayHasKey(
			'admin_enqueue_scripts',
			$wp_filter,
			'admin_enqueue_scripts action should have registered callbacks'
		);

		// Get all callbacks registered for admin_enqueue_scripts
		$enqueue_callbacks = $wp_filter['admin_enqueue_scripts'];

		// Find the callback from Monitor_404_Admin
		$monitor_404_admin_callback_found = false;
		foreach ( $enqueue_callbacks as $callback_data ) {
			if ( isset( $callback_data['callback'] ) && is_array( $callback_data['callback'] ) ) {
				$object = $callback_data['callback'][0];
				$method = $callback_data['callback'][1];

				if ( $object instanceof Monitor_404_Admin && $method === 'enqueue_scripts' ) {
					$monitor_404_admin_callback_found = true;
					break;
				}
			}
		}

		$this->assertTrue(
			$monitor_404_admin_callback_found,
			'Monitor_404_Admin::enqueue_scripts() should be registered for admin_enqueue_scripts action'
		);
	}

	/**
	 * Property 2.7: Preservation - Redirects_Admin Render Page Method Exists
	 *
	 * **Validates: Requirements 3.1, 3.2**
	 *
	 * This test verifies that the render_page() method exists and is callable
	 * in Redirects_Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_redirects_admin_render_page_method_exists() {
		$this->assertTrue(
			method_exists( $this->redirects_admin, 'render_page' ),
			'Redirects_Admin::render_page() method should exist'
		);

		$this->assertTrue(
			is_callable( array( $this->redirects_admin, 'render_page' ) ),
			'Redirects_Admin::render_page() method should be callable'
		);
	}

	/**
	 * Property 2.8: Preservation - Monitor_404_Admin Render Page Method Exists
	 *
	 * **Validates: Requirements 3.2, 3.3**
	 *
	 * This test verifies that the render_page() method exists and is callable
	 * in Monitor_404_Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_monitor_404_admin_render_page_method_exists() {
		$this->assertTrue(
			method_exists( $this->monitor_404_admin, 'render_page' ),
			'Monitor_404_Admin::render_page() method should exist'
		);

		$this->assertTrue(
			is_callable( array( $this->monitor_404_admin, 'render_page' ) ),
			'Monitor_404_Admin::render_page() method should be callable'
		);
	}

	/**
	 * Property 2.9: Preservation - Dashboard Menu Item Registration Method Exists
	 *
	 * **Validates: Requirements 3.1**
	 *
	 * This test verifies that the register_admin_menu() method exists and is callable
	 * in Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_admin_register_menu_method_exists() {
		$this->assertTrue(
			method_exists( $this->admin, 'register_admin_menu' ),
			'Admin::register_admin_menu() method should exist'
		);

		$this->assertTrue(
			is_callable( array( $this->admin, 'register_admin_menu' ) ),
			'Admin::register_admin_menu() method should be callable'
		);
	}

	/**
	 * Property 2.10: Preservation - Dashboard Render Method Exists
	 *
	 * **Validates: Requirements 3.1**
	 *
	 * This test verifies that the render_dashboard_page() method exists and is callable
	 * in Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_admin_render_dashboard_method_exists() {
		$this->assertTrue(
			method_exists( $this->admin, 'render_dashboard_page' ),
			'Admin::render_dashboard_page() method should exist'
		);

		$this->assertTrue(
			is_callable( array( $this->admin, 'render_dashboard_page' ) ),
			'Admin::render_dashboard_page() method should be callable'
		);
	}

	/**
	 * Property 2.11: Preservation - Settings Render Method Exists
	 *
	 * **Validates: Requirements 3.1**
	 *
	 * This test verifies that the render_settings_page() method exists and is callable
	 * in Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_admin_render_settings_method_exists() {
		$this->assertTrue(
			method_exists( $this->admin, 'render_settings_page' ),
			'Admin::render_settings_page() method should exist'
		);

		$this->assertTrue(
			is_callable( array( $this->admin, 'render_settings_page' ) ),
			'Admin::render_settings_page() method should be callable'
		);
	}

	/**
	 * Property 2.12: Preservation - Tools Render Method Exists
	 *
	 * **Validates: Requirements 3.1**
	 *
	 * This test verifies that the render_tools_page() method exists and is callable
	 * in Admin class and remains functional after the fix.
	 *
	 * EXPECTED OUTCOME: Test PASSES on both unfixed and fixed code
	 */
	public function test_admin_render_tools_method_exists() {
		$this->assertTrue(
			method_exists( $this->admin, 'render_tools_page' ),
			'Admin::render_tools_page() method should exist'
		);

		$this->assertTrue(
			is_callable( array( $this->admin, 'render_tools_page' ) ),
			'Admin::render_tools_page() method should be callable'
		);
	}
}
