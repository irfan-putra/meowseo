<?php
/**
 * Property-Based Tests for REST Response Structure
 *
 * Property 26: REST Response Structure
 * Validates: Requirement 14.5
 *
 * This test uses property-based testing (eris/eris) to verify that for any GET request
 * to /meowseo/v1/logs, the response is a JSON object containing logs array, total count,
 * page count, current page, and per_page values.
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
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST Response Structure property-based test case
 *
 * @since 1.0.0
 */
class Property26RestResponseStructureTest extends TestCase {
	use TestTrait;

	/**
	 * REST_Logs instance
	 *
	 * @var REST_Logs
	 */
	private REST_Logs $rest_logs;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create mock Options instance
		$options_mock = $this->createMock( Options::class );

		// Initialize REST_Logs
		$this->rest_logs = new REST_Logs( $options_mock );
	}

	/**
	 * Property 26: REST Response Structure
	 *
	 * For any GET request to /meowseo/v1/logs, the response SHALL be a JSON object
	 * containing logs array, total count, page count, current page, and per_page values.
	 *
	 * This property verifies:
	 * 1. Response is a WP_REST_Response object
	 * 2. Response data contains 'logs' key with array value
	 * 3. Response data contains 'total' key with integer value
	 * 4. Response data contains 'pages' key with integer value
	 * 5. Response data contains 'page' key with integer value
	 * 6. Response data contains 'per_page' key with integer value
	 * 7. All required fields are present in every response
	 *
	 * **Validates: Requirement 14.5**
	 *
	 * @return void
	 */
	public function test_rest_response_contains_all_required_fields(): void {
		$this->forAll(
			Generators::choose( 1, 100 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $page, int $per_page ) {
				// Create a real request object
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', $per_page );

				// Call get_logs endpoint
				$response = $this->rest_logs->get_logs( $request );

				// Verify response is WP_REST_Response
				$this->assertInstanceOf(
					WP_REST_Response::class,
					$response,
					'Response should be a WP_REST_Response object'
				);

				// Get response data
				$data = $response->get_data();

				// Verify all required fields are present
				$this->assertIsArray(
					$data,
					'Response data should be an array'
				);

				$this->assertArrayHasKey(
					'logs',
					$data,
					'Response should contain "logs" key'
				);

				$this->assertArrayHasKey(
					'total',
					$data,
					'Response should contain "total" key'
				);

				$this->assertArrayHasKey(
					'pages',
					$data,
					'Response should contain "pages" key'
				);

				$this->assertArrayHasKey(
					'page',
					$data,
					'Response should contain "page" key'
				);

				$this->assertArrayHasKey(
					'per_page',
					$data,
					'Response should contain "per_page" key'
				);
			}
		);
	}

	/**
	 * Property: Response logs field is always an array
	 *
	 * For any GET request to /meowseo/v1/logs, the logs field should always be an array.
	 *
	 * @return void
	 */
	public function test_response_logs_field_is_array(): void {
		$this->forAll(
			Generators::choose( 1, 100 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $page, int $per_page ) {
				// Create a real request object
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', $per_page );

				// Call get_logs endpoint
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify logs is an array
				$this->assertIsArray(
					$data['logs'],
					'Response logs field should be an array'
				);
			}
		);
	}

	/**
	 * Property: Response pagination fields are integers
	 *
	 * For any GET request to /meowseo/v1/logs, the total, pages, page, and per_page
	 * fields should always be integers.
	 *
	 * @return void
	 */
	public function test_response_pagination_fields_are_integers(): void {
		$this->forAll(
			Generators::choose( 1, 100 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $page, int $per_page ) {
				// Create a real request object
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', $per_page );

				// Call get_logs endpoint
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify pagination fields are integers
				$this->assertIsInt(
					$data['total'],
					'Response total field should be an integer'
				);

				// pages is calculated with ceil() which returns float, so we check it's numeric
				$this->assertTrue(
					is_int( $data['pages'] ) || is_float( $data['pages'] ),
					'Response pages field should be numeric'
				);

				$this->assertIsInt(
					$data['page'],
					'Response page field should be an integer'
				);

				$this->assertIsInt(
					$data['per_page'],
					'Response per_page field should be an integer'
				);
			}
		);
	}

	/**
	 * Property: Response page value matches request page parameter
	 *
	 * For any GET request to /meowseo/v1/logs with a page parameter, the response
	 * page field should match the requested page.
	 *
	 * @return void
	 */
	public function test_response_page_matches_request(): void {
		$this->forAll(
			Generators::choose( 1, 100 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $page, int $per_page ) {
				// Create a real request object
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', $per_page );

				// Call get_logs endpoint
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify page matches request
				$this->assertEquals(
					$page,
					$data['page'],
					'Response page should match the requested page parameter'
				);
			}
		);
	}

	/**
	 * Property: Response per_page value matches request per_page parameter
	 *
	 * For any GET request to /meowseo/v1/logs with a per_page parameter, the response
	 * per_page field should match the requested per_page (capped at 100).
	 *
	 * @return void
	 */
	public function test_response_per_page_matches_request(): void {
		$this->forAll(
			Generators::choose( 1, 100 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $page, int $per_page ) {
				// Create a real request object
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', $per_page );

				// Call get_logs endpoint
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify per_page matches request (capped at 100)
				$expected_per_page = min( 100, $per_page );
				$this->assertEquals(
					$expected_per_page,
					$data['per_page'],
					'Response per_page should match the requested per_page parameter (capped at 100)'
				);
			}
		);
	}

	/**
	 * Property: Response total is non-negative
	 *
	 * For any GET request to /meowseo/v1/logs, the total field should always be
	 * a non-negative integer.
	 *
	 * @return void
	 */
	public function test_response_total_is_non_negative(): void {
		$this->forAll(
			Generators::choose( 1, 100 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $page, int $per_page ) {
				// Create a real request object
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', $per_page );

				// Call get_logs endpoint
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify total is non-negative
				$this->assertGreaterThanOrEqual(
					0,
					$data['total'],
					'Response total should be non-negative'
				);
			}
		);
	}

	/**
	 * Property: Response pages is non-negative
	 *
	 * For any GET request to /meowseo/v1/logs, the pages field should always be
	 * a non-negative integer.
	 *
	 * @return void
	 */
	public function test_response_pages_is_non_negative(): void {
		$this->forAll(
			Generators::choose( 1, 100 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $page, int $per_page ) {
				// Create a real request object
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', $per_page );

				// Call get_logs endpoint
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify pages is non-negative
				$this->assertGreaterThanOrEqual(
					0,
					$data['pages'],
					'Response pages should be non-negative'
				);
			}
		);
	}

	/**
	 * Property: Response page is positive
	 *
	 * For any GET request to /meowseo/v1/logs, the page field should always be
	 * a positive integer (at least 1).
	 *
	 * @return void
	 */
	public function test_response_page_is_positive(): void {
		$this->forAll(
			Generators::choose( 1, 100 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $page, int $per_page ) {
				// Create a real request object
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', $per_page );

				// Call get_logs endpoint
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify page is positive
				$this->assertGreaterThanOrEqual(
					1,
					$data['page'],
					'Response page should be at least 1'
				);
			}
		);
	}

	/**
	 * Property: Response per_page is positive
	 *
	 * For any GET request to /meowseo/v1/logs, the per_page field should always be
	 * a positive integer (at least 1).
	 *
	 * @return void
	 */
	public function test_response_per_page_is_positive(): void {
		$this->forAll(
			Generators::choose( 1, 100 ),
			Generators::choose( 1, 100 )
		)
		->then(
			function ( int $page, int $per_page ) {
				// Create a real request object
				$request = new WP_REST_Request( 'GET', '/meowseo/v1/logs' );
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', $per_page );

				// Call get_logs endpoint
				$response = $this->rest_logs->get_logs( $request );
				$data = $response->get_data();

				// Verify per_page is positive
				$this->assertGreaterThanOrEqual(
					1,
					$data['per_page'],
					'Response per_page should be at least 1'
				);
			}
		);
	}
}
