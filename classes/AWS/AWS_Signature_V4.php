<?php
/**
 * AWS Signature Version 4 implementation
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
 * AWS Signature Version 4 service
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class AWS_Signature_V4 {
	/**
	 * AWS Access Key ID
	 *
	 * @var string
	 */
	private $access_key_id;

	/**
	 * AWS Secret Access Key
	 *
	 * @var string
	 */
	private $secret_access_key;

	/**
	 * AWS Region
	 *
	 * @var string
	 */
	private $region;

	/**
	 * AWS Service name
	 *
	 * @var string
	 */
	private $service_name;

	/**
	 * Constructor
	 *
	 * @param string $access_key_id AWS Access Key ID.
	 * @param string $secret_access_key AWS Secret Access Key.
	 * @param string $region AWS Region.
	 * @param string $service_name AWS Service name.
	 */
	function __construct( $access_key_id, $secret_access_key, $region, $service_name ) {
		$this->access_key_id     = $access_key_id;
		$this->secret_access_key = $secret_access_key;
		$this->region            = $region;
		$this->service_name      = $service_name;
	}

	/**
	 * Sign HTTP request with AWS Signature Version 4
	 *
	 * @param string $method HTTP method.
	 * @param string $endpoint API endpoint hostname.
	 * @param string $path Request path with optional query parameters.
	 * @param string $payload Request payload.
	 * @param array  $headers Additional headers.
	 * @return array Signed headers for the request.
	 * @throws \InvalidArgumentException If required parameters are missing.
	 */
	public function sign_request( $method, $endpoint, $path, $payload = '', $headers = array() ) {
		if ( empty( $method ) || empty( $endpoint ) || empty( $path ) ) {
			throw new \InvalidArgumentException( 'Method, endpoint, and path are required parameters.' );
		}
		$now       = new \DateTime();
		$amz_date  = $now->format( 'Ymd\THis\Z' );
		$date_stamp = $now->format( 'Ymd' );

		// パスからクエリ文字列を分離
		$parsed_url = parse_url( $path );
		$canonical_uri = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';
		$canonical_query_string = '';
		
		if ( isset( $parsed_url['query'] ) ) {
			// クエリパラメータをソートして正規化
			parse_str( $parsed_url['query'], $query_params );
			ksort( $query_params );
			$canonical_query_parts = array();
			foreach ( $query_params as $key => $value ) {
				$canonical_query_parts[] = rawurlencode( $key ) . '=' . rawurlencode( $value );
			}
			$canonical_query_string = implode( '&', $canonical_query_parts );
		}

		$default_headers = array(
			'host'                 => $endpoint,
			'x-amz-date'           => $amz_date,
			'x-amz-content-sha256' => hash( 'sha256', $payload ),
		);

		$all_headers = array_merge( $default_headers, $headers );
		ksort( $all_headers );

		$canonical_headers = '';
		$signed_headers    = array();
		foreach ( $all_headers as $key => $value ) {
			$canonical_headers .= strtolower( $key ) . ':' . trim( $value ) . "\n";
			$signed_headers[]   = strtolower( $key );
		}
		$signed_headers_string = implode( ';', $signed_headers );

		$canonical_request = implode(
			"\n",
			array(
				$method,
				$canonical_uri,
				$canonical_query_string,
				$canonical_headers,
				$signed_headers_string,
				hash( 'sha256', $payload ),
			)
		);

		$canonical_request_hash = hash( 'sha256', $canonical_request );
		$string_to_sign         = implode(
			"\n",
			array(
				'AWS4-HMAC-SHA256',
				$amz_date,
				"{$date_stamp}/{$this->region}/{$this->service_name}/aws4_request",
				$canonical_request_hash,
			)
		);

		$signing_key = $this->get_signature_key( $date_stamp );
		$signature   = hash_hmac( 'sha256', $string_to_sign, $signing_key );

		$authorization_header = "AWS4-HMAC-SHA256 Credential={$this->access_key_id}/{$date_stamp}/{$this->region}/{$this->service_name}/aws4_request, SignedHeaders={$signed_headers_string}, Signature={$signature}";

		$all_headers['Authorization'] = $authorization_header;

		return $all_headers;
	}

	/**
	 * Generate AWS Signature Version 4 signing key
	 *
	 * @param string $date_stamp Date stamp in YYYYMMDD format.
	 * @return string Binary signing key.
	 */
	private function get_signature_key( $date_stamp ) {
		$k_secret  = 'AWS4' . $this->secret_access_key;
		$k_date    = hash_hmac( 'sha256', $date_stamp, $k_secret, true );
		$k_region  = hash_hmac( 'sha256', $this->region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $this->service_name, $k_region, true );
		$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );

		return $k_signing;
	}
}
