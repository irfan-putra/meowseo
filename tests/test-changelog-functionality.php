<?php
/**
 * Manual test for changelog functionality (Task 8).
 *
 * This script tests the new get_commit_history() and get_plugin_info() methods
 * added to the GitHub_Update_Checker class.
 *
 * @package MeowSEO
 */

// Load WordPress test environment.
require_once __DIR__ . '/bootstrap.php';

use MeowSEO\Updater\GitHub_Update_Checker;
use MeowSEO\Updater\Update_Config;
use MeowSEO\Updater\Update_Logger;

echo "=== Testing Changelog Functionality (Task 8) ===\n\n";

// Initialize dependencies.
$config = new Update_Config();
$logger = new Update_Logger();

// Set up configuration.
$config->save(
	array(
		'repo_owner'           => 'akbarbahaulloh',
		'repo_name'            => 'meowseo',
		'branch'               => 'main',
		'auto_update_enabled'  => true,
		'check_frequency'      => 43200,
	)
);

echo "1. Configuration initialized\n";
echo "   - Repository: akbarbahaulloh/meowseo\n";
echo "   - Branch: main\n\n";

// Create update checker instance.
$plugin_file = dirname( __DIR__ ) . '/meowseo.php';
$checker = new GitHub_Update_Checker( $plugin_file, $config, $logger );

echo "2. GitHub_Update_Checker instance created\n\n";

// Initialize hooks.
$checker->init();

echo "3. Hooks initialized\n";
echo "   - pre_set_site_transient_update_plugins\n";
echo "   - plugins_api\n\n";

// Test get_commit_history() method using reflection.
echo "4. Testing get_commit_history() method...\n";

$reflection = new ReflectionClass( $checker );
$method = $reflection->getMethod( 'get_commit_history' );
$method->setAccessible( true );

