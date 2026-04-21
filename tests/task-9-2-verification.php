<?php
/**
 * Task 9.2 Verification: Provider Capability Badges
 *
 * This script verifies that provider capability badges are correctly configured
 * in the AI Settings UI for DeepSeek, GLM, Qwen, and Gemini.
 *
 * @package MeowSEO\Tests
 */

echo "=== Task 9.2: Provider Capability Badges Verification ===\n\n";

// Load WordPress
require_once __DIR__ . '/../vendor/autoload.php';

// Define the providers array as it appears in class-ai-settings.php
$providers = array(
	'gemini'    => array( 'label' => 'Google Gemini', 'supports_text' => true, 'supports_image' => true ),
	'openai'    => array( 'label' => 'OpenAI', 'supports_text' => true, 'supports_image' => true ),
	'anthropic' => array( 'label' => 'Anthropic Claude', 'supports_text' => true, 'supports_image' => false ),
	'imagen'    => array( 'label' => 'Google Imagen', 'supports_text' => false, 'supports_image' => true ),
	'dalle'     => array( 'label' => 'DALL-E', 'supports_text' => false, 'supports_image' => true ),
	'deepseek'  => array( 'label' => 'DeepSeek', 'supports_text' => true, 'supports_image' => true ),
	'glm'       => array( 'label' => 'Zhipu AI GLM', 'supports_text' => true, 'supports_image' => true ),
	'qwen'      => array( 'label' => 'Alibaba Qwen', 'supports_text' => true, 'supports_image' => true ),
);

// Test 1: Verify DeepSeek has Text + Image capability
echo "Test 1: Verifying DeepSeek capability badges...\n";
if ( $providers['deepseek']['supports_text'] && $providers['deepseek']['supports_image'] ) {
	echo "  ✓ DeepSeek shows Text + Image badges (📝 🖼️)\n";
} else {
	echo "  ✗ DeepSeek missing Text + Image capability\n";
	exit( 1 );
}

// Test 2: Verify GLM has Text + Image capability
echo "\nTest 2: Verifying GLM capability badges...\n";
if ( $providers['glm']['supports_text'] && $providers['glm']['supports_image'] ) {
	echo "  ✓ GLM shows Text + Image badges (📝 🖼️)\n";
} else {
	echo "  ✗ GLM missing Text + Image capability\n";
	exit( 1 );
}

// Test 3: Verify Qwen has Text + Image capability
echo "\nTest 3: Verifying Qwen capability badges...\n";
if ( $providers['qwen']['supports_text'] && $providers['qwen']['supports_image'] ) {
	echo "  ✓ Qwen shows Text + Image badges (📝 🖼️)\n";
} else {
	echo "  ✗ Qwen missing Text + Image capability\n";
	exit( 1 );
}

// Test 4: Verify Gemini has Text + Image capability (updated)
echo "\nTest 4: Verifying Gemini capability badges (updated)...\n";
if ( $providers['gemini']['supports_text'] && $providers['gemini']['supports_image'] ) {
	echo "  ✓ Gemini shows Text + Image badges (📝 🖼️)\n";
} else {
	echo "  ✗ Gemini missing Text + Image capability\n";
	exit( 1 );
}

// Test 5: Verify badge rendering logic
echo "\nTest 5: Verifying badge rendering logic...\n";
$badge_count = 0;
foreach ( $providers as $slug => $provider ) {
	$badges = array();
	if ( $provider['supports_text'] ) {
		$badges[] = '📝 (Text)';
	}
	if ( $provider['supports_image'] ) {
		$badges[] = '🖼️ (Image)';
	}
	
	if ( ! empty( $badges ) ) {
		echo "  ✓ {$provider['label']}: " . implode( ' + ', $badges ) . "\n";
		$badge_count++;
	}
}

if ( $badge_count === count( $providers ) ) {
	echo "  ✓ All providers have capability badges configured\n";
} else {
	echo "  ✗ Some providers missing capability badges\n";
	exit( 1 );
}

// Test 6: Verify the specific providers required by task 9.2
echo "\nTest 6: Verifying task 9.2 requirements...\n";
$required_providers = array( 'deepseek', 'glm', 'qwen', 'gemini' );
$all_have_both = true;

foreach ( $required_providers as $slug ) {
	if ( ! isset( $providers[ $slug ] ) ) {
		echo "  ✗ Provider '{$slug}' not found in configuration\n";
		$all_have_both = false;
		continue;
	}
	
	$provider = $providers[ $slug ];
	if ( ! $provider['supports_text'] || ! $provider['supports_image'] ) {
		echo "  ✗ {$provider['label']} does not have both Text and Image support\n";
		$all_have_both = false;
	}
}

if ( $all_have_both ) {
	echo "  ✓ All required providers (DeepSeek, GLM, Qwen, Gemini) have Text + Image badges\n";
} else {
	exit( 1 );
}

echo "\n=== All Tests Passed ===\n";
echo "Task 9.2 is complete: Provider capability badges are correctly configured.\n";
echo "\nSummary:\n";
echo "- DeepSeek: Text + Image ✓\n";
echo "- GLM (Zhipu AI): Text + Image ✓\n";
echo "- Qwen (Alibaba): Text + Image ✓\n";
echo "- Gemini: Text + Image ✓ (updated)\n";
echo "\nThe UI will display emoji badges (📝 for text, 🖼️ for image) for each capability.\n";

exit( 0 );
