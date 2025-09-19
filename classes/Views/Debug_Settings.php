<?php
/**
 * Debug settings for reading page
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
 * Debug settings class
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Debug_Settings {
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

		$this->hook_service->add_action( 'admin_init', array( $this, 'register_debug_settings' ) );
	}

	/**
	 * Register debug settings on reading settings page
	 */
	public function register_debug_settings() {
		register_setting(
			'reading',
			Constants::DEBUG_OPTION_NAME,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanitize_debug_settings' ),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'c3_debug_section',
			__( 'C3 CloudFront Debug Settings', Constants::text_domain() ),
			array( $this, 'debug_section_callback' ),
			'reading'
		);

		add_settings_field(
			'c3_debug_log_cron_register_task',
			__( 'C3 Debug - Log Cron Register Task', Constants::text_domain() ),
			array( $this, 'debug_log_cron_register_task_callback' ),
			'reading',
			'c3_debug_section'
		);

		add_settings_field(
			'c3_debug_log_invalidation_params',
			__( 'C3 Debug - Log Invalidation Parameters', Constants::text_domain() ),
			array( $this, 'debug_log_invalidation_params_callback' ),
			'reading',
			'c3_debug_section'
		);
	}

	/**
	 * Debug section description
	 */
	public function debug_section_callback() {
		echo '<p>' . __( 'Enable debug logging for C3 CloudFront Cache Controller operations.', Constants::text_domain() ) . '</p>';
	}

	/**
	 * Cron register task debug setting field
	 */
	public function debug_log_cron_register_task_callback() {
		$options = get_option( Constants::DEBUG_OPTION_NAME, array() );
		$value = isset( $options[ Constants::DEBUG_LOG_CRON_REGISTER_TASK ] ) ? $options[ Constants::DEBUG_LOG_CRON_REGISTER_TASK ] : false;
		echo '<input type="checkbox" id="c3_debug_log_cron_register_task" name="' . Constants::DEBUG_OPTION_NAME . '[' . Constants::DEBUG_LOG_CRON_REGISTER_TASK . ']" value="1" ' . checked( 1, $value, false ) . ' />';
		echo '<label for="c3_debug_log_cron_register_task">' . __( 'Enable logging for cron register task operations', Constants::text_domain() ) . '</label>';
	}

	/**
	 * Invalidation parameters debug setting field
	 */
	public function debug_log_invalidation_params_callback() {
		$options = get_option( Constants::DEBUG_OPTION_NAME, array() );
		$value = isset( $options[ Constants::DEBUG_LOG_INVALIDATION_PARAMS ] ) ? $options[ Constants::DEBUG_LOG_INVALIDATION_PARAMS ] : false;
		echo '<input type="checkbox" id="c3_debug_log_invalidation_params" name="' . Constants::DEBUG_OPTION_NAME . '[' . Constants::DEBUG_LOG_INVALIDATION_PARAMS . ']" value="1" ' . checked( 1, $value, false ) . ' />';
		echo '<label for="c3_debug_log_invalidation_params">' . __( 'Enable logging for invalidation parameters', Constants::text_domain() ) . '</label>';
	}

	/**
	 * Sanitize debug settings
	 *
	 * @param mixed $input Raw input data.
	 * @return array Sanitized settings.
	 */
	public function sanitize_debug_settings( $input ) {
		$sanitized = array();
		
		if ( isset( $input[ Constants::DEBUG_LOG_CRON_REGISTER_TASK ] ) ) {
			$sanitized[ Constants::DEBUG_LOG_CRON_REGISTER_TASK ] = true;
		} else {
			$sanitized[ Constants::DEBUG_LOG_CRON_REGISTER_TASK ] = false;
		}
		
		if ( isset( $input[ Constants::DEBUG_LOG_INVALIDATION_PARAMS ] ) ) {
			$sanitized[ Constants::DEBUG_LOG_INVALIDATION_PARAMS ] = true;
		} else {
			$sanitized[ Constants::DEBUG_LOG_INVALIDATION_PARAMS ] = false;
		}
		
		return $sanitized;
	}
}
