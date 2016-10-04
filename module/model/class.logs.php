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
		return $lists;
	}
}
