<?php
/**
 * Health Check WP-CLI Commands
 *
 * Provides WP-CLI commands for health checks on schema, sitemap cache, and permissions.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\CLI;

use MeowSEO\Helpers\Schema_Builder;
use MeowSEO\Modules\Sitemap\Sitemap_Cache;
use MeowSEO\Options;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Health Check CLI commands class
 *
 * Implements WP-CLI commands for system health checks.
 *
 * @since 1.0.0
 */
class Health_CLI {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Schema Builder instance
	 *
	 * @var Schema_Builder
	 */
	private Schema_Builder $schema_builder;

	/**
	 * Sitemap Cache instance
	 *
	 * @var Sitemap_Cache|null
	 */
	private ?Sitemap_Cache $sitemap_cache = null;

	/**
	 * Constructor
	 *
	 * @param Options            $options        Options instance.
	 * @param Sitemap_Cache|null $sitemap_cache  Sitemap cache instance (optional).
	 */
	public function __construct( Options $options, ?Sitemap_Cache $sitemap_cache = null ) {
		$this->options = $options;
		$this->schema_builder = new Schema_Builder( $options );
		$this->sitemap_cache = $sitemap_cache;
	}

	/**
	 * Check schema generation health
	 *
	 * Validates schema generation for published posts and reports any issues.
	 *
	 * ## EXAMPLES
	 *
	 *     wp meowseo health check-schema
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function check_schema( array $args, array $assoc_args ): void {
		WP_CLI::log( 'Running schema health check...' );

		global $wpdb;

		// Get sample of published posts
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$posts = $wpdb->get_results(
			"SELECT ID, post_title, post_type 
			FROM {$wpdb->posts} 
			WHERE post_status = 'publish' 
			AND post_type IN ('post', 'page') 
			ORDER BY post_modified DESC 
			LIMIT 10"
		);

		if ( empty( $posts ) ) {
			WP_CLI::warning( 'No published posts found to check.' );
			return;
		}

		$total_checked = 0;
		$total_errors = 0;
		$total_warnings = 0;

		foreach ( $posts as $post ) {
			$total_checked++;

			try {
				// Build schema graph
				$graph = $this->schema_builder->build( $post->ID );

				if ( empty( $graph ) || empty( $graph['@graph'] ) ) {
					WP_CLI::warning( sprintf( 'Post %d (%s): No schema nodes generated', $post->ID, $post->post_title ) );
					$total_warnings++;
					continue;
				}

				// Validate each node
				$node_count = count( $graph['@graph'] );
				$has_errors = false;

				foreach ( $graph['@graph'] as $node ) {
					// Check required properties
					if ( empty( $node['@type'] ) ) {
						WP_CLI::error( sprintf( 'Post %d (%s): Node missing @type', $post->ID, $post->post_title ), false );
						$has_errors = true;
					}

					if ( empty( $node['@id'] ) ) {
						WP_CLI::error( sprintf( 'Post %d (%s): Node missing @id', $post->ID, $post->post_title ), false );
						$has_errors = true;
					}

					// Validate @id format
					if ( ! empty( $node['@id'] ) && ! filter_var( $node['@id'], FILTER_VALIDATE_URL ) ) {
						WP_CLI::error( sprintf( 'Post %d (%s): Invalid @id format: %s', $post->ID, $post->post_title, $node['@id'] ), false );
						$has_errors = true;
					}
				}

				if ( $has_errors ) {
					$total_errors++;
				} else {
					WP_CLI::log( sprintf( '✓ Post %d (%s): %d nodes, valid', $post->ID, $post->post_title, $node_count ) );
				}

			} catch ( \Exception $e ) {
				WP_CLI::error( sprintf( 'Post %d (%s): Exception - %s', $post->ID, $post->post_title, $e->getMessage() ), false );
				$total_errors++;
			}
		}

		// Summary
		WP_CLI::log( '' );
		WP_CLI::log( '=== Schema Health Check Summary ===' );
		WP_CLI::log( sprintf( 'Posts Checked: %d', $total_checked ) );
		WP_CLI::log( sprintf( 'Errors: %d', $total_errors ) );
		WP_CLI::log( sprintf( 'Warnings: %d', $total_warnings ) );

		if ( $total_errors === 0 && $total_warnings === 0 ) {
			WP_CLI::success( 'Schema generation is healthy!' );
		} elseif ( $total_errors === 0 ) {
			WP_CLI::success( sprintf( 'Schema generation is healthy with %d warning(s).', $total_warnings ) );
		} else {
			WP_CLI::error( sprintf( 'Schema generation has %d error(s) and %d warning(s).', $total_errors, $total_warnings ) );
		}
	}

	/**
	 * Check sitemap cache health
	 *
	 * Validates sitemap cache directory, permissions, and file integrity.
	 *
	 * ## EXAMPLES
	 *
	 *     wp meowseo health check-sitemap-cache
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function check_sitemap_cache( array $args, array $assoc_args ): void {
		WP_CLI::log( 'Running sitemap cache health check...' );

		if ( ! $this->sitemap_cache ) {
			WP_CLI::error( 'Sitemap cache not available. Sitemap module may not be loaded.' );
			return;
		}

		$upload_dir = wp_upload_dir();
		$cache_dir = $upload_dir['basedir'] . '/meowseo-sitemaps';

		$issues = array();
		$warnings = array();

		// Check if cache directory exists
		if ( ! file_exists( $cache_dir ) ) {
			$issues[] = 'Cache directory does not exist: ' . $cache_dir;
		} else {
			WP_CLI::log( '✓ Cache directory exists: ' . $cache_dir );

			// Check if directory is writable
			if ( ! is_writable( $cache_dir ) ) {
				$issues[] = 'Cache directory is not writable: ' . $cache_dir;
			} else {
				WP_CLI::log( '✓ Cache directory is writable' );
			}

			// Check directory permissions
			$perms = substr( sprintf( '%o', fileperms( $cache_dir ) ), -4 );
			WP_CLI::log( sprintf( '  Directory permissions: %s', $perms ) );

			if ( $perms !== '0755' && $perms !== '0775' ) {
				$warnings[] = sprintf( 'Directory permissions (%s) may not be optimal. Recommended: 0755 or 0775', $perms );
			}

			// Check .htaccess file
			$htaccess_file = $cache_dir . '/.htaccess';
			if ( ! file_exists( $htaccess_file ) ) {
				$warnings[] = '.htaccess file missing in cache directory (direct access not blocked)';
			} else {
				WP_CLI::log( '✓ .htaccess file exists' );
			}

			// Check for sitemap files
			$files = glob( $cache_dir . '/*.xml' );
			if ( empty( $files ) ) {
				$warnings[] = 'No sitemap XML files found in cache directory';
			} else {
				$file_count = count( $files );
				WP_CLI::log( sprintf( '✓ Found %d sitemap file(s)', $file_count ) );

				// Check file permissions
				$file_issues = 0;
				foreach ( $files as $file ) {
					if ( ! is_readable( $file ) ) {
						$file_issues++;
					}
				}

				if ( $file_issues > 0 ) {
					$issues[] = sprintf( '%d sitemap file(s) are not readable', $file_issues );
				}
			}
		}

		// Summary
		WP_CLI::log( '' );
		WP_CLI::log( '=== Sitemap Cache Health Check Summary ===' );

		if ( ! empty( $issues ) ) {
			WP_CLI::log( 'Issues Found:' );
			foreach ( $issues as $issue ) {
				WP_CLI::error( '  ✗ ' . $issue, false );
			}
		}

		if ( ! empty( $warnings ) ) {
			WP_CLI::log( 'Warnings:' );
			foreach ( $warnings as $warning ) {
				WP_CLI::warning( '  ⚠ ' . $warning );
			}
		}

		if ( empty( $issues ) && empty( $warnings ) ) {
			WP_CLI::success( 'Sitemap cache is healthy!' );
		} elseif ( empty( $issues ) ) {
			WP_CLI::success( sprintf( 'Sitemap cache is healthy with %d warning(s).', count( $warnings ) ) );
		} else {
			WP_CLI::error( sprintf( 'Sitemap cache has %d issue(s) and %d warning(s).', count( $issues ), count( $warnings ) ) );
		}
	}

	/**
	 * Check file permissions
	 *
	 * Validates file permissions for MeowSEO directories and files.
	 *
	 * ## EXAMPLES
	 *
	 *     wp meowseo health check-permissions
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function check_permissions( array $args, array $assoc_args ): void {
		WP_CLI::log( 'Running permissions health check...' );

		$upload_dir = wp_upload_dir();
		$base_upload_dir = $upload_dir['basedir'];

		$paths_to_check = array(
			array(
				'path' => $base_upload_dir,
				'type' => 'directory',
				'should_be_writable' => true,
				'label' => 'WordPress uploads directory',
			),
			array(
				'path' => $base_upload_dir . '/meowseo-sitemaps',
				'type' => 'directory',
				'should_be_writable' => true,
				'label' => 'MeowSEO sitemap cache directory',
			),
		);

		$issues = array();
		$warnings = array();

		foreach ( $paths_to_check as $check ) {
			$path = $check['path'];
			$label = $check['label'];

			if ( ! file_exists( $path ) ) {
				if ( $check['type'] === 'directory' && $check['should_be_writable'] ) {
					$warnings[] = sprintf( '%s does not exist: %s', $label, $path );
				}
				continue;
			}

			WP_CLI::log( sprintf( 'Checking: %s', $label ) );

			// Check permissions
			$perms = substr( sprintf( '%o', fileperms( $path ) ), -4 );
			WP_CLI::log( sprintf( '  Path: %s', $path ) );
			WP_CLI::log( sprintf( '  Permissions: %s', $perms ) );

			// Check if writable when it should be
			if ( $check['should_be_writable'] ) {
				if ( ! is_writable( $path ) ) {
					$issues[] = sprintf( '%s is not writable: %s', $label, $path );
				} else {
					WP_CLI::log( '  ✓ Writable' );
				}
			}

			// Check if readable
			if ( ! is_readable( $path ) ) {
				$issues[] = sprintf( '%s is not readable: %s', $label, $path );
			} else {
				WP_CLI::log( '  ✓ Readable' );
			}

			WP_CLI::log( '' );
		}

		// Summary
		WP_CLI::log( '=== Permissions Health Check Summary ===' );

		if ( ! empty( $issues ) ) {
			WP_CLI::log( 'Issues Found:' );
			foreach ( $issues as $issue ) {
				WP_CLI::error( '  ✗ ' . $issue, false );
			}
		}

		if ( ! empty( $warnings ) ) {
			WP_CLI::log( 'Warnings:' );
			foreach ( $warnings as $warning ) {
				WP_CLI::warning( '  ⚠ ' . $warning );
			}
		}

		if ( empty( $issues ) && empty( $warnings ) ) {
			WP_CLI::success( 'All permissions are correct!' );
		} elseif ( empty( $issues ) ) {
			WP_CLI::success( sprintf( 'Permissions are correct with %d warning(s).', count( $warnings ) ) );
		} else {
			WP_CLI::error( sprintf( 'Found %d permission issue(s) and %d warning(s).', count( $issues ), count( $warnings ) ) );
		}
	}
}
