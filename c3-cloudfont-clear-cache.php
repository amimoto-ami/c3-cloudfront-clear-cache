<?php
/*
Plugin Name: C3 Cloudfront Clear Cache
Version: 1.0
Plugin URI:
Description:This is simple plugin that clear all cloudfront cache if you publish posts.
Author: hideokamoto
Author URI: http://wp-kyoto.net/
*/
require_once('vendor/autoload.php');
use Aws\CloudFront\CloudFrontClient;
use Aws\Common\Credentials\Credentials;

add_action('transition_post_status', 'c3_invalidation', 10, 3);
function c3_is_invalidation ( $new_status, $old_status ){
  if ( 'publish' === $new_status ) {
    //記事公開または記事編集時
    $result = true;
  } elseif ( 'publish' === $old_status && $new_status !== $old_status ){
    //記事を非公開にした際
    $result = true;
  } else {
    $result = false;
  }
  return $result;
}

function c3_get_settings() {
  $c3_settings = get_option('c3_settings');
  //ひとつでも空の値があれば以降の処理を止める
  foreach ($c3_settings as $key => $value) {
    if ( !$value )
      return false;
  }
  return $c3_settings;
}

function c3_invalidation ( $new_status, $old_status, $post ) {
  if ( !c3_is_invalidation ( $new_status, $old_status ) )
    return;

  $key = 'exclusion-process';
  if (get_transient($key))
    return;

  $c3_settings = c3_get_settings();
  if ( !$c3_settings )
    return;

  //CloudFrontクラスを初期化
  $credentials = new Credentials( esc_attr($c3_settings['access_key']), esc_attr($c3_settings['secret_key']) );
  $cloudFront = CloudFrontClient::factory(array(
  	'credentials' => $credentials
  ));

  $args = c3_make_args( $c3_settings );

  set_transient($key, TRUE, 5 * 60);
  try {
    $result = $cloudFront->createInvalidation( $args );
  } catch (Aws\CloudFront\Exception\TooManyInvalidationsInProgressException $e) {
    error_log($e->__toString( ),0);
  }
}

function c3_make_args( $c3_settings ) {
  return array(
    'DistributionId' => esc_attr($c3_settings['distribution_id']),
    'Paths' => array(
      'Quantity' => 1,
      'Items'    => array( "/*" ),
    ),
    'CallerReference' => uniqid(),
  );
}
