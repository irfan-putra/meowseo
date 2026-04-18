<?php
/**
 * Bug Condition Exploration Test - REST API Incompleteness
 *
 * Property 1: Bug Condition - Incomplete REST API and Integration
 *
 * CRITICAL: This test MUST FAIL on unfixed code - failure confirms the bugs exist
 * DO NOT attempt to fix the test or the code when it fails
 * NOTE: This test encodes the expected behavior - it will validate the fix when it passes after implementation
 *
 * GOAL: Surface counterexamples that demonstrate REST API bugs exist
 *
 * Test implementation details from Bug Condition in design:
 * - Test that REST endpoints check nonce but don't verify in all code paths
 * - Test that sitemap rewrite rules have incomplete regex patterns
 * - Test that GSC callback has incomplete token exchange logic
 *
 * Requirements: 2.15, 2.16, 2.17
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Bugfix;

use PHPUnit\Framework\TestCase;
use MeowSEO\REST_API;
use MeowSEO\Options;
use MeowSEO\Module_Manager;
use MeowSEO\Modules\Sitemap\Sitemap;
use MeowSEO\Modules\GSC\GSC_Auth;

/**
 * Test REST API incompleteness bugs
 */
class Task4_RestApiIncompletenessTest extends TestCase {

	/**
	 * Test that REST endpoints have consistent nonce verification in all code paths
	 *
	 * Bug Condition 2.15: REST endpoints check nonce but don't verify in all code paths
	 * Expected Behavior: ALL endpoints SHALL properly verify nonce in all code paths before processing requests
	 *
	 * EXPECTED OUTCOME: Test FAILS on unfixed code (proves bug exists)
	 */
	public function test_rest_endpoints_verify_nonce_consistently() {
		// Create mock dependencies
		$options = $this->createMock( Options::class );
		$module_manager = $this->createMock( Module_Manager::class );
		
		$rest_api = new REST_API( $options, $module_manager );
		
		// Create a mock request without nonce
		$request = new \WP_REST_Request( 'GET', '/meowseo/v1/dashboard/discover-performance' );
		
		// Test get_discover_performance - should verify nonce BEFORE calling Dashboard_Widgets
		$response = $rest_api->get_discover_performance( $request );
		
		// EXPECTED BEHAVIOR: Should return 403 with invalid nonce error
		$this->assertEquals( 403, $response->get_status(), 
			'get_discover_performance should verify nonce and return 403 for invalid nonce' );
		$this->assertFalse( $response->get_data()['success'], 
			'Response should indicate failure' );
		$this->assertStringContainsString( 'security', strtolower( $response->get_data()['message'] ), 
			'Error message should mention security verification' );
		
		// Test get_index_queue - should verify nonce BEFORE calling Dashboard_Widgets
		$request2 = new \WP_REST_Request( 'GET', '/meowseo/v1/dashboard/index-queue' );
		$response2 = $rest_api->get_index_queue( $request2 );
		
		// EXPECTED BEHAVIOR: Should return 403 with invalid nonce error
		$this->assertEquals( 403, $response2->get_status(), 
			'get_index_queue should verify nonce and return 403 for invalid nonce' );
		$this->assertFalse( $response2->get_data()['success'], 
			'Response should indicate failure' );
		$this->assertStringContainsString( 'security', strtolower( $response2->get_data()['message'] ), 
			'Error message should mention security verification' );
		
		// Test that nonce verification happens BEFORE any processing
		// This is the key bug: nonce is checked but not verified in all code paths
		$this->assertNonceVerificationBeforeProcessing( $rest_api );
	}

	/**
	 * Test that sitemap rewrite rules have complete regex patterns
	 *
	 * Bug Condition 2.16: Sitemap rewrite rules have incomplete regex patterns
	 * Expected Behavior: ALL rewrite rules SHALL be complete with proper regex patterns
	 *
	 * EXPECTED OUTCOME: Test FAILS on unfixed code (proves bug exists)
	 */
	public function test_sitemap_rewrite_rules_are_complete() {
		// Read the sitemap class source code to check for truncated patterns
		$sitemap_file = dirname( __DIR__, 2 ) . '/includes/modules/sitemap/class-sitemap.php';
		$this->assertFileExists( $sitemap_file, 'Sitemap class file should exist' );
		
		$source_code = file_get_contents( $sitemap_file );
		$this->assertNotFalse( $source_code, 'Should be able to read sitemap source code' );
		
		// EXPECTED BEHAVIOR: All add_rewrite_rule calls should have complete regex patterns ending with $
		
		// Extract all add_rewrite_rule calls
		preg_match_all( 
			'/add_rewrite_rule\(\s*[\'"]([^\'"]+)[\'"]/s', 
			$source_code, 
			$matches 
		);
		
		$this->assertNotEmpty( $matches[1], 'Should find add_rewrite_rule calls in sitemap class' );
		
		$patterns = $matches[1];
		
		// Check each pattern for truncation indicators
		foreach ( $patterns as $pattern ) {
			// Check for newline characters (indicates truncation)
			$this->assertStringNotContainsString( "\n", $pattern, 
				"Sitemap rewrite pattern should not contain newlines (indicates truncation): {$pattern}" );
			
			// Check for incomplete patterns (missing closing $)
			$this->assertMatchesRegularExpression( '/\$$/', trim( $pattern ), 
				"Sitemap rewrite pattern should end with $ anchor: {$pattern}" );
			
			// Check pattern is not just whitespace or empty
			$this->assertNotEmpty( trim( $pattern ), 
				"Sitemap rewrite pattern should not be empty" );
		}
		
		// Verify we have all expected sitemap patterns
		$expected_patterns = [
			'^sitemap\.xml$',
			'^sitemap-posts\.xml$',
			'^sitemap-pages\.xml$',
			'^sitemap-([^/]+?)\.xml$',
			'^sitemap-([^/]+?)-([0-9]+)\.xml$',
			'^sitemap-news\.xml$',
			'^sitemap-video\.xml$',
		];
		
		foreach ( $expected_patterns as $expected ) {
			$found = false;
			foreach ( $patterns as $pattern ) {
				if ( trim( $pattern ) === $expected ) {
					$found = true;
					break;
				}
			}
			$this->assertTrue( $found, 
				"Expected sitemap rewrite pattern should exist: {$expected}" );
		}
		
		// Additional check: Verify patterns don't have PHP code mixed in (file corruption)
		foreach ( $patterns as $pattern ) {
			$this->assertStringNotContainsString( '<?php', $pattern, 
				"Sitemap pattern should not contain PHP tags: {$pattern}" );
			$this->assertStringNotContainsString( '?>', $pattern, 
				"Sitemap pattern should not contain PHP closing tags: {$pattern}" );
		}
	}

