<?php
/**
 * Property-Based Tests for REST API Capability Check
 *
 * Property 27: Capability Check
 * Validates: Requirements 15.1, 15.3
 *
 * This test uses property-based testing to verify that all REST API endpoints
 * require the manage_options capability and return 403 Forbidden when the
 * capability is not present.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\REST\REST_Logs;
use MeowSEO\Options;

/**
 * REST API Capability Check property-based test case
 *
 * @since 1.0.0
 */
class Property27CapabilityCheckTest extends TestCase {
	use TestTrait;

	/**
	 * REST_Logs instance
	 *
	 * @var REST_Logs
	 */
	private REST_Logs $rest_logs;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Stored capability state for mocking
	 *
	 * @var bool
	 */
	private bool $mock_has_capability = false;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mock Options instance
		$this->options = $this->createMock( Options::class );

		// Create REST_Logs instance
		$this->rest_logs = new REST_Logs( $this->options );

		// Reset mock capability state
		$this->mock_has_capability = false;
		
		// Set global override to null initially
		global $test_current_user_can_override;
		$test_current_user_can_override = null;
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		
		// Reset global overrides
		global $test_current_user_can_override;
		global $test_wp_verify_nonce_override;
		$test_current_user_can_override = null;
		$test_wp_verify_nonce_override = null;
	}

	/**
	 * Property 27: Capability Check
	 *
	 * For any REST API endpoint call without manage_options capability,
	 * the response SHALL be 403 Forbidden.
	 *
	 * This property verifies:
	 * 1. GET /logs endpoint returns false when user lacks manage_options
	 * 2. DELETE /logs endpoint returns WP_Error when user lacks manage_options
	 * 3. GET /logs/{id}/formatted endpoint returns false when user lacks manage_options
	 *
	 * **Validates: Requirements 15.1, 15.3**
	 *
	 * @return void
	 */
	public function test_all_endpoints_require_manage_options_capability(): void {
		$this->forAll(
			Generators::elements( [ 'subscriber', 'contributor', 'author', 'editor' ] )
		)
		->then(
			function ( string $user_role ) {
				// Test with user lacking manage_options capability
				global $test_current_user_can_override;
				$test_current_user_can_override = false;

				// Test GET /logs endpoint
				$get_permission = $this->rest_logs->manage_options_permission();

				$this->assertFalse(
					$get_permission,
					"GET /logs should deny access for user with role: {$user_role}"
				);

				// Test DELETE /logs endpoint
				$delete_request = new \WP_REST_Request( 'DELETE', '/meowseo/v1/logs' );
				$delete_permission = $this->rest_logs->manage_options_permission_with_nonce( $delete_request );

				$this->assertInstanceOf(
					\WP_Error::class,
					$delete_permission,
					"DELETE /logs should return WP_Error for user with role: {$user_role}"
				);

				if ( is_wp_error( $delete_permission ) ) {
					$this->assertEquals(
						'rest_forbidden',
						$delete_permission->get_error_code(),
						"DELETE /logs should return 'rest_forbidden' error code"
					);
				}

				// Test GET /logs/{id}/formatted endpoint
				$formatted_permission = $this->rest_logs->manage_options_permission();

				$this->assertFalse(
					$formatted_permission,
					"GET /logs/{id}/formatted should deny access for user with role: {$user_role}"
				);
			}
		);
	}

	/**
	 * Property: All endpoints grant access with manage_options capability
	 *
	 * For any REST API endpoint call with manage_options capability,
	 * the permission check should pass.
	 *
	 * @return void
	 */
	public function test_all_endpoints_grant_access_with_manage_options(): void {
		$this->forAll(
			Generators::elements( [ 'administrator', 'super_admin' ] )
		)
		->then(
			function ( string $user_role ) {
				// Test with user having manage_options capability
				global $test_current_user_can_override;
				$test_current_user_can_override = true;

				// Test GET /logs endpoint
				$get_permission = $this->rest_logs->manage_options_permission();

				$this->assertTrue(
					$get_permission,
					"GET /logs should grant access for user with role: {$user_role}"
				);

				// Test GET /logs/{id}/formatted endpoint
				$formatted_permission = $this->rest_logs->manage_options_permission();

				$this->assertTrue(
					$formatted_permission,
					"GET /logs/{id}/formatted should grant access for user with role: {$user_role}"
				);
			}
		);
	}

	/**
	 * Property: DELETE endpoint requires both capability and valid nonce
	 *
	 * For any DELETE request, both manage_options capability and valid nonce
	 * are required for access.
	 *
	 * @return void
	 */
	public function test_delete_endpoint_requires_capability_and_nonce(): void {
		$this->forAll(
			Generators::choose( 0, 1 ),
			Generators::choose( 0, 1 )
		)
		->then(
			function ( int $has_capability_int, int $has_valid_nonce_int ) {
				$has_capability = (bool) $has_capability_int;
				$has_valid_nonce = (bool) $has_valid_nonce_int;

				// Set mock capability state
				global $test_current_user_can_override;
				global $test_wp_verify_nonce_override;
				$test_current_user_can_override = $has_capability;
				$test_wp_verify_nonce_override = $has_valid_nonce;

				// Create DELETE request with or without nonce
				$delete_request = new \WP_REST_Request( 'DELETE', '/meowseo/v1/logs' );

				if ( $has_valid_nonce ) {
					// Set nonce in request header
					$delete_request->set_header( 'X-WP-Nonce', 'valid_nonce' );
				}

				// Call permission callback
				$permission = $this->rest_logs->manage_options_permission_with_nonce( $delete_request );

				// If either capability or nonce is missing, should return WP_Error
				if ( ! $has_capability || ! $has_valid_nonce ) {
					$this->assertInstanceOf(
						\WP_Error::class,
						$permission,
						"DELETE should deny access when capability={$has_capability}, nonce={$has_valid_nonce}"
					);
				} else {
					// Both present should return true
					$this->assertTrue(
						$permission,
						"DELETE should grant access when capability={$has_capability}, nonce={$has_valid_nonce}"
					);
				}
			}
		);
	}

	/**
	 * Property: Capability check is consistent across multiple calls
	 *
	 * For any given user capability state, the permission check should
	 * return the same result across multiple calls (deterministic).
	 *
	 * @return void
	 */
	public function test_capability_check_is_deterministic(): void {
		$this->forAll(
			Generators::choose( 0, 1 )
		)
		->then(
			function ( int $has_capability_int ) {
				$has_capability = (bool) $has_capability_int;

				// Set mock capability state
				global $test_current_user_can_override;
				$test_current_user_can_override = $has_capability;

				// Call permission check three times
				$result1 = $this->rest_logs->manage_options_permission();
				$result2 = $this->rest_logs->manage_options_permission();
				$result3 = $this->rest_logs->manage_options_permission();

				// All three should be identical
				$this->assertEquals(
					$result1,
					$result2,
					'Capability check should be deterministic (call 1 vs 2)'
				);

				$this->assertEquals(
					$result2,
					$result3,
					'Capability check should be deterministic (call 2 vs 3)'
				);

				// All should match the expected capability state
				$this->assertEquals(
					$has_capability,
					$result1,
					'Capability check result should match mocked capability state'
				);
			}
		);
	}

	/**
	 * Property: Capability check returns boolean for GET endpoints
	 *
	 * For any GET endpoint, the permission callback should return a boolean
	 * (not WP_Error).
	 *
	 * @return void
	 */
	public function test_get_endpoints_return_boolean_permission(): void {
		$this->forAll(
			Generators::choose( 0, 1 )
		)
		->then(
			function ( int $has_capability_int ) {
				$has_capability = (bool) $has_capability_int;

				// Set mock capability state
				global $test_current_user_can_override;
				$test_current_user_can_override = $has_capability;

				// Test GET /logs endpoint
				$get_permission = $this->rest_logs->manage_options_permission();

				$this->assertIsBool(
					$get_permission,
					'GET /logs permission callback should return boolean'
				);

				$this->assertEquals(
					$has_capability,
					$get_permission,
					'GET /logs permission should match capability state'
				);
			}
		);
	}

	/**
	 * Property: Capability check returns WP_Error for DELETE without capability
	 *
	 * For any DELETE request without manage_options capability,
	 * the permission callback should return WP_Error with 403 status.
	 *
	 * @return void
	 */
	public function test_delete_without_capability_returns_403_error(): void {
		$this->forAll(
			Generators::elements( [ 'subscriber', 'contributor', 'author', 'editor' ] )
		)
		->then(
			function ( string $user_role ) {
				// Set mock capability to false
				global $test_current_user_can_override;
				global $test_wp_verify_nonce_override;
				$test_current_user_can_override = false;
				$test_wp_verify_nonce_override = true;

				// Create DELETE request
				$delete_request = new \WP_REST_Request( 'DELETE', '/meowseo/v1/logs' );
				$delete_request->set_header( 'X-WP-Nonce', 'valid_nonce' );

				// Call permission callback
				$permission = $this->rest_logs->manage_options_permission_with_nonce( $delete_request );

				// Should return WP_Error
				$this->assertInstanceOf(
					\WP_Error::class,
					$permission,
					"DELETE should return WP_Error for user role: {$user_role}"
				);

				// Check error code
				if ( is_wp_error( $permission ) ) {
					$this->assertEquals(
						'rest_forbidden',
						$permission->get_error_code(),
						'Error code should be rest_forbidden'
					);

					// Check error data contains 403 status
					$error_data = $permission->get_error_data();
					$this->assertIsArray( $error_data );
					$this->assertEquals( 403, $error_data['status'] ?? null );
				}
			}
		);
	}

}
