<?php
/**
 * Tests for Bulk Editor module.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Bulk;

use MeowSEO\Modules\Bulk\Bulk_Editor;
use MeowSEO\Modules\Bulk\CSV_Generator;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Bulk Editor test class.
 */
class BulkEditorTest extends TestCase {

	/**
	 * Test module ID.
	 *
	 * @return void
	 */
	public function test_get_id(): void {
		$options = $this->createMock( Options::class );
		$bulk_editor = new Bulk_Editor( $options );

		$this->assertEquals( 'bulk', $bulk_editor->get_id() );
	}

	/**
	 * Test boot method.
	 *
	 * @return void
	 */
	public function test_boot(): void {
		$options = $this->createMock( Options::class );
		$bulk_editor = new Bulk_Editor( $options );

		// Boot should not throw an exception.
		$this->expectNotToPerformAssertions();
		$bulk_editor->boot();
	}

	/**
	 * Test supported post types.
	 *
	 * @return void
	 */
	public function test_get_supported_post_types(): void {
		$options = $this->createMock( Options::class );
		$bulk_editor = new Bulk_Editor( $options );

		$post_types = $bulk_editor->get_supported_post_types();

		$this->assertIsArray( $post_types );
		$this->assertContains( 'post', $post_types );
		$this->assertContains( 'page', $post_types );
	}
}
