<?php
/**
 * IndexNow Client Tests
 *
 * Unit tests for the IndexNow_Client class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\IndexNow;

use PHPUnit\Framework\TestCase;
use MeowSEO\Options;
use MeowSEO\Modules\IndexNow\IndexNowClient;
use MeowSEO\Modules\IndexNow\Submission_Queue;
use MeowSEO\Modules\IndexNow\Submission_Logger;

/**
 * IndexNowClient test case
 *
 * @since 1.0.0
 */
class Test_IndexNow_Client extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Submission queue instance
	 *
	 * @var Submission_Queue
	 */
	private Submission_Queue $queue;

	/**
	 * Submission logger instance
	 *
	 * @var Submission_Logger
	 */
	private Submission_Logger $logger;

	/**
	 * IndexNow client instance
	 *
	 * @var IndexNowClient
	 */
	private IndexNowClient $client;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$this->queue   = new Submission_Queue( $this->options );
		$this->logger  = new Submission_Logger();
		$this->client  = new IndexNowClient( $this->options, $this->queue, $this->logger );

		// Clear any existing options
		delete_option( 'meowseo_indexnow_api_key' );
		delete_option( 'meowseo_indexnow_queue' );
		delete_option( 'meowseo_indexnow_log' );
		delete_option( 'meowseo_indexnow_last_submission' );
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();

		// Clean up options
		delete_option( 'meowseo_indexnow_api_key' );
		delete_option( 'meowseo_indexnow_queue' );
		delete_option( 'meowseo_indexnow_log' );
		delete_option( 'meowseo_indexnow_last_submission' );
	}

	/**
	 * Test IndexNowClient instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( IndexNowClient::class, $this->client );
	}

	/**
	 * Test generate_api_key creates 32-character hex string
	 *
	 * Validates: Requirement 5.5 - Generate 32-character hexadecimal key
	 *
	 * @return void
	 */
	public function test_generate_api_key_creates_32_char_hex(): void {
		$key = $this->client->generate_api_key();

		// Should be 32 characters (16 bytes * 2 hex chars per byte).
		$this->assertEquals( 32, strlen( $key ), 'API key should be 32 characters' );

		// Should be valid hexadecimal.
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{32}$/i', $key, 'API key should be hexadecimal' );
	}

	/**
	 * Test generate_api_key creates unique keys
	 *
	 * @return void
	 */
	public function test_generate_api_key_creates_unique_keys(): void {
		$key1 = $this->client->generate_api_key();
		$key2 = $this->client->generate_api_key();

		$this->assertNotEquals( $key1, $key2, 'Generated keys should be unique' );
	}

	/**
	 * Test get_api_key returns existing key
	 *
	 * Validates: Requirement 5.6 - Store API key in WordPress options
	 *
	 * @return void
	 */
	public function test_get_api_key_returns_existing_key(): void {
		$test_key = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4';
		$this->options->set( 'indexnow_api_key', $test_key );
		$this->options->save();

		$key = $this->client->get_api_key();

		$this->assertEquals( $test_key, $key, 'Should return existing API key' );
	}

	/**
	 * Test get_api_key generates and stores new key if none exists
	 *
	 * Validates: Requirement 5.5 - Generate new API key if none configured
	 *
	 * @return void
	 */
	public function test_get_api_key_generates_and_stores_new_key(): void {
		// Ensure no key exists
		$this->options->set( 'indexnow_api_key', '' );
		$this->options->save();

		$key = $this->client->get_api_key();

		// Should generate a key
		$this->assertNotEmpty( $key, 'Should generate API key' );
		$this->assertEquals( 32, strlen( $key ), 'Generated key should be 32 characters' );

		// Should be stored in options
		$stored_key = $this->options->get( 'indexnow_api_key' );
		$this->assertEquals( $key, $stored_key, 'Generated key should be stored in options' );
	}

	/**
	 * Test is_enabled returns false by default
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_false_by_default(): void {
		$this->assertFalse( $this->client->is_enabled(), 'IndexNow should be disabled by default' );
	}

	/**
	 * Test is_enabled returns true when enabled
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_true_when_enabled(): void {
		$this->options->set( 'indexnow_enabled', true );
		$this->options->save();

		$this->assertTrue( $this->client->is_enabled(), 'IndexNow should be enabled' );
	}

	/**
	 * Test submit_urls returns error when no URLs provided
	 *
	 * @return void
	 */
	public function test_submit_urls_returns_error_when_no_urls(): void {
		$result = $this->client->submit_urls( array() );

		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * Test submit_url delegates to submit_urls
	 *
	 * @return void
	 */
	public function test_submit_url_delegates_to_submit_urls(): void {
		// Set a test API key
		$test_key = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4';
		$this->options->set( 'indexnow_api_key', $test_key );
		$this->options->save();

		// Mock wp_remote_post to return success
		add_filter(
			'pre_http_request',
			function( $preempt, $r, $url ) {
				if ( strpos( $url, 'api.indexnow.org' ) !== false ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => '',
					);
				}
				return $preempt;
			},
			10,
			3
		);

		$result = $this->client->submit_url( 'https://example.com/test' );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );

		remove_all_filters( 'pre_http_request' );
	}
}
