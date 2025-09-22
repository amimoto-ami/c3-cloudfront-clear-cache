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
use C3_CloudFront_Cache_Controller\Constants;
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
	 * CloudFront service.
	 *
	 * @var \C3_CloudFront_Cache_Controller\AWS\CloudFront_Service
	 */
	private $cf_service;

	/**
	 * Admin notice service.
	 *
	 * @var WP\Admin_Notice
	 */
	private $notice;

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
	 * Log invalidation parameters flag
	 *
	 * @var boolean
	 */
	private $log_invalidation_params;

	/**
	 * Initialize the service, register WordPress hooks, and optionally inject dependencies.
	 *
	 * The constructor creates default implementations for hooks, options, invalidation batch,
	 * transients, CloudFront, and admin notices, then registers action handlers used by the
	 * invalidation workflow (post status transitions, attachment deletions, manual admin
	 * invalidation, and AJAX detail requests). Any provided variadic arguments are treated
	 * as dependency overrides and will replace the corresponding default instance when they
	 * are an instance of one of the known service types (WP\Hooks, WP\Transient_Service,
	 * WP\Options_Service, AWS\Invalidation_Batch_Service, AWS\CloudFront_Service,
	 * WP\Admin_Notice). Finally, the constructor reads the `c3_log_cron_register_task`
	 * filter to set the debug flag.
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
			'delete_attachment',
			array(
				$this,
				'invalidate_attachment_cache',
			),
			10,
			1
		);
		$this->hook_service->add_action(
			'admin_init',
			array(
				$this,
				'invalidate_manually',
			)
		);
		$this->hook_service->add_action(
			'wp_ajax_c3_get_invalidation_details',
			array(
				$this,
				'handle_invalidation_details_ajax',
			)
		);
		$this->log_invalidation_params = $this->hook_service->apply_filters( 'c3_log_invalidation_params', $this->get_debug_setting( Constants::DEBUG_LOG_INVALIDATION_PARAMS ) );
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
		if ( $this->log_invalidation_params ) {
			error_log( '===== C3 CRON Job registration [START] ===' );
		}
		if ( ! isset( $query['Paths'] ) || ! isset( $query['Paths']['Items'] ) || $query['Paths']['Items'][0] === '/*' ) {
			if ( $this->log_invalidation_params ) {
				error_log( '===== C3 CRON Job registration [SKIP | NO ITEM] ===' );
			}
			return false;
		}
		if ( $this->hook_service->apply_filters( 'c3_disabled_cron_retry', false ) ) {
			if ( $this->log_invalidation_params ) {
				error_log( '===== C3 CRON Job registration [SKIP | DISABLED] ===' );
			}
			return false;
		}
		$query = $this->transient_service->save_invalidation_query( $query );

		$interval_minutes = $this->hook_service->apply_filters( 'c3_invalidation_cron_interval', 1 );
		$time             = time() + MINUTE_IN_SECONDS * $interval_minutes;
		if ( $this->log_invalidation_params ) {
			error_log( print_r( $query, true ) );
		}

		$result = wp_schedule_single_event( $time, 'c3_cron_invalidation' );

		if ( $this->log_invalidation_params ) {
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

		if ( $this->hook_service->apply_filters( 'c3_log_invalidation_params', $this->get_debug_setting( Constants::DEBUG_LOG_INVALIDATION_PARAMS ) ) ) {
			error_log( 'C3 Invalidation Started - Query: ' . print_r( $query, true ) );
			error_log( 'C3 Invalidation Started - Force: ' . ( $force ? 'true' : 'false' ) );
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
			error_log( 'C3 Invalidation Failed: ' . $result->get_error_message() );
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
	 * Invalidate post cache
	 *
	 * @param \WP_Post $post Target post.
	 * @param boolean  $force Must invalidation.
	 */
	public function invalidate_post_cache( ?\WP_Post $post = null, $force = false ) {
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
	 *
	 * To enable debug logging for this method, add the following filter:
	 * add_filter( 'c3_log_invalidation_list', '__return_true' );
	 *
	 * This will log detailed information about the invalidation list process
	 * to the WordPress error log for troubleshooting purposes.
	 */
	public function list_recent_invalidation_logs() {
		$options = $this->get_plugin_option();
		if ( is_wp_error( $options ) ) {
			error_log( 'C3 List Invalidation Logs Error: ' . $options->get_error_message() );
			return $options;
		}

		$histories = $this->cf_service->list_invalidations();

		// デバッグログを追加
		if ( $this->hook_service->apply_filters( 'c3_log_invalidation_list', false ) ) {
			error_log( 'C3 Invalidation Logs Result: ' . print_r( $histories, true ) );
		}

		// エラーが発生した場合はエラーを返す
		if ( is_wp_error( $histories ) ) {
			error_log( 'C3 List Invalidation Logs Error: ' . $histories->get_error_message() );
			return $histories;
		}

		return $histories;
	}

	/**
	 * Get detailed invalidation information
	 *
	 * @param string $invalidation_id Invalidation ID.
	 * @return array|WP_Error Invalidation details or error.
	 */
	public function get_invalidation_details( $invalidation_id ) {
		$options = $this->get_plugin_option();
		if ( is_wp_error( $options ) ) {
			return $options;
		}

		return $this->cf_service->get_invalidation_details( $invalidation_id );
	}

	/**
	 * Handle AJAX requests for fetching CloudFront invalidation details.
	 *
	 * Verifies the AJAX nonce ('c3_invalidation_details_nonce' via POST key 'nonce')
	 * and the current user's 'cloudfront_clear_cache' capability. Reads and
	 * sanitizes the POST parameter 'invalidation_id', then returns the invalidation
	 * details as a JSON success response or a JSON error message on failure.
	 *
	 * Security and responses:
	 * - Fails immediately with wp_die on nonce check or capability failure.
	 * - If 'invalidation_id' is missing or empty, sends a JSON error.
	 * - If get_invalidation_details() returns a WP_Error, sends its message as a JSON error.
	 * - Otherwise sends the details with wp_send_json_success().
	 *
	 * Expected POST fields:
	 * - nonce: string (AJAX nonce to validate request)
	 * - invalidation_id: string (ID of the invalidation to fetch)
	 *
	 * @return void Sends a JSON response (and exits) or terminates via wp_die on security failures.
	 */
	public function handle_invalidation_details_ajax() {
		if ( ! check_ajax_referer( 'c3_invalidation_details_nonce', 'nonce', false ) ) {
			wp_die( 'Security check failed' );
		}

		if ( ! current_user_can( 'cloudfront_clear_cache' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$invalidation_id = sanitize_text_field( $_POST['invalidation_id'] ?? '' );
		if ( empty( $invalidation_id ) ) {
			wp_send_json_error( 'Invalid invalidation ID' );
		}

		$details = $this->get_invalidation_details( $invalidation_id );

		if ( is_wp_error( $details ) ) {
			wp_send_json_error( $details->get_error_message() );
		}

		wp_send_json_success( $details );
	}

	/**
	 * Trigger CloudFront invalidation for a deleted attachment.
	 *
	 * Builds a wildcard path from the deleted attachment's URL (dirname/filename*) and delegates
	 * the invalidation request to invalidate_by_query().
	 *
	 * @param int $attachment_id ID of the attachment being deleted.
	 * @return mixed|null WP_Error if plugin options are missing, the result returned by invalidate_by_query() on success, or null if the attachment URL/path cannot be determined.
	 */
	public function invalidate_attachment_cache( $attachment_id ) {
		$attachment_url = wp_get_attachment_url( $attachment_id );
		
		if ( ! $attachment_url ) {
			return;
		}
		
		$parsed_url = parse_url( $attachment_url );
		if ( ! isset( $parsed_url['path'] ) ) {
			return;
		}
		
		$path_info = pathinfo( $parsed_url['path'] );
		$wildcard_path = $path_info['dirname'] . '/' . $path_info['filename'] . '*';
		
		$options = $this->get_plugin_option();
		if ( is_wp_error( $options ) ) {
			return $options;
		}
		
		$invalidation_batch = new AWS\Invalidation_Batch();
		$invalidation_batch->put_invalidation_path( $wildcard_path );
		$query = $invalidation_batch->get_invalidation_request_parameter( $options['distribution_id'] );
		
		return $this->invalidate_by_query( $query );
	}

	/**
	 * Get debug setting value
	 *
	 * @param string $setting_key Debug setting key.
	 * @return boolean Debug setting value.
	 */
	private function get_debug_setting( $setting_key ) {
		$debug_options = get_option( Constants::DEBUG_OPTION_NAME, array() );
		$value = isset( $debug_options[ $setting_key ] ) ? $debug_options[ $setting_key ] : false;
		return $value;
	}
}
