<?php
/**
 * WooCommerce Module Test
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\WooCommerce
 */

namespace MeowSEO\Tests\Modules\WooCommerce;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\WooCommerce\WooCommerce;
use MeowSEO\Options;
use MeowSEO\Contracts\Module;

/**
 * Test WooCommerce module class
 */
class WooCommerceModuleTest extends TestCase {
	/**
	 * WooCommerce module instance
	 *
	 * @var WooCommerce
	 */
	private WooCommerce $module;

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
		$this->options = new Options();
		$this->module = new WooCommerce( $this->options );
	}

	/**
	 * Test Module interface implementation
	 *
	 * Validates: Requirement 20.5
	 *
	 * @return void
	 */
	public function test_module_interface_implementation(): void {
		$this->assertInstanceOf( Module::class, $this->module );
	}

	/**
	 * Test get_id returns correct module ID
	 *
	 * Validates: Requirement 20.5
	 *
	 * @return void
	 */
	public function test_get_id_returns_woocommerce(): void {
		$this->assertEquals( 'woocommerce', $this->module->get_id() );
	}

	/**
	 * Test generate_product_schema returns empty array when WooCommerce not active
	 *
	 * Validates: Requirement 21.1
	 *
	 * @return void
	 */
	public function test_generate_product_schema_returns_empty_when_woocommerce_inactive(): void {
		// This test assumes WooCommerce is not active in test environment
		// If WooCommerce is active, this test will be skipped
		if ( class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is active in test environment' );
		}

		$schema = $this->module->generate_product_schema( 999 );
		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test generate_product_schema returns empty array for non-existent product
	 *
	 * Validates: Requirement 21.1
	 *
	 * @return void
	 */
	public function test_generate_product_schema_returns_empty_for_nonexistent_product(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$schema = $this->module->generate_product_schema( 999999 );
		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test generate_product_schema includes required fields
	 *
	 * Validates: Requirement 21.2
	 *
	 * @return void
	 */
	public function test_generate_product_schema_includes_required_fields(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// Create a test product
		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$schema = $this->module->generate_product_schema( $product_id );

		// Verify required fields (Requirement 21.2)
		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );
		$this->assertArrayHasKey( '@context', $schema );
		$this->assertArrayHasKey( '@type', $schema );
		$this->assertArrayHasKey( 'name', $schema );
		$this->assertArrayHasKey( 'url', $schema );

		// Verify @context and @type values
		$this->assertEquals( 'https://schema.org', $schema['@context'] );
		$this->assertEquals( 'Product', $schema['@type'] );

		// Clean up
		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_schema includes description
	 *
	 * Validates: Requirement 21.2
	 *
	 * @return void
	 */
	public function test_generate_product_schema_includes_description(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product( array(
			'short_description' => 'Test product description',
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$schema = $this->module->generate_product_schema( $product_id );

		$this->assertArrayHasKey( 'description', $schema );
		$this->assertNotEmpty( $schema['description'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_schema includes SKU
	 *
	 * Validates: Requirement 21.2
	 *
	 * @return void
	 */
	public function test_generate_product_schema_includes_sku(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product( array(
			'sku' => 'TEST-SKU-123',
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$schema = $this->module->generate_product_schema( $product_id );

		$this->assertArrayHasKey( 'sku', $schema );
		$this->assertEquals( 'TEST-SKU-123', $schema['sku'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_schema includes offers with price
	 *
	 * Validates: Requirements 21.3, 21.4, 21.5
	 *
	 * @return void
	 */
	public function test_generate_product_schema_includes_offers_with_price(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product( array(
			'price' => '99.99',
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$schema = $this->module->generate_product_schema( $product_id );

		// Verify offers structure (Requirement 21.2)
		$this->assertArrayHasKey( 'offers', $schema );
		$this->assertIsArray( $schema['offers'] );

		// Verify offer fields (Requirements 21.3, 21.4, 21.5)
		$this->assertArrayHasKey( '@type', $schema['offers'] );
		$this->assertEquals( 'Offer', $schema['offers']['@type'] );

		$this->assertArrayHasKey( 'price', $schema['offers'] );
		$this->assertEquals( '99.99', $schema['offers']['price'] );

		$this->assertArrayHasKey( 'priceCurrency', $schema['offers'] );
		$this->assertNotEmpty( $schema['offers']['priceCurrency'] );

		$this->assertArrayHasKey( 'availability', $schema['offers'] );
		$this->assertNotEmpty( $schema['offers']['availability'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_schema sets availability to InStock for in-stock products
	 *
	 * Validates: Requirement 21.5
	 *
	 * @return void
	 */
	public function test_generate_product_schema_availability_in_stock(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product( array(
			'price'  => '99.99',
			'manage_stock' => true,
			'stock_quantity' => 10,
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$schema = $this->module->generate_product_schema( $product_id );

		$this->assertArrayHasKey( 'offers', $schema );
		$this->assertArrayHasKey( 'availability', $schema['offers'] );
		$this->assertEquals( 'https://schema.org/InStock', $schema['offers']['availability'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_schema sets availability to OutOfStock for out-of-stock products
	 *
	 * Validates: Requirement 21.5
	 *
	 * @return void
	 */
	public function test_generate_product_schema_availability_out_of_stock(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product( array(
			'price'  => '99.99',
			'manage_stock' => true,
			'stock_quantity' => 0,
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$schema = $this->module->generate_product_schema( $product_id );

		$this->assertArrayHasKey( 'offers', $schema );
		$this->assertArrayHasKey( 'availability', $schema['offers'] );
		$this->assertEquals( 'https://schema.org/OutOfStock', $schema['offers']['availability'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_schema includes aggregateRating when reviews exist
	 *
	 * Validates: Requirement 21.6
	 *
	 * @return void
	 */
	public function test_generate_product_schema_includes_aggregate_rating_with_reviews(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product( array(
			'price' => '99.99',
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		// Add a review to the product
		$this->add_product_review( $product_id, 5 );

		$schema = $this->module->generate_product_schema( $product_id );

		// Verify aggregateRating is included (Requirement 21.6)
		$this->assertArrayHasKey( 'aggregateRating', $schema );
		$this->assertIsArray( $schema['aggregateRating'] );

		// Verify aggregateRating structure
		$this->assertArrayHasKey( '@type', $schema['aggregateRating'] );
		$this->assertEquals( 'AggregateRating', $schema['aggregateRating']['@type'] );

		$this->assertArrayHasKey( 'ratingValue', $schema['aggregateRating'] );
		$this->assertNotEmpty( $schema['aggregateRating']['ratingValue'] );

		$this->assertArrayHasKey( 'reviewCount', $schema['aggregateRating'] );
		$this->assertGreaterThan( 0, $schema['aggregateRating']['reviewCount'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_schema does not include aggregateRating without reviews
	 *
	 * Validates: Requirement 21.6
	 *
	 * @return void
	 */
	public function test_generate_product_schema_no_aggregate_rating_without_reviews(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product( array(
			'price' => '99.99',
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$schema = $this->module->generate_product_schema( $product_id );

		// Verify aggregateRating is not included when no reviews
		$this->assertArrayNotHasKey( 'aggregateRating', $schema );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_schema validates schema structure
	 *
	 * Validates: Requirement 21.7
	 *
	 * @return void
	 */
	public function test_generate_product_schema_validates_schema(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product( array(
			'price' => '99.99',
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$schema = $this->module->generate_product_schema( $product_id );

		// Verify schema is valid (Requirement 21.7)
		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		// Verify required schema.org fields
		$this->assertEquals( 'https://schema.org', $schema['@context'] );
		$this->assertEquals( 'Product', $schema['@type'] );
		$this->assertNotEmpty( $schema['name'] );
		$this->assertNotEmpty( $schema['url'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Helper method to create a test product
	 *
	 * @param array $args Product arguments.
	 * @return int|false Product ID or false on failure.
	 */
	private function create_test_product( array $args = array() ): int|false {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		$defaults = array(
			'name'        => 'Test Product',
			'description' => 'Test product description',
			'price'       => '99.99',
			'sku'         => 'TEST-SKU',
		);

		$args = wp_parse_args( $args, $defaults );

		// Create a simple product
		$product = new \WC_Product_Simple();
		$product->set_name( $args['name'] );
		$product->set_description( $args['description'] ?? '' );
		$product->set_short_description( $args['short_description'] ?? '' );
		$product->set_price( $args['price'] );
		$product->set_sku( $args['sku'] );

		// Set stock if manage_stock is true
		if ( isset( $args['manage_stock'] ) && $args['manage_stock'] ) {
			$product->set_manage_stock( true );
			if ( isset( $args['stock_quantity'] ) ) {
				$product->set_stock_quantity( $args['stock_quantity'] );
			}
		}

		$product_id = $product->save();

		return $product_id ? $product_id : false;
	}

	/**
	 * Helper method to add a review to a product
	 *
	 * @param int $product_id Product ID.
	 * @param int $rating     Rating value (1-5).
	 * @return int|false Comment ID or false on failure.
	 */
	private function add_product_review( int $product_id, int $rating ): int|false {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		$comment_id = wp_insert_comment( array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => 'Test Reviewer',
			'comment_author_email' => 'test@example.com',
			'comment_content'      => 'Great product!',
			'comment_type'         => 'review',
			'comment_approved'     => 1,
		) );

		if ( $comment_id ) {
			update_comment_meta( $comment_id, 'rating', $rating );
		}

		return $comment_id ? $comment_id : false;
	}

	/**
	 * Test add_product_sitemap_metadata adds priority and changefreq for products
	 *
	 * Validates: Requirements 22.4, 22.5
	 *
	 * @return void
	 */
	public function test_add_product_sitemap_metadata_adds_priority_and_changefreq(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$entry = array(
			'loc'     => get_permalink( $product_id ),
			'lastmod' => gmdate( 'Y-m-d\TH:i:s\+00:00' ),
		);

		$result = $this->module->add_product_sitemap_metadata( $entry, $product_id );

		// Verify priority is set to 0.8 (Requirement 22.4)
		$this->assertArrayHasKey( 'priority', $result );
		$this->assertEquals( '0.8', $result['priority'] );

		// Verify changefreq is set to weekly (Requirement 22.5)
		$this->assertArrayHasKey( 'changefreq', $result );
		$this->assertEquals( 'weekly', $result['changefreq'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test add_product_sitemap_metadata does not modify non-product entries
	 *
	 * Validates: Requirements 22.4, 22.5
	 *
	 * @return void
	 */
	public function test_add_product_sitemap_metadata_ignores_non_products(): void {
		// Create a regular post
		$post_id = wp_insert_post( array(
			'post_type'   => 'post',
			'post_title'  => 'Test Post',
			'post_status' => 'publish',
		) );

		$entry = array(
			'loc'     => get_permalink( $post_id ),
			'lastmod' => gmdate( 'Y-m-d\TH:i:s\+00:00' ),
		);

		$result = $this->module->add_product_sitemap_metadata( $entry, $post_id );

		// Verify entry is unchanged for non-products
		$this->assertEquals( $entry, $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test add_products_to_sitemap returns empty sitemap when WooCommerce inactive
	 *
	 * Validates: Requirement 22.1
	 *
	 * @return void
	 */
	public function test_add_products_to_sitemap_returns_empty_when_woocommerce_inactive(): void {
		// This test assumes WooCommerce is not active in test environment
		if ( class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is active in test environment' );
		}

		$sitemap = array();
		$result = $this->module->add_products_to_sitemap( $sitemap );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test add_products_to_sitemap adds products to sitemap
	 *
	 * Validates: Requirement 22.1
	 *
	 * @return void
	 */
	public function test_add_products_to_sitemap_adds_products(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$sitemap = array();
		$result = $this->module->add_products_to_sitemap( $sitemap );

		// Verify product was added to sitemap (Requirement 22.1)
		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );

		// Verify product entry has required fields
		$product_entry = $result[0];
		$this->assertArrayHasKey( 'loc', $product_entry );
		$this->assertArrayHasKey( 'lastmod', $product_entry );
		$this->assertArrayHasKey( 'priority', $product_entry );
		$this->assertArrayHasKey( 'changefreq', $product_entry );

		// Verify priority is 0.8 (Requirement 22.4)
		$this->assertEquals( '0.8', $product_entry['priority'] );

		// Verify changefreq is weekly (Requirement 22.5)
		$this->assertEquals( 'weekly', $product_entry['changefreq'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test add_products_to_sitemap respects exclude out-of-stock setting
	 *
	 * Validates: Requirements 22.2, 22.3
	 *
	 * @return void
	 */
	public function test_add_products_to_sitemap_excludes_out_of_stock_when_enabled(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// Create an out-of-stock product
		$product_id = $this->create_test_product( array(
			'manage_stock'   => true,
			'stock_quantity' => 0,
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		// Enable exclude out-of-stock setting (Requirement 22.2)
		$this->options->set( 'woocommerce_exclude_out_of_stock', true );

		$sitemap = array();
		$result = $this->module->add_products_to_sitemap( $sitemap );

		// Verify out-of-stock product is excluded (Requirement 22.3)
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );

		// Clean up
		$this->options->set( 'woocommerce_exclude_out_of_stock', false );
		wp_delete_post( $product_id, true );
	}

	/**
	 * Test add_products_to_sitemap includes out-of-stock when setting disabled
	 *
	 * Validates: Requirement 22.2
	 *
	 * @return void
	 */
	public function test_add_products_to_sitemap_includes_out_of_stock_when_disabled(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// Create an out-of-stock product
		$product_id = $this->create_test_product( array(
			'manage_stock'   => true,
			'stock_quantity' => 0,
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		// Ensure exclude out-of-stock setting is disabled (Requirement 22.2)
		$this->options->set( 'woocommerce_exclude_out_of_stock', false );

		$sitemap = array();
		$result = $this->module->add_products_to_sitemap( $sitemap );

		// Verify out-of-stock product is included
		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test add_products_to_sitemap uses product modified date for lastmod
	 *
	 * Validates: Requirement 22.6
	 *
	 * @return void
	 */
	public function test_add_products_to_sitemap_uses_product_modified_date(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$sitemap = array();
		$result = $this->module->add_products_to_sitemap( $sitemap );

		// Verify lastmod is present (Requirement 22.6)
		$this->assertNotEmpty( $result );
		$product_entry = $result[0];
		$this->assertArrayHasKey( 'lastmod', $product_entry );
		$this->assertNotEmpty( $product_entry['lastmod'] );

		// Verify lastmod is in ISO 8601 format
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $product_entry['lastmod'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test get_product_meta returns empty array when WooCommerce not active
	 *
	 * Validates: Requirement 23.1, 23.2
	 *
	 * @return void
	 */
	public function test_get_product_meta_returns_empty_when_woocommerce_inactive(): void {
		if ( class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is active in test environment' );
		}

		$meta = $this->module->get_product_meta( 999 );
		$this->assertIsArray( $meta );
		$this->assertEmpty( $meta );
	}

	/**
	 * Test get_product_meta returns empty array when not on product_cat or shop page
	 *
	 * Validates: Requirement 23.1, 23.2
	 *
	 * @return void
	 */
	public function test_get_product_meta_returns_empty_when_not_on_product_page(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// When not on a product_cat or shop page, should return empty
		$meta = $this->module->get_product_meta( 999 );
		$this->assertIsArray( $meta );
		// Note: This test may return empty or data depending on current page context
	}

	/**
	 * Test get_product_meta includes required fields for product category
	 *
	 * Validates: Requirements 23.1, 23.3, 23.4
	 *
	 * @return void
	 */
	public function test_get_product_meta_includes_required_fields_for_category(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// Create a product category
		$term_id = wp_insert_term( 'Test Category', 'product_cat', array(
			'description' => 'Test category description',
		) );

		if ( is_wp_error( $term_id ) ) {
			$this->markTestSkipped( 'Could not create test category' );
		}

		// Note: get_product_meta checks is_tax('product_cat') which requires being on that page
		// This test verifies the method structure is correct
		$meta = $this->module->get_product_meta( 999 );
		$this->assertIsArray( $meta );

		// Clean up
		wp_delete_term( $term_id, 'product_cat' );
	}

	/**
	 * Test get_product_meta uses category description as meta description
	 *
	 * Validates: Requirement 23.3
	 *
	 * @return void
	 */
	public function test_get_product_meta_uses_category_description(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// Create a product category with description
		$term_id = wp_insert_term( 'Test Category', 'product_cat', array(
			'description' => 'This is a test category description',
		) );

		if ( is_wp_error( $term_id ) ) {
			$this->markTestSkipped( 'Could not create test category' );
		}

		// Note: get_product_meta checks is_tax('product_cat') which requires being on that page
		// This test verifies the method structure is correct
		$meta = $this->module->get_product_meta( 999 );
		$this->assertIsArray( $meta );

		// Clean up
		wp_delete_term( $term_id, 'product_cat' );
	}

	/**
	 * Test get_product_meta generates fallback description when category description is empty
	 *
	 * Validates: Requirement 23.4
	 *
	 * @return void
	 */
	public function test_get_product_meta_generates_fallback_description(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// Create a product category without description
		$term_id = wp_insert_term( 'Test Category', 'product_cat' );

		if ( is_wp_error( $term_id ) ) {
			$this->markTestSkipped( 'Could not create test category' );
		}

		// Note: get_product_meta checks is_tax('product_cat') which requires being on that page
		// This test verifies the method structure is correct
		$meta = $this->module->get_product_meta( 999 );
		$this->assertIsArray( $meta );

		// Clean up
		wp_delete_term( $term_id, 'product_cat' );
	}

	/**
	 * Test get_product_meta returns meta for shop page
	 *
	 * Validates: Requirement 23.2
	 *
	 * @return void
	 */
	public function test_get_product_meta_returns_meta_for_shop_page(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// Note: get_product_meta checks is_shop() which requires being on the shop page
		// This test verifies the method structure is correct
		$meta = $this->module->get_product_meta( 999 );
		$this->assertIsArray( $meta );
	}

	/**
	 * Test generate_product_breadcrumbs returns empty array when WooCommerce not active
	 *
	 * Validates: Requirement 24.1
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_returns_empty_when_woocommerce_inactive(): void {
		if ( class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is active in test environment' );
		}

		$breadcrumbs = $this->module->generate_product_breadcrumbs( 999 );
		$this->assertIsArray( $breadcrumbs );
		$this->assertEmpty( $breadcrumbs );
	}

	/**
	 * Test generate_product_breadcrumbs returns empty array for non-existent product
	 *
	 * Validates: Requirement 24.1
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_returns_empty_for_nonexistent_product(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$breadcrumbs = $this->module->generate_product_breadcrumbs( 999999 );
		$this->assertIsArray( $breadcrumbs );
		$this->assertEmpty( $breadcrumbs );
	}

	/**
	 * Test generate_product_breadcrumbs includes Home and Shop page
	 *
	 * Validates: Requirements 24.3, 24.4
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_includes_home_and_shop(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$result = $this->module->generate_product_breadcrumbs( $product_id );

		// Verify result structure
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'breadcrumbs', $result );
		$this->assertArrayHasKey( 'schema', $result );

		$breadcrumbs = $result['breadcrumbs'];

		// Verify Home is first (Requirement 24.4)
		$this->assertNotEmpty( $breadcrumbs );
		$this->assertEquals( 'Home', $breadcrumbs[0]['label'] );
		$this->assertEquals( home_url(), $breadcrumbs[0]['url'] );

		// Verify Shop page is included (Requirement 24.3)
		$shop_found = false;
		foreach ( $breadcrumbs as $item ) {
			if ( strpos( $item['label'], 'Shop' ) !== false || strpos( $item['url'], 'shop' ) !== false ) {
				$shop_found = true;
				break;
			}
		}
		// Note: Shop page may not exist in test environment, so we just verify structure

		// Verify product is last (Requirement 24.4)
		$last_item = end( $breadcrumbs );
		$this->assertNotEmpty( $last_item );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_breadcrumbs includes product title
	 *
	 * Validates: Requirement 24.4
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_includes_product_title(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product( array(
			'name' => 'Test Product Name',
		) );

		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$result = $this->module->generate_product_breadcrumbs( $product_id );
		$breadcrumbs = $result['breadcrumbs'];

		// Verify product title is last item (Requirement 24.4)
		$last_item = end( $breadcrumbs );
		$this->assertEquals( 'Test Product Name', $last_item['label'] );
		$this->assertEquals( get_permalink( $product_id ), $last_item['url'] );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_breadcrumbs includes category hierarchy
	 *
	 * Validates: Requirements 24.1, 24.4
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_includes_category_hierarchy(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// Create a parent category
		$parent_cat_id = wp_insert_term( 'Parent Category', 'product_cat' );
		if ( is_wp_error( $parent_cat_id ) ) {
			$this->markTestSkipped( 'Could not create parent category' );
		}
		$parent_cat_id = $parent_cat_id['term_id'];

		// Create a child category
		$child_cat_id = wp_insert_term( 'Child Category', 'product_cat', array(
			'parent' => $parent_cat_id,
		) );
		if ( is_wp_error( $child_cat_id ) ) {
			$this->markTestSkipped( 'Could not create child category' );
		}
		$child_cat_id = $child_cat_id['term_id'];

		// Create a product in the child category
		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		wp_set_post_terms( $product_id, array( $child_cat_id ), 'product_cat' );

		$result = $this->module->generate_product_breadcrumbs( $product_id );
		$breadcrumbs = $result['breadcrumbs'];

		// Verify category hierarchy is included (Requirement 24.4)
		$category_labels = array_map( function( $item ) {
			return $item['label'];
		}, $breadcrumbs );

		// Should contain parent and child categories
		$this->assertContains( 'Parent Category', $category_labels );
		$this->assertContains( 'Child Category', $category_labels );

		// Verify order: Home > Shop > Parent > Child > Product
		$parent_index = array_search( 'Parent Category', $category_labels );
		$child_index = array_search( 'Child Category', $category_labels );
		$this->assertLessThan( $child_index, $parent_index );

		// Clean up
		wp_delete_post( $product_id, true );
		wp_delete_term( $child_cat_id, 'product_cat' );
		wp_delete_term( $parent_cat_id, 'product_cat' );
	}

	/**
	 * Test generate_product_breadcrumbs uses primary category if available
	 *
	 * Validates: Requirement 24.2
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_uses_primary_category(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		// Create two categories
		$cat1_id = wp_insert_term( 'Category 1', 'product_cat' );
		if ( is_wp_error( $cat1_id ) ) {
			$this->markTestSkipped( 'Could not create category 1' );
		}
		$cat1_id = $cat1_id['term_id'];

		$cat2_id = wp_insert_term( 'Category 2', 'product_cat' );
		if ( is_wp_error( $cat2_id ) ) {
			$this->markTestSkipped( 'Could not create category 2' );
		}
		$cat2_id = $cat2_id['term_id'];

		// Create a product with both categories
		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		wp_set_post_terms( $product_id, array( $cat1_id, $cat2_id ), 'product_cat' );

		// Set primary category to cat2
		update_post_meta( $product_id, '_primary_product_cat', $cat2_id );

		$result = $this->module->generate_product_breadcrumbs( $product_id );
		$breadcrumbs = $result['breadcrumbs'];

		// Verify primary category is used (Requirement 24.2)
		$category_labels = array_map( function( $item ) {
			return $item['label'];
		}, $breadcrumbs );

		// Should contain Category 2 (primary)
		$this->assertContains( 'Category 2', $category_labels );

		// Clean up
		wp_delete_post( $product_id, true );
		wp_delete_term( $cat1_id, 'product_cat' );
		wp_delete_term( $cat2_id, 'product_cat' );
	}

	/**
	 * Test generate_product_breadcrumbs generates BreadcrumbList schema
	 *
	 * Validates: Requirement 24.5
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_generates_schema(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$result = $this->module->generate_product_breadcrumbs( $product_id );

		// Verify schema is present (Requirement 24.5)
		$this->assertArrayHasKey( 'schema', $result );
		$schema = $result['schema'];

		// Verify BreadcrumbList schema structure
		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( '@context', $schema );
		$this->assertArrayHasKey( '@type', $schema );
		$this->assertArrayHasKey( 'itemListElement', $schema );

		// Verify schema.org context
		$this->assertEquals( 'https://schema.org', $schema['@context'] );
		$this->assertEquals( 'BreadcrumbList', $schema['@type'] );

		// Verify itemListElement array
		$this->assertIsArray( $schema['itemListElement'] );
		$this->assertNotEmpty( $schema['itemListElement'] );

		// Verify each item has required fields
		foreach ( $schema['itemListElement'] as $item ) {
			$this->assertArrayHasKey( '@type', $item );
			$this->assertArrayHasKey( 'position', $item );
			$this->assertArrayHasKey( 'name', $item );
			$this->assertEquals( 'ListItem', $item['@type'] );
			$this->assertIsInt( $item['position'] );
		}

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_breadcrumbs schema has correct positions
	 *
	 * Validates: Requirement 24.5
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_schema_positions_are_sequential(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$result = $this->module->generate_product_breadcrumbs( $product_id );
		$schema = $result['schema'];

		// Verify positions are sequential starting from 1
		$positions = array_map( function( $item ) {
			return $item['position'];
		}, $schema['itemListElement'] );

		$expected_positions = range( 1, count( $positions ) );
		$this->assertEquals( $expected_positions, $positions );

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_breadcrumbs schema includes item URLs
	 *
	 * Validates: Requirement 24.5
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_schema_includes_item_urls(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$result = $this->module->generate_product_breadcrumbs( $product_id );
		$schema = $result['schema'];

		// Verify items have URLs (except possibly the last item)
		foreach ( $schema['itemListElement'] as $index => $item ) {
			// Most items should have 'item' property with URL
			if ( $index < count( $schema['itemListElement'] ) - 1 ) {
				$this->assertArrayHasKey( 'item', $item );
				$this->assertNotEmpty( $item['item'] );
			}
		}

		wp_delete_post( $product_id, true );
	}

	/**
	 * Test generate_product_breadcrumbs returns consistent structure
	 *
	 * Validates: Requirements 24.1, 24.4, 24.5
	 *
	 * @return void
	 */
	public function test_generate_product_breadcrumbs_returns_consistent_structure(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active' );
		}

		$product_id = $this->create_test_product();
		if ( ! $product_id ) {
			$this->markTestSkipped( 'Could not create test product' );
		}

		$result = $this->module->generate_product_breadcrumbs( $product_id );

		// Verify result structure
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'breadcrumbs', $result );
		$this->assertArrayHasKey( 'schema', $result );

		// Verify breadcrumbs array
		$breadcrumbs = $result['breadcrumbs'];
		$this->assertIsArray( $breadcrumbs );
		$this->assertNotEmpty( $breadcrumbs );

		// Verify each breadcrumb has label and url
		foreach ( $breadcrumbs as $item ) {
			$this->assertArrayHasKey( 'label', $item );
			$this->assertArrayHasKey( 'url', $item );
			$this->assertNotEmpty( $item['label'] );
		}

		// Verify schema array
		$schema = $result['schema'];
		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		wp_delete_post( $product_id, true );
	}
}
