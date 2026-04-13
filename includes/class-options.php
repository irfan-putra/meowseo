<?php
/**
 * Options class for centralized settings management.
 *
 * Stores all settings as a single serialized array under meowseo_options.
 *
 * @package MeowSEO
 */

namespace MeowSEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options class.
 */
class Options {

	/**
	 * Option key in wp_options table.
	 */
	private const OPTION_KEY = 'meowseo_options';

	/**
	 * Cached options array.
	 *
	 * @var array
	 */
	private array $options = array();

	/**
	 * Constructor.
	 *
	 * Loads options from database.
	 */
	public function __construct() {
		$this->load();
	}

	/**
	 * Load options from database.
	 *
	 * @return void
	 */
	private function load(): void {
		$options = get_option( self::OPTION_KEY, array() );
		$this->options = is_array( $options ) ? $options : array();

		// Set defaults if not already set.
		$this->set_defaults();
	}

	/**
	 * Set default values for options.
	 *
	 * @return void
	 */
	private function set_defaults(): void {
		$defaults = array(
			'enabled_modules'        => array( 'meta' ), // Only meta module enabled by default.
			'separator'              => '|',
			'default_social_image'   => '',
			'delete_on_uninstall'    => false,
			'has_regex_rules'        => false, // Performance flag for redirect module.
		);

		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed Setting value.
	 */
	public function get( string $key, mixed $default = null ): mixed {
		return $this->options[ $key ] ?? $default;
	}

	/**
	 * Set a setting value.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return void
	 */
	public function set( string $key, mixed $value ): void {
		$this->options[ $key ] = $value;
	}

	/**
	 * Save options to database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save(): bool {
		return update_option( self::OPTION_KEY, $this->options );
	}

	/**
	 * Get enabled modules.
	 *
	 * @return array Array of enabled module IDs.
	 */
	public function get_enabled_modules(): array {
		$modules = $this->get( 'enabled_modules', array() );
		return is_array( $modules ) ? $modules : array();
	}

	/**
	 * Get title separator.
	 *
	 * @return string Title separator character.
	 */
	public function get_separator(): string {
		return (string) $this->get( 'separator', '|' );
	}

	/**
	 * Get default social image URL.
	 *
	 * @return string Default social image URL.
	 */
	public function get_default_social_image_url(): string {
		$image_id = $this->get( 'default_social_image', '' );
		
		if ( empty( $image_id ) ) {
			return '';
		}

		$image_url = wp_get_attachment_image_url( (int) $image_id, 'full' );
		return $image_url ? $image_url : '';
	}

	/**
	 * Check if data should be deleted on uninstall.
	 *
	 * @return bool True if data should be deleted, false otherwise.
	 */
	public function is_delete_on_uninstall(): bool {
		return (bool) $this->get( 'delete_on_uninstall', false );
	}

	/**
	 * Get all options.
	 *
	 * @return array All options.
	 */
	public function get_all(): array {
		return $this->options;
	}

	/**
	 * Delete all options from database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete(): bool {
		$this->options = array();
		return delete_option( self::OPTION_KEY );
	}
}
