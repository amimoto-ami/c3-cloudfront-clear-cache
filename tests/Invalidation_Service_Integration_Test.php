<?php
/**
 * Invalidation Service Integration Detailed Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test;

use C3_CloudFront_Cache_Controller\Invalidation_Service;
use C3_CloudFront_Cache_Controller\Constants;
use C3_CloudFront_Cache_Controller\WP\Options_Service;
use C3_CloudFront_Cache_Controller\AWS\Invalidation_Batch_Service;
use Mockery as m;
use WP_Error;

/**
 * @group invalidation
 */
class Invalidation_Service_Integration_Test extends \WP_UnitTestCase {
	public function tearDown(): void {
		m::close();
		parent::tearDown();
	}

	private function create_basic_mocks(array $plugin_options) {
		// Options service that returns desired plugin options
		$mock_options = m::mock(Options_Service::class);
		$mock_options->shouldReceive('get_options')->andReturn($plugin_options);
		$mock_options->shouldReceive('home_url')->andReturn('https://example.org/');

		// Hooks service – basic passthrough
		$mock_hooks = \C3_CloudFront_Cache_Controller\Test\Helpers\WP_Mock_Helper::create_mock_hooks_service();

		// Transient service – disable cron registration path
		$mock_transient = m::mock('C3_CloudFront_Cache_Controller\\WP\\Transient_Service');
		$mock_transient->shouldReceive('should_regist_cron_job')->andReturn(false);
		$mock_transient->shouldReceive('set_invalidation_time')->andReturnNull();

		return [$mock_options, $mock_hooks, $mock_transient];
	}

	public function test_invalidate_all_success() {
		list($mock_options, $mock_hooks, $mock_transient) = $this->create_basic_mocks([
			'distribution_id' => 'DIST_ID',
			'access_key'      => null,
			'secret_key'      => null,
		]);

		// Batch service returns a dummy query
		$dummy_query = [
			'DistributionId'   => 'DIST_ID',
			'InvalidationBatch' => [
				'Paths' => [
					'Items'    => ['/*'],
					'Quantity' => 1,
				],
				'CallerReference' => 'test',
			],
		];
		$mock_batch = m::mock(Invalidation_Batch_Service::class);
		$mock_batch->shouldReceive('create_batch_for_all')->with('DIST_ID')->andReturn($dummy_query);

		// CloudFront service – simulate success
		$mock_cf = m::mock('C3_CloudFront_Cache_Controller\\AWS\\CloudFront_Service');
		$mock_cf->shouldReceive('create_invalidation')->with($dummy_query)->andReturn(true);

		$service = new Invalidation_Service(
			$mock_hooks,
			$mock_transient,
			$mock_options,
			$mock_batch,
			$mock_cf
		);

		$result = $service->invalidate_all();

		$this->assertIsArray($result);
		$this->assertSame('Success', $result['type']);
	}

	public function test_invalidate_all_error_from_cloudfront() {
		list($mock_options, $mock_hooks, $mock_transient) = $this->create_basic_mocks([
			'distribution_id' => 'DIST_ID',
		]);

		$dummy_query = ['foo' => 'bar'];
		$mock_batch = m::mock(Invalidation_Batch_Service::class);
		$mock_batch->shouldReceive('create_batch_for_all')->andReturn($dummy_query);

		$mock_cf = m::mock('C3_CloudFront_Cache_Controller\\AWS\\CloudFront_Service');
		$mock_cf->shouldReceive('create_invalidation')->andReturn(new WP_Error('C3 Invalidation Error', 'Failed'));

		$service = new Invalidation_Service($mock_hooks, $mock_transient, $mock_options, $mock_batch, $mock_cf);

		$result = $service->invalidate_all();

		$this->assertInstanceOf(WP_Error::class, $result);
	}

	public function test_invalidate_posts_cache_success() {
		// Create sample posts
		$post1 = $this->factory->post->create_and_get(['post_status' => 'publish', 'post_name' => 'sample-1']);
		$post2 = $this->factory->post->create_and_get(['post_status' => 'publish', 'post_name' => 'sample-2']);

		list($mock_options, $mock_hooks, $mock_transient) = $this->create_basic_mocks([
			'distribution_id' => 'DIST_ID',
		]);

		$dummy_query = [
			'DistributionId'   => 'DIST_ID',
			'InvalidationBatch' => [
				'Paths' => [
					'Items'    => ['/', '/sample-1/', '/sample-2/'],
					'Quantity' => 3,
				],
				'CallerReference' => 'posts',
			],
		];

		$mock_batch = m::mock(Invalidation_Batch_Service::class);
		$mock_batch->shouldReceive('create_batch_by_posts')->andReturn($dummy_query);

		$mock_cf = m::mock('C3_CloudFront_Cache_Controller\\AWS\\CloudFront_Service');
		$mock_cf->shouldReceive('create_invalidation')->with($dummy_query)->andReturn(true);

		$service = new Invalidation_Service($mock_hooks, $mock_transient, $mock_options, $mock_batch, $mock_cf);

		$result = $service->invalidate_posts_cache([$post1, $post2], true);

		$this->assertIsArray($result);
		$this->assertSame('Success', $result['type']);
	}
} 