<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'C3_AVOID_CACHE_COOKEY_KEY' ) ) {
	define( 'C3_AVOID_CACHE_COOKEY_KEY', 'wordpress_loginuser_last_visit' );
}

/**
 * Set cookie to avoid CloudFront cache if user sign in
 *
 * @since 5.1.0
 * @access public
 */
add_action( 'plugins_loaded', function() {
	if ( is_user_logged_in() ) {
		$cookie_path = preg_replace( '#^https?://[^/]+/?#','/',home_url( '/' ) );
		setcookie( C3_AVOID_CACHE_COOKEY_KEY, time(), 0, $cookie_path );
	}
} );


/**
 * Unet cookie for avoid CloudFront cache when user sign out
 *
 * @since 5.1.0
 */
add_action( 'wp_logout', 'c3_unset_avoid_cache_cookie' );

/**
 * Unet cookie for avoid CloudFront cache when user deactivate the plugin
 *
 * @since 5.1.0
 */
register_deactivation_hook( C3_PLUGIN_ROOT, 'c3_unset_avoid_cache_cookie' );

/**
 * Function unet cookie for avoid CloudFront cache when user sign out
 *
 * @since 5.1.0
 */
function c3_unset_avoid_cache_cookie() {
	$cookie_path = preg_replace( '#^https?://[^/]+/?#','/',home_url( '/' ) );
	setcookie( C3_AVOID_CACHE_COOKEY_KEY, '', time() - 1800, $cookie_path );
}
