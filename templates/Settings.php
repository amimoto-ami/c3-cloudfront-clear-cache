<?php
/**
 * Template file of the plugin setting panel
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 **/

namespace C3_CloudFront_Cache_Controller\Templates;

use C3_CloudFront_Cache_Controller\Constants;

$text_domain = Constants::text_domain();
?>
<div class='wrap' id='c3-dashboard'>
<?php
	$title = '<h2>' . __( 'C3 Cloudfront Cache Controller', $text_domain ) . '</h2>';
	echo apply_filters( 'c3_after_title', $title );
?>
<?php
require_once( C3_PLUGIN_PATH . '/templates/Plugin_Options.php' );
require_once( C3_PLUGIN_PATH . '/templates/Manually_Invalidation.php' );
require_once( C3_PLUGIN_PATH . '/templates/Invalidation_Logs.php' );

?>
</div>
