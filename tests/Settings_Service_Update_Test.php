<?php
/**
 * Settings Service Update Options Detailed Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test;

use C3_CloudFront_Cache_Controller\Settings_Service;
use C3_CloudFront_Cache_Controller\Constants;
use C3_CloudFront_Cache_Controller\WP\Options_Service;
use Mockery as m;
use WP_Error;

/**
 * @group settings
 */
class Settings_Service_Update_Test extends \WP_UnitTestCase {
	public function tearDown(): void {
		m::close();
		parent::tearDown();
	}

	public function test_update_options_returns_wp_error_and_does_not_save_when_cf_api_fails() {
		// Prepare mocks
		$mock_cf = m::mock('C3_CloudFront_Cache_Controller\\AWS\\CloudFront_Service');
		$mock_cf->shouldReceive('try_to_call_aws_api')
			->once()
			->andReturn(new WP_Error('C3 Auth Error', 'Invalid credentials'));

		$mock_options = m::mock(Options_Service::class);
		$mock_options->shouldReceive('update_options')
			->never(); // Should NOT be called on failure

		$service = new Settings_Service($mock_cf, $mock_options);

		$result = $service->update_options('DIST_ID', 'AKIAxxxx', 'SECRET');

		$this->assertInstanceOf(WP_Error::class, $result, 'Should return WP_Error on failure');
	}

	public function test_update_options_saves_options_when_cf_api_succeeds() {
		// Clean slate
		delete_option(Constants::OPTION_NAME);

		// Prepare mocks
		$mock_cf = m::mock('C3_CloudFront_Cache_Controller\\AWS\\CloudFront_Service');
		$mock_cf->shouldReceive('try_to_call_aws_api')
			->once()
			->andReturn(null); // Success path

		$mock_options = m::mock(Options_Service::class);
		$mock_options->shouldReceive('update_options')
			->once()
			->with('DIST_ID', 'AKIAxxxx', 'SECRET')
			->andReturnUsing(function($distribution_id, $access_key, $secret_key) {
				$options = [
					'distribution_id' => $distribution_id,
					'access_key'      => $access_key,
					'secret_key'      => $secret_key,
				];
				// Persist so that we can verify later using the real WP option API
				update_option(Constants::OPTION_NAME, $options);
			});

		$service = new Settings_Service($mock_cf, $mock_options);

		$result = $service->update_options('DIST_ID', 'AKIAxxxx', 'SECRET');

		$this->assertNull($result, 'On success should return null');

		// Verify wp_options actually updated via Options_Service mock implementation above
		$saved = get_option(Constants::OPTION_NAME);
		$this->assertIsArray($saved);
		$this->assertSame('DIST_ID', $saved['distribution_id']);
		$this->assertSame('AKIAxxxx', $saved['access_key']);
		$this->assertSame('SECRET', $saved['secret_key']);

		// Clean up
		delete_option(Constants::OPTION_NAME);
	}
} 