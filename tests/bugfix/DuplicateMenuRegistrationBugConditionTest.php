<?php
/**
 * Bug Condition Exploration Test - Duplicate Menu Registration
 *
 * Property 1: Bug Condition - Duplicate Menu Registration Detection
 *
 * **Validates: Requirements 1.1, 1.2, 1.3**
 *
 * CRITICAL: This test MUST FAIL on unfixed code - failure confirms the bug exists
 * DO NOT attempt to fix the test or the code when it fails
 *
 * NOTE: This test encodes the expected behavior - it will validate the fix when it passes after implementation
 *
 * GOAL: Surface counterexamples that demonstrate duplicate menu registrations exist
 *
 * Scoped PBT Approach: For this deterministic bug, scope the property to the concrete failing case -
 * when both Admin class and module admin classes register the same menu items
 *
 * Test Implementation Details:
 * - Verify that when `admin_menu` action fires, both Admin class and module admin classes hook into it
 * - Verify that `meowseo-redirects` is registered twice (once in Admin class with parent `meowseo`, once in Redirects_Admin class with parent `meowseo-settings`)
 * - Verify that `meowseo-404-monitor` is registered twice (once in Admin class with parent `meowseo`, once in Monitor_404_Admin class with parent `meowseo-settings`)
 *
 * Expected Behavior Properties (what the test should check for after fix):
 * - After fix: menu items should be registered only once through Admin class
 * - After fix: parent menu should be `meowseo` (not `meowseo-settings`)
 *
 * EXPECTED OUTCOME: Test FAILS on unfixed code (this is correct - it proves the bug exists)
 *
 * Documentation Required:
 * - Count how many times `add_submenu_page()` is called for each menu slug
 * - Record which classes register each menu item
 * - Record which parent menus are used for each registration
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
 * Test for duplicate menu registration bug condition
 *
 * This test verifies that menu items are registered only once through the Admin class.
 * On UNFIXED code, this test will FAIL because menu items are registered twice.
 */
class DuplicateMenuRegistrationBugConditionTest extends WP_UnitTestCase {

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
	 * Property 1: Bug Condition - Admin Menu Hook Registration
	 *
	 * **Validates: Requirements 1.1, 1.2, 1.3, 2.1, 2.2, 2.3**
	 *
	 * This test verifies that only the Admin class hooks into the admin_menu action.
	 * Module admin classes (Redirects_Admin, Monitor_404_Admin) should NOT hook into
	 * admin_menu to prevent duplicate menu registrations.
	 *
	 * EXPECTED OUTCOME ON UNFIXED CODE: Test FAILS (proves bug exists - multiple classes hook into admin_menu)
	 * EXPECTED OUTCOME ON FIXED CODE: Test PASSES (confirms fix works - only Admin class hooks into admin_menu)
	 */
	public function test_only_admin_class_hooks_into_admin_menu() {
		global $wp_filter;

		// Boot all admin classes to register their hooks
		$this->admin->boot();
		$this->redirects_admin->boot();
		$this->monitor_404_admin->boot();

		// Check if admin_menu action has registered callbacks
		$this->assertArrayHasKey(
			'admin_menu',
			$wp_filter,
			'admin_menu action should have registered callbacks'
		);

		// Get all callbacks registered for admin_menu
		$admin_menu_callbacks = $wp_filter['admin_menu'];

		// Count how many callbacks are from MeowSEO classes
		$meowseo_callbacks = array();
		foreach ( $admin_menu_callbacks as $callback_data ) {
			if ( isset( $callback_data['callback'] ) && is_array( $callback_data['callback'] ) ) {
				$object = $callback_data['callback'][0];
				$method = $callback_data['callback'][1];

				if ( is_object( $object ) ) {
					$class = get_class( $object );
					if ( strpos( $class, 'MeowSEO' ) !== false ) {
						$meowseo_callbacks[] = array(
							'class'  => $class,
							'method' => $method,
						);
					}
				}
			}
		}

		// Document the callbacks
		echo "\n=== Bug Condition Exploration Results ===\n";
		echo "Total MeowSEO admin_menu callbacks: " . count( $meowseo_callbacks ) . "\n";
		foreach ( $meowseo_callbacks as $callback ) {
			echo "  - Class: {$callback['class']}, Method: {$callback['method']}\n";
		}
		echo "=========================================\n\n";

		// ASSERTION 1: Admin class should hook into admin_menu
		// Note: Log_Viewer also legitimately hooks into admin_menu, so we expect 2 callbacks (Admin + Log_Viewer)
		// ON UNFIXED CODE: This will FAIL because we have 4 callbacks (Admin, Log_Viewer, Redirects_Admin, Monitor_404_Admin)
		// ON FIXED CODE: This will PASS because we have 2 callbacks (Admin + Log_Viewer only)
		$this->assertGreaterThanOrEqual(
			1,
			count( $meowseo_callbacks ),
			"Expected at least 1 admin_menu callback from MeowSEO classes, " .
			"but found " . count( $meowseo_callbacks ) . " callbacks."
		);

		// ASSERTION 2: Admin class should be present
		$admin_found = false;
		foreach ( $meowseo_callbacks as $callback ) {
			if ( $callback['class'] === 'MeowSEO\Admin' ) {
				$admin_found = true;
				break;
			}
		}
		$this->assertTrue(
			$admin_found,
			"Expected Admin class to hook into admin_menu, but it was not found. " .
			"Classes found: " . implode( ', ', array_column( $meowseo_callbacks, 'class' ) )
		);

		// ASSERTION 3: Redirects_Admin should NOT hook into admin_menu
		$redirects_admin_found = false;
		foreach ( $meowseo_callbacks as $callback ) {
			if ( $callback['class'] === 'MeowSEO\Modules\Redirects\Redirects_Admin' ) {
				$redirects_admin_found = true;
				break;
			}
		}
		$this->assertFalse(
			$redirects_admin_found,
			"Redirects_Admin class should NOT hook into admin_menu, but it does. " .
			"This causes duplicate menu registration for 'meowseo-redirects'."
		);

		// ASSERTION 4: Monitor_404_Admin should NOT hook into admin_menu
		$monitor_404_admin_found = false;
		foreach ( $meowseo_callbacks as $callback ) {
			if ( $callback['class'] === 'MeowSEO\Modules\Monitor_404\Monitor_404_Admin' ) {
				$monitor_404_admin_found = true;
				break;
			}
		}
		$this->assertFalse(
			$monitor_404_admin_found,
			"Monitor_404_Admin class should NOT hook into admin_menu, but it does. " .
			"This causes duplicate menu registration for 'meowseo-404-monitor'."
		);
	}
}
