<?php
/**
 * AWS SDK Mock Helper for testing
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test\Helpers;

/**
 * AWS Mock Helper class
 */
class AWS_Mock_Helper {
	
	/**
	 * Create mock CloudFront client that succeeds
	 */
	public static function create_successful_cloudfront_client() {
		$mock_client = \Mockery::mock( 'CloudFrontClient' );
		
		$mock_client->shouldReceive( 'getDistribution' )
			->andReturn( [
				'Distribution' => [
					'Id' => 'E123456789',
					'Status' => 'Deployed'
				]
			] );
		
		$mock_client->shouldReceive( 'createInvalidation' )
			->andReturn( [
				'Invalidation' => [
					'Id' => 'I123456789',
					'Status' => 'InProgress'
				]
			] );
		
		return $mock_client;
	}
	
	/**
	 * Create mock CloudFront client that fails with distribution not found
	 */
	public static function create_distribution_not_found_client() {
		$mock_client = \Mockery::mock( 'CloudFrontClient' );
		
		$mock_client->shouldReceive( 'getDistribution' )
			->andThrow( new \Exception( 'Distribution not found: NoSuchDistribution' ) );
		
		return $mock_client;
	}
	
	/**
	 * Create mock CloudFront client that fails with invalid credentials
	 */
	public static function create_invalid_credentials_client() {
		$mock_client = \Mockery::mock( 'CloudFrontClient' );
		
		$mock_client->shouldReceive( 'getDistribution' )
			->andThrow( new \Exception( 'Invalid credentials: InvalidClientTokenId' ) );
		
		return $mock_client;
	}
	
	/**
	 * Create mock AWS credentials
	 */
	public static function create_mock_credentials() {
		return array(
			'key' => 'test-key',
			'secret' => 'test-secret'
		);
	}

	/**
	 * HTTP Request interceptor for AWS API calls
	 */
	private static $intercepted_requests = array();
	private static $mock_responses = array();
	
	/**
	 * Start HTTP request interception
	 */
	public static function start_http_interception() {
		self::$intercepted_requests = array();
		add_filter( 'pre_http_request', array( __CLASS__, 'intercept_http_request' ), 10, 3 );
	}
	
	/**
	 * Stop HTTP request interception
	 */
	public static function stop_http_interception() {
		remove_filter( 'pre_http_request', array( __CLASS__, 'intercept_http_request' ) );
		self::$intercepted_requests = array();
		self::$mock_responses = array();
	}
	
	/**
	 * Intercept HTTP requests to AWS CloudFront API
	 */
	public static function intercept_http_request( $preempt, $parsed_args, $url ) {
		if ( strpos( $url, 'cloudfront.amazonaws.com' ) === false ) {
			return $preempt;
		}
		
		self::$intercepted_requests[] = array(
			'url' => $url,
			'method' => $parsed_args['method'],
			'headers' => $parsed_args['headers'],
			'body' => isset( $parsed_args['body'] ) ? $parsed_args['body'] : '',
			'timeout' => $parsed_args['timeout'],
		);
		
		return self::generate_mock_response( $url, $parsed_args );
	}
	
