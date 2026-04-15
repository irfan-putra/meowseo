<?php
/**
 * Breadcrumbs Tests
 *
 * Unit tests for the Breadcrumbs helper class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Breadcrumbs;
use MeowSEO\Options;

/**
 * Breadcrumbs test case
 *
 * @since 1.0.0
 */
class Test_Breadcrumbs extends TestCase {

	/**
	 * Breadcrumbs instance
	 *
	 * @var Breadcrumbs
	 */
	private Breadcrumbs $breadcrumbs;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options    = new Options();
		$this->breadcrumbs = new Breadcrumbs( $this->options );
	}

	/**
	 * Test Breadcrumbs instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( Breadcrumbs::class, $this->breadcrumbs );
	}
}
