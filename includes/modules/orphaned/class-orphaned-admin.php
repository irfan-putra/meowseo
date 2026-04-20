<?php
/**
 * Orphaned Admin class.
 *
 * Manages admin interface for orphaned content detection.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Orphaned;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orphaned Admin class.
 *
 * Provides admin interface for viewing and managing orphaned content.
 *
 * Validates: Requirements 8.4, 8.5, 8.9
 */
class Orphaned_Admin {

	/**
	 * Orphaned Detector instance.
	 *
	 * @var Orphaned_Detector
	 */
	private Orphaned_Detector $detector;

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Orphaned_Detector $detector Orphaned Detector instance.
	 * @param Options           $options  Options instance.
	 */
	public function __construct( Orphaned_Detector $detector, Options $options ) {
		$this->detector = $detector;
		$this->options  = $options;
	}

	/**
	 * Boot the admin interface.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register admin menu.
		add_action( 'admin_menu', array( $this, 'register_menu' ), 25 );

		// Register dashboard widget.
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );

		// Enqueue admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * Adds orphaned content page under MeowSEO menu.
	 * Requirement 8.4: THE Orphaned_Detector SHALL provide an admin page
	 *
	 * @return void
	 */
	public function register_menu(): void {
		// Check if user has permission.
		if ( ! current_user_can( 'meowseo_view_link_suggestions' ) ) {
			return;
		}

		add_submenu_page(
			'meowseo',
			__( 'Orphaned Content', 'meowseo' ),
			__( 'Orphaned Content', 'meowseo' ),
			'meowseo_view_link_suggestions',
			'meowseo-orphaned',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render orphaned content admin page.
	 *
	 * Displays list of orphaned posts with filters.
	 * Requirement 8.4: Display list with title, URL, publish date
	 * Requirement 8.5: Allow filtering by post type and date range
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Check permission.
		if ( ! current_user_can( 'meowseo_view_link_suggestions' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'meowseo' ) );
		}

		// Get filter values from request.
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
		$paged = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;

		// Build filters array.
		$filters = array(
			'limit'  => 50,
			'offset' => ( $paged - 1 ) * 50,
		);

		if ( ! empty( $post_type ) ) {
			$filters['post_type'] = array( $post_type );
		}

		if ( ! empty( $date_from ) ) {
			$filters['date_from'] = $date_from . ' 00:00:00';
		}

		if ( ! empty( $date_to ) ) {
			$filters['date_to'] = $date_to . ' 23:59:59';
		}

		// Get orphaned posts.
		$orphaned_posts = $this->detector->get_orphaned_posts( $filters );
		$orphaned_count = $this->detector->get_orphaned_count();

		// Get post types for filter dropdown.
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Orphaned Content', 'meowseo' ); ?></h1>
			<p><?php esc_html_e( 'Posts and pages with no internal links from other content.', 'meowseo' ); ?></p>

			<div class="meowseo-orphaned-stats">
				<div class="stat-box">
					<div class="stat-value"><?php echo absint( $orphaned_count ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Orphaned Posts', 'meowseo' ); ?></div>
				</div>
			</div>

			<form method="get" class="meowseo-orphaned-filters">
				<input type="hidden" name="page" value="meowseo-orphaned" />

				<div class="filter-group">
					<label for="post_type"><?php esc_html_e( 'Post Type:', 'meowseo' ); ?></label>
					<select name="post_type" id="post_type">
						<option value=""><?php esc_html_e( 'All Types', 'meowseo' ); ?></option>
						<?php foreach ( $post_types as $type ) : ?>
							<option value="<?php echo esc_attr( $type->name ); ?>" <?php selected( $post_type, $type->name ); ?>>
								<?php echo esc_html( $type->label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="filter-group">
					<label for="date_from"><?php esc_html_e( 'From:', 'meowseo' ); ?></label>
					<input type="date" name="date_from" id="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
				</div>

				<div class="filter-group">
					<label for="date_to"><?php esc_html_e( 'To:', 'meowseo' ); ?></label>
					<input type="date" name="date_to" id="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
				</div>

				<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'meowseo' ); ?></button>
			</form>

			<?php if ( empty( $orphaned_posts ) ) : ?>
				<div class="notice notice-success inline">
					<p><?php esc_html_e( 'No orphaned content found!', 'meowseo' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Type', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'URL', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Published', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Last Scanned', 'meowseo' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'meowseo' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $orphaned_posts as $post ) : ?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo esc_url( get_edit_post_link( $post['ID'] ) ); ?>">
											<?php echo esc_html( $post['post_title'] ); ?>
										</a>
									</strong>
								</td>
								<td><?php echo esc_html( $post['post_type'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( get_permalink( $post['ID'] ) ); ?>" target="_blank">
										<?php echo esc_html( wp_parse_url( get_permalink( $post['ID'] ), PHP_URL_PATH ) ); ?>
									</a>
								</td>
								<td><?php echo esc_html( wp_date( 'Y-m-d', strtotime( $post['post_date'] ) ) ); ?></td>
								<td><?php echo esc_html( wp_date( 'Y-m-d H:i', strtotime( $post['last_scanned'] ) ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( 'orphaned_id', $post['ID'], admin_url( 'admin.php?page=meowseo-orphaned&action=suggest' ) ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Suggest Links', 'meowseo' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php
				// Pagination.
				$total_orphaned = $this->detector->get_orphaned_count();
				$total_pages = ceil( $total_orphaned / 50 );

				if ( $total_pages > 1 ) {
					echo '<div class="tablenav bottom">';
					echo '<div class="tablenav-pages">';
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => __( '&laquo;' ),
								'next_text' => __( '&raquo;' ),
								'total'     => $total_pages,
								'current'   => $paged,
								'type'      => 'list',
							)
						)
					);
					echo '</div>';
					echo '</div>';
				}
				?>
			<?php endif; ?>
		</div>

		<style>
			.meowseo-orphaned-stats {
				display: flex;
				gap: 20px;
				margin: 20px 0;
			}

			.stat-box {
				background: #fff;
				border: 1px solid #ccc;
				border-radius: 4px;
				padding: 20px;
				text-align: center;
				min-width: 150px;
			}

			.stat-value {
				font-size: 32px;
				font-weight: bold;
				color: #0073aa;
			}

			.stat-label {
				color: #666;
				margin-top: 5px;
			}

			.meowseo-orphaned-filters {
				background: #fff;
				border: 1px solid #ccc;
				border-radius: 4px;
				padding: 15px;
				margin: 20px 0;
				display: flex;
				gap: 15px;
				align-items: flex-end;
				flex-wrap: wrap;
			}

			.filter-group {
				display: flex;
				flex-direction: column;
				gap: 5px;
			}

			.filter-group label {
				font-weight: bold;
				font-size: 13px;
			}

			.filter-group select,
			.filter-group input {
				padding: 5px 8px;
				border: 1px solid #ddd;
				border-radius: 3px;
			}
		</style>
		<?php
	}

	/**
	 * Register dashboard widget.
	 *
	 * Requirement 8.9: THE Orphaned_Detector SHALL display a dashboard widget
	 *
	 * @return void
	 */
	public function register_dashboard_widget(): void {
		// Check if user has permission.
		if ( ! current_user_can( 'meowseo_view_link_suggestions' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'meowseo_orphaned_widget',
			__( 'MeowSEO Orphaned Content', 'meowseo' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		$orphaned_count = $this->detector->get_orphaned_count();

		?>
		<div class="meowseo-widget-content">
			<div class="meowseo-widget-stat">
				<div class="meowseo-widget-number"><?php echo absint( $orphaned_count ); ?></div>
				<div class="meowseo-widget-label"><?php esc_html_e( 'Orphaned Posts', 'meowseo' ); ?></div>
			</div>

			<?php if ( $orphaned_count > 0 ) : ?>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=meowseo-orphaned' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'View Orphaned Content', 'meowseo' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'Great! No orphaned content detected.', 'meowseo' ); ?></p>
			<?php endif; ?>
		</div>

		<style>
			.meowseo-widget-content {
				text-align: center;
				padding: 20px 0;
			}

			.meowseo-widget-stat {
				margin: 20px 0;
			}

			.meowseo-widget-number {
				font-size: 48px;
				font-weight: bold;
				color: #0073aa;
			}

			.meowseo-widget-label {
				color: #666;
				margin-top: 10px;
				font-size: 14px;
			}
		</style>
		<?php
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'meowseo_page_meowseo-orphaned' !== $screen->id ) {
			return;
		}

		// Styles are inline in render_page().
	}
}
