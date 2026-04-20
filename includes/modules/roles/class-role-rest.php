<?php
/**
 * Role REST API class for capability checks.
 *
 * Provides REST API endpoints for role capability management.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Roles;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role REST API class.
 *
 * Manages REST API endpoints for role capabilities.
 */
class Role_REST {

	/**
	 * REST API namespace.
	 */
	private const NAMESPACE = 'meowseo/v1';

	/**
	 * Role Manager instance.
	 *
	 * @var Role_Manager
	 */
	private Role_Manager $role_manager;

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Role_Manager $role_manager Role Manager instance.
	 * @param Options      $options      Options instance.
	 */
	public function __construct( Role_Manager $role_manager, Options $options ) {
		$this->role_manager = $role_manager;
		$this->options      = $options;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Check capability endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/roles/check-capability',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'check_capability' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'capability' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		// Get role capabilities endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/roles/(?P<role>[a-z_]+)/capabilities',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_role_capabilities' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		// Update role capabilities endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/roles/(?P<role>[a-z_]+)/capabilities',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_role_capabilities' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'capability' => array(
						'type'     => 'string',
						'required' => true,
					),
					'grant'      => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Check if user has a capability.
	 *
	 * Validates: Requirement 1.3
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response REST response.
	 */
	public function check_capability( \WP_REST_Request $request ): \WP_REST_Response {
		$capability = $request->get_param( 'capability' );

		if ( ! $capability ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Capability parameter is required',
				),
				400
			);
		}

		$has_capability = $this->role_manager->user_can( $capability );

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'capability'  => $capability,
				'has_access'  => $has_capability,
			)
		);
	}

	/**
	 * Get capabilities for a role.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_role_capabilities( \WP_REST_Request $request ): \WP_REST_Response {
		$role = $request->get_param( 'role' );

		if ( ! $role ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Role parameter is required',
				),
				400
			);
		}

		$capabilities = $this->role_manager->get_role_capabilities( $role );

		return new \WP_REST_Response(
			array(
				'success'       => true,
				'role'          => $role,
				'capabilities'  => $capabilities,
			)
		);
	}

	/**
	 * Update role capabilities.
	 *
	 * Validates: Requirement 1.7
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response REST response.
	 */
	public function update_role_capabilities( \WP_REST_Request $request ): \WP_REST_Response {
		$role       = $request->get_param( 'role' );
		$capability = $request->get_param( 'capability' );
		$grant      = $request->get_param( 'grant' );

		if ( ! $role || ! $capability ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Role and capability parameters are required',
				),
				400
			);
		}

		if ( $grant ) {
			$result = $this->role_manager->add_capability_to_role( $role, $capability );
		} else {
			$result = $this->role_manager->remove_capability_from_role( $role, $capability );
		}

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Failed to update capability',
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'role'        => $role,
				'capability'  => $capability,
				'granted'     => $grant,
			)
		);
	}

	/**
	 * Check if user has permission to access REST endpoints.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public function check_permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * Check if user has admin permission.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}
