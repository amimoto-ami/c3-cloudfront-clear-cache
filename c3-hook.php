<?php

add_filter( 'c3_settings', 'amimoto_remove_credential_for_cloudfront' );
add_filter( 'c3_setting_keys', 'amimoto_remove_credential_for_cloudfront');
function amimoto_remove_credential_for_cloudfront( $c3_settings ) {
  unset( $c3_settings['access_key'] );
  unset( $c3_settings['secret_key'] );
  return $c3_settings;
}
