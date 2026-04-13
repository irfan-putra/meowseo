<?php
/**
 * Internal Links REST API
 *
 * Provides REST endpoints for accessing link health data and suggestions.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\Internal_Links
 */

namespace MeowSEO\Modules\Internal_Links;

use MeowSEO\Helpers\DB;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Internal Links REST API class
 *
 * Handles REST endpoint registration and request processing.
 */
class Internal_Links_REST {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'meowseo/v1';

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /meowseo/v1/internal-links - Get link health data for a post (Requirement 9.5).
		register_rest_route(
			self::NAMESPACE,
			'/internal-links',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_link_health' ),
				'permission_callback' => array( $this, 'check_edit_posts' ),
				'args'                => array(
					'post_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return $param > 0;
						},
					),
				),
			)
		);

		// GET /meowseo/v1/internal-links/suggestions - Get link suggestions for a post.
		register_rest_route(
			self::NAMESPACE,
			'/internal-links/suggestions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_link_suggestions' ),
				'permission_callback' => array( $this, 'check_edit_posts' ),
				'args'                => array(
					'post_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return $param > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * Get link health data for a post.
	 *
	 * Returns all internal links found in the post with their HTTP status codes.
	 * Requirement 9.5: Accessible to users with edit_posts capability.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_link_health( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );

		// Verify post exists.
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Get link checks from database.
		$link_checks = DB::get_link_checks( $post_id );

		// Calculate link health statistics.
		$stats = $this->calculate_link_stats( $link_checks );

		$response = new WP_REST_Response(
			array(
				'post_id'     => $post_id,
				'links'       => $link_checks,
				'stats'       => $stats,
			)
		);

		// Add cache control headers for CDN/edge caching.
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Get link suggestions for a post.
	 *
	 * Returns suggested internal links based on keyword overlap.
	 * Requirement 9.4: Surface link suggestions based on keyword overlap.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_link_suggestions( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );

		// Verify post exists.
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'meowseo' ),
				array( 'status' => 404 )
			);
		}

		// Get module instance to access suggestion logic.
		$plugin = \MeowSEO\Plugin::instance();
		$module_manager = $plugin->get_module_manager();
		$internal_links_module = $module_manager->get_module( 'internal_links' );

		if ( ! $internal_links_module ) {
			return new WP_Error(
				'module_not_loaded',
				__( 'Internal Links module is not loaded.', 'meowseo' ),
				array( 'status' => 500 )
			);
		}

		// Get link suggestions.
		$suggestions = $internal_links_module->get_link_suggestions( $post_id );

		$response = new WP_REST_Response(
			array(
				'post_id'     => $post_id,
				'suggestions' => $suggestions,
			)
		);

		// Add cache control headers.
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Calculate link health statistics.
	 *
	 * @param array $link_checks Array of link check records.
	 * @return array Statistics array.
	 */
	private function calculate_link_stats( array $link_checks ): array {
		$total = count( $link_checks );
		$checked = 0;
		$healthy = 0;
		$broken = 0;
		$redirects = 0;

		foreach ( $link_checks as $link ) {
			$status = $link['http_status'];

			if ( null !== $status ) {
				$checked++;

				if ( $status >= 200 && $status < 300 ) {
					$healthy++;
				} elseif ( $status >= 300 && $status < 400 ) {
					$redirects++;
				} elseif ( $status >= 400 ) {
					$broken++;
				}
			}
		}

		return array(
			'total'     => $total,
			'checked'   => $checked,
			'healthy'   => $healthy,
			'broken'    => $broken,
			'redirects' => $redirects,
			'pending'   => $total - $checked,
		);
	}

	/**
	 * Check if user has edit_posts capability.
	 *
	 * Requirement 9.5: Accessible to users with edit_posts capability.
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public function check_edit_posts(): bool {
		return current_user_can( 'edit_posts' );
	}
}
