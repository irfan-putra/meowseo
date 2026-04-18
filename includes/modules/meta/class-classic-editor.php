<?php
/**
 * Classic Editor Meta Box
 *
 * Renders and saves MeowSEO fields in the WordPress Classic Editor.
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

/**
 * Class Classic_Editor
 *
 * Adds a MeowSEO meta box to the classic post editor.
 */
class Classic_Editor {

	const NONCE_ACTION = 'meowseo_classic_editor_save';
	const NONCE_FIELD  = 'meowseo_classic_editor_nonce';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
	}

	/**
	 * Register the meta box for all public post types.
	 */
	public function register_meta_box(): void {
		$post_types = get_post_types( array( 'public' => true ) );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'meowseo-meta-box',
				'MeowSEO',
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the meta box HTML.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$title         = get_post_meta( $post->ID, '_meowseo_title', true );
		$description   = get_post_meta( $post->ID, '_meowseo_description', true );
		$focus_keyword = get_post_meta( $post->ID, '_meowseo_focus_keyword', true );
		$canonical     = get_post_meta( $post->ID, '_meowseo_canonical', true );
		$noindex       = (bool) get_post_meta( $post->ID, '_meowseo_robots_noindex', true );
		$nofollow      = (bool) get_post_meta( $post->ID, '_meowseo_robots_nofollow', true );
		?>
		<style>
			#meowseo-meta-box .meowseo-field { margin-bottom: 12px; }
			#meowseo-meta-box .meowseo-field label { display: block; font-weight: 600; margin-bottom: 4px; }
			#meowseo-meta-box .meowseo-field input[type="text"],
			#meowseo-meta-box .meowseo-field textarea { width: 100%; box-sizing: border-box; }
			#meowseo-meta-box .meowseo-field textarea { height: 80px; resize: vertical; }
			#meowseo-meta-box .meowseo-robots { display: flex; gap: 20px; }
			#meowseo-meta-box .meowseo-robots label { font-weight: normal; display: flex; align-items: center; gap: 6px; }
		</style>
		<div class="meowseo-field">
			<label for="meowseo_title"><?php esc_html_e( 'SEO Title', 'meowseo' ); ?></label>
			<input type="text" id="meowseo_title" name="meowseo_title"
				value="<?php echo esc_attr( $title ); ?>"
				placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>" />
		</div>
		<div class="meowseo-field">
			<label for="meowseo_description"><?php esc_html_e( 'Meta Description', 'meowseo' ); ?></label>
			<textarea id="meowseo_description" name="meowseo_description"
				placeholder="<?php esc_attr_e( 'Write a short description of this page...', 'meowseo' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
		</div>
		<div class="meowseo-field">
			<label for="meowseo_focus_keyword"><?php esc_html_e( 'Focus Keyword', 'meowseo' ); ?></label>
			<input type="text" id="meowseo_focus_keyword" name="meowseo_focus_keyword"
				value="<?php echo esc_attr( $focus_keyword ); ?>" />
		</div>
		<div class="meowseo-field">
			<label for="meowseo_canonical"><?php esc_html_e( 'Canonical URL', 'meowseo' ); ?></label>
			<input type="text" id="meowseo_canonical" name="meowseo_canonical"
				value="<?php echo esc_attr( $canonical ); ?>"
				placeholder="<?php echo esc_attr( get_permalink( $post ) ); ?>" />
		</div>
		<div class="meowseo-field">
			<span><?php esc_html_e( 'Robots', 'meowseo' ); ?></span>
			<div class="meowseo-robots">
				<label>
					<input type="checkbox" name="meowseo_robots_noindex" value="1" <?php checked( $noindex ); ?> />
					<?php esc_html_e( 'No Index', 'meowseo' ); ?>
				</label>
				<label>
					<input type="checkbox" name="meowseo_robots_nofollow" value="1" <?php checked( $nofollow ); ?> />
					<?php esc_html_e( 'No Follow', 'meowseo' ); ?>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data on post save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		// Skip autosaves and revisions.
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$string_fields = array(
			'meowseo_title'         => '_meowseo_title',
			'meowseo_description'   => '_meowseo_description',
			'meowseo_focus_keyword' => '_meowseo_focus_keyword',
		);

		foreach ( $string_fields as $post_key => $meta_key ) {
			$value = isset( $_POST[ $post_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : '';
			update_post_meta( $post_id, $meta_key, $value );
		}

		$canonical = isset( $_POST['meowseo_canonical'] ) ? esc_url_raw( wp_unslash( $_POST['meowseo_canonical'] ) ) : '';
		update_post_meta( $post_id, '_meowseo_canonical', $canonical );

		update_post_meta( $post_id, '_meowseo_robots_noindex', isset( $_POST['meowseo_robots_noindex'] ) ? 1 : 0 );
		update_post_meta( $post_id, '_meowseo_robots_nofollow', isset( $_POST['meowseo_robots_nofollow'] ) ? 1 : 0 );
	}
}
