<?php
/**
 * C3_Log_Utils
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package c3-cloudfront-clear-cache
 * @since 5.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class C3_Log_Utils {
	/**
	 * Parse Invalidation lists
	 *
	 * @access public
	 * @since 5.3.0
	 * @return string
	 * @param array $list_invaldiations
	 **/
	public function parse_invalidation_lists( $list_invaldiations ) {
		if ( isset($list_invaldiations["InvalidationList"]) && $list_invaldiations["InvalidationList"] ) {
			$v3_list = $list_invaldiations["InvalidationList"];
			if ( $v3_list['Quantity'] < 0 )
				return array();
			return $v3_list['Items'];
		}
		if ( $list_invaldiations['Quantity'] <= 0 ) {
			return array();
		}
		return $list_invaldiations['Items'];
	}
}