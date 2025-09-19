<?php
/**
 * Settings Debug Options Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\Views\Settings;
use C3_CloudFront_Cache_Controller\Constants;
use C3_CloudFront_Cache_Controller\Test\Helpers\WP_Mock_Helper;

/**
 * Settings Debug Options Test
 */
class Settings_Debug_Options_Test extends TestCase {

	public function tearDown(): void {
		\Mockery::close();
	}

	/**
	 * Test case overview: Verify debug options are included in filter_and_escape allowed keys
	 * Expected behavior: Debug options should be processed and included in the result when provided
	 * Test methodology: Pass debug options to filter_and_escape and verify they are included in output
	 */
	public function test_debug_options_allowed_in_filter_and_escape() {
		$settings = new Settings();
		
		$test_args = array(
			Constants::DISTRIBUTION_ID => 'test_dist_id',
			Constants::ACCESS_KEY => 'test_access_key',
			Constants::SECRET_KEY => 'test_secret_key',
			Constants::DEBUG_CRON_REGISTER => '1',
			Constants::DEBUG_INVALIDATION_PARAMS => '1',
		);
		
		$result = $settings->filter_and_escape($test_args);
		
		$this->assertArrayHasKey(Constants::DEBUG_CRON_REGISTER, $result);
		$this->assertArrayHasKey(Constants::DEBUG_INVALIDATION_PARAMS, $result);
		$this->assertEquals('1', $result[Constants::DEBUG_CRON_REGISTER]);
		$this->assertEquals('1', $result[Constants::DEBUG_INVALIDATION_PARAMS]);
	}

	/**
	 * Test case overview: Verify debug options default to empty when not provided
	 * Expected behavior: Debug options should not be included in result when not provided in input
	 * Test methodology: Call filter_and_escape without debug options and verify they are not in output
	 */
	public function test_debug_options_default_empty() {
		$settings = new Settings();
		
		$test_args = array(
			Constants::DISTRIBUTION_ID => 'test_dist_id',
		);
		
		$result = $settings->filter_and_escape($test_args);
		
		$this->assertArrayNotHasKey(Constants::DEBUG_CRON_REGISTER, $result);
		$this->assertArrayNotHasKey(Constants::DEBUG_INVALIDATION_PARAMS, $result);
	}

	/**
	 * Test case overview: Verify debug options are properly escaped
	 * Expected behavior: Debug option values should be escaped using esc_attr
	 * Test methodology: Pass potentially unsafe values and verify they are properly escaped
	 */
	public function test_debug_options_are_escaped() {
		$settings = new Settings();
		
		$test_args = array(
			Constants::DISTRIBUTION_ID => 'test_dist_id',
			Constants::DEBUG_CRON_REGISTER => '<script>alert("xss")</script>',
			Constants::DEBUG_INVALIDATION_PARAMS => '"malicious"',
		);
		
		$result = $settings->filter_and_escape($test_args);
		
		$this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result[Constants::DEBUG_CRON_REGISTER]);
		$this->assertEquals('&quot;malicious&quot;', $result[Constants::DEBUG_INVALIDATION_PARAMS]);
	}
}
