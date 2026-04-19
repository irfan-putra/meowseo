<?php
/**
 * Cornerstone List Table Integration
 *
 * Adds cornerstone column and filter to post list tables.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Cornerstone;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * List Table Integration class
 *
 * Handles cornerstone column and filter in post list tables.
 * Requirements: 6.4, 6.5, 6.6, 6.7, 6.8
 *
 * @since 1.0.0
 */
class List_Table_Integration {

	/**
	 * Cornerstone Manager instance
	 *
	 * @var Cornerstone_Manager
	 */
	private Cornerstone_Manager $manager;

	/**
	 * Constructor
	 *
	 * @param Cornerstone_Manager $manager Cornerstone Manager instance.
	 */
	public function __construct( Cornerstone_Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Get all public post types
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {
			// Add cornerstone column
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_cornerstone_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_cornerstone_column' ), 10, 2 );

			// Make column sortable
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'register_sortable_column' ) );
		}

		// Add filter dropdown
		add_action( 'restrict_manage_posts', array( $this, 'add_cornerstone_filter' ) );

		// Apply filter
		add_filter( 'pre_get_posts', array( $this, 'filter_by_cornerstone' ) );

		// Handle sorting
		add_filter( 'posts_clauses', array( $this, 'handle_cornerstone_sorting' ), 10, 2 );
	}

	/**
	 * Add cornerstone column to post list table
	 *
	 * Requirement: 6.4, 6.7
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_cornerstone_column( array $columns ): array {
		// Insert cornerstone column after title
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' === $key ) {
				$new_columns['meowseo_cornerstone'] = '<span class="dashicons dashicons-star-filled" title="' . esc_attr__( 'Cornerstone Content', 'meowseo' ) . '"></span>';
			}
		}

		return $new_columns;
	}

	/**
	 * Render cornerstone column content
	 *
	 * Requirement: 6.4, 6.7
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_cornerstone_column( string $column, int $post_id ): void {
		if ( 'meowseo_cornerstone' !== $column ) {
			return;
		}

		if ( $this->manager->is_cornerstone( $post_id ) ) {
			echo '<span class="dashicons dashicons-star-filled" style="color: #f0b849;" title="' . esc_attr__( 'Cornerstone Content', 'meowseo' ) . '"></span>';
		} else {
			echo '—';
		}
	}

	/**
	 * Register cornerstone column as sortable
	 *
	 * Requirement: 6.8
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function register_sortable_column( array $columns ): array {
		$columns['meowseo_cornerstone'] = 'meowseo_cornerstone';
		return $columns;
	}

	/**
	 * Add cornerstone filter dropdown
	 *
	 * Requirement: 6.5, 6.6
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public function add_cornerstone_filter( string $post_type ): void {
		// Only show on public post types
		$post_type_obj = get_post_type_object( $post_type );
		if ( ! $post_type_obj || ! $post_type_obj->public ) {
			return;
		}

		$current_filter = isset( $_GET['meowseo_cornerstone_filter'] ) ? sanitize_text_field( $_GET['meowseo_cornerstone_filter'] ) : '';

		?>
		<select name="meowseo_cornerstone_filter">
			<option value=""><?php esc_html_e( 'All Posts', 'meowseo' ); ?></option>
			<option value="cornerstone" <?php selected( $current_filter, 'cornerstone' ); ?>>
				<?php esc_html_e( 'Cornerstone Only', 'meowseo' ); ?>
			</option>
			<option value="non_cornerstone" <?php selected( $current_filter, 'non_cornerstone' ); ?>>
				<?php esc_html_e( 'Non-Cornerstone', 'meowseo' ); ?>
			</option>
		</select>
		<?php
	}

	/**
	 * Filter posts by cornerstone status
	 *
	 * Requirement: 6.6
	 *
	 * @param \WP_Query $query Query object.
	 * @return void
	 */
	public function filter_by_cornerstone( \WP_Query $query ): void {
		// Only apply in admin post list
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Check if filter is set
		if ( ! isset( $_GET['meowseo_cornerstone_filter'] ) ) {
			return;
		}

		$filter = sanitize_text_field( $_GET['meowseo_cornerstone_filter'] );

		if ( 'cornerstone' === $filter ) {
			// Show only cornerstone posts
			$meta_query = $query->get( 'meta_query' ) ?: array();
			$meta_query[] = array(
				'key'   => $this->manager->get_meta_key(),
				'value' => '1',
			);
			$query->set( 'meta_query', $meta_query );
		} elseif ( 'non_cornerstone' === $filter ) {
			// Show only non-cornerstone posts
			$meta_query = $query->get( 'meta_query' ) ?: array();
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => $this->manager->get_meta_key(),
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => $this->manager->get_meta_key(),
					'value'   => '1',
					'compare' => '!=',
				),
			);
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Handle cornerstone column sorting
	 *
	 * Requirement: 6.8
	 *
	 * @param array     $clauses  Query clauses.
	 * @param \WP_Query $query    Query object.
	 * @return array Modified clauses.
	 */
	public function handle_cornerstone_sorting( array $clauses, \WP_Query $query ): array {
		global $wpdb;

		// Only apply in admin post list
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $clauses;
		}

		// Check if sorting by cornerstone
		$orderby = $query->get( 'orderby' );
		if ( 'meowseo_cornerstone' !== $orderby ) {
			return $clauses;
		}

		$order = $query->get( 'order' ) ?: 'ASC';
		$order = strtoupper( $order );

		// Join with postmeta table
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS cornerstone_meta ON {$wpdb->posts}.ID = cornerstone_meta.post_id AND cornerstone_meta.meta_key = '{$this->manager->get_meta_key()}'";

		// Order by meta value
		$clauses['orderby'] = "cornerstone_meta.meta_value {$order}, {$wpdb->posts}.post_title ASC";

		// Ensure distinct results
		$clauses['distinct'] = 'DISTINCT';

		return $clauses;
	}
}
