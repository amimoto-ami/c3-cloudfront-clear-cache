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
		$options = self::get_c3_options();
		if ( ! isset( $options['distribution_id'] ) || ! $options['distribution_id'] ) {
			return $lists;
		}

		if ( 'v2' !== c3_get_aws_sdk_version() ) {
			$sdk = C3_Client_V3::get_instance();
		} else {
			$sdk = C3_Client_V2::get_instance();
		}
		$cf_client = $sdk->create_cloudfront_client( $options );
		if ( is_wp_error( $cf_client ) ) {
			error_log( print_r( $cf_client, true ) );
			return $cf_client;
		}
		try {
			$lists = $cf_client->listInvalidations( array(
				'DistributionId' => $options['distribution_id'],
				'MaxItems'       => apply_filters( 'c3_max_invalidation_logs', 25 ),
			) );
			$logs_utils = new C3_Log_Utils();
			$lists = $logs_utils->parse_invalidation_lists( $lists->toArray() );
		} catch ( Aws\CloudFront\Exception\NoSuchDistributionException $e ) {
			error_log( $options['distribution_id'] . 'not found');
			error_log( $e->__toString(), 0);
		} catch ( Exception $e ) {
			error_log( $e->__toString(), 0);
		}
		return $lists;
	}
}
