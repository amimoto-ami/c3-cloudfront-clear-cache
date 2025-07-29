<?php
/**
 * Transient Service Basic Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\WP\Transient_Service;

/**
 * Transient Service Basic Test
 */
class Transient_Service_Basic_Test extends TestCase {

	/**
	 * Test constructor initialization
	 */
	public function test_constructor_initialization() {
		$service = new Transient_Service();
		$this->assertInstanceOf( Transient_Service::class, $service );
	}

	/**
	 * Test save method exists and callable
	 */
	public function test_save_method_exists() {
		$service = new Transient_Service();
		$this->assertTrue( method_exists( $service, 'save_invalidation_query' ) );
	}

	/**
	 * Test load method exists and callable
	 */
	public function test_load_method_exists() {
		$service = new Transient_Service();
		$this->assertTrue( method_exists( $service, 'load_invalidation_query' ) );
	}

	/**
	 * Test delete method exists and callable
	 */
	public function test_delete_method_exists() {
		$service = new Transient_Service();
		$this->assertTrue( method_exists( $service, 'delete_invalidation_query' ) );
	}

	/**
	 * Test query processing methods
	 */
	public function test_query_processing_methods() {
		$service = new Transient_Service();
		
		// クエリ処理メソッドが存在することを確認
		$this->assertTrue( method_exists( $service, 'query_normalize' ) );
		$this->assertTrue( method_exists( $service, 'merge_transient_invalidation_query' ) );
		$this->assertTrue( method_exists( $service, 'set_invalidation_query' ) );
	}

	/**
	 * Test basic query normalization
	 */
	public function test_basic_query_normalization() {
		$service = new Transient_Service();
		
		// 空のクエリを正規化
		$result = $service->query_normalize( null );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'Paths', $result );
		$this->assertArrayHasKey( 'Quantity', $result['Paths'] );
		$this->assertArrayHasKey( 'Items', $result['Paths'] );
		$this->assertEquals( 0, $result['Paths']['Quantity'] );
		$this->assertIsArray( $result['Paths']['Items'] );
	}
} 