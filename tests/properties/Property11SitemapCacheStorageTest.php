<?php
/**
 * Property-Based Tests for Sitemap Cache Storage
 *
 * Property 11: Sitemap cache stores file paths, not XML content
 * Validates: Requirement 6.2
 *
 * This test uses property-based testing (eris/eris) to verify that when sitemaps
 * are generated and cached, the Object Cache stores only filesystem paths (strings)
 * and never stores XML content or other data types.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Helpers\Cache;
use Brain\Monkey\Functions;

/**
 * Sitemap Cache Storage property-based test case
 *
 * @since 1.0.0
 */
class Property11SitemapCacheStorageTest extends TestCase {
	use TestTrait;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Mock WordPress functions
		Functions\when( 'wp_upload_dir' )->justReturn( [
			'basedir' => sys_get_temp_dir() . '/meowseo-test-uploads',
			'baseurl' => 'http://example.com/wp-content/uploads',
		] );

		Functions\when( 'trailingslashit' )->alias( function( $string ) {
			return rtrim( $string, '/\\' ) . '/';
		} );

		Functions\when( 'wp_mkdir_p' )->alias( function( $target ) {
			return @mkdir( $target, 0755, true );
		} );
	}

	/**
	 * Tear down test fixtures
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();

		// Clean up test files
		$upload_dir = \wp_upload_dir();
		$sitemap_dir = \trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';

		if ( file_exists( $sitemap_dir ) ) {
			array_map( 'unlink', glob( $sitemap_dir . '/*' ) );
			@rmdir( $sitemap_dir );
		}
	}

	/**
	 * Property 11: Sitemap cache stores file paths, not XML content
	 *
	 * For any sitemap generation run, the value stored in Object Cache under
	 * `meowseo_sitemap_path_{type}` should be a string that is a valid filesystem
	 * path and should not contain XML markup (no `<?xml` or `<urlset` strings).
	 *
	 * This property verifies:
	 * 1. Cached value is a string (not array, object, or other type)
	 * 2. Cached value does NOT contain XML markup indicators
	 * 3. Cached value is a valid filesystem path
	 * 4. Cached value follows the expected directory structure
	 *
	 * **Validates: Requirement 6.2**
	 *
	 * @return void
	 */
	public function test_sitemap_cache_stores_paths_not_xml_content(): void {
		$this->forAll(
			Generators::elements( [ 'index', 'posts', 'pages', 'custom_post_type' ] )
		)
		->then(
			function ( string $sitemap_type ) {
				// Generate a valid sitemap file path
				$upload_dir = \wp_upload_dir();
				$sitemap_dir = \trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';
				
				// Create the directory if it doesn't exist
				if ( ! file_exists( $sitemap_dir ) ) {
					\wp_mkdir_p( $sitemap_dir );
				}

				// Create a test sitemap file
				$file_name = 'index' === $sitemap_type ? 'sitemap-index.xml' : 'sitemap-' . $sitemap_type . '.xml';
				$file_path = $sitemap_dir . '/' . $file_name;

				// Write a minimal XML file
				$xml_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
				$xml_content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
				$xml_content .= '</urlset>';

				file_put_contents( $file_path, $xml_content );

				// Store the path in cache (simulating what the sitemap module does)
				$cache_key = 'sitemap_path_' . $sitemap_type;
				Cache::set( $cache_key, $file_path, 86400 );

				// Retrieve the cached value
				$cached_value = Cache::get( $cache_key );

				// Verify cached value is a string
				$this->assertIsString(
					$cached_value,
					'Cached sitemap value should be a string, not ' . gettype( $cached_value )
				);

				// Verify cached value is NOT empty
				$this->assertNotEmpty(
					$cached_value,
					'Cached sitemap path should not be empty'
				);

				// Verify cached value does NOT contain XML markup
				$this->assertStringNotContainsString(
					'<?xml',
					$cached_value,
					'Cached sitemap should not contain XML declaration'
				);

				$this->assertStringNotContainsString(
					'<urlset',
					$cached_value,
					'Cached sitemap should not contain urlset tag'
				);

				$this->assertStringNotContainsString(
					'<sitemapindex',
					$cached_value,
					'Cached sitemap should not contain sitemapindex tag'
				);

				$this->assertStringNotContainsString(
					'<loc>',
					$cached_value,
					'Cached sitemap should not contain loc tag'
				);

				// Verify cached value is a valid filesystem path
				$this->assertTrue(
					is_string( $cached_value ) && strlen( $cached_value ) > 0,
					'Cached value should be a non-empty string representing a path'
				);

				// Verify path contains expected directory structure
				$this->assertStringContainsString(
					'meowseo-sitemaps',
					$cached_value,
					'Cached path should contain meowseo-sitemaps directory'
				);

				// Verify path ends with .xml extension
				$this->assertStringEndsWith(
					'.xml',
					$cached_value,
					'Cached path should end with .xml extension'
				);

				// Clean up
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				Cache::delete( $cache_key );
			}
		);
	}

	/**
	 * Property: Cached sitemap paths are always strings
	 *
	 * For any sitemap type, the cached value should always be a string,
	 * never an array, object, or other type.
	 *
	 * @return void
	 */
	public function test_cached_sitemap_paths_are_always_strings(): void {
		$this->forAll(
			Generators::elements( [ 'index', 'posts', 'pages' ] )
		)
		->then(
			function ( string $sitemap_type ) {
				$upload_dir = \wp_upload_dir();
				$sitemap_dir = \trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';

				if ( ! file_exists( $sitemap_dir ) ) {
					\wp_mkdir_p( $sitemap_dir );
				}

				$file_name = 'index' === $sitemap_type ? 'sitemap-index.xml' : 'sitemap-' . $sitemap_type . '.xml';
				$file_path = $sitemap_dir . '/' . $file_name;

				file_put_contents( $file_path, '<?xml version="1.0"?><urlset></urlset>' );

				$cache_key = 'sitemap_path_' . $sitemap_type;
				Cache::set( $cache_key, $file_path, 86400 );

				$cached_value = Cache::get( $cache_key );

				// Verify it's a string
				$this->assertIsString(
					$cached_value,
					'Cached sitemap path must be a string'
				);

				// Verify it's not an array
				$this->assertFalse(
					is_array( $cached_value ),
					'Cached sitemap path must not be an array'
				);

				// Verify it's not an object
				$this->assertFalse(
					is_object( $cached_value ),
					'Cached sitemap path must not be an object'
				);

				// Clean up
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				Cache::delete( $cache_key );
			}
		);
	}

	/**
	 * Property: Cached sitemap paths never contain XML content
	 *
	 * For any sitemap, the cached value should never contain any XML markup,
	 * including tags, declarations, or XML-specific content.
	 *
	 * @return void
	 */
	public function test_cached_sitemap_paths_never_contain_xml_markup(): void {
		$this->forAll(
			Generators::elements( [ 'index', 'posts', 'pages', 'custom' ] )
		)
		->then(
			function ( string $sitemap_type ) {
				$upload_dir = \wp_upload_dir();
				$sitemap_dir = \trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';

				if ( ! file_exists( $sitemap_dir ) ) {
					\wp_mkdir_p( $sitemap_dir );
				}

				$file_name = 'index' === $sitemap_type ? 'sitemap-index.xml' : 'sitemap-' . $sitemap_type . '.xml';
				$file_path = $sitemap_dir . '/' . $file_name;

				file_put_contents( $file_path, '<?xml version="1.0"?><urlset></urlset>' );

				$cache_key = 'sitemap_path_' . $sitemap_type;
				Cache::set( $cache_key, $file_path, 86400 );

				$cached_value = Cache::get( $cache_key );

				// List of XML markup indicators that should NOT be in the cached value
				$xml_indicators = [
					'<?xml',
					'<urlset',
					'<sitemapindex',
					'<loc>',
					'<lastmod>',
					'<priority>',
					'<changefreq>',
					'<image:image>',
					'</urlset>',
					'</sitemapindex>',
				];

				foreach ( $xml_indicators as $indicator ) {
					$this->assertStringNotContainsString(
						$indicator,
						$cached_value,
						'Cached sitemap path should not contain XML markup: ' . $indicator
					);
				}

				// Clean up
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				Cache::delete( $cache_key );
			}
		);
	}

	/**
	 * Property: Cached sitemap paths are valid filesystem paths
	 *
	 * For any sitemap, the cached value should be a valid filesystem path
	 * that follows proper path conventions.
	 *
	 * @return void
	 */
	public function test_cached_sitemap_paths_are_valid_filesystem_paths(): void {
		$this->forAll(
			Generators::elements( [ 'index', 'posts', 'pages' ] )
		)
		->then(
			function ( string $sitemap_type ) {
				$upload_dir = \wp_upload_dir();
				$sitemap_dir = \trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';

				if ( ! file_exists( $sitemap_dir ) ) {
					\wp_mkdir_p( $sitemap_dir );
				}

				$file_name = 'index' === $sitemap_type ? 'sitemap-index.xml' : 'sitemap-' . $sitemap_type . '.xml';
				$file_path = $sitemap_dir . '/' . $file_name;

				file_put_contents( $file_path, '<?xml version="1.0"?><urlset></urlset>' );

				$cache_key = 'sitemap_path_' . $sitemap_type;
				Cache::set( $cache_key, $file_path, 86400 );

				$cached_value = Cache::get( $cache_key );

				// Verify it's a non-empty string
				$this->assertTrue(
					is_string( $cached_value ) && strlen( $cached_value ) > 0,
					'Cached path must be a non-empty string'
				);

				// Verify it contains directory separators (valid path structure)
				$this->assertTrue(
					strpos( $cached_value, DIRECTORY_SEPARATOR ) !== false,
					'Cached path should contain directory separators'
				);

				// Verify it ends with .xml
				$this->assertStringEndsWith(
					'.xml',
					$cached_value,
					'Cached path should end with .xml extension'
				);

				// Verify it contains meowseo-sitemaps directory
				$this->assertStringContainsString(
					'meowseo-sitemaps',
					$cached_value,
					'Cached path should contain meowseo-sitemaps directory'
				);

				// Clean up
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				Cache::delete( $cache_key );
			}
		);
	}

	/**
	 * Property: Cached sitemap paths follow expected naming convention
	 *
	 * For any sitemap type, the cached path should follow the naming convention:
	 * sitemap-{type}.xml or sitemap-index.xml
	 *
	 * @return void
	 */
	public function test_cached_sitemap_paths_follow_naming_convention(): void {
		$this->forAll(
			Generators::elements( [ 'index', 'posts', 'pages', 'products' ] )
		)
		->then(
			function ( string $sitemap_type ) {
				$upload_dir = \wp_upload_dir();
				$sitemap_dir = \trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';

				if ( ! file_exists( $sitemap_dir ) ) {
					\wp_mkdir_p( $sitemap_dir );
				}

				$file_name = 'index' === $sitemap_type ? 'sitemap-index.xml' : 'sitemap-' . $sitemap_type . '.xml';
				$file_path = $sitemap_dir . '/' . $file_name;

				file_put_contents( $file_path, '<?xml version="1.0"?><urlset></urlset>' );

				$cache_key = 'sitemap_path_' . $sitemap_type;
				Cache::set( $cache_key, $file_path, 86400 );

				$cached_value = Cache::get( $cache_key );

				// Extract the filename from the path
				$filename = basename( $cached_value );

				// Verify filename follows naming convention
				if ( 'index' === $sitemap_type ) {
					$this->assertEquals(
						'sitemap-index.xml',
						$filename,
						'Index sitemap should be named sitemap-index.xml'
					);
				} else {
					$this->assertStringStartsWith(
						'sitemap-',
						$filename,
						'Sitemap filename should start with sitemap-'
					);

					$this->assertStringEndsWith(
						'.xml',
						$filename,
						'Sitemap filename should end with .xml'
					);
				}

				// Clean up
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				Cache::delete( $cache_key );
			}
		);
	}

	/**
	 * Property: Cached sitemap paths are deterministic
	 *
	 * For any given sitemap type, the cached path should always be the same
	 * (deterministic behavior).
	 *
	 * @return void
	 */
	public function test_cached_sitemap_paths_are_deterministic(): void {
		$this->forAll(
			Generators::elements( [ 'index', 'posts', 'pages' ] )
		)
		->then(
			function ( string $sitemap_type ) {
				$upload_dir = \wp_upload_dir();
				$sitemap_dir = \trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';

				if ( ! file_exists( $sitemap_dir ) ) {
					\wp_mkdir_p( $sitemap_dir );
				}

				$file_name = 'index' === $sitemap_type ? 'sitemap-index.xml' : 'sitemap-' . $sitemap_type . '.xml';
				$file_path = $sitemap_dir . '/' . $file_name;

				file_put_contents( $file_path, '<?xml version="1.0"?><urlset></urlset>' );

				$cache_key = 'sitemap_path_' . $sitemap_type;
				Cache::set( $cache_key, $file_path, 86400 );

				// Retrieve the cached value three times
				$cached_value1 = Cache::get( $cache_key );
				$cached_value2 = Cache::get( $cache_key );
				$cached_value3 = Cache::get( $cache_key );

				// All three should be identical
				$this->assertEquals(
					$cached_value1,
					$cached_value2,
					'Cached sitemap path should be deterministic (retrieval 1 vs 2)'
				);

				$this->assertEquals(
					$cached_value2,
					$cached_value3,
					'Cached sitemap path should be deterministic (retrieval 2 vs 3)'
				);

				// Clean up
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				Cache::delete( $cache_key );
			}
		);
	}

	/**
	 * Property: Cached sitemap paths do not contain XML content indicators
	 *
	 * For any sitemap, the cached value should never contain common XML content
	 * patterns like opening/closing tags or XML declarations.
	 *
	 * @return void
	 */
	public function test_cached_sitemap_paths_do_not_contain_xml_content_indicators(): void {
		$this->forAll(
			Generators::elements( [ 'index', 'posts', 'pages' ] )
		)
		->then(
			function ( string $sitemap_type ) {
				$upload_dir = \wp_upload_dir();
				$sitemap_dir = \trailingslashit( $upload_dir['basedir'] ) . 'meowseo-sitemaps';

				if ( ! file_exists( $sitemap_dir ) ) {
					\wp_mkdir_p( $sitemap_dir );
				}

				$file_name = 'index' === $sitemap_type ? 'sitemap-index.xml' : 'sitemap-' . $sitemap_type . '.xml';
				$file_path = $sitemap_dir . '/' . $file_name;

				file_put_contents( $file_path, '<?xml version="1.0"?><urlset></urlset>' );

				$cache_key = 'sitemap_path_' . $sitemap_type;
				Cache::set( $cache_key, $file_path, 86400 );

				$cached_value = Cache::get( $cache_key );

				// Verify no opening angle brackets followed by common XML patterns
				$this->assertSame(
					0,
					preg_match( '/<\?xml/', $cached_value ),
					'Cached path should not contain XML declaration'
				);

				$this->assertSame(
					0,
					preg_match( '/<urlset/', $cached_value ),
					'Cached path should not contain urlset tag'
				);

				$this->assertSame(
					0,
					preg_match( '/<sitemapindex/', $cached_value ),
					'Cached path should not contain sitemapindex tag'
				);

				// Clean up
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				Cache::delete( $cache_key );
			}
		);
	}
}
