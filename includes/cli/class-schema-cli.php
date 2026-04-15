<?php
/**
 * Schema WP-CLI Commands
 *
 * Provides WP-CLI commands for schema generation, validation, and cache management.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\CLI;

use MeowSEO\Helpers\Schema_Builder;
use MeowSEO\Helpers\Cache;
use MeowSEO\Options;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema CLI commands class
 *
 * Implements WP-CLI commands for schema operations.
 *
 * @since 1.0.0
 */
class Schema_CLI {

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
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->schema_builder = new Schema_Builder( $options );
	}

	/**
	 * Generate schema for a post
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The post ID to generate schema for.
	 *
	 * ## EXAMPLES
	 *
	 *     wp meowseo schema generate 123
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function generate( array $args, array $assoc_args ): void {
		list( $post_id ) = $args;
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			WP_CLI::error( 'Invalid post ID provided.' );
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			WP_CLI::error( sprintf( 'Post with ID %d not found.', $post_id ) );
			return;
		}

		WP_CLI::log( sprintf( 'Generating schema for post %d (%s)...', $post_id, $post->post_title ) );

		try {
			// Build schema graph
			$graph = $this->schema_builder->build( $post_id );

			if ( empty( $graph ) ) {
				WP_CLI::warning( 'No schema nodes generated for this post.' );
				return;
			}

			// Convert to JSON
			$json = $this->schema_builder->to_json( $graph );

			// Cache the result
			$cache_key = "schema_{$post_id}";
			Cache::set( $cache_key, $json, 3600 );

			WP_CLI::success( sprintf( 'Schema generated successfully for post %d.', $post_id ) );
			WP_CLI::log( sprintf( 'Generated %d schema nodes.', count( $graph ) ) );
			WP_CLI::log( 'Schema JSON-LD:' );
			WP_CLI::log( $json );

		} catch ( \Exception $e ) {
			WP_CLI::error( sprintf( 'Failed to generate schema: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Validate schema for a post
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The post ID to validate schema for.
	 *
	 * ## EXAMPLES
	 *
	 *     wp meowseo schema validate 123
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function validate( array $args, array $assoc_args ): void {
		list( $post_id ) = $args;
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			WP_CLI::error( 'Invalid post ID provided.' );
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			WP_CLI::error( sprintf( 'Post with ID %d not found.', $post_id ) );
			return;
		}

		WP_CLI::log( sprintf( 'Validating schema for post %d (%s)...', $post_id, $post->post_title ) );

		try {
			// Build schema graph
			$graph = $this->schema_builder->build( $post_id );

			if ( empty( $graph ) ) {
				WP_CLI::warning( 'No schema nodes generated for this post.' );
				return;
			}

			$errors = array();
			$warnings = array();

			// Validate each node
			foreach ( $graph as $node ) {
				// Check required properties
				if ( empty( $node['@type'] ) ) {
					$errors[] = 'Node missing required @type property';
				}

				if ( empty( $node['@id'] ) ) {
					$errors[] = 'Node missing required @id property';
				}

				// Validate @id format
				if ( ! empty( $node['@id'] ) && ! $this->is_valid_url( $node['@id'] ) ) {
					$errors[] = sprintf( 'Invalid @id format: %s', $node['@id'] );
				}

				// Check for common issues
				if ( isset( $node['datePublished'] ) && ! $this->is_valid_iso8601( $node['datePublished'] ) ) {
					$warnings[] = sprintf( 'Invalid datePublished format in %s node', $node['@type'] ?? 'unknown' );
				}

				if ( isset( $node['dateModified'] ) && ! $this->is_valid_iso8601( $node['dateModified'] ) ) {
					$warnings[] = sprintf( 'Invalid dateModified format in %s node', $node['@type'] ?? 'unknown' );
				}
			}

			// Convert to JSON and validate JSON structure
			$json = $this->schema_builder->to_json( $graph );
			$decoded = json_decode( $json, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$errors[] = sprintf( 'Invalid JSON: %s', json_last_error_msg() );
			}

			// Report results
			if ( ! empty( $errors ) ) {
				WP_CLI::error_multi_line( $errors );
				WP_CLI::error( sprintf( 'Schema validation failed with %d error(s).', count( $errors ) ) );
			}

			if ( ! empty( $warnings ) ) {
				WP_CLI::warning_multi_line( $warnings );
			}

			if ( empty( $errors ) && empty( $warnings ) ) {
				WP_CLI::success( sprintf( 'Schema is valid for post %d. Generated %d nodes.', $post_id, count( $graph ) ) );
			} elseif ( empty( $errors ) ) {
				WP_CLI::success( sprintf( 'Schema is valid for post %d with %d warning(s).', $post_id, count( $warnings ) ) );
			}

		} catch ( \Exception $e ) {
			WP_CLI::error( sprintf( 'Failed to validate schema: %s', $e->getMessage() ) );
		}
	}

	/**
	 * Clear schema cache
	 *
	 * ## OPTIONS
	 *
	 * [--post_id=<id>]
	 * : Optional post ID to clear cache for. If not provided, clears all schema cache.
	 *
	 * ## EXAMPLES
	 *
	 *     # Clear cache for specific post
	 *     wp meowseo schema clear-cache --post_id=123
	 *
	 *     # Clear all schema cache
	 *     wp meowseo schema clear-cache
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function clear_cache( array $args, array $assoc_args ): void {
		$post_id = isset( $assoc_args['post_id'] ) ? absint( $assoc_args['post_id'] ) : null;

		if ( $post_id ) {
			// Clear cache for specific post
			$post = get_post( $post_id );
			if ( ! $post ) {
				WP_CLI::error( sprintf( 'Post with ID %d not found.', $post_id ) );
				return;
			}

			$cache_key = "schema_{$post_id}";
			Cache::delete( $cache_key );

			WP_CLI::success( sprintf( 'Schema cache cleared for post %d.', $post_id ) );
		} else {
			// Clear all schema cache
			global $wpdb;

			// Get all post IDs
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$post_ids = $wpdb->get_col(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish'"
			);

			if ( empty( $post_ids ) ) {
				WP_CLI::warning( 'No published posts found.' );
				return;
			}

			$cleared = 0;
			foreach ( $post_ids as $id ) {
				$cache_key = "schema_{$id}";
				if ( Cache::delete( $cache_key ) ) {
					$cleared++;
				}
			}

			WP_CLI::success( sprintf( 'Schema cache cleared for %d post(s).', $cleared ) );
		}
	}

	/**
	 * Check if a string is a valid URL
	 *
	 * @param string $url URL to validate.
	 * @return bool True if valid URL.
	 */
	private function is_valid_url( string $url ): bool {
		return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
	}

	/**
	 * Check if a string is valid ISO 8601 date format
	 *
	 * @param string $date Date string to validate.
	 * @return bool True if valid ISO 8601 format.
	 */
	private function is_valid_iso8601( string $date ): bool {
		$dt = \DateTime::createFromFormat( \DateTime::ISO8601, $date );
		return $dt !== false && $dt->format( \DateTime::ISO8601 ) === $date;
	}
}
