<?php
/**
 * Meta Postmeta Registration Class
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

/**
 * Meta_Postmeta class
 *
 * Responsible for registering all SEO postmeta fields with WordPress.
 */
class Meta_Postmeta {
	/**
	 * Postmeta keys with their types
	 *
	 * @var array
	 */
	private const META_KEYS = array(
		'title'               => 'string',
		'description'         => 'string',
		'robots_noindex'      => 'boolean',
		'robots_nofollow'     => 'boolean',
		'canonical'           => 'string',
		'og_title'            => 'string',
		'og_description'      => 'string',
		'og_image'            => 'integer',
		'twitter_title'       => 'string',
		'twitter_description' => 'string',
		'twitter_image'       => 'integer',
		'focus_keyword'       => 'string',
		'direct_answer'       => 'string',
		'schema_type'         => 'string',
		'schema_config'       => 'string',
		'gsc_last_submit'     => 'integer',
	);

	/**
	 * Register all postmeta fields
	 *
	 * @return void
	 */
	public function register(): void {
		// TODO: Implement register() method
	}

	/**
	 * Get all public post types
	 *
	 * @return array Post types.
	 */
	private function get_post_types(): array {
		// TODO: Implement get_post_types() method
		return array();
	}

	/**
	 * Get meta registration args
	 *
	 * @param string $key  Meta key.
	 * @param string $type Meta type.
	 * @return array Registration args.
	 */
	private function get_meta_args( string $key, string $type ): array {
		// TODO: Implement get_meta_args() method
		return array();
	}
}
