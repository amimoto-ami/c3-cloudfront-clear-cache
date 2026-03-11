<?php
/**
 * Test ECS Metadata Service
 *
 * @package C3_CloudFront_Cache_Controller
 */

use C3_CloudFront_Cache_Controller\AWS\ECS_Metadata_Service;

class ECS_Metadata_Service_Test extends \WP_UnitTestCase {

	public function test_get_credentials_returns_null_when_required_fields_are_missing() {
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

		$service = new ECS_Metadata_Service();
		$result  = $service->get_credentials();

		remove_filter( 'pre_http_request', $callback, 10 );

		$this->assertNull( $result );
	}
}
