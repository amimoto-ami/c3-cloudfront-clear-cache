<?php
/**
 * WP Hook adapter
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Hooks {
	/**
	 * Apply filter
	 *
	 * @param string $name Hook name.
	 * @param mixed  $value Hooked value.
	 */
	public function apply_filters( string $name, $value ) {
		return apply_filters( $name, $value );
	}

	/**
	 * Add action hook
	 *
	 * @param string $tag Hook tag name.
	 * @param mixed  $function_to_add Execute hook action.
	 * @param int    $priority Hook priority.
	 * @param int    $accepted_args Accepted args from the hook.
	 */
	public function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return add_action( $tag, $function_to_add, $priority, $accepted_args );
	}

	/**
	 * Add action filter
	 *
	 * @param string $tag Hook tag name.
	 * @param mixed  $function_to_add Execute hook action.
	 * @param int    $priority Hook priority.
	 * @param int    $accepted_args Accepted args from the hook.
	 */
	public function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return add_filter( $tag, $function_to_add, $priority, $accepted_args );
	}
}