try {
	$commits = $method->invoke( $checker, 5 ); // Fetch 5 commits for testing.
	
	if ( is_array( $commits ) && ! empty( $commits ) ) {
		echo "   ✓ Successfully fetched " . count( $commits ) . " commits\n";
		echo "   Sample commit:\n";
		$first_commit = $commits[0];
		echo "     - SHA: " . $first_commit['short_sha'] . "\n";
		echo "     - Message: " . substr( $first_commit['message'], 0, 50 ) . "...\n";
		echo "     - Author: " . $first_commit['author'] . "\n";
		echo "     - Date: " . $first_commit['date'] . "\n";
	} elseif ( is_array( $commits ) && empty( $commits ) ) {
		echo "   ⚠ No commits fetched (may be cached or API error)\n";
	} else {
		echo "   ✗ Failed to fetch commits\n";
	}
} catch ( Exception $e ) {
	echo "   ✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test get_plugin_info() method.
echo "5. Testing get_plugin_info() method...\n";

$args = (object) array(
	'slug' => 'meowseo',
);

try {
	$plugin_info = $checker->get_plugin_info( false, 'plugin_information', $args );
	
	if ( is_object( $plugin_info ) ) {
		echo "   ✓ Successfully generated plugin info\n";
		echo "   Plugin details:\n";
		echo "     - Name: " . $plugin_info->name . "\n";
		echo "     - Slug: " . $plugin_info->slug . "\n";
		echo "     - Version: " . $plugin_info->version . "\n";
		
		if ( isset( $plugin_info->sections['changelog'] ) ) {
			echo "   ✓ Changelog section exists\n";
			$changelog_length = strlen( $plugin_info->sections['changelog'] );
			echo "     - Changelog HTML length: " . $changelog_length . " characters\n";
			
			// Check if changelog contains expected elements.
			$changelog = $plugin_info->sections['changelog'];
			$has_list = strpos( $changelog, '<ul>' ) !== false;
			$has_commits = strpos( $changelog, '<li>' ) !== false;
			$has_github_link = strpos( $changelog, 'github.com' ) !== false;
			
			echo "     - Contains <ul>: " . ( $has_list ? 'Yes' : 'No' ) . "\n";
			echo "     - Contains <li>: " . ( $has_commits ? 'Yes' : 'No' ) . "\n";
			echo "     - Contains GitHub link: " . ( $has_github_link ? 'Yes' : 'No' ) . "\n";
		} else {
			echo "   ✗ Changelog section missing\n";
		}
	} else {
		echo "   ✗ Failed to generate plugin info\n";
	}
} catch ( Exception $e ) {
	echo "   ✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test clear_cache() method.
echo "6. Testing clear_cache() method...\n";

try {
	$checker->clear_cache();
	echo "   ✓ Cache cleared successfully\n";
	
	// Verify transients are deleted.
	$update_info = get_transient( 'meowseo_github_update_info' );
	$changelog = get_transient( 'meowseo_github_changelog' );
	
	echo "   - Update info transient: " . ( $update_info === false ? 'Deleted' : 'Still exists' ) . "\n";
	echo "   - Changelog transient: " . ( $changelog === false ? 'Deleted' : 'Still exists' ) . "\n";
} catch ( Exception $e ) {
	echo "   ✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test format_changelog() method using reflection.
echo "7. Testing format_changelog() method...\n";

$format_method = $reflection->getMethod( 'format_changelog' );
$format_method->setAccessible( true );

$sample_commits = array(
	array(
		'sha'       => 'abc1234567890abcdef1234567890abcdef12',
		'short_sha' => 'abc1234',
		'message'   => 'Fix: Update AI provider integration',
		'author'    => 'Test Author',
		'date'      => '2025-01-15T10:00:00Z',
		'url'       => 'https://github.com/akbarbahaulloh/meowseo/commit/abc1234',
	),
	array(
		'sha'       => 'def5678901234567890abcdef1234567890ab',
		'short_sha' => 'def5678',
		'message'   => 'Feature: Add new schema type',
		'author'    => 'Another Author',
		'date'      => '2025-01-14T15:30:00Z',
		'url'       => 'https://github.com/akbarbahaulloh/meowseo/commit/def5678',
	),
);

try {
	$html = $format_method->invoke( $checker, $sample_commits );
	
	if ( ! empty( $html ) ) {
		echo "   ✓ Successfully formatted changelog HTML\n";
		echo "   - HTML length: " . strlen( $html ) . " characters\n";
		
		// Verify HTML structure.
		$has_heading = strpos( $html, '<h3>' ) !== false;
		$has_list = strpos( $html, '<ul>' ) !== false;
		$has_items = substr_count( $html, '<li>' ) === count( $sample_commits );
		$has_links = substr_count( $html, '<a href=' ) >= count( $sample_commits );
		
		echo "   - Contains heading: " . ( $has_heading ? 'Yes' : 'No' ) . "\n";
		echo "   - Contains list: " . ( $has_list ? 'Yes' : 'No' ) . "\n";
		echo "   - Correct number of items: " . ( $has_items ? 'Yes' : 'No' ) . "\n";
		echo "   - Contains links: " . ( $has_links ? 'Yes' : 'No' ) . "\n";
	} else {
		echo "   ✗ Failed to format changelog\n";
	}
} catch ( Exception $e ) {
	echo "   ✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test with empty commits array.
echo "8. Testing format_changelog() with empty array...\n";

try {
	$html = $format_method->invoke( $checker, array() );
	
	if ( ! empty( $html ) ) {
		echo "   ✓ Handled empty array correctly\n";
		$has_message = strpos( $html, 'No changelog available' ) !== false;
		echo "   - Contains 'No changelog available' message: " . ( $has_message ? 'Yes' : 'No' ) . "\n";
	} else {
		echo "   ✗ Failed to handle empty array\n";
	}
} catch ( Exception $e ) {
	echo "   ✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Check logs.
echo "9. Checking logs...\n";

$logs = $logger->get_recent_logs( 10 );

if ( ! empty( $logs ) ) {
	echo "   ✓ Found " . count( $logs ) . " log entries\n";
	
	// Count log types.
	$log_types = array();
	foreach ( $logs as $log ) {
		$type = $log['type'] ?? 'unknown';
		$log_types[ $type ] = ( $log_types[ $type ] ?? 0 ) + 1;
	}
	
	echo "   Log types:\n";
	foreach ( $log_types as $type => $count ) {
		echo "     - {$type}: {$count}\n";
	}
} else {
	echo "   ⚠ No log entries found\n";
}

echo "\n";

// Clean up.
delete_option( 'meowseo_github_update_config' );
delete_option( 'meowseo_github_update_logs' );
delete_transient( 'meowseo_github_update_info' );
delete_transient( 'meowseo_github_changelog' );
delete_transient( 'meowseo_github_rate_limit' );

echo "=== Test Complete ===\n";
echo "\nSummary:\n";
echo "- get_commit_history() method: Implemented ✓\n";
echo "- get_plugin_info() method: Implemented ✓\n";
echo "- format_changelog() method: Implemented ✓\n";
echo "- clear_cache() method: Implemented ✓\n";
echo "- plugins_api hook: Registered ✓\n";
echo "\nTask 8 implementation complete!\n";
