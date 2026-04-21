<?php
/**
 * Task 9 Complete Verification
 *
 * This script verifies all sub-tasks of Task 9:
 * - 9.1: API key input fields for new providers
 * - 9.2: Provider capability badges
 * - 9.3: Provider information and help text
 * - 9.4: Provider order drag-and-drop interface
 * - 9.5: Test Connection functionality
 *
 * @package MeowSEO\Tests
 */

echo "=== Task 9: Complete Verification ===\n\n";

// Load WordPress
require_once __DIR__ . '/../vendor/autoload.php';

$all_passed = true;

// ============================================================================
// Sub-task 9.1: API key input fields for new providers
// ============================================================================
echo "Sub-task 9.1: API key input fields for new providers\n";
echo str_repeat( '-', 60 ) . "\n";

$settings_file = __DIR__ . '/../includes/modules/ai/class-ai-settings.php';
$settings_content = file_get_contents( $settings_file );

$required_providers = array( 'deepseek', 'glm', 'qwen' );
$subtask_9_1_passed = true;

// Check register_settings method
foreach ( $required_providers as $provider ) {
	if ( strpos( $settings_content, "'$provider'" ) !== false ) {
		echo "  ✓ Found '$provider' in register_settings\n";
	} else {
		echo "  ✗ Missing '$provider' in register_settings\n";
		$subtask_9_1_passed = false;
	}
}

// Check provider configuration section (fields are dynamically generated)
if ( strpos( $settings_content, 'ai_api_key_<?php echo esc_attr( $provider_slug ); ?>' ) !== false ) {
	echo "  ✓ API key input fields are dynamically generated for all providers\n";
} else {
	echo "  ✗ API key input field generation code missing\n";
	$subtask_9_1_passed = false;
}

if ( $subtask_9_1_passed ) {
	echo "✓ Sub-task 9.1 PASSED\n\n";
} else {
	echo "✗ Sub-task 9.1 FAILED\n\n";
	$all_passed = false;
}

// ============================================================================
// Sub-task 9.2: Provider capability badges
// ============================================================================
echo "Sub-task 9.2: Provider capability badges\n";
echo str_repeat( '-', 60 ) . "\n";

$subtask_9_2_passed = true;

// Check that providers array includes capability flags
$providers_to_check = array(
	'deepseek' => array( 'supports_text' => true, 'supports_image' => true ),
	'glm' => array( 'supports_text' => true, 'supports_image' => true ),
	'qwen' => array( 'supports_text' => true, 'supports_image' => true ),
	'gemini' => array( 'supports_text' => true, 'supports_image' => true ),
);

foreach ( $providers_to_check as $slug => $capabilities ) {
	// Check for supports_text and supports_image in the providers array
	if ( preg_match( "/'$slug'.*?'supports_text'\s*=>\s*true/s", $settings_content ) &&
	     preg_match( "/'$slug'.*?'supports_image'\s*=>\s*true/s", $settings_content ) ) {
		echo "  ✓ $slug has Text + Image capability badges\n";
	} else {
		echo "  ✗ $slug missing capability badges\n";
		$subtask_9_2_passed = false;
	}
}

// Check for badge rendering code
if ( strpos( $settings_content, 'meowseo-capability-badge' ) !== false ) {
	echo "  ✓ Badge rendering code exists\n";
} else {
	echo "  ✗ Badge rendering code missing\n";
	$subtask_9_2_passed = false;
}

if ( $subtask_9_2_passed ) {
	echo "✓ Sub-task 9.2 PASSED\n\n";
} else {
	echo "✗ Sub-task 9.2 FAILED\n\n";
	$all_passed = false;
}

// ============================================================================
// Sub-task 9.3: Provider information and help text
// ============================================================================
echo "Sub-task 9.3: Provider information and help text\n";
echo str_repeat( '-', 60 ) . "\n";

$subtask_9_3_passed = true;

$info_fields = array(
	'model' => 'Model information',
	'context_window' => 'Context window size',
	'pricing' => 'Pricing information',
	'api_key_url' => 'API key URL',
	'regional_note' => 'Regional availability notes',
);

foreach ( $required_providers as $provider ) {
	$provider_found = false;
	
	foreach ( $info_fields as $field => $description ) {
		if ( preg_match( "/'$provider'.*?'$field'/s", $settings_content ) ) {
			$provider_found = true;
		}
	}
	
	if ( $provider_found ) {
		echo "  ✓ $provider has provider information fields\n";
	} else {
		echo "  ✗ $provider missing provider information\n";
		$subtask_9_3_passed = false;
	}
}

// Check for specific help text elements
$help_elements = array(
	'meowseo-provider-info' => 'Provider info section',
	'meowseo-api-key-link' => 'API key link',
	'meowseo-provider-regional-note' => 'Regional note styling',
);

foreach ( $help_elements as $class => $description ) {
	if ( strpos( $settings_content, $class ) !== false ) {
		echo "  ✓ Found $description\n";
	} else {
		echo "  ✗ Missing $description\n";
		$subtask_9_3_passed = false;
	}
}

if ( $subtask_9_3_passed ) {
	echo "✓ Sub-task 9.3 PASSED\n\n";
} else {
	echo "✗ Sub-task 9.3 FAILED\n\n";
	$all_passed = false;
}

// ============================================================================
// Sub-task 9.4: Provider order drag-and-drop interface
// ============================================================================
echo "Sub-task 9.4: Provider order drag-and-drop interface\n";
echo str_repeat( '-', 60 ) . "\n";

$subtask_9_4_passed = true;

