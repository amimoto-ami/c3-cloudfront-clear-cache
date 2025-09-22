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
	 * Queries WordPress for posts whose IDs are in $post_ids (searches only public post types)
	 * and returns the resulting array of WP_Post objects. Resets global post data after the query.
	 *
	 * @param int[] $post_ids Array of post IDs to fetch.
	 * @return \WP_Post[] Array of posts matching the given IDs (may be empty).
	 */
	public function list_posts_by_ids( $post_ids ) {
		if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
			return array();
		}

		$post_ids = array_filter( array_map( 'intval', $post_ids ), function( $id ) {
			return $id > 0;
		} );
		if ( empty( $post_ids ) ) {
			return array();
		}

		$public_post_types = array_values( get_post_types( array( 'public' => true ), 'names' ) );

		$query = new \WP_Query(
			array(
				'post__in' => $post_ids,
				'post_type' => $public_post_types,
				'posts_per_page' => -1,
				'no_found_rows' => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		$posts = $query->get_posts();
		wp_reset_postdata();
		return $posts;
	}
}
