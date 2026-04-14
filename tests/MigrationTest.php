<?php
/**
 * Migration Test
 *
 * @package MeowSEO
 * @subpackage Tests
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Migration;
use MeowSEO\Options;

/**
 * Test Migration class
 */
class MigrationTest extends TestCase {
	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset global storage.
		global $wp_options_storage;
		$wp_options_storage = array();
	}

	/**
	 * Test migration version tracking
	 *
	 * @return void
	 */
	public function test_migration_version_tracking(): void {
		// Initially, no migration version should be set.
		$this->assertSame( '0.0.0', Migration::get_version() );

		// Migration should be needed.
		$this->assertTrue( Migration::is_migration_needed() );

		// Run migrations.
		Migration::run();

		// Version should be updated.
		$this->assertSame( '2.0.0', Migration::get_version() );

		// Migration should no longer be needed.
		$this->assertFalse( Migration::is_migration_needed() );
	}

	/**
	 * Test migration from old separator option
	 *
	 * @return void
	 */
	public function test_migrate_old_separator_option(): void {
		// Set old separator option.
		update_option( 'meowseo_separator', '-' );

		// Run migration.
		Migration::run();

		// Check new options structure.
		$options = get_option( 'meowseo_options', array() );
		$this->assertArrayHasKey( 'separator', $options );
		$this->assertSame( '-', $options['separator'] );

		// Old option should be deleted.
		$this->assertFalse( get_option( 'meowseo_separator' ) );
	}

	/**
	 * Test migration from old OG image option
	 *
	 * @return void
	 */
	public function test_migrate_old_og_image_option(): void {
		// Set old OG image option.
		update_option( 'meowseo_default_og_image', 'https://example.com/image.jpg' );

		// Run migration.
		Migration::run();

		// Check new options structure.
		$options = get_option( 'meowseo_options', array() );
		$this->assertArrayHasKey( 'default_og_image_url', $options );
		$this->assertSame( 'https://example.com/image.jpg', $options['default_og_image_url'] );

		// Old option should be deleted.
		$this->assertFalse( get_option( 'meowseo_default_og_image' ) );
	}

	/**
	 * Test migration initializes title patterns
	 *
	 * @return void
	 */
	public function test_migrate_initializes_title_patterns(): void {
		// Run migration.
		Migration::run();

		// Check new options structure.
		$options = get_option( 'meowseo_options', array() );
		$this->assertArrayHasKey( 'title_patterns', $options );
		$this->assertIsArray( $options['title_patterns'] );

		// Check that default patterns are set.
		$this->assertArrayHasKey( 'post', $options['title_patterns'] );
		$this->assertArrayHasKey( 'page', $options['title_patterns'] );
		$this->assertArrayHasKey( 'homepage', $options['title_patterns'] );
		$this->assertArrayHasKey( 'category', $options['title_patterns'] );
	}

	/**
	 * Test migration initializes noindex_date_archives
	 *
	 * @return void
	 */
	public function test_migrate_initializes_noindex_date_archives(): void {
		// Run migration.
		Migration::run();

		// Check new options structure.
		$options = get_option( 'meowseo_options', array() );
		$this->assertArrayHasKey( 'noindex_date_archives', $options );
		$this->assertFalse( $options['noindex_date_archives'] );
	}

	/**
	 * Test migration initializes robots_txt_custom
	 *
	 * @return void
	 */
	public function test_migrate_initializes_robots_txt_custom(): void {
		// Run migration.
		Migration::run();

		// Check new options structure.
		$options = get_option( 'meowseo_options', array() );
		$this->assertArrayHasKey( 'robots_txt_custom', $options );
		$this->assertSame( '', $options['robots_txt_custom'] );
	}

	/**
	 * Test migration does not overwrite existing options
	 *
	 * @return void
	 */
	public function test_migrate_does_not_overwrite_existing_options(): void {
		// Set existing options.
		$existing_options = array(
			'separator'              => '~',
			'default_og_image_url'   => 'https://example.com/existing.jpg',
			'title_patterns'         => array( 'custom' => 'pattern' ),
			'noindex_date_archives'  => true,
			'robots_txt_custom'      => 'Custom directives',
		);
		update_option( 'meowseo_options', $existing_options );

		// Set old options (should be ignored).
		update_option( 'meowseo_separator', '-' );
		update_option( 'meowseo_default_og_image', 'https://example.com/old.jpg' );

		// Run migration.
		Migration::run();

		// Check that existing options were not overwritten.
		$options = get_option( 'meowseo_options', array() );
		$this->assertSame( '~', $options['separator'] );
		$this->assertSame( 'https://example.com/existing.jpg', $options['default_og_image_url'] );
		$this->assertSame( array( 'custom' => 'pattern' ), $options['title_patterns'] );
		$this->assertTrue( $options['noindex_date_archives'] );
		$this->assertSame( 'Custom directives', $options['robots_txt_custom'] );

		// Old options should still be deleted.
		$this->assertFalse( get_option( 'meowseo_separator' ) );
		$this->assertFalse( get_option( 'meowseo_default_og_image' ) );
	}

	/**
	 * Test migration with default values
	 *
	 * @return void
	 */
	public function test_migrate_with_default_values(): void {
		// Don't set any old options (use defaults).
		// Run migration.
		Migration::run();

		// Check new options structure has defaults.
		$options = get_option( 'meowseo_options', array() );
		$this->assertArrayHasKey( 'separator', $options );
		$this->assertSame( '|', $options['separator'] );

		$this->assertArrayHasKey( 'default_og_image_url', $options );
		$this->assertSame( '', $options['default_og_image_url'] );
	}

	/**
	 * Test migration runs only once
	 *
	 * @return void
	 */
	public function test_migration_runs_only_once(): void {
		// Set old options.
		update_option( 'meowseo_separator', '-' );

		// Run migration first time.
		Migration::run();

		// Verify migration ran.
		$options = get_option( 'meowseo_options', array() );
		$this->assertSame( '-', $options['separator'] );

		// Modify the migrated option.
		$options['separator'] = '~';
		update_option( 'meowseo_options', $options );

		// Run migration again.
		Migration::run();

		// Verify the modified option was not overwritten.
		$options = get_option( 'meowseo_options', array() );
		$this->assertSame( '~', $options['separator'] );
	}

	/**
	 * Test migration deletes old options
	 *
	 * @return void
	 */
	public function test_migration_deletes_old_options(): void {
		// Set old options.
		update_option( 'meowseo_separator', '-' );
		update_option( 'meowseo_default_og_image', 'https://example.com/image.jpg' );

		// Verify old options exist.
		$this->assertNotFalse( get_option( 'meowseo_separator' ) );
		$this->assertNotFalse( get_option( 'meowseo_default_og_image' ) );

		// Run migration.
		Migration::run();

		// Verify old options are deleted.
		$this->assertFalse( get_option( 'meowseo_separator' ) );
		$this->assertFalse( get_option( 'meowseo_default_og_image' ) );
	}

	/**
	 * Tear down test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Reset global storage.
		global $wp_options_storage;
		$wp_options_storage = array();

		parent::tearDown();
	}
}