// Check JavaScript file
$js_file = __DIR__ . '/../includes/modules/ai/assets/js/ai-settings.js';
$js_content = file_get_contents( $js_file );

if ( strpos( $js_content, 'initDragAndDrop' ) !== false ) {
	echo "  ✓ Drag-and-drop initialization exists\n";
} else {
	echo "  ✗ Drag-and-drop initialization missing\n";
	$subtask_9_4_passed = false;
}

if ( strpos( $js_content, 'updateProviderOrder' ) !== false ) {
	echo "  ✓ Provider order update function exists\n";
} else {
	echo "  ✗ Provider order update function missing\n";
	$subtask_9_4_passed = false;
}

// Check that new providers are in the default order (they're in the loop)
$provider_order_pattern = '/\$provider_order = \$this->options->get\( \'ai_provider_order\', array\((.*?)\) \);/s';
if ( preg_match( $provider_order_pattern, $settings_content, $matches ) ) {
	$order_string = $matches[1];
	$all_providers_in_order = true;
	
	foreach ( $required_providers as $provider ) {
		if ( strpos( $order_string, "'$provider'" ) !== false ) {
			echo "  ✓ $provider is in default provider order\n";
		} else {
			echo "  ✗ $provider missing from default provider order\n";
			$all_providers_in_order = false;
		}
	}
	
	if ( ! $all_providers_in_order ) {
		$subtask_9_4_passed = false;
	}
} else {
	echo "  ✗ Could not find provider order initialization\n";
	$subtask_9_4_passed = false;
}

// Check for active provider toggle
if ( strpos( $settings_content, 'meowseo-provider-active-toggle' ) !== false ) {
	echo "  ✓ Active provider toggle exists\n";
} else {
	echo "  ✗ Active provider toggle missing\n";
	$subtask_9_4_passed = false;
}

if ( $subtask_9_4_passed ) {
	echo "✓ Sub-task 9.4 PASSED\n\n";
} else {
	echo "✗ Sub-task 9.4 FAILED\n\n";
	$all_passed = false;
}

// ============================================================================
// Sub-task 9.5: Test Connection functionality
// ============================================================================
echo "Sub-task 9.5: Test Connection functionality\n";
echo str_repeat( '-', 60 ) . "\n";

$subtask_9_5_passed = true;

// Check JavaScript implementation
if ( strpos( $js_content, 'initTestConnection' ) !== false ) {
	echo "  ✓ Test connection initialization exists\n";
} else {
	echo "  ✗ Test connection initialization missing\n";
	$subtask_9_5_passed = false;
}

if ( strpos( $js_content, 'testProviderConnection' ) !== false ) {
	echo "  ✓ Test provider connection function exists\n";
} else {
	echo "  ✗ Test provider connection function missing\n";
	$subtask_9_5_passed = false;
}

if ( strpos( $js_content, 'test-provider' ) !== false ) {
	echo "  ✓ JavaScript calls test-provider endpoint\n";
} else {
	echo "  ✗ JavaScript does not call test-provider endpoint\n";
	$subtask_9_5_passed = false;
}

// Check REST API support
$rest_file = __DIR__ . '/../includes/modules/ai/class-ai-rest.php';
$rest_content = file_get_contents( $rest_file );

foreach ( $required_providers as $provider ) {
	if ( strpos( $rest_content, "'$provider'" ) !== false ) {
		echo "  ✓ REST API supports $provider\n";
	} else {
		echo "  ✗ REST API does not support $provider\n";
		$subtask_9_5_passed = false;
	}
}

// Check for validate_api_key call
if ( strpos( $rest_content, 'validate_api_key' ) !== false ) {
	echo "  ✓ REST API calls validate_api_key\n";
} else {
	echo "  ✗ REST API does not call validate_api_key\n";
	$subtask_9_5_passed = false;
}

// Check UI elements
if ( strpos( $settings_content, 'meowseo-test-connection-btn' ) !== false ) {
	echo "  ✓ Test connection button exists in UI\n";
} else {
	echo "  ✗ Test connection button missing from UI\n";
	$subtask_9_5_passed = false;
}

if ( strpos( $settings_content, 'meowseo-test-status' ) !== false ) {
	echo "  ✓ Test status display exists in UI\n";
} else {
	echo "  ✗ Test status display missing from UI\n";
	$subtask_9_5_passed = false;
}

if ( $subtask_9_5_passed ) {
	echo "✓ Sub-task 9.5 PASSED\n\n";
} else {
	echo "✗ Sub-task 9.5 FAILED\n\n";
	$all_passed = false;
}

// ============================================================================
// Final Summary
// ============================================================================
echo str_repeat( '=', 60 ) . "\n";
if ( $all_passed ) {
	echo "✓✓✓ ALL SUB-TASKS PASSED ✓✓✓\n";
	echo "\nTask 9 is COMPLETE:\n";
	echo "  ✓ 9.1: API key input fields for new providers\n";
	echo "  ✓ 9.2: Provider capability badges\n";
	echo "  ✓ 9.3: Provider information and help text\n";
	echo "  ✓ 9.4: Provider order drag-and-drop interface\n";
	echo "  ✓ 9.5: Test Connection functionality\n";
	echo "\nAll new providers (DeepSeek, GLM, Qwen) are fully integrated\n";
	echo "into the AI Settings UI with complete configuration support.\n";
	exit( 0 );
} else {
	echo "✗✗✗ SOME SUB-TASKS FAILED ✗✗✗\n";
	echo "\nPlease review the failed sub-tasks above.\n";
	exit( 1 );
}
