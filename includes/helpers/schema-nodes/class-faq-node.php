<?php
/**
 * FAQ Schema Node builder.
 *
 * Generates FAQPage schema node with Question/Answer pairs from postmeta configuration.
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
 * FAQ Schema Node class.
 *
 * Generates FAQPage schema node (Requirements 1.6, 9.2).
 *
 * @since 1.0.0
 */
class FAQ_Node extends Abstract_Schema_Node {

	/**
	 * Generate FAQPage schema node
	 *
	 * Generates FAQPage schema with mainEntity array containing Question/Answer pairs.
	 * Reads FAQ items from _meowseo_schema_config postmeta (Requirement 9.2).
	 *
	 * @since 1.0.0
	 * @return array FAQPage schema node.
	 */
	public function generate(): array {
		$node = array(
			'@type' => 'FAQPage',
			'@id'   => $this->get_id_url( 'faqpage' ),
		);

		// Read FAQ items from postmeta.
		$schema_config = get_post_meta( $this->post_id, '_meowseo_schema_config', true );
		
		// Parse JSON if it's a string.
		if ( is_string( $schema_config ) ) {
			$schema_config = json_decode( $schema_config, true );
		}

		// Build mainEntity array with Question/Answer pairs.
		$main_entity = array();
		
		if ( ! empty( $schema_config['faq_items'] ) && is_array( $schema_config['faq_items'] ) ) {
			foreach ( $schema_config['faq_items'] as $faq_item ) {
				// Skip items without both question and answer.
				if ( empty( $faq_item['question'] ) || empty( $faq_item['answer'] ) ) {
					continue;
				}

				$main_entity[] = array(
					'@type'          => 'Question',
					'name'           => $faq_item['question'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $faq_item['answer'],
					),
				);
			}
		}

		// Only add mainEntity if we have FAQ items.
		if ( ! empty( $main_entity ) ) {
			$node['mainEntity'] = $main_entity;
		}

		return $node;
	}

	/**
	 * Check if FAQ node is needed
	 *
	 * FAQ node is included when schema_type is "FAQPage" AND FAQ items exist (Requirement 1.6).
	 *
	 * @since 1.0.0
	 * @return bool True if FAQ node should be included, false otherwise.
	 */
	public function is_needed(): bool {
		// Check if schema type is "FAQPage".
		$schema_type = get_post_meta( $this->post_id, '_meowseo_schema_type', true );
		if ( 'FAQPage' !== $schema_type ) {
			return false;
		}

		// Check if FAQ items exist.
		$schema_config = get_post_meta( $this->post_id, '_meowseo_schema_config', true );
		
		// Parse JSON if it's a string.
		if ( is_string( $schema_config ) ) {
			$schema_config = json_decode( $schema_config, true );
		}

		// Verify we have at least one valid FAQ item.
		if ( ! empty( $schema_config['faq_items'] ) && is_array( $schema_config['faq_items'] ) ) {
			foreach ( $schema_config['faq_items'] as $faq_item ) {
				// If we find at least one item with both question and answer, we need this node.
				if ( ! empty( $faq_item['question'] ) && ! empty( $faq_item['answer'] ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
