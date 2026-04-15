<?php
/**
 * Breadcrumbs class for generating semantic breadcrumb trails.
 *
 * Generates breadcrumb trails for different page types (posts, pages, archives, search, 404).
 * Provides both array format for programmatic use and HTML output with Schema.org microdata.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Helpers;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Breadcrumbs class.
 *
 * Generates semantic breadcrumb trails with Schema.org microdata support.
 *
 * @since 1.0.0
 */
class Breadcrumbs {

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
	 * Get breadcrumb trail
	 *
	 * Returns the breadcrumb trail as an array of items with label and URL.
	 * Automatically detects the current page type and builds the appropriate trail.
	 *
	 * Requirement 8.1: THE Breadcrumbs SHALL provide get_trail() method returning array of items with label and URL
	 *
	 * @since 1.0.0
	 * @return array Array of breadcrumb items, each with 'label' and 'url' keys.
	 */
	public function get_trail(): array {
		$trail = array();

		// Determine page type and build appropriate trail.
		if ( is_404() ) {
			$trail = $this->build_trail_for_404();
		} elseif ( is_search() ) {
			$trail = $this->build_trail_for_search();
		} elseif ( is_archive() ) {
			$trail = $this->build_trail_for_archive();
		} elseif ( is_page() ) {
			$trail = $this->build_trail_for_page();
		} elseif ( is_single() ) {
			$trail = $this->build_trail_for_post();
		} else {
			// Default: just home.
			$trail = array(
				array(
					'label' => __( 'Home', 'meowseo' ),
					'url'   => home_url(),
				),
			);
		}

		/**
		 * Filter breadcrumb trail
		 *
		 * Allows customization of the breadcrumb trail array.
		 *
		 * @since 1.0.0
		 * @param array $trail Breadcrumb trail array.
		 */
		return apply_filters( 'meowseo_breadcrumb_trail', $trail );
	}

	/**
	 * Build trail for single post
	 *
	 * Generates breadcrumb trail: Home → Category → Post
	 *
	 * Requirement 8.2: THE Breadcrumbs SHALL generate correct trails for single posts with Home → Category → Post
	 * Requirement 13.1: Handle errors gracefully with logging.
	 *
	 * @since 1.0.0
	 * @return array Breadcrumb trail array.
	 */
	private function build_trail_for_post(): array {
		$trail = array();
		$post  = get_post();

		if ( ! $post ) {
			Logger::warning(
				'Failed to get post for breadcrumb trail',
				array(
					'context' => 'build_trail_for_post',
				)
			);
			return array(
				array(
					'label' => __( 'Home', 'meowseo' ),
					'url'   => home_url(),
				),
			);
		}

		// Add home.
		$trail[] = array(
			'label' => __( 'Home', 'meowseo' ),
			'url'   => home_url(),
		);

		// Add category if post has categories.
		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			$category = $categories[0]; // Use first category.
			$trail[]  = array(
				'label' => $category->name,
				'url'   => get_category_link( $category->term_id ),
			);
		}

		// Add post title.
		$trail[] = array(
			'label' => get_the_title( $post->ID ),
			'url'   => get_permalink( $post->ID ),
		);

