<?php
/**
 * Invalidation bach entity
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\AWS;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Invalidation batch class
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Invalidation_Batch {

	/**
	 * Invalidation path items
	 *
	 * @var array
	 */
	private $items = array();

	/**
	 * Put a invalidation path
	 *
	 * @param string $path The invalidation path.
	 */
	public function put_invalidation_path( string $path ) {
		$result = $this->make_invalidate_path( $path );

		if ( ! $result || ! isset( $result ) ) {
			return;
		}

		$this->items[] = $result;
		$this->items   = array_unique( $this->items );
	}

	/**
	 * Apply WordPress filter hook.
	 * We can overwrite the invalidation item by manually
	 *
	 * @param \WP_Post $post WordPress Post object.
	 */
	public function apply_invalidation_item_filter( \WP_Post $post = null ) {
		$this->items = apply_filters( 'c3_invalidation_items', $this->items, $post );
	}

	/**
	 * Get the invalidation target items.
	 * If over the defined limit, should return '/*' to remove all cache.
	 */
	public function get_invalidation_path_items() {
		if ( 1 > count( $this->items ) || 10 < count( $this->items ) ) {
			return array( '/*' );
		}
		return $this->items;
	}

	/**
	 * Create Invalidation path from url
	 *
	 * @param string $url The invalidation target url.
	 * @since 4.0.0
	 * @access public
	 */
	public function make_invalidate_path( $url ) {
		$parse_url = parse_url( $url );
		return isset( $parse_url['path'] )
			? $parse_url['path']
			: preg_replace( array( '#^https?://[^/]*#', '#\?.*$#' ), '', $url );
	}

	/**
	 * Get the invalidation batch
	 */
	public function get_invalidation_batch() {
		return array(
			'CallerReference' => uniqid(),
			'Paths'           => array(
				'Items'    => $this->items,
				'Quantity' => count( $this->items ),
			),
		);
	}

	/**
	 * Get the invalidation request parameter
	 *
	 * @param string $distribution_id CloudFront distribution id.
	 */
	public function get_invalidation_request_parameter( string $distribution_id ) {
		return array(
			'DistributionId'    => esc_attr( $distribution_id ),
			'InvalidationBatch' => $this->get_invalidation_batch(),
		);
	}

}
