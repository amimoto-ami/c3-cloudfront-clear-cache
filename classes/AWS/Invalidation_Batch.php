<?php

namespace C3_CloudFront_Cache_Controller\AWS;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Invalidation_Batch {
    private $items = [];

    public function put_invalidation_path( string $path ) {
        $result = $this->make_invalidate_path( $path );
        
        if ( ! $result || ! isset( $result ) ) {
            return;
        }

        $this->items[] = $result;
        $this->items = array_unique( $this->items );
    }

    public function get_invalidation_path_items() {
        if ( 1 > count( $this->items ) || 10 < count( $this->items ) ) {
            return [ '/*' ];
        }
        return $this->items; 
    }

	/**
	 * Create Invalidation path from url
	 *
	 * @return (string) $url
	 * @param string
	 * @since 4.0.0
	 * @access public
	 */
	public function make_invalidate_path( $url ) {
		$parse_url = parse_url( $url );
		return isset( $parse_url['path'] )
			? $parse_url['path']
			: preg_replace( array( '#^https?://[^/]*#', '#\?.*$#' ), '', $url );
	}

    public function get_invalidation_batch() {
        return array(
            'CallerReference' => uniqid(),
            'Paths' => array(
                'Items' => $this->items,
                'Quantity' => count( $this->items ),
            ),
        );
    }

    public function get_invalidation_request_parameter( string $distribution_id ) {
		return array(
			'DistributionId' => esc_attr( $distribution_id ),
			'InvalidationBatch' => $this->get_invalidation_batch()
		);
    }

}