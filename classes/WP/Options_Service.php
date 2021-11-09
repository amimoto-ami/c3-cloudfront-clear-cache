<?php
/**
 * WP Options management service
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\WP;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use C3_CloudFront_Cache_Controller\AWS;

/**
 * Options service class
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Options_Service {
	/**
	 * Env class
	 *
	 * @var Environment
	 */
	private $env;

	/**
	 * Option
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Hook
	 *
	 * @var Hooks
	 */
	private $hook_service;


	/**
	 * Inject a external services
	 *
	 * @param mixed ...$args Inject class.
	 */
	function __construct( ...$args ) {
		$this->hook_service = new Hooks();
		$this->env          = new Environment();
		$this->options      = new Options();

		if ( $args && ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				if ( $value instanceof Hooks ) {
					$this->hook_service = $value;
				} elseif ( $value instanceof Environment ) {
					$this->env = $value;
				} elseif ( $value instanceof Options ) {
					$this->options = $value;
				}
			}
		}
	}

	/**
	 * Get the home url
	 *
	 * @param string $path Target path.
	 */
	public function home_url( string $path ) {
		return $this->options->home_url( $path );
	}

	/**
	 * Test the requested parameter and save it
	 *
	 * @param string $distribution_id CloudFront distribution id.
	 * @param string $access_key AWS access key id.
	 * @param string $secret_key AWS secret access key id.
	 * @return void
	 */
	public function update_options( string $distribution_id, string $access_key = null, string $secret_key = null ) {
		$options = array(
			'distribution_id' => $distribution_id,
		);

		if ( $access_key ) {
			$options['access_key'] = $access_key;
		}
		if ( $secret_key ) {
			$options['secret_key'] = $secret_key;
		}

		$this->options->update_options( $options );
	}

	/**
	 * Get the plugin options.
	 * If returns null, you can not call AWS API because there is no credentials to request to it.
	 */
	public function get_options() {
		$filter_name = 'c3_setting';

		/**
		 * You can put these parameters by using `define( 'KEY_NAME', 'ATTRIBUTES' );`.
		 * These defined parameters are using first.
		 */
		$results = array(
			'distribution_id' => $this->env->get_distribution_id(),
			'access_key'      => $this->env->get_aws_access_key(),
			'secret_key'      => $this->env->get_aws_secret_key(),
		);

		/**
		 * If all parameters are fulfilled, should use it.
		 */
		if ( count( $results ) === count( array_filter( $results ) ) ) {
			return $results;
		}

		/**
		 * If using the plugin in the AMIMOTO Managed hosting,
		 * The WordPress must use the EC2 Instance Role and defined Distribution ID.
		 */
		if ( $this->env->is_amimoto_managed() ) {
			return $this->hook_service->apply_filters(
				$filter_name,
				array(
					'distribution_id' => $results['distribution_id'],
					'access_key'      => null,
					'secret_key'      => null,
				)
			);
		}

		/**
		 * If not AMIMOTO Managed, we have to load these information from the wp_options table.
		 */
		$options = $this->options->get_options();
		if ( $options ) {
			if ( isset( $options['distribution_id'] ) ) {
				$results['distribution_id'] = $options['distribution_id'];
			}
			if ( isset( $options['access_key'] ) ) {
				$results['access_key'] = $options['access_key'];
			}
			if ( isset( $options['secret_key'] ) ) {
				$results['secret_key'] = $options['secret_key'];
			}
		}

		if ( 0 === count( array_filter( $results ) ) ) {
			$results = null;
		}
		return $this->hook_service->apply_filters( $filter_name, $results );
	}
}
