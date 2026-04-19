<?php
/**
 * Recipe Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Recipe_Schema_Generator class
 *
 * Generates Recipe schema markup with cooking instructions and nutrition information.
 */
class Recipe_Schema_Generator {
	/**
	 * Generate Recipe schema
	 *
	 * @param int   $post_id Post ID.
	 * @param array $config  Schema configuration.
	 * @return array Schema data.
	 */
	public function generate( int $post_id, array $config ): array {
		$validation = $this->validate_config( $config );
		if ( is_wp_error( $validation ) ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$permalink = get_permalink( $post );
		$site_url  = get_site_url();

		$schema = array(
			'@type'       => 'Recipe',
			'@id'         => $permalink . '#recipe',
			'name'        => $config['name'] ?? get_the_title( $post ),
			'description' => $config['description'] ?? '',
		);

		// Add author
		$schema['author'] = array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', $post->post_author ),
		);

		// Add datePublished
		$schema['datePublished'] = get_the_date( 'c', $post );

		// Add image if available
		if ( ! empty( $config['image'] ) ) {
			$schema['image'] = $config['image'];
		} elseif ( has_post_thumbnail( $post ) ) {
			$image_url = get_the_post_thumbnail_url( $post, 'full' );
			if ( $image_url ) {
				$schema['image'] = $image_url;
			}
		}

		// Add required fields
		$schema['recipeIngredient']   = $this->format_ingredients( $config['recipeIngredient'] ?? array() );
		$schema['recipeInstructions'] = $this->format_instructions( $config['recipeInstructions'] ?? array() );

		// Add optional time fields
		if ( ! empty( $config['prepTime'] ) ) {
			$schema['prepTime'] = $config['prepTime'];
		}
		if ( ! empty( $config['cookTime'] ) ) {
			$schema['cookTime'] = $config['cookTime'];
		}
		if ( ! empty( $config['totalTime'] ) ) {
			$schema['totalTime'] = $config['totalTime'];
		}

		// Add optional yield and category fields
		if ( ! empty( $config['recipeYield'] ) ) {
			$schema['recipeYield'] = $config['recipeYield'];
		}
		if ( ! empty( $config['recipeCategory'] ) ) {
			$schema['recipeCategory'] = $config['recipeCategory'];
		}
		if ( ! empty( $config['recipeCuisine'] ) ) {
			$schema['recipeCuisine'] = $config['recipeCuisine'];
		}

		// Add nutrition information if provided
		if ( ! empty( $config['nutrition'] ) && is_array( $config['nutrition'] ) ) {
			$nutrition = $this->format_nutrition( $config['nutrition'] );
			if ( ! empty( $nutrition ) ) {
				$schema['nutrition'] = $nutrition;
			}
		}

		return $schema;
	}

	/**
	 * Get required fields
	 *
	 * @return array Required field names.
	 */
	public function get_required_fields(): array {
		return array( 'name', 'description', 'recipeIngredient', 'recipeInstructions' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array(
			'prepTime',
			'cookTime',
			'totalTime',
			'recipeYield',
			'recipeCategory',
			'recipeCuisine',
			'nutrition',
			'image',
		);
	}

	/**
	 * Validate configuration
	 *
	 * @param array $config Schema configuration.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_config( array $config ) {
		$required_fields = $this->get_required_fields();

		foreach ( $required_fields as $field ) {
			if ( empty( $config[ $field ] ) ) {
				return new \WP_Error(
					'missing_required_field',
					sprintf( 'Recipe schema missing required field: %s', $field )
				);
			}
		}

		// Validate recipeIngredient is an array
		if ( ! is_array( $config['recipeIngredient'] ) ) {
			return new \WP_Error(
				'invalid_field_type',
				'recipeIngredient must be an array'
			);
		}

		// Validate recipeInstructions is an array
		if ( ! is_array( $config['recipeInstructions'] ) ) {
			return new \WP_Error(
				'invalid_field_type',
				'recipeInstructions must be an array'
			);
		}

		return true;
	}

	/**
	 * Format ingredients array
	 *
	 * @param array $ingredients Raw ingredients array.
	 * @return array Formatted ingredients array.
	 */
	private function format_ingredients( array $ingredients ): array {
		$formatted = array();

		foreach ( $ingredients as $ingredient ) {
			if ( is_string( $ingredient ) && ! empty( trim( $ingredient ) ) ) {
				$formatted[] = trim( $ingredient );
			}
		}

		return $formatted;
	}

	/**
	 * Format instructions array
	 *
	 * @param array $instructions Raw instructions array.
	 * @return array Formatted instructions array with HowToStep structure.
	 */
	private function format_instructions( array $instructions ): array {
		$formatted = array();

		foreach ( $instructions as $instruction ) {
			if ( is_string( $instruction ) && ! empty( trim( $instruction ) ) ) {
				$formatted[] = array(
					'@type' => 'HowToStep',
					'text'  => trim( $instruction ),
				);
			} elseif ( is_array( $instruction ) && ! empty( $instruction['text'] ) ) {
				$formatted[] = array(
					'@type' => 'HowToStep',
					'text'  => trim( $instruction['text'] ),
				);
			}
		}

		return $formatted;
	}

	/**
	 * Format nutrition information
	 *
	 * @param array $nutrition Raw nutrition data.
	 * @return array Formatted NutritionInformation schema.
	 */
	private function format_nutrition( array $nutrition ): array {
		$formatted = array(
			'@type' => 'NutritionInformation',
		);

		$valid_fields = array(
			'calories',
			'fatContent',
			'carbohydrateContent',
			'proteinContent',
			'fiberContent',
			'sugarContent',
			'sodiumContent',
			'cholesterolContent',
			'saturatedFatContent',
			'unsaturatedFatContent',
			'transFatContent',
		);

		foreach ( $valid_fields as $field ) {
			if ( ! empty( $nutrition[ $field ] ) ) {
				$formatted[ $field ] = $nutrition[ $field ];
			}
		}

		// Return empty array if only @type is present
		if ( count( $formatted ) === 1 ) {
			return array();
		}

		return $formatted;
	}
}
