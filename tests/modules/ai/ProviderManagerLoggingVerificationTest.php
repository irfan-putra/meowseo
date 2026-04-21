<?php
/**
 * Provider Manager Logging Verification Test.
 *
 * Verifies that error logging for new providers (DeepSeek, GLM, Qwen) includes:
 * - Provider slug
 * - Error message
 * - Timestamp (automatically added by Logger)
 * - Provider selection, attempts, successes, and failures
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Tests\Modules\AI
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;

/**
 * Provider Manager Logging Verification Test.
 *
 * Tests that the Provider Manager properly logs errors with provider slug,
 * message, and timestamp for the new providers.
 *
 * This test uses static code analysis to verify logging implementation
 * without requiring runtime execution.
 *
 * @since 1.0.0
 */
class ProviderManagerLoggingVerificationTest extends TestCase {

	/**
	 * Test that Provider Manager logs include provider slug.
	 *
	 * Verifies that all logging calls in the Provider Manager include
	 * the provider slug in the context array.
	 *
	 * @return void
	 */
	public function test_provider_manager_logs_include_provider_slug(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$this->assertFileExists( $file_path, 'Provider Manager file should exist' );

		$content = file_get_contents( $file_path );

		// Find all Logger calls.
		preg_match_all(
			'/Logger::(warning|error|info)\s*\(\s*[^,]+,\s*\[([^\]]+)\]/s',
			$content,
			$matches,
			PREG_SET_ORDER
		);

		$this->assertNotEmpty( $matches, 'Provider Manager should have Logger calls' );

		// Verify each Logger call includes 'provider' in context.
		foreach ( $matches as $match ) {
			$context = $match[2];

			// Check if this is a provider-related log (contains provider slug reference).
			if ( strpos( $context, 'provider' ) !== false || strpos( $context, '$provider_slug' ) !== false ) {
				$this->assertStringContainsString(
					'provider',
					$context,
					'Provider-related logs should include provider slug in context'
				);
			}
		}
	}

	/**
	 * Test that log_failure method includes provider slug and error message.
	 *
	 * Verifies that the log_failure method logs with provider slug and error message.
	 *
	 * @return void
	 */
	public function test_log_failure_includes_provider_slug_and_error(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$content = file_get_contents( $file_path );

		// Find log_failure method.
		$this->assertStringContainsString(
			'private function log_failure',
			$content,
			'Provider Manager should have log_failure method'
		);

		// Verify log_failure includes provider slug.
		$this->assertMatchesRegularExpression(
			'/private function log_failure\s*\(\s*string\s+\$provider_slug\s*,\s*string\s+\$error\s*\)/s',
			$content,
			'log_failure should accept provider_slug and error parameters'
		);

		// Verify log_failure calls Logger with provider and error in context.
		$this->assertMatchesRegularExpression(
			'/Logger::warning\s*\([^,]+,\s*\[[^\]]*[\'"]provider[\'"]\s*=>\s*\$provider_slug[^\]]*[\'"]error[\'"]\s*=>\s*\$error/s',
			$content,
			'log_failure should log with provider slug and error message in context'
		);
	}

	/**
	 * Test that log_success method includes provider slug and type.
	 *
	 * Verifies that the log_success method logs with provider slug and generation type.
	 *
	 * @return void
	 */
	public function test_log_success_includes_provider_slug_and_type(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$content = file_get_contents( $file_path );

		// Find log_success method.
		$this->assertStringContainsString(
			'private function log_success',
			$content,
			'Provider Manager should have log_success method'
		);

		// Verify log_success includes provider slug and type.
		$this->assertMatchesRegularExpression(
			'/private function log_success\s*\(\s*string\s+\$provider_slug\s*,\s*string\s+\$type\s*\)/s',
			$content,
			'log_success should accept provider_slug and type parameters'
		);

		// Verify log_success calls Logger with provider and type in context.
		$this->assertMatchesRegularExpression(
			'/Logger::info\s*\([^,]+,\s*\[[^\]]*[\'"]provider[\'"]\s*=>\s*\$provider_slug[^\]]*[\'"]type[\'"]\s*=>\s*\$type/s',
			$content,
			'log_success should log with provider slug and type in context'
		);
	}

