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

	private function __construct() {
		self::$text_domain = C3_Base::text_domain();
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
		$html .= $this->_get_header();
		$html .= $this->_get_manual_invalidation_form();
		$html .= $this->_get_auth_form();
		return $html;
	}

	/**
	 *  Get pugin root admin header HTML
	 *
	 * @access public
	 * @param none
	 * @return string(HTML)
	 * @since 4.0.0
	 */
	private function _get_header() {
		$html  = '';
		$html .= '<h2>'.  __( 'C3 Cloudfront Cache Controller' , self::$text_domain ). '</h2>';
		$html .= apply_filters( 'c3_after_title', $html );

		return $html;
	}

	/**
	 *  get CloudFront Auth Form HTML
	 *
	 * @access public
	 * @param none
	 * @return string(HTML)
	 * @since 4.0.0
	 */
	private function _get_auth_form() {
		$c3_settings = $this->get_c3_options();
		$c3_settings_keys = $this->get_c3_options_name();
		$html  = '';
		$html .= '<h3>'. __( 'General Settings', self::$text_domain ). '</h3>';
		$html .= "<form method='post' action='' >";
		$html .= "<table class='widefat form-table'><tbody>";
		foreach ( $c3_settings_keys as $key => $title ) {
			$name = self::OPTION_NAME. esc_attr( "[{$key}]" );
			$key  = esc_attr( $key );
			$value = esc_attr( $c3_settings[ $key ] );
			$input = "<input name='{$name}' type='text' id='{$key}' value='{$value}' class='regular-text code' / >";
			$html .= '<tr>';
			$html .= '<th>'. esc_html( $title ). '</th>';
			$html .= "<td>{$input}</td>";
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';
		$html .= get_submit_button( __( 'Save Change' , self::$text_domain ) , 'primary large' );
		$html .= wp_nonce_field( self::C3_AUTHENTICATION , self::C3_AUTHENTICATION , true , false );
		$html .= '</form>';
		return apply_filters( 'c3_after_auth_form', $html );
	}

	/**
	 *  get CloudFront invalidation form
	 *
	 * @access public
	 * @param none
	 * @return string(HTML)
	 * @since 4.0.0
	 */
	private function _get_manual_invalidation_form() {

	}
}
