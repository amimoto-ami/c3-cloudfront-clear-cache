<?php
/**
 * Cron service
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron service
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Cron_Service {
	/**
	 * Hook
	 *
	 * @var WP\Hooks
	 */
	private $hook_service;

	/**
	 * WP Transient service
	 *
	 * @var WP\Transient_Service
	 */
	private $transient_service;

	/**
	 * Debug flag
	 *
	 * @var boolean
	 */
	private $debug;

	/**
	 * Inject a external services
	 *
	 * @param mixed ...$args Inject class.
	 */
	function __construct( ...$args ) {
		$this->hook_service      = new WP\Hooks();
		$this->transient_service = new WP\Transient_Service();
		$this->cf_service        = new AWS\CloudFront_Service();

		if ( $args && ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				if ( $value instanceof WP\Hooks ) {
					$this->hook_service = $value;
				} elseif ( $value instanceof WP\Transient_Service ) {
					$this->transient_service = $value;
				} elseif ( $value instanceof AWS\CloudFront_Service ) {
					$this->cf_service = $value;
				}
			}
		}
		$this->hook_service->add_action(
			'c3_cron_invalidation',
			array(
				$this,
				'run_schedule_invalidate',
			)
		);
		$this->debug = $this->hook_service->apply_filters( 'c3_log_cron_invalidation_task', false );
	}

	/**
	 * Run the schedule invalidation
	 *
	 * @return boolean
	 */
	public function run_schedule_invalidate() {
		if ( $this->debug ) {
			error_log( '===== C3 Invalidation cron is started ===' );
		}
		if ( $this->hook_service->apply_filters( 'c3_disabled_cron_retry', false ) ) {
			if ( $this->debug ) {
				error_log( '===== C3 Invalidation cron has been SKIPPED [Disabled] ===' );
			}
			return false;
		}
		$invalidation_batch = $this->transient_service->load_invalidation_query();
		if ( $this->debug ) {
			error_log( print_r( $invalidation_batch, true ) );
		}
		if ( ! $invalidation_batch || empty( $invalidation_batch ) ) {
			if ( $this->debug ) {
				error_log( '===== C3 Invalidation cron has been SKIPPED [No Target Item] ===' );
			}
			return false;
		}
		$distribution_id = $this->cf_service->get_distribution_id();
		$query           = array(
			'DistributionId'    => esc_attr( $distribution_id ),
			'InvalidationBatch' => $invalidation_batch,
		);
		if ( $this->debug ) {
			error_log( print_r( $query, true ) );
		}

		/**
		 * Execute the invalidation.
		 */
		$result = $this->cf_service->create_invalidation( $query );
		if ( $this->debug ) {
			error_log( print_r( $result, true ) );
		}
		$this->transient_service->delete_invalidation_query();
		if ( $this->debug ) {
			error_log( '===== C3 Invalidation cron has been COMPLETED ===' );
		}
		return true;
	}
}
