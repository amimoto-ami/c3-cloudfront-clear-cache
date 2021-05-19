<?php
namespace C3_CloudFront_Cache_Controller\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Admin_Notice {
    public function echo_success_message( string $message, string $code = null ) {
        ?>
        <div class='notice notice-success is-dismissible'>
            <p>
                <?php if ( isset( $code ) ) {?> 
                    <b><?php echo esc_html( $code );?></b>
                <? } ;?>
                <?php echo esc_html( $message ); ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>
        <?php
    }
    public function show_admin_success( string $message, string $code = null ) {
        add_action( 'admin_notices', function () use ( $message, $code ) {
            $this->echo_success_message( $message, $code );
        });
    }
    public function echo_error( \WP_Error $result ) {
        $messages = $result->get_error_messages();
        $codes = $result->get_error_codes();
        $code = esc_html( $codes[0] );
        ?>
        <div class='error notice-error is-dismissible'><ul>
                <?php foreach ( $messages as $key => $message ) : ?>
                    <li>
                        <b><?php echo esc_html( $code );?></b>
                        : <?php echo esc_html( $message );?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>
        <?php
    }

    public function show_admin_error( \WP_Error $e ) {
        add_action( 'admin_notices', function () use ($e) {
            $this->echo_error( $e );
        });
    }
}