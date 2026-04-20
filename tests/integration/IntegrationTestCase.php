<?php
/**
 * Base Integration Test Case
 *
 * Provides common functionality for integration tests that require
 * WordPress test framework.
 *
 * @package MeowSEO
 * @subpackage Tests\Integration
 */

namespace MeowSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration Test Case Base Class
 *
 * Automatically skips tests if WordPress test framework is not available.
 */
abstract class IntegrationTestCase extends TestCase {
	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Skip if WordPress test framework is not available
		if ( ! class_exists( '\WP_UnitTestCase' ) ) {
			$this->markTestSkipped( 'WordPress test framework is not available. These tests require a full WordPress installation with the WordPress Test Suite.' );
		}

		// Skip if activate_plugin function is not available
		if ( ! function_exists( 'activate_plugin' ) ) {
			$this->markTestSkipped( 'WordPress test framework functions are not available. These tests require a full WordPress installation.' );
		}
	}
}
