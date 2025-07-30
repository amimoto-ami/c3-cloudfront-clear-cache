<?php
/**
 * CloudFront HTTP Client Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\AWS\CloudFront_HTTP_Client;
use C3_CloudFront_Cache_Controller\Test\Helpers\WP_Mock_Helper;

/**
 * CloudFront HTTP Client Test
 */
class CloudFront_HTTP_Client_Test extends TestCase {

	public function tearDown(): void {
		\Mockery::close();
	}

	/**
	 * Test get_invalidation with successful response
	 */
	public function test_get_invalidation_success() {
		$distribution_id = 'E123456789';
		$invalidation_id = 'I123456789';
		
		$xml_response = '<?xml version="1.0" encoding="UTF-8"?>
<Invalidation>
	<Id>I123456789</Id>
	<Status>Completed</Status>
	<CreateTime>2023-01-01T00:00:00.000Z</CreateTime>
	<InvalidationBatch>
		<CallerReference>test-ref</CallerReference>
		<Paths>
			<Quantity>2</Quantity>
			<Items>
				<Path>/test-path-1</Path>
				<Path>/test-path-2</Path>
			</Items>
		</Paths>
	</InvalidationBatch>
</Invalidation>';

		$mock_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => $xml_response,
		);

		\WP_Mock::userFunction( 'wp_remote_request' )
			->once()
			->with(
				\Mockery::pattern( '/https:\/\/cloudfront\.amazonaws\.com\/2020-05-31\/distribution\/E123456789\/invalidation\/I123456789/' ),
				\Mockery::type( 'array' )
			)
			->andReturn( $mock_response );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->once()
			->with( $mock_response )
			->andReturn( 200 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->once()
			->with( $mock_response )
			->andReturn( $xml_response );

		$client = new CloudFront_HTTP_Client( 'test-key', 'test-secret' );
		$result = $client->get_invalidation( $distribution_id, $invalidation_id );

		$this->assertIsArray( $result );
		$this->assertEquals( 'I123456789', $result['Id'] );
		$this->assertEquals( 'Completed', $result['Status'] );
		$this->assertArrayHasKey( 'InvalidationBatch', $result );
	}

	/**
	 * Test get_invalidation with HTTP error response
	 */
	public function test_get_invalidation_http_error() {
		$distribution_id = 'E123456789';
		$invalidation_id = 'I123456789';
		
		$error_xml = '<?xml version="1.0" encoding="UTF-8"?>
<ErrorResponse>
	<Error>
		<Code>AccessDenied</Code>
		<Message>Access denied</Message>
	</Error>
</ErrorResponse>';

		$mock_response = array(
			'response' => array( 'code' => 403 ),
			'body'     => $error_xml,
		);

		\WP_Mock::userFunction( 'wp_remote_request' )
			->once()
			->andReturn( $mock_response );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->once()
			->with( $mock_response )
			->andReturn( 403 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->once()
			->with( $mock_response )
			->andReturn( $error_xml );

		$client = new CloudFront_HTTP_Client( 'test-key', 'test-secret' );
		$result = $client->get_invalidation( $distribution_id, $invalidation_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'cloudfront_api_error', $result->get_error_code() );
		$this->assertStringContainsString( 'Access denied', $result->get_error_message() );
	}

	/**
	 * Test get_invalidation with WP_Error from wp_remote_request
	 */
	public function test_get_invalidation_wp_error() {
		$distribution_id = 'E123456789';
		$invalidation_id = 'I123456789';
		
		$wp_error = new \WP_Error( 'http_request_failed', 'Connection timeout' );

		\WP_Mock::userFunction( 'wp_remote_request' )
			->once()
			->andReturn( $wp_error );

		$client = new CloudFront_HTTP_Client( 'test-key', 'test-secret' );
		$result = $client->get_invalidation( $distribution_id, $invalidation_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'http_request_failed', $result->get_error_code() );
		$this->assertEquals( 'Connection timeout', $result->get_error_message() );
	}

	/**
	 * Test get_invalidation with empty response body
	 */
	public function test_get_invalidation_empty_response() {
		$distribution_id = 'E123456789';
		$invalidation_id = 'I123456789';

		$mock_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
		);

		\WP_Mock::userFunction( 'wp_remote_request' )
			->once()
			->andReturn( $mock_response );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->once()
			->with( $mock_response )
			->andReturn( 200 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->once()
			->with( $mock_response )
			->andReturn( '' );

		$client = new CloudFront_HTTP_Client( 'test-key', 'test-secret' );
		$result = $client->get_invalidation( $distribution_id, $invalidation_id );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_invalidation with invalid XML response
	 */
	public function test_get_invalidation_invalid_xml() {
		$distribution_id = 'E123456789';
		$invalidation_id = 'I123456789';
		
		$invalid_xml = 'This is not valid XML content';

		$mock_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => $invalid_xml,
		);

		\WP_Mock::userFunction( 'wp_remote_request' )
			->once()
			->andReturn( $mock_response );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->once()
			->with( $mock_response )
			->andReturn( 200 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->once()
			->with( $mock_response )
			->andReturn( $invalid_xml );

		$client = new CloudFront_HTTP_Client( 'test-key', 'test-secret' );
		$result = $client->get_invalidation( $distribution_id, $invalidation_id );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'raw_response', $result );
		$this->assertEquals( $invalid_xml, $result['raw_response'] );
	}

	/**
	 * Test get_invalidation with 404 Not Found error
	 */
	public function test_get_invalidation_not_found() {
		$distribution_id = 'E123456789';
		$invalidation_id = 'I123456789';
		
		$error_xml = '<?xml version="1.0" encoding="UTF-8"?>
<ErrorResponse>
	<Error>
		<Code>NoSuchInvalidation</Code>
		<Message>The specified invalidation does not exist</Message>
	</Error>
</ErrorResponse>';

		$mock_response = array(
			'response' => array( 'code' => 404 ),
			'body'     => $error_xml,
		);

		\WP_Mock::userFunction( 'wp_remote_request' )
			->once()
			->andReturn( $mock_response );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->once()
			->with( $mock_response )
			->andReturn( 404 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->once()
			->with( $mock_response )
			->andReturn( $error_xml );

		$client = new CloudFront_HTTP_Client( 'test-key', 'test-secret' );
		$result = $client->get_invalidation( $distribution_id, $invalidation_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'cloudfront_api_error', $result->get_error_code() );
		$this->assertStringContainsString( 'The specified invalidation does not exist', $result->get_error_message() );
	}

	/**
	 * Test get_invalidation with malformed error XML
	 */
	public function test_get_invalidation_malformed_error_xml() {
		$distribution_id = 'E123456789';
		$invalidation_id = 'I123456789';
		
		$malformed_error = 'HTTP 500 Internal Server Error';

		$mock_response = array(
			'response' => array( 'code' => 500 ),
			'body'     => $malformed_error,
		);

		\WP_Mock::userFunction( 'wp_remote_request' )
			->once()
			->andReturn( $mock_response );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->once()
			->with( $mock_response )
			->andReturn( 500 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->once()
			->with( $mock_response )
			->andReturn( $malformed_error );

		$client = new CloudFront_HTTP_Client( 'test-key', 'test-secret' );
		$result = $client->get_invalidation( $distribution_id, $invalidation_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'cloudfront_api_error', $result->get_error_code() );
		$this->assertStringContainsString( 'CloudFront API error (HTTP 500)', $result->get_error_message() );
		$this->assertStringContainsString( $malformed_error, $result->get_error_message() );
	}
}
