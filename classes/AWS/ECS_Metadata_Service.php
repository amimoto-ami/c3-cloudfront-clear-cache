<?php
/**
 * ECS Instance Metadata Service client
 *
 * @author wokamoto <w-okamoto@colsis.jp>
 * @since 7.3.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\AWS;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ECS Instance Metadata Service client
 *
 * @since 7.3.1
 * @package C3_CloudFront_Cache_Controller
 */
class ECS_Metadata_Service {
	/**
	 * ECS Instance Metadata Service endpoint
	 *
	 * @var string
	 */
	private $metadata_endpoint = 'http://169.254.170.2';

	/**
	 * HTTP request timeout in seconds
	 *
	 * @var int
	 */
	private $timeout = 5;

	/**
	 * Cached credentials
	 *
	 * @var array|null
	 */
	private $cached_credentials = null;

	/**
	 * Cache expiry timestamp
	 *
	 * @var int|null
	 */
	private $cache_expiry = null;

	/**
	 * Get temporary credentials from ECS instance role
	 *
	 * @return array|null Array with 'key', 'secret', 'token' or null if not available.
	 */
	public function get_credentials() {
		if ( $this->cached_credentials && $this->cache_expiry && time() < $this->cache_expiry - 300 ) {
			return $this->cached_credentials;
		}

		$credentials = $this->fetch_credentials();

		if ( $credentials ) {
			$this->cached_credentials = $credentials;
			$this->cache_expiry       = isset( $credentials['expiration'] ) ? strtotime( $credentials['expiration'] ) : time() + 3600;
		}

		return $credentials;
	}

	/**
	 * Fetch credentials from metadata service
	 *
	 * @param array $headers HTTP headers.
	 * @return array|null
	 */
	private function fetch_credentials( $headers = array() ) {
		$relative_uri = getenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );
		if ( ! $relative_uri ) {
			return null;
		}

		$response = wp_remote_request(
			$this->metadata_endpoint . $relative_uri,
			array(
				'method'  => 'GET',
				'headers' => $headers,
				'timeout' => $this->timeout,
			)
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$creds_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! $creds_data ) {
			return null;
		}
		if ( ! isset( $creds_data['AccessKeyId'], $creds_data['SecretAccessKey'], $creds_data['Token'], $creds_data['Expiration'] ) ) {
			return null;
		}

		return array(
			'key'        => $creds_data['AccessKeyId'],
			'secret'     => $creds_data['SecretAccessKey'],
			'token'      => $creds_data['Token'],
			'expiration' => $creds_data['Expiration'],
		);
	}

	/**
	 * Check if running on ECS task
	 *
	 * @return bool
	 */
	public function is_ecs_task() {
		$relative_uri = getenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );
		if ( ! $relative_uri ) {
			return false;
		}

		$response = wp_remote_request(
			$this->metadata_endpoint . $relative_uri,
			array(
				'method'  => 'GET',
				'timeout' => $this->timeout,
			)
		);
		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}
}
