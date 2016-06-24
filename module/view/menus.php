<?php
/**
 * C3_Menus
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package c3-cloudfront-clear-cache
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Define C3 Plugin's admin page menus
 *
 * @class C3_Menus
 * @since 4.0.0
 */
class C3_Menus extends C3_Base {
	private static $instance;
	private static $text_domain;
	private function __construct() {
		self::$text_domain = C3_Base::text_domain();
	}

	/**
	 * Get Instance Class
	 *
	 * @return C3_Menus
	 * @since 4.0.0
	 * @access public
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	/**
	 *  Init plugin menu.
	 *
	 * @access public
	 * @param none
	 * @since 4.0.0
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'define_menus' ) );
	}

	/**
	 *  Define C3 plugin menus
	 *
	 * @access public
	 * @param none
	 * @since 4.0.0
	 */
	public function define_menus() {
		$root = C3_Admin::get_instance();
		add_menu_page(
			__( 'CloudFront Settings', self::$text_domain ),
			__( 'CloudFront Settings', self::$text_domain ),
			'administrator',
			self::MENU_ID,
			array( $root, 'init_panel' )
		);
	}
}
