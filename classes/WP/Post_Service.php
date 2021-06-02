<?php
namespace C3_CloudFront_Cache_Controller\WP;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Post_Service {
    public function list_posts_by_ids( $post_ids ) {
        $query    = new \WP_Query(
            array(
                'post__in' => $post_ids,
            )
        );
        $posts    = $query->get_posts();
        wp_reset_postdata();
        return $posts;
    }
} 