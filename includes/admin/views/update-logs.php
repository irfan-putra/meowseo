<?php
/**
 * GitHub Update Logs View
 *
 * Displays recent update logs in a table format with expandable details.
 *
 * @package MeowSEO
 * @subpackage Updater
 * @since 1.0.0
 *
 * Variables available in this template:
 * - $logs: Array of recent log entries
 * - $clear_logs_nonce: Security nonce for clear logs action
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="meowseo-update-logs">
	<h2><?php esc_html_e( 'Recent Activity', 'meowseo' ); ?></h2>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Timestamp', 'meowseo' ); ?></th>
				<th><?php esc_html_e( 'Level', 'meowseo' ); ?></th>
				<th><?php esc_html_e( 'Type', 'meowseo' ); ?></th>
				<th><?php esc_html_e( 'Message', 'meowseo' ); ?></th>
				<th><?php esc_html_e( 'Details', 'meowseo' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $logs ) ) : ?>
				<?php foreach ( $logs as $index => $log ) : ?>
					<?php
					// Generate unique ID for expandable row.
					$row_id = 'meowseo-log-' . $index;
					$has_context = ! empty( $log['context'] );
					?>
					<tr>
						<td>
							<code><?php echo esc_html( $log['timestamp'] ); ?></code>
						</td>
						<td>
							<?php
							// Determine level icon and color.
							$level_class = 'info' === $log['level'] ? 'dashicons-yes' : 'dashicons-warning';
							$level_color = 'info' === $log['level'] ? '#46b450' : '#dc3545';
							?>
							<span class="dashicons <?php echo esc_attr( $level_class ); ?>" style="color: <?php echo esc_attr( $level_color ); ?>;"></span>
							<?php echo esc_html( strtoupper( $log['level'] ) ); ?>
						</td>
						<td>
							<code><?php echo esc_html( $log['type'] ); ?></code>
						</td>
						<td>
							<?php echo esc_html( $log['message'] ); ?>
						</td>
						<td>
							<?php if ( $has_context ) : ?>
								<button
									type="button"
									class="button button-small meowseo-toggle-details"
									data-target="<?php echo esc_attr( $row_id ); ?>"
									aria-expanded="false"
									aria-controls="<?php echo esc_attr( $row_id ); ?>"
								>
									<?php esc_html_e( 'Show', 'meowseo' ); ?>
								</button>
							<?php else : ?>
								<em><?php esc_html_e( 'None', 'meowseo' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>

					<?php if ( $has_context ) : ?>
						<tr id="<?php echo esc_attr( $row_id ); ?>" class="meowseo-log-details" style="display: none;">
							<td colspan="5">
								<div style="background-color: #f5f5f5; padding: 10px; border-radius: 3px; margin: 5px 0;">
									<strong><?php esc_html_e( 'Context Data:', 'meowseo' ); ?></strong>
									<pre style="margin: 5px 0; overflow-x: auto; background-color: #fff; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
<?php
// Format context data for display.
$context_json = wp_json_encode( $log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
echo esc_html( $context_json );
?>
									</pre>
								</div>
							</td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="5">
						<em><?php esc_html_e( 'No activity logged yet.', 'meowseo' ); ?></em>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Clear Old Logs Button -->
	<div style="margin-top: 15px;">
		<form method="post" style="display: inline;">
			<?php wp_nonce_field( 'meowseo_clear_old_logs', 'meowseo_clear_logs_nonce' ); ?>
			<input type="hidden" name="action" value="clear_old_logs">
			<button type="submit" class="button">
				<?php esc_html_e( 'Clear Old Logs (30+ days)', 'meowseo' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Remove log entries older than 30 days to keep the database clean.', 'meowseo' ); ?>
			</p>
		</form>
	</div>
</div>

<style>
	.meowseo-update-logs {
		margin: 20px 0;
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

	.meowseo-toggle-details {
		cursor: pointer;
	}

	.meowseo-log-details {
		background-color: #f9f9f9;
	}

	.meowseo-log-details pre {
		font-family: 'Courier New', Courier, monospace;
		white-space: pre-wrap;
		word-wrap: break-word;
	}
</style>

<script>
	(function() {
		// Handle expandable details toggle.
		document.querySelectorAll('.meowseo-toggle-details').forEach(function(button) {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const target = document.getElementById(this.getAttribute('data-target'));
				const isExpanded = this.getAttribute('aria-expanded') === 'true';

				if (target) {
					if (isExpanded) {
						target.style.display = 'none';
						this.setAttribute('aria-expanded', 'false');
						this.textContent = '<?php esc_html_e( 'Show', 'meowseo' ); ?>';
					} else {
						target.style.display = 'table-row';
						this.setAttribute('aria-expanded', 'true');
						this.textContent = '<?php esc_html_e( 'Hide', 'meowseo' ); ?>';
					}
				}
			});
		});
	})();
</script>
