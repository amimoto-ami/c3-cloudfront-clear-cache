<?php
/**
 * Admin notice manager
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin notice class
 *
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */
class Admin_Notice {

	/**
	 * Echo the success message
	 *
	 * @param string $message Successfull message.
	 * @param string $code Successfull code.
	 */
	public function echo_success_message( string $message, string $code = null ) {
		?>
		<div class='notice notice-success is-dismissible'>
			<p>
				<?php if ( isset( $code ) ) { ?> 
					<b><?php echo esc_html( $code ); ?></b>
				<?php }; ?>
				<?php echo esc_html( $message ); ?>
			</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
		<?php
	}

	/**
	 * Show success message
	 *
	 * @param string $message Successfull message.
	 * @param string $code Successfull code.
	 */
	public function show_admin_success( string $message, string $code = null ) {
		add_action(
			'admin_notices',
			function () use ( $message, $code ) {
				$this->echo_success_message( $message, $code );
			}
		);
	}

	/**
	 * Echo error message
	 *
	 * @param \WP_Error $result Error object.
	 */
	public function echo_error( \WP_Error $result ) {
		$messages = $result->get_error_messages();
		$codes    = $result->get_error_codes();
		$code     = esc_html( $codes[0] );
		?>
		<div class='error notice-error is-dismissible'><ul>
				<?php foreach ( $messages as $key => $message ) : ?>
					<li>
						<b><?php echo esc_html( $code ); ?></b>
						: <?php echo esc_html( $message ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
		<?php
	}

	/**
	 * Show error message
	 *
	 * @param \WP_Error $e Error object.
	 */
	public function show_admin_error( \WP_Error $e ) {
		add_action(
			'admin_notices',
			function () use ( $e ) {
				$this->echo_error( $e );
			}
		);
	}
}
