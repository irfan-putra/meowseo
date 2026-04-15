<?php
/**
 * Sitemap Ping
 *
 * Notifies search engines of sitemap updates with rate limiting.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Sitemap;

use MeowSEO\Helpers\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemap Ping class
 *
 * Handles pinging search engines when sitemaps are updated.
 * Implements rate limiting to prevent excessive pings (Requirement 7.4).
 *
 * @since 1.0.0
 */
class Sitemap_Ping {

	/**
	 * Rate limit in seconds (1 hour)
	 *
	 * Prevents pinging search engines more than once per hour (Requirement 7.4).
	 *
	 * @var int
	 */
	private int $rate_limit = 3600;

	/**
	 * Ping search engines with sitemap URL
	 *
	 * Notifies Google and Bing of sitemap updates (Requirement 7.1).
	 * Implements rate limiting to prevent excessive pings (Requirement 7.4).
	 *
	 * @param string $sitemap_url Full URL to the sitemap.
	 * @return bool True if ping was sent, false if skipped due to rate limiting.
	 */
	public function ping( string $sitemap_url ): bool {
		// Check rate limiting (Requirement 7.4)
		if ( ! $this->should_ping() ) {
			Logger::info(
				'Sitemap ping skipped due to rate limiting',
				array(
					'sitemap_url' => $sitemap_url,
					'rate_limit' => $this->rate_limit,
				)
			);
			return false;
		}

		// Get ping URLs for search engines (Requirement 7.1)
		$ping_urls = $this->get_ping_urls( $sitemap_url );

		$success = true;

		// Ping each search engine (Requirement 7.6)
		foreach ( $ping_urls as $engine => $ping_url ) {
			$response = wp_remote_get(
				$ping_url,
				array(
					'timeout' => 10,
					'sslverify' => true,
				)
			);

			if ( is_wp_error( $response ) ) {
				Logger::error(
					'Sitemap ping failed',
					array(
						'search_engine' => $engine,
						'ping_url' => $ping_url,
						'error' => $response->get_error_message(),
					)
				);
				$success = false;
			} else {
				Logger::info(
					'Sitemap ping sent successfully',
					array(
						'search_engine' => $engine,
						'sitemap_url' => $sitemap_url,
						'response_code' => wp_remote_retrieve_response_code( $response ),
					)
				);

				/**
				 * Action when sitemap ping is sent
				 *
				 * Fires after a sitemap ping is successfully sent to a search engine.
				 *
				 * @since 1.0.0
				 * @param string $sitemap_url   Full URL to the sitemap.
				 * @param string $search_engine Search engine name ('google' or 'bing').
				 */
				do_action( 'meowseo_sitemap_ping_sent', $sitemap_url, $engine );
			}
		}

		// Update last ping time (Requirement 7.5)
		$this->update_last_ping_time();

		return $success;
	}

	/**
	 * Check if ping should be sent
	 *
	 * Implements rate limiting by checking last ping timestamp (Requirement 7.4).
	 *
	 * @return bool True if ping should be sent, false if rate limited.
	 */
	private function should_ping(): bool {
		$last_ping = get_option( 'meowseo_sitemap_last_ping', 0 );
		$time_since_last_ping = time() - $last_ping;

		// Allow ping if more than rate_limit seconds have passed (Requirement 7.4)
		return $time_since_last_ping >= $this->rate_limit;
	}

	/**
	 * Update last ping timestamp
	 *
	 * Stores timestamp in wp_options table (Requirement 7.5).
	 *
	 * @return void
	 */
	private function update_last_ping_time(): void {
		update_option( 'meowseo_sitemap_last_ping', time() );
	}

	/**
	 * Get ping URLs for search engines
	 *
	 * Returns Google and Bing ping endpoints (Requirement 7.1).
	 *
	 * @param string $sitemap_url Full URL to the sitemap.
	 * @return array Array of search engine ping URLs.
	 */
	private function get_ping_urls( string $sitemap_url ): array {
		return array(
			'google' => 'https://www.google.com/ping?sitemap=' . urlencode( $sitemap_url ),
			'bing'   => 'https://www.bing.com/ping?sitemap=' . urlencode( $sitemap_url ),
		);
	}
}
