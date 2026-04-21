<?php
/**
 * Tests for ZIP File Validation - Task 22
 *
 * This test file verifies ZIP file validation functionality including:
 * - File existence and readability checks
 * - ZIP archive validity verification
 * - Plugin file presence verification
 * - ZIP structure validation
 * - Error handling and logging
 *
 * @package MeowSEO
 * @subpackage Tests\Updater
 */

namespace MeowSEO\Tests\Updater;

use PHPUnit\Framework\TestCase;
use MeowSEO\Updater\GitHub_Update_Checker;
use MeowSEO\Updater\Update_Config;
use MeowSEO\Updater\Update_Logger;

/**
 * Test ZIP file validation functionality.
 */
class Test_ZIP_Validation extends TestCase {

	/**
	 * GitHub_Update_Checker instance.
	 *
	 * @var GitHub_Update_Checker
	 */
	private GitHub_Update_Checker $checker;

	/**
	 * Update_Logger instance.
	 *
	 * @var Update_Logger
	 */
	private Update_Logger $logger;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Clean up any existing data.
		delete_option( 'meowseo_github_update_logs' );

		// Create instances.
		$config = new Update_Config();
		$this->logger = new Update_Logger();
		$this->checker = new GitHub_Update_Checker( MEOWSEO_FILE, $config, $this->logger );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		// Clean up.
		delete_option( 'meowseo_github_update_logs' );

