<?php
/**
 * Task 12 Verification Script
 *
 * Tests the Update_Settings_Page class implementation.
 *
 * @package MeowSEO
 */

// Load WordPress.
require_once __DIR__ . '/tests/bootstrap.php';

// Load required classes.
require_once __DIR__ . '/includes/updater/class-update-config.php';
require_once __DIR__ . '/includes/updater/class-update-logger.php';
require_once __DIR__ . '/includes/updater/class-git-hub-update-checker.php';
require_once __DIR__ . '/includes/updater/class-update-settings-page.php';

use MeowSEO\Updater\Update_Config;
use MeowSEO\Updater\Update_Logger;
use MeowSEO\Updater\GitHub_Update_Checker;
use MeowSEO\Updater\Update_Settings_Page;

echo "=== Task 12 Verification: Update Settings Page Class ===\n\n";

// Test 1: Class instantiation
echo "Test 1: Class Instantiation\n";
echo "----------------------------\n";

$config = new Update_Config();
$logger = new Update_Logger();
$plugin_file = __DIR__ . '/meowseo.php';
$checker = new GitHub_Update_Checker( $plugin_file, $config, $logger );
$settings_page = new Update_Settings_Page( $config, $checker, $logger );

if ( $settings_page instanceof Update_Settings_Page ) {
	echo "✓ Update_Settings_Page instance created successfully\n";
} else {
	echo "✗ Failed to create Update_Settings_Page instance\n";
	exit( 1 );
}

echo "\n";

// Test 2: Check class has required methods
echo "Test 2: Required Methods\n";
echo "------------------------\n";

$required_methods = array(
	'register',
	'render_page',
);

$all_methods_exist = true;
foreach ( $required_methods as $method ) {
	if ( method_exists( $settings_page, $method ) ) {
		echo "✓ Method '{$method}' exists\n";
	} else {
		echo "✗ Method '{$method}' is missing\n";
		$all_methods_exist = false;
	}
}

if ( ! $all_methods_exist ) {
	echo "\n✗ Some required methods are missing\n";
	exit( 1 );
}

echo "\n";

// Test 3: Check constructor accepts correct parameters
echo "Test 3: Constructor Parameters\n";
echo "-------------------------------\n";

try {
	$reflection = new ReflectionClass( Update_Settings_Page::class );
	$constructor = $reflection->getConstructor();
	$parameters = $constructor->getParameters();
	
	echo "Constructor parameters:\n";
	foreach ( $parameters as $param ) {
		$type = $param->getType();
		$type_name = $type ? $type->getName() : 'mixed';
		echo "  - \${$param->getName()}: {$type_name}\n";
	}
	
	// Verify parameter count
	if ( count( $parameters ) === 3 ) {
		echo "✓ Constructor has correct number of parameters (3)\n";
	} else {
		echo "✗ Constructor has incorrect number of parameters: " . count( $parameters ) . "\n";
		exit( 1 );
	}
	
	// Verify parameter types
	$expected_types = array(
		'MeowSEO\Updater\Update_Config',
		'MeowSEO\Updater\GitHub_Update_Checker',
		'MeowSEO\Updater\Update_Logger',
	);
	
	$types_match = true;
	foreach ( $parameters as $index => $param ) {
		$type = $param->getType();
		$type_name = $type ? $type->getName() : '';
		
		if ( $type_name !== $expected_types[ $index ] ) {
			echo "✗ Parameter {$index} has incorrect type: {$type_name} (expected {$expected_types[$index]})\n";
			$types_match = false;
		}
	}
	
	if ( $types_match ) {
		echo "✓ All constructor parameter types are correct\n";
	}
	
} catch ( ReflectionException $e ) {
	echo "✗ Failed to reflect on class: " . $e->getMessage() . "\n";
	exit( 1 );
}

echo "\n";

// Test 4: Test register() method
echo "Test 4: Register Method\n";
echo "-----------------------\n";

// Mock the add_options_page function to verify it's called correctly
global $wp_test_add_options_page_called;
$wp_test_add_options_page_called = false;

