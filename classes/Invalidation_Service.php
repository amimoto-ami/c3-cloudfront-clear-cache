<?php
namespace C3_CloudFront_Cache_Controller;
use C3_CloudFront_Cache_Controller\WP\Post_Service;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Invalidation_Service {
	private $hook_service;
	private $option_service;
	private $transient_service;
	private $invalidation_batch;
	private $debug;

	function __construct( ...$args ) {
		$this->hook_service       = new WP\Hooks();
		$this->option_service     = new WP\Options_Service();
		$this->invalidation_batch = new AWS\Invalidation_Batch_Service();
		$this->transient_service  = new WP\Transient_Service();
		$this->cf_service         = new AWS\CloudFront_Service();
		$this->notice             = new WP\Admin_Notice();

		if ( $args && ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				if ( $value instanceof WP\Hooks ) {
					$this->hook_service = $value;
				} elseif ( $value instanceof WP\Transient_Service ) {
					$this->transient_service = $value;
				} elseif ( $value instanceof WP\Options_Service ) {
					$this->option_service = $value;
				} elseif ( $value instanceof AWS\Invalidation_Batch_Service ) {
					$this->invalidation_batch = $value;
				} elseif ( $value instanceof AWS\CloudFront_Service ) {
					$this->cf_service = $value;
				} elseif ( $value instanceof WP\Admin_Notice ) {
					$this->notice = $value;
				}
			}
		}
		$this->hook_service->add_action(
			'transition_post_status',
			array(
				$this,
				'invalidate_by_changing_post_status',
			),
			10,
			3
		);
		$this->hook_service->add_action(
			'admin_init',
			array(
				$this,
				'invalidate_manually',
			)
		);
		$this->debug = $this->hook_service->apply_filters( 'c3_log_cron_register_task', false );
	}

	/**
	 * Invalidate all cache manually
	 */
	public function invalidate_manually() {
		if ( empty( $_POST ) ) {
			return;
		}
		$result = null;
		$key    = Constants::C3_INVALIDATION;
		if ( ! isset( $_POST[ $key ] ) || ! $_POST[ $key ] ) {
			return;
		}

		if ( ! check_admin_referer( $key, $key ) ) {
			return;
		}

		$invalidation_target = $_POST['invalidation_target'];

		try {
			if ( ! isset( $invalidation_target ) ) {
				throw new \Error( 'invalidation_target is required' );
			}
			if ( 'all' === $invalidation_target ) {
				$result = $this->invalidate_all();
			} else {
				$post_service = new Post_Service();
				$posts = $post_service->list_posts_by_ids( explode( ',', $invalidation_target ) );
				$this->invalidate_posts_cache( $posts, true );
			}
		} catch ( \Exception $e ) {
			$result = new \WP_Error( 'C3 Invalidation Error', $e->getMessage() );
		}

		if ( ! isset( $result ) ) {
			return;
		}
		if ( is_wp_error( $result ) ) {
			$this->notice->show_admin_error( $result );
		} else {
			$this->notice->show_admin_success( $result['message'], $result['type'] );
		}
	}

	/**
	 * Register cron event to send the overflowed invalidation paths
	 *
	 * @return boolean - If true, cron has been scheduled
	 */
	public function register_cron_event( $query ) {
		if ( $this->debug ) {
			error_log( '===== C3 CRON Job registration [START] ===' );
		}
		if ( ! isset( $query['Paths'] ) || ! isset( $query['Paths']['Items'] ) || $query['Paths']['Items'][0] === '/*' ) {
			if ( $this->debug ) {
				error_log( '===== C3 CRON Job registration [SKIP | NO ITEM] ===' );
			}
			return false;
		}
		if ( $this->hook_service->apply_filters( 'c3_disabled_cron_retry', false ) ) {
			if ( $this->debug ) {
				error_log( '===== C3 CRON Job registration [SKIP | DISABLED] ===' );
			}
			return false;
		}
		$query = $this->transient_service->save_invalidation_query( $query );

		$interval_minutes = $this->hook_service->apply_filters( 'c3_invalidation_cron_interval', 1 );
		$time             = time() + MINUTE_IN_SECONDS * $interval_minutes;
		if ( $this->debug ) {
			error_log( print_r( $query, true ) );
		}

		$result = wp_schedule_single_event( $time, 'c3_cron_invalidation' );

		if ( $this->debug ) {
			error_log( '===== C3 CRON Job registration [COMPLETE] ===' );
		}
		return $result;
	}

	/**
	 * Check the post status to run invalidation or not.
	 */
	public function should_invalidate( $new_status, $old_status ) {
		if ( 'publish' === $new_status ) {
			// if publish or update posts.
			$result = true;
		} elseif ( 'publish' === $old_status && $new_status !== $old_status ) {
			// if un-published post.
			$result = true;
		} else {
			$result = false;
		}
		$result = $this->hook_service->apply_filters( 'c3_is_invalidation', $result );
		return $result;
	}

	/**
	 * Execute invalidation process when post status has been changed.
	 */
	public function invalidate_by_changing_post_status( $new_status, $old_status, $post ) {
		if ( ! $this->should_invalidate( $new_status, $old_status ) ) {
			return;
		}
		$this->invalidate_post_cache( $post );
	}

	public function invalidate_by_query( $query, $force = false ) {
		if ( $this->transient_service->should_regist_cron_job() && false === $force ) {
			/**
			 * Just regist a cron job.
			 */
			$this->register_cron_event( $query['InvalidationBatch'] );
			return array(
				'type'    => 'Success',
				'message' => 'Invalidation has been succeeded, please wait a 5 ~ 10 minutes to remove the cache.',
			);
		}
		/**
		 * Execute invalidation request
		 */
		$this->transient_service->set_invalidation_time();
		$result = $this->cf_service->create_invalidation( $query );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return array(
			'type'    => 'Success',
			'message' => 'Invalidation has been succeeded, please wait a few minutes to remove the cache.',
		);
	}

	/**
	 * Invalidate the post's caches
	 */
	public function invalidate_post_cache( \WP_Post $post, $force = false ) {
		$home_url = $this->option_service->home_url( '/' );
		$options  = $this->option_service->get_options();
		$query    = $this->invalidation_batch->create_batch_by_post( $home_url, $options['distribution_id'], $post );
		return $this->invalidate_by_query( $query, $force );
	}

	/**
	 * Invalidate the post's caches
	 */
	public function invalidate_posts_cache( array $posts = array(), $force = false ) {
		$home_url = $this->option_service->home_url( '/' );
		$options  = $this->option_service->get_options();
		$query    = $this->invalidation_batch->create_batch_by_posts( $home_url, $options['distribution_id'], $posts );
		return $this->invalidate_by_query( $query, $force );
	}

	/**
	 * Invalidate all cache
	 */
	public function invalidate_all() {
		$options = $this->option_service->get_options();
		$query   = $this->invalidation_batch->create_batch_for_all( $options['distribution_id'] );
		return $this->invalidate_by_query( $query, true );
	}

	public function list_recent_invalidation_logs() {
		$histories = $this->cf_service->list_invalidations();
		return $histories;
	}
}
