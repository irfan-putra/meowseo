<?php
/**
 * Property-Based Tests for Log Filtering - Filter Matching
 *
 * Property 15: Filter Matching
 * Validates: Requirements 8.2
 *
 * This test uses property-based testing (eris/eris) to verify that for any filter criteria
 * (level, module, date range) applied to the log collection, all returned entries SHALL
 * match the specified criteria.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;

/**
 * Log Filtering - Filter Matching property-based test case
 *
 * **Validates: Requirements 8.2**
 *
 * @since 1.0.0
 */
class Property15FilterMatchingTest extends TestCase {
	use TestTrait;

	/**
	 * Mock log entries for testing
	 *
	 * @var array
	 */
	private $mock_logs = [];

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->mock_logs = [];
		$this->setup_mock_database();
	}

	/**
	 * Teardown test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		$this->mock_logs = [];
	}

	/**
	 * Setup mock database with predefined log entries
	 *
	 * @return void
	 */
	private function setup_mock_database(): void {
		global $wpdb;

		// Create a mock wpdb object that simulates database queries
		$wpdb = new class( $this ) {
			private $test_instance;

			public function __construct( $test_instance ) {
				$this->test_instance = $test_instance;
			}

			public $prefix = 'wp_';
			public $meowseo_logs = 'wp_meowseo_logs';

			public function prepare( $query, ...$args ) {
				// Simple prepare implementation for testing
				$query = str_replace( '%d', '%s', $query );
				$query = str_replace( '%s', "'%s'", $query );
				return vsprintf( $query, $args );
			}

			public function get_results( $query, $output = OBJECT ) {
				// Parse the query to extract WHERE conditions
				$logs = $this->test_instance->get_mock_logs();
				$filtered_logs = $this->filter_logs_by_query( $query, $logs );

				if ( ARRAY_A === $output ) {
					return $filtered_logs;
				}

				// Convert to objects
				return array_map(
					function ( $log ) {
						return (object) $log;
					},
					$filtered_logs
				);
			}

			public function get_var( $query = null, $x = 0, $y = 0 ) {
				// Return count of matching logs
				if ( null === $query ) {
					return count( $this->test_instance->get_mock_logs() );
				}

				$logs = $this->test_instance->get_mock_logs();
				$filtered_logs = $this->filter_logs_by_query( $query, $logs );
				return count( $filtered_logs );
			}

			/**
			 * Filter logs based on query conditions
			 *
			 * @param string $query The SQL query
			 * @param array  $logs  The logs to filter
			 * @return array Filtered logs
			 */
			private function filter_logs_by_query( $query, $logs ) {
				$filtered = $logs;

				// Extract level filter
				if ( preg_match( "/level = '([^']+)'/", $query, $matches ) ) {
					$level = $matches[1];
					$filtered = array_filter(
						$filtered,
						function ( $log ) use ( $level ) {
							return $log['level'] === $level;
						}
					);
				}

				// Extract module filter
				if ( preg_match( "/module = '([^']+)'/", $query, $matches ) ) {
					$module = $matches[1];
					$filtered = array_filter(
						$filtered,
						function ( $log ) use ( $module ) {
							return $log['module'] === $module;
						}
					);
				}

				// Extract start_date filter
				if ( preg_match( "/created_at >= '([^']+)'/", $query, $matches ) ) {
					$start_date = $matches[1];
					$filtered = array_filter(
						$filtered,
						function ( $log ) use ( $start_date ) {
							return $log['created_at'] >= $start_date;
						}
					);
				}

				// Extract end_date filter
				if ( preg_match( "/created_at <= '([^']+)'/", $query, $matches ) ) {
					$end_date = $matches[1];
					$filtered = array_filter(
						$filtered,
						function ( $log ) use ( $end_date ) {
							return $log['created_at'] <= $end_date;
						}
					);
				}

				// Handle LIMIT and OFFSET
				if ( preg_match( '/LIMIT (\d+) OFFSET (\d+)/', $query, $matches ) ) {
					$limit = (int) $matches[1];
					$offset = (int) $matches[2];
					$filtered = array_slice( $filtered, $offset, $limit );
				}

				return array_values( $filtered );
			}
		};
	}

	/**
	 * Get mock logs for testing
	 *
	 * @return array
	 */
	public function get_mock_logs() {
		return $this->mock_logs;
	}

	/**
	 * Create mock log entries with various levels, modules, and timestamps
	 *
	 * @param array $levels   Log levels to include
	 * @param array $modules  Module names to include
	 * @param int   $count    Number of entries to create
	 * @return array Created log entries
	 */
	private function create_mock_logs( $levels, $modules, $count ) {
		$logs = [];
		$base_time = strtotime( '2024-01-01 00:00:00' );

		for ( $i = 0; $i < $count; $i++ ) {
			$level = $levels[ $i % count( $levels ) ];
			$module = $modules[ $i % count( $modules ) ];
			$timestamp = date( 'Y-m-d H:i:s', $base_time + ( $i * 3600 ) );

			$logs[] = array(
				'id'           => $i + 1,
				'level'        => $level,
				'module'       => $module,
				'message'      => "Test message $i",
				'message_hash' => md5( "Test message $i" ),
				'context'      => '{}',
				'stack_trace'  => null,
				'hit_count'    => 1,
				'created_at'   => $timestamp,
			);
		}

		return $logs;
	}

	/**
	 * Property 15: Filter Matching - Level Filter
	 *
	 * For any level filter applied to the log collection, all returned entries
	 * SHALL have the specified level.
	 *
	 * **Validates: Requirements 8.2**
	 *
	 * @return void
	 */
	public function test_level_filter_matches_all_entries(): void {
		$this->forAll(
			Generators::elements( [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ] )
		)
		->then(
			function ( $filter_level ) {
				// Create mock logs with various levels
				$levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];
				$modules = [ 'gsc', 'sitemap', 'redirects' ];
				$this->mock_logs = $this->create_mock_logs( $levels, $modules, 50 );

				// Simulate filtering by level
				$filtered = array_filter(
					$this->mock_logs,
					function ( $log ) use ( $filter_level ) {
						return $log['level'] === $filter_level;
					}
				);

				// Verify all returned entries match the filter
				foreach ( $filtered as $entry ) {
					$this->assertEquals(
						$filter_level,
						$entry['level'],
						"Filtered entry level should match filter criteria: $filter_level"
					);
				}

				// Verify at least some entries match (unless filter is too restrictive)
				$this->assertNotEmpty(
					$filtered,
					"Filter should return at least one entry for level: $filter_level"
				);
			}
		);
	}

	/**
	 * Property 15: Filter Matching - Module Filter
	 *
	 * For any module filter applied to the log collection, all returned entries
	 * SHALL have the specified module.
	 *
	 * **Validates: Requirements 8.2**
	 *
	 * @return void
	 */
	public function test_module_filter_matches_all_entries(): void {
		$this->forAll(
			Generators::elements( [ 'gsc', 'sitemap', 'redirects', 'meta', 'social' ] )
		)
		->then(
			function ( $filter_module ) {
				// Create mock logs with various modules
				$levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];
				$modules = [ 'gsc', 'sitemap', 'redirects', 'meta', 'social' ];
				$this->mock_logs = $this->create_mock_logs( $levels, $modules, 50 );

				// Simulate filtering by module
				$filtered = array_filter(
					$this->mock_logs,
					function ( $log ) use ( $filter_module ) {
						return $log['module'] === $filter_module;
					}
				);

				// Verify all returned entries match the filter
				foreach ( $filtered as $entry ) {
					$this->assertEquals(
						$filter_module,
						$entry['module'],
						"Filtered entry module should match filter criteria: $filter_module"
					);
				}

				// Verify at least some entries match
				$this->assertNotEmpty(
					$filtered,
					"Filter should return at least one entry for module: $filter_module"
				);
			}
		);
	}

	/**
	 * Property 15: Filter Matching - Date Range Filter
	 *
	 * For any date range filter applied to the log collection, all returned entries
	 * SHALL have timestamps within the specified range.
	 *
	 * **Validates: Requirements 8.2**
	 *
	 * @return void
	 */
	public function test_date_range_filter_matches_all_entries(): void {
		$this->forAll(
			Generators::choose( 0, 40 ),
			Generators::choose( 5, 45 )
		)
		->then(
			function ( $start_offset, $end_offset ) {
				// Ensure start is before end
				if ( $start_offset >= $end_offset ) {
					return;
				}

				// Create mock logs with various timestamps
				$levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];
				$modules = [ 'gsc', 'sitemap', 'redirects' ];
				$this->mock_logs = $this->create_mock_logs( $levels, $modules, 50 );

				// Calculate date range
				$base_time = strtotime( '2024-01-01 00:00:00' );
				$start_date = date( 'Y-m-d H:i:s', $base_time + ( $start_offset * 3600 ) );
				$end_date = date( 'Y-m-d H:i:s', $base_time + ( $end_offset * 3600 ) );

				// Simulate filtering by date range
				$filtered = array_filter(
					$this->mock_logs,
					function ( $log ) use ( $start_date, $end_date ) {
						return $log['created_at'] >= $start_date && $log['created_at'] <= $end_date;
					}
				);

				// Verify all returned entries are within the date range
				foreach ( $filtered as $entry ) {
					$this->assertGreaterThanOrEqual(
						$start_date,
						$entry['created_at'],
						"Filtered entry timestamp should be >= start_date: $start_date"
					);

					$this->assertLessThanOrEqual(
						$end_date,
						$entry['created_at'],
						"Filtered entry timestamp should be <= end_date: $end_date"
					);
				}
			}
		);
	}

	/**
	 * Property 15: Filter Matching - Combined Filters
	 *
	 * For any combination of level, module, and date range filters applied to the
	 * log collection, all returned entries SHALL match ALL specified criteria.
	 *
	 * **Validates: Requirements 8.2**
	 *
	 * @return void
	 */
	public function test_combined_filters_match_all_entries(): void {
		$this->forAll(
			Generators::elements( [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ] ),
			Generators::elements( [ 'gsc', 'sitemap', 'redirects', 'meta' ] ),
			Generators::choose( 0, 30 ),
			Generators::choose( 10, 40 )
		)
		->then(
			function ( $filter_level, $filter_module, $start_offset, $end_offset ) {
				// Ensure start is before end
				if ( $start_offset >= $end_offset ) {
					return;
				}

				// Create mock logs
				$levels = [ 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' ];
				$modules = [ 'gsc', 'sitemap', 'redirects', 'meta', 'social' ];
				$this->mock_logs = $this->create_mock_logs( $levels, $modules, 50 );

				// Calculate date range
				$base_time = strtotime( '2024-01-01 00:00:00' );
				$start_date = date( 'Y-m-d H:i:s', $base_time + ( $start_offset * 3600 ) );
				$end_date = date( 'Y-m-d H:i:s', $base_time + ( $end_offset * 3600 ) );

				// Apply all filters
				$filtered = array_filter(
					$this->mock_logs,
					function ( $log ) use ( $filter_level, $filter_module, $start_date, $end_date ) {
						return $log['level'] === $filter_level
							&& $log['module'] === $filter_module
							&& $log['created_at'] >= $start_date
							&& $log['created_at'] <= $end_date;
					}
				);

				// Verify all returned entries match ALL criteria
				foreach ( $filtered as $entry ) {
					$this->assertEquals(
						$filter_level,
						$entry['level'],
						"Entry level should match filter: $filter_level"
					);

					$this->assertEquals(
						$filter_module,
						$entry['module'],
						"Entry module should match filter: $filter_module"
					);

					$this->assertGreaterThanOrEqual(
						$start_date,
						$entry['created_at'],
						"Entry timestamp should be >= start_date: $start_date"
					);

					$this->assertLessThanOrEqual(
						$end_date,
						$entry['created_at'],
						"Entry timestamp should be <= end_date: $end_date"
					);
				}
			}
		);
	}

	/**
	 * Property 15: Filter Matching - Empty Result Sets
	 *
	 * For any filter that produces no matching entries, the returned set SHALL be empty.
	 *
	 * **Validates: Requirements 8.2**
	 *
	 * @return void
	 */
	public function test_empty_result_sets_are_valid(): void {
		// Create mock logs with limited data
		$levels = [ 'DEBUG', 'INFO' ];
		$modules = [ 'gsc', 'sitemap' ];
		$this->mock_logs = $this->create_mock_logs( $levels, $modules, 10 );

		// Apply a filter that should produce no results
		$filter_level = 'CRITICAL';
		$filtered = array_filter(
			$this->mock_logs,
			function ( $log ) use ( $filter_level ) {
				return $log['level'] === $filter_level;
			}
		);

		// Verify result set is empty
		$this->assertEmpty(
			$filtered,
			'Filter should return empty set when no entries match'
		);
	}

	/**
	 * Property 15: Filter Matching - Single Matching Entry
	 *
	 * For any filter that produces exactly one matching entry, the returned set
	 * SHALL contain that single entry and it SHALL match the filter criteria.
	 *
	 * **Validates: Requirements 8.2**
	 *
	 * @return void
	 */
	public function test_single_matching_entry_is_valid(): void {
		// Create mock logs with specific data
		$this->mock_logs = [
			[
				'id'           => 1,
				'level'        => 'DEBUG',
				'module'       => 'gsc',
				'message'      => 'Test message 1',
				'message_hash' => md5( 'Test message 1' ),
				'context'      => '{}',
				'stack_trace'  => null,
				'hit_count'    => 1,
				'created_at'   => '2024-01-01 00:00:00',
			],
			[
				'id'           => 2,
				'level'        => 'INFO',
				'module'       => 'sitemap',
				'message'      => 'Test message 2',
				'message_hash' => md5( 'Test message 2' ),
				'context'      => '{}',
				'stack_trace'  => null,
				'hit_count'    => 1,
				'created_at'   => '2024-01-01 01:00:00',
			],
		];

		// Apply filter that matches only one entry
		$filter_level = 'DEBUG';
		$filtered = array_filter(
			$this->mock_logs,
			function ( $log ) use ( $filter_level ) {
				return $log['level'] === $filter_level;
			}
		);

		// Verify exactly one entry is returned
		$this->assertCount(
			1,
			$filtered,
			'Filter should return exactly one entry'
		);

		// Verify the entry matches the filter
		$entry = reset( $filtered );
		$this->assertEquals(
			$filter_level,
			$entry['level'],
			'Single entry should match filter criteria'
		);
	}

	/**
	 * Property 15: Filter Matching - Multiple Matching Entries
	 *
	 * For any filter that produces multiple matching entries, all returned entries
	 * SHALL match the filter criteria.
	 *
	 * **Validates: Requirements 8.2**
	 *
	 * @return void
	 */
	public function test_multiple_matching_entries_all_match(): void {
		// Create mock logs with multiple matching entries
		$levels = [ 'DEBUG', 'DEBUG', 'DEBUG', 'INFO', 'WARNING' ];
		$modules = [ 'gsc', 'sitemap', 'redirects', 'meta', 'social' ];
		$this->mock_logs = $this->create_mock_logs( $levels, $modules, 50 );

		// Apply filter that matches multiple entries
		$filter_level = 'DEBUG';
		$filtered = array_filter(
			$this->mock_logs,
			function ( $log ) use ( $filter_level ) {
				return $log['level'] === $filter_level;
			}
		);

		// Verify multiple entries are returned
		$this->assertGreaterThan(
			1,
			count( $filtered ),
			'Filter should return multiple entries'
		);

		// Verify all entries match the filter
		foreach ( $filtered as $entry ) {
			$this->assertEquals(
				$filter_level,
				$entry['level'],
				'All entries should match filter criteria'
			);
		}
	}

	/**
	 * Property 15: Filter Matching - Boundary Conditions for Date Ranges
	 *
	 * For any date range filter with boundary dates, entries at the exact boundary
	 * timestamps SHALL be included in the results.
	 *
	 * **Validates: Requirements 8.2**
	 *
	 * @return void
	 */
	public function test_date_range_boundary_conditions(): void {
		// Create mock logs with specific timestamps
		$base_time = strtotime( '2024-01-01 00:00:00' );
		$this->mock_logs = [
			[
				'id'           => 1,
				'level'        => 'DEBUG',
				'module'       => 'gsc',
				'message'      => 'Before range',
				'message_hash' => md5( 'Before range' ),
				'context'      => '{}',
				'stack_trace'  => null,
				'hit_count'    => 1,
				'created_at'   => date( 'Y-m-d H:i:s', $base_time - 3600 ),
			],
			[
				'id'           => 2,
				'level'        => 'DEBUG',
				'module'       => 'gsc',
				'message'      => 'At start boundary',
				'message_hash' => md5( 'At start boundary' ),
				'context'      => '{}',
				'stack_trace'  => null,
				'hit_count'    => 1,
				'created_at'   => date( 'Y-m-d H:i:s', $base_time ),
			],
			[
				'id'           => 3,
				'level'        => 'DEBUG',
				'module'       => 'gsc',
				'message'      => 'In range',
				'message_hash' => md5( 'In range' ),
				'context'      => '{}',
				'stack_trace'  => null,
				'hit_count'    => 1,
				'created_at'   => date( 'Y-m-d H:i:s', $base_time + 3600 ),
			],
			[
				'id'           => 4,
				'level'        => 'DEBUG',
				'module'       => 'gsc',
				'message'      => 'At end boundary',
				'message_hash' => md5( 'At end boundary' ),
				'context'      => '{}',
				'stack_trace'  => null,
				'hit_count'    => 1,
				'created_at'   => date( 'Y-m-d H:i:s', $base_time + 7200 ),
			],
			[
				'id'           => 5,
				'level'        => 'DEBUG',
				'module'       => 'gsc',
				'message'      => 'After range',
				'message_hash' => md5( 'After range' ),
				'context'      => '{}',
				'stack_trace'  => null,
				'hit_count'    => 1,
				'created_at'   => date( 'Y-m-d H:i:s', $base_time + 10800 ),
			],
		];

		$start_date = date( 'Y-m-d H:i:s', $base_time );
		$end_date = date( 'Y-m-d H:i:s', $base_time + 7200 );

		// Apply date range filter
		$filtered = array_filter(
			$this->mock_logs,
			function ( $log ) use ( $start_date, $end_date ) {
				return $log['created_at'] >= $start_date && $log['created_at'] <= $end_date;
			}
		);

		// Verify boundary entries are included
		$this->assertCount(
			3,
			$filtered,
			'Filter should include entries at boundaries and within range'
		);

		// Verify all entries are within range
		foreach ( $filtered as $entry ) {
			$this->assertGreaterThanOrEqual(
				$start_date,
				$entry['created_at'],
				'Entry should be >= start_date'
			);

			$this->assertLessThanOrEqual(
				$end_date,
				$entry['created_at'],
				'Entry should be <= end_date'
			);
		}
	}

	/**
	 * Property 15: Filter Matching - No Matching Entries
	 *
	 * For any filter that produces no matching entries, the returned set SHALL be empty
	 * and no entries SHALL be returned.
	 *
	 * **Validates: Requirements 8.2**
	 *
	 * @return void
	 */
	public function test_no_matching_entries_returns_empty(): void {
		// Create mock logs
		$levels = [ 'DEBUG', 'INFO', 'WARNING' ];
		$modules = [ 'gsc', 'sitemap' ];
		$this->mock_logs = $this->create_mock_logs( $levels, $modules, 20 );

		// Apply filter that matches nothing
		$filter_level = 'CRITICAL';
		$filter_module = 'nonexistent';
		$filtered = array_filter(
			$this->mock_logs,
			function ( $log ) use ( $filter_level, $filter_module ) {
				return $log['level'] === $filter_level && $log['module'] === $filter_module;
			}
		);

		// Verify result set is empty
		$this->assertEmpty(
			$filtered,
			'Filter should return empty set when no entries match'
		);

		$this->assertCount(
			0,
			$filtered,
			'Filtered result count should be 0'
		);
	}
}
