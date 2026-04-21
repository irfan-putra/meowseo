<?php
/**
 * Tests for Security Features - Task 21
 *
 * This test file verifies security functionality including:
 * - Input validation (commit IDs, branch names, repo names)
 * - Input sanitization
 * - Output escaping
 * - Nonce verification
 * - User capability checks
 * - ZIP file validation
 * - HTTPS enforcement
 *
 * @package MeowSEO
 * @subpackage Tests\Updater
 */

namespace MeowSEO\Tests\Updater;

use PHPUnit\Framework\TestCase;
use MeowSEO\Updater\Update_Security;

/**
 * Test security functionality.
 */
class Test_Security extends TestCase {

	/**
	 * Test 1: Validate commit ID - Valid format.
	 */
	public function test_validate_commit_id_valid() {
		// Test valid commit IDs.
		$this->assertTrue( Update_Security::validate_commit_id( 'abc1234' ) );
		$this->assertTrue( Update_Security::validate_commit_id( 'def5678' ) );
		$this->assertTrue( Update_Security::validate_commit_id( 'abc1234567890abcdef' ) );
		$this->assertTrue( Update_Security::validate_commit_id( 'ABCDEF1234567890' ) );
	}

	/**
	 * Test 2: Validate commit ID - Invalid format.
	 */
	public function test_validate_commit_id_invalid() {
		// Test invalid commit IDs.
		$this->assertFalse( Update_Security::validate_commit_id( 'abc' ) ); // Too short.
		$this->assertFalse( Update_Security::validate_commit_id( 'xyz1234' ) ); // Invalid characters.
		$this->assertFalse( Update_Security::validate_commit_id( 'abc1234567890abcdef1234567890abcdef1234567890' ) ); // Too long.
		$this->assertFalse( Update_Security::validate_commit_id( '' ) ); // Empty.
		$this->assertFalse( Update_Security::validate_commit_id( 'abc-1234' ) ); // Invalid character.
	}

	/**
	 * Test 3: Validate branch name - Valid format.
	 */
	public function test_validate_branch_name_valid() {
		// Test valid branch names.
		$this->assertTrue( Update_Security::validate_branch_name( 'main' ) );
		$this->assertTrue( Update_Security::validate_branch_name( 'master' ) );
		$this->assertTrue( Update_Security::validate_branch_name( 'develop' ) );
		$this->assertTrue( Update_Security::validate_branch_name( 'feature/new-feature' ) );
		$this->assertTrue( Update_Security::validate_branch_name( 'release-1.0.0' ) );
		$this->assertTrue( Update_Security::validate_branch_name( 'bugfix_issue_123' ) );
	}

	/**
	 * Test 4: Validate branch name - Invalid format.
	 */
	public function test_validate_branch_name_invalid() {
		// Test invalid branch names.
		$this->assertFalse( Update_Security::validate_branch_name( '' ) ); // Empty.
		$this->assertFalse( Update_Security::validate_branch_name( 'branch@name' ) ); // Invalid character.
		$this->assertFalse( Update_Security::validate_branch_name( 'branch name' ) ); // Space.
		$this->assertFalse( Update_Security::validate_branch_name( 'branch#name' ) ); // Invalid character.
	}

	/**
	 * Test 5: Validate repository owner - Valid format.
	 */
	public function test_validate_repo_owner_valid() {
		// Test valid repository owners.
		$this->assertTrue( Update_Security::validate_repo_owner( 'akbarbahaulloh' ) );
		$this->assertTrue( Update_Security::validate_repo_owner( 'john-doe' ) );
		$this->assertTrue( Update_Security::validate_repo_owner( 'user123' ) );
		$this->assertTrue( Update_Security::validate_repo_owner( 'a' ) );
	}

	/**
	 * Test 6: Validate repository owner - Invalid format.
	 */
	public function test_validate_repo_owner_invalid() {
		// Test invalid repository owners.
		$this->assertFalse( Update_Security::validate_repo_owner( '' ) ); // Empty.
		$this->assertFalse( Update_Security::validate_repo_owner( '-user' ) ); // Starts with hyphen.
		$this->assertFalse( Update_Security::validate_repo_owner( 'user-' ) ); // Ends with hyphen.
		$this->assertFalse( Update_Security::validate_repo_owner( 'user@name' ) ); // Invalid character.
		$this->assertFalse( Update_Security::validate_repo_owner( 'user name' ) ); // Space.
	}

	/**
	 * Test 7: Validate repository name - Valid format.
	 */
	public function test_validate_repo_name_valid() {
		// Test valid repository names.
		$this->assertTrue( Update_Security::validate_repo_name( 'meowseo' ) );
		$this->assertTrue( Update_Security::validate_repo_name( 'my-repo' ) );
		$this->assertTrue( Update_Security::validate_repo_name( 'my_repo' ) );
		$this->assertTrue( Update_Security::validate_repo_name( 'my.repo' ) );
		$this->assertTrue( Update_Security::validate_repo_name( 'repo123' ) );
	}

