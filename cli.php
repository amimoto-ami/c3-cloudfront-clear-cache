<?php
/**
 * Control C3_CloudFront_Clear_Cache.
 * @author hideokamoto
 */
 require_once( dirname( __FILE__ ).'/c3-cloudfront-clear-cache.php' );

/**
 * WP-CLI Command for control C3 CloudFront Clear Cache Plugins
 *
 * @class C3_CloudFront_Clear_Cache_Command
 * @since 2.3.0
 */
class C3_CloudFront_Clear_Cache_Command extends WP_CLI_Command {
    /**
     * Flush All CloudFront Cache
	 *
	 * ## OPTIONS
	 * <post_id>
	 * post_id
	 *
	 * [--force]
	 * Activate Force Clear Mode
     *
     * ## EXAMPLES
     *
     *     wp c3 flush <post_id>       : Flush <post_id>'s CloudFront Cache.
     *     wp c3 flush all             : Flush All CloudFront Cache.
	 *     wp c3 flush all --force     : Flush All CloudFront Cache.( Force )
     *
	 * @param string $args: WP-CLI Command Name
	 * @param string $assoc_args: WP-CLI Command Option
	 * @since 2.3.0
     */
    function flush( $args, $assoc_args ) {
		WP_CLI::line( 'Start to Clear CloudFront Cache...' );
		list( $type ) = $args;
		$c3 = CloudFront_Clear_Cache::get_instance();
		if ( array_search( 'force', $assoc_args ) ) {
			WP_CLI::line( 'Force Clear Mode');
			add_filter( 'c3_invalidation_flag', '__return_false' );
		}
		if ( 'all' == $type ) {
			WP_CLI::line( 'Clear Item = All');
			$result = $c3->c3_invalidation();
		} elseif ( is_numeric( $type ) ) {
			WP_CLI::line( "Clear Item = (post_id={$type})" );
			$result = $c3->c3_invalidation( $type );
		} else {
			WP_CLI::error( 'Please input parameter:post_id(numeric) or all' );
			exit;
		}
		if ( ! is_wp_error( $result ) ) {
			WP_CLI::success( "Create Invalidation Request. Please wait few minutes to finished clear CloudFront Cache." );
		}
	}
}

WP_CLI::add_command( 'c3', 'C3_CloudFront_Clear_Cache_Command' );
