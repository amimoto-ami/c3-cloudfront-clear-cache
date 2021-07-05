<?php
/**
 * Run script to remove the plugin options
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

/**
 * Load classes
 */
require_once( __DIR__ . '/loader.php' );
use C3_CloudFront_Cache_Controller\WP;
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Remove avoid cache cookie
 */
$C3_CloudFront_Cache_Controller_fixtures = new WP\Fixtures();
$C3_CloudFront_Cache_Controller_fixtures->unset_avoid_cache_cookie();

/**
 * Remove plugin options
 */
$C3_CloudFront_Cache_Controller_options = new WP\Options();
$C3_CloudFront_Cache_Controller_options->delete_options();
