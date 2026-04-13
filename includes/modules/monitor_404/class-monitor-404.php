<?php
/**
 * Monitor 404 Module
 *
 * Detects 404 responses and buffers them in Object Cache for efficient batch processing.
 * Prevents synchronous database writes during requests.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\Monitor_404
 */

namespace MeowSEO\Modules\Monitor_404;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\Cache;
use MeowSEO\Helpers\DB;
use MeowSEO\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Monitor 404 Module class
 *
 * Implements buffered 404 logging with per-minute bucket keys.
 */
class Monitor_404 implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module.
	 *
	 * Register hooks for 404 detection and cron flushing.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Hook into wp to detect 404 responses.
		add_action( 'wp', array( $this, 'detect_404' ), 1 );

		// Register custom cron interval.
		add_filter( 'cron_schedules', array( $this, 'register_cron_interval' ) );

		// Register cron hook for flushing buffered data.
		add_action( 'meowseo_flush_404_cron', array( $this, 'flush_404_buffer' ) );

		// Schedule cron event if not already scheduled.
		if ( ! wp_next_scheduled( 'meowseo_flush_404_cron' ) ) {
			wp_schedule_event( time(), 'meowseo_60s', 'meowseo_flush_404_cron' );
		}

		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$rest = new Monitor_404_REST();
		$rest->register_routes();
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'monitor_404';
	}

	/**
	 * Register custom 60-second cron interval.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified schedules.
	 */
	public function register_cron_interval( array $schedules ): array {
		$schedules['meowseo_60s'] = array(
			'interval' => 60,
			'display'  => __( 'Every 60 seconds', 'meowseo' ),
		);

		return $schedules;
	}

	/**
	 * Detect 404 responses and buffer in Object Cache.
	 *
	 * Uses per-minute bucket keys for efficient batching.
	 * Prevents synchronous database writes during requests.
	 *
	 * @return void
	 */
	public function detect_404(): void {
		if ( ! is_404() ) {
			return;
		}

		// Get current bucket key (per-minute).
		$bucket_key = $this->get_bucket_key();

		// Get existing bucket data.
		$bucket = Cache::get( $bucket_key );
		if ( ! is_array( $bucket ) ) {
			$bucket = array();
		}

		// Prepare 404 hit data.
		$hit = array(
			'url'        => $this->get_request_url(),
			'referrer'   => $this->get_referrer(),
			'user_agent' => $this->get_user_agent(),
			'timestamp'  => time(),
		);

		// Append to bucket.
		$bucket[] = $hit;

		// Store bucket with 120-second TTL (2 minutes to ensure cron catches it).
		Cache::set( $bucket_key, $bucket, 120 );
	}

	/**
	 * Flush buffered 404 data to database.
	 *
	 * Runs via WP-Cron every 60 seconds.
	 * Uses bulk INSERT with ON DUPLICATE KEY UPDATE to preserve hit counts.
	 *
	 * @return void
	 */
	public function flush_404_buffer(): void {
		// Get bucket keys for the past 2 minutes.
		$bucket_keys = $this->get_recent_bucket_keys( 2 );

		$all_hits = array();

		foreach ( $bucket_keys as $bucket_key ) {
			$bucket = Cache::get( $bucket_key );

			if ( is_array( $bucket ) && ! empty( $bucket ) ) {
				$all_hits = array_merge( $all_hits, $bucket );

				// Delete the bucket after reading.
				Cache::delete( $bucket_key );
			}
		}

		if ( empty( $all_hits ) ) {
			return;
		}

		// Aggregate hits by URL.
		$aggregated = $this->aggregate_hits( $all_hits );

		// Bulk upsert to database.
		DB::bulk_upsert_404( $aggregated );
	}

	/**
	 * Get current bucket key.
	 *
	 * Format: meowseo_404_{YYYYMMDD_HHmm}
	 *
	 * @return string Bucket key.
	 */
	private function get_bucket_key(): string {
		return '404_' . gmdate( 'Ymd_Hi' );
	}

	/**
	 * Get recent bucket keys.
	 *
	 * @param int $minutes Number of minutes to look back.
	 * @return array Array of bucket keys.
	 */
	private function get_recent_bucket_keys( int $minutes ): array {
		$keys = array();

		for ( $i = 0; $i < $minutes; $i++ ) {
			$timestamp = time() - ( $i * 60 );
			$keys[]    = '404_' . gmdate( 'Ymd_Hi', $timestamp );
		}

		return $keys;
	}

	/**
	 * Aggregate hits by URL.
	 *
	 * Combines multiple hits for the same URL into a single row with hit count.
	 *
	 * @param array $hits Array of hit data.
	 * @return array Aggregated rows for database insertion.
	 */
	private function aggregate_hits( array $hits ): array {
		$aggregated = array();

		foreach ( $hits as $hit ) {
			$url = $hit['url'];

			if ( ! isset( $aggregated[ $url ] ) ) {
				$aggregated[ $url ] = array(
					'url'        => $url,
					'referrer'   => $hit['referrer'],
					'user_agent' => $hit['user_agent'],
					'hit_count'  => 0,
					'first_seen' => gmdate( 'Y-m-d', $hit['timestamp'] ),
					'last_seen'  => gmdate( 'Y-m-d', $hit['timestamp'] ),
				);
			}

			$aggregated[ $url ]['hit_count']++;

			// Update last_seen if this hit is more recent.
			$last_seen_timestamp = strtotime( $aggregated[ $url ]['last_seen'] );
			if ( $hit['timestamp'] > $last_seen_timestamp ) {
				$aggregated[ $url ]['last_seen'] = gmdate( 'Y-m-d', $hit['timestamp'] );
			}
		}

		return array_values( $aggregated );
	}

	/**
	 * Get the current request URL.
	 *
	 * @return string Request URL.
	 */
	private function get_request_url(): string {
		$protocol = is_ssl() ? 'https://' : 'http://';
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return $protocol . $host . $uri;
	}

	/**
	 * Get the referrer URL.
	 *
	 * @return string Referrer URL or empty string.
	 */
	private function get_referrer(): string {
		return isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	}

	/**
	 * Get the user agent string.
	 *
	 * @return string User agent or empty string.
	 */
	private function get_user_agent(): string {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}
}
