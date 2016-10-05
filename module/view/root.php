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
		$html .= $this->_get_auth_form();
		$html .= $this->_get_manual_invalidation_form();
		$html .= $this->get_invalidation_logs();
		return $html;
	}

	/**
	 * Inject content to AMIMOTO Dashboard
	 *
	 * @since 4.2.0
	 * @access public
	 * @return string
	 **/
	public function inject_to_amimoto_dashboard() {
		$html  = '';
		$html .= $this->get_invalidation_logs();
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
			$html .= '<th>　'. esc_html( $title ). '</th>';
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
		$c3_settings = get_option( self::OPTION_NAME );
		$html = '';
		if ( ! $c3_settings ) {
			return $html;
		}
		$html .= "<form method='post' action=''>";
		$html .= "<table class='wp-list-table widefat plugins'>";
		$html .= '<thead>';
		$html .= "<tr><th colspan='2'><h2>" . __( 'CloudFront Cache Control', self::$text_domain ). '</h2></th></tr>';
		$html .= '</thead>';
		$html .= '<tbody>';
		$html .= '<tr><th><b>'. __( 'Flush All Cache', self::$text_domain ). '</b><br/>';
		$html .= '<small>'. __( "Notice: Every page's cache is removed." , self::$text_domain ). '</small></th>';
		$html .= '<td>';
		$html .= "<input type='hidden' name='invalidation_target' value='all' />";
		$html .= wp_nonce_field( self::C3_INVALIDATION , self::C3_INVALIDATION , true , false );
		$html .= get_submit_button( __( 'Flush All Cache', self::$text_domain ) );
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</tbody></table>';
		$html .= '</form>';
		$html .= '<hr/>';
		return $html;
	}

	/**
	 * Get Invalidation Logs
	 *
	 * @access public
	 * @since 4.1.0
	 * @return string
	 **/
	public function get_invalidation_logs() {
		$c3_settings = get_option( self::OPTION_NAME );
		$html = '';
		if ( ! $c3_settings ) {
			return $html;
		}
		$logs = new C3_Logs();
		$invalidations = $logs->list_invalidations();
		$html .= "<table class='wp-list-table widefat plugins'>";
		$html .= '<thead>';
		$html .= "<tr><th colspan='3'><h2>" . __( 'CloudFront Invalidation Logs', self::$text_domain ). '</h2></th></tr>';
		$html .= '<tr>';
		$html .= '<th><b>'. __( 'Invalidation Start Time (UTC)', self::$text_domain ). '</b></th>';
		$html .= '<th><b>'. __( 'Invalidation Status', self::$text_domain ). '</b></th>';
		$html .= '<th><b>'. __( 'Invalidation Id', self::$text_domain ). '</b></th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';
		if ( $invalidations ) {
			foreach ( $invalidations as $invalidation ) {
				$time = date_i18n( 'y/n/j G:i:s', strtotime( $invalidation['CreateTime'] ) );
				$html .= "<tr><td>{$time}</td>";
				$html .= "<td>{$invalidation['Status']}</td><td>{$invalidation['Id']}</td></tr>";
			}
		} else {
			$html .= "<tr><th colspan='3'>". __( 'There is no invalidations', self::$text_domain ). '</td></tr>';
		}
		$html .= '</tbody></table>';
		return $html;
	}
}