	/**
	 * Generate appropriate mock response based on request
	 */
	private static function generate_mock_response( $url, $parsed_args ) {
		if ( isset( self::$mock_responses['error'] ) ) {
			$error = self::$mock_responses['error'];
			return array(
				'response' => array( 'code' => $error['status_code'] ),
				'body' => sprintf(
					'<Error><Code>%s</Code><Message>%s</Message></Error>',
					$error['error_code'],
					$error['message']
				),
			);
		}

		$method = $parsed_args['method'];
		
		if ( $method === 'POST' && strpos( $url, '/invalidation' ) !== false ) {
			return array(
				'response' => array( 'code' => 201 ),
				'body' => self::get_mock_invalidation_response(),
			);
		} elseif ( $method === 'GET' && strpos( $url, '/invalidation' ) !== false ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body' => self::get_mock_invalidation_list_response(),
			);
		} elseif ( $method === 'GET' && strpos( $url, '/distribution/' ) !== false && strpos( $url, '/invalidation' ) === false ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body' => self::get_mock_distribution_response(),
			);
		}
		
		return array(
			'response' => array( 'code' => 400 ),
			'body' => '<Error><Code>InvalidRequest</Code><Message>Mock error</Message></Error>',
		);
	}
	
	/**
	 * Get intercepted requests for validation
	 */
	public static function get_intercepted_requests() {
		return self::$intercepted_requests;
	}
	
	/**
	 * Get the last intercepted request
	 */
	public static function get_last_request() {
		return end( self::$intercepted_requests );
	}
	
	/**
	 * Mock XML responses for different API endpoints
	 */
	private static function get_mock_invalidation_response() {
		return '<?xml version="1.0" encoding="UTF-8"?>
<Invalidation>
	<Id>I123456789ABCDEF</Id>
	<Status>InProgress</Status>
	<CreateTime>2024-01-01T00:00:00.000Z</CreateTime>
	<InvalidationBatch>
		<CallerReference>test-reference</CallerReference>
		<Paths>
			<Quantity>1</Quantity>
			<Items>
				<Path>/*</Path>
			</Items>
		</Paths>
	</InvalidationBatch>
</Invalidation>';
	}
	
	private static function get_mock_invalidation_list_response() {
		return '<?xml version="1.0" encoding="UTF-8"?>
<InvalidationList>
	<Items>
		<Invalidation>
			<Id>I123456789ABCDEF</Id>
			<Status>Completed</Status>
			<CreateTime>2024-01-01T00:00:00.000Z</CreateTime>
		</Invalidation>
	</Items>
	<Quantity>1</Quantity>
</InvalidationList>';
	}
	
	private static function get_mock_distribution_response() {
		return '<?xml version="1.0" encoding="UTF-8"?>
<Distribution>
	<Id>E123456789ABCDEF</Id>
	<Status>Deployed</Status>
	<DomainName>d123456789abcdef.cloudfront.net</DomainName>
</Distribution>';
	}

	/**
	 * Configure mock to return error response
	 */
	public static function set_mock_error_response( $status_code, $error_code, $message ) {
		self::$mock_responses['error'] = array(
			'status_code' => $status_code,
			'error_code' => $error_code,
			'message' => $message,
		);
	}

	/**
	 * Clear mock error response
	 */
	public static function clear_mock_error_response() {
		unset( self::$mock_responses['error'] );
	}

	/**
	 * Validate AWS signature components
	 */
	public static function validate_aws_signature( $authorization_header, $expected_access_key ) {
		$validation_results = array();
		
		$validation_results['algorithm'] = strpos( $authorization_header, 'AWS4-HMAC-SHA256' ) === 0;
		
		$credential_pattern = '/Credential=' . preg_quote( $expected_access_key ) . '\/\d{8}\/[^\/]+\/[^\/]+\/aws4_request/';
		$validation_results['credential'] = preg_match( $credential_pattern, $authorization_header );
		
		$validation_results['signed_headers'] = strpos( $authorization_header, 'SignedHeaders=' ) !== false;
		
		$validation_results['signature'] = strpos( $authorization_header, 'Signature=' ) !== false;
		
		return $validation_results;
	}

	/**
	 * Validate XML structure
	 */
	public static function validate_invalidation_xml( $xml_body ) {
		$validation_results = array();
		
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_body );
		$validation_results['valid_xml'] = $xml !== false;
		
		if ( $xml ) {
			$validation_results['has_caller_reference'] = isset( $xml->CallerReference );
			$validation_results['has_paths'] = isset( $xml->Paths );
			$validation_results['has_quantity'] = isset( $xml->Paths->Quantity );
			$validation_results['has_items'] = isset( $xml->Paths->Items );
		}
		
		return $validation_results;
	}
}        