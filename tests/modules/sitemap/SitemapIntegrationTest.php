<?php
/**
 * Sitemap Module Integration Tests
 *
 * Tests the sitemap module integration with Module_Manager.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Sitemap;

use PHPUnit\Framework\TestCase;
use MeowSEO\Module_Manager;
use MeowSEO\Options;

/**
 * Sitemap integration test case
 */
class SitemapIntegrationTest extends TestCase {

	/**
	 * Test sitemap module loads via Module_Manager
	 */
	public function test_sitemap_module_loads(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get_enabled_modules' )
			->willReturn( array( 'sitemap' ) );

		$manager = new Module_Manager( $options );
		$manager->boot();

		$this->assertTrue( $manager->is_active( 'sitemap' ) );
		
		$sitemap = $manager->get_module( 'sitemap' );
		$this->assertNotNull( $sitemap );
		$this->assertSame( 'sitemap', $sitemap->get_id() );
	}

	/**
	 * Test sitemap module does not load when disabled
	 */
	public function test_sitemap_module_not_loaded_when_disabled(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get_enabled_modules' )
			->willReturn( array( 'meta' ) ); // Only meta enabled

		$manager = new Module_Manager( $options );
		$manager->boot();

		$this->assertFalse( $manager->is_active( 'sitemap' ) );
		$this->assertNull( $manager->get_module( 'sitemap' ) );
	}
}
