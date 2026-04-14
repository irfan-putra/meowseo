<?php
/**
 * Property-Based Tests for Module Boot Continuation
 *
 * Property 9: Module Boot Continuation
 * Validates: Requirements 4.4
 *
 * This test uses property-based testing to verify that for any module that throws
 * an exception during boot(), the Module_Manager SHALL continue attempting to boot
 * subsequent modules in the list.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Module_Manager;
use MeowSEO\Options;
use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\Logger;

/**
 * Module Boot Continuation property-based test case
 *
 * @since 1.0.0
 */
class Property9ModuleBootContinuationTest extends TestCase {
	use TestTrait;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Clear mock logs before each test
		global $meowseo_test_logs;
		$meowseo_test_logs = [];
		// Mock the database to capture log entries
		$this->setup_mock_database();
	}

	/**
	 * Teardown test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		// Clear mock logs after each test
		global $meowseo_test_logs;
		$meowseo_test_logs = [];
	}

	/**
	 * Setup mock database to capture log entries
	 *
	 * @return void
	 */
	private function setup_mock_database(): void {
		global $wpdb;

		// Create a mock wpdb object that captures log entries
		$wpdb = new class {
			public $prefix = 'wp_';
			public $meowseo_logs = 'wp_meowseo_logs';

			public function prepare( $query, ...$args ) {
				// Simple prepare implementation for testing
				$query = str_replace( '%d', '%s', $query );
				$query = str_replace( '%s', "'%s'", $query );
				return vsprintf( $query, $args );
			}

			public function get_results( $query, $output = OBJECT ) {
				return [];
			}

			public function get_row( $query, $output = OBJECT ) {
				return null;
			}

			public function get_var( $query = null, $x = 0, $y = 0 ) {
				// Return a count that's always under the limit to avoid cleanup
				return 100;
			}

			public function insert( $table, $data, $format = null ) {
				// Capture the log entry
				if ( strpos( $table, 'meowseo_logs' ) !== false ) {
					global $meowseo_test_logs;
					$meowseo_test_logs[] = $data;
					return true;
				}
				return false;
			}

			public function query( $query ) {
				return true;
			}
		};
	}

	/**
	 * Create a mock module that can be configured to throw or succeed
	 *
	 * @param string $module_id Module ID.
	 * @param bool   $should_throw Whether the module should throw an exception.
	 * @param string $exception_message Exception message if throwing.
	 * @return Module Mock module instance.
	 */
	private function create_mock_module( string $module_id, bool $should_throw = false, string $exception_message = 'Test exception' ): Module {
		return new class( $module_id, $should_throw, $exception_message ) implements Module {
			private string $id;
			private bool $throw;
			private string $message;
			public bool $boot_called = false;

			public function __construct( string $id, bool $throw, string $message ) {
				$this->id = $id;
				$this->throw = $throw;
				$this->message = $message;
			}

			public function boot(): void {
				$this->boot_called = true;
				if ( $this->throw ) {
					throw new \Exception( $this->message );
				}
			}

			public function get_id(): string {
				return $this->id;
			}

			public function was_boot_called(): bool {
				return $this->boot_called;
			}
		};
	}

	/**
	 * Property 9: Module Boot Continuation - First module throws, subsequent boot
	 *
	 * When the first module throws an exception during boot(), subsequent modules
	 * SHALL still be booted.
	 *
	 * **Validates: Requirements 4.4**
	 *
	 * @return void
	 */
	public function test_first_module_throws_subsequent_modules_boot(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 50 ),
			Generators::string( 'a-zA-Z0-9 ', 1, 50 )
		)
		->then(
			function ( string $exception_msg, string $module_name ) {
				// Skip empty strings
				if ( empty( trim( $exception_msg ) ) || empty( trim( $module_name ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create mock modules
				$module1 = $this->create_mock_module( 'module1', true, $exception_msg );
				$module2 = $this->create_mock_module( 'module2', false );
				$module3 = $this->create_mock_module( 'module3', false );

				// Create a mock Module_Manager with our mock modules
				$manager = $this->create_manager_with_modules( [ $module1, $module2, $module3 ] );

				// Boot the manager
				$manager->boot();

				// Verify all modules' boot() was called
				$this->assertTrue(
					$module1->was_boot_called(),
					'First module boot() should be called even if it throws'
				);

				$this->assertTrue(
					$module2->was_boot_called(),
					'Second module boot() should be called after first throws'
				);

				$this->assertTrue(
					$module3->was_boot_called(),
					'Third module boot() should be called after first throws'
				);

				// Verify exception was logged
				$this->assertNotEmpty(
					$meowseo_test_logs,
					'Exception should be logged'
				);

				// Verify the logged exception contains the error message
				$logged_exception = false;
				foreach ( $meowseo_test_logs as $log ) {
					if ( strpos( $log['message'], 'Failed to boot module' ) !== false ) {
						$logged_exception = true;
						break;
					}
				}

				$this->assertTrue(
					$logged_exception,
					'Exception should be logged via Logger::error()'
				);
			}
		);
	}

	/**
	 * Property 9: Module Boot Continuation - Middle module throws, remaining boot
	 *
	 * When a middle module throws an exception during boot(), remaining modules
	 * SHALL still be booted.
	 *
	 * **Validates: Requirements 4.4**
	 *
	 * @return void
	 */
	public function test_middle_module_throws_remaining_modules_boot(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 50 )
		)
		->then(
			function ( string $exception_msg ) {
				// Skip empty strings
				if ( empty( trim( $exception_msg ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create mock modules
				$module1 = $this->create_mock_module( 'module1', false );
				$module2 = $this->create_mock_module( 'module2', true, $exception_msg );
				$module3 = $this->create_mock_module( 'module3', false );
				$module4 = $this->create_mock_module( 'module4', false );

				// Create a mock Module_Manager with our mock modules
				$manager = $this->create_manager_with_modules( [ $module1, $module2, $module3, $module4 ] );

				// Boot the manager
				$manager->boot();

				// Verify all modules' boot() was called
				$this->assertTrue(
					$module1->was_boot_called(),
					'First module boot() should be called'
				);

				$this->assertTrue(
					$module2->was_boot_called(),
					'Middle module boot() should be called even if it throws'
				);

				$this->assertTrue(
					$module3->was_boot_called(),
					'Module after throwing module should be called'
				);

				$this->assertTrue(
					$module4->was_boot_called(),
					'Last module boot() should be called after middle throws'
				);
			}
		);
	}

	/**
	 * Property 9: Module Boot Continuation - Last module throws, all previous boot
	 *
	 * When the last module throws an exception during boot(), all previous modules
	 * SHALL have already been booted.
	 *
	 * **Validates: Requirements 4.4**
	 *
	 * @return void
	 */
	public function test_last_module_throws_all_previous_boot(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 50 )
		)
		->then(
			function ( string $exception_msg ) {
				// Skip empty strings
				if ( empty( trim( $exception_msg ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create mock modules
				$module1 = $this->create_mock_module( 'module1', false );
				$module2 = $this->create_mock_module( 'module2', false );
				$module3 = $this->create_mock_module( 'module3', true, $exception_msg );

				// Create a mock Module_Manager with our mock modules
				$manager = $this->create_manager_with_modules( [ $module1, $module2, $module3 ] );

				// Boot the manager
				$manager->boot();

				// Verify all modules' boot() was called
				$this->assertTrue(
					$module1->was_boot_called(),
					'First module boot() should be called'
				);

				$this->assertTrue(
					$module2->was_boot_called(),
					'Second module boot() should be called'
				);

				$this->assertTrue(
					$module3->was_boot_called(),
					'Last module boot() should be called even if it throws'
				);
			}
		);
	}

	/**
	 * Property 9: Module Boot Continuation - Multiple modules throw, all logged
	 *
	 * When multiple modules throw exceptions during boot(), all exceptions
	 * SHALL be logged but booting SHALL continue.
	 *
	 * **Validates: Requirements 4.4**
	 *
	 * @return void
	 */
	public function test_multiple_modules_throw_all_logged(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 50 ),
			Generators::string( 'a-zA-Z0-9 ', 1, 50 )
		)
		->then(
			function ( string $exception_msg1, string $exception_msg2 ) {
				// Skip empty strings
				if ( empty( trim( $exception_msg1 ) ) || empty( trim( $exception_msg2 ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create mock modules - multiple throwing
				$module1 = $this->create_mock_module( 'module1', true, $exception_msg1 );
				$module2 = $this->create_mock_module( 'module2', false );
				$module3 = $this->create_mock_module( 'module3', true, $exception_msg2 );
				$module4 = $this->create_mock_module( 'module4', false );

				// Create a mock Module_Manager with our mock modules
				$manager = $this->create_manager_with_modules( [ $module1, $module2, $module3, $module4 ] );

				// Boot the manager
				$manager->boot();

				// Verify all modules' boot() was called
				$this->assertTrue(
					$module1->was_boot_called(),
					'First throwing module boot() should be called'
				);

				$this->assertTrue(
					$module2->was_boot_called(),
					'Module between throwing modules should be called'
				);

				$this->assertTrue(
					$module3->was_boot_called(),
					'Second throwing module boot() should be called'
				);

				$this->assertTrue(
					$module4->was_boot_called(),
					'Module after throwing modules should be called'
				);

				// Verify both exceptions were logged
				$error_count = 0;
				foreach ( $meowseo_test_logs as $log ) {
					if ( strpos( $log['message'], 'Failed to boot module' ) !== false ) {
						$error_count++;
					}
				}

				$this->assertGreaterThanOrEqual(
					2,
					$error_count,
					'Both exceptions should be logged'
				);
			}
		);
	}

	/**
	 * Property 9: Module Boot Continuation - Mix of success and failure
	 *
	 * When a mix of successful and failing modules are booted, all modules
	 * SHALL be attempted regardless of exceptions.
	 *
	 * **Validates: Requirements 4.4**
	 *
	 * @return void
	 */
	public function test_mix_of_successful_and_failing_modules(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9 ', 1, 50 )
		)
		->then(
			function ( string $exception_msg ) {
				// Skip empty strings
				if ( empty( trim( $exception_msg ) ) ) {
					return;
				}

				// Clear previous logs
				global $meowseo_test_logs;
				$meowseo_test_logs = [];

				// Create mock modules - alternating success and failure
				$module1 = $this->create_mock_module( 'module1', false );
				$module2 = $this->create_mock_module( 'module2', true, $exception_msg );
				$module3 = $this->create_mock_module( 'module3', false );
				$module4 = $this->create_mock_module( 'module4', true, $exception_msg );
				$module5 = $this->create_mock_module( 'module5', false );

				// Create a mock Module_Manager with our mock modules
				$manager = $this->create_manager_with_modules( [ $module1, $module2, $module3, $module4, $module5 ] );

				// Boot the manager
				$manager->boot();

				// Verify all modules' boot() was called
				$this->assertTrue( $module1->was_boot_called(), 'Module 1 should boot' );
				$this->assertTrue( $module2->was_boot_called(), 'Module 2 should boot' );
				$this->assertTrue( $module3->was_boot_called(), 'Module 3 should boot' );
				$this->assertTrue( $module4->was_boot_called(), 'Module 4 should boot' );
				$this->assertTrue( $module5->was_boot_called(), 'Module 5 should boot' );

				// Verify exceptions were logged
				$error_count = 0;
				foreach ( $meowseo_test_logs as $log ) {
					if ( strpos( $log['message'], 'Failed to boot module' ) !== false ) {
						$error_count++;
					}
				}

				$this->assertGreaterThanOrEqual(
					2,
					$error_count,
					'Both exceptions should be logged'
				);
			}
		);
	}

	/**
	 * Create a Module_Manager with mock modules for testing
	 *
	 * @param array<Module> $modules Array of mock modules.
	 * @return Module_Manager Manager instance with mock modules.
	 */
	private function create_manager_with_modules( array $modules ): Module_Manager {
		// Create a mock Options object
		$options = $this->createMock( Options::class );
		$options->method( 'get_enabled_modules' )->willReturn( array_map( fn( $m ) => $m->get_id(), $modules ) );

		// Create the manager
		$manager = new Module_Manager( $options );

		// Inject mock modules using reflection
		$reflection = new \ReflectionClass( $manager );
		$property = $reflection->getProperty( 'modules' );
		$property->setAccessible( true );

		$modules_array = [];
		foreach ( $modules as $module ) {
			$modules_array[ $module->get_id() ] = $module;
		}

		$property->setValue( $manager, $modules_array );

		return $manager;
	}
}
