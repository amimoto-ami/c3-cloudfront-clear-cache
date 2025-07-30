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

// エラーハンドリングを追加
if ( is_wp_error( $histories ) ) {
	$error_message = $histories->get_error_message();
	?>
	<div class="notice notice-error">
		<p><strong><?php _e( 'Error loading invalidation logs:', $text_domain ); ?></strong> <?php echo esc_html( $error_message ); ?></p>
	</div>
	<?php
	return;
}

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
			<th><b><?php _e( 'Actions', $text_domain ); ?></b></th>
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
					<td>
						<button type="button" class="button c3-view-details" data-invalidation-id="<?php echo esc_attr( $invalidation['Id'] ); ?>">
							<?php _e( 'View Details', $text_domain ); ?>
						</button>
					</td>
				</tr>
			<?php
		}
		}
		?>
	</tbody>
</table>

<div id="c3-invalidation-details-modal" style="display: none;">
	<div class="c3-modal-overlay">
		<div class="c3-modal-content">
			<div class="c3-modal-header">
				<h3><?php _e( 'Invalidation Details', $text_domain ); ?></h3>
				<button type="button" class="c3-modal-close">&times;</button>
			</div>
			<div class="c3-modal-body">
				<div id="c3-invalidation-details-content">
					<div class="c3-loading"><?php _e( 'Loading...', $text_domain ); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.c3-modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.5);
	z-index: 100000;
	display: flex;
	align-items: center;
	justify-content: center;
}
.c3-modal-content {
	background: white;
	border-radius: 4px;
	max-width: 600px;
	width: 90%;
	max-height: 80vh;
	overflow-y: auto;
	box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.c3-modal-header {
	padding: 20px;
	border-bottom: 1px solid #ddd;
	display: flex;
	justify-content: space-between;
	align-items: center;
}
.c3-modal-header h3 {
	margin: 0;
}
.c3-modal-close {
	background: none;
	border: none;
	font-size: 24px;
	cursor: pointer;
	padding: 0;
	width: 30px;
	height: 30px;
	display: flex;
	align-items: center;
	justify-content: center;
}
.c3-modal-body {
	padding: 20px;
}
.c3-detail-row {
	margin-bottom: 15px;
	padding-bottom: 15px;
	border-bottom: 1px solid #eee;
}
.c3-detail-row:last-child {
	border-bottom: none;
}
.c3-detail-label {
	font-weight: bold;
	margin-bottom: 5px;
}
.c3-detail-value {
	word-break: break-all;
}
.c3-paths-list {
	max-height: 200px;
	overflow-y: auto;
	background: #f9f9f9;
	padding: 10px;
	border-radius: 4px;
}
.c3-error {
	color: #d63638;
	padding: 10px;
	background: #fcf0f1;
	border: 1px solid #d63638;
	border-radius: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
	$('.c3-view-details').on('click', function() {
		var invalidationId = $(this).data('invalidation-id');
		var modal = $('#c3-invalidation-details-modal');
		var content = $('#c3-invalidation-details-content');
		
		modal.show();
		content.html('<div class="c3-loading"><?php _e( 'Loading...', $text_domain ); ?></div>');
		
		$.post(ajaxurl, {
			action: 'c3_get_invalidation_details',
			invalidation_id: invalidationId,
			nonce: '<?php echo wp_create_nonce( 'c3_invalidation_details_nonce' ); ?>'
		}, function(response) {
			if (response.success) {
				var data = response.data;
				var html = '<div class="c3-detail-row">' +
					'<div class="c3-detail-label"><?php _e( 'Invalidation ID', $text_domain ); ?>:</div>' +
					'<div class="c3-detail-value">' + data.Id + '</div>' +
					'</div>' +
					'<div class="c3-detail-row">' +
					'<div class="c3-detail-label"><?php _e( 'Status', $text_domain ); ?>:</div>' +
					'<div class="c3-detail-value">' + data.Status + '</div>' +
					'</div>' +
					'<div class="c3-detail-row">' +
					'<div class="c3-detail-label"><?php _e( 'Create Time', $text_domain ); ?>:</div>' +
					'<div class="c3-detail-value">' + data.CreateTime + '</div>' +
					'</div>';
				
				if (data.InvalidationBatch && data.InvalidationBatch.CallerReference) {
					html += '<div class="c3-detail-row">' +
						'<div class="c3-detail-label"><?php _e( 'Caller Reference', $text_domain ); ?>:</div>' +
						'<div class="c3-detail-value">' + data.InvalidationBatch.CallerReference + '</div>' +
						'</div>';
				}
				
				if (data.InvalidationBatch && data.InvalidationBatch.Paths && data.InvalidationBatch.Paths.Items) {
					html += '<div class="c3-detail-row">' +
						'<div class="c3-detail-label"><?php _e( 'Invalidated Paths', $text_domain ); ?> (' + data.InvalidationBatch.Paths.Quantity + '):</div>' +
						'<div class="c3-paths-list">';
					
					data.InvalidationBatch.Paths.Items.forEach(function(path) {
						html += '<div>' + path + '</div>';
					});
					
					html += '</div></div>';
				}
				
				content.html(html);
			} else {
				content.html('<div class="c3-error">' + response.data + '</div>');
			}
		}).fail(function() {
			content.html('<div class="c3-error"><?php _e( 'Failed to load invalidation details.', $text_domain ); ?></div>');
		});
	});
	
	$('.c3-modal-close, .c3-modal-overlay').on('click', function(e) {
		if (e.target === this) {
			$('#c3-invalidation-details-modal').hide();
		}
	});
	
	$('.c3-modal-content').on('click', function(e) {
		e.stopPropagation();
	});
});
</script>
