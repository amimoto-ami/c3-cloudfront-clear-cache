<?php
namespace C3_CloudFront_Cache_Controller\Test\AWS;
use C3_CloudFront_Cache_Controller\AWS;

class Invalidation_Batch_Test extends \WP_UnitTestCase {
    public function test_invalidation_path_items_empty_array() {
        $paths = [];
        $expected = ['/*'];
        
		$target = new AWS\Invalidation_Batch();
        foreach ( $paths as $key => $value ) {
            $target->put_invalidation_path( $value );
        }
        
		$this->assertEquals( $expected, $target->get_invalidation_path_items() );
	}
    
    public function test_invalidation_path_items_simple_array() {
        $paths = ['a', 'b', 'c'];
        $expected = $paths;
        
		$target = new AWS\Invalidation_Batch();
        foreach ( $paths as $key => $value ) {
            $target->put_invalidation_path( $value );
        }
        
		$this->assertEquals( $expected, $target->get_invalidation_path_items() );
	}
    
    public function test_invalidation_path_items_with_duplicates() {
        $paths = ['a', 'b', 'c', 'a', 'b', 'c'];
        $expected = ['a', 'b', 'c'];
        
		$target = new AWS\Invalidation_Batch();
        foreach ( $paths as $key => $value ) {
            $target->put_invalidation_path( $value );
        }
        
		$this->assertEquals( $expected, $target->get_invalidation_path_items() );
	}
    
    public function test_invalidation_path_items_ten_items() {
        $paths = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'];
        $expected = $paths;
        
		$target = new AWS\Invalidation_Batch();
        foreach ( $paths as $key => $value ) {
            $target->put_invalidation_path( $value );
        }
        
		$this->assertEquals( $expected, $target->get_invalidation_path_items() );
	}
    
    public function test_invalidation_path_items_over_ten_items() {
        $paths = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'];
        $expected = ['/*'];
        
		$target = new AWS\Invalidation_Batch();
        foreach ( $paths as $key => $value ) {
            $target->put_invalidation_path( $value );
        }
        
		$this->assertEquals( $expected, $target->get_invalidation_path_items() );
	}
    
    public function test_invalidation_path_items_ten_items_with_duplicate() {
        $paths = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'h'];
        $expected = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'];
        
		$target = new AWS\Invalidation_Batch();
        foreach ( $paths as $key => $value ) {
            $target->put_invalidation_path( $value );
        }
        
		$this->assertEquals( $expected, $target->get_invalidation_path_items() );
	}
}