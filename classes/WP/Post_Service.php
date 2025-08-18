<?php
/**
 * WordPress Post management service
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\WP;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post service
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Post_Service {
	/**
	 * WP Hook service
	 *
	 * @var Hooks
	 */
	private $hook_service;

	/**
	 * Inject a external services
	 *
	 * @param mixed ...$args Inject class.
	 */
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
	}

	/**
	 * Get the target post ids
	 *
	 * @param array $post_ids The target post ids.
	 */
	public function list_posts_by_ids( $post_ids ) {
		$default_args = array(
			'post__in' => $post_ids,
			'post_type' => 'post',
		);
		
		$query_args = $this->hook_service->apply_filters( 'c3_post_service_query_args', $default_args, $post_ids );
		
		$query = new \WP_Query( $query_args );
		$posts = $query->get_posts();
		wp_reset_postdata();
		return $posts;
	}
}
