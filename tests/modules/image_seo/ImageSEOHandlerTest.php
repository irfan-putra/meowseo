<?php
/**
 * Tests for Image_SEO_Handler class
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Image_SEO;

use MeowSEO\Modules\Image_SEO\Image_SEO_Handler;
use MeowSEO\Modules\Image_SEO\Pattern_Processor;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Image_SEO_Handler test case
 *
 * Requirements: 4.1, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10
 */
class ImageSEOHandlerTest extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Pattern_Processor instance
	 *
	 * @var Pattern_Processor
	 */
	private Pattern_Processor $pattern_processor;

	/**
	 * Image_SEO_Handler instance
	 *
	 * @var Image_SEO_Handler
	 */
	private Image_SEO_Handler $handler;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options           = new Options();
		$this->pattern_processor = new Pattern_Processor();
		$this->handler           = new Image_SEO_Handler( $this->options, $this->pattern_processor );
	}

	/**
	 * Test is_enabled returns false by default
	 *
	 * Requirement: 4.7
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_false_by_default(): void {
		$this->assertFalse( $this->handler->is_enabled() );
	}

	/**
	 * Test is_enabled returns true when enabled
	 *
	 * Requirement: 4.7
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_true_when_enabled(): void {
		$this->options->set( 'image_seo_enabled', true );
		$this->assertTrue( $this->handler->is_enabled() );
	}

	/**
	 * Test should_override_existing returns false by default
	 *
	 * Requirement: 4.10
	 *
	 * @return void
	 */
	public function test_should_override_existing_returns_false_by_default(): void {
		$this->assertFalse( $this->handler->should_override_existing() );
	}

	/**
	 * Test should_override_existing returns true when enabled
	 *
	 * Requirement: 4.10
	 *
	 * @return void
	 */
	public function test_should_override_existing_returns_true_when_enabled(): void {
		$this->options->set( 'image_seo_override_existing', true );
		$this->assertTrue( $this->handler->should_override_existing() );
	}
}
