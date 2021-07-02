<?php
namespace C3_CloudFront_Cache_Controller\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Fixtures {
	private $hook_service;
	private $cookie_key = 'wordpress_loginuser_last_visit';

	function __construct( ...$args ) {
		if ( $args && ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				if ( $value instanceof Hooks ) {
					$this->hook_service = $value;
				}
			}
		}
		if ( ! $this->hook_service ) {
			$this->hook_service = new Hooks();
		}

		$this->hook_service->add_filter( 'wp_is_mobile', array( $this, 'cloudfront_is_mobile' ) );
		$this->hook_service->add_action( 'plugins_loaded', array( $this, 'set_avoid_cache_cookie' ) );
		$this->hook_service->add_action( 'wp_logout', array( $this, 'unset_avoid_cache_cookie' ) );

		if ( defined( 'C3_AVOID_CACHE_COOKIE_KEY' ) && C3_AVOID_CACHE_COOKIE_KEY ) {
			$this->cookie_key = C3_AVOID_CACHE_COOKIE_KEY;
		}
	}

	/**
	 * Detect the viewer option from CloudFront,
	 * and overwrite wp_is_mobile result
	 */
	public function cloudfront_is_mobile( $is_mobile ) {
		// CloudFront でスマートフォンと判定された場合、true を返す。
		if ( isset( $_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'] ) && 'true' === $_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'] ) {
			$is_mobile = true;
		}

		// CloudFront でタブレットと判定された場合、true を返す。
		// （タブレットはPCと同じ扱いにしたい場合は、$is_mobile を false にする
		if ( isset( $_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER'] ) && 'true' === $_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER'] ) {
			$is_mobile = true;
		}

		return $is_mobile;
	}

	private function set_cookie( $key, $value, $expires = 0 ) {
		$cookie_path = preg_replace( '#^https?://[^/]+/?#', '/', home_url( '/' ) );
		// Thanks for Human Made team!
		// @see https://github.com/amimoto-ami/c3-cloudfront-clear-cache/issues/53
		if ( version_compare( '7.3.0', phpversion(), '>=' ) ) {
			// PHP 7.3.0 or higher
			$args = array(
				'expires'  => $expires,
				'samesite' => 'None',
				'secure'   => true,
				'path'     => $cookie_path,
			);
			setcookie( $key, $value, $args );
		} else {
			// Less than PHP 7.3.0
			setcookie( $key, $value, $expires, $cookie_path, '', true, true ); 
		}
	}

	public function set_avoid_cache_cookie() {
		if ( is_user_logged_in() ) {
			$this->set_cookie( $this->cookie_key, time(), 0 );
		}
	}

	public function unset_avoid_cache_cookie() {
		$this->set_cookie( $this->cookie_key, '', time() - 1800 );
	}
}
