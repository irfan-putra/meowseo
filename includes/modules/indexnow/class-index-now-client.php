<?php
/**
 * IndexNow Client
 *
 * Submits URL updates to IndexNow API for instant indexing by Bing, Yandex, and Seznam.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\IndexNow;

use MeowSEO\Options;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IndexNowClient class
 *
 * Submits URL updates to IndexNow API for instant indexing.
 * Implements retry logic with exponential backoff for failed submissions.
 *
 * @since 1.0.0
 */
class IndexNowClient {

	/**
	 * IndexNow API endpoint
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const API_ENDPOINT = 'https://api.indexnow.org/indexnow';

	/**
	 * Maximum retry attempts
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const MAX_RETRIES = 3;

	/**
	 * Base retry delay in seconds
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const BASE_RETRY_DELAY = 5;

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Submission queue instance
	 *
	 * @since 1.0.0
	 * @var Submission_Queue
	 */
	private Submission_Queue $queue;

	/**
	 * Submission logger instance
	 *
	 * @since 1.0.0
	 * @var Submission_Logger
	 */
	private Submission_Logger $logger;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options              $options Options instance.
	 * @param Submission_Queue     $queue   Submission queue instance.
	 * @param Submission_Logger    $logger  Submission logger instance.
	 */
	public function __construct( Options $options, Submission_Queue $queue, Submission_Logger $logger ) {
		$this->options = $options;
		$this->queue   = $queue;
		$this->logger  = $logger;
	}

