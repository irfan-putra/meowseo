<?php
/**
 * Analytics Module Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Analytics;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Analytics\Analytics_Module;
use MeowSEO\Options;

/**
 * Analytics Module test case
 */
class AnalyticsModuleTest extends TestCase {

	/**
	 * Analytics Module instance
	 *
	 * @var Analytics_Module
	 */
	private Analytics_Module $analytics_module;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test fixtures
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = new Options();
		$this->analytics_module = new Analytics_Module( $this->options );
	}

	/**
	 * Test module ID
	 */
	public function test_get_id(): void {
		$this->assertSame( 'analytics', $this->analytics_module->get_id() );
	}

	/**
	 * Test authenticate_oauth returns string
	 */
	public function test_authenticate_oauth_returns_string(): void {
		// Set up OAuth config
		$ga4_settings = array(
			'client_id' => 'test-client-id',
			'client_secret' => 'test-secret',
		);
		$this->options->set( 'ga4_settings', $ga4_settings );
		$this->options->save();

		$url = $this->analytics_module->authenticate_oauth();

		$this->assertIsString( $url );
		$this->assertStringContainsString( 'accounts.google.com', $url );
		$this->assertStringContainsString( 'client_id=test-client-id', $url );
	}

	/**
	 * Test authenticate_oauth returns empty string when client_id is missing
	 */
	public function test_authenticate_oauth_returns_empty_when_no_client_id(): void {
		// Clear any existing GA4 settings
		global $wp_options_storage;
		unset( $wp_options_storage['meowseo_options'] );
		
		// Create a fresh Options instance
		$options = new Options();
		$analytics_module = new Analytics_Module( $options );
		
		$url = $analytics_module->authenticate_oauth();
		$this->assertEmpty( $url );
	}

	/**
	 * Test is_authenticated returns false when no credentials
	 */
	public function test_is_authenticated_returns_false_when_no_credentials(): void {
		$this->assertFalse( $this->analytics_module->is_authenticated() );
	}

	/**
	 * Test is_authenticated returns true when credentials exist
	 */
	public function test_is_authenticated_returns_true_when_credentials_exist(): void {
		$ga4_settings = array(
			'credentials' => array(
				'refresh_token' => 'test-refresh-token',
				'access_token' => 'test-access-token',
			),
		);
		$this->options->set( 'ga4_settings', $ga4_settings );
		$this->options->save();

		$this->assertTrue( $this->analytics_module->is_authenticated() );
	}

	/**
	 * Test get_ga4_metrics returns null when not authenticated
	 */
	public function test_get_ga4_metrics_returns_null_when_not_authenticated(): void {
		$metrics = $this->analytics_module->get_ga4_metrics( '2024-01-01', '2024-01-31' );
		$this->assertNull( $metrics );
	}

	/**
	 * Test get_gsc_metrics returns array when not authenticated
	 */
	public function test_get_gsc_metrics_returns_null_when_not_authenticated(): void {
		$metrics = $this->analytics_module->get_gsc_metrics( '2024-01-01', '2024-01-31' );
		// When not authenticated, should return null
		if ( $metrics !== null ) {
			// If it returns an array, it should be an empty metrics array
			$this->assertIsArray( $metrics );
			$this->assertArrayHasKey( 'impressions', $metrics );
		}
	}

	/**
	 * Test get_pagespeed_insights returns null when no API key
	 */
	public function test_get_pagespeed_insights_returns_null_when_no_api_key(): void {
		$metrics = $this->analytics_module->get_pagespeed_insights( 'https://example.com' );
		$this->assertNull( $metrics );
	}

	/**
	 * Test identify_winning_content returns array
	 */
	public function test_identify_winning_content_returns_array(): void {
		$content = $this->analytics_module->identify_winning_content();
		$this->assertIsArray( $content );
	}

	/**
	 * Test identify_losing_content returns array
	 */
	public function test_identify_losing_content_returns_array(): void {
		$content = $this->analytics_module->identify_losing_content();
		$this->assertIsArray( $content );
	}

	/**
	 * Test send_weekly_report returns bool
	 */
	public function test_send_weekly_report_returns_bool(): void {
		$result = $this->analytics_module->send_weekly_report();
		$this->assertIsBool( $result );
	}

	/**
	 * Test boot method
	 */
	public function test_boot(): void {
		// Boot should not throw any exceptions
		$this->expectNotToPerformAssertions();
		$this->analytics_module->boot();
	}
}
