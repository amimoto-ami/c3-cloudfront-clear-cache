<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Set cookie to avoid CloudFront cache if user sign in
 *
 * @since 5.1.0
 * @access public
 */
add_action( 'plugins_loaded', function(){
	if ( is_user_logged_in() ) {
		setcookie( 'wordpress_loginuser_last_visit', time() );
	}
});


/**
 * Unet cookie for avoid CloudFront cache when user sign out
 *
 * @since 5.1.0
 */
add_action( 'wp_logout', function(){
	setcookie('wordpress_loginuser_last_visit', '', time() - 1800);
});
