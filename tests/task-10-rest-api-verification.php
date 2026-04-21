<?php
/**
 * Task 10 Verification: AI_REST for new provider endpoints
 *
 * This script verifies that:
 * - Task 10.1: valid_providers array includes 'deepseek', 'glm', 'qwen'
 * - Task 10.2: get_provider_instance() method supports new providers
 *
 * @package MeowSEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=== Task 10 Verification: AI_REST for new provider endpoints ===\n\n";

// Test 10.1: Verify valid_providers array includes new providers
echo "Test 10.1: Verifying valid_providers array...\n";

$rest_file = __DIR__ . '/../includes/modules/ai/class-ai-rest.php';
if ( ! file_exists( $rest_file ) ) {
	echo "  ✗ AI_REST file not found\n";
	exit( 1 );
}

$rest_content = file_get_contents( $rest_file );

// Check for valid_providers array with new providers
if ( preg_match( "/private array \\\$valid_providers = array\([^)]+\);/s", $rest_content, $matches ) ) {
	$valid_providers_line = $matches[0];
	
	$has_deepseek = strpos( $valid_providers_line, "'deepseek'" ) !== false;
	$has_glm = strpos( $valid_providers_line, "'glm'" ) !== false;
	$has_qwen = strpos( $valid_providers_line, "'qwen'" ) !== false;
	
	if ( $has_deepseek && $has_glm && $has_qwen ) {
		echo "  ✓ valid_providers array includes 'deepseek', 'glm', 'qwen'\n";
	} else {
		echo "  ✗ valid_providers array missing new providers:\n";
		if ( ! $has_deepseek ) {
			echo "    - Missing 'deepseek'\n";
		}
		if ( ! $has_glm ) {
			echo "    - Missing 'glm'\n";
		}
		if ( ! $has_qwen ) {
			echo "    - Missing 'qwen'\n";
		}
		exit( 1 );
	}
} else {
	echo "  ✗ Could not find valid_providers array\n";
	exit( 1 );
}

// Test 10.2: Verify get_provider_instance() method supports new providers
echo "\nTest 10.2: Verifying get_provider_instance() method...\n";

// Check for provider_classes array in get_provider_instance method
if ( preg_match( "/private function get_provider_instance.*?\{.*?\\\$provider_classes = array\((.*?)\);/s", $rest_content, $matches ) ) {
	$provider_classes_content = $matches[1];
	
	$has_deepseek_class = strpos( $provider_classes_content, "'deepseek'" ) !== false 
		&& strpos( $provider_classes_content, 'Provider_DeepSeek::class' ) !== false;
	$has_glm_class = strpos( $provider_classes_content, "'glm'" ) !== false 
		&& strpos( $provider_classes_content, 'Provider_GLM::class' ) !== false;
	$has_qwen_class = strpos( $provider_classes_content, "'qwen'" ) !== false 
		&& strpos( $provider_classes_content, 'Provider_Qwen::class' ) !== false;
	
	if ( $has_deepseek_class && $has_glm_class && $has_qwen_class ) {
		echo "  ✓ get_provider_instance() includes all new provider classes\n";
	} else {
		echo "  ✗ get_provider_instance() missing new provider classes:\n";
		if ( ! $has_deepseek_class ) {
			echo "    - Missing 'deepseek' => Provider_DeepSeek::class\n";
		}
		if ( ! $has_glm_class ) {
			echo "    - Missing 'glm' => Provider_GLM::class\n";
		}
		if ( ! $has_qwen_class ) {
			echo "    - Missing 'qwen' => Provider_Qwen::class\n";
		}
		exit( 1 );
	}
} else {
	echo "  ✗ Could not find get_provider_instance() method or provider_classes array\n";
	exit( 1 );
}

// Verify provider class files exist
echo "\nVerifying provider class files exist...\n";

$provider_files = array(
	'deepseek' => __DIR__ . '/../includes/modules/ai/providers/class-provider-deep-seek.php',
	'glm'      => __DIR__ . '/../includes/modules/ai/providers/class-provider-glm.php',
	'qwen'     => __DIR__ . '/../includes/modules/ai/providers/class-provider-qwen.php',
);

$all_files_exist = true;
foreach ( $provider_files as $provider => $file ) {
	if ( file_exists( $file ) ) {
		echo "  ✓ Provider file exists: $provider\n";
	} else {
		echo "  ✗ Provider file missing: $provider ($file)\n";
		$all_files_exist = false;
	}
}

if ( ! $all_files_exist ) {
	exit( 1 );
}

// Verify test-provider endpoint is registered
echo "\nVerifying test-provider endpoint registration...\n";

if ( preg_match( "/register_rest_route.*?'\/ai\/test-provider'/s", $rest_content ) ) {
	echo "  ✓ /ai/test-provider endpoint is registered\n";
} else {
	echo "  ✗ /ai/test-provider endpoint not found\n";
	exit( 1 );
}

// Verify test_provider method exists and validates provider slugs
echo "\nVerifying test_provider() method implementation...\n";

if ( preg_match( "/public function test_provider\(/", $rest_content ) ) {
	echo "  ✓ test_provider() method exists\n";
	
	// Check if it validates against valid_providers
	if ( preg_match( "/in_array.*?\\\$provider.*?\\\$this->valid_providers/s", $rest_content ) ) {
		echo "  ✓ test_provider() validates against valid_providers array\n";
	} else {
		echo "  ✗ test_provider() does not validate against valid_providers array\n";
		exit( 1 );
	}
	
	// Check if it calls get_provider_instance
	if ( preg_match( "/\\\$this->get_provider_instance\(/", $rest_content ) ) {
		echo "  ✓ test_provider() calls get_provider_instance()\n";
	} else {
		echo "  ✗ test_provider() does not call get_provider_instance()\n";
		exit( 1 );
	}
	
	// Check if it calls validate_api_key
	if ( preg_match( "/->validate_api_key\(/", $rest_content ) ) {
		echo "  ✓ test_provider() calls validate_api_key()\n";
	} else {
		echo "  ✗ test_provider() does not call validate_api_key()\n";
		exit( 1 );
	}
} else {
	echo "  ✗ test_provider() method not found\n";
	exit( 1 );
}

echo "\n=== All Task 10 verifications passed! ===\n";
echo "\nSummary:\n";
echo "  ✓ Task 10.1: valid_providers array includes 'deepseek', 'glm', 'qwen'\n";
echo "  ✓ Task 10.2: get_provider_instance() supports new providers\n";
echo "  ✓ test-provider endpoint is properly registered\n";
echo "  ✓ test_provider() method validates and tests new providers\n";
echo "  ✓ All provider class files exist\n";

exit( 0 );
