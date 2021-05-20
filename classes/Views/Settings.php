<?php
namespace C3_CloudFront_Cache_Controller\Views;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Constants;

class Settings {

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
				require_once( C3_PLUGIN_PATH . '/templates/Settings.php' );
			}
		);
	}

	public function create_options_page() {
		add_options_page(
			__( 'CloudFront Settings', Constants::text_domain() ),
			__( 'CloudFront Settings', Constants::text_domain() ),
			'administrator',
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
