<?php
/**
 * Capability Checker class for integrating capability checks throughout the plugin.
 *
 * Provides utility methods for checking capabilities and hiding UI elements.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Roles;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capability Checker class.
 *
 * Utility class for capability checks throughout the plugin.
 */
class Capability_Checker {

	/**
	 * Role Manager instance.
	 *
	 * @var Role_Manager
	 */
	private static ?Role_Manager $role_manager = null;

	/**
	 * Set Role Manager instance.
	 *
	 * @param Role_Manager $role_manager Role Manager instance.
	 * @return void
	 */
	public static function set_role_manager( Role_Manager $role_manager ): void {
		self::$role_manager = $role_manager;
	}

	/**
	 * Check if current user can perform an action.
	 *
	 * Validates: Requirement 1.3
	 *
	 * @param string $capability Capability to check.
	 * @return bool True if user has capability, false otherwise.
	 */
	public static function user_can( string $capability ): bool {
		if ( ! self::$role_manager ) {
			return current_user_can( $capability );
		}

		return self::$role_manager->user_can( $capability );
	}

	/**
	 * Check if current user can access a feature.
	 *
	 * Returns WP_Error if user doesn't have access.
	 * Validates: Requirement 1.9
	 *
	 * @param string $capability Capability to check.
	 * @return true|\WP_Error True if user has capability, WP_Error otherwise.
	 */
	public static function check_capability( string $capability ) {
		if ( ! self::user_can( $capability ) ) {
			return new \WP_Error(
				'meowseo_unauthorized',
				__( 'You do not have permission to access this feature.', 'meowseo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Hide UI element if user doesn't have capability.
	 *
	 * Validates: Requirement 1.8
	 *
	 * @param string $capability Capability to check.
	 * @return bool True if element should be shown, false otherwise.
	 */
	public static function should_show_ui( string $capability ): bool {
		return self::user_can( $capability );
	}

	/**
	 * Get capability for a feature.
	 *
	 * Maps feature names to capabilities.
	 *
	 * @param string $feature Feature name.
	 * @return string Capability name.
	 */
	public static function get_capability_for_feature( string $feature ): string {
		$feature_map = array(
			'settings'        => 'meowseo_manage_settings',
			'redirects'       => 'meowseo_manage_redirects',
			'404_monitor'     => 'meowseo_view_404_monitor',
			'analytics'       => 'meowseo_manage_analytics',
			'general_meta'    => 'meowseo_edit_general_meta',
			'advanced_meta'   => 'meowseo_edit_advanced_meta',
			'social_meta'     => 'meowseo_edit_social_meta',
			'schema'          => 'meowseo_edit_schema',
			'ai_generation'   => 'meowseo_use_ai_generation',
			'ai_optimizer'    => 'meowseo_use_ai_optimizer',
			'link_suggestions' => 'meowseo_view_link_suggestions',
			'locations'       => 'meowseo_manage_locations',
			'bulk_edit'       => 'meowseo_bulk_edit',
			'admin_bar'       => 'meowseo_view_admin_bar',
			'import_export'   => 'meowseo_import_export',
		);

		return $feature_map[ $feature ] ?? 'manage_options';
	}
}
