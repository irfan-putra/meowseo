<?php
/**
 * Checkpoint 8 Verification Script
 *
 * Verifies that all three new providers (DeepSeek, GLM, Qwen) are properly
 * integrated with the Provider Manager.
 *
 * @package MeowSEO\Tests
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress constants if not already defined.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'AUTH_KEY' ) ) {
	define( 'AUTH_KEY', 'test-auth-key-for-verification-32-chars!' );
}

use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Modules\AI\Providers\Provider_DeepSeek;
use MeowSEO\Modules\AI\Providers\Provider_GLM;
use MeowSEO\Modules\AI\Providers\Provider_Qwen;
use MeowSEO\Options;

echo "=== Checkpoint 8: Provider Manager Integration Verification ===\n\n";

// Test 1: Verify provider classes can be loaded
echo "Test 1: Verifying provider classes can be loaded...\n";
$classes = [
	'DeepSeek' => Provider_DeepSeek::class,
	'GLM'      => Provider_GLM::class,
	'Qwen'     => Provider_Qwen::class,
];

foreach ( $classes as $name => $class ) {
	if ( class_exists( $class ) ) {
		echo "  ✓ {$name} provider class loaded\n";
	} else {
		echo "  ✗ {$name} provider class NOT found\n";
		exit( 1 );
	}
}

// Test 2: Verify providers can be instantiated
echo "\nTest 2: Verifying providers can be instantiated...\n";
$test_key = 'test-api-key-12345';

try {
	$deepseek = new Provider_DeepSeek( $test_key );
	echo "  ✓ DeepSeek provider instantiated\n";
} catch ( Exception $e ) {
	echo "  ✗ DeepSeek instantiation failed: " . $e->getMessage() . "\n";
	exit( 1 );
}

try {
	$glm = new Provider_GLM( $test_key );
	echo "  ✓ GLM provider instantiated\n";
} catch ( Exception $e ) {
	echo "  ✗ GLM instantiation failed: " . $e->getMessage() . "\n";
	exit( 1 );
}

try {
	$qwen = new Provider_Qwen( $test_key );
	echo "  ✓ Qwen provider instantiated\n";
} catch ( Exception $e ) {
	echo "  ✗ Qwen instantiation failed: " . $e->getMessage() . "\n";
	exit( 1 );
}

// Test 3: Verify provider slugs and labels
echo "\nTest 3: Verifying provider slugs and labels...\n";
$expected = [
	'deepseek' => [ 'slug' => 'deepseek', 'label' => 'DeepSeek' ],
	'glm'      => [ 'slug' => 'glm', 'label' => 'Zhipu AI GLM' ],
	'qwen'     => [ 'slug' => 'qwen', 'label' => 'Alibaba Qwen' ],
];

foreach ( [ $deepseek, $glm, $qwen ] as $provider ) {
	$slug = $provider->get_slug();
	$label = $provider->get_label();
	
	if ( isset( $expected[ $slug ] ) ) {
		if ( $expected[ $slug ]['slug'] === $slug && $expected[ $slug ]['label'] === $label ) {
			echo "  ✓ {$label} has correct slug '{$slug}' and label\n";
		} else {
			echo "  ✗ {$slug} has incorrect slug or label\n";
			exit( 1 );
		}
	} else {
		echo "  ✗ Unexpected provider slug: {$slug}\n";
		exit( 1 );
	}
}

// Test 4: Verify provider capabilities
echo "\nTest 4: Verifying provider capabilities...\n";
foreach ( [ $deepseek, $glm, $qwen ] as $provider ) {
	$slug = $provider->get_slug();
	$supports_text = $provider->supports_text();
	$supports_image = $provider->supports_image();
	
	if ( $supports_text && $supports_image ) {
		echo "  ✓ {$slug} supports both text and image generation\n";
	} else {
		echo "  ✗ {$slug} missing capabilities (text: " . ( $supports_text ? 'yes' : 'no' ) . ", image: " . ( $supports_image ? 'yes' : 'no' ) . ")\n";
		exit( 1 );
	}
}

// Test 5: Verify Provider Manager integration
echo "\nTest 5: Verifying Provider Manager integration...\n";

// Mock Options class
$options = new class {
	public function get( $key, $default = null ) {
		return $default;
	}
};

try {
	$manager = new AI_Provider_Manager( $options );
	echo "  ✓ Provider Manager instantiated\n";
} catch ( Exception $e ) {
	echo "  ✗ Provider Manager instantiation failed: " . $e->getMessage() . "\n";
	exit( 1 );
}

// Test 6: Verify provider statuses include new providers
echo "\nTest 6: Verifying provider statuses include new providers...\n";
$statuses = $manager->get_provider_statuses();

$required_providers = [ 'deepseek', 'glm', 'qwen' ];
foreach ( $required_providers as $slug ) {
	if ( isset( $statuses[ $slug ] ) ) {
		echo "  ✓ Provider status includes '{$slug}'\n";
	} else {
		echo "  ✗ Provider status missing '{$slug}'\n";
		exit( 1 );
	}
}

// Test 7: Verify provider status structure
echo "\nTest 7: Verifying provider status structure...\n";
$required_keys = [ 'label', 'active', 'has_api_key', 'supports_text', 'supports_image', 'rate_limited', 'rate_limit_remaining', 'priority' ];

foreach ( $required_providers as $slug ) {
	$status = $statuses[ $slug ];
	$missing_keys = [];
	
	foreach ( $required_keys as $key ) {
		if ( ! array_key_exists( $key, $status ) ) {
			$missing_keys[] = $key;
		}
	}
	
	if ( empty( $missing_keys ) ) {
		echo "  ✓ {$slug} status has all required keys\n";
	} else {
		echo "  ✗ {$slug} status missing keys: " . implode( ', ', $missing_keys ) . "\n";
		exit( 1 );
	}
}

// Test 8: Verify provider ordering works
echo "\nTest 8: Verifying provider ordering...\n";
$all_providers = [ 'gemini', 'openai', 'anthropic', 'imagen', 'dalle', 'deepseek', 'glm', 'qwen' ];
$found_all = true;

foreach ( $all_providers as $slug ) {
	if ( ! isset( $statuses[ $slug ] ) ) {
		echo "  ✗ Missing provider: {$slug}\n";
		$found_all = false;
	}
}

if ( $found_all ) {
	echo "  ✓ All providers present in status list\n";
} else {
	exit( 1 );
}

// Test 9: Verify provider labels are correct
echo "\nTest 9: Verifying provider labels...\n";
$expected_labels = [
	'deepseek' => 'DeepSeek',
	'glm'      => 'Zhipu AI GLM',
	'qwen'     => 'Alibaba Qwen',
];

foreach ( $expected_labels as $slug => $expected_label ) {
	$actual_label = $statuses[ $slug ]['label'];
	if ( $actual_label === $expected_label ) {
		echo "  ✓ {$slug} has correct label '{$expected_label}'\n";
	} else {
		echo "  ✗ {$slug} has incorrect label '{$actual_label}' (expected '{$expected_label}')\n";
		exit( 1 );
	}
}

// Test 10: Verify capability flags
echo "\nTest 10: Verifying capability flags in status...\n";
foreach ( $required_providers as $slug ) {
	$status = $statuses[ $slug ];
	if ( $status['supports_text'] && $status['supports_image'] ) {
		echo "  ✓ {$slug} status shows text and image support\n";
	} else {
		echo "  ✗ {$slug} status has incorrect capabilities\n";
		exit( 1 );
	}
}

echo "\n=== All Checkpoint 8 Verification Tests Passed! ===\n";
echo "\nSummary:\n";
echo "  • All three new provider classes load correctly\n";
echo "  • All providers can be instantiated with API keys\n";
echo "  • Provider slugs and labels are correct\n";
echo "  • All providers support text and image generation\n";
echo "  • Provider Manager correctly integrates new providers\n";
echo "  • Provider status endpoint returns correct data\n";
echo "  • Provider ordering includes all providers\n";
echo "\n";

exit( 0 );
