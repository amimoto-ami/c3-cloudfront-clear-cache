<?php
/**
 * C3_Logs
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-plugin-dashboard
 * @since 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * manage Logs
 *
 * @class C3_Logs
 * @since 4.1.0
 */
class C3_Logs extends C3_Base {
	/**
	 * List Invalidations
	 *
	 * @access public
	 * @return array
	 * @since 4.1.0
	 **/
	public function list_invalidations() {
		$lists = array();
		$options = $this->get_c3_options();
		if ( ! isset( $options['distribution_id'] ) || ! $options['distribution_id'] ) {
			return $lists;
		}

		//if ( c3_is_later_than_php_55() ) {
		//	$sdk = C3_Client_V3::get_instance();
		//} else {
			$sdk = C3_Client_V2::get_instance();
		//}
		$cf_client = $sdk->create_cloudfront_client( $options );
		if ( is_wp_error( $cf_client ) ) {
			error_log( print_r( $cf_client, true ) );
			return $cf_client;
		}
		$lists = $cf_client->listInvalidations( array(
			'DistributionId' =>  $options['distribution_id'],
			'MaxItems' => apply_filters( 'c3_max_invalidation_logs', 25 ),
		) );

		$lists = $this->_parse_invalidations( $lists->toArray() );

		return $lists;
	}

	/**
	 * Parse Invalidation lists
	 *
	 * @access private
	 * @since 4.1.0
	 * @return string
	 * @param array $list_invaldiations
	 **/
	private function _parse_invalidations( $list_invaldiations ) {
		if ( $list_invaldiations['Quantity'] < 0 ) {
			return false;
		}
		return $list_invaldiations['Items'];
	}
}
