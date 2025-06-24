<?php
/**
 * Invalidation Service Basic Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\Invalidation_Service;
use C3_CloudFront_Cache_Controller\Test\Helpers\WP_Mock_Helper;

/**
 * Invalidation Service Basic Test
 */
class Invalidation_Service_Basic_Test extends TestCase {

	public function tearDown(): void {
		\Mockery::close();
	}

	/**
	 * Test constructor initialization
	 */
	public function test_constructor_initialization() {
		$service = new Invalidation_Service();
		$this->assertInstanceOf( Invalidation_Service::class, $service );
	}

	/**
	 * Test core methods exist
	 */
	public function test_core_methods_exist() {
		$service = new Invalidation_Service();
		
		$this->assertTrue( method_exists( $service, 'invalidate_all' ) );
		$this->assertTrue( method_exists( $service, 'invalidate_posts_cache' ) );
		$this->assertTrue( method_exists( $service, 'invalidate_by_changing_post_status' ) );
	}

	/**
	 * Test with mocked dependencies
	 */
	public function test_with_mocked_dependencies() {
		// 基本モックサービス作成
		$mock_hooks = WP_Mock_Helper::create_mock_hooks_service();  
		$mock_cloudfront = WP_Mock_Helper::create_mock_cloudfront_service();
		$mock_post = WP_Mock_Helper::create_mock_post_service();
		$mock_transient = WP_Mock_Helper::create_mock_transient_service();
		
		// モック依存でサービス初期化
		$service = new Invalidation_Service(
			$mock_hooks,
			$mock_cloudfront,
			$mock_post,
			$mock_transient
		);
		
		$this->assertInstanceOf( Invalidation_Service::class, $service );
	}

	/**
	 * Test class properties exist
	 */
	public function test_class_properties_exist() {
		// クラスの基本プロパティが存在することを確認
		$reflection = new ReflectionClass( Invalidation_Service::class );
		$properties = $reflection->getProperties();
		
		$property_names = array_map( function( $prop ) {
			return $prop->getName();
		}, $properties );
		
		$this->assertContains( 'cf_service', $property_names );
		$this->assertContains( 'hook_service', $property_names );
	}

	/**
	 * Test invalidate_all returns expected type
	 */
	public function test_invalidate_all_return_type() {
		$service = new Invalidation_Service();
		
		// エラーなく呼び出せることを確認（実際の処理は環境依存）
		$result = $service->invalidate_all();
		
		// 戻り値がbooleanまたはWP_Errorであることを確認
		$this->assertTrue( 
			is_bool( $result ) || 
			is_wp_error( $result ) || 
			is_null( $result )
		);
	}
} 