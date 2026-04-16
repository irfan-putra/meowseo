<?php
/**
 * Tests for Dashboard_Widgets class
 *
 * @package MeowSEO
 * @subpackage Tests
 */

namespace MeowSEO\Tests\Admin;

use MeowSEO\Admin\Dashboard_Widgets;
use MeowSEO\Options;
use MeowSEO\Module_Manager;
use PHPUnit\Framework\TestCase;

/**
 * Dashboard_Widgets test case
 */
class DashboardWidgetsTest extends TestCase {

	/**
	 * Test get_content_health_data returns expected structure
	 */
	public function test_get_content_health_data_structure(): void {
		$options = $this->createMock( Options::class );
		$module_manager = $this->createMock( Module_Manager::class );
		
		$widgets = new Dashboard_Widgets( $options, $module_manager );
		$data = $widgets->get_content_health_data();
		
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'total_posts', $data );
		$this->assertArrayHasKey( 'missing_title', $data );
		$this->assertArrayHasKey( 'missing_description', $data );
		$this->assertArrayHasKey( 'missing_focus_keyword', $data );
		$this->assertArrayHasKey( 'percentage_complete', $data );
		
		$this->assertIsInt( $data['total_posts'] );
		$this->assertIsInt( $data['missing_title'] );
		$this->assertIsInt( $data['missing_description'] );
		$this->assertIsInt( $data['missing_focus_keyword'] );
		$this->assertIsFloat( $data['percentage_complete'] );
	}

	/**
	 * Test get_sitemap_status_data returns expected structure
	 */
	public function test_get_sitemap_status_data_structure(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturnMap( [
			[ 'meowseo_sitemap_enabled', false, false ],
		] );
		
		$module_manager = $this->createMock( Module_Manager::class );
		
		$widgets = new Dashboard_Widgets( $options, $module_manager );
		$data = $widgets->get_sitemap_status_data();
		
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'enabled', $data );
		$this->assertArrayHasKey( 'last_generated', $data );
		$this->assertArrayHasKey( 'total_urls', $data );
		$this->assertArrayHasKey( 'post_types', $data );
		$this->assertArrayHasKey( 'cache_status', $data );
		
		$this->assertIsBool( $data['enabled'] );
		$this->assertIsInt( $data['total_urls'] );
		$this->assertIsArray( $data['post_types'] );
		$this->assertIsString( $data['cache_status'] );
	}

	/**
	 * Test get_top_404s_data returns array
	 */
	public function test_get_top_404s_data_returns_array(): void {
		$options = $this->createMock( Options::class );
		$module_manager = $this->createMock( Module_Manager::class );
		
		$widgets = new Dashboard_Widgets( $options, $module_manager );
		$data = $widgets->get_top_404s_data();
		
		$this->assertIsArray( $data );
	}

	/**
	 * Test get_gsc_summary_data returns expected structure
	 */
	public function test_get_gsc_summary_data_structure(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( null );
		
		$module_manager = $this->createMock( Module_Manager::class );
		
		$widgets = new Dashboard_Widgets( $options, $module_manager );
		$data = $widgets->get_gsc_summary_data();
		
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'clicks', $data );
		$this->assertArrayHasKey( 'impressions', $data );
		$this->assertArrayHasKey( 'ctr', $data );
		$this->assertArrayHasKey( 'position', $data );
		$this->assertArrayHasKey( 'date_range', $data );
		$this->assertArrayHasKey( 'last_synced', $data );
		
		$this->assertIsInt( $data['clicks'] );
		$this->assertIsInt( $data['impressions'] );
		$this->assertIsFloat( $data['ctr'] );
		$this->assertIsFloat( $data['position'] );
		$this->assertIsArray( $data['date_range'] );
	}

	/**
	 * Test get_discover_performance_data returns expected structure
	 */
	public function test_get_discover_performance_data_structure(): void {
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturn( null );
		
		$module_manager = $this->createMock( Module_Manager::class );
		
		$widgets = new Dashboard_Widgets( $options, $module_manager );
		$data = $widgets->get_discover_performance_data();
		
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'impressions', $data );
		$this->assertArrayHasKey( 'clicks', $data );
		$this->assertArrayHasKey( 'ctr', $data );
		$this->assertArrayHasKey( 'available', $data );
		$this->assertArrayHasKey( 'date_range', $data );
		
		$this->assertIsInt( $data['impressions'] );
		$this->assertIsInt( $data['clicks'] );
		$this->assertIsFloat( $data['ctr'] );
		$this->assertIsBool( $data['available'] );
		$this->assertIsArray( $data['date_range'] );
	}

	/**
	 * Test get_index_queue_data returns expected structure
	 */
	public function test_get_index_queue_data_structure(): void {
		$options = $this->createMock( Options::class );
		$module_manager = $this->createMock( Module_Manager::class );
		
		$widgets = new Dashboard_Widgets( $options, $module_manager );
		$data = $widgets->get_index_queue_data();
		
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'pending', $data );
		$this->assertArrayHasKey( 'processing', $data );
		$this->assertArrayHasKey( 'completed', $data );
		$this->assertArrayHasKey( 'failed', $data );
		$this->assertArrayHasKey( 'last_processed', $data );
		
		$this->assertIsInt( $data['pending'] );
		$this->assertIsInt( $data['processing'] );
		$this->assertIsInt( $data['completed'] );
		$this->assertIsInt( $data['failed'] );
	}
}
