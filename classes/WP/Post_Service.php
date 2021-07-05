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
	 * Get the target post ids
	 *
	 * @param array $post_ids The target post ids.
	 */
	public function list_posts_by_ids( $post_ids ) {
		$query = new \WP_Query(
			array(
				'post__in' => $post_ids,
			)
		);
		$posts = $query->get_posts();
		wp_reset_postdata();
		return $posts;
	}
}
