<?php
/*
 * Plugin Name: C3 Cloudfront Cache Controller
 * Version: 6.1.0
 * Plugin URI:https://github.com/amimoto-ami/c3-cloudfront-clear-cache
 * Description: Manage CloudFront Cache and provide some fixtures.
 * Author: hideokamoto
 * Author URI: https://wp-kyoto.net/
 * Requires PHP: 7.0
 * Text Domain: c3-cloudfront-clear-cache
 * @package c3-cloudfront-clear-cache
 */

define( 'C3_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'C3_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'C3_PLUGIN_ROOT', __FILE__ );
require_once( __DIR__ . '/loader.php' );

use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\AWS;

// fixtures
function c3_init() {
	if ( ! class_exists( '\\Aws\\CloudFront\\CloudFrontClient' ) ) {
		$aws_sdk_path = apply_filters( 'c3_aws_sdk_path', dirname( __FILE__ ) . '/libs/aws.phar' );
		if ( isset( $aws_sdk_path ) ) {
			require_once( $aws_sdk_path );
		}
	}
	new C3_CloudFront_Cache_Controller\Invalidation_Service();
	new C3_CloudFront_Cache_Controller\Cron_Service();
	new C3_CloudFront_Cache_Controller\Settings_Service();
	new C3_CloudFront_Cache_Controller\Views\Settings();
	new WP\Fixtures();
}

add_action( 'plugins_loaded', 'c3_init' );

// WP-CLI
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'c3', 'C3_CloudFront_Cache_Controller\\WP\\WP_CLI_Command' );
}


/**
 * Backward compatibility
 */
class CloudFront_Clear_Cache {
	private static $instance;
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c              = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public function c3_invalidation() {
		$service = new C3_CloudFront_Cache_Controller\Invalidation_Service();
		return $service->invalidate_all();
	}
}
