<?php
/**
 * Synonym Module
 *
 * Main module class for synonym analysis functionality.
 *
 * @package MeowSEO
 * @subpackage Modules\Synonyms
 */

namespace MeowSEO\Modules\Synonyms;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Synonym_Module class
 *
 * Initializes and manages synonym analysis functionality.
 */
class Synonym_Module implements Module {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Synonym Analyzer instance
	 *
	 * @var Synonym_Analyzer
	 */
	private Synonym_Analyzer $analyzer;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->analyzer = new Synonym_Analyzer();
	}

	/**
	 * Boot the module
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register REST API endpoints
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Get module ID
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'synonyms';
	}

	/**
	 * Get Synonym Analyzer instance
	 *
	 * @return Synonym_Analyzer Analyzer instance.
	 */
	public function get_analyzer(): Synonym_Analyzer {
		return $this->analyzer;
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		// Analyze synonyms endpoint
		register_rest_route(
			'meowseo/v1',
			'/synonyms/analyze/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_analyze_synonyms' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * REST API callback: Analyze synonyms for a post
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function rest_analyze_synonyms( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );

		// Get post
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_REST_Response(
				array( 'error' => 'Post not found' ),
				404
			);
		}

		// Get synonyms
		$synonyms = $this->analyzer->get_synonyms( $post_id );

		// Get primary keyword
		$primary_keyword = get_post_meta( $post_id, '_meowseo_focus_keyword', true );

		// Get context data
		$title = get_post_meta( $post_id, '_meowseo_title', true );
		if ( empty( $title ) ) {
			$title = $post->post_title;
		}

		$description = get_post_meta( $post_id, '_meowseo_description', true );

		$context = array(
			'title'       => $title,
			'description' => $description,
		);

		// Analyze primary keyword (using existing SEO analyzer)
		$primary_results = array();
		if ( ! empty( $primary_keyword ) ) {
			$seo_analysis = \MeowSEO\Modules\Meta\SEO_Analyzer::analyze(
				array(
					'title'         => $title,
					'description'   => $description,
					'content'       => $post->post_content,
					'slug'          => $post->post_name,
					'focus_keyword' => $primary_keyword,
				)
			);
			$primary_results = array(
				'keyword' => $primary_keyword,
				'score'   => $seo_analysis['score'],
				'checks'  => $seo_analysis['checks'],
			);
		}

		// Analyze each synonym
		$synonym_results = array();
		if ( ! empty( $synonyms ) ) {
			foreach ( $synonyms as $synonym ) {
				$synonym_results[] = $this->analyzer->analyze_synonym(
					$synonym,
					$post->post_content,
					$context
				);
			}
		}

		// Calculate combined score
		$combined_score = 0;
		if ( ! empty( $primary_results ) ) {
			$combined_score = $this->analyzer->calculate_combined_score(
				$primary_results,
				$synonym_results
			);
		}

		return new \WP_REST_Response(
			array(
				'primary'         => $primary_results,
				'synonyms'        => $synonym_results,
				'combined_score'  => $combined_score,
			),
			200
		);
	}
}
