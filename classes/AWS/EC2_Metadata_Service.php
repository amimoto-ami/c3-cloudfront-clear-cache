<?php
/**
 * EC2 Instance Metadata Service client
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.2
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\AWS;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EC2 Instance Metadata Service client
 *
 * @since 6.1.2
 * @package C3_CloudFront_Cache_Controller
 */
class EC2_Metadata_Service {
	/**
	 * EC2 Instance Metadata Service endpoint
	 *
	 * @var string
	 */
	private $metadata_endpoint = 'http://169.254.169.254';

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
	 * Get temporary credentials from EC2 instance role
	 *
	 * @return array|null Array with 'key', 'secret', 'token' or null if not available.
	 */
	public function get_instance_credentials() {
		if ( $this->cached_credentials && $this->cache_expiry && time() < $this->cache_expiry - 300 ) {
			return $this->cached_credentials;
		}

		$credentials = $this->get_credentials_v2();
		if ( ! $credentials ) {
			$credentials = $this->get_credentials_v1();
		}

		if ( $credentials ) {
			$this->cached_credentials = $credentials;
			$this->cache_expiry       = isset( $credentials['expiration'] ) ? strtotime( $credentials['expiration'] ) : time() + 3600;
		}

		return $credentials;
	}

	/**
	 * Get credentials using IMDSv2
	 *
	 * @return array|null
	 */
	private function get_credentials_v2() {
		$token = $this->get_imdsv2_token();
		if ( ! $token ) {
			return null;
		}

		$headers = array( 'X-aws-ec2-metadata-token' => $token );
		return $this->fetch_credentials( $headers );
	}

	/**
	 * Get credentials using IMDSv1
	 *
	 * @return array|null
	 */
	private function get_credentials_v1() {
		return $this->fetch_credentials();
	}

	/**
	 * Get IMDSv2 token
	 *
	 * @return string|null
	 */
	private function get_imdsv2_token() {
		$response = wp_remote_request(
			$this->metadata_endpoint . '/latest/api/token',
			array(
				'method'  => 'PUT',
				'headers' => array( 'X-aws-ec2-metadata-token-ttl-seconds' => '21600' ),
				'timeout' => $this->timeout,
			)
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Fetch credentials from metadata service
	 *
	 * @param array $headers HTTP headers.
	 * @return array|null
	 */
	private function fetch_credentials( $headers = array() ) {
		$role_response = wp_remote_request(
			$this->metadata_endpoint . '/latest/meta-data/iam/security-credentials/',
			array(
				'method'  => 'GET',
				'headers' => $headers,
				'timeout' => $this->timeout,
			)
		);

		if ( is_wp_error( $role_response ) || wp_remote_retrieve_response_code( $role_response ) !== 200 ) {
			return null;
		}

		$role_name = trim( wp_remote_retrieve_body( $role_response ) );
		if ( empty( $role_name ) ) {
			return null;
		}

		$creds_response = wp_remote_request(
			$this->metadata_endpoint . '/latest/meta-data/iam/security-credentials/' . $role_name,
			array(
				'method'  => 'GET',
				'headers' => $headers,
				'timeout' => $this->timeout,
			)
		);

		if ( is_wp_error( $creds_response ) || wp_remote_retrieve_response_code( $creds_response ) !== 200 ) {
			return null;
		}

		$creds_data = json_decode( wp_remote_retrieve_body( $creds_response ), true );
		if ( ! $creds_data || $creds_data['Code'] !== 'Success' ) {
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
	 * Check if running on EC2 instance
	 *
	 * @return bool
	 */
	public function is_ec2_instance() {
		// First, get a token with IMDSv2
		$token = $this->get_imdsv2_token();
		if ( $token ) {
			// Accessing metadata using tokens
			$response = wp_remote_request(
				$this->metadata_endpoint . '/latest/meta-data/',
				array(
					'method'  => 'GET',
					'headers' => array( 'X-aws-ec2-metadata-token' => $token ),
					'timeout' => 2,
				)
			);
			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				return true;
			}
		}

		// If IMDSv2 fails, fall back to IMDSv1
		$response = wp_remote_request(
			$this->metadata_endpoint . '/latest/meta-data/',
			array(
				'method'  => 'GET',
				'timeout' => 2,
			)
		);
		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}
}
