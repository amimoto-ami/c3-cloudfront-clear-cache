<?php
/**
 * Manage the plugin settings
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use C3_CloudFront_Cache_Controller\AWS;
use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Constants;

/**
 * Setting service
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Settings_Service {
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
	 * CloudFront service
	 *
	 * @var AWS\CloudFront_Service
	 */
	private $cf_service;

	/**
	 * Inject a external services
	 *
	 * @param mixed ...$args Inject class.
	 */
	function __construct( ...$args ) {
		$this->hook_service    = new WP\Hooks();
		$this->env             = new WP\Environment();
		$this->options_service = new WP\Options_Service();
		$this->cf_service      = new AWS\CloudFront_Service();

		if ( $args && ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				if ( $value instanceof WP\Hooks ) {
					$this->hook_service = $value;
				} elseif ( $value instanceof WP\Environment ) {
					$this->env = $value;
				} elseif ( $value instanceof WP\Options_Service ) {
					$this->options_service = $value;
				} elseif ( $value instanceof AWS\CloudFront_Service ) {
					$this->cf_service = $value;
				}
			}
		}
	}

	/**
	 * Test the requested parameter and save it
	 *
	 * @param string $distribution_id CloudFront distribution id.
	 * @param string $access_key AWS access key id.
	 * @param string $secret_key AWS secret access key id.
	 * @return \WP_Error|null
	 */
	public function update_options( string $distribution_id, string $access_key = null, string $secret_key = null ) {
		// CloudFront API call.
		$error = $this->cf_service->try_to_call_aws_api( $distribution_id, $access_key, $secret_key );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		// Save.
		$this->options_service->update_options( $distribution_id, $access_key, $secret_key );

		return null;
	}

	/**
	 * Get the plugin options.
	 * If returns null, you can not call AWS API because there is no credentials to request to it.
	 */
	public function get_options() {
		return $this->options_service->get_options();
	}
}
