<?php
/**
 * Property-Based Tests for Credential Encryption
 *
 * Property 21: Credential encryption round-trip is lossless
 * Validates: Requirement 15.6
 *
 * This test uses property-based testing (eris/eris) to verify that the credential
 * encryption mechanism correctly encrypts and decrypts OAuth credentials without
 * data loss. The encryption uses AES-256-CBC with WordPress secret keys.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generators;
use Eris\TestTrait;
use MeowSEO\Options;

/**
 * Credential Encryption property-based test case
 *
 * @since 1.0.0
 */
class Property21_CredentialEncryptionTest extends TestCase {
	use TestTrait;

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = new Options();
		// Clear any existing GSC credentials before each test
		$this->options->delete_gsc_credentials();
	}

	/**
	 * Teardown test environment
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		// Clean up GSC credentials after each test
		$this->options->delete_gsc_credentials();
	}

	/**
	 * Property 21: Credential encryption round-trip is lossless
	 *
	 * For any set of OAuth credentials, the encryption and decryption process must:
	 * 1. Encrypt credentials using AES-256-CBC with WordPress secret keys
	 * 2. Store encrypted data in the database
	 * 3. Decrypt credentials on retrieval
	 * 4. Return the exact same credentials as the original input
	 * 5. Never expose raw credentials via REST endpoints
	 *
	 * This property verifies that the round-trip encryption/decryption is lossless.
	 *
	 * **Validates: Requirement 15.6**
	 *
	 * @return void
	 */
	public function test_credential_encryption_round_trip_is_lossless(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-', 20, 50 ),
			Generators::string( 'a-zA-Z0-9_-', 20, 50 ),
			Generators::string( 'a-zA-Z0-9_-', 10, 30 )
		)
		->then(
			function ( string $access_token, string $refresh_token, string $token_type ) {
				// Create credentials array
				$original_credentials = array(
					'access_token'  => $access_token,
					'refresh_token' => $refresh_token,
					'token_type'    => $token_type,
					'expires_in'    => 3600,
					'scope'         => 'https://www.googleapis.com/auth/webmasters.readonly',
				);

				// Set (encrypt) credentials
				$set_result = $this->options->set_gsc_credentials( $original_credentials );

				$this->assertTrue(
					$set_result,
					'Setting credentials should succeed'
				);

				// Get (decrypt) credentials
				$retrieved_credentials = $this->options->get_gsc_credentials();

				// Verify round-trip is lossless
				$this->assertNotNull(
					$retrieved_credentials,
					'Retrieved credentials should not be null'
				);

				$this->assertIsArray(
					$retrieved_credentials,
					'Retrieved credentials should be an array'
				);

				$this->assertEquals(
					$original_credentials,
					$retrieved_credentials,
					'Decrypted credentials should match original credentials'
				);

				// Verify each field
				$this->assertEquals(
					$access_token,
					$retrieved_credentials['access_token'],
					'Access token should match'
				);

				$this->assertEquals(
					$refresh_token,
					$retrieved_credentials['refresh_token'],
					'Refresh token should match'
				);

				$this->assertEquals(
					$token_type,
					$retrieved_credentials['token_type'],
					'Token type should match'
				);
			}
		);
	}

	/**
	 * Property: Encrypted credentials are different from plaintext
	 *
	 * For any credentials, the encrypted form must be different from the plaintext
	 * to ensure credentials are actually encrypted, not just stored as-is.
	 *
	 * @return void
	 */
	public function test_encrypted_credentials_differ_from_plaintext(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-', 20, 50 )
		)
		->then(
			function ( string $access_token ) {
				$credentials = array(
					'access_token'  => $access_token,
					'refresh_token' => 'refresh_' . $access_token,
					'token_type'    => 'Bearer',
				);

				// Set credentials
				$this->options->set_gsc_credentials( $credentials );

				// Get encrypted data directly from database
				$encrypted = get_option( 'meowseo_gsc_credentials', '' );

				// Verify encrypted data is not empty
				$this->assertNotEmpty(
					$encrypted,
					'Encrypted credentials should be stored'
				);

				// Verify encrypted data is different from plaintext
				$plaintext = wp_json_encode( $credentials );

				$this->assertNotEquals(
					$plaintext,
					$encrypted,
					'Encrypted credentials should differ from plaintext'
				);

				// Verify encrypted data is base64-encoded
				$this->assertTrue(
					$this->is_base64( $encrypted ),
					'Encrypted credentials should be base64-encoded'
				);
			}
		);
	}

	/**
	 * Property: Multiple credentials can be stored and retrieved independently
	 *
	 * For any sequence of credential updates, each set operation must overwrite
	 * the previous credentials, and retrieval must return the most recent set.
	 *
	 * @return void
	 */
	public function test_multiple_credentials_updates_independent(): void {
		$this->forAll(
			Generators::choose( 2, 5 )
		)
		->then(
			function ( int $update_count ) {
				$credentials_history = array();

				// Perform multiple credential updates
				for ( $i = 0; $i < $update_count; $i++ ) {
					$credentials = array(
						'access_token'  => 'token_' . $i,
						'refresh_token' => 'refresh_' . $i,
						'token_type'    => 'Bearer',
						'expires_in'    => 3600 + $i,
					);

					$credentials_history[] = $credentials;

					// Set credentials
					$set_result = $this->options->set_gsc_credentials( $credentials );

					$this->assertTrue(
						$set_result,
						"Setting credentials at iteration $i should succeed"
					);
				}

				// Verify only the last credentials are stored
				$retrieved = $this->options->get_gsc_credentials();

				$this->assertEquals(
					$credentials_history[ $update_count - 1 ],
					$retrieved,
					'Retrieved credentials should be the most recent set'
				);

				// Verify it's not an earlier version
				if ( $update_count > 1 ) {
					$this->assertNotEquals(
						$credentials_history[0],
						$retrieved,
						'Retrieved credentials should not be the first set'
					);
				}
			}
		);
	}

	/**
	 * Property: Credentials with special characters are preserved
	 *
	 * For any credentials containing special characters, the encryption/decryption
	 * must preserve all characters exactly.
	 *
	 * @return void
	 */
	public function test_credentials_with_special_characters_preserved(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-./+', 20, 50 )
		)
		->then(
			function ( string $token_with_special ) {
				$credentials = array(
					'access_token'  => $token_with_special,
					'refresh_token' => 'refresh_' . $token_with_special,
					'token_type'    => 'Bearer',
				);

				// Set credentials
				$this->options->set_gsc_credentials( $credentials );

				// Get credentials
				$retrieved = $this->options->get_gsc_credentials();

				// Verify special characters are preserved
				$this->assertEquals(
					$token_with_special,
					$retrieved['access_token'],
					'Special characters in access token should be preserved'
				);

				$this->assertEquals(
					'refresh_' . $token_with_special,
					$retrieved['refresh_token'],
					'Special characters in refresh token should be preserved'
				);
			}
		);
	}

	/**
	 * Property: Deleted credentials cannot be retrieved
	 *
	 * For any credentials that are deleted, subsequent retrieval must return null.
	 *
	 * @return void
	 */
	public function test_deleted_credentials_cannot_be_retrieved(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-', 20, 50 )
		)
		->then(
			function ( string $access_token ) {
				$credentials = array(
					'access_token'  => $access_token,
					'refresh_token' => 'refresh_' . $access_token,
					'token_type'    => 'Bearer',
				);

				// Set credentials
				$this->options->set_gsc_credentials( $credentials );

				// Verify credentials exist
				$retrieved = $this->options->get_gsc_credentials();
				$this->assertNotNull( $retrieved, 'Credentials should exist after set' );

				// Delete credentials
				$delete_result = $this->options->delete_gsc_credentials();

				$this->assertTrue(
					$delete_result,
					'Deleting credentials should succeed'
				);

				// Verify credentials cannot be retrieved
				$retrieved_after_delete = $this->options->get_gsc_credentials();

				$this->assertNull(
					$retrieved_after_delete,
					'Credentials should be null after deletion'
				);
			}
		);
	}

	/**
	 * Property: Credentials array structure is preserved
	 *
	 * For any credentials array, the structure (keys and value types) must be
	 * preserved through encryption/decryption.
	 *
	 * @return void
	 */
	public function test_credentials_array_structure_preserved(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-', 10, 30 )
		)
		->then(
			function ( string $token_suffix ) {
				$credentials = array(
					'access_token'  => 'access_' . $token_suffix,
					'refresh_token' => 'refresh_' . $token_suffix,
					'token_type'    => 'Bearer',
					'expires_in'    => 3600,
					'scope'         => 'https://www.googleapis.com/auth/webmasters.readonly',
					'created_at'    => time(),
				);

				// Set credentials
				$this->options->set_gsc_credentials( $credentials );

				// Get credentials
				$retrieved = $this->options->get_gsc_credentials();

				// Verify array structure
				$this->assertIsArray( $retrieved, 'Retrieved credentials should be an array' );

				// Verify all keys exist
				foreach ( array_keys( $credentials ) as $key ) {
					$this->assertArrayHasKey(
						$key,
						$retrieved,
						"Key '$key' should exist in retrieved credentials"
					);
				}

				// Verify value types
				$this->assertIsString(
					$retrieved['access_token'],
					'access_token should be a string'
				);

				$this->assertIsString(
					$retrieved['refresh_token'],
					'refresh_token should be a string'
				);

				$this->assertIsString(
					$retrieved['token_type'],
					'token_type should be a string'
				);

				$this->assertIsInt(
					$retrieved['expires_in'],
					'expires_in should be an integer'
				);

				$this->assertIsInt(
					$retrieved['created_at'],
					'created_at should be an integer'
				);
			}
		);
	}

	/**
	 * Property: Empty credentials are handled gracefully
	 *
	 * For empty or missing credentials, the system must handle gracefully
	 * without throwing errors.
	 *
	 * @return void
	 */
	public function test_empty_credentials_handled_gracefully(): void {
		// Verify no credentials exist initially
		$retrieved = $this->options->get_gsc_credentials();

		$this->assertNull(
			$retrieved,
			'Non-existent credentials should return null'
		);

		// Try to delete non-existent credentials
		$delete_result = $this->options->delete_gsc_credentials();

		$this->assertTrue(
			$delete_result,
			'Deleting non-existent credentials should not error'
		);

		// Verify still null
		$retrieved_after = $this->options->get_gsc_credentials();

		$this->assertNull(
			$retrieved_after,
			'Credentials should still be null after delete'
		);
	}

	/**
	 * Property: Credentials are never exposed in plaintext in database
	 *
	 * For any stored credentials, the database value must be encrypted and
	 * not contain any plaintext tokens.
	 *
	 * @return void
	 */
	public function test_credentials_never_exposed_in_plaintext(): void {
		$this->forAll(
			Generators::string( 'a-zA-Z0-9_-', 20, 50 )
		)
		->then(
			function ( string $access_token ) {
				$credentials = array(
					'access_token'  => $access_token,
					'refresh_token' => 'refresh_' . $access_token,
					'token_type'    => 'Bearer',
				);

				// Set credentials
				$this->options->set_gsc_credentials( $credentials );

				// Get encrypted data from database
				$encrypted = get_option( 'meowseo_gsc_credentials', '' );

				// Verify encrypted data does not contain plaintext tokens
				$this->assertStringNotContainsString(
					$access_token,
					$encrypted,
					'Encrypted data should not contain plaintext access token'
				);

				$this->assertStringNotContainsString(
					'refresh_' . $access_token,
					$encrypted,
					'Encrypted data should not contain plaintext refresh token'
				);

				$this->assertStringNotContainsString(
					'Bearer',
					$encrypted,
					'Encrypted data should not contain plaintext token type'
				);
			}
		);
	}

	/**
	 * Helper: Check if string is valid base64
	 *
	 * @param string $string String to check.
	 * @return bool True if valid base64, false otherwise.
	 */
	private function is_base64( string $string ): bool {
		if ( empty( $string ) ) {
			return false;
		}

		// Check if string is valid base64
		$decoded = base64_decode( $string, true );

		if ( false === $decoded ) {
			return false;
		}

		// Re-encode and compare to ensure it's valid base64
		return base64_encode( $decoded ) === $string;
	}
}
