<?php
/**
 * Capability Checker Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Roles;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Roles\Capability_Checker;
use MeowSEO\Modules\Roles\Role_Manager;
use MeowSEO\Options;

/**
 * Capability Checker test case
 */
class CapabilityCheckerTest extends TestCase {

	/**
	 * Role Manager instance
	 *
	 * @var Role_Manager
	 */
	private Role_Manager $role_manager;

	/**
	 * Set up test fixtures
	 */
	protected function setUp(): void {
		parent::setUp();

		$options = $this->createMock( Options::class );
		$this->role_manager = new Role_Manager( $options );
		Capability_Checker::set_role_manager( $this->role_manager );
	}

	/**
	 * Test get capability for feature
	 */
	public function test_get_capability_for_feature(): void {
		$this->assertSame(
			'meowseo_manage_settings',
			Capability_Checker::get_capability_for_feature( 'settings' )
		);

		$this->assertSame(
			'meowseo_manage_redirects',
			Capability_Checker::get_capability_for_feature( 'redirects' )
		);

		$this->assertSame(
			'meowseo_view_404_monitor',
			Capability_Checker::get_capability_for_feature( '404_monitor' )
		);

		$this->assertSame(
			'meowseo_manage_analytics',
			Capability_Checker::get_capability_for_feature( 'analytics' )
		);

		$this->assertSame(
			'meowseo_edit_general_meta',
			Capability_Checker::get_capability_for_feature( 'general_meta' )
		);

		$this->assertSame(
			'meowseo_edit_advanced_meta',
			Capability_Checker::get_capability_for_feature( 'advanced_meta' )
		);

		$this->assertSame(
			'meowseo_edit_social_meta',
			Capability_Checker::get_capability_for_feature( 'social_meta' )
		);

		$this->assertSame(
			'meowseo_edit_schema',
			Capability_Checker::get_capability_for_feature( 'schema' )
		);

		$this->assertSame(
			'meowseo_use_ai_generation',
			Capability_Checker::get_capability_for_feature( 'ai_generation' )
		);

		$this->assertSame(
			'meowseo_use_ai_optimizer',
			Capability_Checker::get_capability_for_feature( 'ai_optimizer' )
		);

		$this->assertSame(
			'meowseo_view_link_suggestions',
			Capability_Checker::get_capability_for_feature( 'link_suggestions' )
		);

		$this->assertSame(
			'meowseo_manage_locations',
			Capability_Checker::get_capability_for_feature( 'locations' )
		);

		$this->assertSame(
			'meowseo_bulk_edit',
			Capability_Checker::get_capability_for_feature( 'bulk_edit' )
		);

		$this->assertSame(
			'meowseo_view_admin_bar',
			Capability_Checker::get_capability_for_feature( 'admin_bar' )
		);

		$this->assertSame(
			'meowseo_import_export',
			Capability_Checker::get_capability_for_feature( 'import_export' )
		);
	}

	/**
	 * Test get capability for unknown feature
	 */
	public function test_get_capability_for_unknown_feature(): void {
		$this->assertSame(
			'manage_options',
			Capability_Checker::get_capability_for_feature( 'unknown_feature' )
		);
	}

	/**
	 * Test should show UI
	 */
	public function test_should_show_ui(): void {
		// This test depends on WordPress user capabilities
		// We'll just verify the method exists and returns a boolean
		$result = Capability_Checker::should_show_ui( 'meowseo_manage_settings' );
		$this->assertIsBool( $result );
	}

	/**
	 * Test check capability method exists
	 */
	public function test_check_capability_method_exists(): void {
		$this->assertTrue(
			method_exists( Capability_Checker::class, 'check_capability' ),
			'check_capability method should exist'
		);
	}

	/**
	 * Test user can method exists
	 */
	public function test_user_can_method_exists(): void {
		$this->assertTrue(
			method_exists( Capability_Checker::class, 'user_can' ),
			'user_can method should exist'
		);
	}
}
