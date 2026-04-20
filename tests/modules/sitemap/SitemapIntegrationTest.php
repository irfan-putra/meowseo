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

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use MeowSEO\Module_Manager;
use MeowSEO\Options;

/**
 * Sitemap integration test case
 */
class SitemapIntegrationTest extends TestCase {

	/**
	 * Set up test
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Skip if WordPress functions are already defined (can't mock with Patchwork).
		if ( function_exists( 'wp_upload_dir' ) ) {
			$this->markTestSkipped( 'WordPress functions already defined. These tests require Brain\Monkey mocking which cannot override existing functions.' );
		}
		
		Monkey\setUp();

		// Mock WordPress functions needed by sitemap module
		Functions\when( 'wp_upload_dir' )->justReturn( [
			'basedir' => sys_get_temp_dir() . '/meowseo-test-uploads',
			'baseurl' => 'http://example.com/wp-content/uploads',
		] );
		Functions\when( 'trailingslashit' )->alias( function( $string ) {
			return rtrim( $string, '/\\' ) . '/';
		} );
		Functions\when( 'wp_mkdir_p' )->justReturn( true );
	}

	/**
	 * Tear down test
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

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
