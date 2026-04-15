<?php
/**
 * WP-CLI Commands Registration
 *
 * Registers all MeowSEO WP-CLI commands.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\CLI;

use MeowSEO\Options;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CLI Commands registration class
 *
 * Handles registration of all WP-CLI commands for MeowSEO.
 *
 * @since 1.0.0
 */
class CLI_Commands {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register all WP-CLI commands
	 *
	 * @return void
	 */
	public function register(): void {
		// Only register commands if WP-CLI is available
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		// Register schema commands
		$schema_cli = new Schema_CLI( $this->options );
		WP_CLI::add_command( 'meowseo schema', $schema_cli );

		// Register sitemap commands
		$sitemap_cli = new Sitemap_CLI( $this->options );
		WP_CLI::add_command( 'meowseo sitemap', $sitemap_cli );

		// Register health check commands
		// Note: Sitemap cache instance will be passed if sitemap module is loaded
		$sitemap_cache = null;
		if ( class_exists( '\MeowSEO\Modules\Sitemap\Sitemap_Cache' ) ) {
			$upload_dir = wp_upload_dir();
			$sitemap_cache = new \MeowSEO\Modules\Sitemap\Sitemap_Cache( $upload_dir['basedir'] . '/meowseo-sitemaps' );
		}
		$health_cli = new Health_CLI( $this->options, $sitemap_cache );
		WP_CLI::add_command( 'meowseo health', $health_cli );
	}
}