		return $trail;
	}

	/**
	 * Build trail for hierarchical page
	 *
	 * Generates breadcrumb trail with hierarchical parents: Home → Parent → Child
	 *
	 * Requirement 8.3: THE Breadcrumbs SHALL generate correct trails for hierarchical pages with Home → Parent → Child
	 * Requirement 13.1: Handle invalid post hierarchy gracefully with logging.
	 *
	 * @since 1.0.0
	 * @return array Breadcrumb trail array.
	 */
	private function build_trail_for_page(): array {
		$trail = array();
		$post  = get_post();

		if ( ! $post ) {
			Logger::warning(
				'Failed to get post for breadcrumb trail',
				array(
					'context' => 'build_trail_for_page',
				)
			);
			return array(
				array(
					'label' => __( 'Home', 'meowseo' ),
					'url'   => home_url(),
				),
			);
		}

		// Add home.
		$trail[] = array(
			'label' => __( 'Home', 'meowseo' ),
			'url'   => home_url(),
		);

		// Get all ancestors with error handling (Requirement 13.1).
		$ancestors = get_post_ancestors( $post->ID );

		// Handle get_post_ancestors() errors.
		if ( is_wp_error( $ancestors ) ) {
			Logger::warning(
				'get_post_ancestors() returned error in breadcrumb generation',
				array(
					'post_id' => $post->ID,
					'error'   => $ancestors->get_error_message(),
				)
			);
			// Provide fallback trail without ancestors.
			$trail[] = array(
				'label' => get_the_title( $post->ID ),
				'url'   => get_permalink( $post->ID ),
			);
			return $trail;
		}

		// Reverse to get from root to current.
		$ancestors = array_reverse( $ancestors );

		// Add ancestors to trail.
		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_post( $ancestor_id );
			if ( $ancestor ) {
				$trail[] = array(
					'label' => get_the_title( $ancestor_id ),
					'url'   => get_permalink( $ancestor_id ),
				);
			} else {
				Logger::warning(
					'Failed to get ancestor post in breadcrumb trail',
					array(
						'post_id'     => $post->ID,
						'ancestor_id' => $ancestor_id,
					)
				);
			}
		}

		// Add current page.
		$trail[] = array(
			'label' => get_the_title( $post->ID ),
			'url'   => get_permalink( $post->ID ),
		);

		return $trail;
	}

	/**
	 * Build trail for archive page
	 *
	 * Generates breadcrumb trail for archive pages: Home → Archive
	 *
	 * Requirement 8.4: THE Breadcrumbs SHALL generate correct trails for archives with Home → Archive
	 *
	 * @since 1.0.0
	 * @return array Breadcrumb trail array.
	 */
	private function build_trail_for_archive(): array {
		$trail = array();

		// Add home.
		$trail[] = array(
			'label' => __( 'Home', 'meowseo' ),
			'url'   => home_url(),
		);

		// Determine archive type and add appropriate label.
		if ( is_category() ) {
			$term = get_queried_object();
			if ( $term ) {
				$trail[] = array(
					'label' => $term->name,
					'url'   => get_term_link( $term ),
				);
			}
		} elseif ( is_tag() ) {
			$term = get_queried_object();
			if ( $term ) {
				$trail[] = array(
					'label' => $term->name,
					'url'   => get_term_link( $term ),
				);
			}
		} elseif ( is_tax() ) {
			$term = get_queried_object();
			if ( $term ) {
				$trail[] = array(
					'label' => $term->name,
					'url'   => get_term_link( $term ),
				);
			}
		} elseif ( is_date() ) {
			if ( is_year() ) {
				$trail[] = array(
					'label' => get_the_time( 'Y' ),
					'url'   => get_year_link( get_the_time( 'Y' ) ),
				);
			} elseif ( is_month() ) {
				$trail[] = array(
					'label' => get_the_time( 'Y' ),
					'url'   => get_year_link( get_the_time( 'Y' ) ),
				);
				$trail[] = array(
					'label' => get_the_time( 'F' ),
					'url'   => get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ),
				);
			} elseif ( is_day() ) {
				$trail[] = array(
					'label' => get_the_time( 'Y' ),
					'url'   => get_year_link( get_the_time( 'Y' ) ),
				);
				$trail[] = array(
					'label' => get_the_time( 'F' ),
					'url'   => get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ),
				);
				$trail[] = array(
					'label' => get_the_time( 'd' ),
					'url'   => get_day_link( get_the_time( 'Y' ), get_the_time( 'm' ), get_the_time( 'd' ) ),
				);
			}
		} elseif ( is_author() ) {
			$author = get_queried_object();
			if ( $author ) {
				$trail[] = array(
					'label' => $author->display_name,
					'url'   => get_author_posts_url( $author->ID ),
				);
			}
		} elseif ( is_post_type_archive() ) {
			$post_type = get_queried_object();
			if ( $post_type ) {
				$trail[] = array(
					'label' => $post_type->label,
					'url'   => get_post_type_archive_link( $post_type->name ),
				);
			}
		}

		return $trail;
	}

	/**
	 * Build trail for search results
	 *
	 * Generates breadcrumb trail for search results: Home → Search Results
	 *
	 * Requirement 8.5: THE Breadcrumbs SHALL generate correct trails for search results with Home → Search Results
	 *
	 * @since 1.0.0
	 * @return array Breadcrumb trail array.
	 */
	private function build_trail_for_search(): array {
		$trail = array();

		// Add home.
		$trail[] = array(
			'label' => __( 'Home', 'meowseo' ),
			'url'   => home_url(),
		);

		// Add search results label.
		$search_query = get_search_query();
		$trail[]      = array(
			'label' => sprintf(
				/* translators: %s is the search query */
				__( 'Search Results for "%s"', 'meowseo' ),
				esc_html( $search_query )
			),
			'url'   => get_search_link(),
		);

		return $trail;
	}

	/**
	 * Build trail for 404 page
	 *
	 * Generates breadcrumb trail for 404 error pages: Home → Page Not Found
	 *
	 * Requirement 8.6: THE Breadcrumbs SHALL generate correct trails for 404 pages with Home → Page Not Found
	 *
	 * @since 1.0.0
	 * @return array Breadcrumb trail array.
	 */
	private function build_trail_for_404(): array {
		$trail = array();

		// Add home.
		$trail[] = array(
			'label' => __( 'Home', 'meowseo' ),
			'url'   => home_url(),
		);

		// Add 404 label.
		$trail[] = array(
			'label' => __( 'Page Not Found', 'meowseo' ),
			'url'   => '',
		);

		return $trail;
	}

	/**
	 * Render breadcrumbs as HTML
	 *
	 * Outputs semantic HTML5 nav with Schema.org microdata.
	 *
	 * Requirement 8.7: THE Breadcrumbs SHALL provide render() method outputting semantic HTML with Schema.org microdata
	 * Requirement 18.1: THE Breadcrumbs render() method SHALL accept optional CSS class parameter
	 * Requirement 18.2: THE Breadcrumbs render() method SHALL accept optional separator parameter (default: " › ")
	 * Requirement 18.5: THE Breadcrumbs SHALL use semantic HTML5 nav element with aria-label="Breadcrumb"
	 * Requirement 18.6: THE Breadcrumbs SHALL include Schema.org microdata using itemscope and itemprop attributes
	 *
	 * @since 1.0.0
	 * @param string $css_class Optional CSS class for the nav element.
	 * @param string $separator Optional separator between breadcrumbs (default: ' › ').
	 * @return string HTML output with breadcrumbs and microdata.
	 */
	public function render( string $css_class = '', string $separator = ' › ' ): string {
		$trail = $this->get_trail();

		if ( empty( $trail ) ) {
			return '';
		}

		/**
		 * Filter breadcrumb separator
		 *
		 * Allows customization of the separator between breadcrumb items.
		 *
		 * @since 1.0.0
		 * @param string $separator Separator string (default: ' › ').
		 */
		$separator = apply_filters( 'meowseo_breadcrumb_separator', $separator );

		$css_class = $css_class ? ' ' . sanitize_html_class( $css_class ) : '';

		$html = '<nav aria-label="Breadcrumb" class="meowseo-breadcrumbs' . $css_class . '">' . "\n";
		$html .= '  <ol itemscope itemtype="https://schema.org/BreadcrumbList">' . "\n";

		foreach ( $trail as $position => $item ) {
			$position_num = $position + 1;
			$html        .= '    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">' . "\n";

			if ( ! empty( $item['url'] ) ) {
				$html .= '      <a itemprop="item" href="' . esc_url( $item['url'] ) . '">' . "\n";
				$html .= '        <span itemprop="name">' . esc_html( $item['label'] ) . '</span>' . "\n";
				$html .= '      </a>' . "\n";
			} else {
				$html .= '      <span itemprop="name">' . esc_html( $item['label'] ) . '</span>' . "\n";
			}

			$html .= '      <meta itemprop="position" content="' . (int) $position_num . '" />' . "\n";
			$html .= '    </li>' . "\n";

			// Add separator between items (but not after the last item).
			if ( $position < count( $trail ) - 1 ) {
				$html .= '    <li aria-hidden="true">' . esc_html( $separator ) . '</li>' . "\n";
			}
		}

		$html .= '  </ol>' . "\n";
		$html .= '</nav>';

		/**
		 * Filter breadcrumb HTML output
		 *
		 * Allows customization of the rendered HTML.
		 *
		 * @since 1.0.0
		 * @param string $html Rendered HTML.
		 * @param array  $trail Breadcrumb trail array.
		 */
		return apply_filters( 'meowseo_breadcrumb_html', $html, $trail );
	}
}
