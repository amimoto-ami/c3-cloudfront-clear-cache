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
	 * Retrieve posts matching the provided post IDs.
	 *
	 * Queries WordPress for posts whose IDs are in $post_ids (searches across all post types)
	 * and returns the resulting array of WP_Post objects. Resets global post data after the query.
	 *
	 * @param int[] $post_ids Array of post IDs to fetch.
	 * @return \WP_Post[] Array of posts matching the given IDs (may be empty).
	 */
	public function list_posts_by_ids( $post_ids ) {
		$query = new \WP_Query(
			array(
				'post__in' => $post_ids,
				'post_type' => 'any'
			)
		);
		$posts = $query->get_posts();
		wp_reset_postdata();
		return $posts;
	}
}
