<?php
namespace C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Constants;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Options {
	/**
	 * Get the plugin setting attributes.
	 */
	public function get_options() {
		return get_option( Constants::OPTION_NAME );
	}

	/**
	 * Get the plugin setting attributes.
	 */
	public function update_options( $params ) {
		return update_option( Constants::OPTION_NAME, $params );
	}

	public function delete_options() {
		return delete_option( Constants::OPTION_NAME );
	}

	public function home_url( string $path ) {
		return home_url( $path );
	}
}
