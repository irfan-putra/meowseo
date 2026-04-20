<?php
/**
 * Location Custom Post Type
 *
 * Manages the meowseo_location custom post type for multi-location businesses.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\Locations;

use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Location_CPT class
 *
 * Registers and manages the meowseo_location custom post type with custom fields
 * for business details, GPS coordinates, and opening hours.
 * Requirements 4.1, 4.2: Register CPT with custom fields for business details and coordinates.
 *
 * @since 1.0.0
 */
class Location_CPT {

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module
	 *
	 * Registers hooks and initializes the module.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'save_post_meowseo_location', array( $this, 'validate_and_save_location' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_meowseo_location', array( $this, 'save_location_meta' ), 10, 2 );
	}

	/**
	 * Register the meowseo_location custom post type
	 *
	 * Requirements 4.1, 4.2: Register CPT with appropriate labels and capabilities.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_cpt(): void {
		$labels = array(
			'name'               => _x( 'Locations', 'post type general name', 'meowseo' ),
			'singular_name'      => _x( 'Location', 'post type singular name', 'meowseo' ),
			'menu_name'          => _x( 'Locations', 'admin menu', 'meowseo' ),
			'name_admin_bar'     => _x( 'Location', 'add new on admin bar', 'meowseo' ),
			'add_new'            => _x( 'Add New', 'location', 'meowseo' ),
			'add_new_item'       => __( 'Add New Location', 'meowseo' ),
			'new_item'           => __( 'New Location', 'meowseo' ),
			'edit_item'          => __( 'Edit Location', 'meowseo' ),
			'view_item'          => __( 'View Location', 'meowseo' ),
			'all_items'          => __( 'All Locations', 'meowseo' ),
			'search_items'       => __( 'Search Locations', 'meowseo' ),
			'parent_item_colon'  => __( 'Parent Locations:', 'meowseo' ),
			'not_found'          => __( 'No locations found.', 'meowseo' ),
			'not_found_in_trash' => __( 'No locations found in Trash.', 'meowseo' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Business locations for local SEO', 'meowseo' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'meowseo',
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'location' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'capabilities'       => array(
				'create_posts'       => 'meowseo_manage_locations',
				'edit_posts'         => 'meowseo_manage_locations',
				'edit_others_posts'  => 'meowseo_manage_locations',
				'delete_posts'       => 'meowseo_manage_locations',
				'delete_others_posts' => 'meowseo_manage_locations',
				'publish_posts'      => 'meowseo_manage_locations',
				'read_private_posts' => 'meowseo_manage_locations',
			),
		);

		register_post_type( 'meowseo_location', $args );
	}

	/**
	 * Add meta boxes for location custom fields
	 *
	 * Requirements 4.1, 4.2: Add custom fields for business details and GPS coordinates.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'meowseo_location_business_details',
			__( 'Business Details', 'meowseo' ),
			array( $this, 'render_business_details_meta_box' ),
			'meowseo_location',
			'normal',
			'high'
		);

		add_meta_box(
			'meowseo_location_coordinates',
			__( 'GPS Coordinates', 'meowseo' ),
			array( $this, 'render_coordinates_meta_box' ),
			'meowseo_location',
			'normal',
			'high'
		);

		add_meta_box(
			'meowseo_location_opening_hours',
			__( 'Opening Hours', 'meowseo' ),
			array( $this, 'render_opening_hours_meta_box' ),
			'meowseo_location',
			'normal',
			'high'
		);
	}

	/**
	 * Render business details meta box
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_business_details_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'meowseo_location_nonce', 'meowseo_location_nonce' );

		$business_name = get_post_meta( $post->ID, '_meowseo_business_name', true );
		$street_address = get_post_meta( $post->ID, '_meowseo_street_address', true );
		$city = get_post_meta( $post->ID, '_meowseo_city', true );
		$state = get_post_meta( $post->ID, '_meowseo_state', true );
		$postal_code = get_post_meta( $post->ID, '_meowseo_postal_code', true );
		$country = get_post_meta( $post->ID, '_meowseo_country', true );
		$phone = get_post_meta( $post->ID, '_meowseo_phone', true );
		$email = get_post_meta( $post->ID, '_meowseo_email', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="meowseo_business_name"><?php esc_html_e( 'Business Name', 'meowseo' ); ?></label></th>
				<td><input type="text" id="meowseo_business_name" name="meowseo_business_name" value="<?php echo esc_attr( $business_name ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="meowseo_street_address"><?php esc_html_e( 'Street Address', 'meowseo' ); ?></label></th>
				<td><input type="text" id="meowseo_street_address" name="meowseo_street_address" value="<?php echo esc_attr( $street_address ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="meowseo_city"><?php esc_html_e( 'City', 'meowseo' ); ?></label></th>
				<td><input type="text" id="meowseo_city" name="meowseo_city" value="<?php echo esc_attr( $city ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="meowseo_state"><?php esc_html_e( 'State/Province', 'meowseo' ); ?></label></th>
				<td><input type="text" id="meowseo_state" name="meowseo_state" value="<?php echo esc_attr( $state ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="meowseo_postal_code"><?php esc_html_e( 'Postal Code', 'meowseo' ); ?></label></th>
				<td><input type="text" id="meowseo_postal_code" name="meowseo_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="meowseo_country"><?php esc_html_e( 'Country', 'meowseo' ); ?></label></th>
				<td><input type="text" id="meowseo_country" name="meowseo_country" value="<?php echo esc_attr( $country ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="meowseo_phone"><?php esc_html_e( 'Phone', 'meowseo' ); ?></label></th>
				<td><input type="tel" id="meowseo_phone" name="meowseo_phone" value="<?php echo esc_attr( $phone ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="meowseo_email"><?php esc_html_e( 'Email', 'meowseo' ); ?></label></th>
				<td><input type="email" id="meowseo_email" name="meowseo_email" value="<?php echo esc_attr( $email ); ?>" class="widefat" /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render coordinates meta box
	 *
	 * Requirements 4.2: Add custom fields for GPS coordinates.
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_coordinates_meta_box( \WP_Post $post ): void {
		$latitude = get_post_meta( $post->ID, '_meowseo_latitude', true );
		$longitude = get_post_meta( $post->ID, '_meowseo_longitude', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="meowseo_latitude"><?php esc_html_e( 'Latitude (-90 to 90)', 'meowseo' ); ?></label></th>
				<td><input type="number" id="meowseo_latitude" name="meowseo_latitude" value="<?php echo esc_attr( $latitude ); ?>" step="0.000001" min="-90" max="90" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="meowseo_longitude"><?php esc_html_e( 'Longitude (-180 to 180)', 'meowseo' ); ?></label></th>
				<td><input type="number" id="meowseo_longitude" name="meowseo_longitude" value="<?php echo esc_attr( $longitude ); ?>" step="0.000001" min="-180" max="180" class="widefat" /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render opening hours meta box
	 *
	 * Requirements 4.2: Add custom field for opening hours (JSON array).
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_opening_hours_meta_box( \WP_Post $post ): void {
		$opening_hours = get_post_meta( $post->ID, '_meowseo_opening_hours', true );
		if ( is_array( $opening_hours ) ) {
			$opening_hours = wp_json_encode( $opening_hours );
		}
		?>
		<p><?php esc_html_e( 'Enter opening hours as JSON array. Example:', 'meowseo' ); ?></p>
		<pre>[{"day":"Monday","open":"09:00","close":"17:00"},{"day":"Tuesday","open":"09:00","close":"17:00"}]</pre>
		<textarea id="meowseo_opening_hours" name="meowseo_opening_hours" class="widefat" rows="10"><?php echo esc_textarea( $opening_hours ); ?></textarea>
		<?php
	}

	/**
	 * Validate and save location
	 *
	 * Requirements 4.3: Validate coordinates on post save.
	 *
	 * @since 1.0.0
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function validate_and_save_location( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST['meowseo_location_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meowseo_location_nonce'] ) ), 'meowseo_location_nonce' ) ) {
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'meowseo_manage_locations' ) ) {
			return;
		}

		// Validate coordinates.
		$latitude = isset( $_POST['meowseo_latitude'] ) ? floatval( $_POST['meowseo_latitude'] ) : null;
		$longitude = isset( $_POST['meowseo_longitude'] ) ? floatval( $_POST['meowseo_longitude'] ) : null;

		if ( $latitude !== null || $longitude !== null ) {
			$validator = new Location_Validator();
			$errors = $validator->validate_coordinates( $latitude, $longitude );

			if ( ! empty( $errors ) ) {
				// Store validation errors in transient for display.
				set_transient( 'meowseo_location_validation_errors_' . $post_id, $errors, 30 );
				// Prevent post update if validation fails.
				wp_die( esc_html( implode( ', ', $errors ) ) );
			}
		}
	}

	/**
	 * Save location meta
	 *
	 * @since 1.0.0
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function save_location_meta( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST['meowseo_location_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meowseo_location_nonce'] ) ), 'meowseo_location_nonce' ) ) {
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'meowseo_manage_locations' ) ) {
			return;
		}

		// Save business details.
		if ( isset( $_POST['meowseo_business_name'] ) ) {
			update_post_meta( $post_id, '_meowseo_business_name', sanitize_text_field( wp_unslash( $_POST['meowseo_business_name'] ) ) );
		}
		if ( isset( $_POST['meowseo_street_address'] ) ) {
			update_post_meta( $post_id, '_meowseo_street_address', sanitize_text_field( wp_unslash( $_POST['meowseo_street_address'] ) ) );
		}
		if ( isset( $_POST['meowseo_city'] ) ) {
			update_post_meta( $post_id, '_meowseo_city', sanitize_text_field( wp_unslash( $_POST['meowseo_city'] ) ) );
		}
		if ( isset( $_POST['meowseo_state'] ) ) {
			update_post_meta( $post_id, '_meowseo_state', sanitize_text_field( wp_unslash( $_POST['meowseo_state'] ) ) );
		}
		if ( isset( $_POST['meowseo_postal_code'] ) ) {
			update_post_meta( $post_id, '_meowseo_postal_code', sanitize_text_field( wp_unslash( $_POST['meowseo_postal_code'] ) ) );
		}
		if ( isset( $_POST['meowseo_country'] ) ) {
			update_post_meta( $post_id, '_meowseo_country', sanitize_text_field( wp_unslash( $_POST['meowseo_country'] ) ) );
		}
		if ( isset( $_POST['meowseo_phone'] ) ) {
			update_post_meta( $post_id, '_meowseo_phone', sanitize_text_field( wp_unslash( $_POST['meowseo_phone'] ) ) );
		}
		if ( isset( $_POST['meowseo_email'] ) ) {
			update_post_meta( $post_id, '_meowseo_email', sanitize_email( wp_unslash( $_POST['meowseo_email'] ) ) );
		}

		// Save coordinates.
		if ( isset( $_POST['meowseo_latitude'] ) ) {
			update_post_meta( $post_id, '_meowseo_latitude', floatval( $_POST['meowseo_latitude'] ) );
		}
		if ( isset( $_POST['meowseo_longitude'] ) ) {
			update_post_meta( $post_id, '_meowseo_longitude', floatval( $_POST['meowseo_longitude'] ) );
		}

		// Save opening hours.
		if ( isset( $_POST['meowseo_opening_hours'] ) ) {
			$opening_hours = json_decode( wp_unslash( $_POST['meowseo_opening_hours'] ), true );
			if ( is_array( $opening_hours ) ) {
				update_post_meta( $post_id, '_meowseo_opening_hours', $opening_hours );
			}
		}
	}

	/**
	 * Get location data
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array Location data.
	 */
	public function get_location_data( int $post_id ): array {
		return array(
			'business_name'  => get_post_meta( $post_id, '_meowseo_business_name', true ),
			'street_address' => get_post_meta( $post_id, '_meowseo_street_address', true ),
			'city'           => get_post_meta( $post_id, '_meowseo_city', true ),
			'state'          => get_post_meta( $post_id, '_meowseo_state', true ),
			'postal_code'    => get_post_meta( $post_id, '_meowseo_postal_code', true ),
			'country'        => get_post_meta( $post_id, '_meowseo_country', true ),
			'phone'          => get_post_meta( $post_id, '_meowseo_phone', true ),
			'email'          => get_post_meta( $post_id, '_meowseo_email', true ),
			'latitude'       => floatval( get_post_meta( $post_id, '_meowseo_latitude', true ) ),
			'longitude'      => floatval( get_post_meta( $post_id, '_meowseo_longitude', true ) ),
			'opening_hours'  => get_post_meta( $post_id, '_meowseo_opening_hours', true ),
		);
	}

	/**
	 * Get all locations
	 *
	 * @since 1.0.0
	 * @return array Array of location posts.
	 */
	public function get_all_locations(): array {
		$args = array(
			'post_type'      => 'meowseo_location',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		return get_posts( $args );
	}
}
