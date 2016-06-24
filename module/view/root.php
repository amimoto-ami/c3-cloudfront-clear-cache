<?php
/**
 * C3_Admin Class file
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package c3-cloudfront-clear-cache
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * C3 Plugin admin page scripts
 *
 * @class C3_Admin
 * @since 4.0.0
 */
class C3_Admin extends C3_Component {
	private static $instance;
	private static $text_domain;
	public $amimoto_plugins = array();
	public $amimoto_uninstalled_plugins = array();

	private function __construct() {
		self::$text_domain = C3_Base::text_domain();
		$this->amimoto_plugins = $this->get_amimoto_plugin_file_list();
	}

	/**
	 * Get Instance Class
	 *
	 * @return C3_Admin
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
	 *  Show admin page html
	 *
	 * @access public
	 * @param none
	 * @return none
	 * @since 4.0.0
	 */
	public function init_panel() {
		$this->show_panel_html();
	}

	/**
	 *  Get admin page html content
	 *
	 * @access public
	 * @param none
	 * @return string(HTML)
	 * @since 4.0.0
	 */
	public function get_content_html() {
		$html = '';
		return $html;
	}
}
