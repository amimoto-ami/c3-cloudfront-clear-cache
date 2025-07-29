<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

?>

<?php
require dirname( __DIR__ ) . '/vendor/autoload.php';

// Add polyfill for enum_exists() function for PHP < 8.1
if ( ! function_exists( 'enum_exists' ) ) {
    /**
     * Polyfill for enum_exists() function.
     * 
     * @param string $enum The enum name to check.
     * @param bool $autoload Whether to autoload if not already loaded.
     * @return bool Always returns false for PHP < 8.1 since enums don't exist.
     */
    function enum_exists( $enum, $autoload = true ) {
        return false;
    }
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/' );
}

// See temp dir.
if ( ! $_tests_dir ) {
	$_try_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
	if ( file_exists( $_try_tests_dir . '/includes/functions.php' ) ) {
		$_tests_dir = $_try_tests_dir;
	}
	unset( $_try_tests_dir );
}

// Next, try the WP_PHPUNIT composer package.
if ( ! $_tests_dir ) {
	$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}

// See if we're installed inside an existing WP dev instance.
if ( ! $_tests_dir ) {
	$_try_tests_dir = __DIR__ . '/../../../../../tests/phpunit';
	if ( file_exists( $_try_tests_dir . '/includes/functions.php' ) ) {
		$_tests_dir = $_try_tests_dir;
	}
}

// Fallback.
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore
	exit( 1 );
}
// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';