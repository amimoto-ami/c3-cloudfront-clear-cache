<?php
/**
 * Plugin Name: C3 Cloudfront Cache Controller
 * Version: 6.1.5
 * Plugin URI:https://github.com/amimoto-ami/c3-cloudfront-clear-cache
 * Description: Manage CloudFront Cache and provide some fixtures.
 * Author: hideokamoto
 * Author URI: https://wp-kyoto.net/
 * Requires PHP: 7.4
 * Text Domain: c3-cloudfront-clear-cache
 *
 * @package c3-cloudfront-clear-cache
 */

/**
 * Load the class loader
 */
require_once( __DIR__ . '/loader.php' );

use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\AWS;

/**
 * Load AWS SDK and classes
 */
function c3_init() {
	if ( ! class_exists( '\\Aws\\CloudFront\\CloudFrontClient' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	}
	new C3_CloudFront_Cache_Controller\Invalidation_Service();
	new C3_CloudFront_Cache_Controller\Cron_Service();
	new C3_CloudFront_Cache_Controller\Settings_Service();
	new C3_CloudFront_Cache_Controller\Views\Settings();
	new WP\Fixtures();
}
c3_init();

/**
 * For WP-CLI.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'c3', 'C3_CloudFront_Cache_Controller\\WP\\WP_CLI_Command' );
}


/**
 * Backward compatibility
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class CloudFront_Clear_Cache {
	/**
	 * Class instance
	 *
	 * @var CloudFront_Clear_Cache
	 */
	private static $instance;

	/**
	 * Create instance
	 *
	 * @return CloudFront_Clear_Cache
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c              = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	/**
	 * Run invalidation all
	 */
	public function c3_invalidation() {
		$service = new C3_CloudFront_Cache_Controller\Invalidation_Service();
		return $service->invalidate_all();
	}
}
