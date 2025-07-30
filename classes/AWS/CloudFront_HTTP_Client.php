<?php
/**
 * CloudFront HTTP Client for direct API communication
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\AWS;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CloudFront HTTP Client
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class CloudFront_HTTP_Client {
	/**
	 * AWS Signature service
	 *
	 * @var AWS_Signature_V4
	 */
	private $signature_service;

	/**
	 * CloudFront API endpoint
	 *
	 * @var string
	 */
	private $endpoint = 'cloudfront.amazonaws.com';

	/**
	 * API version
	 *
	 * @var string
	 */
	private $api_version = '2020-05-31';

	/**
	 * HTTP request timeout in seconds
	 *
	 * @var int
	 */
	private $timeout = 30;

	/**
	 * Constructor
	 *
	 * @param string $access_key_id AWS Access Key ID.
	 * @param string $secret_access_key AWS Secret Access Key.
	 * @param string $region AWS Region (optional, defaults to us-east-1).
	 * @param string $session_token AWS Session Token (optional, for temporary credentials).
	 */
	function __construct( $access_key_id, $secret_access_key, $region = null, $session_token = null ) {
		$cloudfront_region       = $region ?: ( defined( 'C3_CLOUDFRONT_REGION' ) ? C3_CLOUDFRONT_REGION : 'us-east-1' );
		$this->timeout           = defined( 'C3_HTTP_TIMEOUT' ) ? C3_HTTP_TIMEOUT : 30;
		$this->signature_service = new AWS_Signature_V4(
			$access_key_id,
			$secret_access_key,
			$cloudfront_region,
			'cloudfront',
			$session_token
		);
	}

	/**
	 * Create CloudFront invalidation
	 *
	 * @param string $distribution_id CloudFront distribution ID.
	 * @param array  $paths Array of paths to invalidate.
	 * @return array|WP_Error API response or error.
	 */
	public function create_invalidation( $distribution_id, $paths ) {
		$caller_reference = uniqid( 'c3-' . time() . '-' );
		$path             = "/{$this->api_version}/distribution/{$distribution_id}/invalidation";

		$xml_payload = $this->build_invalidation_xml( $caller_reference, $paths );

		$headers = array(
			'Content-Type' => 'application/xml',
		);

		$signed_headers = $this->signature_service->sign_request(
			'POST',
			$this->endpoint,
			$path,
			$xml_payload,
			$headers
		);

		$response = wp_remote_request(
			"https://{$this->endpoint}{$path}",
			array(
				'method'  => 'POST',
				'headers' => $signed_headers,
				'body'    => $xml_payload,
				'timeout' => $this->timeout,
			)
		);

		return $this->handle_response( $response );
	}

	/**
	 * List CloudFront invalidations
	 *
	 * @param string $distribution_id CloudFront distribution ID.
	 * @param int    $max_items Maximum number of items to return.
	 * @return array|WP_Error API response or error.
	 */
	public function list_invalidations( $distribution_id, $max_items = 25 ) {
		$path = "/{$this->api_version}/distribution/{$distribution_id}/invalidation";
		if ( $max_items > 0 ) {
			$path .= "?MaxItems={$max_items}";
		}

		$signed_headers = $this->signature_service->sign_request(
			'GET',
			$this->endpoint,
			$path
		);

		$response = wp_remote_request(
			"https://{$this->endpoint}{$path}",
			array(
				'method'  => 'GET',
				'headers' => $signed_headers,
				'timeout' => $this->timeout,
			)
		);

		return $this->handle_response( $response );
	}

	/**
	 * Get CloudFront invalidation details
	 *
	 * @param string $distribution_id CloudFront distribution ID.
	 * @param string $invalidation_id Invalidation ID.
	 * @return array|WP_Error API response or error.
	 */
	public function get_invalidation( $distribution_id, $invalidation_id ) {
		$path = "/{$this->api_version}/distribution/{$distribution_id}/invalidation/{$invalidation_id}";

		$signed_headers = $this->signature_service->sign_request(
			'GET',
			$this->endpoint,
			$path
		);

		$response = wp_remote_request(
			"https://{$this->endpoint}{$path}",
			array(
				'method'  => 'GET',
				'headers' => $signed_headers,
				'timeout' => $this->timeout,
			)
		);

		return $this->handle_response( $response );
	}

	/**
	 * Get CloudFront distribution information
	 *
	 * @param string $distribution_id CloudFront distribution ID.
	 * @return array|WP_Error API response or error.
	 */
	public function get_distribution( $distribution_id ) {
		$path = "/{$this->api_version}/distribution/{$distribution_id}";

		$signed_headers = $this->signature_service->sign_request(
			'GET',
			$this->endpoint,
			$path
		);

		$response = wp_remote_request(
			"https://{$this->endpoint}{$path}",
			array(
				'method'  => 'GET',
				'headers' => $signed_headers,
				'timeout' => $this->timeout,
			)
		);

		return $this->handle_response( $response );
	}

	/**
	 * Build XML payload for invalidation request
	 *
	 * @param string $caller_reference Unique caller reference.
	 * @param array  $paths Array of paths to invalidate.
	 * @return string XML payload.
	 */
	private function build_invalidation_xml( $caller_reference, $paths ) {
		$quantity = count( $paths );
		$xml      = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml     .= '<InvalidationBatch>' . "\n";
		$xml     .= "  <CallerReference>{$caller_reference}</CallerReference>" . "\n";
		$xml     .= '  <Paths>' . "\n";
		$xml     .= "    <Quantity>{$quantity}</Quantity>" . "\n";
		$xml     .= '    <Items>' . "\n";

		foreach ( $paths as $path ) {
			$xml .= '      <Path>' . esc_xml( $path ) . '</Path>' . "\n";
		}

		$xml .= '    </Items>' . "\n";
		$xml .= '  </Paths>' . "\n";
		$xml .= '</InvalidationBatch>';

		return $xml;
	}

	/**
	 * Handle HTTP response from CloudFront API
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @return array|WP_Error Parsed response or error.
	 */
	private function handle_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( $status_code >= 400 ) {
			$error_message = $this->parse_error_response( $body, $status_code );
			return new \WP_Error( 'cloudfront_api_error', $error_message );
		}

		return $this->parse_xml_response( $body );
	}

	/**
	 * Parse XML response from CloudFront API
	 *
	 * @param string $xml_body XML response body.
	 * @return array Parsed response data.
	 */
	private function parse_xml_response( $xml_body ) {
		if ( empty( $xml_body ) ) {
			return array();
		}

		libxml_use_internal_errors( true );
		libxml_disable_entity_loader( true );
		$xml = simplexml_load_string( $xml_body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT );

		if ( false === $xml ) {
			return array( 'raw_response' => $xml_body );
		}

		return json_decode( json_encode( $xml ), true );
	}

	/**
	 * Parse error response from CloudFront API
	 *
	 * @param string $xml_body XML error response body.
	 * @param int    $status_code HTTP status code.
	 * @return string Error message.
	 */
	private function parse_error_response( $xml_body, $status_code ) {
		libxml_use_internal_errors( true );
		libxml_disable_entity_loader( true );
		$xml = simplexml_load_string( $xml_body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT );
		if ( false !== $xml && isset( $xml->Message ) ) {
			return (string) $xml->Message;
		}

		return "CloudFront API error (HTTP {$status_code}): {$xml_body}";
	}
}
