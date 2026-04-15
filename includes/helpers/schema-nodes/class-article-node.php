<?php
/**
 * Article Schema Node builder.
 *
 * Generates Article schema node for blog posts with author, publisher, and speakable properties.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Helpers\Schema_Nodes;

use MeowSEO\Helpers\Abstract_Schema_Node;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Article Schema Node class.
 *
 * Generates Article schema node (Requirements 1.4, 1.11, 20.1, 20.2).
 *
 * @since 1.0.0
 */
class Article_Node extends Abstract_Schema_Node {

	/**
	 * Generate Article schema node
	 *
	 * Generates Article schema with author, publisher, and speakable properties.
	 *
	 * @since 1.0.0
	 * @return array Article schema node.
	 */
	public function generate(): array {
		$permalink = get_permalink( $this->post );
		$language  = get_bloginfo( 'language' );

		$node = array(
			'@type'            => 'Article',
			'@id'              => $this->get_id_url( 'article' ),
			'isPartOf'         => array(
				'@id' => $this->get_id_url( 'webpage' ),
			),
			'headline'         => get_the_title( $this->post ),
			'datePublished'    => $this->format_date( $this->post->post_date_gmt ),
			'dateModified'     => $this->format_date( $this->post->post_modified_gmt ),
			'mainEntityOfPage' => array(
				'@id' => $this->get_id_url( 'webpage' ),
			),
			'publisher'        => array(
				'@id' => $this->get_site_id_url( 'organization' ),
			),
			'inLanguage'       => $language,
		);

		// Add author Person.
		$author_id = $this->post->post_author;
		if ( ! empty( $author_id ) ) {
			$author_name = get_the_author_meta( 'display_name', $author_id );
			$node['author'] = array(
				'@type' => 'Person',
				'@id'   => $this->get_site_url() . '/#/schema/person/' . $author_id,
				'name'  => $author_name,
			);
		}

		// Add word count.
		$content    = $this->post->post_content;
		$word_count = str_word_count( wp_strip_all_tags( $content ) );
		if ( $word_count > 0 ) {
			$node['wordCount'] = $word_count;
		}

		// Add comment count.
		$comment_count = (int) $this->post->comment_count;
		if ( $comment_count > 0 ) {
			$node['commentCount'] = $comment_count;
		}

		// Add primary image if available.
		if ( has_post_thumbnail( $this->post ) ) {
			$node['image'] = array(
				'@id' => $this->get_id_url( 'primaryimage' ),
			);

			// Add thumbnailUrl.
			$thumbnail_url = get_the_post_thumbnail_url( $this->post, 'full' );
			if ( ! empty( $thumbnail_url ) ) {
				$node['thumbnailUrl'] = $thumbnail_url;
			}
		}

		// Add article sections (categories).
		$categories = get_the_category( $this->post_id );
		if ( ! empty( $categories ) ) {
			$sections = array();
			foreach ( $categories as $category ) {
				$sections[] = $category->name;
			}
			if ( ! empty( $sections ) ) {
				$node['articleSection'] = $sections;
			}
		}

		// Add keywords (tags).
		$tags = get_the_tags( $this->post_id );
		if ( ! empty( $tags ) && is_array( $tags ) ) {
			$keywords = array();
			foreach ( $tags as $tag ) {
				$keywords[] = $tag->name;
			}
			if ( ! empty( $keywords ) ) {
				$node['keywords'] = $keywords;
			}
		}

		// Add speakable property (Requirements 1.11, 20.1, 20.2).
		$node['speakable'] = array(
			'@type'       => 'SpeakableSpecification',
			'cssSelector' => array( '#meowseo-direct-answer' ),
		);

		return $node;
	}

	/**
	 * Check if Article node is needed
	 *
	 * Article node is included when post_type is "post" OR schema_type is "Article" (Requirement 1.4).
	 *
	 * @since 1.0.0
	 * @return bool True if Article node should be included, false otherwise.
	 */
	public function is_needed(): bool {
		// Check if post type is "post".
		if ( 'post' === $this->post->post_type ) {
			return true;
		}

		// Check if schema type is explicitly set to "Article".
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		if ( 'Article' === $schema_type ) {
			return true;
		}

		return false;
	}
}
