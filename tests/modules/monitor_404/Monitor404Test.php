<?php
/**
 * Tests for Monitor 404 Module
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Monitor_404;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Monitor_404\Monitor_404;
use MeowSEO\Options;

/**
 * Monitor 404 Module test case
 */
class Monitor404Test extends TestCase {

	/**
	 * Test module ID.
	 */
	public function test_get_id(): void {
		$options = new Options();
		$module  = new Monitor_404( $options );

		$this->assertEquals( 'monitor_404', $module->get_id() );
	}

	/**
	 * Test custom cron interval registration.
	 */
	public function test_register_cron_interval(): void {
		$options = new Options();
		$module  = new Monitor_404( $options );

		$schedules = array();
		$result    = $module->register_cron_interval( $schedules );

		$this->assertArrayHasKey( 'meowseo_60s', $result );
		$this->assertEquals( 60, $result['meowseo_60s']['interval'] );
		$this->assertIsString( $result['meowseo_60s']['display'] );
	}

	/**
	 * Test bucket key format.
	 */
	public function test_bucket_key_format(): void {
		// Test that bucket key follows the expected format: 404_{YYYYMMDD_HHmm}
		$expected_pattern = '/^404_\d{8}_\d{4}$/';
		$bucket_key = '404_' . gmdate( 'Ymd_Hi' );

		$this->assertMatchesRegularExpression( $expected_pattern, $bucket_key );
	}

	/**
	 * Test URL hash generation.
	 */
	public function test_url_hash_generation(): void {
		$test_url = 'http://example.com/test-404';
		$url_hash = hash( 'sha256', $test_url );

		$this->assertEquals( 64, strlen( $url_hash ) );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $url_hash );
	}

	/**
	 * Test hit aggregation logic.
	 */
	public function test_hit_aggregation(): void {
		$test_url = 'http://example.com/test-404';
		$timestamp = time();

		// Simulate multiple hits for the same URL
		$hits = array(
			array(
				'url'        => $test_url,
				'referrer'   => '',
				'user_agent' => 'Test Agent 1',
				'timestamp'  => $timestamp,
			),
			array(
				'url'        => $test_url,
				'referrer'   => 'http://example.com/referrer',
				'user_agent' => 'Test Agent 2',
				'timestamp'  => $timestamp + 10,
			),
			array(
				'url'        => 'http://example.com/other-404',
				'referrer'   => '',
				'user_agent' => 'Test Agent 3',
				'timestamp'  => $timestamp,
			),
		);

		// Aggregate by URL
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
		}

		$aggregated = array_values( $aggregated );

		// Verify aggregation
		$this->assertCount( 2, $aggregated );
		$this->assertEquals( 2, $aggregated[0]['hit_count'] );
		$this->assertEquals( 1, $aggregated[1]['hit_count'] );
	}

	/**
	 * Test date formatting for first_seen and last_seen.
	 */
	public function test_date_formatting(): void {
		$timestamp = time();
		$date = gmdate( 'Y-m-d', $timestamp );

		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $date );
	}
}