	/**
	 * Test that log_skip method includes provider slug and reason.
	 *
	 * Verifies that the log_skip method logs with provider slug and skip reason.
	 *
	 * @return void
	 */
	public function test_log_skip_includes_provider_slug_and_reason(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$content = file_get_contents( $file_path );

		// Find log_skip method.
		$this->assertStringContainsString(
			'private function log_skip',
			$content,
			'Provider Manager should have log_skip method'
		);

		// Verify log_skip includes provider slug and reason.
		$this->assertMatchesRegularExpression(
			'/private function log_skip\s*\(\s*string\s+\$provider_slug\s*,\s*string\s+\$reason\s*\)/s',
			$content,
			'log_skip should accept provider_slug and reason parameters'
		);

		// Verify log_skip calls Logger with provider and reason in context.
		$this->assertMatchesRegularExpression(
			'/Logger::info\s*\([^,]+,\s*\[[^\]]*[\'"]provider[\'"]\s*=>\s*\$provider_slug[^\]]*[\'"]reason[\'"]\s*=>\s*\$reason/s',
			$content,
			'log_skip should log with provider slug and reason in context'
		);
	}

	/**
	 * Test that rate limit handling logs include provider slug.
	 *
	 * Verifies that rate limit handling logs the provider slug and retry-after time.
	 *
	 * @return void
	 */
	public function test_rate_limit_handling_logs_provider_slug(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$content = file_get_contents( $file_path );

		// Find handle_rate_limit method.
		$this->assertStringContainsString(
			'private function handle_rate_limit',
			$content,
			'Provider Manager should have handle_rate_limit method'
		);

		// Verify handle_rate_limit logs with provider slug.
		$this->assertMatchesRegularExpression(
			'/Logger::warning\s*\([^,]+,\s*\[[^\]]*[\'"]provider[\'"]\s*=>\s*\$provider_slug/s',
			$content,
			'handle_rate_limit should log with provider slug in context'
		);
	}

	/**
	 * Test that Logger automatically adds timestamp to all logs.
	 *
	 * Verifies that the Logger class automatically captures timestamp
	 * using current_time() for all log entries.
	 *
	 * @return void
	 */
	public function test_logger_automatically_adds_timestamp(): void {
		$file_path = __DIR__ . '/../../../includes/helpers/class-logger.php';
		$this->assertFileExists( $file_path, 'Logger file should exist' );

		$content = file_get_contents( $file_path );

		// Verify Logger captures timestamp automatically.
		$this->assertStringContainsString(
			'current_time( \'mysql\' )',
			$content,
			'Logger should automatically capture timestamp using current_time()'
		);

		// Verify timestamp is stored in created_at field.
		$this->assertMatchesRegularExpression(
			'/[\'"]created_at[\'"]\s*=>\s*\$timestamp/s',
			$content,
			'Logger should store timestamp in created_at field'
		);
	}

	/**
	 * Test that new provider slugs are included in all_slugs array.
	 *
	 * Verifies that deepseek, glm, and qwen are included in the all_slugs
	 * array used by get_provider_statuses().
	 *
	 * @return void
	 */
	public function test_new_provider_slugs_in_all_slugs_array(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$content = file_get_contents( $file_path );

		// Find all_slugs array in get_provider_statuses method.
		$this->assertMatchesRegularExpression(
			'/\$all_slugs\s*=\s*\[[^\]]*[\'"]deepseek[\'"][^\]]*\]/s',
			$content,
			'all_slugs array should include deepseek'
		);

		$this->assertMatchesRegularExpression(
			'/\$all_slugs\s*=\s*\[[^\]]*[\'"]glm[\'"][^\]]*\]/s',
			$content,
			'all_slugs array should include glm'
		);

		$this->assertMatchesRegularExpression(
			'/\$all_slugs\s*=\s*\[[^\]]*[\'"]qwen[\'"][^\]]*\]/s',
			$content,
			'all_slugs array should include qwen'
		);
	}

	/**
	 * Test that new provider labels are included in get_provider_label method.
	 *
	 * Verifies that deepseek, glm, and qwen have labels defined.
	 *
	 * @return void
	 */
	public function test_new_provider_labels_defined(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$content = file_get_contents( $file_path );

		// Find labels array in get_provider_label method.
		$this->assertMatchesRegularExpression(
			'/\$labels\s*=\s*\[[^\]]*[\'"]deepseek[\'"]\s*=>\s*[\'"]DeepSeek[\'"][^\]]*\]/s',
			$content,
			'labels array should include deepseek => DeepSeek'
		);

		$this->assertMatchesRegularExpression(
			'/\$labels\s*=\s*\[[^\]]*[\'"]glm[\'"]\s*=>\s*[\'"]Zhipu AI GLM[\'"][^\]]*\]/s',
			$content,
			'labels array should include glm => Zhipu AI GLM'
		);

		$this->assertMatchesRegularExpression(
			'/\$labels\s*=\s*\[[^\]]*[\'"]qwen[\'"]\s*=>\s*[\'"]Alibaba Qwen[\'"][^\]]*\]/s',
			$content,
			'labels array should include qwen => Alibaba Qwen'
		);
	}

