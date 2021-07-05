<?php
/**
 * Load classes
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @since 6.1.1
 * @package C3_CloudFront_Cache_Controller
 */

namespace C3_CloudFront_Cache_Controller;

/**
 * Class Class_Loader
 */
class Class_Loader {

	/**
	 * Plugin base directory
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * Class_Loader constructor.
	 *
	 * @param string $base_dir Plugin base directory.
	 */
	public function __construct( $base_dir ) {
		$this->base_dir = $base_dir;
		$this->register_autoloader();
	}

	/**
	 * Register auto loader
	 */
	private function register_autoloader() {
		spl_autoload_register( array( $this, 'autoloader' ) );
	}

	/**
	 * Load class files
	 *
	 * @param string $class_name Target class name.
	 * @return void
	 */
	public function autoloader( $class_name ) {
		$dir = $this->base_dir;

		preg_match( '/(?<=\\\)([^\\\]+$)/', $class_name, $matches );

		if ( ! $matches || empty( $matches ) || ! $matches[0] ) {
			return;
		}
		$file_name = $matches[0] . '.php';

		$file_path = $dir . '/' . $file_name;
		if ( is_readable( $file_path ) ) {
			include_once $file_path;
		}
	}
}
