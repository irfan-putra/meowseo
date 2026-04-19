<?php
/**
 * Recipe Schema Generator Tests
 *
 * Unit tests for the Recipe_Schema_Generator class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use MeowSEO\Modules\Schema\Generators\Recipe_Schema_Generator;
use PHPUnit\Framework\TestCase;

/**
 * Test_Recipe_Schema_Generator class
 *
 * @since 1.0.0
 */
class Test_Recipe_Schema_Generator extends TestCase {

	/**
	 * Recipe generator instance
	 *
	 * @var Recipe_Schema_Generator
	 */
	private Recipe_Schema_Generator $generator;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->generator = new Recipe_Schema_Generator();
	}

	/**
	 * Test Recipe_Schema_Generator instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$this->assertInstanceOf( Recipe_Schema_Generator::class, $this->generator );
	}

	/**
	 * Test get_required_fields returns correct fields
	 *
	 * Validates Requirement 1.1: Recipe schema has required fields.
	 *
	 * @return void
	 */
	public function test_get_required_fields(): void {
		$required = $this->generator->get_required_fields();

		$this->assertIsArray( $required );
		$this->assertContains( 'name', $required );
		$this->assertContains( 'description', $required );
		$this->assertContains( 'recipeIngredient', $required );
		$this->assertContains( 'recipeInstructions', $required );
	}

	/**
	 * Test get_optional_fields returns correct fields
	 *
	 * Validates Requirement 1.1: Recipe schema has optional fields.
	 *
	 * @return void
	 */
	public function test_get_optional_fields(): void {
		$optional = $this->generator->get_optional_fields();

		$this->assertIsArray( $optional );
		$this->assertContains( 'prepTime', $optional );
		$this->assertContains( 'cookTime', $optional );
		$this->assertContains( 'totalTime', $optional );
		$this->assertContains( 'recipeYield', $optional );
		$this->assertContains( 'recipeCategory', $optional );
		$this->assertContains( 'recipeCuisine', $optional );
		$this->assertContains( 'nutrition', $optional );
		$this->assertContains( 'image', $optional );
	}

	/**
	 * Test validate_config returns true for valid config
	 *
	 * @return void
	 */
	public function test_validate_config_returns_true_for_valid_config(): void {
		$config = array(
			'name'                => 'Test Recipe',
			'description'         => 'A test recipe',
			'recipeIngredient'    => array( '1 cup flour', '2 eggs' ),
			'recipeInstructions'  => array( 'Mix ingredients', 'Bake' ),
		);

		$result = $this->generator->validate_config( $config );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate_config returns WP_Error for missing required field
	 *
	 * @return void
	 */
	public function test_validate_config_returns_error_for_missing_field(): void {
		$config = array(
			'name'        => 'Test Recipe',
			'description' => 'A test recipe',
			// Missing recipeIngredient and recipeInstructions
		);

		$result = $this->generator->validate_config( $config );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test validate_config returns WP_Error for invalid ingredient type
	 *
	 * @return void
	 */
	public function test_validate_config_returns_error_for_invalid_ingredient_type(): void {
		$config = array(
			'name'                => 'Test Recipe',
			'description'         => 'A test recipe',
			'recipeIngredient'    => 'not an array', // Should be array
			'recipeInstructions'  => array( 'Mix ingredients' ),
		);

		$result = $this->generator->validate_config( $config );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_field_type', $result->get_error_code() );
	}

	/**
	 * Test generate returns valid Recipe schema
	 *
	 * Validates Requirement 1.1: Recipe schema generation with all required fields.
	 *
	 * @return void
	 */
	public function test_generate_returns_valid_schema(): void {
		// Create a mock post
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Recipe Post',
				'post_content' => 'Recipe content',
				'post_author'  => 1,
			)
		);

		$config = array(
			'name'                => 'Chocolate Chip Cookies',
			'description'         => 'Delicious homemade cookies',
			'recipeIngredient'    => array(
				'2 cups flour',
				'1 cup sugar',
				'1 cup chocolate chips',
			),
			'recipeInstructions'  => array(
				'Mix dry ingredients',
				'Add wet ingredients',
				'Bake at 350°F for 12 minutes',
			),
			'prepTime'            => 'PT15M',
			'cookTime'            => 'PT12M',
			'totalTime'           => 'PT27M',
			'recipeYield'         => '24 cookies',
			'recipeCategory'      => 'Dessert',
			'recipeCuisine'       => 'American',
		);

		$schema = $this->generator->generate( $post_id, $config );

		// Verify schema structure
		$this->assertIsArray( $schema );
		$this->assertEquals( 'Recipe', $schema['@type'] );
		$this->assertStringContainsString( '#recipe', $schema['@id'] );
		$this->assertEquals( 'Chocolate Chip Cookies', $schema['name'] );
		$this->assertEquals( 'Delicious homemade cookies', $schema['description'] );

		// Verify required fields
		$this->assertArrayHasKey( 'recipeIngredient', $schema );
		$this->assertIsArray( $schema['recipeIngredient'] );
		$this->assertCount( 3, $schema['recipeIngredient'] );

		$this->assertArrayHasKey( 'recipeInstructions', $schema );
		$this->assertIsArray( $schema['recipeInstructions'] );
		$this->assertCount( 3, $schema['recipeInstructions'] );
		$this->assertEquals( 'HowToStep', $schema['recipeInstructions'][0]['@type'] );

		// Verify optional fields
		$this->assertEquals( 'PT15M', $schema['prepTime'] );
		$this->assertEquals( 'PT12M', $schema['cookTime'] );
		$this->assertEquals( 'PT27M', $schema['totalTime'] );
		$this->assertEquals( '24 cookies', $schema['recipeYield'] );
		$this->assertEquals( 'Dessert', $schema['recipeCategory'] );
		$this->assertEquals( 'American', $schema['recipeCuisine'] );

		// Verify author
		$this->assertArrayHasKey( 'author', $schema );
		$this->assertEquals( 'Person', $schema['author']['@type'] );

		// Verify datePublished
		$this->assertArrayHasKey( 'datePublished', $schema );
	}

	/**
	 * Test generate includes nutrition information
	 *
	 * Validates Requirement 1.1: Recipe schema includes nutrition.
	 *
	 * @return void
	 */
	public function test_generate_includes_nutrition(): void {
		$post_id = wp_insert_post( array( 'post_title' => 'Test Recipe' ) );

		$config = array(
			'name'                => 'Test Recipe',
			'description'         => 'Test description',
			'recipeIngredient'    => array( '1 cup flour' ),
			'recipeInstructions'  => array( 'Mix' ),
			'nutrition'           => array(
				'calories'            => '250 calories',
				'fatContent'          => '10g',
				'carbohydrateContent' => '30g',
				'proteinContent'      => '8g',
			),
		);

		$schema = $this->generator->generate( $post_id, $config );

		$this->assertArrayHasKey( 'nutrition', $schema );
		$this->assertEquals( 'NutritionInformation', $schema['nutrition']['@type'] );
		$this->assertEquals( '250 calories', $schema['nutrition']['calories'] );
		$this->assertEquals( '10g', $schema['nutrition']['fatContent'] );
		$this->assertEquals( '30g', $schema['nutrition']['carbohydrateContent'] );
		$this->assertEquals( '8g', $schema['nutrition']['proteinContent'] );
	}

	/**
	 * Test generate returns empty array for invalid config
	 *
	 * @return void
	 */
	public function test_generate_returns_empty_for_invalid_config(): void {
		$post_id = wp_insert_post( array( 'post_title' => 'Test Recipe' ) );

		$config = array(
			'name' => 'Test Recipe',
			// Missing required fields
		);

		$schema = $this->generator->generate( $post_id, $config );

		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test generate returns empty array for non-existent post
	 *
	 * @return void
	 */
	public function test_generate_returns_empty_for_nonexistent_post(): void {
		$config = array(
			'name'                => 'Test Recipe',
			'description'         => 'Test description',
			'recipeIngredient'    => array( '1 cup flour' ),
			'recipeInstructions'  => array( 'Mix' ),
		);

		$schema = $this->generator->generate( 999999, $config );

		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}
}

