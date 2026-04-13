<?php
/**
 * Sitemap Module Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Sitemap;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Sitemap\Sitemap;
use MeowSEO\Modules\Sitemap\Sitemap_Generator;
use MeowSEO\Options;

/**
 * Sitemap module test case
 */
class SitemapTest extends TestCase {

	/**
	 * Test module ID
	 */
	public function test_get_id(): void {
		$options = $this->createMock( Options::class );
		$sitemap = new Sitemap( $options );

		$this->assertSame( 'sitemap', $sitemap->get_id() );
	}

	/**
	 * Test module boots without errors
	 */
	public function test_boot(): void {
		$options = $this->createMock( Options::class );
		$sitemap = new Sitemap( $options );

		// Boot should not throw any exceptions
		$this->expectNotToPerformAssertions();
		$sitemap->boot();
	}
}
