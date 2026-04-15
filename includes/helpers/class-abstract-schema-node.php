<?php
/**
 * Abstract Schema Node base class.
 *
 * Base class for all schema node builders. Each schema type (WebSite, Organization, Article, etc.)
 * extends this class and implements the generate() and is_needed() methods.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Helpers;

use MeowSEO\Options;
use WP_Post;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Schema Node class.
 *
 * Provides base functionality for all schema node builders.
 *
 * @since 1.0.0
 */
abstract class Abstract_Schema_Node {

	/**
	 * Post ID
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected int $post_id;

	/**
	 * Post object
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected object $post;

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	protected Options $options;

	/**
	 * Context data
	 *
	 * Additional context data that may be needed by node builders.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected array $context;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param object  $post    Post object.
	 * @param Options $options Options instance.
	 * @param array   $context Additional context data.
	 */
	public function __construct( int $post_id, object $post, Options $options, array $context = array() ) {
		$this->post_id = $post_id;
		$this->post    = $post;
		$this->options = $options;
		$this->context = $context;
	}

	/**
	 * Generate schema node
	 *
	 * Generates and returns the schema node array for this schema type.
	 * Must be implemented by child classes.
	 *
	 * @since 1.0.0
	 * @return array Schema node array.
	 */
	abstract public function generate(): array;

	/**
	 * Check if node is needed
	 *
	 * Determines whether this schema node should be included in the @graph.
	 * Must be implemented by child classes.
	 *
	 * @since 1.0.0
	 * @return bool True if node should be included, false otherwise.
	 */
	abstract public function is_needed(): bool;

	/**
	 * Get ID URL for schema node
	 *
	 * Generates a consistent @id URL using the format {url}#{fragment}.
	 * This format enables Google Knowledge Graph resolution (Requirement 1.7).
	 *
	 * @since 1.0.0
	 * @param string $fragment Fragment identifier (e.g., 'website', 'organization', 'article').
	 * @return string Full @id URL.
	 */
	protected function get_id_url( string $fragment ): string {
		$url = get_permalink( $this->post );
		if ( ! $url ) {
			$url = get_site_url();
		}
		return rtrim( $url, '/' ) . '/#' . $fragment;
	}

	/**
	 * Format date in ISO 8601 format
	 *
	 * Converts a date string to ISO 8601 format required by Schema.org.
	 *
	 * @since 1.0.0
	 * @param string $date Date string (any format accepted by strtotime).
	 * @return string ISO 8601 formatted date.
	 */
	protected function format_date( string $date ): string {
		$timestamp = strtotime( $date );
		if ( false === $timestamp ) {
			// Fallback to current time if date is invalid.
			$timestamp = time();
		}
		return gmdate( 'c', $timestamp );
	}

	/**
	 * Get site URL
	 *
	 * Helper method to get the site URL.
	 *
	 * @since 1.0.0
	 * @return string Site URL.
	 */
	protected function get_site_url(): string {
		return get_site_url();
	}

	/**
	 * Get site @id URL
	 *
	 * Helper method to get the site @id URL with fragment.
	 *
	 * @since 1.0.0
	 * @param string $fragment Fragment identifier.
	 * @return string Site @id URL.
	 */
	protected function get_site_id_url( string $fragment ): string {
		return rtrim( $this->get_site_url(), '/' ) . '/#' . $fragment;
	}
}