	/**
	 * Test that GSC callback has complete token exchange logic
	 *
	 * Bug Condition 2.17: GSC callback has incomplete token exchange logic
	 * Expected Behavior: handle_callback() SHALL be complete with token exchange, error handling, storage, and redirect
	 *
	 * EXPECTED OUTCOME: Test FAILS on unfixed code (proves bug exists)
	 */
	public function test_gsc_callback_has_complete_token_exchange() {
		// Create GSC_Auth instance
		$options = $this->createMock( Options::class );
		$gsc_auth = new GSC_Auth( $options );
		
		// Set up client credentials
		update_option( 'meowseo_gsc_client_id', 'test_client_id' );
		update_option( 'meowseo_gsc_client_secret', 'test_client_secret' );
		
		// Mock authorization code
		$auth_code = 'test_authorization_code';
		
		// EXPECTED BEHAVIOR: handle_callback should have complete implementation
		
		// Test 1: Method should exist
		$this->assertTrue( method_exists( $gsc_auth, 'handle_callback' ), 
			'handle_callback method should exist' );
		
		// Test 2: Method should implement OAuth token exchange
		// We'll use reflection to check if the method has the expected logic
		$reflection = new \ReflectionMethod( $gsc_auth, 'handle_callback' );
		$method_source = $this->getMethodSource( $reflection );
		
		// Check for token exchange implementation
		$this->assertStringContainsString( 'wp_remote_post', $method_source, 
			'handle_callback should make HTTP POST request for token exchange' );
		$this->assertStringContainsString( 'authorization_code', $method_source, 
			'handle_callback should use authorization_code grant type' );
		$this->assertStringContainsString( 'access_token', $method_source, 
			'handle_callback should handle access_token in response' );
		$this->assertStringContainsString( 'refresh_token', $method_source, 
			'handle_callback should handle refresh_token in response' );
		
		// Test 3: Method should have error handling
		$this->assertStringContainsString( 'is_wp_error', $method_source, 
			'handle_callback should check for WP_Error' );
		$this->assertStringContainsString( 'Logger::error', $method_source, 
			'handle_callback should log errors' );
		
		// Test 4: Method should store credentials
		$this->assertStringContainsString( 'store_credentials', $method_source, 
			'handle_callback should store credentials' );
		
		// Test 5: Method should handle success/failure properly
		$this->assertStringContainsString( 'return', $method_source, 
			'handle_callback should return boolean indicating success/failure' );
		
		// Test 6: Verify complete implementation by checking method length
		$method_lines = substr_count( $method_source, "\n" );
		$this->assertGreaterThan( 50, $method_lines, 
			'handle_callback should have substantial implementation (>50 lines), not just a stub' );
	}

	/**
	 * Helper: Assert nonce verification happens before processing
	 */
	private function assertNonceVerificationBeforeProcessing( $rest_api ) {
		// Use reflection to check method implementation
		$reflection = new \ReflectionMethod( $rest_api, 'get_discover_performance' );
		$method_source = $this->getMethodSource( $reflection );
		
		// Check that verify_nonce is called early in the method
		$verify_nonce_pos = strpos( $method_source, 'verify_nonce' );
		$dashboard_widgets_pos = strpos( $method_source, 'Dashboard_Widgets' );
		
		$this->assertNotFalse( $verify_nonce_pos, 
			'Method should call verify_nonce' );
		$this->assertNotFalse( $dashboard_widgets_pos, 
			'Method should instantiate Dashboard_Widgets' );
		$this->assertLessThan( $dashboard_widgets_pos, $verify_nonce_pos, 
			'verify_nonce should be called BEFORE Dashboard_Widgets instantiation' );
	}

	/**
	 * Helper: Get method source code using reflection
	 */
	private function getMethodSource( \ReflectionMethod $method ) {
		$filename = $method->getFileName();
		$start_line = $method->getStartLine();
		$end_line = $method->getEndLine();
		
		$file_lines = file( $filename );
		$method_lines = array_slice( $file_lines, $start_line - 1, $end_line - $start_line + 1 );
		
		return implode( '', $method_lines );
	}
}
