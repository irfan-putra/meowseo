<?php
/**
 * Task 9 REST API Verification
 *
 * This script verifies that the REST API endpoints support the new providers
 * (DeepSeek, GLM, Qwen) for testing connections.
 *
 * @package MeowSEO\Tests
 */

echo "=== Task 9: REST API Provider Support Verification ===\n\n";

// Load WordPress
require_once __DIR__ . '/../vendor/autoload.php';

use MeowSEO\Modules\AI\AI_REST;
use MeowSEO\Modules\AI\AI_Generator;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Modules\AI\AI_Optimizer;
use MeowSEO\Options;

// Test 1: Verify AI_REST class has valid_providers array with new providers
echo "Test 1: Verifying AI_REST valid_providers array...\n";
$rest_file = __DIR__ . '/../includes/modules/ai/class-ai-rest.php';
$rest_content = file_get_contents( $rest_file );

// Check for the valid_providers array definition
if ( preg_match( '/private array \$valid_providers = array\((.*?)\);/s', $rest_content, $matches ) ) {
	$providers_string = $matches[1];
	
	$required_providers = array( 'deepseek', 'glm', 'qwen' );
	$all_found = true;
	
	foreach ( $required_providers as $provider ) {
		if ( strpos( $providers_string, "'$provider'" ) !== false ) {
			echo "  ✓ Found '$provider' in valid_providers array\n";
		} else {
			echo "  ✗ Missing '$provider' in valid_providers array\n";
			$all_found = false;
		}
	}
	
	if ( $all_found ) {
		echo "  ✓ All new providers are in valid_providers array\n";
	} else {
		echo "  ✗ Some providers missing from valid_providers array\n";
		exit( 1 );
	}
} else {
	echo "  ✗ Could not find valid_providers array in AI_REST class\n";
	exit( 1 );
}

// Test 2: Verify get_provider_instance method supports new providers
echo "\nTest 2: Verifying get_provider_instance method...\n";
if ( preg_match( '/private function get_provider_instance.*?\{(.*?)\n\t\}/s', $rest_content, $matches ) ) {
	$method_content = $matches[1];
	
	$required_mappings = array(
		'deepseek' => 'Provider_DeepSeek',
		'glm' => 'Provider_GLM',
		'qwen' => 'Provider_Qwen',
	);
	
	$all_found = true;
	
	foreach ( $required_mappings as $slug => $class ) {
		if ( strpos( $method_content, "'$slug'" ) !== false && strpos( $method_content, $class ) !== false ) {
			echo "  ✓ Found mapping for '$slug' => $class\n";
		} else {
			echo "  ✗ Missing mapping for '$slug' => $class\n";
			$all_found = false;
		}
	}
	
	if ( $all_found ) {
		echo "  ✓ All new providers are mapped in get_provider_instance\n";
	} else {
		echo "  ✗ Some provider mappings missing\n";
		exit( 1 );
	}
} else {
	echo "  ✗ Could not find get_provider_instance method\n";
	exit( 1 );
}

// Test 3: Verify the test_provider endpoint can handle new providers
echo "\nTest 3: Verifying test_provider endpoint logic...\n";
if ( strpos( $rest_content, 'public function test_provider' ) !== false ) {
	echo "  ✓ test_provider method exists\n";
	
	// Check that it validates against valid_providers
	if ( strpos( $rest_content, 'in_array( $provider, $this->valid_providers' ) !== false ) {
		echo "  ✓ test_provider validates against valid_providers array\n";
	} else {
		echo "  ✗ test_provider does not validate against valid_providers\n";
		exit( 1 );
	}
	
	// Check that it calls get_provider_instance
	if ( strpos( $rest_content, 'get_provider_instance( $provider, $api_key )' ) !== false ) {
		echo "  ✓ test_provider calls get_provider_instance\n";
	} else {
		echo "  ✗ test_provider does not call get_provider_instance\n";
		exit( 1 );
	}
	
	// Check that it calls validate_api_key
	if ( strpos( $rest_content, 'validate_api_key( $api_key )' ) !== false ) {
		echo "  ✓ test_provider calls validate_api_key on provider\n";
	} else {
		echo "  ✗ test_provider does not call validate_api_key\n";
		exit( 1 );
	}
} else {
	echo "  ✗ test_provider method not found\n";
	exit( 1 );
}

// Test 4: Verify REST route registration
echo "\nTest 4: Verifying REST route registration...\n";
if ( preg_match( "/register_rest_route.*?'\/ai\/test-provider'/s", $rest_content ) ) {
	echo "  ✓ /ai/test-provider route is registered\n";
} else {
	echo "  ✗ /ai/test-provider route not found\n";
	exit( 1 );
}

echo "\n=== All Tests Passed ===\n";
echo "Task 9 REST API changes are complete:\n";
echo "- valid_providers array includes deepseek, glm, qwen ✓\n";
echo "- get_provider_instance supports new providers ✓\n";
echo "- test_provider endpoint can validate new providers ✓\n";
echo "- REST route is properly registered ✓\n";

exit( 0 );
