<?php

$c3_admin = CloudFront_Clear_Cache_Admin::get_instance();
$c3_admin->add_hook();

class CloudFront_Clear_Cache_Admin {
	private static $instance;

	private static $text_domain;

	const MENU_ID = 'c3-admin-menu';
	const MESSAGE_KEY = 'c3-admin-errors';
	const FLUSH_CACHE = 'c3-flush-cache';

	private function __construct() {}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public function add_hook() {
		self::$text_domain = 'a';
		//add_action( 'admin_init',    array( $this, 'c3_admin_init' ) );
		add_action( 'admin_notices', array( $this, 'c3_admin_notices' ) );
	}

	public function c3_admin_init() {
		$option_name = CloudFront_Clear_Cache::OPTION_NAME;
		$nonce_key = CloudFront_Clear_Cache::OPTION_NAME;

		if ( isset ( $_POST[ self::FLUSH_CACHE ] ) && $_POST[ self::FLUSH_CACHE ] ) {
			$c3 = CloudFront_Clear_Cache::get_instance();
			add_filter( 'c3_invalidation_flag' , array( $this , 'force_invalidation') );
			//$c3->c3_invalidation();
		}

		load_plugin_textdomain( self::$text_domain );

	}

	public function force_invalidation( $flag ) {
		return false;
	}

	public function c3_admin_notices(){
		$messages = get_transient( self::MESSAGE_KEY );
		if ( ! $messages ) {
			return;
		}
?>
    <div class="updated">
      <ul>
        <?php foreach ( $messages as $message ) : ?>
          <li><?php echo esc_html( $message );?></li>
        <?php endforeach;?>
      </ul>
    </div>
<?php
	}
}
