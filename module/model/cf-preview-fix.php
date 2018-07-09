<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( class_exists( 'CF_preview_fix' ) ) {
	return;
}
add_action( 'init', function(){
	$cf_fix = CF_preview_fix::get_instance();
	$cf_fix->add_hook();
});

/**
 * Fixture for post preview
 *
 * @class CF_preview_fix
 * @since 4.0.0
 */
class CF_preview_fix {
	private static $instance;

	private function __construct() {}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public function add_hook() {
		add_action( 'plugins_loaded', array( $this, 'set_loginuser_cookie') );
		add_action( 'wp_logout', array( $this, 'unset_loginuser_cookie') );
	}

	/**
	 * Set 'wordpress_loginuser_last_visit' user cookie if user logged in
	 *
	 * @since 5.4.0
	 */
	public function set_loginuser_cookie() {
		if ( is_user_logged_in() ) {
			setcookie( 'wordpress_loginuser_last_visit', time() );
		}
	}
	/**
	 * unet 'wordpress_loginuser_last_visit' user cookie if user logout
	 *
	 * @since 5.4.0
	 */
	public function unset_loginuser_cookie() {
		setcookie('wordpress_loginuser_last_visit', '', time() - 1800);
	}
}