	/**
	 * Boot the client
	 *
	 * Initializes hooks and cron events for IndexNow submission.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Hook into post publish/update.
		add_action( 'transition_post_status', array( $this, 'handle_post_transition' ), 10, 3 );

		// Process queued submissions.
		add_action( 'meowseo_process_indexnow_queue', array( $this, 'process_queue' ) );

		// Schedule queue processing if not already scheduled.
		if ( ! wp_next_scheduled( 'meowseo_process_indexnow_queue' ) ) {
			wp_schedule_event( time(), 'meowseo_indexnow_interval', 'meowseo_process_indexnow_queue' );
		}
	}

	/**
	 * Handle post status transition
	 *
	 * Queues URL for submission when post is published or updated.
	 * Requirements 5.1, 5.2: Submit on publish and update.
	 *
	 * @since 1.0.0
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_post_transition( string $new_status, string $old_status, \WP_Post $post ): void {
		// Only submit when post is published.
		if ( 'publish' !== $new_status ) {
			return;
		}

		// Skip if post type is not public.
		if ( ! is_post_type_viewable( $post->post_type ) ) {
			return;
		}

		$url = get_permalink( $post );

		if ( ! $url ) {
			return;
		}

		// Add to queue instead of immediate submission.
		$this->queue->add( $url );
	}

	/**
	 * Process queue
	 *
	 * Processes queued URLs by submitting batches to IndexNow API.
	 * Called by WP-Cron event.
	 * Requirement 5.8: Hook Submission_Queue::process() to cron event.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function process_queue(): void {
		$result = $this->queue->process();

		// If no URLs to process, return early.
		if ( ! isset( $result['urls'] ) || empty( $result['urls'] ) ) {
			return;
		}

		// Submit the batch.
		$this->submit_urls( $result['urls'] );
	}

	/**
	 * Submit single URL
	 *
	 * Submits a single URL to IndexNow API.
	 * Requirement 5.1: Submit post URL to api.indexnow.org.
	 *
	 * @since 1.0.0
	 * @param string $url URL to submit.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function submit_url( string $url ) {
		return $this->submit_urls( array( $url ) );
	}

	/**
	 * Submit multiple URLs
	 *
	 * Submits multiple URLs to IndexNow API.
	 * Requirement 5.1, 5.2: Submit URLs to api.indexnow.org.
	 *
	 * @since 1.0.0
	 * @param array $urls URLs to submit.
	 * @return array Submission results with success status and message.
	 */
	public function submit_urls( array $urls ): array {
		// Validate URLs.
		if ( empty( $urls ) ) {
			return array(
				'success' => false,
				'error'   => __( 'No URLs provided', 'meowseo' ),
			);
		}

		// Get API key.
		$api_key = $this->get_api_key();

		if ( empty( $api_key ) ) {
			return array(
				'success' => false,
				'error'   => __( 'IndexNow API key not configured', 'meowseo' ),
			);
		}

		// Make request with retry logic.
		$result = $this->make_request_with_retry( $urls );

		// Log submission.
		$this->logger->log( $urls, $result );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'error'   => $result->get_error_message(),
			);
		}

		return array(
			'success'         => true,
			'urls_submitted'  => count( $urls ),
		);
	}

	/**
	 * Make request with retry logic
	 *
	 * Attempts to submit URLs to IndexNow API with exponential backoff retry.
	 * Requirement 5.11: Retry up to 3 times with exponential backoff (5s, 10s, 20s).
	 *
	 * @since 1.0.0
	 * @param array $urls        URLs to submit.
	 * @param int   $retry_count Current retry attempt count.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function make_request_with_retry( array $urls, int $retry_count = 0 ) {
		$result = $this->make_request( $urls );

		// If successful or max retries reached, return result.
		if ( ! is_wp_error( $result ) || $retry_count >= self::MAX_RETRIES ) {
			return $result;
		}

		// Calculate exponential backoff delay: 5s, 10s, 20s.
		$delay = self::BASE_RETRY_DELAY * pow( 2, $retry_count );

		// Log retry attempt.
		$this->logger->log(
			$urls,
			new WP_Error(
				'indexnow_retry',
				sprintf(
					/* translators: %1$d: retry count, %2$d: delay in seconds */
					__( 'Retry attempt %1$d after %2$d seconds', 'meowseo' ),
					$retry_count + 1,
					$delay
				)
			)
		);

		// Wait before retrying.
		sleep( $delay );

		// Retry recursively.
		return $this->make_request_with_retry( $urls, $retry_count + 1 );
	}

	/**
	 * Make request to IndexNow API
	 *
	 * Posts URL list to IndexNow API endpoint.
	 * Requirement 5.1, 5.2, 5.3: POST to api.indexnow.org with host, key, and urlList.
	 *
	 * @since 1.0.0
	 * @param array $urls URLs to submit.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function make_request( array $urls ) {
		$api_key = $this->get_api_key();
		$host    = parse_url( home_url(), PHP_URL_HOST );

		if ( ! $host ) {
			return new WP_Error(
				'indexnow_invalid_host',
				__( 'Unable to determine site host', 'meowseo' )
			);
		}

		// Build request body.
		$body = array(
			'host'    => $host,
			'key'     => $api_key,
			'urlList' => $urls,
		);

		// Make POST request to IndexNow API.
		$response = wp_remote_post(
			self::API_ENDPOINT,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 10,
			)
		);

		// Handle request errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check response status code.
		$status_code = wp_remote_retrieve_response_code( $response );

		// Requirement 5.4: Handle API responses (200/202 = success, other = error).
		if ( ! in_array( $status_code, array( 200, 202 ), true ) ) {
			return new WP_Error(
				'indexnow_request_failed',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'IndexNow request failed with status code %d', 'meowseo' ),
					$status_code
				)
			);
		}

		return true;
	}

	/**
	 * Get API key
	 *
	 * Retrieves stored API key or generates a new one if not configured.
	 * Requirement 5.5, 5.6: Generate and store API key.
	 *
	 * @since 1.0.0
	 * @return string API key.
	 */
	public function get_api_key(): string {
		$api_key = $this->options->get( 'indexnow_api_key', '' );

		// If no key exists, generate and store one.
		if ( empty( $api_key ) ) {
			$api_key = $this->generate_api_key();
			$this->options->set( 'indexnow_api_key', $api_key );
			$this->options->save();
		}

		return $api_key;
	}

	/**
	 * Generate API key
	 *
	 * Generates a random 32-character hexadecimal API key.
	 * Requirement 5.5: Generate 32-character hexadecimal key.
	 *
	 * @since 1.0.0
	 * @return string Generated API key (32 hex characters).
	 */
	public function generate_api_key(): string {
		// Generate 16 random bytes and convert to 32-character hex string.
		return bin2hex( random_bytes( 16 ) );
	}

	/**
	 * Check if IndexNow is enabled
	 *
	 * Checks if IndexNow submissions are enabled in settings.
	 *
	 * @since 1.0.0
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		return (bool) $this->options->get( 'indexnow_enabled', false );
	}
}