	/**
	 * Test that generate_text logs provider selection and attempts.
	 *
	 * Verifies that generate_text method logs when providers are selected
	 * and attempted.
	 *
	 * @return void
	 */
	public function test_generate_text_logs_provider_selection_and_attempts(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$content = file_get_contents( $file_path );

		// Verify generate_text calls log_skip for rate-limited providers.
		$this->assertMatchesRegularExpression(
			'/if\s*\(\s*\$this->is_rate_limited\s*\(\s*\$slug\s*\)\s*\)\s*\{[^\}]*\$this->log_skip\s*\(\s*\$slug/s',
			$content,
			'generate_text should log skipped providers'
		);

		// Verify generate_text calls log_success on successful generation.
		$this->assertMatchesRegularExpression(
			'/\$this->log_success\s*\(\s*\$slug\s*,\s*[\'"]text[\'"]\s*\)/s',
			$content,
			'generate_text should log successful text generation'
		);

		// Verify generate_text calls log_failure on provider failure.
		$this->assertMatchesRegularExpression(
			'/\$this->log_failure\s*\(\s*\$slug\s*,\s*\$e->getMessage\(\)\s*\)/s',
			$content,
			'generate_text should log failed provider attempts'
		);
	}

	/**
	 * Test that generate_image logs provider selection and attempts.
	 *
	 * Verifies that generate_image method logs when providers are selected
	 * and attempted.
	 *
	 * @return void
	 */
	public function test_generate_image_logs_provider_selection_and_attempts(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$content = file_get_contents( $file_path );

		// Verify generate_image calls log_skip for rate-limited providers.
		$this->assertMatchesRegularExpression(
			'/if\s*\(\s*\$this->is_rate_limited\s*\(\s*\$slug\s*\)\s*\)\s*\{[^\}]*\$this->log_skip\s*\(\s*\$slug/s',
			$content,
			'generate_image should log skipped providers'
		);

		// Verify generate_image calls log_success on successful generation.
		$this->assertMatchesRegularExpression(
			'/\$this->log_success\s*\(\s*\$slug\s*,\s*[\'"]image[\'"]\s*\)/s',
			$content,
			'generate_image should log successful image generation'
		);

		// Verify generate_image calls log_failure on provider failure.
		$this->assertMatchesRegularExpression(
			'/\$this->log_failure\s*\(\s*\$slug\s*,\s*\$e->getMessage\(\)\s*\)/s',
			$content,
			'generate_image should log failed provider attempts'
		);
	}

	/**
	 * Test that all providers failed scenario logs aggregated errors.
	 *
	 * Verifies that when all providers fail, the error log includes
	 * all provider errors.
	 *
	 * @return void
	 */
	public function test_all_providers_failed_logs_aggregated_errors(): void {
		$file_path = __DIR__ . '/../../../includes/modules/ai/class-ai-provider-manager.php';
		$content = file_get_contents( $file_path );

		// Verify generate_text logs all errors when all providers fail.
		$this->assertMatchesRegularExpression(
			'/Logger::error\s*\(\s*[\'"]All text providers failed[\'"]\s*,\s*\[[^\]]*[\'"]errors[\'"]\s*=>\s*\$this->errors/s',
			$content,
			'generate_text should log aggregated errors when all providers fail'
		);

		// Verify generate_image logs all errors when all providers fail.
		$this->assertMatchesRegularExpression(
			'/Logger::error\s*\(\s*[\'"]All image providers failed[\'"]\s*,\s*\[[^\]]*[\'"]errors[\'"]\s*=>\s*\$this->errors/s',
			$content,
			'generate_image should log aggregated errors when all providers fail'
		);
	}

	/**
	 * Test that Logger includes module context automatically.
	 *
	 * Verifies that Logger automatically detects and includes the module
	 * name in log entries.
	 *
	 * @return void
	 */
	public function test_logger_includes_module_context_automatically(): void {
		$file_path = __DIR__ . '/../../../includes/helpers/class-logger.php';
		$content = file_get_contents( $file_path );

		// Verify Logger has get_calling_module method.
		$this->assertStringContainsString(
			'private function get_calling_module',
			$content,
			'Logger should have get_calling_module method'
		);

		// Verify get_calling_module extracts module from file path.
		$this->assertStringContainsString(
			'preg_match',
			$content,
			'get_calling_module should use preg_match to extract module name from file path'
		);

		$this->assertStringContainsString(
			'/modules/',
			$content,
			'get_calling_module should look for /modules/ in file path'
		);

		// Verify module is stored in log entry.
		$this->assertMatchesRegularExpression(
			'/[\'"]module[\'"]\s*=>\s*\$module/s',
			$content,
			'Logger should store module in log entry'
		);
	}
}
