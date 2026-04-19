<?php
/**
 * IndexNow Module
 *
 * Manages instant URL indexing via IndexNow API.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\IndexNow;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IndexNow module class
 *
 * Implements the Module interface to provide IndexNow instant indexing.
 * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12
 *
 * @since 1.0.0
 */
class IndexNow implements Module {

	/**
	 * Module ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const MODULE_ID = 'indexnow';

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * IndexNow_Client instance
	 *
	 * @since 1.0.0
	 * @var IndexNowClient
	 */
	private IndexNowClient $client;

	/**
	 * Submission_Queue instance
	 *
	 * @since 1.0.0
	 * @var Submission_Queue
	 */
	private Submission_Queue $queue;

	/**
	 * Submission_Logger instance
	 *
	 * @since 1.0.0
	 * @var Submission_Logger
	 */
	private Submission_Logger $logger;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		// Initialize queue and logger.
		$this->queue  = new Submission_Queue( $options );
		$this->logger = new Submission_Logger();

		// Initialize client.
		$this->client = new IndexNowClient( $options, $this->queue, $this->logger );
	}

	/**
	 * Boot the module
	 *
	 * Register hooks and initialize module functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		// Register custom cron interval (10 seconds).
		add_filter( 'cron_schedules', array( $this, 'register_cron_interval' ) );

		// Boot the IndexNow_Client.
		$this->client->boot();
	}

	/**
	 * Register custom cron interval
	 *
	 * Registers a 10-second cron interval for queue processing.
	 * Requirement 5.8: Register custom cron interval (10 seconds).
	 *
	 * @since 1.0.0
	 * @param array $schedules Existing cron schedules.
	 * @return array Updated cron schedules.
	 */
	public function register_cron_interval( array $schedules ): array {
		$schedules['meowseo_indexnow_interval'] = array(
			'interval' => 10,
			'display'  => __( 'Every 10 seconds', 'meowseo' ),
		);

		return $schedules;
	}

	/**
	 * Get module ID
	 *
	 * @since 1.0.0
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return self::MODULE_ID;
	}

	/**
	 * Get client instance
	 *
	 * @since 1.0.0
	 * @return IndexNowClient Client instance.
	 */
	public function get_client(): IndexNowClient {
		return $this->client;
	}

	/**
	 * Get queue instance
	 *
	 * @since 1.0.0
	 * @return Submission_Queue Queue instance.
	 */
	public function get_queue(): Submission_Queue {
		return $this->queue;
	}

	/**
	 * Get logger instance
	 *
	 * @since 1.0.0
	 * @return Submission_Logger Logger instance.
	 */
	public function get_logger(): Submission_Logger {
		return $this->logger;
	}
}
