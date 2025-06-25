<?php
/**
 * CloudFront management service class
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\AWS;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use C3_CloudFront_Cache_Controller\WP;

/**
 * CloudFront service
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class CloudFront_Service {
	/**
	 * Env class
	 *
	 * @var WP\Environment
	 */
	private $env;

	/**
	 * Option service
	 *
	 * @var WP\Options_service
	 */
	private $options_service;

	/**
	 * Hook
	 *
	 * @var WP\Hooks
	 */
	private $hook_service;

	/**
	 * Inject a external services
	 *
	 * @param mixed ...$args Inject class.
	 */
	function __construct( ...$args ) {
		if ( $args && ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				if ( $value instanceof WP\Options_Service ) {
					$this->options_service = $value;
				} elseif ( $value instanceof WP\Hooks ) {
					$this->hook_service = $value;
				} elseif ( $value instanceof WP\Environment ) {
					$this->env = $value;
				}
			}
		}
		if ( ! $this->hook_service ) {
			$this->hook_service = new WP\Hooks();
		}
		if ( ! $this->env ) {
			$this->env = new WP\Environment();
		}
		if ( ! $this->options_service ) {
			$this->options_service = new WP\Options_Service();
		}
	}

	/**
	 * Get AWS credentials
	 *
	 * @param string $access_key AWS access key id.
	 * @param string $secret_key AWS secret access key id.
	 * @return array|null Array with 'key' and 'secret' or null if not available.
	 */
	public function get_credentials( ?string $access_key = null, ?string $secret_key = null ) {
		$key    = isset( $access_key ) ? $access_key : $this->env->get_aws_access_key();
		$secret = isset( $secret_key ) ? $secret_key : $this->env->get_aws_secret_key();
		if ( ! isset( $key ) || ! isset( $secret ) ) {
			return null;
		}
		return array(
			'key'    => $key,
			'secret' => $secret,
		);
	}

	/**
	 * Check the plugin option parameter.
	 * Calling GetDistribution API to check these parameters.
	 *
	 * @param string $distribution_id CloudFront distribution id.
	 * @param string $access_key AWS access key id.
	 * @param string $secret_key AWS secret access key id.
	 * @return \WP_Error|null  Return WP_Error if AWS API returns any error.
	 */
	public function try_to_call_aws_api( string $distribution_id, ?string $access_key = null, ?string $secret_key = null ) {
		$credentials = $this->get_credentials( $access_key, $secret_key );
		if ( ! $credentials ) {
			return new \WP_Error( 'C3 Auth Error', 'AWS credentials are not available.' );
		}

		$client = new CloudFront_HTTP_Client( $credentials['key'], $credentials['secret'] );
		$result = $client->get_distribution( $distribution_id );

		if ( is_wp_error( $result ) ) {
			$error_message = $result->get_error_message();
			$error_code = $result->get_error_code();
			
			if ( $error_code === 'cloudfront_api_error' ) {
				if ( strpos( $error_message, 'NoSuchDistribution' ) !== false ) {
					$e = new \WP_Error( 'C3 Auth Error', "Can not find CloudFront Distribution ID: {$distribution_id} is not found." );
				} elseif ( strpos( $error_message, 'InvalidClientTokenId' ) !== false || strpos( $error_message, 'SignatureDoesNotMatch' ) !== false ) {
					$e = new \WP_Error( 'C3 Auth Error', 'AWS Access Key or AWS Secret Key is invalid.' );
				} else {
					$e = new \WP_Error( 'C3 Auth Error', $error_message );
				}
			} else {
				$e = new \WP_Error( 'C3 Auth Error', $error_message );
			}
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'C3 CloudFront Auth Error: ' . $error_message );
			}
			return $e;
		}
		return null;
	}

	/**
	 * Create CloudFront HTTP Client
	 */
	public function create_client() {

		/**
		 * Load credentials from wp_options or defined values.
		 */
		$options = $this->options_service->get_options();
		if ( ! $options ) {
			return new \WP_Error( 'C3 Create Client Error', 'General setting params not defined.' );
		}

		/**
		 * You can overwrite the AWS API credentials.
		 */
		$credentials = $this->hook_service->apply_filters(
			'c3_credential',
			array(
				'key'    => $options['access_key'],
				'secret' => $options['secret_key'],
			)
		);

		/**
		 * If AWS credentials are available, create HTTP client.
		 */
		if ( $credentials['key'] && $credentials['secret'] ) {
			$client = new CloudFront_HTTP_Client( $credentials['key'], $credentials['secret'] );
			return $client;
		}

		return new \WP_Error( 'C3 Create Client Error', 'AWS credentials are required.' );
	}

	/**
	 * Get the target CloudFront distribution id
	 *
	 * @return string distribution id
	 * @throws \Exception If no distribution id provided.
	 */
	public function get_distribution_id() {
		/**
		 * Try to find the id from the defined values.
		 */
		$from_defined_value = $this->env->get_distribution_id();
		if ( $from_defined_value ) {
			return $from_defined_value;
		}

		/**
		 * Then, load the wp_option table to get the saved id
		 */
		$options = $this->options_service->get_options();
		if ( $options && $options['distribution_id'] ) {
			return $options['distribution_id'];
		}
		throw new \Exception( 'distribution_id does not exists.' );
	}

	/**
	 * Create Invalidation request to AWS
	 *
	 * @param mixed $params Invalidation request.
	 */
	public function create_invalidation( $params ) {
		try {
			$client = $this->create_client();
			if ( is_wp_error( $client ) ) {
				return $client;
			}

			$distribution_id = $params['DistributionId'];
			$paths           = $params['InvalidationBatch']['Paths']['Items'];

			$result = $client->create_invalidation( $distribution_id, $paths );
			return $result;
		} catch ( \Exception $e ) {
			$e = new \WP_Error( 'C3 Invalidation Error', $e->getMessage() );
			error_log( print_r( $e->get_error_messages(), true ), 0 );
			return $e;
		} catch ( \Error $e ) {
			error_log( $e->__toString(), 0 );
			return $e;
		}
	}

	/**
	 * List created invalidations.
	 */
	public function list_invalidations() {
		try {
			$client = $this->create_client();
			if ( is_wp_error( $client ) ) {
				error_log( 'C3 CloudFront: Failed to create CloudFront client: ' . $client->get_error_message() );
				return new \WP_Error( 'C3 List Invalidations Error', 'Failed to create CloudFront client: ' . $client->get_error_message() );
			}

			$distribution_id = $this->get_distribution_id();
			error_log( 'C3 CloudFront: Listing invalidations for distribution: ' . $distribution_id );
			
			$max_items       = $this->hook_service->apply_filters( 'c3_max_invalidation_logs', 25 );
			$result          = $client->list_invalidations( $distribution_id, $max_items );

			if ( is_wp_error( $result ) ) {
				$error_message = $result->get_error_message();
				$error_code = $result->get_error_code();
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'C3 CloudFront: API Error: ' . $error_message );
				}
				
				if ( $error_code === 'cloudfront_api_error' ) {
					if ( strpos( $error_message, 'NoSuchDistribution' ) !== false ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'C3 CloudFront: Distribution not found: ' . $distribution_id );
						}
						return new \WP_Error( 'C3 List Invalidations Error', "CloudFront Distribution ID: {$distribution_id} not found." );
					} elseif ( strpos( $error_message, 'InvalidClientTokenId' ) !== false || strpos( $error_message, 'SignatureDoesNotMatch' ) !== false ) {
						return new \WP_Error( 'C3 List Invalidations Error', 'AWS Access Key or AWS Secret Key is invalid.' );
					} else {
						return new \WP_Error( 'C3 List Invalidations Error', $error_message );
					}
				} else {
					return new \WP_Error( 'C3 List Invalidations Error', $error_message );
				}
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'C3 CloudFront: API response received (quantity: ' . ( isset( $result['Quantity'] ) ? $result['Quantity'] : 'unknown' ) . ')' );
			}

			if ( isset( $result['Quantity'] ) && $result['Quantity'] > 0 && isset( $result['Items']['InvalidationSummary'] ) ) {
				error_log( 'C3 CloudFront: Found ' . $result['Quantity'] . ' invalidations' );
				return $result['Items']['InvalidationSummary'];
			}
			
			error_log( 'C3 CloudFront: No invalidations found' );
			return array();
		} catch ( \Exception $e ) {
			error_log( 'C3 CloudFront: Exception in list_invalidations: ' . $e->__toString() );
			return new \WP_Error( 'C3 List Invalidations Error', $e->getMessage() );
		} catch ( \Error $e ) {
			error_log( 'C3 CloudFront: Error in list_invalidations: ' . $e->__toString() );
			return new \WP_Error( 'C3 List Invalidations Error', $e->getMessage() );
		}
	}
}
