<?php
/**
 * Redirects Module Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Redirects;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Redirects\Redirects;
use MeowSEO\Options;

/**
 * Redirects module test case
 */
class RedirectsTest extends TestCase {

	/**
	 * Test module ID
	 */
	public function test_get_id(): void {
		$options = $this->createMock( Options::class );
		$redirects = new Redirects( $options );

		$this->assertSame( 'redirects', $redirects->get_id() );
	}

	/**
	 * Test module boots without errors
	 */
	public function test_boot(): void {
		$options = $this->createMock( Options::class );
		$redirects = new Redirects( $options );

		// Boot should not throw any exceptions
		$this->expectNotToPerformAssertions();
		$redirects->boot();
	}
}
