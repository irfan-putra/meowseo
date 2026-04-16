<?php
/**
 * Public SEO Endpoints Tests
 *
 * Tests for the public SEO REST endpoints.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Public SEO Endpoints test case
 *
 * @since 1.0.0
 */
class Test_Public_SEO_Endpoints extends TestCase {

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * REST API instance
	 *
	 * @var \MeowSEO\REST_API
	 */
	private \MeowSEO\REST_API $rest_api;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a test post
		$this->post_id = wp_insert_post( array(
			'post_title'   => 'Test Post',
			'post_content' => 'Test content',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		) );

		// Create REST API instance
		$options = new \MeowSEO\Options();
		$module_manager = new \MeowSEO\Module_Manager( $options );
		$this->rest_api = new \MeowSEO\REST_API( $options, $module_manager );
	}

	/**
	 * Test GET /meowseo/v1/seo/post/{id} endpoint
	 *
	 * @return void
	 */
	public function test_get_seo_data_by_post_id(): void {
		// Create REST request
		$request = new \WP_REST_Request( 'GET', '/meowseo/v1/seo/post/' . $this->post_id );

		// Call the endpoint callback directly
		$response = $this->rest_api->get_seo_data_by_post_id( $request );

		// Assert response is successful
		$this->assertEquals( 200, $response->get_status() );

		// Get response data
		$data = $response->get_data();

		// Assert post_id is correct
		$this->assertEquals( $this->post_id, $data['post_id'] );

		// Assert required fields are present
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'robots', $data );
		$this->assertArrayHasKey( 'canonical', $data );
		$this->assertArrayHasKey( 'og_title', $data );
		$this->assertArrayHasKey( 'og_description', $data );
		$this->assertArrayHasKey( 'og_image', $data );
		$this->assertArrayHasKey( 'twitter_card', $data );
		$this->assertArrayHasKey( 'twitter_title', $data );
		$this->assertArrayHasKey( 'twitter_description', $data );
		$this->assertArrayHasKey( 'twitter_image', $data );
		$this->assertArrayHasKey( 'schema_json', $data );

		// Assert Cache-Control header is set
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertEquals( 'public, max-age=300', $headers['Cache-Control'] );

		// Assert ETag header is set
		$this->assertArrayHasKey( 'ETag', $headers );
		$this->assertNotEmpty( $headers['ETag'] );

		// Assert Vary header is set
		$this->assertArrayHasKey( 'Vary', $headers );
		$this->assertEquals( 'Accept', $headers['Vary'] );
	}

	/**
	 * Test GET /meowseo/v1/seo/post/{id} endpoint returns 404 for non-existent post
	 *
	 * @return void
	 */
	public function test_get_seo_data_by_post_id_not_found(): void {
		// Create REST request with non-existent post ID
		$request = new \WP_REST_Request( 'GET', '/meowseo/v1/seo/post/99999' );

		// Call the endpoint callback directly
		$response = $this->rest_api->get_seo_data_by_post_id( $request );

		// Assert response is 404
		$this->assertEquals( 404, $response->get_status() );

		// Get response data
		$data = $response->get_data();

		// Assert error message
		$this->assertFalse( $data['success'] );
		$this->assertEquals( 'post_not_found', $data['code'] );
	}

	/**
	 * Test GET /meowseo/v1/seo/post/{id} endpoint returns 304 with matching ETag
	 *
	 * @return void
	 */
	public function test_get_seo_data_by_post_id_etag_304(): void {
		// Create first request to get ETag
		$request1 = new \WP_REST_Request( 'GET', '/meowseo/v1/seo/post/' . $this->post_id );
		$response1 = $this->rest_api->get_seo_data_by_post_id( $request1 );
		$headers1 = $response1->get_headers();
		$etag = $headers1['ETag'];

		// Create second request with If-None-Match header
		$request2 = new \WP_REST_Request( 'GET', '/meowseo/v1/seo/post/' . $this->post_id );
		$request2->set_header( 'If-None-Match', $etag );
		$response2 = $this->rest_api->get_seo_data_by_post_id( $request2 );

		// Assert response is 304
		$this->assertEquals( 304, $response2->get_status() );

		// Assert ETag header is still set
		$headers2 = $response2->get_headers();
		$this->assertArrayHasKey( 'ETag', $headers2 );
		$this->assertEquals( $etag, $headers2['ETag'] );
	}

	/**
	 * Test GET /meowseo/v1/schema/post/{id} endpoint
	 *
	 * @return void
	 */
	public function test_get_schema_by_post_id(): void {
		// Create REST request
		$request = new \WP_REST_Request( 'GET', '/meowseo/v1/schema/post/' . $this->post_id );

		// Call the endpoint callback directly
		$response = $this->rest_api->get_schema_by_post_id( $request );

		// Assert response is successful or 400 (if schema module not available)
		$this->assertThat(
			$response->get_status(),
			$this->logicalOr(
				$this->equalTo( 200 ),
				$this->equalTo( 400 )
			)
		);

		// Assert Cache-Control header is set
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertEquals( 'public, max-age=300', $headers['Cache-Control'] );
	}

	/**
	 * Test GET /meowseo/v1/redirects/check?url={url} endpoint
	 *
	 * @return void
	 */
	public function test_check_redirect_by_url(): void {
		// Create REST request
		$request = new \WP_REST_Request( 'GET', '/meowseo/v1/redirects/check' );
		$request->set_param( 'url', 'http://example.com/old-page' );

		// Call the endpoint callback directly
		$response = $this->rest_api->check_redirect_by_url( $request );

		// Assert response is successful
		$this->assertEquals( 200, $response->get_status() );

		// Get response data
		$data = $response->get_data();

		// Assert required fields are present
		$this->assertArrayHasKey( 'url', $data );
		$this->assertArrayHasKey( 'has_redirect', $data );
		$this->assertArrayHasKey( 'redirect', $data );

		// Assert Cache-Control header is set
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertEquals( 'public, max-age=300', $headers['Cache-Control'] );
	}

	/**
	 * Test GET /meowseo/v1/redirects/check?url={url} endpoint without URL parameter
	 *
	 * @return void
	 */
	public function test_check_redirect_by_url_missing_url(): void {
		// Create REST request without URL parameter
		$request = new \WP_REST_Request( 'GET', '/meowseo/v1/redirects/check' );

		// Call the endpoint callback directly
		$response = $this->rest_api->check_redirect_by_url( $request );

		// Assert response is 400
		$this->assertEquals( 400, $response->get_status() );

		// Get response data
		$data = $response->get_data();

		// Assert error message
		$this->assertFalse( $data['success'] );
		$this->assertEquals( 'missing_url', $data['code'] );
	}

	/**
	 * Test public_seo_permission callback
	 *
	 * @return void
	 */
	public function test_public_seo_permission(): void {
		// Create REST request
		$request = new \WP_REST_Request( 'GET', '/meowseo/v1/seo/post/' . $this->post_id );

		// Assert permission is granted for published post
		$this->assertTrue( $this->rest_api->public_seo_permission( $request ) );
	}

	/**
	 * Test public_seo_permission callback denies access to unpublished post
	 *
	 * @return void
	 */
	public function test_public_seo_permission_unpublished(): void {
		// Create unpublished post
		$unpublished_post_id = wp_insert_post( array(
			'post_title'   => 'Unpublished Post',
			'post_content' => 'Test content',
			'post_status'  => 'draft',
			'post_type'    => 'post',
		) );

		// Create REST request
		$request = new \WP_REST_Request( 'GET', '/meowseo/v1/seo/post/' . $unpublished_post_id );

		// Assert permission is denied for unpublished post
		$this->assertFalse( $this->rest_api->public_seo_permission( $request ) );
	}

	/**
	 * Test validate_url callback
	 *
	 * @return void
	 */
	public function test_validate_url(): void {
		// Assert valid URL passes validation
		$this->assertTrue( $this->rest_api->validate_url( 'http://example.com' ) );
		$this->assertTrue( $this->rest_api->validate_url( 'https://example.com/path' ) );

		// Assert invalid URL fails validation
		$this->assertFalse( $this->rest_api->validate_url( '' ) );
		$this->assertFalse( $this->rest_api->validate_url( 'not a url' ) );
	}
}
