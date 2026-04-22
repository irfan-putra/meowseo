<?php
/**
 * GitHub Update Settings Page View
 *
 * Displays the settings page for configuring and managing the GitHub
 * auto-update system.
 *
 * @package MeowSEO
 * @subpackage Updater
 * @since 1.0.0
 *
 * Variables available in this template:
 * - $current_version: Current installed version with commit ID
 * - $latest_version: Latest available version from GitHub
 * - $update_available: Boolean indicating if update is available
 * - $last_check_time: Timestamp of last update check
 * - $next_check_time: Estimated time of next update check
 * - $rate_limit: Array with rate limit information (limit, remaining, reset)
 * - $config: Update_Config instance
 * - $nonce: Security nonce for form submission
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'GitHub Updates', 'meowseo' ); ?></h1>
	<p><?php esc_html_e( 'Configure automatic updates from the GitHub repository.', 'meowseo' ); ?></p>

	<!-- Status Section -->
	<div class="meowseo-update-status">
		<h2><?php esc_html_e( 'Update Status', 'meowseo' ); ?></h2>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Current Version', 'meowseo' ); ?></label>
					</th>
					<td>
						<code><?php echo esc_html( $current_version ); ?></code>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Latest Version', 'meowseo' ); ?></label>
					</th>
					<td>
						<code><?php echo esc_html( $latest_version ); ?></code>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Update Available', 'meowseo' ); ?></label>
					</th>
					<td>
						<?php if ( $update_available ) : ?>
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
							<strong><?php esc_html_e( 'Yes', 'meowseo' ); ?></strong>
						<?php else : ?>
							<span class="dashicons dashicons-no" style="color: #dc3545;"></span>
							<strong><?php esc_html_e( 'No', 'meowseo' ); ?></strong>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Last Check Time', 'meowseo' ); ?></label>
					</th>
					<td>
						<?php if ( $last_check_time > 0 ) : ?>
							<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $last_check_time ) ); ?>
						<?php else : ?>
							<em><?php esc_html_e( 'Never checked', 'meowseo' ); ?></em>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Next Check Time', 'meowseo' ); ?></label>
					</th>
					<td>
						<?php if ( $next_check_time > 0 ) : ?>
							<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $next_check_time ) ); ?>
						<?php else : ?>
							<em><?php esc_html_e( 'Not scheduled', 'meowseo' ); ?></em>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'GitHub Rate Limit', 'meowseo' ); ?></label>
					</th>
					<td>
						<?php if ( ! empty( $rate_limit ) ) : ?>
							<strong><?php echo esc_html( $rate_limit['remaining'] ); ?></strong> / <?php echo esc_html( $rate_limit['limit'] ); ?> requests remaining
							<br>
							<small>
								<?php
								$reset_time = wp_date( 'Y-m-d H:i:s', $rate_limit['reset'] );
								printf(
									/* translators: %s: Reset time */
									esc_html__( 'Resets at: %s', 'meowseo' ),
									esc_html( $reset_time )
								);
								?>
							</small>
						<?php else : ?>
							<em><?php esc_html_e( 'Rate limit information not available', 'meowseo' ); ?></em>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- Action Buttons -->
		<div class="meowseo-update-actions">
			<form method="post" style="display: inline;">
				<?php wp_nonce_field( 'meowseo_check_update_now', 'meowseo_check_nonce' ); ?>
				<input type="hidden" name="action" value="check_update_now">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Check for Updates Now', 'meowseo' ); ?>
				</button>
			</form>

			<form method="post" style="display: inline;">
				<?php wp_nonce_field( 'meowseo_clear_cache', 'meowseo_cache_nonce' ); ?>
				<input type="hidden" name="action" value="clear_cache">
				<button type="submit" class="button">
					<?php esc_html_e( 'Clear Cache', 'meowseo' ); ?>
				</button>
			</form>
		</div>
	</div>

	<hr>

	<!-- Configuration Form Section -->
	<div class="meowseo-update-config">
		<h2><?php esc_html_e( 'Configuration', 'meowseo' ); ?></h2>

		<form method="post" action="">
			<?php wp_nonce_field( 'meowseo_update_settings', 'meowseo_settings_nonce' ); ?>

			<table class="form-table">
				<tbody>
					<!-- Repository Owner (Read-only) -->
					<tr>
						<th scope="row">
							<label for="repo_owner"><?php esc_html_e( 'Repository Owner', 'meowseo' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="repo_owner"
								name="repo_owner"
								value="<?php echo esc_attr( $config->get_repo_owner() ); ?>"
								class="regular-text"
								readonly
								disabled
							>
							<p class="description">
								<?php esc_html_e( 'This value is hardcoded and cannot be changed.', 'meowseo' ); ?>
							</p>
						</td>
					</tr>

					<!-- Repository Name (Read-only) -->
					<tr>
						<th scope="row">
							<label for="repo_name"><?php esc_html_e( 'Repository Name', 'meowseo' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="repo_name"
								name="repo_name"
								value="<?php echo esc_attr( $config->get_repo_name() ); ?>"
								class="regular-text"
								readonly
								disabled
							>
							<p class="description">
								<?php esc_html_e( 'This value is hardcoded and cannot be changed.', 'meowseo' ); ?>
							</p>
						</td>
					</tr>

					<!-- Branch Selection -->
					<tr>
						<th scope="row">
							<label for="branch"><?php esc_html_e( 'Branch to Track', 'meowseo' ); ?></label>
						</th>
						<td>
							<select id="branch" name="branch" class="regular-text">
								<option value="main" <?php selected( $config->get_branch(), 'main' ); ?>>
									<?php esc_html_e( 'main', 'meowseo' ); ?>
								</option>
								<option value="master" <?php selected( $config->get_branch(), 'master' ); ?>>
									<?php esc_html_e( 'master', 'meowseo' ); ?>
								</option>
								<option value="develop" <?php selected( $config->get_branch(), 'develop' ); ?>>
									<?php esc_html_e( 'develop', 'meowseo' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Select which branch to check for updates.', 'meowseo' ); ?>
							</p>
						</td>
					</tr>

					<!-- Auto-Update Enabled -->
					<tr>
						<th scope="row">
							<label for="auto_update_enabled"><?php esc_html_e( 'Enable Automatic Updates', 'meowseo' ); ?></label>
						</th>
						<td>
							<input
								type="checkbox"
								id="auto_update_enabled"
								name="auto_update_enabled"
								value="1"
								<?php checked( $config->is_auto_update_enabled(), true ); ?>
							>
							<label for="auto_update_enabled">
								<?php esc_html_e( 'Automatically check for updates every 12 hours', 'meowseo' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, the plugin will check for updates automatically. You can still manually check for updates using the button above.', 'meowseo' ); ?>
							</p>
						</td>
					</tr>

					<!-- Check Frequency -->
					<tr>
						<th scope="row">
							<label for="check_frequency"><?php esc_html_e( 'Check Frequency', 'meowseo' ); ?></label>
						</th>
						<td>
							<select id="check_frequency" name="check_frequency" class="regular-text">
								<option value="3600" <?php selected( $config->get_check_frequency(), 3600 ); ?>>
									<?php esc_html_e( 'Every 1 hour', 'meowseo' ); ?>
								</option>
								<option value="21600" <?php selected( $config->get_check_frequency(), 21600 ); ?>>
									<?php esc_html_e( 'Every 6 hours', 'meowseo' ); ?>
								</option>
								<option value="43200" <?php selected( $config->get_check_frequency(), 43200 ); ?>>
									<?php esc_html_e( 'Every 12 hours', 'meowseo' ); ?>
								</option>
								<option value="86400" <?php selected( $config->get_check_frequency(), 86400 ); ?>>
									<?php esc_html_e( 'Every 24 hours', 'meowseo' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'How often to check for updates. Note: GitHub API has a rate limit of 60 requests per hour.', 'meowseo' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary" name="submit">
					<?php esc_html_e( 'Save Settings', 'meowseo' ); ?>
				</button>
			</p>
		</form>
	</div>
</div>

<style>
	.meowseo-update-status,
	.meowseo-update-config,
	.meowseo-update-logs {
		margin: 20px 0;
	}

	.meowseo-update-actions {
		margin: 15px 0;
	}

	.meowseo-update-actions form {
		margin-right: 10px;
	}

	.meowseo-update-logs table {
		margin-top: 10px;
	}

	.meowseo-update-logs code {
		background-color: #f5f5f5;
		padding: 2px 4px;
		border-radius: 3px;
		font-size: 12px;
	}
</style>
