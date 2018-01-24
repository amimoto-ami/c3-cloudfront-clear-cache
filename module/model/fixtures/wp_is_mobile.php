<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Fixture for CloudFront
 *
 * @return bool
 * @since 5.0.0
 **/
add_filter( 'wp_is_mobile', function( $is_mobile ) {
    // CloudFront でスマートフォンと判定された場合、true を返す。
    if ( isset($_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER']) && "true" === $_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'] ) {
        $is_mobile = true;
    }

    // CloudFront でタブレットと判定された場合、true を返す。
    // （タブレットはPCと同じ扱いにしたい場合は、$is_mobile を false にする
    if ( isset($_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER']) && "true" === $_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER'] ) {
        $is_mobile = true;
    }

    return $is_mobile;
});
