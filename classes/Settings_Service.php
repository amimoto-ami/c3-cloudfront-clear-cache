<?php
namespace C3_CloudFront_Cache_Controller;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use C3_CloudFront_Cache_Controller\AWS;
use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Constants;

class Settings_Service {
	private $env;
	private $options_service;
	private $hook_service;
	private $cf_service;
	/**
	 * Inject a external services
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
	 */
	public function update_options( string $distribution_id, string $access_key = null, string $secret_key = null ) {
		// null check
		if ( ! $distribution_id ) {
			throw new \WP_Error( 'distribution id is required' );
		}

		// CloudFront API call
		$this->cf_service->try_to_call_aws_api( $distribution_id, $access_key, $secret_key );

		// Save
		$result = $this->options_service->update_options( $distribution_id, $access_key, $secret_key );
		return $result;
	}

	/**
	 * Get the plugin options.
	 * If returns null, you can not call AWS API because there is no credentials to request to it.
	 */
	public function get_options() {
		return $this->options_service->get_options();
	}
}
