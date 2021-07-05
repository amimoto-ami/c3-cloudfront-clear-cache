<?php
/**
 * Manage WP options
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Constants;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options class
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Options {
	/**
	 * Get the plugin setting attributes.
	 */
	public function get_options() {
		return get_option( Constants::OPTION_NAME );
	}

	/**
	 * Get the plugin setting attributes.
	 *
	 * @param mixed $params Plugin options.
	 */
	public function update_options( $params ) {
		return update_option( Constants::OPTION_NAME, $params );
	}

	/**
	 * Delete the plugin option
	 */
	public function delete_options() {
		return delete_option( Constants::OPTION_NAME );
	}

	/**
	 * Get the WordPress home url
	 *
	 * @param string $path The path.
	 */
	public function home_url( string $path ) {
		return home_url( $path );
	}
}
