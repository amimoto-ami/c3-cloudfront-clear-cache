<?php
use C3_CloudFront_Cache_Controller\Constants;
use C3_CloudFront_Cache_Controller\WP\Options_Service;

$options_service = new Options_Service();
$options = $options_service->get_options();
$text_domain = Constants::text_domain();

if ( ! $options || ! isset( $options[ Constants::DISTRIBUTION_ID ] ) ) {
    return null;
}
?>

<form method='post' action=''>
    <table class='wp-list-table widefat plugins'>
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
                    <small><?php _e( "Notice: Every page's cache is removed." , $text_domain ); ?></small>
                </th>
                <td>
                    <input type='hidden' name='invalidation_target' value='all' />
                    <?php echo wp_nonce_field( Constants::C3_INVALIDATION , Constants::C3_INVALIDATION , true , false ); ?>
                    <?php echo get_submit_button( __( 'Flush All Cache', $text_domain ) );?>
                </td>
            </tr>
        </tbody>
    </table>
</form>