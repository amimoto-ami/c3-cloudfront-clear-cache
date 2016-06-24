<?php
/**
 * C3_Component Class file
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package c3-cloudfront-clear-cache
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Define C3 Plugin's common comnponents
 *
 * @class C3_Component
 * @since 4.0.0
 */
class C3_Component extends C3_Base {
	private static $instance;
	private static $text_domain;

	private function __construct() {
		self::$text_domain = C3_Base::text_domain();
	}

	/**
	 * Get Instance Class
	 *
	 * @return C3_Component
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
	 *  Show C3 Plugin admin page html
	 *
	 * @access public
	 * @param none
	 * @since 4.0.0
	 */
	public function show_panel_html() {
		$content = $this->get_content_html();
		$html = $this->get_layout_html( $content );
		echo $html;
	}

	/**
	 *  Create C3 Plugin's admin page html
	 *
	 * @access public
	 * @param none
	 * @return string(HTML)
	 * @since 4.0.0
	 */
	public function get_layout_html( $content ) {
		$html  = "<div class='wrap' id='c3-dashboard'>";
		$html .= $content;
		$html .= '</div>';
		return $html;
	}
}
