<?php
/**
 * Settings Service Basic Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\Settings_Service;
use C3_CloudFront_Cache_Controller\Test\Helpers\WP_Mock_Helper;

/**
 * Settings Service Basic Test
 */
class Settings_Service_Basic_Test extends TestCase {

	public function tearDown(): void {
		\Mockery::close();
	}

	/**
	 * Test constructor default initialization
	 */
	public function test_constructor_default_initialization() {
		$service = new Settings_Service();
		$this->assertInstanceOf( Settings_Service::class, $service );
	}

	/**
	 * Test get_options basic functionality
	 */
	public function test_get_options_basic() {
		$service = new Settings_Service();
		
		// デフォルト初期化されたサービスでget_optionsが呼べることを確認
		$result = $service->get_options();
		
		// 実際の戻り値は環境依存だが、エラーが出ないことを確認
		$this->assertTrue( is_array( $result ) || is_null( $result ) );
	}

	/**
	 * Test update_options method exists
	 */
	public function test_update_options_method_exists() {
		$service = new Settings_Service();
		
		// update_optionsメソッドが存在することを確認
		$this->assertTrue( method_exists( $service, 'update_options' ) );
	}

	/**
	 * Test with mock services
	 */
	public function test_with_mock_services() {
		// 基本的なモックサービスでの初期化テスト
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();
		$mock_env = WP_Mock_Helper::create_mock_environment();
		$mock_options = WP_Mock_Helper::create_mock_options_service();
		
		// モックサービスで初期化できることを確認
		$service = new Settings_Service( $mock_hooks, $mock_env, $mock_options );
		$this->assertInstanceOf( Settings_Service::class, $service );
	}
} 