<?php
/**
 * Control C3_CloudFront_Clear_Cache.
 * @author hideokamoto
 */
 require_once( dirname( __FILE__ ).'/c3-cloudfront-clear-cache.php' );

/**
 * WP-CLI Command to control C3 CloudFront Cache Controller Plugins
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
		if ( empty( $args ) ) {
			WP_CLI::error( 'Please input parameter:post_id(numeric) or all' );
			exit;
		}
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

	/**
	 * Update C3 CloudFront Cache Controller Settings
	 *
	 * ## OPTIONS
	 * distribution_id
	 *  Update Distribution ID
	 *
	 * access_key
	 *  Update Access Key
	 *
	 * secret_key
	 *  Update Secrete Key
	 *
	 * <Setting Param>
	 *  Update Setting value
	 *
	 * ## EXAMPLES
	 *
	 *     wp c3 update distribution_id <Setting Param>      :Default usage.
	 *     wp c3 update access_key <Setting Param>      :Default usage.
	 *     wp c3 update secret_key <Setting Param>      :Default usage.
	 *
	 * @param string $args: WP-CLI Command Name
	 * @param string $assoc_args: WP-CLI Command Option
	 * @since 2.4.0
	 */
	function update( $args, $assoc_args ) {
		if ( 1 > count( $args ) ) {
			WP_CLI::error( 'No type serected' );
		} elseif ( 2 > count( $args ) ) {
			WP_CLI::error( 'No value defined' );
		}
		list( $type, $value ) = $args;
		$name = 'c3_settings';
		$options = get_option( $name );
		switch ( $type ) {
			case 'distribution_id':
				$options['distribution_id'] = esc_attr( $value );
				break;

			case 'access_key':
				$options['access_key'] = esc_attr( $value );
				break;

			case 'secret_key':
				$options['secret_key'] = esc_attr( $value );
				break;

			default:
				WP_CLI::error( 'No Match Setting Type.' );
				break;
		}
		if ( ! isset( $options['distribution_id'] ) ) {
			$options['distribution_id'] = '';
		}
		if ( ! isset( $options['access_key'] ) ) {
			$options['access_key'] = '';
		}
		if ( ! isset( $options['secret_key'] ) ) {
			$options['secret_key'] = '';
		}

		update_option( 'c3_settings', $options );
		WP_CLI::success( "Update Option" );

	}
}

WP_CLI::add_command( 'c3', 'C3_CloudFront_Clear_Cache_Command' );
