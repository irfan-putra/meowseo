<?php
/**
 * Social Module Verification Script
 *
 * Manual verification script for Social module functionality.
 * Run this in a WordPress environment with MeowSEO active.
 *
 * @package MeowSEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verify Social Module Implementation
 */
function meowseo_verify_social_module() {
	echo "<h2>MeowSEO Social Module Verification</h2>\n";

	// Check if module class exists.
	if ( ! class_exists( 'MeowSEO\Modules\Social\Social' ) ) {
		echo "<p style='color: red;'>❌ Social module class not found</p>\n";
		return;
	}
	echo "<p style='color: green;'>✓ Social module class exists</p>\n";

	// Check if REST class exists.
	if ( ! class_exists( 'MeowSEO\Modules\Social\Social_REST' ) ) {
		echo "<p style='color: red;'>❌ Social REST class not found</p>\n";
		return;
	}
	echo "<p style='color: green;'>✓ Social REST class exists</p>\n";

	// Get plugin instance.
	$plugin = \MeowSEO\Plugin::instance();
	$module_manager = $plugin->get_module_manager();

	// Check if social module is registered.
	if ( ! $module_manager->is_active( 'social' ) ) {
		echo "<p style='color: orange;'>⚠ Social module is not active (enable it in settings)</p>\n";
	} else {
		echo "<p style='color: green;'>✓ Social module is active</p>\n";

		$social_module = $module_manager->get_module( 'social' );
		
		// Verify module ID.
		if ( $social_module->get_id() === 'social' ) {
			echo "<p style='color: green;'>✓ Module ID is correct</p>\n";
		} else {
			echo "<p style='color: red;'>❌ Module ID is incorrect</p>\n";
		}
	}

	// Check REST endpoints.
	$rest_server = rest_get_server();
	$routes = $rest_server->get_routes();

	$expected_routes = array(
		'/meowseo/v1/social/(?P<post_id>\d+)',
	);

	foreach ( $expected_routes as $route ) {
		$route_pattern = str_replace( '(?P<post_id>\d+)', '\d+', $route );
		$found = false;
		
		foreach ( array_keys( $routes ) as $registered_route ) {
			if ( preg_match( '#^' . $route_pattern . '$#', $registered_route ) ) {
				$found = true;
				break;
			}
		}

		if ( $found ) {
			echo "<p style='color: green;'>✓ REST endpoint registered: {$route}</p>\n";
		} else {
			echo "<p style='color: red;'>❌ REST endpoint not found: {$route}</p>\n";
		}
	}

	// Test with a sample post.
	$test_post_id = wp_insert_post( array(
		'post_title'   => 'Social Module Test Post',
		'post_content' => 'This is a test post for social module verification.',
		'post_excerpt' => 'Test excerpt for social sharing.',
		'post_status'  => 'publish',
		'post_type'    => 'post',
	) );

	if ( $test_post_id && ! is_wp_error( $test_post_id ) ) {
		echo "<p style='color: green;'>✓ Created test post (ID: {$test_post_id})</p>\n";

		// Test social data retrieval.
		if ( $module_manager->is_active( 'social' ) ) {
			$social_module = $module_manager->get_module( 'social' );
			$social_data = $social_module->get_social_data( $test_post_id );

			if ( ! empty( $social_data ) ) {
				echo "<p style='color: green;'>✓ Social data retrieved successfully</p>\n";
				echo "<pre>";
				echo "Title: " . esc_html( $social_data['title'] ?? 'N/A' ) . "\n";
				echo "Description: " . esc_html( $social_data['description'] ?? 'N/A' ) . "\n";
				echo "Type: " . esc_html( $social_data['type'] ?? 'N/A' ) . "\n";
				echo "URL: " . esc_html( $social_data['url'] ?? 'N/A' ) . "\n";
				echo "Image: " . esc_html( $social_data['image'] ?? 'N/A' ) . "\n";
				echo "</pre>";
			} else {
				echo "<p style='color: red;'>❌ Failed to retrieve social data</p>\n";
			}

			// Test custom social meta.
			update_post_meta( $test_post_id, 'meowseo_social_title', 'Custom Social Title' );
			update_post_meta( $test_post_id, 'meowseo_social_description', 'Custom social description.' );

			// Clear cache.
			\MeowSEO\Helpers\Cache::delete( "social_{$test_post_id}" );

			$social_data = $social_module->get_social_data( $test_post_id );

			if ( $social_data['title'] === 'Custom Social Title' && 
			     $social_data['description'] === 'Custom social description.' ) {
				echo "<p style='color: green;'>✓ Custom social meta overrides work correctly</p>\n";
			} else {
				echo "<p style='color: red;'>❌ Custom social meta overrides not working</p>\n";
			}
		}

		// Clean up test post.
		wp_delete_post( $test_post_id, true );
		echo "<p style='color: green;'>✓ Cleaned up test post</p>\n";
	} else {
		echo "<p style='color: red;'>❌ Failed to create test post</p>\n";
	}

	echo "<h3>Verification Complete</h3>\n";
}

// Run verification if accessed directly via admin.
if ( is_admin() && isset( $_GET['meowseo_verify_social'] ) ) {
	add_action( 'admin_notices', 'meowseo_verify_social_module' );
}
