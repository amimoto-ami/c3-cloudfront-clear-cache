<?php
namespace C3_CloudFront_Cache_Controller\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fixtures {
	private $hook_service;

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

		if ( ! defined( 'C3_AVOID_CACHE_COOKIE_KEY' ) ) {
			define( 'C3_AVOID_CACHE_COOKIE_KEY', 'wordpress_loginuser_last_visit' );
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

	public function set_avoid_cache_cookie() {
		if ( is_user_logged_in() ) {
			$cookie_path = preg_replace( '#^https?://[^/]+/?#', '/', home_url( '/' ) );
			// Thanks for Human Made team!
			// @see https://github.com/amimoto-ami/c3-cloudfront-clear-cache/issues/53
			setcookie(
				C3_AVOID_CACHE_COOKIE_KEY,
				time(),
				array(
					'expires'  => 0,
					'samesite' => 'None',
					'secure'   => true,
					'path'     => $cookie_path,
				)
			);
		}
	}

	public function unset_avoid_cache_cookie() {
		$cookie_path = preg_replace( '#^https?://[^/]+/?#', '/', home_url( '/' ) );
		// Thanks for Human Made team!
		// @see https://github.com/amimoto-ami/c3-cloudfront-clear-cache/issues/53
		setcookie(
			C3_AVOID_CACHE_COOKIE_KEY,
			'',
			array(
				'expires'  => time() - 1800,
				'samesite' => 'None',
				'secure'   => true,
				'path'     => $cookie_path,
			)
		);
	}
}
