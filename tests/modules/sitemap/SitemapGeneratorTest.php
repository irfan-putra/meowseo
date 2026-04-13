<?php
/**
 * Sitemap Generator Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Sitemap;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Sitemap\Sitemap_Generator;
use MeowSEO\Options;

/**
 * Sitemap generator test case
 */
class SitemapGeneratorTest extends TestCase {

	/**
	 * Options mock
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Sitemap generator instance
	 *
	 * @var Sitemap_Generator
	 */
	private $generator;

	/**
	 * Set up test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( Options::class );
		$this->generator = new Sitemap_Generator( $this->options );

		// Clean up any existing test files
		$this->cleanupTestFiles();
	}

	/**
	 * Tear down test
	 */
	protected function tearDown(): void {
		$this->cleanupTestFiles();
		parent::tearDown();
	}

	/**
	 * Clean up test files
	 */
	private function cleanupTestFiles(): void {
		$upload_dir = wp_upload_dir();
		$sitemap_dir = trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';

		if ( file_exists( $sitemap_dir ) ) {
			$files = glob( $sitemap_dir . '/*' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}
			rmdir( $sitemap_dir );
		}
	}

	/**
	 * Test index sitemap generation
	 */
	public function test_generate_index(): void {
		$file_path = $this->generator->generate_index();

		$this->assertIsString( $file_path );
		$this->assertFileExists( $file_path );
		$this->assertStringContainsString( 'sitemap-index.xml', $file_path );

		// Verify XML content
		$content = file_get_contents( $file_path );
		$this->assertStringContainsString( '<?xml version="1.0" encoding="UTF-8"?>', $content );
		$this->assertStringContainsString( '<sitemapindex', $content );
		$this->assertStringContainsString( '</sitemapindex>', $content );
	}

	/**
	 * Test child sitemap generation
	 */
	public function test_generate_child(): void {
		$file_path = $this->generator->generate_child( 'post' );

		$this->assertIsString( $file_path );
		$this->assertFileExists( $file_path );
		$this->assertStringContainsString( 'sitemap-post.xml', $file_path );

		// Verify XML content
		$content = file_get_contents( $file_path );
		$this->assertStringContainsString( '<?xml version="1.0" encoding="UTF-8"?>', $content );
		$this->assertStringContainsString( '<urlset', $content );
		$this->assertStringContainsString( '</urlset>', $content );
	}

	/**
	 * Test sitemap deletion
	 */
	public function test_delete_sitemap(): void {
		// Generate a sitemap first
		$file_path = $this->generator->generate_child( 'post' );
		$this->assertFileExists( $file_path );

		// Delete it
		$result = $this->generator->delete_sitemap( 'post' );
		$this->assertTrue( $result );
		$this->assertFileDoesNotExist( $file_path );
	}

	/**
	 * Test delete non-existent sitemap
	 */
	public function test_delete_nonexistent_sitemap(): void {
		$result = $this->generator->delete_sitemap( 'nonexistent' );
		$this->assertTrue( $result );
	}
}
