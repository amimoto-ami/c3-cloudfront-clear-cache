<?php
/**
 * Template file to show the logs of invalidation history
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 **/

namespace C3_CloudFront_Cache_Controller\Templates;
use C3_CloudFront_Cache_Controller\Constants;
use C3_CloudFront_Cache_Controller\Invalidation_Service;
use C3_CloudFront_Cache_Controller\WP\Options_Service;

$options_service = new Options_Service();
$options         = $options_service->get_options();
$text_domain     = Constants::text_domain();

if ( ! $options || ! isset( $options[ Constants::DISTRIBUTION_ID ] ) ) {
	return null;
}

$invalidation_service = new Invalidation_Service();
$histories            = $invalidation_service->list_recent_invalidation_logs();

?>


<table class='wp-list-table widefat plugins'>
	<thead>
		<tr>
			<th colspan='3'>
				<h2><?php _e( 'CloudFront Invalidation Logs', $text_domain ); ?></h2>
			</th>
		</tr>
		<tr>
			<th><b><?php _e( 'Invalidation Start Time (UTC)', $text_domain ); ?></b></th>
			<th><b><?php _e( 'Invalidation Status', $text_domain ); ?></b></th>
			<th><b><?php _e( 'Invalidation Id', $text_domain ); ?></b></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if ( 1 > count( $histories ) ) {
			echo '<tr><td>' . __( 'There is no invalidations', $text_domain ) . '</td></tr>';
		} else {
			foreach ( $histories as $invalidation ) {
				$time = date_i18n( 'y/n/j G:i:s', strtotime( $invalidation['CreateTime'] ) );
				?>
					<tr>
						<td><?php echo esc_html( $time ); ?></td>
						<td><?php echo esc_html( $invalidation['Status'] ); ?></td>
						<td><?php echo esc_html( $invalidation['Id'] ); ?></td>
					</tr>
				<?php
			}
		}
		?>
	</tbody>
</table>
