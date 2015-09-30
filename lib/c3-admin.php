<?php
add_action(   'admin_menu', 'c3_setting_menu' );
add_action(   'admin_init', 'c3_admin_init');
add_action('admin_notices', 'c3_admin_notices');

function c3_setting_menu(){
  add_menu_page(
    __('C3 Settings', 'c3_cloudfront_clear_cache'),
    __('C3 Settings', 'c3_cloudfront_clear_cache'),
    'administrator',
    'c3-admin-menu',
    'c3_admin_menu'
  );
}

function c3_admin_menu(){
  $c3_settings = get_option('c3_settings');
  if ( ! $c3_settings) {
    $c3_settings = array(
      'distribution_id' => '',
      'access_key'      => '',
      'secret_key'      => ''
    );
  }
?>
<div class="wrap">
  <h2><?php printf(__('C3 CloudFront Clear Cache','c3_cloudfront_clear_cache'));?></h2>
  <h3><?php printf(__('General Settings','c3_cloudfront_clear_cache'));?></h3>
  <form method="post" action="" novalidate="novalidate">
    <?php wp_nonce_field( 'my-nonce-key', 'c3-admin-menu');?>
    <table class="widefat form-table">
      <tbody>
        <tr>
          <th>　<?php printf(__('CloudFront Distribution ID','c3_cloudfront_clear_cache'));?></th>
          <td><input name="c3_settings[distribution_id]" type="text" id='distribution_id' value="<?php echo esc_attr($c3_settings['distribution_id']);?>" class="regular-text code"></td>
        </tr>
        <tr>
          <th>　<?php printf(__('AWS Access Key','c3_cloudfront_clear_cache'));?></th>
          <td><input name="c3_settings[access_key]" type="text" id='access_key' value="<?php echo esc_attr($c3_settings['access_key']);?>" class="regular-text code"></td>
        </tr>
        <tr>
          <th>　<?php printf(__('AWS Secret Key','c3_cloudfront_clear_cache'));?></th>
          <td><input name="c3_settings[secret_key]" type="text" id='secret_key' value="<?php echo esc_attr($c3_settings['secret_key']);?>" class="regular-text code"></td>
        </tr>
      </tbody>
    </table>
    <p class="submit">
      <input type="submit"
        class="button button-primary"
        value="<?php printf(__('Save Change','c3_cloudfront_clear_cache'));?>">
    </p>
  </form>
</div>
<?php
}

function c3_admin_init()
{
  if( isset ( $_POST['c3-admin-menu']) && $_POST['c3-admin-menu'] ){
    if( check_admin_referer('my-nonce-key', 'c3-admin-menu')) {
      $e = new WP_Error();
      update_option('c3_settings', $_POST['c3_settings']);
    } else {
      update_option('c3_settings', '');
    }
    wp_safe_redirect(menu_page_url('c3-admin-menu', false));
  }
}

function c3_admin_notices(){
  ?>
  <?php if($messages = get_transient('c3-admin-errors')):?>
    <div class="updated">
      <ul>
        <?php foreach( $messages as $message):?>
          <li><?php echo esc_html($message);?></li>
        <?php endforeach;?>
      </ul>
    </div>
  <?php endif;
}
