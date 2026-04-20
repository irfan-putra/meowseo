<?php
/**
 * Admin Bar Module class for displaying SEO score in WordPress admin bar.
 *
 * Displays SEO score, readability score, and other metrics in the WordPress admin bar
 * on the frontend for quick assessment without opening the editor.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\AdminBar;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin_Bar_Module class.
 *
 * Manages SEO score display in WordPress admin bar.
 */
class Admin_Bar_Module implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Transient cache duration in seconds (5 minutes).
	 *
	 * @var int
	 */
	private const CACHE_DURATION = 300;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module.
	 *
	 * Validates: Requirements 7.1, 7.6, 7.7, 7.8
	 *
	 * @return void
	 */
	public function boot(): void {
		// Only hook on frontend, not in admin.
		if ( is_admin() ) {
			return;
		}

		// Hook into admin bar menu.
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 100 );
	}

	/**
	 * Get Role Manager instance.
	 *
	 * @return \MeowSEO\Modules\Roles\Role_Manager|null
	 */
	private function get_role_manager(): ?\MeowSEO\Modules\Roles\Role_Manager {
		$plugin = \MeowSEO\Plugin::instance();
		$module_manager = $plugin->get_module_manager();

		if ( ! $module_manager ) {
			return null;
		}

		return $module_manager->get_module( 'roles' );
	}

	/**
	 * Get Analysis Engine instance.
	 *
	 * @return \MeowSEO\Modules\Analysis\Analysis_Engine|null
	 */
	private function get_analysis_engine(): ?\MeowSEO\Modules\Analysis\Analysis_Engine {
		// Create Analysis Engine on demand.
		static $analysis_engine = null;

		if ( null === $analysis_engine ) {
			try {
				$fix_provider = new \MeowSEO\Modules\Analysis\Fix_Explanation_Provider();
				$analysis_engine = new \MeowSEO\Modules\Analysis\Analysis_Engine( $fix_provider );
			} catch ( \Exception $e ) {
				return null;
			}
		}

		return $analysis_engine;
	}

	/**
	 * Get module name.
	 *
	 * @return string Module name.
	 */
	public function get_name(): string {
		return 'Admin Bar Module';
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'admin-bar';
	}

	/**
	 * Get module version.
	 *
	 * @return string Module version.
	 */
	public function get_version(): string {
		return '1.0.0';
	}

	/**
	 * Check if module is enabled.
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		return true;
	}

	/**
	 * Add MeowSEO menu item to WordPress admin bar.
	 *
	 * Validates: Requirements 7.1, 7.2, 7.3
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress admin bar instance.
	 * @return void
	 */
	public function add_admin_bar_menu( \WP_Admin_Bar $wp_admin_bar ): void {
		// Get Role Manager.
		$role_manager = $this->get_role_manager();

		if ( ! $role_manager ) {
			return;
		}

		// Check capability.
		if ( ! $role_manager->user_can( 'meowseo_view_admin_bar' ) ) {
			return;
		}

		// Only display on singular posts/pages.
		if ( ! is_singular() ) {
			return;
		}

		// Get current post.
		$post = get_queried_object();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// Get SEO score.
		$score_data = $this->get_current_page_score();

		if ( empty( $score_data ) ) {
			return;
		}

		$seo_score = $score_data['seo_score'] ?? 0;
		$color = $this->get_score_color( $seo_score );
		$focus_keyword = $score_data['focus_keyword'] ?? '';

		// Build menu item title with color indicator.
		$title = sprintf(
			'<span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%%; background-color: %s; margin-right: 8px;"></span>MeowSEO %d%%',
			esc_attr( $color ),
			intval( $seo_score )
		);

		if ( ! empty( $focus_keyword ) ) {
			$title .= sprintf( ' - %s', esc_html( $focus_keyword ) );
		}

		// Add main menu item.
		$wp_admin_bar->add_menu(
			array(
				'id'    => 'meowseo-admin-bar',
				'title' => $title,
				'href'  => '#',
				'meta'  => array(
					'class' => 'meowseo-admin-bar-menu',
				),
			)
		);

		// Add dropdown content.
		$this->render_admin_bar_dropdown( $wp_admin_bar, $post, $score_data );
	}

	/**
	 * Get current page SEO score and related data.
	 *
	 * Validates: Requirements 7.2, 7.4, 7.6, 7.7
	 *
	 * @return array {
	 *     Score data.
	 *
	 *     @type int    $seo_score        SEO score (0-100).
	 *     @type int    $readability_score Readability score (0-100).
	 *     @type string $focus_keyword    Focus keyword.
	 *     @type int    $failing_checks   Number of failing checks.
	 * }
	 */
	public function get_current_page_score(): array {
		$post = get_queried_object();

		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		// Check cache first.
		$cache_key = 'meowseo_admin_bar_score_' . $post->ID;
		$cached_score = get_transient( $cache_key );

		if ( false !== $cached_score ) {
			return $cached_score;
		}

		// Get Analysis Engine.
		$analysis_engine = $this->get_analysis_engine();

		if ( ! $analysis_engine ) {
			return array();
		}

		// Get post metadata.
		$title = get_post_meta( $post->ID, '_meowseo_title', true );
		$description = get_post_meta( $post->ID, '_meowseo_description', true );
		$focus_keyword = get_post_meta( $post->ID, '_meowseo_focus_keyword', true );

		// Use post title if SEO title not set.
		if ( empty( $title ) ) {
			$title = $post->post_title;
		}

		// Use post excerpt if description not set.
		if ( empty( $description ) ) {
			$description = $post->post_excerpt;
		}

		// Get post content.
		$content = $post->post_content;

		// Get post slug.
		$slug = $post->post_name;

		// Run analysis.
		try {
			$analysis_result = $analysis_engine->analyze(
				$post->ID,
				array(
					'title' => $title,
					'description' => $description,
					'content' => $content,
					'slug' => $slug,
					'focus_keyword' => $focus_keyword,
				)
			);
		} catch ( \Exception $e ) {
			// If analysis fails, return empty array.
			return array();
		}

		// Count failing checks.
		$seo_results = $analysis_result['seo_results'] ?? array();
		$failing_checks = 0;

		foreach ( $seo_results as $check ) {
			if ( ! $check['pass'] ) {
				$failing_checks++;
			}
		}

		// Build score data.
		$score_data = array(
			'seo_score' => $analysis_result['seo_score'] ?? 0,
			'readability_score' => $analysis_result['readability_score'] ?? 0,
			'focus_keyword' => $focus_keyword,
			'failing_checks' => $failing_checks,
		);

		// Cache the score.
		set_transient( $cache_key, $score_data, self::CACHE_DURATION );

		return $score_data;
	}

	/**
	 * Render admin bar dropdown content.
	 *
	 * Validates: Requirements 7.3, 7.4, 7.5
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress admin bar instance.
	 * @param \WP_Post      $post         Current post object.
	 * @param array         $score_data   Score data from get_current_page_score().
	 * @return void
	 */
	private function render_admin_bar_dropdown( \WP_Admin_Bar $wp_admin_bar, \WP_Post $post, array $score_data ): void {
		$seo_score = $score_data['seo_score'] ?? 0;
		$readability_score = $score_data['readability_score'] ?? 0;
		$focus_keyword = $score_data['focus_keyword'] ?? '';
		$failing_checks = $score_data['failing_checks'] ?? 0;

		// Build dropdown HTML.
		$dropdown_html = '<div class="meowseo-admin-bar-dropdown" style="padding: 10px; min-width: 250px;">';

		// SEO Score.
		$seo_color = $this->get_score_color( $seo_score );
		$dropdown_html .= sprintf(
			'<div style="margin-bottom: 10px;"><strong>SEO Score:</strong> <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%%; background-color: %s; margin-right: 5px;"></span>%d%%</div>',
			esc_attr( $seo_color ),
			intval( $seo_score )
		);

		// Readability Score.
		$readability_color = $this->get_score_color( $readability_score );
		$dropdown_html .= sprintf(
			'<div style="margin-bottom: 10px;"><strong>Readability:</strong> <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%%; background-color: %s; margin-right: 5px;"></span>%d%%</div>',
			esc_attr( $readability_color ),
			intval( $readability_score )
		);

		// Focus Keyword.
		if ( ! empty( $focus_keyword ) ) {
			$dropdown_html .= sprintf(
				'<div style="margin-bottom: 10px;"><strong>Focus Keyword:</strong> %s</div>',
				esc_html( $focus_keyword )
			);
		}

		// Failing Checks.
		$dropdown_html .= sprintf(
			'<div style="margin-bottom: 10px;"><strong>Failing Checks:</strong> %d</div>',
			intval( $failing_checks )
		);

		// Edit SEO Link.
		$edit_url = add_query_arg(
			array(
				'post' => $post->ID,
				'action' => 'edit',
				'meowseo_sidebar' => '1',
			),
			admin_url( 'post.php' )
		);

		$dropdown_html .= sprintf(
			'<div style="margin-top: 15px;"><a href="%s" class="button button-primary" style="width: 100%%; text-align: center;">Edit SEO</a></div>',
			esc_url( $edit_url )
		);

		$dropdown_html .= '</div>';

		// Add dropdown as child menu item.
		$wp_admin_bar->add_menu(
			array(
				'parent' => 'meowseo-admin-bar',
				'id'     => 'meowseo-admin-bar-dropdown',
				'title'  => $dropdown_html,
				'href'   => false,
				'meta'   => array(
					'html' => true,
				),
			)
		);
	}

	/**
	 * Get color for score.
	 *
	 * Validates: Requirement 7.2
	 *
	 * Red: 0-49, Orange: 50-79, Green: 80-100
	 *
	 * @param int $score Score value (0-100).
	 * @return string Hex color code.
	 */
	private function get_score_color( int $score ): string {
		if ( $score < 50 ) {
			return '#dc3545'; // Red.
		} elseif ( $score < 80 ) {
			return '#fd7e14'; // Orange.
		} else {
			return '#28a745'; // Green.
		}
	}
}
