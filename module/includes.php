<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
require_once( 'utils.php' );
require_once( 'base.php' );
require_once( 'model/client-base.php' );

// Model
require_once( 'model/auth.php' );
require_once( 'model/invalidation.php' );
if ( 'v2' === c3_get_aws_sdk_version() ) {
	require_once( 'model/client-v2.php' );
} else {
	require_once( 'model/client-v3.php' );
}
if ( ! class_exists( 'CF_preview_fix' ) ) {
	require_once( 'model/cf-preview-fix.php' );
}
// class
require_once( 'classes/class.logs.php' );
require_once( 'model/class.logs.php' );

// fixtures
require_once( 'model/fixtures/wp_is_mobile.php' );
require_once( 'model/fixtures/avoid_preview_cache.php' );

// View
require_once( 'view/components.php' );
require_once( 'view/root.php' );
require_once( 'view/menus.php' );
