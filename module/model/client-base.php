<?php
/**
 * C3_Client_Base
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-plugin-dashboard
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CloudFront Client Base
 *
 * @class C3_Client_Base
 * @since 4.0.0
 */
class C3_Client_Base extends C3_Base {
	private static $instance;
	private static $text_domain;

	private function __construct() {
		self::$text_domain = C3_Base::text_domain();
	}

	/**
	 * Get Instance Class
	 *
	 * @return C3_Auth
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
	 * Create Invalidation Query
	 *
	 * @return array
	 * @since 4.0.0
	 * @access public
	 */
	public function get_invalidation_items( $options, $post = false ) {
		$items = array();
		$post = get_post( $post );
		if ( $post && ! is_wp_error( $post ) ) {
			$items = $this->_get_invalidation_items_from_post( $post );
		} else {
			$items[] = '/*';
		}

		if ( 10 < count( $items ) ) {
			$items = array( '/*' );
		}
		$items = apply_filters( 'c3_invalidation_items' , $items , 	$post );
		return $items;
	}

	/**
	 * Create Invalidation Items list from post
	 *
	 * @return array
	 * @param WP_Post
	 * @since 4.0.0
	 * @access private
	 */
	private function _get_invalidation_items_from_post( $post ) {
		// home
		$items[] = $this->_make_invalidate_path( home_url( '/' ) );

		// single page permalink
		$items[] = $this->_make_invalidate_path( get_permalink( $post ) ) . '*';
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
				$items[] = $this->_make_invalidate_path( get_term_link( $term, $taxonomy ) ) . '*';
			}
		}
		return $items;
	}

	/**
	 * Create Invalidation path from url
	 *
	 * @return (string) $url
	 * @param string
	 * @since 4.0.0
	 * @access private
	 */
	private function _make_invalidate_path( $url ) {
		$parse_url = parse_url( $url );
		return isset( $parse_url['path'] )
			? $parse_url['path']
			: preg_replace( array( '#^https?://[^/]*#', '#\?.*$#' ), '', $url );
	}
}
