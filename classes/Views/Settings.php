<?php
/**
 * Setting page view service
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\Views;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Constants;

/**
 * Setting page class
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Settings {
	/**
	 * Hook
	 *
	 * @var WP\Hooks
	 */
	private $hook_service;

	/**
	 * Inject a external services
	 *
	 * @param mixed ...$args Inject class.
	 */
	function __construct( ...$args ) {
		$this->hook_service = new WP\Hooks();

		if ( $args && ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				if ( $value instanceof WP\Hooks ) {
					$this->hook_service = $value;
				}
			}
		}

		$this->hook_service->add_action( 'admin_menu', array( $this, 'create_options_page' ) );
		$this->hook_service->add_filter(
			'amimoto_c3_add_settings',
			function () {
				require_once( C3_PLUGIN_PATH . '/templates/Plugin_Options.php' );
				require_once( C3_PLUGIN_PATH . '/templates/Manually_Invalidation.php' );
				require_once( C3_PLUGIN_PATH . '/templates/Invalidation_Logs.php' );
			}
		);
		/**
		 * Backward compatibility for AMIMOTO Dashboard plugin.
		 */
		$this->hook_service->add_filter( 'amimoto_show_c3_setting_form', '__return_false' );
		$this->hook_service->add_filter( 'amimoto_show_invalidation_form', '__return_false' );

		$this->hook_service->add_action( 'admin_init', array( $this, 'define_caps' ) );
	}

	/**
	 * Create the plugin option page
	 */
	public function create_options_page() {
		add_options_page(
			__( 'CloudFront Settings', Constants::text_domain() ),
			__( 'CloudFront Settings', Constants::text_domain() ),
			'cloudfront_clear_cache',
			Constants::MENU_ID,
			function () {
				require_once( C3_PLUGIN_PATH . '/templates/Settings.php' );
			}
		);

		register_setting(
			Constants::MENU_ID,
			Constants::OPTION_NAME,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'filter_and_escape' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Define C3 plugin capabilities
	 *
	 * @access public
	 * @since 4.0.0
	 */
	public function define_caps() {
		$role = get_role( 'administrator' );
		$role->add_cap( 'cloudfront_clear_cache' );
	}
	/**
	 * Filter the saving option's request
	 *
	 * @param mixed $args form parameter.
	 */
	function filter_and_escape( $args ) {
		$allow_keys = array(
			Constants::DISTRIBUTION_ID,
			Constants::ACCESS_KEY,
			Constants::SECRET_KEY,
		);
		$items      = array();
		foreach ( $allow_keys as $key ) {
			if ( ! array_key_exists( $key, $args ) ) {
				continue;
			}
			$items[ $key ] = esc_attr( $args[ $key ] );
		}
		return $items;
	}
}
