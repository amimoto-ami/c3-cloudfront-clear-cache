<?php
/**
 * Test ECS Metadata Service
 *
 * @package C3_CloudFront_Cache_Controller
 */

use C3_CloudFront_Cache_Controller\AWS\ECS_Metadata_Service;

class ECS_Metadata_Service_Test extends \WP_UnitTestCase {

	public function test_get_credentials_returns_null_without_relative_uri_and_skips_http_request() {
		$previous_relative_uri = getenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );
		$http_calls           = 0;
		putenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );

		$callback = function( $preempt ) use ( &$http_calls ) {
			++$http_calls;
			return $preempt;
		};

		add_filter( 'pre_http_request', $callback, 10, 3 );

		try {
			$service = new ECS_Metadata_Service();
			$result  = $service->get_credentials();
		} finally {
			remove_filter( 'pre_http_request', $callback, 10 );
			if ( false === $previous_relative_uri ) {
				putenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );
			} else {
				putenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI=' . $previous_relative_uri );
			}
		}

		$this->assertNull( $result );
		$this->assertSame( 0, $http_calls );
	}

	public function test_is_ecs_task_returns_false_without_relative_uri_and_skips_http_request() {
		$previous_relative_uri = getenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );
		$http_calls           = 0;
		putenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );

		$callback = function( $preempt ) use ( &$http_calls ) {
			++$http_calls;
			return $preempt;
		};

		add_filter( 'pre_http_request', $callback, 10, 3 );

		try {
			$service = new ECS_Metadata_Service();
			$result  = $service->is_ecs_task();
		} finally {
			remove_filter( 'pre_http_request', $callback, 10 );
			if ( false === $previous_relative_uri ) {
				putenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );
			} else {
				putenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI=' . $previous_relative_uri );
			}
		}

		$this->assertFalse( $result );
		$this->assertSame( 0, $http_calls );
	}

	public function test_get_credentials_returns_null_when_required_fields_are_missing() {
		$previous_relative_uri = getenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );
		putenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI=/v2/credentials/test' );

		$callback = function( $preempt, $parsed_args, $url ) {
			if ( 'http://169.254.170.2/v2/credentials/test' !== $url ) {
				return $preempt;
			}

			return array(
				'headers'  => array(),
				'body'     => wp_json_encode(
					array(
						'AccessKeyId'     => 'test-key',
						'SecretAccessKey' => 'test-secret',
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};

		add_filter( 'pre_http_request', $callback, 10, 3 );

		try {
			$service = new ECS_Metadata_Service();
			$result  = $service->get_credentials();
		} finally {
			remove_filter( 'pre_http_request', $callback, 10 );
			if ( false === $previous_relative_uri ) {
				putenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );
			} else {
				putenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI=' . $previous_relative_uri );
			}
		}

		$this->assertNull( $result );
	}
}
