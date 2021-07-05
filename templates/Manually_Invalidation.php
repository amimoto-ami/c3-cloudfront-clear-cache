<?php
/**
 * Template file of manually invalidation panel
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\Templates;
use C3_CloudFront_Cache_Controller\Constants;
use C3_CloudFront_Cache_Controller\WP\Options_Service;

$options_service = new Options_Service();
$options         = $options_service->get_options();
$text_domain     = Constants::text_domain();

if ( ! $options || ! isset( $options[ Constants::DISTRIBUTION_ID ] ) ) {
	return null;
}
?>

	<table class='wp-list-table widefat plugins' style="margin-bottom: 2rem;">
		<thead>
			<tr>
				<th colspan='2'>
					<h2><?php _e( 'CloudFront Cache Control', $text_domain ); ?></h2>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>
					<b><?php _e( 'Flush All Cache', $text_domain ); ?></b><br/>
					<small><?php _e( "Notice: Every page's cache is removed.", $text_domain ); ?></small>
				</th>
				<td>
					<form method='post' action=''>
						<input type='hidden' name='invalidation_target' value='all' />
						<?php echo wp_nonce_field( Constants::C3_INVALIDATION, Constants::C3_INVALIDATION, true, false ); ?>
						<?php echo get_submit_button( __( 'Flush All Cache', $text_domain ) ); ?>
					</form>
				</td>
			</tr>
			<tr>
				<th>
					<b><?php _e( 'Flush Cache by Post ids', $text_domain ); ?></b><br/>
					<small><?php _e( 'Provide a post ids like (1,2,3)', $text_domain ); ?></small>
				</th>
				<td>
					<form method='post' action=''>
						<input name="invalidation_target" placeholder="1,2,3" type="text" required="required" />
						<?php echo wp_nonce_field( Constants::C3_INVALIDATION, Constants::C3_INVALIDATION, true, false ); ?>
						<?php echo get_submit_button( __( 'Flush Cache', $text_domain ), 'primary large', 'Submit', false ); ?>
					</form>
				</td>
			</tr>
		</tbody>
	</table>
