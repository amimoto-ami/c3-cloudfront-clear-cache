<?php
/**
 * Create a invalidation batch
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\AWS;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use C3_CloudFront_Cache_Controller\WP\Hooks;
use C3_CloudFront_Cache_Controller\WP\Post;

/**
 * Invalidation batch service
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Invalidation_Batch_Service {
	/**
	 * WP Hook service
	 *
	 * @var Hooks
	 */
	private $hook_service;

	/**
	 * Post object
	 *
	 * @var Post
	 */
	private $post;

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
				} elseif ( $value instanceof Post ) {
					$this->post = $value;
				}
			}
		}
		if ( ! $this->hook_service ) {
			$this->hook_service = new Hooks();
		}
		if ( ! $this->post ) {
			$this->post = new Post();
		}
	}
	/**
	 * Set Wp_Post data into Post instance
	 *
	 * @param \WP_Post $post WP post object.
	 */
	public function set_post( $post ) {
		/**
		 * To get the trashed post's permalink.
		 *
		 * @see https://github.com/amimoto-ami/c3-cloudfront-clear-cache/pull/54/files
		 */
		if ( 'trash' === $post->post_status ) {
			// For trashed post, get the permalink when it was published.
			$post->post_status = 'publish';
		}
		$this->post->set_post( $post );
	}

	/**
	 * Put invalidation path of the post
	 *
	 * @param Invalidation_Batch $invalidation_batch invalidation batch class.
	 * @param \WP_Post           $post WP post object.
	 */
	public function put_post_invalidation_batch( Invalidation_Batch $invalidation_batch, \WP_Post $post ) {
		if ( $post ) {
			$this->set_post( $post );
		}
		$invalidation_batch->put_invalidation_path( $this->post->get_permalink() . '*' );
		$term_links = $this->post->get_the_post_term_links();
		foreach ( $term_links as $key => $url ) {
			$invalidation_batch->put_invalidation_path( $url );
		}
		$archive_links = $this->post->get_the_post_type_archive_links();
		foreach ( $archive_links as $key => $url ) {
			$invalidation_batch->put_invalidation_path( $url );
		}
		$invalidation_batch->apply_invalidation_item_filter( $post );
		return $invalidation_batch;
	}

	/**
	 * Invalidate by post
	 *
	 * @param string   $home_url WP home url.
	 * @param string   $distribution_id CloudFront distribution id.
	 * @param \WP_Post $post WP post object.
	 */
	public function create_batch_by_post( string $home_url, string $distribution_id, \WP_Post $post = null ) {
		$invalidation_batch = new Invalidation_Batch();
		$invalidation_batch->put_invalidation_path( $home_url );
		$invalidation_batch = $this->put_post_invalidation_batch( $invalidation_batch, $post );
		return $invalidation_batch->get_invalidation_request_parameter( $distribution_id );
	}

	/**
	 * Invalidate by post
	 *
	 * @param string $home_url WP home url.
	 * @param string $distribution_id CloudFront distribution id.
	 * @param array  $posts The lists of WP post object.
	 */
	public function create_batch_by_posts( string $home_url, string $distribution_id, array $posts = array() ) {
		$invalidation_batch = new Invalidation_Batch();
		$invalidation_batch->put_invalidation_path( $home_url );
		foreach ( $posts as $post ) {
			$invalidation_batch = $this->put_post_invalidation_batch( $invalidation_batch, $post );
		}
		return $invalidation_batch->get_invalidation_request_parameter( $distribution_id );
	}


	/**
	 * Invalidate all cache
	 *
	 * @param string $distribution_id CloudFront distribution id.
	 */
	public function create_batch_for_all( string $distribution_id ) {
		$invalidation_batch = new Invalidation_Batch();
		$invalidation_batch->put_invalidation_path( '/*' );
		return $invalidation_batch->get_invalidation_request_parameter( $distribution_id );
	}
}
