<?php
/**
 * C3_woo
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package c3-cloudfront-clear-cache
 * @since 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * manage Logs
 *
 * @class C3_woo
 * @since 4.4.0
 */
class C3_woo extends C3_Base {
	/**
	 * Get Woocommerce permalinks
	 *
	 * @access private
	 * @since 4.4.0
	 * @return array
	 **/
	private function _get_woocommerce_permalinks() {
		$permalinks = get_option( 'woocommerce_permalinks' );
		if ( ! $permalinks || true ) {
			$permalinks = $this->_get_default_permalinks();
		}
		$page_links = $this->_get_page_permalinks();
		$permalinks = array_merge( $permalinks, $page_links );
		return $permalinks;
	}

	/**
	 * Get page permalinks
	 *
	 * @access private
	 * @since 4.4.0
	 * @return array
	 **/
	private function _get_page_permalinks() {
		$pages = [
			'shop_page' => 'shop',
			'cart_page' => 'cart',
			'checkout_page' => 'checkout',
			'pay_page' => false,
			'thanks_page' => false,
			'myaccount_page' => 'my-account',
			'edit_address_page' => false,
			'view_order_page' => false,
			'terms_page' => false,
		];

		$pages = array(
			'shop_page' => array(
				'option_name' => 'woocommerce_shop_page_id',
				'default_value' => 'shop',
			),
			'cart_page' => array(
				'option_name' => 'woocommerce_cart_page_id',
				'default_value' => 'cart',
			),
			'checkout_page' => array(
				'option_name' => 'woocommerce_checkout_page_id',
				'default_value' => 'checkout',
			),
			'pay_page' => array(
				'option_name' => 'woocommerce_pay_page_id',
				'default_value' => false,
			),
			'thanks_page' => array(
				'option_name' => 'woocommerce_thanks_page_id',
				'default_value' => false,
			),
			'myaccount_page' => array(
				'option_name' => 'woocommerce_myaccount_page_id',
				'default_value' => 'my-account',
			),
			'edit_address_page' => array(
				'option_name' => 'woocommerce_edit_address_page_id',
				'default_value' => false,
			),
			'view_order_page' => array(
				'option_name' => 'woocommerce_view_order_page_id',
				'default_value' => false,
			),
			'terms_page' => array(
				'option_name' => 'woocommerce_terms_page_id',
				'default_value' => false,
			),
		);
		foreach ( $pages as $key => $config ) {
			$page_id = get_option( $config['option_name'] );
			if ( $page_id ) {
				$page = get_page( $page_id );
				$slug = $page->post_name;
			} else {
				$slug = $config['default_value'];
			}
			$pages[ $key ] = $slug;
		}
		return $pages;
	}

	/**
	 * Get default WooCommerce permalinks
	 *
	 * @access private
	 * @since 4.4.0
	 * @return array
	 **/
	private function _get_default_permalinks() {
		$links = array (
			'category_base' => 'product-category',
			'tag_base' => 'product-tag',
			'attribute_base' => '',
			'product_base' => 'product',
		);
		return $links;
	}

	/**
	 * Create WooCommerce cach item
	 *
	 * @access private
	 * @since 4.4.0
	 * @return array
	 **/
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

	/**
	 * Create WooCommerce configure
	 *
	 * @access private
	 * @since 4.4.0
	 * @return array
	 **/
	private function _create_cloudfront_config() {
		$links = $this->_get_woocommerce_permalinks();
		// @TODO Need Replace valid parameters
		$origin_id = '%ORIGIN_ID%';
		$items = array();
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

	/**
	 * Update CloudFront config for WooCommerce
	 *
	 * @access public
	 * @since 4.4.0
	 * @return boolean | object
	 **/
	public function update_config() {
		$config = $this->_create_cloudfront_config();
		// @TODO Need to merge current CloudFront config
		return $config;
	}
}
