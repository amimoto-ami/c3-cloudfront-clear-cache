<?php
require_once( 'module/model/class.woocommerce.php' );
class C3_Woo_Test extends WP_UnitTestCase
{
	protected $C3;
	function __construct() {
		$this->C3 = new C3_Woo();
	}

	function test_default_new_config() {

		$reflection = new \ReflectionClass( $this->C3 );
		$method = $reflection->getMethod( '_create_cloudfront_config' );
		$method->setAccessible( true );
		$result = $method->invoke( $this->C3 );
		$default_links = [
			'product-category',
			'product-tag',
			'product',
			'shop',
			'cart',
			'checkout',
			'my-account',
		];
		$desired_result = $this->_create_distribution_config( $default_links );
		$this->assertEquals( $result, $desired_result );
	}

	private function _create_cache_item( $path, $origin_id ) {
		$item = array(
			'AllowedMethods' => [
				'CachedMethods' => [
					'Items' => [
						"HEAD",
						"GET"
					],
					'Quantity' => 2,
				],
				'Items' => [
					"HEAD",
					"DELETE",
					"POST",
					"GET",
					"OPTIONS",
					"PUT",
					"PATCH"
				],
				'Quantity' => 7,
			],
			'Compress' => true,
			'DefaultTTL' => 0,
			'ForwardedValues' => [
				'Cookies' => [
					'Forward' => 'all',
				],
				'Headers' => [
					'Items' => [
						'*',
					],
					'Quantity' => 1,
				],
				'QueryString' => true,
				'QueryStringCacheKeys' => [
					'Quantity' => 0,
				],
			],
			'MaxTTL' => 0,
			'MinTTL' => 0,
			'PathPattern' => "/{$path}/*",
			'SmoothStreaming' => false,
			'TargetOriginId' => $origin_id,
			'TrustedSigners' => [
				'Enabled' => false,
				'Quantity' => 0,
			],
			'ViewerProtocolPolicy' => 'allow-all',
		);
		return $item;
	}

	private function _create_distribution_config( $links ) {
		$origin_id = '%ORIGIN_ID%';
		foreach ( $links as $link ) {
			if ( ! $link ) {
				continue;
			}
			$items[] = $this->_create_cache_item( $link, $origin_id );
		}
		$config = array(
			'CacheBehaviors' => [
				'Items' => $items,
				'Quantity' => count( $items ),
			],
		);
		return $config;
	}

}
