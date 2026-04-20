<?php
/**
 * Tests for CSV Generator class.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Tests\Modules\Bulk;

use MeowSEO\Modules\Bulk\CSV_Generator;
use PHPUnit\Framework\TestCase;

/**
 * CSV Generator test class.
 */
class CSVGeneratorTest extends TestCase {

	/**
	 * Test basic CSV generation.
	 *
	 * @return void
	 */
	public function test_basic_csv_generation(): void {
		$generator = new CSV_Generator();

		$generator->add_row( array( 'ID', 'Title', 'URL' ) );
		$generator->add_row( array( '1', 'Test Post', 'https://example.com/test' ) );

		$csv = $generator->generate();

		$this->assertStringContainsString( 'ID,Title,URL', $csv );
		$this->assertStringContainsString( '1,Test Post,https://example.com/test', $csv );
	}

	/**
	 * Test CSV escaping with commas.
	 *
	 * @return void
	 */
	public function test_csv_escaping_commas(): void {
		$generator = new CSV_Generator();

		$generator->add_row( array( 'Title with, comma', 'Normal' ) );

		$csv = $generator->generate();

		// Field with comma should be quoted.
		$this->assertStringContainsString( '"Title with, comma"', $csv );
	}

	/**
	 * Test CSV escaping with quotes.
	 *
	 * @return void
	 */
	public function test_csv_escaping_quotes(): void {
		$generator = new CSV_Generator();

		$generator->add_row( array( 'Title with "quotes"', 'Normal' ) );

		$csv = $generator->generate();

		// Quotes should be escaped by doubling them.
		$this->assertStringContainsString( '"Title with ""quotes"""', $csv );
	}

	/**
	 * Test CSV escaping with newlines.
	 *
	 * @return void
	 */
	public function test_csv_escaping_newlines(): void {
		$generator = new CSV_Generator();

		$generator->add_row( array( "Title with\nnewline", 'Normal' ) );

		$csv = $generator->generate();

		// Field with newline should be quoted.
		$this->assertStringContainsString( '"Title with', $csv );
	}

	/**
	 * Test RFC 4180 compliance with complex data.
	 *
	 * @return void
	 */
	public function test_rfc4180_compliance(): void {
		$generator = new CSV_Generator();

		$generator->add_row( array( 'ID', 'Title', 'Description' ) );
		$generator->add_row( array( '1', 'Simple', 'No special chars' ) );
		$generator->add_row( array( '2', 'With, comma', 'Description with "quotes"' ) );
		$generator->add_row( array( '3', "With\nnewline", 'Normal' ) );

		$csv = $generator->generate();

		// Parse CSV to verify it's valid.
		$lines = explode( "\r\n", trim( $csv ) );

		$this->assertCount( 4, $lines );

		// Verify header.
		$this->assertEquals( 'ID,Title,Description', $lines[0] );

		// Verify simple row.
		$this->assertEquals( '1,Simple,No special chars', $lines[1] );

		// Verify complex row with comma and quotes.
		$this->assertStringContainsString( '"With, comma"', $lines[2] );
		$this->assertStringContainsString( '"Description with ""quotes"""', $lines[2] );
	}

	/**
	 * Test empty CSV generation.
	 *
	 * @return void
	 */
	public function test_empty_csv(): void {
		$generator = new CSV_Generator();

		$csv = $generator->generate();

		$this->assertEquals( '', $csv );
	}

	/**
	 * Test multiple rows.
	 *
	 * @return void
	 */
	public function test_multiple_rows(): void {
		$generator = new CSV_Generator();

		for ( $i = 1; $i <= 5; $i++ ) {
			$generator->add_row( array( $i, "Post $i", "URL $i" ) );
		}

		$csv = $generator->generate();

		// Verify all rows are present.
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->assertStringContainsString( "Post $i", $csv );
		}
	}
}
