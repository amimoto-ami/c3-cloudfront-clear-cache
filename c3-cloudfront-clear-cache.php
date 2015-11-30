<?php
/*
Plugin Name: C3 Cloudfront Clear Cache
Version: 2.0.1
Plugin URI:https://github.com/megumiteam/C3-Cloudfront-Clear-Cache
Description:This is simple plugin that clear all cloudfront cache if you publish posts.
Author: hideokamoto
Author URI: http://wp-kyoto.net/
Text Domain: c3_cloudfront_clear_cache
*/
require_once( dirname( __FILE__ ).'/aws.phar' );
require_once( dirname( __FILE__ ).'/lib/c3-admin.php' );
use Aws\CloudFront\CloudFrontClient;
use Aws\Common\Credentials\Credentials;

$c3 = CloudFront_Clear_Cache::get_instance();
$c3->add_hook();


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
		add_action( 'transition_post_status' , array( $this, 'c3_invalidation' ) , 10 , 3 );
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
			//記事公開または記事編集時
			$result = true;
		} elseif ( 'publish' === $old_status && $new_status !== $old_status ) {
			//記事を非公開にした際
			$result = true;
		} else {
			$result = false;
		}
		return $result;
	}

	private function c3_get_settings() {
		$c3_settings = get_option( self::OPTION_NAME );
		//ひとつでも空の値があれば以降の処理を止める
		foreach ( $c3_settings as $key => $value ) {
			if ( ! $value ) {
				return false;
			}
		}
		return $c3_settings;
	}

	public function c3_invalidation ( $new_status, $old_status, $post ) {
		if ( ! $this->c3_is_invalidation( $new_status , $old_status ) ) {
			return;
		}

		$key = 'exclusion-process';
		if ( get_transient( $key ) ) {
			return;
		}

		$c3_settings = $this->c3_get_settings();
		if ( ! $c3_settings ) {
			return;
		}

		//CloudFrontクラスを初期化
		$credentials = new Credentials( esc_attr( $c3_settings['access_key'] ) , esc_attr( $c3_settings['secret_key'] ) );
		$cloudFront = CloudFrontClient::factory(array(
			'credentials' => $credentials,
		));

		$args = $this->c3_make_args( $c3_settings, $post );

		set_transient( $key , true , 5 * 60 );
		try {
			$result = $cloudFront->createInvalidation( $args );
		} catch ( Aws\CloudFront\Exception\TooManyInvalidationsInProgressException $e ) {
			error_log( $e->__toString( ) , 0 );
		}
	}

	private function c3_make_invalidate_path( $url ) {
		$parse_url = parse_url( $url );
		return isset( $parse_url['path'] )
			? $parse_url['path']
		: preg_replace( array( '#^https?://[^/]*#', '#\?.*$#' ), '', $url );
	}

	private function c3_make_args( $c3_settings, $post ) {
		$post = get_post( $post );
		$categories = wp_get_post_categories( $post->ID );
		$items = array( '/' );
		$items[] = $this->c3_make_invalidate_path( get_permalink( $post ) ) . '*';
		foreach ( $categories as $category ) {
			$items[] = $this->c3_make_invalidate_path( get_category_link( $category ) ) . '*';
		}

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
