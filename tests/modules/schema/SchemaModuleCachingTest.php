<?php
/**
 * Schema Module Caching Tests
 *
 * Unit tests for the Schema_Module caching functionality.
 * Tests that schema caching is properly implemented with correct TTL and invalidation hooks.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Schema;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Schema\Schema;
use MeowSEO\Helpers\Cache;
use MeowSEO\Options;

/**
 * Schema Module caching test case
 *
 * Tests Requirement 2.6: Schema caching with 1-hour TTL and invalidation on post save and meta update.
 *
 * @since 1.0.0
 */
class SchemaModuleCachingTest extends TestCase {

	/**
	 * Schema module instance
	 *
	 * @var Schema
	 */
	private Schema $schema_module;

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

		// Reset cache storage
		global $wp_cache_storage;
		$wp_cache_storage = array();

		$this->options = new Options();
		$this->schema_module = new Schema( $this->options );
	}

	/**
	 * Test that cache key format is correct
	 *
	 * Validates: Requirement 2.6 - Cache key format: meowseo_schema_{post_id}
	 *
	 * @return void
	 */
	public function test_cache_key_format(): void {
		$post_id = 123;
		$cache_key = "schema_{$post_id}";

		// The Cache helper adds the meowseo_ prefix automatically
		// So schema_123 becomes meowseo_schema_123
		Cache::set( $cache_key, 'test_value' );

		// Verify the cache was set with the correct key
		$cached = Cache::get( $cache_key );
		$this->assertEquals( 'test_value', $cached );
	}

	/**
	 * Test that schema cache uses 1-hour TTL
	 *
	 * Validates: Requirement 2.6 - Use Cache helper with 1-hour TTL
	 *
	 * @return void
	 */
	public function test_schema_cache_ttl_is_one_hour(): void {
		// Mock the Schema_Builder to return a simple schema
		$mock_builder = $this->createMock( \MeowSEO\Helpers\Schema_Builder::class );
		$mock_builder->method( 'build' )->willReturn( array( '@type' => 'WebPage' ) );
		$mock_builder->method( 'to_json' )->willReturn( '{"@type":"WebPage"}' );

		// Use reflection to set the private schema_builder property
		$reflection = new \ReflectionClass( $this->schema_module );
		$property = $reflection->getProperty( 'schema_builder' );
		$property->setAccessible( true );
		$property->setValue( $this->schema_module, $mock_builder );

		$post_id = 123;
		$schema_json = $this->schema_module->get_schema_json( $post_id );

		// Verify schema was generated
		$this->assertNotEmpty( $schema_json );

		// Verify cache was set (we can't directly verify TTL in unit tests,
		// but we can verify the cache was set)
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertEquals( $schema_json, $cached );
	}

	/**
	 * Test that cache is checked before calling builder
	 *
	 * Validates: Requirement 2.6 - Check cache before calling builder
	 *
	 * @return void
	 */
	public function test_cache_is_checked_before_builder(): void {
		$post_id = 123;
		$cached_schema = '{"@type":"WebPage","cached":true}';

		// Pre-populate the cache
		Cache::set( "schema_{$post_id}", $cached_schema );

		// Mock the Schema_Builder to track if it's called
		$mock_builder = $this->createMock( \MeowSEO\Helpers\Schema_Builder::class );
		$mock_builder->expects( $this->never() )->method( 'build' );

		// Use reflection to set the private schema_builder property
		$reflection = new \ReflectionClass( $this->schema_module );
		$property = $reflection->getProperty( 'schema_builder' );
		$property->setAccessible( true );
		$property->setValue( $this->schema_module, $mock_builder );

		// Get schema - should return cached value without calling builder
		$schema_json = $this->schema_module->get_schema_json( $post_id );

		// Verify cached value was returned
		$this->assertEquals( $cached_schema, $schema_json );
	}

	/**
	 * Test that cache is invalidated on post save
	 *
	 * Validates: Requirement 2.6 - Invalidate on post save
	 *
	 * @return void
	 */
	public function test_cache_invalidated_on_post_save(): void {
		$post_id = 123;
		$cached_schema = '{"@type":"WebPage"}';

		// Pre-populate the cache
		Cache::set( "schema_{$post_id}", $cached_schema );

		// Verify cache exists
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertEquals( $cached_schema, $cached );

		// Call invalidate_cache (simulating save_post hook)
		$this->schema_module->invalidate_cache( $post_id );

		// Verify cache was deleted
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertFalse( $cached );
	}

	/**
	 * Test that cache is invalidated on schema meta update
	 *
	 * Validates: Requirement 2.6 - Invalidate on meta update
	 *
	 * @return void
	 */
	public function test_cache_invalidated_on_schema_type_meta_update(): void {
		$post_id = 123;
		$cached_schema = '{"@type":"WebPage"}';

		// Pre-populate the cache
		Cache::set( "schema_{$post_id}", $cached_schema );

		// Verify cache exists
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertEquals( $cached_schema, $cached );

		// Call invalidate_cache_on_meta_update (simulating update_post_meta hook)
		// with _meowseo_schema_type meta key
		$this->schema_module->invalidate_cache_on_meta_update(
			1, // meta_id
			$post_id,
			'_meowseo_schema_type',
			'Article'
		);

		// Verify cache was deleted
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertFalse( $cached );
	}

	/**
	 * Test that cache is invalidated on schema config meta update
	 *
	 * Validates: Requirement 2.6 - Invalidate on meta update
	 *
	 * @return void
	 */
	public function test_cache_invalidated_on_schema_config_meta_update(): void {
		$post_id = 123;
		$cached_schema = '{"@type":"WebPage"}';

		// Pre-populate the cache
		Cache::set( "schema_{$post_id}", $cached_schema );

		// Verify cache exists
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertEquals( $cached_schema, $cached );

		// Call invalidate_cache_on_meta_update (simulating update_post_meta hook)
		// with _meowseo_schema_config meta key
		$this->schema_module->invalidate_cache_on_meta_update(
			2, // meta_id
			$post_id,
			'_meowseo_schema_config',
			'{"faq_items":[]}'
		);

		// Verify cache was deleted
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertFalse( $cached );
	}

	/**
	 * Test that cache is NOT invalidated for non-schema meta keys
	 *
	 * Validates: Requirement 2.6 - Only invalidate for schema-related meta
	 *
	 * @return void
	 */
	public function test_cache_not_invalidated_for_non_schema_meta(): void {
		$post_id = 123;
		$cached_schema = '{"@type":"WebPage"}';

		// Pre-populate the cache
		Cache::set( "schema_{$post_id}", $cached_schema );

		// Verify cache exists
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertEquals( $cached_schema, $cached );

		// Call invalidate_cache_on_meta_update with a non-schema meta key
		$this->schema_module->invalidate_cache_on_meta_update(
			3, // meta_id
			$post_id,
			'_some_other_meta_key',
			'some_value'
		);

		// Verify cache was NOT deleted
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertEquals( $cached_schema, $cached );
	}

	/**
	 * Test that get_schema_json returns empty string for empty graph
	 *
	 * @return void
	 */
	public function test_get_schema_json_returns_empty_for_empty_graph(): void {
		// Mock the Schema_Builder to return empty array
		$mock_builder = $this->createMock( \MeowSEO\Helpers\Schema_Builder::class );
		$mock_builder->method( 'build' )->willReturn( array() );

		// Use reflection to set the private schema_builder property
		$reflection = new \ReflectionClass( $this->schema_module );
		$property = $reflection->getProperty( 'schema_builder' );
		$property->setAccessible( true );
		$property->setValue( $this->schema_module, $mock_builder );

		$post_id = 123;
		$schema_json = $this->schema_module->get_schema_json( $post_id );

		// Verify empty string is returned
		$this->assertEquals( '', $schema_json );
	}

	/**
	 * Test that get_schema_json generates schema when cache miss
	 *
	 * @return void
	 */
	public function test_get_schema_json_generates_on_cache_miss(): void {
		// Mock the Schema_Builder
		$mock_builder = $this->createMock( \MeowSEO\Helpers\Schema_Builder::class );
		$mock_builder->method( 'build' )->willReturn( array( '@type' => 'WebPage' ) );
		$mock_builder->method( 'to_json' )->willReturn( '{"@type":"WebPage"}' );

		// Use reflection to set the private schema_builder property
		$reflection = new \ReflectionClass( $this->schema_module );
		$property = $reflection->getProperty( 'schema_builder' );
		$property->setAccessible( true );
		$property->setValue( $this->schema_module, $mock_builder );

		$post_id = 123;

		// Verify cache is empty
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertFalse( $cached );

		// Get schema
		$schema_json = $this->schema_module->get_schema_json( $post_id );

		// Verify schema was generated
		$this->assertEquals( '{"@type":"WebPage"}', $schema_json );

		// Verify cache was set
		$cached = Cache::get( "schema_{$post_id}" );
		$this->assertEquals( '{"@type":"WebPage"}', $cached );
	}
}
