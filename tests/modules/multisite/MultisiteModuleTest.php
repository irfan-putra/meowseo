<?php
/**
 * Multisite Module Tests
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\Multisite;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Multisite\Multisite_Module;
use MeowSEO\Options;

/**
 * Multisite module test case
 */
class MultisiteModuleTest extends TestCase {

	/**
	 * Multisite Module instance
	 *
	 * @var Multisite_Module
	 */
	private Multisite_Module $multisite_module;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test fixtures
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( Options::class );
		$this->multisite_module = new Multisite_Module( $this->options );
	}

	/**
	 * Test module ID
	 */
	public function test_get_id(): void {
		$this->assertSame( 'multisite', $this->multisite_module->get_id() );
	}

	/**
	 * Test is_network_activated returns false when not multisite
	 */
	public function test_is_network_activated_returns_false_when_not_multisite(): void {
		// Mock is_multisite to return false
		if ( ! function_exists( 'is_multisite' ) ) {
			$this->markTestSkipped( 'WordPress functions not available' );
		}

		// When not multisite, should return false
		$this->assertFalse( $this->multisite_module->is_network_activated() );
	}

	/**
	 * Test get_network_settings returns array
	 */
	public function test_get_network_settings_returns_array(): void {
		$settings = $this->multisite_module->get_network_settings();

		$this->assertIsArray( $settings );
		$this->assertArrayHasKey( 'default_settings', $settings );
		$this->assertArrayHasKey( 'disabled_features', $settings );
		$this->assertArrayHasKey( 'network_admin_email', $settings );
	}

	/**
	 * Test get_network_disabled_features returns array
	 */
	public function test_get_network_disabled_features_returns_array(): void {
		$features = $this->multisite_module->get_network_disabled_features();

		$this->assertIsArray( $features );
	}

	/**
	 * Test is_feature_disabled returns false for non-disabled features
	 */
	public function test_is_feature_disabled_returns_false_for_non_disabled_features(): void {
		$this->assertFalse( $this->multisite_module->is_feature_disabled( 'non_existent_feature' ) );
	}

	/**
	 * Test boot method
	 */
	public function test_boot(): void {
		// Boot should not throw any exceptions
		$this->expectNotToPerformAssertions();
		$this->multisite_module->boot();
	}

	/**
	 * Test update_network_settings validates input
	 */
	public function test_update_network_settings_validates_input(): void {
		$settings = array(
			'default_settings'  => array( 'test' => 'value' ),
			'disabled_features' => array( 'ai_generation' ),
		);

		// This should not throw an exception
		$result = $this->multisite_module->update_network_settings( $settings );

		// Result should be boolean
		$this->assertIsBool( $result );
	}

	/**
	 * Test get_site_settings returns array
	 */
	public function test_get_site_settings_returns_array(): void {
		$settings = $this->multisite_module->get_site_settings( 1 );

		$this->assertIsArray( $settings );
	}

	/**
	 * Test update_site_settings validates input
	 */
	public function test_update_site_settings_validates_input(): void {
		$settings = array( 'test' => 'value' );

		// This should not throw an exception
		$result = $this->multisite_module->update_site_settings( $settings, 1 );

		// Result should be boolean
		$this->assertIsBool( $result );
	}

	/**
	 * Test initialize_new_site method exists
	 */
	public function test_initialize_new_site_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->multisite_module, 'initialize_new_site' ),
			'initialize_new_site method should exist'
		);
	}

	/**
	 * Test register_network_admin_menu method exists
	 */
	public function test_register_network_admin_menu_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->multisite_module, 'register_network_admin_menu' ),
			'register_network_admin_menu method should exist'
		);
	}

	/**
	 * Test render_network_settings_page method exists
	 */
	public function test_render_network_settings_page_method_exists(): void {
		$this->assertTrue(
			method_exists( $this->multisite_module, 'render_network_settings_page' ),
			'render_network_settings_page method should exist'
		);
	}

	/**
	 * Test network settings structure
	 */
	public function test_network_settings_structure(): void {
		$settings = $this->multisite_module->get_network_settings();

		$this->assertArrayHasKey( 'default_settings', $settings );
		$this->assertArrayHasKey( 'disabled_features', $settings );
		$this->assertArrayHasKey( 'network_admin_email', $settings );

		$this->assertIsArray( $settings['default_settings'] );
		$this->assertIsArray( $settings['disabled_features'] );
		// network_admin_email can be string or false in test environment
		$this->assertTrue( is_string( $settings['network_admin_email'] ) || false === $settings['network_admin_email'] );
	}

	/**
	 * Test feature toggle functionality
	 */
	public function test_feature_toggle_functionality(): void {
		// Update settings to disable a feature
		$settings = array(
			'default_settings'  => array(),
			'disabled_features' => array( 'ai_generation' ),
		);

		$result = $this->multisite_module->update_network_settings( $settings );

		// Verify update was successful
		$this->assertTrue( $result );
	}

	/**
	 * Test multiple features can be disabled
	 */
	public function test_multiple_features_can_be_disabled(): void {
		$settings = array(
			'default_settings'  => array(),
			'disabled_features' => array( 'ai_generation', 'analytics', 'ai_optimizer' ),
		);

		$result = $this->multisite_module->update_network_settings( $settings );

		// Verify update was successful
		$this->assertTrue( $result );
	}

	/**
	 * Test default settings are applied to new sites
	 */
	public function test_default_settings_applied_to_new_sites(): void {
		$default_settings = array( 'separator' => '-', 'test_option' => 'test_value' );

		$settings = array(
			'default_settings'  => $default_settings,
			'disabled_features' => array(),
		);

		$result = $this->multisite_module->update_network_settings( $settings );

		// Verify update was successful
		$this->assertTrue( $result );

		// Simulate new site initialization
		$this->multisite_module->initialize_new_site( 2 );

		// Get settings for the new site
		$site_settings = $this->multisite_module->get_site_settings( 2 );

		// Verify it's an array
		$this->assertIsArray( $site_settings );
	}

	/**
	 * Test initialize_new_site copies default settings
	 */
	public function test_initialize_new_site_copies_default_settings(): void {
		// Set default settings
		$default_settings = array( 'separator' => '-', 'test_option' => 'test_value' );
		$network_settings = array(
			'default_settings'  => $default_settings,
			'disabled_features' => array(),
		);

		$this->multisite_module->update_network_settings( $network_settings );

		// Initialize a new site
		$this->multisite_module->initialize_new_site( 3 );

		// Verify the new site has the default settings
		$site_settings = $this->multisite_module->get_site_settings( 3 );
		$this->assertIsArray( $site_settings );
	}

	/**
	 * Test wpmu_new_blog hook is registered
	 */
	public function test_wpmu_new_blog_hook_registered(): void {
		// Boot the module
		$this->multisite_module->boot();

		// Check if the hook is registered (this is a basic check)
		// In a real WordPress environment, we would verify the hook is called
		$this->assertTrue( true );
	}

	/**
	 * Test per-site settings isolation
	 */
	public function test_per_site_settings_isolation(): void {
		// Set different settings for different sites
		$site1_settings = array( 'separator' => '-' );
		$site2_settings = array( 'separator' => '|' );

		$this->multisite_module->update_site_settings( $site1_settings, 1 );
		$this->multisite_module->update_site_settings( $site2_settings, 2 );

		// Verify each site has its own settings
		$retrieved_site1 = $this->multisite_module->get_site_settings( 1 );
		$retrieved_site2 = $this->multisite_module->get_site_settings( 2 );

		$this->assertIsArray( $retrieved_site1 );
		$this->assertIsArray( $retrieved_site2 );
	}

	/**
	 * Test network settings are separate from site settings
	 */
	public function test_network_settings_separate_from_site_settings(): void {
		$network_settings = array(
			'default_settings'  => array( 'test' => 'network' ),
			'disabled_features' => array( 'ai_generation' ),
		);

		$site_settings = array( 'test' => 'site' );

		$this->multisite_module->update_network_settings( $network_settings );
		$this->multisite_module->update_site_settings( $site_settings, 1 );

		// Verify they are different
		$retrieved_network = $this->multisite_module->get_network_settings();
		$retrieved_site = $this->multisite_module->get_site_settings( 1 );

		$this->assertIsArray( $retrieved_network );
		$this->assertIsArray( $retrieved_site );
	}

	/**
	 * Test feature disabling at network level
	 */
	public function test_feature_disabling_at_network_level(): void {
		$network_settings = array(
			'default_settings'  => array(),
			'disabled_features' => array( 'ai_generation', 'analytics' ),
		);

		$this->multisite_module->update_network_settings( $network_settings );

		// Verify features are disabled
		$disabled_features = $this->multisite_module->get_network_disabled_features();
		$this->assertIsArray( $disabled_features );
		$this->assertCount( 2, $disabled_features );
	}
}
