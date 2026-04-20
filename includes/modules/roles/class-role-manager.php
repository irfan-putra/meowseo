<?php
/**
 * Role Manager class for managing WordPress capabilities.
 *
 * Handles registration of MeowSEO capabilities and permission checking.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Roles;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role Manager class.
 *
 * Manages WordPress capabilities for MeowSEO features.
 */
class Role_Manager implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * List of all MeowSEO capabilities.
	 *
	 * @var array
	 */
	private array $capabilities = array(
		'meowseo_manage_settings',
		'meowseo_manage_redirects',
		'meowseo_view_404_monitor',
		'meowseo_manage_analytics',
		'meowseo_edit_general_meta',
		'meowseo_edit_advanced_meta',
		'meowseo_edit_social_meta',
		'meowseo_edit_schema',
		'meowseo_use_ai_generation',
		'meowseo_use_ai_optimizer',
		'meowseo_view_link_suggestions',
		'meowseo_manage_locations',
		'meowseo_bulk_edit',
		'meowseo_view_admin_bar',
		'meowseo_import_export',
	);

	/**
	 * Default capability assignments per role.
	 *
	 * @var array
	 */
	private array $default_assignments = array(
		'administrator' => array(
			'meowseo_manage_settings',
			'meowseo_manage_redirects',
			'meowseo_view_404_monitor',
			'meowseo_manage_analytics',
			'meowseo_edit_general_meta',
			'meowseo_edit_advanced_meta',
			'meowseo_edit_social_meta',
			'meowseo_edit_schema',
			'meowseo_use_ai_generation',
			'meowseo_use_ai_optimizer',
			'meowseo_view_link_suggestions',
			'meowseo_manage_locations',
			'meowseo_bulk_edit',
			'meowseo_view_admin_bar',
			'meowseo_import_export',
		),
		'editor' => array(
			'meowseo_edit_general_meta',
			'meowseo_edit_social_meta',
			'meowseo_view_link_suggestions',
		),
	);

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register capabilities on init hook.
		add_action( 'init', array( $this, 'register_capabilities' ), 10 );

		// Initialize Capability_Checker with this instance.
		Capability_Checker::set_role_manager( $this );

		// Register REST API routes.
		$rest = new Role_REST( $this, $this->options );
		add_action( 'rest_api_init', array( $rest, 'register_routes' ) );

		// Initialize admin interface if in admin context.
		if ( is_admin() ) {
			$admin = new Role_Admin( $this, $this->options );
			$admin->boot();
		}
	}

	/**
	 * Get module name.
	 *
	 * @return string Module name.
	 */
	public function get_name(): string {
		return 'Role Manager';
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'roles';
	}

	/**
	 * Get module version.
	 *
	 * @return string Module version.
	 */
	public function get_version(): string {
		return '1.0.0';
	}

	/**
	 * Check if module is enabled.
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		return true;
	}

	/**
	 * Register all MeowSEO capabilities.
	 *
	 * Assigns default capabilities to Administrator and Editor roles.
	 * Validates: Requirements 1.1, 1.2, 1.5, 1.6
	 *
	 * @return void
	 */
	public function register_capabilities(): void {
		// Assign capabilities to roles.
		foreach ( $this->default_assignments as $role_name => $role_capabilities ) {
			$role = get_role( $role_name );

			if ( ! $role ) {
				continue;
			}

			foreach ( $role_capabilities as $capability ) {
				$role->add_cap( $capability );
			}
		}
	}

	/**
	 * Check if current user has a capability.
	 *
	 * Validates: Requirement 1.3
	 *
	 * @param string $capability Capability to check.
	 * @return bool True if user has capability, false otherwise.
	 */
	public function user_can( string $capability ): bool {
		return current_user_can( $capability );
	}

	/**
	 * Get all capabilities assigned to a role.
	 *
	 * @param string $role Role name.
	 * @return array Array of capabilities assigned to the role.
	 */
	public function get_role_capabilities( string $role ): array {
		$role_obj = get_role( $role );

		if ( ! $role_obj ) {
			return array();
		}

		$capabilities = array();

		foreach ( $this->capabilities as $capability ) {
			if ( isset( $role_obj->capabilities[ $capability ] ) && $role_obj->capabilities[ $capability ] ) {
				$capabilities[] = $capability;
			}
		}

		return $capabilities;
	}

	/**
	 * Add a capability to a role.
	 *
	 * Validates: Requirement 1.7
	 *
	 * @param string $role       Role name.
	 * @param string $capability Capability to add.
	 * @return bool True on success, false on failure.
	 */
	public function add_capability_to_role( string $role, string $capability ): bool {
		// Validate capability exists.
		if ( ! in_array( $capability, $this->capabilities, true ) ) {
			return false;
		}

		$role_obj = get_role( $role );

		if ( ! $role_obj ) {
			return false;
		}

		$role_obj->add_cap( $capability );

		return true;
	}

	/**
	 * Remove a capability from a role.
	 *
	 * Validates: Requirement 1.7
	 *
	 * @param string $role       Role name.
	 * @param string $capability Capability to remove.
	 * @return bool True on success, false on failure.
	 */
	public function remove_capability_from_role( string $role, string $capability ): bool {
		// Validate capability exists.
		if ( ! in_array( $capability, $this->capabilities, true ) ) {
			return false;
		}

		$role_obj = get_role( $role );

		if ( ! $role_obj ) {
			return false;
		}

		$role_obj->remove_cap( $capability );

		return true;
	}

	/**
	 * Get all MeowSEO capabilities.
	 *
	 * @return array Array of all MeowSEO capabilities.
	 */
	public function get_all_meowseo_capabilities(): array {
		return $this->capabilities;
	}
}
