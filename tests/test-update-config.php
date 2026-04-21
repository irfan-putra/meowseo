<?php
/**
 * Tests for Update_Config class.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Updater\Update_Config;

/**
 * Test Update_Config class.
 */
class Test_Update_Config extends TestCase {

	/**
	 * Update_Config instance.
	 *
	 * @var Update_Config
	 */
	private Update_Config $config;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->config = new Update_Config();
		
		// Clean up any existing config.
		delete_option( 'meowseo_github_update_config' );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		// Clean up.
		delete_option( 'meowseo_github_update_config' );
		parent::tearDown();
	}

	/**
	 * Test default values are returned when no config exists.
	 */
	public function test_default_values() {
		$this->assertEquals( 'akbarbahaulloh', $this->config->get_repo_owner() );
		$this->assertEquals( 'meowseo', $this->config->get_repo_name() );
		$this->assertEquals( 'main', $this->config->get_branch() );
		$this->assertTrue( $this->config->is_auto_update_enabled() );
		$this->assertEquals( 43200, $this->config->get_check_frequency() );
		$this->assertEquals( 0, $this->config->get_last_check() );
	}

	/**
	 * Test saving and retrieving configuration.
	 */
	public function test_save_and_retrieve() {
		$config_data = array(
			'repo_owner'          => 'testuser',
			'repo_name'           => 'testrepo',
			'branch'              => 'develop',
			'auto_update_enabled' => false,
			'check_frequency'     => 7200,
		);

		$result = $this->config->save( $config_data );
		$this->assertTrue( $result );

		// Create new instance to test retrieval from database.
		$new_config = new Update_Config();
		$this->assertEquals( 'testuser', $new_config->get_repo_owner() );
		$this->assertEquals( 'testrepo', $new_config->get_repo_name() );
		$this->assertEquals( 'develop', $new_config->get_branch() );
		$this->assertFalse( $new_config->is_auto_update_enabled() );
		$this->assertEquals( 7200, $new_config->get_check_frequency() );
	}

	/**
	 * Test get_all returns complete configuration.
	 */
	public function test_get_all() {
		$all = $this->config->get_all();

		$this->assertIsArray( $all );
		$this->assertArrayHasKey( 'repo_owner', $all );
		$this->assertArrayHasKey( 'repo_name', $all );
		$this->assertArrayHasKey( 'branch', $all );
		$this->assertArrayHasKey( 'auto_update_enabled', $all );
		$this->assertArrayHasKey( 'check_frequency', $all );
		$this->assertArrayHasKey( 'last_check', $all );
	}

	/**
	 * Test input sanitization.
	 */
	public function test_input_sanitization() {
		$config_data = array(
			'repo_owner'          => '  testuser  ',
			'repo_name'           => '<script>alert("xss")</script>testrepo',
			'branch'              => 'feature/test-branch',
			'auto_update_enabled' => 'yes', // Should be converted to boolean.
			'check_frequency'     => '3600', // Should be converted to int.
		);

		$this->config->save( $config_data );

		$this->assertEquals( 'testuser', $this->config->get_repo_owner() );
		$this->assertStringNotContainsString( '<script>', $this->config->get_repo_name() );
		$this->assertTrue( $this->config->is_auto_update_enabled() );
		$this->assertIsInt( $this->config->get_check_frequency() );
	}

	/**
	 * Test minimum check frequency enforcement.
	 */
	public function test_minimum_check_frequency() {
		$config_data = array(
			'check_frequency' => 1800, // 30 minutes - should be increased to 1 hour.
		);

		$this->config->save( $config_data );

		// Should be enforced to minimum of 3600 seconds (1 hour).
		$this->assertEquals( 3600, $this->config->get_check_frequency() );
	}

	/**
	 * Test update_last_check method.
	 */
	public function test_update_last_check() {
		$timestamp = time() - 3600; // 1 hour ago.
		$result = $this->config->update_last_check( $timestamp );

		$this->assertTrue( $result );
		$this->assertEquals( $timestamp, $this->config->get_last_check() );
	}

	/**
	 * Test update_last_check with null uses current time.
	 */
	public function test_update_last_check_null() {
		$before = time();
		$this->config->update_last_check( null );
		$after = time();

		$last_check = $this->config->get_last_check();
		$this->assertGreaterThanOrEqual( $before, $last_check );
		$this->assertLessThanOrEqual( $after, $last_check );
	}

	/**
	 * Test reset method.
	 */
	public function test_reset() {
		// Save custom config.
		$this->config->save( array(
			'repo_owner' => 'customuser',
			'branch'     => 'develop',
		) );

		// Reset to defaults.
		$result = $this->config->reset();
		$this->assertTrue( $result );

		// Verify defaults are restored.
		$new_config = new Update_Config();
		$this->assertEquals( 'akbarbahaulloh', $new_config->get_repo_owner() );
		$this->assertEquals( 'main', $new_config->get_branch() );
	}

	/**
	 * Test delete method.
	 */
	public function test_delete() {
		// Save config.
		$this->config->save( array( 'repo_owner' => 'testuser' ) );

		// Delete config.
		$result = $this->config->delete();
		$this->assertTrue( $result );

		// Verify option is deleted.
		$this->assertFalse( get_option( 'meowseo_github_update_config' ) );
	}

	/**
	 * Test partial config updates preserve existing values.
	 */
	public function test_partial_update() {
		// Save initial config.
		$this->config->save( array(
			'repo_owner' => 'user1',
			'branch'     => 'develop',
		) );

		// Update only branch.
		$this->config->save( array(
			'branch' => 'main',
		) );

		// Verify repo_owner is preserved.
		$this->assertEquals( 'user1', $this->config->get_repo_owner() );
		$this->assertEquals( 'main', $this->config->get_branch() );
	}

	/**
	 * Test invalid repository owner format.
	 */
	public function test_invalid_repo_owner() {
		$result = $this->config->save( array(
			'repo_owner' => '-invalid-', // Cannot start/end with hyphen.
		) );

		$this->assertFalse( $result );
	}

	/**
	 * Test invalid repository name format.
	 */
	public function test_invalid_repo_name() {
		$result = $this->config->save( array(
			'repo_name' => 'invalid repo!', // Contains invalid characters.
		) );

		$this->assertFalse( $result );
	}

	/**
	 * Test invalid branch name format.
	 */
	public function test_invalid_branch_name() {
		$result = $this->config->save( array(
			'branch' => '', // Empty branch name.
		) );

		$this->assertFalse( $result );
	}

	/**
	 * Test valid branch name formats.
	 */
	public function test_valid_branch_names() {
		$valid_branches = array(
			'main',
			'master',
			'develop',
			'feature/test',
			'release/1.0.0',
			'hotfix/bug-fix',
		);

		foreach ( $valid_branches as $branch ) {
			$result = $this->config->save( array( 'branch' => $branch ) );
			$this->assertTrue( $result, "Branch '$branch' should be valid" );
			$this->assertEquals( $branch, $this->config->get_branch() );
		}
	}
}
