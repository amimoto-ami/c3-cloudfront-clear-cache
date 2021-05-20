<?php

use C3_CloudFront_Cache_Controller\WP;
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Remove avoid cache cookie
 */
$fixtures = new WP\Fixtures();
$fixtures->unset_avoid_cache_cookie();

/**
 * Remove plugin options
 */
$options = new WP\Options();
$options->delete_options();
