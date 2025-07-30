<?php
/**
 * Template file to show cache purge status
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 **/

namespace C3_CloudFront_Cache_Controller\Templates;
use C3_CloudFront_Cache_Controller\Constants;
use C3_CloudFront_Cache_Controller\WP\Status_Service;
use C3_CloudFront_Cache_Controller\WP\Options_Service;

$options_service = new Options_Service();
$options         = $options_service->get_options();
$text_domain     = Constants::text_domain();

if ( ! $options || ! isset( $options[ Constants::DISTRIBUTION_ID ] ) ) {
	return null;
}

$status_service = new Status_Service();
$status         = $status_service->get_cache_status();
?>

<table class='wp-list-table widefat plugins'>
	<thead>
		<tr>
			<th colspan='2'>
				<h2><?php _e( 'Cache Purge Status', $text_domain ); ?></h2>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><strong><?php _e( 'Current Status', $text_domain ); ?></strong></td>
			<td>
				<?php
				$status_text = '';
				$status_class = '';
				switch ( $status['current_status'] ) {
					case 'processing':
						$status_text = __( 'Processing...', $text_domain );
						$status_class = 'notice-warning';
						break;
					case 'scheduled':
						$status_text = __( 'Scheduled', $text_domain );
						$status_class = 'notice-info';
						break;
					case 'error':
						$status_text = __( 'Error', $text_domain );
						$status_class = 'notice-error';
						break;
					default:
						$status_text = __( 'Idle', $text_domain );
						$status_class = 'notice-success';
				}
				?>
				<span class="<?php echo esc_attr( $status_class ); ?>" style="padding: 2px 8px; border-radius: 3px;">
					<?php echo esc_html( $status_text ); ?>
				</span>
			</td>
		</tr>
		<?php if ( $status['last_successful'] ) : ?>
		<tr>
			<td><strong><?php _e( 'Last Successful Purge', $text_domain ); ?></strong></td>
			<td>
				<?php echo esc_html( $status['last_successful']['timestamp'] ); ?>
				<small style="color: #666;"> (UTC)</small>
				<?php if ( isset( $status['last_successful']['invalidation_id'] ) ) : ?>
					<br><small>ID: <?php echo esc_html( $status['last_successful']['invalidation_id'] ); ?></small>
				<?php endif; ?>
			</td>
		</tr>
		<?php endif; ?>
		<?php if ( $status['next_scheduled'] ) : ?>
		<tr>
			<td><strong><?php _e( 'Next Scheduled Purge', $text_domain ); ?></strong></td>
			<td>
				<?php echo esc_html( $status['next_scheduled'] ); ?>
				<small style="color: #666;"> (UTC)</small>
			</td>
		</tr>
		<?php endif; ?>
		<?php if ( $status['scheduled_paths'] && is_array( $status['scheduled_paths'] ) ) : ?>
		<tr>
			<td><strong><?php _e( 'Scheduled Paths', $text_domain ); ?></strong></td>
			<td>
				<?php if ( count( $status['scheduled_paths'] ) === 1 && $status['scheduled_paths'][0] === '/*' ) : ?>
					<span class="notice-info" style="padding: 2px 8px; border-radius: 3px;">
						<?php _e( 'Full cache clear (all paths)', $text_domain ); ?>
					</span>
				<?php else : ?>
					<div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 8px; background: #f9f9f9;">
						<?php foreach ( $status['scheduled_paths'] as $path ) : ?>
							<div><code><?php echo esc_html( $path ); ?></code></div>
						<?php endforeach; ?>
					</div>
					<small><?php printf( _n( '%d path scheduled', '%d paths scheduled', count( $status['scheduled_paths'] ), $text_domain ), count( $status['scheduled_paths'] ) ); ?></small>
				<?php endif; ?>
			</td>
		</tr>
		<?php endif; ?>
		<?php if ( $status['last_error'] ) : ?>
		<tr>
			<td><strong><?php _e( 'Last Error', $text_domain ); ?></strong></td>
			<td>
				<span class="notice-error" style="padding: 2px 8px; border-radius: 3px;">
					<?php echo esc_html( $status['last_error']['timestamp'] ); ?><small style="color: #666;"> (UTC)</small>: 
					<?php echo esc_html( $status['last_error']['message'] ); ?>
				</span>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>
<br>
