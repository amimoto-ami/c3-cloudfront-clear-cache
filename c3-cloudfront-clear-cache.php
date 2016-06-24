<?php
/*
 * Plugin Name: C3 Cloudfront Cache Controller
 * Version: 3.0.0
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
		$this->base = C3_Base::get_instance();
		$menu = C3_Menus::get_instance();
		$menu->init();
		add_action( 'admin_init',    array( $this, 'update_settings' ) );
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
			//@TODO Create Invalidation script
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

// Old Version

require_once( dirname( __FILE__ ).'/lib/c3-admin.php' );
if ( ! class_exists( 'CF_preview_fix' ) ) {
	require_once( dirname( __FILE__ ).'/lib/cf-preview-fix.php' );
}

use Aws\CloudFront\CloudFrontClient;
use Aws\Common\Credentials\Credentials;

//$c3 = CloudFront_Clear_Cache::get_instance();
//$c3->add_hook();

if ( defined('WP_CLI') && WP_CLI ) {
	include __DIR__ . '/cli.php';
}


class CloudFront_Clear_Cache {
	private static $instance;

	const OPTION_NAME = 'c3_settings';

	private function __construct() {}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public function add_hook() {
		add_action( 'transition_post_status' , array( $this, 'c3_start_invalidation' ) , 10 , 3 );
		add_filter( 'c3_credential', array( $this, 'create_credentials' ), 10 );
	}

	public static function version() {
		static $version;

		if ( ! $version ) {
			$data = get_file_data( __FILE__ , array( 'version' => 'Version' ) );
			$version = $data['version'];
		}
		return $version;
	}

	public static function text_domain() {
		static $text_domain;

		if ( ! $text_domain ) {
			$data = get_file_data( __FILE__ , array( 'text_domain' => 'Text Domain' ) );
			$text_domain = $data['text_domain'];
		}
		return $text_domain;
	}

	private function c3_is_invalidation ( $new_status, $old_status ) {
		if ( 'publish' === $new_status ) {
			//if publish or update posts.
			$result = true;
		} elseif ( 'publish' === $old_status && $new_status !== $old_status ) {
			//if un-published post.
			$result = true;
		} else {
			$result = false;
		}
		$result = apply_filters( 'c3_is_invalidation' , $result );
		return $result;
	}

	private function c3_get_settings() {
		$c3_settings = get_option( self::OPTION_NAME );
		if ( ! is_array( $c3_settings ) ) {
			return false;
		}

		$c3_settings = apply_filters( 'c3_get_setting', $c3_settings );
		//IF not complete setting param. stop working.
		foreach ( $c3_settings as $key => $value ) {
			if ( ! $value ) {
				return false;
			}
		}
		return $c3_settings;
	}

	public function c3_start_invalidation ( $new_status, $old_status, $post ) {
		if ( ! $this->c3_is_invalidation( $new_status , $old_status ) ) {
			return;
		}
		$this->c3_invalidation( $post );
	}

	public function create_credentials() {
		/*
		$c3_settings = $this->c3_get_settings();
		$credentials = array(
			'credentials' => new Credentials( esc_attr( $c3_settings['access_key'] ) , esc_attr( $c3_settings['secret_key'] ) ),
		);
		return $credentials;
		*/
	}

	public function c3_invalidation( $post = null ) {
		$key = 'exclusion-process';
		if ( apply_filters( 'c3_invalidation_flag', get_transient( $key ) ) ) {
			return;
		}

		$c3_settings = $this->c3_get_settings();
		if ( ! $c3_settings ) {
			return;
		}

		$credential = null;
		$credential = apply_filters( 'c3_credential', $credential );
		if( $credential ) {
			$cloudFront = CloudFrontClient::factory( $credential );
		} else {
			$cloudFront = CloudFrontClient::factory();
		}

		$args = $this->c3_make_args( $c3_settings, $post );

		set_transient( $key , true , 5 * 60 );
		try {
			$result = $cloudFront->createInvalidation( $args );
		} catch ( Aws\CloudFront\Exception\TooManyInvalidationsInProgressException $e ) {
			error_log( $e->__toString( ) , 0 );
		} catch ( Aws\CloudFront\Exception\AccessDeniedException $e ) {
			error_log( $e->__toString( ) , 0 );
		} catch ( Exception $e ) {
			error_log( $e->__toString( ) , 0 );
		}
	}

	private function c3_make_invalidate_path( $url ) {
		$parse_url = parse_url( $url );
		return isset( $parse_url['path'] )
			? $parse_url['path']
			: preg_replace( array( '#^https?://[^/]*#', '#\?.*$#' ), '', $url );
	}

	private function c3_make_args( $c3_settings, $post = null ) {
		$items = array();
		$post = get_post( $post );
		if ( $post && ! is_wp_error( $post ) ) {
			// home
			$items[] = $this->c3_make_invalidate_path( home_url( '/' ) );

			// single page permalink
			$items[] = $this->c3_make_invalidate_path( get_permalink( $post ) ) . '*';
			// term archives permalink
			$taxonomies = get_object_taxonomies( $post->post_type );
			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_post_terms( $post->ID, $taxonomy );
				if ( is_wp_error( $terms ) ) {
					continue;
				}
				foreach ( $terms as $term ) {
					$parsed_url = parse_url( get_term_link( $term, $taxonomy ) );
					$url = $parsed_url['scheme'] . '://' . $parsed_url['host']. $parsed_url['path'];
					if ( trailingslashit( home_url() ) === $url ) {
						continue;
					}
					$item = $items[] = $this->c3_make_invalidate_path( get_term_link( $term, $taxonomy ) ) . '*';
				}
			}
		} else {
			// ALL URL
			$items[] = '/*';
		}

		if ( 10 < count( $items ) ) {
			$items = array( '/*' );
		}

		$items = apply_filters( 'c3_invalidation_items' , $items , 	$post );

		return array(
			'DistributionId' => esc_attr( $c3_settings['distribution_id'] ),
			'Paths' => array(
				'Quantity' => count( $items ),
				'Items'    => $items,
			),
			'CallerReference' => uniqid(),
		);
	}
}
