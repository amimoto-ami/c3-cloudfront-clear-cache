<?php
/*
 * Plugin Name: C3 Cloudfront Cache Controller
 * Version: 4.3.0
 * Plugin URI:https://github.com/megumiteam/C3-Cloudfront-Clear-Cache
 * Description:This is simple plugin that clear all cloudfront cache if you publish posts.
 * Author: hideokamoto
 * Author URI: http://wp-kyoto.net/
 * Text Domain: c3-cloudfront-clear-cache
 * @package c3-cloudfront-clear-cache
 */

if ( c3_is_later_than_php_55() ) {
	require_once( dirname( __FILE__ ).'/vendor/autoload.php' );
} else {
	require_once( dirname( __FILE__ ).'/aws.phar' );
}
define( 'C3_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'C3_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'C3_PLUGIN_ROOT', __FILE__ );

require_once( dirname( __FILE__ ).'/module/includes.php' );

$c3 = C3_Controller::get_instance();
$c3->init();

function c3_is_later_than_php_55() {
	return false;
	if ( 5.5 > (float) phpversion() ) {
		return false;
	} else {
		return true;
	}
}

class C3_Controller {
	private $base;
	private static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	/**
	 *  Initialize Plugin
	 *
	 * @access public
	 * @param none
	 * @return none
	 * @since 4.0.0
	 */
	public function init() {
		$this->base = new C3_Base();
		$menu = C3_Menus::get_instance();
		$menu->init();
		add_action( 'admin_init',    array( $this, 'update_settings' ) );
		$invalidator = C3_Invalidation::get_instance();
		add_action( 'transition_post_status' , array( $invalidator, 'post_invalidation' ) , 10 , 3 );
		add_filter( 'amimoto_c3_add_settings', array( $this, 'inject_c3_admin_content' ) );
		add_action( 'c3_cron_invalidation', array( $invalidator, 'cron_invalidation' ) );
	}

	/**
	 * Inject C3 Content to AMIMOTO Dashboard
	 *
	 * @access public
	 * @since 4.2.0
	 * @param string $html
	 * @return string
	 **/
	public function inject_c3_admin_content( $html ) {
		$root = C3_Admin::get_instance();
		$html .= '<hr/>';
		$html .= $root->inject_to_amimoto_dashboard();
		return $html;
	}

	/**
	 *  Controller of C3 plugin
	 *
	 * @access public
	 * @param none
	 * @return none
	 * @since 4.0.0
	 */
	public function update_settings() {
		if ( empty( $_POST ) ) {
			return;
		}
		if ( $this->is_trust_post_param( C3_Base::C3_AUTHENTICATION ) ) {
			$options = $this->_esc_setting_param( $_POST[ C3_Base::OPTION_NAME ] );
			update_option( C3_Base::OPTION_NAME, $options );

			$auth = C3_Auth::get_instance();
			$result = $auth->auth( $options );
			if ( is_wp_error( $result ) ) {
				//@TODO Show WP Error message
			}
		}
		if ( $this->is_trust_post_param( C3_Base::C3_INVALIDATION ) ) {
			$invalidator = C3_Invalidation::get_instance();
			$result = $invalidator->invalidation();
			if ( is_wp_error( $result ) ) {
				//@TODO Show WP Error message
			}
		}

	}

	/**
	 *  Check plugin nonce key
	 *
	 * @access public
	 * @param none
	 * @return none
	 * @since 4.0.0
	 */
	private function is_trust_post_param( $key ) {
		if ( isset( $_POST[ $key ] ) && $_POST[ $key ] ) {
			if ( check_admin_referer( $key, $key ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Escape setting params
	 *
	 * @return array
	 * @since 4.0.0
	 * @access private
	 */
	private function _esc_setting_param( $param ) {
		$esc_param = array();
		foreach ( $param as $key => $value ) {
			$esc_param[ $key ] = esc_attr( $value );
		}
		return $esc_param;
	}

}

/**
 * Backward compatible ( Before version3.x)
 *
 * @class CloudFront_Clear_Cache
 * @since 1.0.0
 */
class CloudFront_Clear_Cache {
	private static $instance;
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public function c3_invalidation( $post = null ) {
		$invalidator = C3_Invalidation::get_instance();
		$result = $invalidator->invalidation( $post );
	}
}

// WP-CLI
if ( defined('WP_CLI') && WP_CLI ) {
	include __DIR__ . '/cli.php';
}