	/**
	 * Test 8: Validate repository name - Invalid format.
	 */
	public function test_validate_repo_name_invalid() {
		// Test invalid repository names.
		$this->assertFalse( Update_Security::validate_repo_name( '' ) ); // Empty.
		$this->assertFalse( Update_Security::validate_repo_name( 'repo@name' ) ); // Invalid character.
		$this->assertFalse( Update_Security::validate_repo_name( 'repo name' ) ); // Space.
		$this->assertFalse( Update_Security::validate_repo_name( 'repo/name' ) ); // Slash.
	}

	/**
	 * Test 9: Validate GitHub API URL - Valid.
	 */
	public function test_validate_github_api_url_valid() {
		// Test valid GitHub API URLs.
		$this->assertTrue( Update_Security::validate_github_api_url( 'https://api.github.com/repos/akbarbahaulloh/meowseo/commits/main' ) );
		$this->assertTrue( Update_Security::validate_github_api_url( 'https://api.github.com/repos/user/repo/commits' ) );
	}

	/**
	 * Test 10: Validate GitHub API URL - Invalid.
	 */
	public function test_validate_github_api_url_invalid() {
		// Test invalid GitHub API URLs.
		$this->assertFalse( Update_Security::validate_github_api_url( 'http://api.github.com/repos/user/repo' ) ); // HTTP instead of HTTPS.
		$this->assertFalse( Update_Security::validate_github_api_url( 'https://github.com/user/repo' ) ); // Not API endpoint.
		$this->assertFalse( Update_Security::validate_github_api_url( 'https://evil.com/api.github.com' ) ); // SSRF attempt.
		$this->assertFalse( Update_Security::validate_github_api_url( '' ) ); // Empty.
	}

	/**
	 * Test 11: Validate GitHub archive URL - Valid.
	 */
	public function test_validate_github_archive_url_valid() {
		// Test valid GitHub archive URLs.
		$this->assertTrue( Update_Security::validate_github_archive_url( 'https://github.com/akbarbahaulloh/meowseo/archive/abc1234.zip' ) );
		$this->assertTrue( Update_Security::validate_github_archive_url( 'https://github.com/user/repo/archive/main.zip' ) );
	}

	/**
	 * Test 12: Validate GitHub archive URL - Invalid.
	 */
	public function test_validate_github_archive_url_invalid() {
		// Test invalid GitHub archive URLs.
		$this->assertFalse( Update_Security::validate_github_archive_url( 'http://github.com/user/repo/archive/abc1234.zip' ) ); // HTTP instead of HTTPS.
		$this->assertFalse( Update_Security::validate_github_archive_url( 'https://github.com/user/repo/releases/abc1234.zip' ) ); // Not archive endpoint.
		$this->assertFalse( Update_Security::validate_github_archive_url( 'https://github.com/user/repo/archive/abc1234.tar.gz' ) ); // Not ZIP.
		$this->assertFalse( Update_Security::validate_github_archive_url( 'https://evil.com/github.com/user/repo/archive/abc1234.zip' ) ); // SSRF attempt.
		$this->assertFalse( Update_Security::validate_github_archive_url( '' ) ); // Empty.
	}

	/**
	 * Test 13: Sanitize commit ID.
	 */
	public function test_sanitize_commit_id() {
		// Test sanitization.
		$this->assertEquals( 'abc1234', Update_Security::sanitize_commit_id( 'abc1234' ) );
		$this->assertEquals( 'abc1234', Update_Security::sanitize_commit_id( 'abc-1234' ) ); // Removes hyphen.
		$this->assertEquals( 'abc1234', Update_Security::sanitize_commit_id( 'abc 1234' ) ); // Removes space.
		$this->assertEquals( 'abc1234', Update_Security::sanitize_commit_id( 'abc@1234' ) ); // Removes @.
		$this->assertEquals( '', Update_Security::sanitize_commit_id( 'xyz' ) ); // Removes invalid characters.
	}

	/**
	 * Test 14: Sanitize branch name.
	 */
	public function test_sanitize_branch_name() {
		// Test sanitization.
		$this->assertEquals( 'main', Update_Security::sanitize_branch_name( 'main' ) );
		$this->assertEquals( 'feature/new', Update_Security::sanitize_branch_name( 'feature/new' ) );
		$this->assertEquals( 'featurenew', Update_Security::sanitize_branch_name( 'feature@new' ) ); // Removes @.
		$this->assertEquals( 'featurenew', Update_Security::sanitize_branch_name( 'feature new' ) ); // Removes space.
	}

	/**
	 * Test 15: Escape HTML output.
	 */
	public function test_escape_html() {
		// Test HTML escaping.
		$this->assertEquals( '&lt;script&gt;', Update_Security::escape_html( '<script>' ) );
		$this->assertEquals( '&quot;test&quot;', Update_Security::escape_html( '"test"' ) );
		$this->assertEquals( 'normal text', Update_Security::escape_html( 'normal text' ) );
	}

