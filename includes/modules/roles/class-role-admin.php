<?php
/**
 * Role Admin class for managing role capabilities in WordPress admin.
 *
 * Provides admin interface for role capability management.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Roles;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role Admin class.
 *
 * Manages admin interface for role capability management.
 */
class Role_Admin {

	/**
	 * Role Manager instance.
	 *
	 * @var Role_Manager
	 */
	private Role_Manager $role_manager;

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Role_Manager $role_manager Role Manager instance.
	 * @param Options      $options      Options instance.
	 */
	public function __construct( Role_Manager $role_manager, Options $options ) {
		$this->role_manager = $role_manager;
		$this->options      = $options;
	}

	/**
	 * Boot the admin interface.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register admin menu.
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );

		// Register AJAX handlers.
		add_action( 'wp_ajax_meowseo_add_capability', array( $this, 'handle_add_capability' ) );
		add_action( 'wp_ajax_meowseo_remove_capability', array( $this, 'handle_remove_capability' ) );

		// Enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * Adds role management page under MeowSEO menu.
	 * Validates: Requirement 1.4
	 *
	 * @return void
	 */
	public function register_menu(): void {
		// Check if user has permission to manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_submenu_page(
			'meowseo',
			__( 'Role Manager', 'meowseo' ),
			__( 'Role Manager', 'meowseo' ),
			'manage_options',
			'meowseo-roles',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render role management page.
	 *
	 * Displays capability matrix (roles × capabilities).
	 * Validates: Requirement 1.4
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Check permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'meowseo' ) );
		}

		// Get all WordPress roles.
		$roles = wp_roles()->roles;

		// Get all MeowSEO capabilities.
		$capabilities = $this->role_manager->get_all_meowseo_capabilities();

		// Get current role capabilities.
		$role_capabilities = array();
		foreach ( array_keys( $roles ) as $role_name ) {
			$role_capabilities[ $role_name ] = $this->role_manager->get_role_capabilities( $role_name );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MeowSEO Role Manager', 'meowseo' ); ?></h1>
			<p><?php esc_html_e( 'Manage which user roles can access specific MeowSEO features.', 'meowseo' ); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Role', 'meowseo' ); ?></th>
						<?php foreach ( $capabilities as $capability ) : ?>
							<th title="<?php echo esc_attr( $capability ); ?>">
								<?php echo esc_html( $this->format_capability_name( $capability ) ); ?>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $roles as $role_name => $role_data ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $role_data['name'] ); ?></strong></td>
							<?php foreach ( $capabilities as $capability ) : ?>
								<td class="meowseo-capability-cell">
									<input
										type="checkbox"
										class="meowseo-capability-checkbox"
										data-role="<?php echo esc_attr( $role_name ); ?>"
										data-capability="<?php echo esc_attr( $capability ); ?>"
										<?php checked( in_array( $capability, $role_capabilities[ $role_name ], true ) ); ?>
									/>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<style>
				.meowseo-capability-cell {
					text-align: center;
				}
				.meowseo-capability-checkbox {
					cursor: pointer;
				}
			</style>

			<script>
				(function() {
					const checkboxes = document.querySelectorAll('.meowseo-capability-checkbox');
					checkboxes.forEach(checkbox => {
						checkbox.addEventListener('change', function() {
							const role = this.dataset.role;
							const capability = this.dataset.capability;
							const action = this.checked ? 'meowseo_add_capability' : 'meowseo_remove_capability';

							fetch(ajaxurl, {
								method: 'POST',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded',
								},
								body: new URLSearchParams({
									action: action,
									role: role,
									capability: capability,
									nonce: '<?php echo esc_js( wp_create_nonce( 'meowseo_role_nonce' ) ); ?>',
								}),
							})
							.then(response => response.json())
							.then(data => {
								if (!data.success) {
									this.checked = !this.checked;
									alert('<?php esc_html_e( 'Failed to update capability.', 'meowseo' ); ?>');
								}
							})
							.catch(error => {
								this.checked = !this.checked;
								console.error('Error:', error);
							});
						});
					});
				})();
			</script>
		</div>
		<?php
	}

	/**
	 * Handle add capability AJAX request.
	 *
	 * Validates: Requirement 1.7
	 *
	 * @return void
	 */
	public function handle_add_capability(): void {
		// Check permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'meowseo_role_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		// Get role and capability.
		$role       = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
		$capability = isset( $_POST['capability'] ) ? sanitize_text_field( wp_unslash( $_POST['capability'] ) ) : '';

		// Validate inputs.
		if ( empty( $role ) || empty( $capability ) ) {
			wp_send_json_error( array( 'message' => 'Missing parameters' ) );
		}

		// Add capability.
		$result = $this->role_manager->add_capability_to_role( $role, $capability );

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Capability added' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to add capability' ) );
		}
	}

	/**
	 * Handle remove capability AJAX request.
	 *
	 * Validates: Requirement 1.7
	 *
	 * @return void
	 */
	public function handle_remove_capability(): void {
		// Check permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'meowseo_role_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		// Get role and capability.
		$role       = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
		$capability = isset( $_POST['capability'] ) ? sanitize_text_field( wp_unslash( $_POST['capability'] ) ) : '';

		// Validate inputs.
		if ( empty( $role ) || empty( $capability ) ) {
			wp_send_json_error( array( 'message' => 'Missing parameters' ) );
		}

		// Remove capability.
		$result = $this->role_manager->remove_capability_from_role( $role, $capability );

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Capability removed' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to remove capability' ) );
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Only enqueue on role management page.
		$screen = get_current_screen();
		if ( ! $screen || 'meowseo_page_meowseo-roles' !== $screen->id ) {
			return;
		}

		// Scripts are inline in render_page().
	}

	/**
	 * Format capability name for display.
	 *
	 * Converts capability name to human-readable format.
	 *
	 * @param string $capability Capability name.
	 * @return string Formatted capability name.
	 */
	private function format_capability_name( string $capability ): string {
		// Remove 'meowseo_' prefix.
		$name = str_replace( 'meowseo_', '', $capability );

		// Replace underscores with spaces.
		$name = str_replace( '_', ' ', $name );

		// Capitalize words.
		$name = ucwords( $name );

		return $name;
	}
}
