<?php
/**
 * Load class files
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 **/

define( 'C3_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'C3_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'C3_PLUGIN_ROOT', __FILE__ );
require_once( __DIR__ . '/classes/Class_Loader.php' );
new C3_CloudFront_Cache_Controller\Class_Loader( dirname( __FILE__ ) . '/classes' );
new C3_CloudFront_Cache_Controller\Class_Loader( dirname( __FILE__ ) . '/classes/WP' );
new C3_CloudFront_Cache_Controller\Class_Loader( dirname( __FILE__ ) . '/classes/AWS' );
new C3_CloudFront_Cache_Controller\Class_Loader( dirname( __FILE__ ) . '/classes/Views' );
