<?php
/**
 * Internal Links Module Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Internal_Links;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Internal_Links\Internal_Links;
use MeowSEO\Options;

/**
 * Internal Links module test case
 */
class InternalLinksTest extends TestCase {

	/**
	 * Test module ID
	 */
	public function test_get_id(): void {
		$options = $this->createMock( Options::class );
		$internal_links = new Internal_Links( $options );

		$this->assertSame( 'internal_links', $internal_links->get_id() );
	}

	/**
	 * Test module boots without errors
	 */
	public function test_boot(): void {
		$options = $this->createMock( Options::class );
		$internal_links = new Internal_Links( $options );

		// Boot should not throw any exceptions
		$this->expectNotToPerformAssertions();
		$internal_links->boot();
	}
}
