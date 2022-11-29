<?php
/**
 * Invalidation execution service
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller;
use C3_CloudFront_Cache_Controller\WP\Post_Service;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Invalidation service
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Invalidation_Service {
	/**
	 * Hook service
	 *
	 * @var WP\Hooks
	 */
	private $hook_service;

	/**
	 * WP Options serivce
	 *
	 * @var WP\Options_Service
	 */
	private $option_service;

	/**
	 * Transient service.
	 *
	 * @var WP\Transient_Service
	 */
	private $transient_service;

	/**
	 * Invalidation batch service.
	 *
	 * @var AWS\Invalidation_Batch_Service
	 */
	private $invalidation_batch;

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
	 *
	 * @throws \Error Failed to invalidate.
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
				$posts        = $post_service->list_posts_by_ids( explode( ',', $invalidation_target ) );
				$result       = $this->invalidate_posts_cache( $posts, true );
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
	 * @param mixed $query Invalidation query.
	 * @return boolean If true, cron has been scheduled.
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
	 *
	 * @param string $new_status The post's new status.
	 * @param string $old_status The post's old status.
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
	 *
	 * @param string   $new_status The post's new status.
	 * @param string   $old_status The post's old status.
	 * @param \WP_Post $post The target post object.
	 */
	public function invalidate_by_changing_post_status( $new_status, $old_status, $post ) {
		if ( ! $this->should_invalidate( $new_status, $old_status ) ) {
			return;
		}
		$this->invalidate_post_cache( $post );
	}

	/**
	 * Execute invalidation by query
	 *
	 * @param mixed   $query Invalidation query.
	 * @param boolean $force Must run the invalidation.
	 */
	public function invalidate_by_query( $query, $force = false ) {
		if ( is_wp_error( $query ) ) {
			return $query;
		}

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
	 * The distribution_id must be provided to send the invalidation request.
	 * So if the option is empty, we must return WP_Error and terminate the process.
	 */
	public function get_plugin_option() {
		$options = $this->option_service->get_options();
		if ( ! isset( $options['distribution_id'] ) || empty( $options['distribution_id'] ) ) {
			return new \WP_Error( 'C3 Invalidation Error', 'distribution_id is required. Please update setting or define a C3_DISTRIBUTION_ID on wp-config.php' );
		}
		return $options;
	}

	/**
	 * Create invalidation batch query for post
	 *
	 * @param array   $posts The lists of WP_Posts.
	 * @param boolean $force Must run the invalidation.
	 */
	public function create_post_invalidation_batch( array $posts = array(), $force = false ) {
		$home_url = $this->option_service->home_url( '/' );
		$options  = $this->get_plugin_option();
		if ( is_wp_error( $options ) ) {
			return $options;
		}
		$query = $this->invalidation_batch->create_batch_by_posts( $home_url, $options['distribution_id'], $posts );
		return $query;
	}

	/**
	 * Invalidate the post's caches
	 *
	 * @param \WP_Post $post WP_Posts.
	 * @param boolean  $force Must run the invalidation.
	 */
	public function invalidate_post_cache( \WP_Post $post = null, $force = false ) {
		if ( ! isset( $post ) ) {
			return new \WP_Error( 'C3 Invalidation Error', 'No such post' );
		}
		$query = $this->create_post_invalidation_batch( array( $post ), $force );
		return $this->invalidate_by_query( $query, $force );
	}

	/**
	 * Invalidate the post's caches
	 *
	 * @param array   $posts The lists of WP_Posts.
	 * @param boolean $force Must run the invalidation.
	 */
	public function invalidate_posts_cache( array $posts = array(), $force = false ) {
		$query = $this->create_post_invalidation_batch( $posts, $force );
		return $this->invalidate_by_query( $query, $force );
	}

	/**
	 * Invalidate all cache
	 */
	public function invalidate_all() {
		$options = $this->get_plugin_option();
		if ( is_wp_error( $options ) ) {
			return $options;
		}
		$query = $this->invalidation_batch->create_batch_for_all( $options['distribution_id'] );
		return $this->invalidate_by_query( $query, true );
	}

	/**
	 * List the invalidation logs
	 */
	public function list_recent_invalidation_logs() {
		$histories = $this->cf_service->list_invalidations();
		return $histories;
	}
}