		parent::tearDown();
	}

	/**
	 * Test 1: Validate ZIP file - Valid ZIP with plugin file.
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
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is true.
		$this->assertTrue( $result );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 2: Validate ZIP file - File not found.
	 */
	public function test_validate_zip_file_not_found() {
		// Validate non-existent file.
		$result = $this->checker->validate_zip_file( '/nonexistent/file.zip' );

		// Verify result is WP_Error.
		$this->assertInstanceOf( \WP_Error::class, $result );

		// Verify error code.
		$this->assertEquals( 'zip_not_found', $result->get_error_code() );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]['level'] );
	}

	/**
	 * Test 3: Validate ZIP file - File not readable.
	 *
	 * Note: This test is skipped on Windows due to permission handling differences.
	 */
	public function test_validate_zip_file_not_readable() {
		// Skip on Windows where permission handling is different.
		if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
			$this->markTestSkipped( 'File permission test skipped on Windows' );
		}

		// Create a temporary file.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' );

		// Make file unreadable (if possible on this system).
		chmod( $temp_file, 0000 );

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is WP_Error.
		$this->assertInstanceOf( \WP_Error::class, $result );

		// Verify error code.
		$this->assertEquals( 'zip_not_readable', $result->get_error_code() );

		// Clean up.
		chmod( $temp_file, 0644 );
		@unlink( $temp_file );
	}

	/**
	 * Test 4: Validate ZIP file - Invalid ZIP archive.
	 */
	public function test_validate_zip_file_invalid_zip() {
		// Create a temporary file that's not a ZIP.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		file_put_contents( $temp_file, 'This is not a ZIP file' );

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is WP_Error.
		$this->assertInstanceOf( \WP_Error::class, $result );

		// Verify error code.
		$this->assertEquals( 'invalid_zip', $result->get_error_code() );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]['level'] );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 5: Validate ZIP file - Missing plugin file.
	 */
	public function test_validate_zip_file_missing_plugin() {
		// Create a temporary ZIP file without plugin file.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/readme.txt', 'Plugin readme' );
		$zip->addFromString( 'meowseo-abc1234/includes/class.php', '<?php // Class file' );
		$zip->close();

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is WP_Error.
		$this->assertInstanceOf( \WP_Error::class, $result );

		// Verify error code.
		$this->assertEquals( 'missing_plugin_file', $result->get_error_code() );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]['level'] );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 6: Validate ZIP file - Invalid structure (mixed root directories).
	 */
	public function test_validate_zip_file_invalid_structure() {
		// Create a temporary ZIP file with mixed root directories.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );
		$zip->addFromString( 'other-dir/readme.txt', 'Other file' );
		$zip->close();

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is WP_Error.
		$this->assertInstanceOf( \WP_Error::class, $result );

		// Verify error code.
		$this->assertEquals( 'invalid_zip_structure', $result->get_error_code() );

		// Verify error was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'error', $logs[0]['level'] );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 7: Validate ZIP file - Successful validation logs success.
	 */
	public function test_validate_zip_file_logs_success() {
		// Create a temporary ZIP file with plugin file.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );
		$zip->close();

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is true.
		$this->assertTrue( $result );

		// Verify success was logged.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'info', $logs[0]['level'] );
		$this->assertStringContainsString( 'zip_validation_successful', $logs[0]['context']['action'] ?? '' );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 8: Validate ZIP file - Error message is user-friendly.
	 */
	public function test_validate_zip_file_error_message_friendly() {
		// Create a temporary file that's not a ZIP.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		file_put_contents( $temp_file, 'This is not a ZIP file' );

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify error message is user-friendly.
		$message = $result->get_error_message();
		$this->assertIsString( $message );
		$this->assertGreaterThan( 0, strlen( $message ) );
		$this->assertStringNotContainsString( 'ZipArchive', $message );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 9: Validate ZIP file - Multiple files in nested directory.
	 */
	public function test_validate_zip_file_multiple_files() {
		// Create a temporary ZIP file with multiple files.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );
		$zip->addFromString( 'meowseo-abc1234/readme.txt', 'Plugin readme' );
		$zip->addFromString( 'meowseo-abc1234/includes/class.php', '<?php // Class file' );
		$zip->addFromString( 'meowseo-abc1234/includes/functions.php', '<?php // Functions file' );
		$zip->addFromString( 'meowseo-abc1234/assets/style.css', 'CSS content' );
		$zip->close();

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is true.
		$this->assertTrue( $result );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 10: Validate ZIP file - Plugin file in subdirectory.
	 */
	public function test_validate_zip_file_plugin_in_subdirectory() {
		// Create a temporary ZIP file with plugin file in subdirectory.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );
		$zip->addFromString( 'meowseo-abc1234/includes/meowseo.php', '<?php // Another file' );
		$zip->close();

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is true (should find meowseo.php).
		$this->assertTrue( $result );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 11: Validate ZIP file - Empty ZIP archive.
	 *
	 * Note: This test is skipped on Windows due to ZipArchive behavior differences.
	 */
	public function test_validate_zip_file_empty_zip() {
		// Skip on Windows where ZipArchive behaves differently.
		if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
			$this->markTestSkipped( 'Empty ZIP test skipped on Windows due to ZipArchive behavior' );
		}

		// Create an empty ZIP file.
		$temp_dir = sys_get_temp_dir();
		$temp_file = $temp_dir . DIRECTORY_SEPARATOR . 'test_empty_' . uniqid() . '.zip';
		
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->close();

		// Verify file exists before validation.
		$this->assertTrue( file_exists( $temp_file ) );

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is WP_Error (empty ZIP has no files, so invalid structure).
		$this->assertInstanceOf( \WP_Error::class, $result );
		// Empty ZIP will fail at the structure check (no root_dir)
		$this->assertEquals( 'invalid_zip_structure', $result->get_error_code() );

		// Clean up.
		if ( file_exists( $temp_file ) ) {
			unlink( $temp_file );
		}
	}

	/**
	 * Test 12: Validate ZIP file - Large ZIP archive.
	 */
	public function test_validate_zip_file_large_zip() {
		// Create a large ZIP file.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );

		// Add many files to make it larger.
		for ( $i = 0; $i < 100; $i++ ) {
			$zip->addFromString( "meowseo-abc1234/file-$i.txt", "File content $i" );
		}

		$zip->close();

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is true.
		$this->assertTrue( $result );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 13: Validate ZIP file - Logging includes file path.
	 */
	public function test_validate_zip_file_logging_includes_path() {
		// Create a temporary ZIP file with plugin file.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );
		$zip->close();

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is true.
		$this->assertTrue( $result );

		// Verify logging includes file path.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertArrayHasKey( 'context', $logs[0] );
		$this->assertArrayHasKey( 'file_path', $logs[0]['context'] );
		$this->assertEquals( $temp_file, $logs[0]['context']['file_path'] );

		// Clean up.
		unlink( $temp_file );
	}

	/**
	 * Test 14: Validate ZIP file - Logging includes root directory.
	 */
	public function test_validate_zip_file_logging_includes_root_dir() {
		// Create a temporary ZIP file with plugin file.
		$temp_file = tempnam( sys_get_temp_dir(), 'test_' ) . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $temp_file, \ZipArchive::CREATE );
		$zip->addFromString( 'meowseo-abc1234/meowseo.php', '<?php // Plugin file' );
		$zip->close();

		// Validate ZIP file.
		$result = $this->checker->validate_zip_file( $temp_file );

		// Verify result is true.
		$this->assertTrue( $result );

		// Verify logging includes root directory.
		$logs = $this->logger->get_recent_logs( 1 );
		$this->assertCount( 1, $logs );
		$this->assertArrayHasKey( 'context', $logs[0] );
		$this->assertArrayHasKey( 'root_dir', $logs[0]['context'] );
		$this->assertEquals( 'meowseo-abc1234', $logs[0]['context']['root_dir'] );

		// Clean up.
		unlink( $temp_file );
	}
}
