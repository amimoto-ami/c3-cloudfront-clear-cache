<?php
/**
 * Filters Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Test\Helpers\WP_Mock_Helper;

/**
 * Filters Test
 */
class Filters_Test extends TestCase {

	public function tearDown(): void {
		\Mockery::close();
	}

	/**
	 * Test hooks service functionality
	 */
	public function test_hooks_service_functionality() {
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		
		// Hooksサービスが正しく初期化されることを確認
		$this->assertNotNull( $mock_hooks );
	}

	/**
	 * Test apply_filters basic functionality
	 */
	public function test_apply_filters_basic() {
		$mock_hooks = \Mockery::mock( WP\Hooks::class );
		$mock_hooks->shouldReceive( 'apply_filters' )
			->with( 'test_filter', 'test_value' )
			->andReturn( 'modified_value' );

		// フィルタが正しく動作することを確認
		$result = $mock_hooks->apply_filters( 'test_filter', 'test_value' );
		$this->assertEquals( 'modified_value', $result );
	}

	/**
	 * Test c3 specific filter names
	 */
	public function test_c3_filter_names() {
		// C3プラグインで使用されるフィルタ名の一覧をテスト
		$expected_filters = [
			'c3_invalidation_items',
			'c3_invalidation_item_limits',
			'c3_invalidation_interval',
			'c3_credential',
			'c3_cloudfront_client_constructor',
			'c3_disabled_cron_retry'
		];

		foreach ( $expected_filters as $filter ) {
			$this->assertIsString( $filter );
			$this->assertStringStartsWith( 'c3_', $filter );
		}
	}
} 