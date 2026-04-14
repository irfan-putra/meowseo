<?php
/**
 * Migration class for handling plugin upgrades
 *
 * @package MeowSEO
 */

namespace MeowSEO;

use MeowSEO\Modules\Meta\Title_Patterns;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration class
 *
 * Handles data migration between plugin versions.
 */
class Migration {
	/**
	 * Current migration version
	 */
	private const MIGRATION_VERSION = '2.0.0';

	/**
	 * Migration version option key
	 */
	private const VERSION_KEY = 'meowseo_migration_version';

	/**
	 * Run all pending migrations
	 *
	 * @return void
	 */
	public static function run(): void {
		$current_version = get_option( self::VERSION_KEY, '0.0.0' );

		// Run migrations in order.
		if ( version_compare( $current_version, '2.0.0', '<' ) ) {
			self::migrate_to_2_0_0();
		}

		// Update migration version.
		update_option( self::VERSION_KEY, self::MIGRATION_VERSION );
	}

	/**
	 * Migrate to version 2.0.0 (Meta Module rebuild)
	 *
	 * Migrates old individual options to new meowseo_options structure.
	 *
	 * @return void
	 */
	private static function migrate_to_2_0_0(): void {
		// Get old options.
		$old_separator = get_option( 'meowseo_separator', '|' );
		$old_og_image  = get_option( 'meowseo_default_og_image', '' );

		// Get current options array.
		$options = get_option( 'meowseo_options', array() );

		// Migrate separator if not already set.
		if ( ! isset( $options['separator'] ) ) {
			$options['separator'] = $old_separator;
		}

		// Migrate default OG image if not already set.
		if ( ! isset( $options['default_og_image_url'] ) ) {
			$options['default_og_image_url'] = $old_og_image;
		}

		// Initialize title patterns with defaults if not already set.
		if ( ! isset( $options['title_patterns'] ) ) {
			// Get default patterns from Title_Patterns class.
			$patterns_instance = new Title_Patterns( new Options() );
			$options['title_patterns'] = $patterns_instance->get_default_patterns();
		}

		// Initialize noindex_date_archives if not already set.
		if ( ! isset( $options['noindex_date_archives'] ) ) {
			$options['noindex_date_archives'] = false;
		}

		// Initialize robots_txt_custom if not already set.
		if ( ! isset( $options['robots_txt_custom'] ) ) {
			$options['robots_txt_custom'] = '';
		}

		// Save updated options.
		update_option( 'meowseo_options', $options );

		// Delete old option keys.
		delete_option( 'meowseo_separator' );
		delete_option( 'meowseo_default_og_image' );
	}

	/**
	 * Get current migration version
	 *
	 * @return string Current migration version.
	 */
	public static function get_version(): string {
		return get_option( self::VERSION_KEY, '0.0.0' );
	}

	/**
	 * Check if migration is needed
	 *
	 * @return bool True if migration is needed, false otherwise.
	 */
	public static function is_migration_needed(): bool {
		$current_version = self::get_version();
		return version_compare( $current_version, self::MIGRATION_VERSION, '<' );
	}
}
