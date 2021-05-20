<?php

namespace C3_CloudFront_Cache_Controller\AWS;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
use C3_CloudFront_Cache_Controller\WP\Hooks;
use C3_CloudFront_Cache_Controller\WP\Post;

class Invalidation_Batch_Service {
    private $hook_service;
    private $post;

    /**
     * Inject a external services
     */
    function __construct(...$args) {
        if ( $args && ! empty( $args ) ) {
            foreach ( $args as $key => $value ) {
				if ( $value instanceof Hooks ) {
                    $this->hook_service = $value;
                } else if ( $value instanceof Post ) {
                    $this->post = $value;
                }
            }
        }
        if ( ! $this->hook_service ) $this->hook_service = new Hooks();
        if ( ! $this->post ) $this->post = new Post();
    }

    public function set_post( $post ) {
        $this->post->set_post( $post );
    }

    /**
     * Invalidate 
     */
    public function create_batch_by_post( string $home_url, string $distribution_id, \WP_Post $post = null ) {
        /**
         * @see https://github.com/amimoto-ami/c3-cloudfront-clear-cache/pull/54/files
         */
		if ( 'trash' === $post->post_status ) {
			// For trashed post, get the permalink when it was published.
			$post->post_status = 'publish';
		}

        if ( $post ) $this->set_post( $post );
        $this->invalidation_batch = new Invalidation_Batch();
        $this->invalidation_batch->put_invalidation_path( $home_url );
        $this->invalidation_batch->put_invalidation_path( $this->post->get_permalink() . '*' );
        $term_links = $this->post->get_the_post_term_links();
        foreach ( $term_links as $key => $url ) {
            $this->invalidation_batch->put_invalidation_path( $url );
        }

        return $this->invalidation_batch->get_invalidation_request_parameter( $distribution_id );
    }

    /**
     * Invalidate all cache
     */
    public function create_batch_for_all( string $distribution_id ) {
        $this->invalidation_batch = new Invalidation_Batch();
        $this->invalidation_batch->put_invalidation_path( '/*' );
        return $this->invalidation_batch->get_invalidation_request_parameter( $distribution_id );
    }    
}