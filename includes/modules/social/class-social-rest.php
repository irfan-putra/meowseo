<?php
/**
 * Social REST API
 *
 * Handles REST API endpoints for social meta CRUD operations.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Social;

use MeowSEO\Helpers\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Social REST API class
 *
 * Provides REST endpoints for social meta management.
 *
 * @since 1.0.0
 */
class Social_REST {

	/**
	 * Postmeta key prefix
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const META_PREFIX = 'meowseo_';

	/**
	 * Register REST API routes
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_routes(): void {
		// GET endpoint for retrieving social meta.
		register_rest_route(
			'meowseo/v1',
			'/social/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_social_meta' ),
				'permission_callback' => array( __CLASS__, 'get_permission_callback' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( __CLASS__, 'validate_post_id' ),
					),
				),
			)
		);

		// POST endpoint for updating social meta.
		register_rest_route(
			'meowseo/v1',
			'/social/(?P<post_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'update_social_meta' ),
				'permission_callback' => array( __CLASS__, 'update_permission_callback' ),
				'args'                => array(
					'post_id'             => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( __CLASS__, 'validate_post_id' ),
					),
					'social_title'        => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'social_description'  => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'social_image_id'     => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// DELETE endpoint for clearing social meta.
		register_rest_route(
			'meowseo/v1',
			'/social/(?P<post_id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'delete_social_meta' ),
				'permission_callback' => array( __CLASS__, 'update_permission_callback' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( __CLASS__, 'validate_post_id' ),
					),
				),
			)
		);
	}

	/**
	 * Get social meta for a post
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public static function get_social_meta( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];

		$social_title = get_post_meta( $post_id, self::META_PREFIX . 'social_title', true );
		$social_description = get_post_meta( $post_id, self::META_PREFIX . 'social_description', true );
		$social_image_id = get_post_meta( $post_id, self::META_PREFIX . 'social_image_id', true );

		$social_image_url = '';
		if ( ! empty( $social_image_id ) ) {
			$image_url = wp_get_attachment_image_url( (int) $social_image_id, 'full' );
			if ( $image_url ) {
				$social_image_url = $image_url;
			}
		}

		$response = new \WP_REST_Response(
			array(
				'post_id'            => $post_id,
				'social_title'       => $social_title ?: '',
				'social_description' => $social_description ?: '',
				'social_image_id'    => $social_image_id ? (int) $social_image_id : 0,
				'social_image_url'   => $social_image_url,
			),
			200
		);

		// Add cache headers for CDN/edge caching.
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * Update social meta for a post
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public static function update_social_meta( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];

		// Verify nonce.
		if ( ! self::verify_nonce( $request ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'meowseo' ),
				),
				403
			);
		}

		// Update social title if provided.
		if ( $request->has_param( 'social_title' ) ) {
			$social_title = $request->get_param( 'social_title' );
			update_post_meta( $post_id, self::META_PREFIX . 'social_title', $social_title );
		}

		// Update social description if provided.
		if ( $request->has_param( 'social_description' ) ) {
			$social_description = $request->get_param( 'social_description' );
			update_post_meta( $post_id, self::META_PREFIX . 'social_description', $social_description );
		}

		// Update social image ID if provided.
		if ( $request->has_param( 'social_image_id' ) ) {
			$social_image_id = $request->get_param( 'social_image_id' );
			update_post_meta( $post_id, self::META_PREFIX . 'social_image_id', $social_image_id );
		}

		// Invalidate cache.
		Cache::delete( "social_{$post_id}" );

		$response = new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Social meta updated successfully.', 'meowseo' ),
				'post_id' => $post_id,
			),
			200
		);

		// No cache for mutations.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Delete social meta for a post
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public static function delete_social_meta( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];

		// Verify nonce.
		if ( ! self::verify_nonce( $request ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'meowseo' ),
				),
				403
			);
		}

		// Delete all social meta fields.
		delete_post_meta( $post_id, self::META_PREFIX . 'social_title' );
		delete_post_meta( $post_id, self::META_PREFIX . 'social_description' );
		delete_post_meta( $post_id, self::META_PREFIX . 'social_image_id' );

		// Invalidate cache.
		Cache::delete( "social_{$post_id}" );

		$response = new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Social meta deleted successfully.', 'meowseo' ),
				'post_id' => $post_id,
			),
			200
		);

		// No cache for mutations.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Permission callback for GET requests
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool True if user has permission.
	 */
	public static function get_permission_callback( \WP_REST_Request $request ): bool {
		$post_id = (int) $request['post_id'];
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		return is_post_publicly_viewable( $post );
	}

	/**
	 * Permission callback for POST/DELETE requests
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool True if user has permission.
	 */
	public static function update_permission_callback( \WP_REST_Request $request ): bool {
		$post_id = (int) $request['post_id'];

		// Verify user can edit this post.
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Validate post ID
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return bool True if valid.
	 */
	public static function validate_post_id( int $post_id ): bool {
		return get_post( $post_id ) !== null;
	}

	/**
	 * Verify nonce from request
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request object.
	 * @return bool True if nonce is valid.
	 */
	private static function verify_nonce( \WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( empty( $nonce ) ) {
			return false;
		}

		return wp_verify_nonce( $nonce, 'wp_rest' );
	}
}
