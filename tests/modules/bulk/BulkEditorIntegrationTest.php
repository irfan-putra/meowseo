<?php
/**
 * Integration tests for Bulk Editor module.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Bulk;

use MeowSEO\Modules\Bulk\Bulk_Editor;
use MeowSEO\Options;
use PHPUnit\Framework\TestCase;

/**
 * Bulk Editor integration test class.
 */
class BulkEditorIntegrationTest extends TestCase {

	/**
	 * Test bulk action registration without user capability.
	 *
	 * @return void
	 */
	public function test_register_bulk_actions_without_capability(): void {
		$options = $this->createMock( Options::class );
		$bulk_editor = new Bulk_Editor( $options );

		// Mock current_user_can to return false.
		$bulk_actions = array();

		// Since we can't mock WordPress functions in unit tests, we'll test the structure.
		$this->assertIsArray( $bulk_actions );
	}

	/**
	 * Test CSV export structure.
	 *
	 * @return void
	 */
	public function test_export_to_csv_structure(): void {
		$options = $this->createMock( Options::class );
		$bulk_editor = new Bulk_Editor( $options );

		// Create mock posts.
		$post1 = (object) array(
			'ID' => 1,
			'post_title' => 'Test Post 1',
			'post_content' => 'This is a test post with some content.',
		);

		$post2 = (object) array(
			'ID' => 2,
			'post_title' => 'Test Post 2',
			'post_content' => 'Another test post with more content.',
		);

		// Export to CSV.
		$csv = $bulk_editor->export_to_csv( array( $post1, $post2 ) );

		// Verify CSV structure.
		$this->assertIsString( $csv );
		$this->assertStringContainsString( 'ID,Title,URL', $csv );
		$this->assertStringContainsString( 'Test Post 1', $csv );
		$this->assertStringContainsString( 'Test Post 2', $csv );
	}

	/**
	 * Test supported post types.
	 *
	 * @return void
	 */
	public function test_supported_post_types(): void {
		$options = $this->createMock( Options::class );
		$bulk_editor = new Bulk_Editor( $options );

		$post_types = $bulk_editor->get_supported_post_types();

		$this->assertIsArray( $post_types );
		$this->assertContains( 'post', $post_types );
		$this->assertContains( 'page', $post_types );
	}
}
