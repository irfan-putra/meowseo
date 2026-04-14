<?php
/**
 * Sitemap Module Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Sitemap;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Sitemap\Sitemap;
use MeowSEO\Modules\Sitemap\Sitemap_Generator;
use MeowSEO\Options;

/**
 * Sitemap module test case
 */
class SitemapTest extends TestCase {

	/**
	 * Set up test
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Mock WordPress functions
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
