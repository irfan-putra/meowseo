<?php
/**
 * Sitemap Logging Tests
 *
 * Tests logging integration in the Sitemap module.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Sitemap;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Sitemap\Sitemap_Generator;
use MeowSEO\Options;

/**
 * Test sitemap logging integration
 */
class SitemapLoggingTest extends TestCase {

	/**
	 * Test that Logger is imported in Sitemap class
	 */
	public function test_logger_imported_in_sitemap_class(): void {
		$file_content = file_get_contents( __DIR__ . '/../../../includes/modules/sitemap/class-sitemap.php' );
		$this->assertStringContainsString( 'use MeowSEO\Helpers\Logger;', $file_content );
	}

	/**
	 * Test that Logger is imported in Sitemap_Generator class
	 */
	public function test_logger_imported_in_sitemap_generator_class(): void {
		$file_content = file_get_contents( __DIR__ . '/../../../includes/modules/sitemap/class-sitemap-generator.php' );
		$this->assertStringContainsString( 'use MeowSEO\Helpers\Logger;', $file_content );
	}

	/**
	 * Test that error logging is present for generation failures
	 */
	public function test_error_logging_for_generation_failures(): void {
		$file_content = file_get_contents( __DIR__ . '/../../../includes/modules/sitemap/class-sitemap.php' );
		$this->assertStringContainsString( "Logger::error(", $file_content );
		$this->assertStringContainsString( "'Sitemap generation failed'", $file_content );
	}

	/**
	 * Test that info logging is present for cache regeneration
	 */
	public function test_info_logging_for_cache_regeneration(): void {
		$file_content = file_get_contents( __DIR__ . '/../../../includes/modules/sitemap/class-sitemap.php' );
		$this->assertStringContainsString( "Logger::info(", $file_content );
		$this->assertStringContainsString( "'Sitemap cache regenerated'", $file_content );
	}

	/**
	 * Test that error logging is present for file write failures
	 */
	public function test_error_logging_for_file_write_failures(): void {
		$file_content = file_get_contents( __DIR__ . '/../../../includes/modules/sitemap/class-sitemap-generator.php' );
		$this->assertStringContainsString( "Logger::error(", $file_content );
		$this->assertStringContainsString( "'Sitemap file write failed'", $file_content );
	}

	/**
	 * Test that logging includes post_type in context
	 */
	public function test_logging_includes_post_type_context(): void {
		$file_content = file_get_contents( __DIR__ . '/../../../includes/modules/sitemap/class-sitemap.php' );
		$this->assertStringContainsString( "'post_type'", $file_content );
	}

	/**
	 * Test that logging includes entry_count in context
	 */
	public function test_logging_includes_entry_count_context(): void {
		$file_content = file_get_contents( __DIR__ . '/../../../includes/modules/sitemap/class-sitemap.php' );
		$this->assertStringContainsString( "'entry_count'", $file_content );
	}

	/**
	 * Test that logging includes file_path in context
	 */
	public function test_logging_includes_file_path_context(): void {
		$file_content = file_get_contents( __DIR__ . '/../../../includes/modules/sitemap/class-sitemap-generator.php' );
		$this->assertStringContainsString( "'file_path'", $file_content );
	}

	/**
	 * Test that logging includes error message in context
	 */
	public function test_logging_includes_error_message_context(): void {
		$file_content = file_get_contents( __DIR__ . '/../../../includes/modules/sitemap/class-sitemap.php' );
		$this->assertStringContainsString( "'error'", $file_content );
	}
}
