<?php
/**
 * Product Node Tests
 *
 * Unit tests for the Product_Node schema builder class.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use MeowSEO\Helpers\Schema_Nodes\Product_Node;
use MeowSEO\Options;
use WP_Post;

/**
 * Product Node test case
 *
 * Tests Product_Node builder (Requirements 1.5, 11.1, 11.2, 11.3).
 *
 * @since 1.0.0
 */
class Test_Product_Node extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = $this->createMock( Options::class );
	}

	/**
	 * Test Product_Node instantiation
	 *
	 * @return void
	 */
	public function test_instantiation(): void {
		$post = $this->create_mock_post();
		$node = new Product_Node( $post->ID, $post, $this->options );
		
		$this->assertInstanceOf( Product_Node::class, $node );
	}

	/**
	 * Test is_needed returns true when WooCommerce is active and post type is product
	 *
	 * Validates Requirements 1.5, 11.1: Product node included when post_type is "product" AND WooCommerce is active.
	 *
	 * @return void
	 */
	public function test_is_needed_returns_true_for_product_type(): void {
		// Skip if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$post = $this->create_mock_post( array( 'post_type' => 'product' ) );
		$node = new Product_Node( $post->ID, $post, $this->options );
		
		$this->assertTrue( $node->is_needed() );
	}

	/**
	 * Test is_needed returns false when post type is not product
	 *
	 * Validates Requirement 11.1: Product node only for product post type.
	 *
	 * @return void
	 */
	public function test_is_needed_returns_false_for_non_product_type(): void {
		$post = $this->create_mock_post( array( 'post_type' => 'post' ) );
		$node = new Product_Node( $post->ID, $post, $this->options );
		
		$this->assertFalse( $node->is_needed() );
	}

	/**
	 * Test is_needed returns false when WooCommerce is not active
	 *
	 * Validates Requirement 11.1: Product node requires WooCommerce to be active.
	 *
	 * @return void
	 */
	public function test_is_needed_returns_false_when_woocommerce_not_active(): void {
		// This test validates the check, but we can't actually unload WooCommerce in runtime.
		// The actual check is in the is_needed() method.
		$post = $this->create_mock_post( array( 'post_type' => 'product' ) );
		$node = new Product_Node( $post->ID, $post, $this->options );
		
		// If WooCommerce is not active, is_needed should return false.
		// If WooCommerce is active, we can't test this scenario.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->assertFalse( $node->is_needed() );
		} else {
			$this->markTestSkipped( 'Cannot test WooCommerce inactive scenario when WooCommerce is active' );
		}
	}

	/**
	 * Test generate returns valid Product schema
	 *
	 * Validates Requirement 11.2: Product node includes name, url, description, sku, image, offers.
	 *
	 * @return void
	 */
	public function test_generate_returns_valid_schema(): void {
		// Skip if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$post = $this->create_mock_post( array( 'post_type' => 'product' ) );
		
		// Create a mock WooCommerce product.
		$product = $this->create_mock_wc_product( $post->ID );
		
		$node = new Product_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify required properties (Requirement 11.2).
		$this->assertIsArray( $schema );
		$this->assertEquals( 'Product', $schema['@type'] );
		$this->assertArrayHasKey( '@id', $schema );
		$this->assertArrayHasKey( 'name', $schema );
		$this->assertArrayHasKey( 'url', $schema );
	}

	/**
	 * Test generate includes offers with required properties
	 *
	 * Validates Requirement 11.3: Offers includes @type, priceCurrency, price, availability.
	 *
	 * @return void
	 */
	public function test_generate_includes_offers(): void {
		// Skip if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$post = $this->create_mock_post( array( 'post_type' => 'product' ) );
		
		// Create a mock WooCommerce product with price.
		$product = $this->create_mock_wc_product( $post->ID, array(
			'price' => '99.99',
		));
		
		$node = new Product_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify offers property (Requirement 11.3).
		$this->assertArrayHasKey( 'offers', $schema );
		$this->assertEquals( 'Offer', $schema['offers']['@type'] );
		$this->assertArrayHasKey( 'url', $schema['offers'] );
		$this->assertArrayHasKey( 'priceCurrency', $schema['offers'] );
		$this->assertArrayHasKey( 'price', $schema['offers'] );
		$this->assertArrayHasKey( 'availability', $schema['offers'] );
	}

	/**
	 * Test generate includes aggregateRating when reviews exist
	 *
	 * Validates Requirement 11.4: Product includes aggregateRating when reviews exist.
	 *
	 * @return void
	 */
	public function test_generate_includes_aggregate_rating(): void {
		// Skip if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$post = $this->create_mock_post( array( 'post_type' => 'product' ) );
		
		// Create a mock WooCommerce product with reviews.
		$product = $this->create_mock_wc_product( $post->ID, array(
			'review_count'   => 24,
			'average_rating' => 4.5,
		));
		
		$node = new Product_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify aggregateRating property (Requirement 11.4).
		$this->assertArrayHasKey( 'aggregateRating', $schema );
		$this->assertEquals( 'AggregateRating', $schema['aggregateRating']['@type'] );
		$this->assertArrayHasKey( 'ratingValue', $schema['aggregateRating'] );
		$this->assertArrayHasKey( 'reviewCount', $schema['aggregateRating'] );
		$this->assertEquals( 4.5, $schema['aggregateRating']['ratingValue'] );
		$this->assertEquals( 24, $schema['aggregateRating']['reviewCount'] );
	}

	/**
	 * Test generate excludes aggregateRating when no reviews exist
	 *
	 * Validates Requirement 11.4: aggregateRating only when reviews exist.
	 *
	 * @return void
	 */
	public function test_generate_excludes_aggregate_rating_without_reviews(): void {
		// Skip if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$post = $this->create_mock_post( array( 'post_type' => 'product' ) );
		
		// Create a mock WooCommerce product without reviews.
		$product = $this->create_mock_wc_product( $post->ID, array(
			'review_count'   => 0,
			'average_rating' => 0,
		));
		
		$node = new Product_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify aggregateRating is not present.
		$this->assertArrayNotHasKey( 'aggregateRating', $schema );
	}

	/**
	 * Test generate includes description
	 *
	 * Validates Requirement 11.2: Product includes description.
	 *
	 * @return void
	 */
	public function test_generate_includes_description(): void {
		// Skip if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$post = $this->create_mock_post( array( 'post_type' => 'product' ) );
		
		// Create a mock WooCommerce product with description.
		$product = $this->create_mock_wc_product( $post->ID, array(
			'description' => 'This is a test product description.',
		));
		
		$node = new Product_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify description property (Requirement 11.2).
		$this->assertArrayHasKey( 'description', $schema );
		$this->assertIsString( $schema['description'] );
	}

	/**
	 * Test generate includes SKU
	 *
	 * Validates Requirement 11.2: Product includes sku.
	 *
	 * @return void
	 */
	public function test_generate_includes_sku(): void {
		// Skip if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$post = $this->create_mock_post( array( 'post_type' => 'product' ) );
		
		// Create a mock WooCommerce product with SKU.
		$product = $this->create_mock_wc_product( $post->ID, array(
			'sku' => 'PROD-123',
		));
		
		$node = new Product_Node( $post->ID, $post, $this->options );
		$schema = $node->generate();
		
		// Verify sku property (Requirement 11.2).
		$this->assertArrayHasKey( 'sku', $schema );
		$this->assertEquals( 'PROD-123', $schema['sku'] );
	}

	/**
	 * Create a mock post object for testing
	 *
	 * @param array $overrides Optional property overrides.
	 * @return WP_Post Mock post object.
	 */
	private function create_mock_post( array $overrides = array() ): WP_Post {
		$defaults = array(
			'ID'            => 1,
			'post_title'    => 'Test Product',
			'post_content'  => 'This is test content for the product.',
			'post_excerpt'  => 'Test excerpt',
			'post_type'     => 'product',
			'post_status'   => 'publish',
			'post_author'   => 1,
			'post_date_gmt' => '2024-01-01 12:00:00',
			'post_modified_gmt' => '2024-01-02 12:00:00',
		);

		$data = array_merge( $defaults, $overrides );
		$post = new \stdClass();
		
		foreach ( $data as $key => $value ) {
			$post->$key = $value;
		}

		return (object) $post;
	}

	/**
	 * Create a mock WooCommerce product for testing
	 *
	 * Note: This is a simplified mock. In real tests with WordPress environment,
	 * you would use WC_Helper_Product::create_simple_product() or similar.
	 *
	 * @param int   $post_id   Post ID.
	 * @param array $overrides Optional property overrides.
	 * @return object Mock product object.
	 */
	private function create_mock_wc_product( int $post_id, array $overrides = array() ): object {
		$defaults = array(
			'name'           => 'Test Product',
			'description'    => 'Test product description',
			'sku'            => 'TEST-SKU',
			'price'          => '99.99',
			'review_count'   => 0,
			'average_rating' => 0,
		);

		$data = array_merge( $defaults, $overrides );
		
		// This is a mock - in real WordPress environment, wc_get_product() would be used.
		// For unit tests, you would need to mock the WC_Product class.
		
		return (object) $data;
	}
}
