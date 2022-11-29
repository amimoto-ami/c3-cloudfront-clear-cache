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

use Aws\Exception\AwsException;
use C3_CloudFront_Cache_Controller\WP;
use Aws\CloudFront\CloudFrontClient;

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
	 * Create the AWS SDK credential
	 *
	 * @param string $access_key AWS access key id.
	 * @param string $secret_key AWS secret access key id.
	 */
	public function create_credential( string $access_key = null, string $secret_key = null ) {
		$key    = isset( $access_key ) ? $access_key : $this->env->get_aws_access_key();
		$secret = isset( $secret_key ) ? $secret_key : $this->env->get_aws_secret_key();
		if ( ! isset( $key ) || ! isset( $secret ) ) {
			return null;
		}
		return new \Aws\Credentials\Credentials( $key, $secret );
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
	public function try_to_call_aws_api( string $distribution_id, string $access_key = null, string $secret_key = null ) {
		$credentials = $this->create_credential( $access_key, $secret_key );
		$params      = array(
			'version' => 'latest',
			'region'  => 'us-east-1',
		);
		if ( isset( $credentials ) ) {
			$params['credentials'] = $credentials;
		}
		$cloudfront = CloudFrontClient::factory( $params );
		try {
			$cloudfront->getDistribution(
				array(
					'Id' => $distribution_id,
				)
			);
			return null;
		} catch ( \Exception $e ) {
			if ( $e instanceof AwsException && 'NoSuchDistribution' === $e->getAwsErrorCode() ) {
				$e = new \WP_Error( 'C3 Auth Error', "Can not find CloudFront Distribution ID: {$distribution_id} is not found." );
			} elseif ( $e instanceof AwsException && 'InvalidClientTokenId' === $e->getAwsErrorCode() ) {
				$e = new \WP_Error( 'C3 Auth Error', 'AWS AWS Access Key or AWS Secret Key is invalid.' );
			} else {
				$e = new \WP_Error( 'C3 Auth Error', $e->getMessage() );
			}
			error_log( print_r( $e->get_error_messages(), true ), 0 );
			return $e;
		}
	}

	/**
	 * Create CloudFront Client
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
		 * Should use us-east-1 region, because CloudFront resources are always in there.
		 */
		$params = array(
			'version' => 'latest',
			'region'  => 'us-east-1',
		);

		/**
		 * If AWS credentials are available, will put it.
		 */
		if ( $options['access_key'] && $options['secret_key'] ) {
			$params['credentials'] = $credentials;
		}

		/**
		 * You can overwrite the CloudFront client constructor parameters
		 */
		$this->hook_service->apply_filters( 'c3_cloudfront_client_constructor', $params );

		$cloudfront = CloudFrontClient::factory( $params );
		return $cloudfront;
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
			$result = $client->createInvalidation( $params );
			return $result;
		} catch ( \Aws\CloudFront\Exception\CloudFrontException $e ) {
			error_log( $e->__toString(), 0 );
			$e = new \WP_Error( 'C3 Invalidation Error', $e->__toString() );
			return $e;
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
			$client          = $this->create_client();
			$distribution_id = $this->get_distribution_id();
			$lists           = $client->listInvalidations(
				array(
					'DistributionId' => $distribution_id,
					'MaxItems'       => $this->hook_service->apply_filters( 'c3_max_invalidation_logs', 25 ),
				)
			);
			if ( $lists['InvalidationList'] && $lists['InvalidationList']['Quantity'] > 0 ) {
				return $lists['InvalidationList']['Items'];
			}
			return array();
		} catch ( \Aws\CloudFront\Exception\CloudFrontException $e ) {
			if ( isset( $distribution_id ) && 'NoSuchDistribution' === $e->getAwsErrorCode() ) {
				error_log( $distribution_id . ' not found' );
			}
			error_log( $e->__toString(), 0 );
		} catch ( \Exception $e ) {
			error_log( $e->__toString(), 0 );
		} catch ( \Error $e ) {
			error_log( $e->__toString(), 0 );
		}
		return array();
	}
}
