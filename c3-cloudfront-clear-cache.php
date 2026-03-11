<?php
/**
 * Plugin Name: C3 Cloudfront Cache Controller
 * Version: 7.3.1
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
require_once __DIR__ . '/loader.php';

use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\AWS;

/**
 * Load classes and initialize services
 */
function c3_init() {
	$debug_logger = new C3_CloudFront_Cache_Controller\WP\Debug_Logger();
	new C3_CloudFront_Cache_Controller\Invalidation_Service( $debug_logger );
	new C3_CloudFront_Cache_Controller\Cron_Service( $debug_logger );
	new C3_CloudFront_Cache_Controller\Settings_Service();
	new C3_CloudFront_Cache_Controller\Views\Settings();
	new C3_CloudFront_Cache_Controller\Views\Debug_Settings();
	new WP\Fixtures();

	// Add filters for instance role detection, allowing overrides via constants for testing or specific environments.
	add_filter( 'c3_has_ec2_instance_role', function( $has_ec2_instance_role = false ) {
		return defined( 'C3_USE_EC2_INSTANCE_ROLE' ) ? C3_USE_EC2_INSTANCE_ROLE : $has_ec2_instance_role;
	});
	add_filter( 'c3_has_ecs_task_role', function( $has_ecs_task_role = false ) {
		return defined( 'C3_USE_ECS_TASK_ROLE' ) ? C3_USE_ECS_TASK_ROLE : $has_ecs_task_role;
	});
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