	/**
	 * Test 16: Escape URL output.
	 */
	public function test_escape_url() {
		// Test URL escaping.
		$url = 'https://github.com/user/repo';
		$escaped = Update_Security::escape_url( $url );
		$this->assertStringContainsString( 'github.com', $escaped );
		$this->assertStringContainsString( 'https', $escaped );
	}

	/**
	 * Test 17: Escape attribute output.
	 */
	public function test_escape_attr() {
		// Test attribute escaping.
		$this->assertEquals( 'test&quot;value', Update_Security::escape_attr( 'test"value' ) );
		$this->assertEquals( 'normal', Update_Security::escape_attr( 'normal' ) );
	}

	/**
	 * Test 18: Verify nonce - Valid.
	 */
	public function test_verify_nonce_valid() {
		// Create a nonce.
		$nonce = wp_create_nonce( 'test_action' );

		// Verify nonce.
		$result = Update_Security::verify_nonce( $nonce, 'test_action' );
		$this->assertTrue( $result );
	}

	/**
	 * Test 19: Verify nonce - Invalid.
	 */
	public function test_verify_nonce_invalid() {
		// Test with invalid nonce.
		$result = Update_Security::verify_nonce( 'invalid_nonce', 'test_action' );
		$this->assertFalse( $result );

		// Test with wrong action.
		$nonce = wp_create_nonce( 'test_action' );
		$result = Update_Security::verify_nonce( $nonce, 'wrong_action' );
		$this->assertFalse( $result );
	}

	/**
	 * Test 20: Check user capability - Has capability.
	 */
	public function test_check_capability_has() {
		// Use global override to set capability to true.
		global $test_current_user_can_override;
		$test_current_user_can_override = true;

		// Check capability.
		$result = Update_Security::check_capability( 'manage_options' );
		$this->assertTrue( $result );

		// Reset override.
		$test_current_user_can_override = null;
	}

	/**
	 * Test 21: Check user capability - No capability.
	 */
	public function test_check_capability_no() {
		// Use global override to set capability to false.
		global $test_current_user_can_override;
		$test_current_user_can_override = false;

		// Check capability.
		$result = Update_Security::check_capability( 'manage_options' );
		$this->assertFalse( $result );

		// Reset override.
		$test_current_user_can_override = null;
	}

	/**
	 * Test 22: Validate ZIP file - Valid.
	 */
	public function test_validate_zip_file_valid() {
		// Create a temporary ZIP file with plugin file.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );
		$zip->addFromString( 'meowseo-abc1234/readme.txt', 'Plugin readme' );
		$zip->close();

		// Validate ZIP file.
		$result = Update_Security::validate_zip_file( $temp_file );
		$this->assertTrue( $result );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 23: Validate ZIP file - Missing plugin file.
	 */
	public function test_validate_zip_file_missing_plugin() {
		// Create a temporary ZIP file without plugin file.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/readme.txt', 'Plugin readme' );
		$zip->close();

		// Validate ZIP file.
		$result = Update_Security::validate_zip_file( $temp_file );
		$this->assertFalse( $result );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 24: Validate ZIP file - Invalid ZIP.
	 */
	public function test_validate_zip_file_invalid_zip() {
		// Create a temporary file that's not a ZIP.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		file_put_contents( $temp_file, 'This is not a ZIP file' );

		// Validate ZIP file.
		$result = Update_Security::validate_zip_file( $temp_file );
		$this->assertFalse( $result );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 25: Validate ZIP file - File not found.
	 */
	public function test_validate_zip_file_not_found() {
		// Validate non-existent file.
		$result = Update_Security::validate_zip_file( '/nonexistent/file.zip' );
		$this->assertFalse( $result );
	}

	/**
	 * Test 26: Validate ZIP structure - Valid.
	 */
	public function test_validate_zip_structure_valid() {
		// Create a temporary ZIP file with nested directory structure.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );
		$zip->addFromString( 'meowseo-abc1234/readme.txt', 'Plugin readme' );
		$zip->addFromString( 'meowseo-abc1234/includes/class.php', '<?php // Class file' );
		$zip->close();

		// Validate ZIP structure.
		$result = Update_Security::validate_zip_structure( $temp_file );
		$this->assertTrue( $result );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 27: Validate ZIP structure - Invalid (mixed root directories).
	 */
	public function test_validate_zip_structure_invalid() {
		// Create a temporary ZIP file with mixed root directories.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );
		$zip->addFromString( 'other-dir/readme.txt', 'Other file' );
		$zip->close();

		// Validate ZIP structure.
		$result = Update_Security::validate_zip_structure( $temp_file );
		$this->assertFalse( $result );

		// Clean up.
		unlink( $temp_file );
	}
}
