<?php
/**
 * Image SEO Module
 *
 * Manages automatic alt text generation for images.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Image_SEO;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image_SEO module class
 *
 * Implements the Module interface to provide automatic image alt text generation.
 * Requirements: 4.1, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10
 *
 * @since 1.0.0
 */
class Image_SEO implements Module {

	/**
	 * Module ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const MODULE_ID = 'image_seo';

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Image_SEO_Handler instance
	 *
	 * @since 1.0.0
	 * @var Image_SEO_Handler
	 */
	private Image_SEO_Handler $handler;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		// Initialize Pattern_Processor and Image_SEO_Handler.
		$pattern_processor = new Pattern_Processor();
		$this->handler     = new Image_SEO_Handler( $options, $pattern_processor );
	}

	/**
	 * Boot the module
	 *
	 * Register hooks and initialize module functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		// Boot the Image_SEO_Handler.
		$this->handler->boot();
	}

	/**
	 * Get module ID
	 *
	 * @since 1.0.0
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return self::MODULE_ID;
	}

	/**
	 * Get handler instance
	 *
	 * @since 1.0.0
	 * @return Image_SEO_Handler Handler instance.
	 */
	public function get_handler(): Image_SEO_Handler {
		return $this->handler;
	}
}
