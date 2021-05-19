<?php
namespace C3_CloudFront_Cache_Controller;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Constants {
	//Panel key
	const MENU_ID = 'c3-admin-menu';

	// Action key
	const AUTHENTICATION = 'c3_auth';
	const C3_INVALIDATION = 'c3_invalidation';
	const OPTION_NAME = 'c3_settings';
    
    const DISTRIBUTION_ID = 'distribution_id';
    const ACCESS_KEY = 'access_key';
    const SECRET_KEY = 'secret_key';
	/**
	 * Get Plugin text_domain
	 *
	 * @return string
	 * @since 4.0.0
	 */
	public static function text_domain() {
		static $text_domain;

		if ( ! $text_domain ) {
			$data = get_file_data( C3_PLUGIN_ROOT , array( 'text_domain' => 'Text Domain' ) );
			$text_domain = $data['text_domain'];
		}
		return $text_domain;
	}
}