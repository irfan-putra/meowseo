<?php
/**
 * Role Manager Module Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Roles;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Roles\Role_Manager;
use MeowSEO\Options;

/**
 * Role Manager module test case
 */
class RoleManagerTest extends TestCase {

	/**
	 * Role Manager instance
	 *
	 * @var Role_Manager
	 */
	private Role_Manager $role_manager;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test fixtures
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( Options::class );
		$this->role_manager = new Role_Manager( $this->options );
	}

	/**
	 * Test module name
	 */
	public function test_get_name(): void {
		$this->assertSame( 'Role Manager', $this->role_manager->get_name() );
	}

	/**
	 * Test module version
	 */
	public function test_get_version(): void {
		$this->assertSame( '1.0.0', $this->role_manager->get_version() );
	}

	/**
	 * Test module is enabled
	 */
	public function test_is_enabled(): void {
		$this->assertTrue( $this->role_manager->is_enabled() );
	}

	/**
	 * Test get all capabilities
	 */
	public function test_get_all_meowseo_capabilities(): void {
		$capabilities = $this->role_manager->get_all_meowseo_capabilities();

		$this->assertIsArray( $capabilities );
		$this->assertCount( 15, $capabilities );
		$this->assertContains( 'meowseo_manage_settings', $capabilities );
		$this->assertContains( 'meowseo_manage_redirects', $capabilities );
		$this->assertContains( 'meowseo_view_404_monitor', $capabilities );
		$this->assertContains( 'meowseo_manage_analytics', $capabilities );
		$this->assertContains( 'meowseo_edit_general_meta', $capabilities );
		$this->assertContains( 'meowseo_edit_advanced_meta', $capabilities );
		$this->assertContains( 'meowseo_edit_social_meta', $capabilities );
		$this->assertContains( 'meowseo_edit_schema', $capabilities );
		$this->assertContains( 'meowseo_use_ai_generation', $capabilities );
		$this->assertContains( 'meowseo_use_ai_optimizer', $capabilities );
		$this->assertContains( 'meowseo_view_link_suggestions', $capabilities );
		$this->assertContains( 'meowseo_manage_locations', $capabilities );
		$this->assertContains( 'meowseo_bulk_edit', $capabilities );
		$this->assertContains( 'meowseo_view_admin_bar', $capabilities );
		$this->assertContains( 'meowseo_import_export', $capabilities );
	}

	/**
	 * Test add capability to role method exists
	 */
	public function test_add_capability_to_role_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->role_manager, 'add_capability_to_role' ),
			'add_capability_to_role method should exist'
		);
	}

	/**
	 * Test remove capability from role method exists
	 */
	public function test_remove_capability_from_role_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->role_manager, 'remove_capability_from_role' ),
			'remove_capability_from_role method should exist'
		);
	}

	/**
	 * Test get role capabilities method exists
	 */
	public function test_get_role_capabilities_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->role_manager, 'get_role_capabilities' ),
			'get_role_capabilities method should exist'
		);
	}

	/**
	 * Test user can method exists
	 */
	public function test_user_can_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->role_manager, 'user_can' ),
			'user_can method should exist'
		);
	}

	/**
	 * Test boot method
	 */
	public function test_boot(): void {
		// Boot should not throw any exceptions
		$this->expectNotToPerformAssertions();
		$this->role_manager->boot();
	}
}