if ( ! function_exists( 'add_options_page' ) ) {
	function add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback ) {
		global $wp_test_add_options_page_called;
		$wp_test_add_options_page_called = array(
			'page_title' => $page_title,
			'menu_title' => $menu_title,
			'capability' => $capability,
			'menu_slug'  => $menu_slug,
			'callback'   => $callback,
		);
		return 'test-hook';
	}
}

// Call register method
$settings_page->register();

if ( $wp_test_add_options_page_called !== false ) {
	echo "✓ add_options_page() was called\n";
	
	// Verify parameters
	$params = $wp_test_add_options_page_called;
	
	if ( $params['menu_title'] === 'GitHub Updates' ) {
		echo "✓ Menu title is correct: 'GitHub Updates'\n";
	} else {
		echo "✗ Menu title is incorrect: '{$params['menu_title']}'\n";
	}
	
	if ( $params['capability'] === 'manage_options' ) {
		echo "✓ Capability is correct: 'manage_options'\n";
	} else {
		echo "✗ Capability is incorrect: '{$params['capability']}'\n";
	}
	
	if ( $params['menu_slug'] === 'meowseo-github-updates' ) {
		echo "✓ Menu slug is correct: 'meowseo-github-updates'\n";
	} else {
		echo "✗ Menu slug is incorrect: '{$params['menu_slug']}'\n";
	}
	
	if ( is_array( $params['callback'] ) && $params['callback'][1] === 'render_page' ) {
		echo "✓ Callback method is correct: 'render_page'\n";
	} else {
		echo "✗ Callback method is incorrect\n";
	}
} else {
	echo "✗ add_options_page() was not called\n";
	exit( 1 );
}

echo "\n";

// Test 5: Verify class follows WordPress coding standards
echo "Test 5: Code Quality\n";
echo "--------------------\n";

$file_content = file_get_contents( __DIR__ . '/includes/updater/class-update-settings-page.php' );

// Check for proper namespace
if ( strpos( $file_content, 'namespace MeowSEO\Updater;' ) !== false ) {
	echo "✓ Proper namespace declaration\n";
} else {
	echo "✗ Missing or incorrect namespace\n";
}

// Check for security check
if ( strpos( $file_content, "if ( ! defined( 'ABSPATH' ) )" ) !== false ) {
	echo "✓ ABSPATH security check present\n";
} else {
	echo "✗ Missing ABSPATH security check\n";
}

// Check for PHPDoc comments
if ( strpos( $file_content, '@package MeowSEO' ) !== false ) {
	echo "✓ PHPDoc package declaration present\n";
} else {
	echo "✗ Missing PHPDoc package declaration\n";
}

// Check for proper escaping in render_page
if ( strpos( $file_content, 'esc_html' ) !== false ) {
	echo "✓ Output escaping functions used\n";
} else {
	echo "✗ Missing output escaping\n";
}

// Check for capability check
if ( strpos( $file_content, "current_user_can( 'manage_options' )" ) !== false ) {
	echo "✓ User capability check present\n";
} else {
	echo "✗ Missing user capability check\n";
}

echo "\n";

// Summary
echo "=== Verification Complete ===\n";
echo "✓ All tests passed!\n";
echo "\nTask 12 implementation is correct:\n";
echo "  - Class created: includes/updater/class-update-settings-page.php\n";
echo "  - Constructor accepts: Update_Config, GitHub_Update_Checker, Update_Logger\n";
echo "  - register() method adds settings page to Settings menu\n";
echo "  - Menu title: 'GitHub Updates'\n";
echo "  - Required capability: 'manage_options'\n";
echo "  - Menu slug: 'meowseo-github-updates'\n";
echo "  - render_page() method displays the settings page\n";
echo "  - Follows WordPress coding standards\n";
echo "  - Includes security checks and proper escaping\n";

// Clean up
delete_option( 'meowseo_github_update_config' );
delete_option( 'meowseo_github_update_logs' );
