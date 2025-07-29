<?php
/**
 * WordPress Mock Helper for testing
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test\Helpers;

use C3_CloudFront_Cache_Controller\WP;

/**
 * WordPress Mock Helper class
 */
class WP_Mock_Helper {
	
	/**
	 * Create mock Environment service
	 */
	public static function create_mock_environment( $distribution_id = null, $access_key = null, $secret_key = null ) {
		$mock_env = \Mockery::mock( WP\Environment::class );
		
		$mock_env->shouldReceive( 'get_distribution_id' )
			->andReturn( $distribution_id );
		
		$mock_env->shouldReceive( 'get_aws_access_key' )
			->andReturn( $access_key );
		
		$mock_env->shouldReceive( 'get_aws_secret_key' )
			->andReturn( $secret_key );
		
		return $mock_env;
	}
	
	/**
	 * Create mock Options service
	 */
	public static function create_mock_options_service( $options = null ) {
		$mock_options = \Mockery::mock( WP\Options_Service::class );
		
		$mock_options->shouldReceive( 'get_options' )
			->andReturn( $options );
		
		$mock_options->shouldReceive( 'update_options' )
			->andReturn( true );
		
		$mock_options->shouldReceive( 'home_url' )
			->andReturn( 'https://example.com' );
		
		return $mock_options;
	}
	
	/**
	 * Create mock Hooks service
	 */
	public static function create_mock_hooks_service() {
		$mock_hooks = \Mockery::mock( WP\Hooks::class );
		
		$mock_hooks->shouldReceive( 'apply_filters' )
			->andReturnUsing( function( $filter, $value, ...$args ) {
				// デフォルトでは第二引数をそのまま返す
				return $value;
			} );
		
		$mock_hooks->shouldReceive( 'add_action' )
			->andReturn( true );
		
		$mock_hooks->shouldReceive( 'do_action' )
			->andReturn( true );
		
		return $mock_hooks;
	}
	
	/**
	 * Create mock Transient service
	 */
	public static function create_mock_transient_service() {
		$mock_transient = \Mockery::mock( WP\Transient_Service::class );
		
		$mock_transient->shouldReceive( 'save' )
			->andReturn( true );
		
		$mock_transient->shouldReceive( 'load' )
			->andReturn( false );
		
		$mock_transient->shouldReceive( 'delete' )
			->andReturn( true );
		
		return $mock_transient;
	}

	/**
	 * Create mock CloudFront service
	 */
	public static function create_mock_cloudfront_service() {
		$mock_cloudfront = \Mockery::mock( 'C3_CloudFront_Cache_Controller\AWS\CloudFront_Service' );
		
		$mock_cloudfront->shouldReceive( 'get_distribution_id' )
			->andReturn( 'test-distribution-id' );
		
		$mock_cloudfront->shouldReceive( 'create_invalidation' )
			->andReturn( true );
		
		return $mock_cloudfront;
	}

	/**
	 * Create mock Post service
	 */
	public static function create_mock_post_service() {
		$mock_post = \Mockery::mock( 'C3_CloudFront_Cache_Controller\WP\Post' );
		
		$mock_post->shouldReceive( 'get_post_urls' )
			->andReturn( array( '/', '/sample-post/' ) );
		
		return $mock_post;
	}
} 