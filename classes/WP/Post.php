<?php
namespace C3_CloudFront_Cache_Controller\WP;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Post {
    private $post;

    public function set_post( $post ) {
        $this->post = $post;
    }

    public function get_permalink() {
        if ( ! $this->post ) {
            throw new \WP_Error( 'Post is required' );
        }
        return get_permalink( $this->post );
    }

    public function parse_url( string $url ) {
        $parsed_url = parse_url( $url );
        $url = $parsed_url['scheme'] . '://' . $parsed_url['host']. $parsed_url['path'];
        return $url;
    }
    
    public function get_the_post_term_links() {
        if ( ! $this->post ) {
            throw new \WP_Error( 'Post is required' );
        }
        $post = $this->post;
		$taxonomies = get_object_taxonomies( $post->post_type );
        $home_url = $this->parse_url( home_url() );

        $links = [];

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_post_terms( $post->ID, $taxonomy );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$url = $this->parse_url( get_term_link( $term, $taxonomy ) );

				if ( trailingslashit( $home_url ) === $url ) {
					continue;
				}
				$links[] = get_term_link( $term, $taxonomy ) . '*';
			}
		}
        return $links;

    }

}
